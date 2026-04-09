<?php

require_once __DIR__ . '/../Models/Comment.php';

class CommentController
{
    private Comment $model;

    public function __construct(PDO $conn)
    {
        $this->model = new Comment($conn);
    }

    /**
     * Récupère le commentaire d'un objet.
     * GET : ?objet_id=XX
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
     * POST : objet_id, com_admin, [comment_id] (si mise à jour)
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
                // Mise à jour d'un commentaire existant
                $this->model->updateAdminComment($commentId, $comAdmin);
                ApiResponse::success([], 'Commentaire modifié avec succès');
            } else {
                // Création d'un nouveau commentaire + liaison à l'objet
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
     * Supprime le commentaire élève (vide com_user).
     * Si com_admin est aussi vide après suppression, supprime la ligne et remet id_com à NULL.
     * POST : comment_id
     */
    public function deleteUserComment(): void
    {
        try {
            $commentId = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
            if ($commentId <= 0) {
                ApiResponse::error('ID commentaire requis');
            }

            $this->model->clearUserComment($commentId);

            // Vérifier si les deux champs sont vides → suppression complète
            $deleted = $this->model->cleanupIfEmpty($commentId);

            ApiResponse::success([
                'row_deleted' => $deleted
            ], 'Commentaire élève supprimé avec succès');
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    /**
     * Supprime le commentaire admin (vide com_admin).
     * Si com_user est aussi vide après suppression, supprime la ligne et remet id_com à NULL.
     * POST : comment_id
     */
    public function deleteAdminComment(): void
    {
        try {
            $commentId = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
            if ($commentId <= 0) {
                ApiResponse::error('ID commentaire requis');
            }

            $this->model->clearAdminComment($commentId);

            // Vérifier si les deux champs sont vides → suppression complète
            $deleted = $this->model->cleanupIfEmpty($commentId);

            ApiResponse::success([
                'row_deleted' => $deleted
            ], 'Commentaire admin supprimé avec succès');
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }
}
