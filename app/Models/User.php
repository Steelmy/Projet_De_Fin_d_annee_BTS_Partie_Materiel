<?php

/**
 * Modèle d'accès à la table `utilisateurs`.
 */
class User
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
     * Retourne tous les utilisateurs triés par Nom puis Prénom.
     *
     * @return array<int, array{id:int, Nom:string, Prénom:string}>
     */
    public function getAll(): array
    {
        $stmt = $this->conn->prepare("SELECT id, Nom, Prénom FROM utilisateurs ORDER BY Nom, Prénom");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Récupère un utilisateur par son identifiant.
     *
     * @param int $id Identifiant utilisateur.
     * @return array{id:int, Nom:string, Prénom:string}|null Utilisateur trouvé ou null.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT id, Nom, Prénom FROM utilisateurs WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Recherche par préfixe sur Nom ou Prénom (autocomplete).
     *
     * @param string $query Préfixe de recherche (chaîne vide = aucun filtre).
     * @param int $limit Nombre maximum de résultats.
     * @return array<int, array{id:int, Nom:string, Prénom:string}>
     */
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
