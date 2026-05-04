<?php

/**
 * Modèle d'accès à la table `commentaires` et liaison avec `objets.id_com`.
 */
class Comment
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
     * Récupère le commentaire lié à un objet via `objets.id_com`.
     *
     * @param int $objetId Identifiant de l'objet.
     * @return array{id:int, com_user:string, com_admin:string, created_at:string}|null Commentaire ou null si l'objet n'en a pas.
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
     * Crée une ligne `commentaires` avec un texte admin (com_user vide).
     *
     * @param string $comAdmin Texte du commentaire admin.
     * @return int Identifiant du commentaire créé.
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
     * Lie un commentaire à un objet en renseignant `objets.id_com`.
     *
     * @param int $objetId Identifiant de l'objet.
     * @param int $commentId Identifiant du commentaire.
     * @return void
     */
    public function linkToObjet(int $objetId, int $commentId): void
    {
        $stmt = $this->conn->prepare("
            UPDATE objets SET id_com = :comment_id WHERE id = :objet_id
        ");
        $stmt->execute([':comment_id' => $commentId, ':objet_id' => $objetId]);
    }

    /**
     * Met à jour le texte du commentaire admin.
     *
     * @param int $id Identifiant commentaire.
     * @param string $comAdmin Nouveau texte admin.
     * @return void
     */
    public function updateAdminComment(int $id, string $comAdmin): void
    {
        $stmt = $this->conn->prepare("
            UPDATE commentaires SET com_admin = :com_admin WHERE id = :id
        ");
        $stmt->execute([':com_admin' => $comAdmin, ':id' => $id]);
    }

    /**
     * Vide le texte du commentaire élève (com_user → '').
     *
     * @param int $id Identifiant commentaire.
     * @return void
     */
    public function clearUserComment(int $id): void
    {
        $stmt = $this->conn->prepare("
            UPDATE commentaires SET com_user = '' WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
    }

    /**
     * Vide le texte du commentaire admin (com_admin → '').
     *
     * @param int $id Identifiant commentaire.
     * @return void
     */
    public function clearAdminComment(int $id): void
    {
        $stmt = $this->conn->prepare("
            UPDATE commentaires SET com_admin = '' WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
    }

    /**
     * Récupère un commentaire par son identifiant.
     *
     * @param int $id Identifiant commentaire.
     * @return array{id:int, com_user:string, com_admin:string}|null Commentaire ou null.
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
     * Délie un commentaire de tous les objets (objets.id_com → NULL).
     *
     * @param int $commentId Identifiant commentaire.
     * @return void
     */
    public function unlinkFromObjet(int $commentId): void
    {
        $stmt = $this->conn->prepare("
            UPDATE objets SET id_com = NULL WHERE id_com = :comment_id
        ");
        $stmt->execute([':comment_id' => $commentId]);
    }

    /**
     * Supprime une ligne `commentaires` par identifiant.
     *
     * @param int $id Identifiant commentaire.
     * @return void
     */
    public function delete(int $id): void
    {
        $stmt = $this->conn->prepare("
            DELETE FROM commentaires WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
    }

    /**
     * Supprime le commentaire si com_user et com_admin sont tous deux vides
     * et délie l'objet associé.
     *
     * @param int $commentId Identifiant commentaire à inspecter.
     * @return bool true si la ligne a été supprimée, false sinon.
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
