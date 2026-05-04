<?php

class User
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getAll(): array
    {
        $stmt = $this->conn->prepare("SELECT id, Nom, Prénom FROM utilisateurs ORDER BY Nom, Prénom");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT id, Nom, Prénom FROM utilisateurs WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function search(string $query, int $limit): array
    {
        $sql = "SELECT id, Nom, Prénom FROM utilisateurs";
        $params = [];

        if (!empty($query)) {
            $sql .= " WHERE (Nom LIKE :q_start OR Prénom LIKE :q_start)";
            $params[':q_start'] = $query . '%';
        }
        $sql .= " ORDER BY Nom, Prénom LIMIT " . (int) $limit;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
