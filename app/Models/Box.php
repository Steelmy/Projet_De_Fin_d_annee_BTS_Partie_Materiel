<?php

class Box
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getAll(): array
    {
        $stmt = $this->conn->prepare("
            SELECT c.id, c.Nom, c.Etat, c.created_at, c.updated_at, c.Emprunteur_id,
                   u.Prénom, u.Nom AS Nom_utilisateur
            FROM caisses c
            LEFT JOIN utilisateurs u ON c.Emprunteur_id = u.id
            ORDER BY c.Nom
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT c.id, c.Nom, c.Etat, c.created_at, c.updated_at, c.Emprunteur_id,
                   u.Prénom, u.Nom as user_nom
            FROM caisses c
            LEFT JOIN utilisateurs u ON c.Emprunteur_id = u.id
            WHERE c.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getByName(string $nom): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT c.id, c.Nom, c.Etat, c.created_at, c.updated_at, c.Emprunteur_id,
                   u.Prénom, u.Nom as user_nom
            FROM caisses c
            LEFT JOIN utilisateurs u ON c.Emprunteur_id = u.id
            WHERE c.Nom = :nom
        ");
        $stmt->execute([':nom' => $nom]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT id, Nom, Etat FROM caisses WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByName(string $nom): ?array
    {
        $stmt = $this->conn->prepare("SELECT id, Nom, Etat FROM caisses WHERE Nom = :nom");
        $stmt->execute([':nom' => $nom]);
        return $stmt->fetch() ?: null;
    }

    public function nameExists(string $nom): bool
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM caisses WHERE Nom = :nom");
        $stmt->execute([':nom' => $nom]);
        return $stmt->fetch()['count'] > 0;
    }

    public function create(string $nom): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO caisses (Nom, Etat, Emprunteur_id)
            VALUES (:nom, 'disponible', NULL)
        ");
        $stmt->execute([':nom' => $nom]);
        return (int) $this->conn->lastInsertId();
    }

    public function update(int $id, array $fields): void
    {
        if (empty($fields)) {
            return;
        }

        $updates = [];
        $params = [':id' => $id];

        foreach ($fields as $column => $value) {
            if ($value === null) {
                $updates[] = "$column = NULL";
            } else {
                $placeholder = ':' . str_replace('.', '_', $column);
                $updates[] = "$column = $placeholder";
                $params[$placeholder] = $value;
            }
        }

        $sql = "UPDATE caisses SET " . implode(", ", $updates) . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->conn->prepare("DELETE FROM caisses WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function beginTransaction(): void
    {
        $this->conn->beginTransaction();
    }

    public function commit(): void
    {
        $this->conn->commit();
    }

    public function rollBack(): void
    {
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
    }
}
