<?php

class Comment
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Récupère le commentaire lié à un objet via son id.
     * Retourne null si l'objet n'a pas de commentaire (id_com IS NULL).
     */
    public function getByObjetId(int $objetId): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT c.id, c.com_user, c.com_admin, c.created_at
            FROM commentaires c
            INNER JOIN objets o ON o.id_com = c.id
            WHERE o.id = :objet_id
        ");
        $stmt->execute([':objet_id' => $objetId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Crée un nouveau commentaire avec le texte admin.
     * Retourne l'id du commentaire créé.
     */
    public function create(string $comAdmin): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO commentaires (com_user, com_admin)
            VALUES ('', :com_admin)
        ");
        $stmt->execute([':com_admin' => $comAdmin]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Lie un commentaire à un objet via id_com.
     */
    public function linkToObjet(int $objetId, int $commentId): void
    {
        $stmt = $this->conn->prepare("
            UPDATE objets SET id_com = :comment_id WHERE id = :objet_id
        ");
        $stmt->execute([':comment_id' => $commentId, ':objet_id' => $objetId]);
    }

    /**
     * Met à jour le commentaire admin.
     */
    public function updateAdminComment(int $id, string $comAdmin): void
    {
        $stmt = $this->conn->prepare("
            UPDATE commentaires SET com_admin = :com_admin WHERE id = :id
        ");
        $stmt->execute([':com_admin' => $comAdmin, ':id' => $id]);
    }

    /**
     * Vide le commentaire élève (com_user → '').
     */
    public function clearUserComment(int $id): void
    {
        $stmt = $this->conn->prepare("
            UPDATE commentaires SET com_user = '' WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
    }

    /**
     * Vide le commentaire admin (com_admin → '').
     */
    public function clearAdminComment(int $id): void
    {
        $stmt = $this->conn->prepare("
            UPDATE commentaires SET com_admin = '' WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
    }

    /**
     * Récupère un commentaire par son id.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT id, com_user, com_admin FROM commentaires WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Délie un commentaire de son objet (id_com → NULL).
     */
    public function unlinkFromObjet(int $commentId): void
    {
        $stmt = $this->conn->prepare("
            UPDATE objets SET id_com = NULL WHERE id_com = :comment_id
        ");
        $stmt->execute([':comment_id' => $commentId]);
    }

    /**
     * Supprime une ligne de la table commentaires.
     */
    public function delete(int $id): void
    {
        $stmt = $this->conn->prepare("
            DELETE FROM commentaires WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
    }

    /**
     * Vérifie si com_user ET com_admin sont vides.
     * Si oui, délie l'objet et supprime la ligne commentaire.
     * Retourne true si la ligne a été supprimée.
     */
    public function cleanupIfEmpty(int $commentId): bool
    {
        $comment = $this->getById($commentId);
        if (!$comment) return false;

        $userEmpty = empty(trim($comment['com_user']));
        $adminEmpty = empty(trim($comment['com_admin']));

        if ($userEmpty && $adminEmpty) {
            $this->unlinkFromObjet($commentId);
            $this->delete($commentId);
            return true;
        }

        return false;
    }
}
