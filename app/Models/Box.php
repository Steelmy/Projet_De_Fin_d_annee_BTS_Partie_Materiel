<?php

/**
 * Modèle d'accès à la table `caisses` et gestion transactionnelle associée.
 */
class Box
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
     * Retourne toutes les caisses avec les infos d'utilisateur emprunteur.
     *
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * Récupère une caisse complète (avec utilisateur joint) par identifiant.
     *
     * @param int $id Identifiant caisse.
     * @return array<string, mixed>|null Caisse trouvée ou null.
     */
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

    /**
     * Récupère une caisse complète (avec utilisateur joint) par son nom.
     *
     * @param string $nom Nom de la caisse.
     * @return array<string, mixed>|null Caisse trouvée ou null.
     */
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

    /**
     * Variante allégée de getById utilisée pour les contrôles d'existence.
     *
     * @param int $id Identifiant caisse.
     * @return array{id:int, Nom:string, Etat:string}|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT id, Nom, Etat FROM caisses WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Variante allégée de getByName utilisée pour les contrôles d'existence.
     *
     * @param string $nom Nom de la caisse.
     * @return array{id:int, Nom:string, Etat:string}|null
     */
    public function findByName(string $nom): ?array
    {
        $stmt = $this->conn->prepare("SELECT id, Nom, Etat FROM caisses WHERE Nom = :nom");
        $stmt->execute([':nom' => $nom]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Recherche par préfixe sur le nom (autocomplete).
     *
     * @param string $query Préfixe de recherche (chaîne vide = aucun filtre).
     * @param int $limit Nombre maximum de résultats.
     * @return array<int, array{id:int, Nom:string, Etat:string}>
     */
    public function search(string $query, int $limit): array
    {
        $sql = "SELECT id, Nom, Etat FROM caisses";
        $params = [];

        if (!empty($query)) {
            $sql .= " WHERE Nom LIKE :q_start";
            $params[':q_start'] = $query . '%';
        }
        $sql .= " ORDER BY Nom LIMIT " . (int) $limit;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Indique si une caisse portant ce nom existe déjà.
     *
     * @param string $nom Nom à tester.
     * @return bool true si une caisse de ce nom existe.
     */
    public function nameExists(string $nom): bool
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM caisses WHERE Nom = :nom");
        $stmt->execute([':nom' => $nom]);
        return $stmt->fetch()['count'] > 0;
    }

    /**
     * Crée une nouvelle caisse à l'état `disponible`.
     *
     * @param string $nom Nom de la caisse à créer.
     * @return int Identifiant de la caisse créée.
     */
    public function create(string $nom): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO caisses (Nom, Etat, Emprunteur_id)
            VALUES (:nom, 'disponible', NULL)
        ");
        $stmt->execute([':nom' => $nom]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Met à jour les colonnes fournies sur une caisse.
     *
     * @param int $id Identifiant caisse.
     * @param array<string, mixed> $fields Map colonne → valeur (null force `column = NULL`).
     * @return void
     */
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

    /**
     * Supprime une caisse par identifiant.
     *
     * @param int $id Identifiant caisse à supprimer.
     * @return void
     */
    public function delete(int $id): void
    {
        $stmt = $this->conn->prepare("DELETE FROM caisses WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    /**
     * Démarre une transaction PDO sur la connexion partagée.
     */
    public function beginTransaction(): void
    {
        $this->conn->beginTransaction();
    }

    /**
     * Valide la transaction PDO en cours.
     */
    public function commit(): void
    {
        $this->conn->commit();
    }

    /**
     * Annule la transaction PDO en cours si une transaction est active.
     */
    public function rollBack(): void
    {
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
    }
}
