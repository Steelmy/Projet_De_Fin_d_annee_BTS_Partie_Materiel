<?php

require_once __DIR__ . '/../Models/Comment.php';

/**
 * Contrôleur des commentaires admin/élève associés aux objets.
 */
class CommentController
{
    /** @var Comment Modèle d'accès aux commentaires. */
    private Comment $model;

    /**
     * @param PDO $conn Connexion PDO active.
     */
    public function __construct(PDO $conn)
    {
        $this->model = new Comment($conn);
    }

    /**
     * Récupère le commentaire associé à un objet.
     * Entrée GET : `objet_id`.
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function show(): void
    {
        try {
            $objetId = isset($_GET['objet_id']) ? intval($_GET['objet_id']) : 0;
            if ($objetId <= 0) {
                ApiResponse::error('ID objet requis');
            }

            $comment = $this->model->getByObjetId($objetId);

            ApiResponse::success([
                'comment' => $comment
            ]);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    /**
     * Crée ou met à jour le commentaire admin d'un objet.
     * Entrée POST : `objet_id`, `com_admin`, `comment_id` (optionnel : si fourni → update).
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function save(): void
    {
        try {
            $objetId = isset($_POST['objet_id']) ? intval($_POST['objet_id']) : 0;
            $comAdmin = isset($_POST['com_admin']) ? trim($_POST['com_admin']) : '';
            $commentId = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;

            if ($objetId <= 0) {
                ApiResponse::error('ID objet requis');
            }
            if (empty($comAdmin)) {
                ApiResponse::error('Le commentaire admin ne peut pas être vide');
            }

            if ($commentId > 0) {
                $this->model->updateAdminComment($commentId, $comAdmin);
                ApiResponse::success([], 'Commentaire modifié avec succès');
            } else {
                $newId = $this->model->create($comAdmin);
                $this->model->linkToObjet($objetId, $newId);
                ApiResponse::success([
                    'comment_id' => $newId
                ], 'Commentaire ajouté avec succès');
            }
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    /**
     * Vide le commentaire élève (com_user).
     * Si le commentaire admin est aussi vide, supprime la ligne et délie l'objet.
     * Entrée POST : `comment_id`.
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function deleteUserComment(): void
    {
        try {
            $commentId = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
            if ($commentId <= 0) {
                ApiResponse::error('ID commentaire requis');
            }

            $this->model->clearUserComment($commentId);
            $deleted = $this->model->cleanupIfEmpty($commentId);

            ApiResponse::success([
                'row_deleted' => $deleted
            ], 'Commentaire élève supprimé avec succès');
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    /**
     * Vide le commentaire admin (com_admin).
     * Si le commentaire élève est aussi vide, supprime la ligne et délie l'objet.
     * Entrée POST : `comment_id`.
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function deleteAdminComment(): void
    {
        try {
            $commentId = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
            if ($commentId <= 0) {
                ApiResponse::error('ID commentaire requis');
            }

            $this->model->clearAdminComment($commentId);
            $deleted = $this->model->cleanupIfEmpty($commentId);

            ApiResponse::success([
                'row_deleted' => $deleted
            ], 'Commentaire admin supprimé avec succès');
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }
}
