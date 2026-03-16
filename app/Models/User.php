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
}
