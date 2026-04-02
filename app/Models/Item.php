<?php

class Item
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getAll(): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                o.id, o.Code_bar, o.Etat,
                t.nom_type AS Type, st.nom_sous_type AS Sous_type, nr.nom_reference AS Nom,
                u.Prénom, u.Nom AS Nom_utilisateur,
                c.Nom AS Nom_caisse,
                o.created_at
            FROM objets o
            LEFT JOIN noms_references nr ON o.id_nom_reference = nr.id
            LEFT JOIN sous_types st ON nr.id_sous_type = st.id
            LEFT JOIN types t ON st.id_type = t.id
            LEFT JOIN utilisateurs u ON o.Emprunteur_id = u.id
            LEFT JOIN caisses c ON o.Caisse_id = c.id
            ORDER BY t.nom_type, st.nom_sous_type, nr.nom_reference
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByBarcode(string $codeBarre): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT
                o.id, o.Code_bar, o.Etat,
                t.nom_type AS Type, st.nom_sous_type AS Sous_type, nr.nom_reference AS Nom,
                o.Emprunteur_id, o.Caisse_id,
                o.created_at, o.updated_at,
                u.Nom as user_nom, u.Prénom as user_prenom,
                c.Nom as caisse_nom
            FROM objets o
            LEFT JOIN noms_references nr ON o.id_nom_reference = nr.id
            LEFT JOIN sous_types st ON nr.id_sous_type = st.id
            LEFT JOIN types t ON st.id_type = t.id
            LEFT JOIN utilisateurs u ON o.Emprunteur_id = u.id
            LEFT JOIN caisses c ON o.Caisse_id = c.id
            WHERE o.Code_bar = :code_barre
        ");
        $stmt->execute([':code_barre' => $codeBarre]);
        return $stmt->fetch() ?: null;
    }

    public function getByTypeAndName(string $type, string $nom): array
    {
        $stmt = $this->conn->prepare("
            SELECT o.id, o.Code_bar, o.Etat, o.Emprunteur_id
            FROM objets o
            JOIN noms_references nr ON o.id_nom_reference = nr.id
            JOIN sous_types st ON nr.id_sous_type = st.id
            JOIN types t ON st.id_type = t.id
            WHERE t.nom_type = :type AND nr.nom_reference = :nom
            ORDER BY o.Code_bar
        ");
        $stmt->execute([':type' => $type, ':nom' => $nom]);
        return $stmt->fetchAll();
    }

    public function getAvailable(): array
    {
        $stmt = $this->conn->prepare("
            SELECT o.id, o.Code_bar, t.nom_type AS Type, st.nom_sous_type AS Sous_type, nr.nom_reference AS Nom, o.Etat
            FROM objets o
            LEFT JOIN noms_references nr ON o.id_nom_reference = nr.id
            LEFT JOIN sous_types st ON nr.id_sous_type = st.id
            LEFT JOIN types t ON st.id_type = t.id
            WHERE o.Etat = 'disponible' AND o.Caisse_id IS NULL
            ORDER BY t.nom_type, st.nom_sous_type, nr.nom_reference
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByCaisseId(int $caisseId): array
    {
        $stmt = $this->conn->prepare("
            SELECT o.id, o.Code_bar, t.nom_type AS Type, st.nom_sous_type AS Sous_type, nr.nom_reference AS Nom, o.Etat
            FROM objets o
            LEFT JOIN noms_references nr ON o.id_nom_reference = nr.id
            LEFT JOIN sous_types st ON nr.id_sous_type = st.id
            LEFT JOIN types t ON st.id_type = t.id
            WHERE o.Caisse_id = ?
            ORDER BY t.nom_type, st.nom_sous_type, nr.nom_reference
        ");
        $stmt->execute([$caisseId]);
        return $stmt->fetchAll();
    }

    public function barcodeExists(string $codeBarre): bool
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM objets WHERE Code_bar = :code");
        $stmt->execute([':code' => $codeBarre]);
        return $stmt->fetchColumn() > 0;
    }

    public function create(string $type, string $sousType, string $nom): array
    {
        // 1. Chercher ou créer Type
        $stmtType = $this->conn->prepare("SELECT id FROM types WHERE nom_type = ?");
        $stmtType->execute([$type]);
        $typeId = $stmtType->fetchColumn();
        if (!$typeId) {
            $this->conn->prepare("INSERT INTO types (nom_type) VALUES (?)")->execute([$type]);
            $typeId = (int) $this->conn->lastInsertId();
        }

        // 2. Chercher ou créer Sous_type
        if (empty($sousType)) $sousType = 'Non défini';
        $stmtSousType = $this->conn->prepare("SELECT id FROM sous_types WHERE nom_sous_type = ? AND id_type = ?");
        $stmtSousType->execute([$sousType, $typeId]);
        $sousTypeId = $stmtSousType->fetchColumn();
        if (!$sousTypeId) {
            $this->conn->prepare("INSERT INTO sous_types (nom_sous_type, id_type) VALUES (?, ?)")->execute([$sousType, $typeId]);
            $sousTypeId = (int) $this->conn->lastInsertId();
        }

        // 3. Chercher ou créer Nom_reference
        $stmtNom = $this->conn->prepare("SELECT id FROM noms_references WHERE nom_reference = ? AND id_sous_type = ?");
        $stmtNom->execute([$nom, $sousTypeId]);
        $nomRefId = $stmtNom->fetchColumn();
        if (!$nomRefId) {
            $this->conn->prepare("INSERT INTO noms_references (nom_reference, id_sous_type) VALUES (?, ?)")->execute([$nom, $sousTypeId]);
            $nomRefId = (int) $this->conn->lastInsertId();
        }

        // 4. Insérer l'objet avec un code-barre temporaire
        $stmt = $this->conn->prepare("
            INSERT INTO objets (id_nom_reference, Nom, Etat, Emprunteur_id, Code_bar)
            VALUES (:id_nom_reference, :nom, 'disponible', NULL, 'TEMP')
        ");
        $stmt->execute([':id_nom_reference' => $nomRefId, ':nom' => $nom]);
        $objetId = (int) $this->conn->lastInsertId();

        // 5. Générer le code EAN-13 à partir des IDs
        $codeEAN = $this->generateEAN13((int)$typeId, (int)$sousTypeId, (int)$nomRefId, $objetId);

        // 6. Mettre à jour l'objet avec le vrai code-barre
        $stmtUpdate = $this->conn->prepare("UPDATE objets SET Code_bar = ? WHERE id = ?");
        $stmtUpdate->execute([$codeEAN, $objetId]);

        return ['id' => $objetId, 'code_bar' => $codeEAN];
    }

    public function updateState(string $codeBarre, string $etat, ?int $emprunteurId): void
    {
        $stmt = $this->conn->prepare("
            UPDATE objets
            SET Etat = :etat, Emprunteur_id = :emprunteur_id
            WHERE Code_bar = :code_barre
        ");
        $stmt->execute([
            ':code_barre' => $codeBarre,
            ':etat' => $etat,
            ':emprunteur_id' => $emprunteurId
        ]);
    }

    public function delete(string $codeBarre): void
    {
        $stmt = $this->conn->prepare("DELETE FROM objets WHERE Code_bar = :code_barre");
        $stmt->execute([':code_barre' => $codeBarre]);
    }

    public function findByBarcode(string $codeBarre): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT o.id, t.nom_type AS Type, nr.nom_reference AS Nom, o.Code_bar, o.Etat, o.Caisse_id, st.nom_sous_type AS Sous_type
            FROM objets o
            LEFT JOIN noms_references nr ON o.id_nom_reference = nr.id
            LEFT JOIN sous_types st ON nr.id_sous_type = st.id
            LEFT JOIN types t ON st.id_type = t.id
            WHERE o.Code_bar = :code_barre
        ");
        $stmt->execute([':code_barre' => $codeBarre]);
        return $stmt->fetch() ?: null;
    }

    public function assignToBox(int $objetId, int $caisseId): void
    {
        $stmt = $this->conn->prepare("
            UPDATE objets
            SET Caisse_id = :caisse_id, Etat = 'réservé'
            WHERE id = :objet_id AND Caisse_id IS NULL
        ");
        $stmt->execute([':caisse_id' => $caisseId, ':objet_id' => $objetId]);
    }

    public function freeFromBox(int $caisseId): void
    {
        $stmt = $this->conn->prepare("
            UPDATE objets SET Caisse_id = NULL, Etat = 'disponible' WHERE Caisse_id = :caisse_id
        ");
        $stmt->execute([':caisse_id' => $caisseId]);
    }

    public function setBoxState(int $caisseId, string $etat): void
    {
        $stmt = $this->conn->prepare("
            UPDATE objets SET Etat = 'disponible' WHERE Caisse_id = :caisse_id
        ");
        $stmt->execute([':caisse_id' => $caisseId]);
    }

    /**
     * Génère un code-barres EAN-13 valide à partir des IDs catégoriels.
     * Structure : 2 (usage interne) + id_type (2) + id_sous_type (2) + id_nom (3) + id_objet (4) + clé contrôle (1) = 13
     */
    public function generateEAN13(int $idType, int $idSousType, int $idNom, int $idObjet): string
    {
        $code12 = '2'
            . str_pad($idType, 2, '0', STR_PAD_LEFT)
            . str_pad($idSousType, 2, '0', STR_PAD_LEFT)
            . str_pad($idNom, 3, '0', STR_PAD_LEFT)
            . str_pad($idObjet, 4, '0', STR_PAD_LEFT);

        // Calcul de la clé de contrôle EAN-13
        $somme = 0;
        for ($i = 0; $i < 12; $i++) {
            $poids = ($i % 2 === 0) ? 1 : 3;
            $somme += intval($code12[$i]) * $poids;
        }
        $reste = $somme % 10;
        $cleControle = ($reste === 0) ? 0 : 10 - $reste;

        return $code12 . $cleControle;
    }

    /**
     * Récupère tous les codes-barres existants avec leurs infos catégorielles (pour le générateur/réimpression).
     */
    public function getAllBarcodes(?string $filterType = null, ?string $filterSousType = null, ?string $filterNom = null): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filterType)) {
            $conditions[] = "t.nom_type = :type";
            $params[':type'] = $filterType;
        }
        if (!empty($filterSousType)) {
            $conditions[] = "st.nom_sous_type = :sous_type";
            $params[':sous_type'] = $filterSousType;
        }
        if (!empty($filterNom)) {
            $conditions[] = "nr.nom_reference = :nom";
            $params[':nom'] = $filterNom;
        }

        $sql = "
            SELECT o.Code_bar, t.nom_type AS Type, st.nom_sous_type AS Sous_type, nr.nom_reference AS Nom, o.created_at
            FROM objets o
            LEFT JOIN noms_references nr ON o.id_nom_reference = nr.id
            LEFT JOIN sous_types st ON nr.id_sous_type = st.id
            LEFT JOIN types t ON st.id_type = t.id
        ";

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY t.nom_type, st.nom_sous_type, nr.nom_reference, o.Code_bar";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
