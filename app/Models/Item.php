<?php

/**
 * Modèle d'accès à la table `objets` (matériel) et opérations associées :
 * jointures avec types/sous-types/noms de références, génération de codes EAN-13,
 * gestion d'état (disponible / réservé / emprunté), affectation à une caisse,
 * mise à jour de l'historique de restitution.
 */
class Item
{
    /** @var PDO Connexion PDO active. */
    private PDO $conn;

    /**
     * @param PDO $conn Connexion PDO active.
     */
    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Liste tous les objets avec leurs métadonnées (type/sous-type/nom, utilisateur, caisse).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                o.id, o.Code_bar, o.Etat, o.id_com,
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

    /**
     * Récupère un objet complet (avec utilisateur emprunteur et caisse joints) par code-barres.
     *
     * @param string $codeBarre Code-barres EAN-13.
     * @return array<string, mixed>|null Objet trouvé ou null.
     */
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

    /**
     * Récupère les objets correspondant à un couple (type, nom de référence).
     *
     * @param string $type Nom du type.
     * @param string $nom Nom de la référence.
     * @return array<int, array{id:int, Code_bar:string, Etat:string, Emprunteur_id:?int}>
     */
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

    /**
     * Liste les objets actuellement disponibles et hors caisse.
     *
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * Liste les objets contenus dans une caisse donnée.
     *
     * @param int $caisseId Identifiant de la caisse.
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * Vérifie l'existence d'un objet portant le code-barres donné.
     *
     * @param string $codeBarre Code-barres EAN-13.
     * @return bool true si un objet existe pour ce code.
     */
    public function barcodeExists(string $codeBarre): bool
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM objets WHERE Code_bar = :code");
        $stmt->execute([':code' => $codeBarre]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Crée un nouvel objet en cascadant le find-or-create sur Type/Sous-type/Nom,
     * puis génère un code EAN-13 dérivé des identifiants catégoriels.
     *
     * @param string $type Nom du type.
     * @param string $sousType Nom du sous-type (vide → `Non défini`).
     * @param string $nom Nom de la référence.
     * @return array{id:int, code_bar:string} Identifiant et code-barres de l'objet créé.
     */
    public function create(string $type, string $sousType, string $nom): array
    {
        $stmtType = $this->conn->prepare("SELECT id FROM types WHERE nom_type = ?");
        $stmtType->execute([$type]);
        $typeId = $stmtType->fetchColumn();
        if (!$typeId) {
            $this->conn->prepare("INSERT INTO types (nom_type) VALUES (?)")->execute([$type]);
            $typeId = (int) $this->conn->lastInsertId();
        }

        if (empty($sousType)) $sousType = 'Non défini';
        $stmtSousType = $this->conn->prepare("SELECT id FROM sous_types WHERE nom_sous_type = ? AND id_type = ?");
        $stmtSousType->execute([$sousType, $typeId]);
        $sousTypeId = $stmtSousType->fetchColumn();
        if (!$sousTypeId) {
            $this->conn->prepare("INSERT INTO sous_types (nom_sous_type, id_type) VALUES (?, ?)")->execute([$sousType, $typeId]);
            $sousTypeId = (int) $this->conn->lastInsertId();
        }

        $stmtNom = $this->conn->prepare("SELECT id FROM noms_references WHERE nom_reference = ? AND id_sous_type = ?");
        $stmtNom->execute([$nom, $sousTypeId]);
        $nomRefId = $stmtNom->fetchColumn();
        if (!$nomRefId) {
            $this->conn->prepare("INSERT INTO noms_references (nom_reference, id_sous_type) VALUES (?, ?)")->execute([$nom, $sousTypeId]);
            $nomRefId = (int) $this->conn->lastInsertId();
        }

        // Insertion avec code-barres temporaire : l'EAN-13 final dépend de l'auto-increment
        $stmt = $this->conn->prepare("
            INSERT INTO objets (id_nom_reference, Nom, Etat, Emprunteur_id, Code_bar)
            VALUES (:id_nom_reference, :nom, 'disponible', NULL, 'TEMP')
        ");
        $stmt->execute([':id_nom_reference' => $nomRefId, ':nom' => $nom]);
        $objetId = (int) $this->conn->lastInsertId();

        $codeEAN = $this->generateEAN13((int)$typeId, (int)$sousTypeId, (int)$nomRefId, $objetId);

        $stmtUpdate = $this->conn->prepare("UPDATE objets SET Code_bar = ? WHERE id = ?");
        $stmtUpdate->execute([$codeEAN, $objetId]);

        return ['id' => $objetId, 'code_bar' => $codeEAN];
    }

    /**
     * Met à jour l'état et l'emprunteur d'un objet.
     *
     * @param string $codeBarre Code-barres de l'objet à modifier.
     * @param string $etat Nouvel état (`disponible`, `réservé`, `emprunté`).
     * @param int|null $emprunteurId Identifiant emprunteur, ou null pour l'état `disponible`.
     * @return void
     */
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

    /**
     * Supprime un objet par code-barres.
     *
     * @param string $codeBarre Code-barres de l'objet à supprimer.
     * @return void
     */
    public function delete(string $codeBarre): void
    {
        $stmt = $this->conn->prepare("DELETE FROM objets WHERE Code_bar = :code_barre");
        $stmt->execute([':code_barre' => $codeBarre]);
    }

    /**
     * Restitue un objet : remet l'état à `disponible`, libère l'emprunteur
     * et clôture la dernière entrée d'historique ouverte.
     *
     * @param string $codeBarre Code-barres de l'objet à restituer.
     * @return void
     */
    public function restitute(string $codeBarre): void
    {
        $stmtObj = $this->conn->prepare("SELECT id, Emprunteur_id FROM objets WHERE Code_bar = :code_barre");
        $stmtObj->execute([':code_barre' => $codeBarre]);
        $objet = $stmtObj->fetch();

        if ($objet) {
            $idMateriel = $objet['id'];

            $stmtHistUpdate = $this->conn->prepare("
                UPDATE historique
                SET Date_retour_reelle = NOW()
                WHERE id_materiel = :id_materiel
                AND Date_retour_reelle IS NULL
                ORDER BY id DESC LIMIT 1
            ");
            $stmtHistUpdate->execute([
                ':id_materiel' => $idMateriel
            ]);

            $stmt = $this->conn->prepare("
                UPDATE objets
                SET Etat = 'disponible', Emprunteur_id = NULL
                WHERE Code_bar = :code_barre
            ");
            $stmt->execute([':code_barre' => $codeBarre]);
        }
    }

    /**
     * Variante allégée de getByBarcode utilisée pour les contrôles préalables
     * (existence, état, présence en caisse).
     *
     * @param string $codeBarre Code-barres recherché.
     * @return array<string, mixed>|null Objet trouvé ou null.
     */
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

    /**
     * Affecte un objet à une caisse et le passe en état `réservé`.
     * L'opération n'a effet que si l'objet n'est dans aucune autre caisse.
     *
     * @param int $objetId Identifiant de l'objet.
     * @param int $caisseId Identifiant de la caisse cible.
     * @return void
     */
    public function assignToBox(int $objetId, int $caisseId): void
    {
        $stmt = $this->conn->prepare("
            UPDATE objets
            SET Caisse_id = :caisse_id, Etat = 'réservé'
            WHERE id = :objet_id AND Caisse_id IS NULL
        ");
        $stmt->execute([':caisse_id' => $caisseId, ':objet_id' => $objetId]);
    }

    /**
     * Retire tous les objets d'une caisse et les remet à l'état `disponible`.
     *
     * @param int $caisseId Identifiant de la caisse à vider.
     * @return void
     */
    public function freeFromBox(int $caisseId): void
    {
        $stmt = $this->conn->prepare("
            UPDATE objets SET Caisse_id = NULL, Etat = 'disponible' WHERE Caisse_id = :caisse_id
        ");
        $stmt->execute([':caisse_id' => $caisseId]);
    }

    /**
     * Force l'état des objets d'une caisse à `disponible`.
     *
     * @param int $caisseId Identifiant caisse.
     * @param string $etat État cible (paramètre conservé pour compatibilité, valeur appliquée : `disponible`).
     * @return void
     */
    public function setBoxState(int $caisseId, string $etat): void
    {
        $stmt = $this->conn->prepare("
            UPDATE objets SET Etat = 'disponible' WHERE Caisse_id = :caisse_id
        ");
        $stmt->execute([':caisse_id' => $caisseId]);
    }

    /**
     * Génère un code-barres EAN-13 valide à partir des identifiants catégoriels.
     *
     * Structure des 12 premiers chiffres :
     *   - 1 chiffre `2` (préfixe usage interne)
     *   - id_type sur 2 chiffres
     *   - id_sous_type sur 2 chiffres
     *   - id_nom_reference sur 3 chiffres
     *   - id_objet sur 4 chiffres
     * Le 13ᵉ chiffre est la clé de contrôle EAN-13.
     *
     * @param int $idType Identifiant du type.
     * @param int $idSousType Identifiant du sous-type.
     * @param int $idNom Identifiant du nom de référence.
     * @param int $idObjet Identifiant de l'objet.
     * @return string Code EAN-13 (13 chiffres).
     */
    public function generateEAN13(int $idType, int $idSousType, int $idNom, int $idObjet): string
    {
        $code12 = '2'
            . str_pad($idType, 2, '0', STR_PAD_LEFT)
            . str_pad($idSousType, 2, '0', STR_PAD_LEFT)
            . str_pad($idNom, 3, '0', STR_PAD_LEFT)
            . str_pad($idObjet, 4, '0', STR_PAD_LEFT);

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
     * Recherche d'objets par préfixe de code-barres avec filtres catégoriels (autocomplete).
     *
     * @param string $query Préfixe de code-barres.
     * @param int $limit Nombre maximum de résultats.
     * @param string|null $filterType Filtre nom de type.
     * @param string|null $filterSousType Filtre nom de sous-type.
     * @param string|null $filterNom Filtre nom de référence.
     * @return array<int, array<string, mixed>>
     */
    public function searchByCode(
        string $query,
        int $limit,
        ?string $filterType = null,
        ?string $filterSousType = null,
        ?string $filterNom = null
    ): array {
        $conditions = [];
        $params = [];

        if (!empty($query)) {
            $conditions[] = "o.Code_bar LIKE :q_start";
            $params[':q_start'] = $query . '%';
        }
        if (!empty($filterType)) {
            $conditions[] = "t.nom_type = :f_type";
            $params[':f_type'] = $filterType;
        }
        if (!empty($filterSousType)) {
            $conditions[] = "st.nom_sous_type = :f_sous_type";
            $params[':f_sous_type'] = $filterSousType;
        }
        if (!empty($filterNom)) {
            $conditions[] = "nr.nom_reference = :f_nom";
            $params[':f_nom'] = $filterNom;
        }

        $sql = "
            SELECT o.id, o.Code_bar, nr.nom_reference AS Nom, t.nom_type AS Type, st.nom_sous_type AS Sous_type
            FROM objets o
            LEFT JOIN noms_references nr ON o.id_nom_reference = nr.id
            LEFT JOIN sous_types st ON nr.id_sous_type = st.id
            LEFT JOIN types t ON st.id_type = t.id
        ";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY o.Code_bar LIMIT " . (int) $limit;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Recherche de codes-barres avec filtres étendus (état, disponibilité)
     * pour l'autocomplete code-barres.
     *
     * @param string $query Préfixe de code-barres (chaîne vide acceptée).
     * @param string|null $type Filtre nom de type.
     * @param string|null $sousType Filtre nom de sous-type.
     * @param string|null $nom Filtre nom de référence.
     * @param string|null $etat Filtre exact sur l'état.
     * @param bool $disponibleOnly Restreint aux objets disponibles et hors caisse.
     * @param bool $nonDisponibleOnly Restreint aux objets emprunté/réservé hors caisse.
     * @param int $limit Nombre maximum de résultats.
     * @return array<int, array<string, mixed>>
     */
    public function searchBarcodes(
        string $query,
        ?string $type,
        ?string $sousType,
        ?string $nom,
        ?string $etat,
        bool $disponibleOnly,
        bool $nonDisponibleOnly,
        int $limit
    ): array {
        $conditions = [];
        $params = [];

        if (!empty($query)) {
            $conditions[] = "o.Code_bar LIKE :query";
            $params[':query'] = $query . '%';
        }
        if (!empty($type)) {
            $conditions[] = "t.nom_type = :type";
            $params[':type'] = $type;
        }
        if (!empty($sousType)) {
            $conditions[] = "st.nom_sous_type = :sous_type";
            $params[':sous_type'] = $sousType;
        }
        if (!empty($nom)) {
            $conditions[] = "nr.nom_reference = :nom";
            $params[':nom'] = $nom;
        }
        if (!empty($etat)) {
            $conditions[] = "o.Etat = :etat";
            $params[':etat'] = $etat;
        }
        if ($disponibleOnly) {
            $conditions[] = "o.Etat = 'disponible'";
            $conditions[] = "o.Caisse_id IS NULL";
        }
        if ($nonDisponibleOnly) {
            $conditions[] = "o.Etat IN ('emprunté', 'réservé')";
            $conditions[] = "o.Caisse_id IS NULL";
        }

        $sql = "
            SELECT o.Code_bar, t.nom_type AS Type, st.nom_sous_type AS Sous_type, nr.nom_reference AS Nom
            FROM objets o
            LEFT JOIN noms_references nr ON o.id_nom_reference = nr.id
            LEFT JOIN sous_types st ON nr.id_sous_type = st.id
            LEFT JOIN types t ON st.id_type = t.id
        ";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY o.Code_bar LIMIT " . (int) $limit;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Liste tous les codes-barres existants avec leurs infos catégorielles
     * (utilisé par le générateur pour la réimpression).
     *
     * @param string|null $filterType Filtre nom de type.
     * @param string|null $filterSousType Filtre nom de sous-type.
     * @param string|null $filterNom Filtre nom de référence.
     * @return array<int, array<string, mixed>>
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
