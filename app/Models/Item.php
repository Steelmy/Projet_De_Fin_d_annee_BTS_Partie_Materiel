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
                o.id, o.Code_bar, o.Type, o.Sous_type, o.Nom, o.Etat,
                u.Prénom, u.Nom AS Nom_utilisateur,
                c.Nom AS Nom_caisse
            FROM objets o
            LEFT JOIN utilisateurs u ON o.Emprunteur_id = u.id
            LEFT JOIN caisses c ON o.Caisse_id = c.id
            ORDER BY o.Type, o.Sous_type, o.Nom
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByBarcode(string $codeBarre): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT
                o.id, o.Code_bar, o.Type, o.Sous_type, o.Nom, o.Etat,
                o.Emprunteur_id, o.Caisse_id,
                o.created_at, o.updated_at,
                u.Nom as user_nom, u.Prénom as user_prenom,
                c.Nom as caisse_nom
            FROM objets o
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
            SELECT id, Code_bar, Etat, Emprunteur_id
            FROM objets
            WHERE Type = :type AND Nom = :nom
            ORDER BY Code_bar
        ");
        $stmt->execute([':type' => $type, ':nom' => $nom]);
        return $stmt->fetchAll();
    }

    public function getAvailable(): array
    {
        $stmt = $this->conn->prepare("
            SELECT id, Code_bar, Type, Sous_type, Nom, Etat
            FROM objets
            WHERE Etat = 'disponible' AND Caisse_id IS NULL
            ORDER BY Type, Sous_type, Nom
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByCaisseId(int $caisseId): array
    {
        $stmt = $this->conn->prepare("
            SELECT id, Code_bar, Type, Sous_type, Nom, Etat
            FROM objets
            WHERE Caisse_id = ?
            ORDER BY Type, Sous_type, Nom
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

    public function create(string $type, string $sousType, string $nom, string $codeBarre): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO objets (Type, Sous_type, Nom, Etat, Emprunteur_id, Code_bar)
            VALUES (:type, :sous_type, :nom, 'disponible', NULL, :code_barre)
        ");
        $stmt->execute([
            ':type' => $type,
            ':sous_type' => $sousType,
            ':nom' => $nom,
            ':code_barre' => $codeBarre
        ]);
        return (int) $this->conn->lastInsertId();
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
            SELECT id, Type, Nom, Code_bar, Etat, Caisse_id, Sous_type
            FROM objets WHERE Code_bar = :code_barre
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

    public function generateUniqueBarcode(int $length = 13, int $maxAttempts = 100): ?string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $barcode = '';
            for ($i = 0; $i < $length; $i++) {
                $barcode .= mt_rand(0, 9);
            }
            if (!$this->barcodeExists($barcode)) {
                return $barcode;
            }
        }
        return null;
    }
}
