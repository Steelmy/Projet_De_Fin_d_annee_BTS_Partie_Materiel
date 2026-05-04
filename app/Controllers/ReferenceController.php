<?php

require_once __DIR__ . '/../Models/Reference.php';

/**
 * Contrôleur du catalogue de références (types/sous-types/noms).
 *
 * Les opérations create/update encadrent l'appel modèle d'une transaction
 * pour garantir l'atomicité de la cascade Type → Sous-type → Nom.
 */
class ReferenceController
{
    /** @var Reference Modèle d'accès au catalogue. */
    private Reference $model;

    /** @var PDO Connexion PDO active (utilisée pour les transactions). */
    private PDO $conn;

    /**
     * @param PDO $conn Connexion PDO active.
     */
    public function __construct(PDO $conn)
    {
        $this->model = new Reference($conn);
        $this->conn = $conn;
    }

    /**
     * Renvoie l'arbre complet du catalogue (Type → Sous-type → liste de noms).
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function tree(): void
    {
        try {
            $tree = $this->model->getTree();
            ApiResponse::success(['tree' => $tree]);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    /**
     * Ajoute une référence au catalogue (find-or-create en cascade).
     * Méthode HTTP : POST. Entrée JSON : `type`, `sous_type`, `nom`.
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function store(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                ApiResponse::error('Méthode non autorisée', 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            $type = isset($data['type']) ? trim($data['type']) : '';
            $sousType = isset($data['sous_type']) ? trim($data['sous_type']) : '';
            $nom = isset($data['nom']) ? trim($data['nom']) : '';

            if (empty($type) || empty($nom)) {
                ApiResponse::error('Le type et le nom sont requis');
            }

            $type = mb_convert_case($type, MB_CASE_TITLE, "UTF-8");
            if (!empty($sousType)) {
                $sousType = mb_convert_case($sousType, MB_CASE_TITLE, "UTF-8");
            }
            $nom = mb_convert_case($nom, MB_CASE_TITLE, "UTF-8");

            $this->conn->beginTransaction();

            try {
                $inserted = $this->model->create($type, $sousType, $nom);

                $message = $inserted
                    ? 'Référence ajoutée avec succès au catalogue.'
                    : 'Cette combinaison de références existe déjà dans le catalogue.';

                $this->conn->commit();

                ApiResponse::success([
                    'message' => $message,
                    'inserted' => $inserted,
                    'reference' => [
                        'type' => $type,
                        'sous_type' => $sousType,
                        'nom' => $nom
                    ]
                ]);
            } catch (Exception $e) {
                $this->conn->rollBack();
                throw $e;
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                ApiResponse::error('Cette référence existe déjà', 409);
            }
            error_log("Database Error in addReference: " . $e->getMessage());
            ApiResponse::error('Erreur base de données lors de l\'ajout de la référence');
        } catch (Exception $e) {
            error_log("Error in addReference: " . $e->getMessage());
            ApiResponse::error('Erreur serveur lors de l\'ajout');
        }
    }

    /**
     * Renvoie la liste plate des références (id, Type, Sous_type, Nom).
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function getAll(): void
    {
        try {
            $references = $this->model->getAllFlat();
            ApiResponse::success(['references' => $references]);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    /**
     * Supprime une liste de références (et nettoie en cascade les sous-types/types vides).
     * Méthode HTTP : POST. Entrée JSON : `ids` (liste d'identifiants `noms_references`).
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function delete(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                ApiResponse::error('Méthode non autorisée', 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $ids = isset($data['ids']) ? $data['ids'] : [];

            if (empty($ids) || !is_array($ids)) {
                ApiResponse::error('Aucune référence sélectionnée pour la suppression.', 400);
            }

            $result = $this->model->delete($ids);

            ApiResponse::success([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Références supprimées avec succès.' : 'Erreur lors de la suppression de certaines références.',
                'deleted_count' => $result['deleted'],
                'errors' => $result['errors']
            ]);

        } catch (PDOException $e) {
            error_log("Database Error in deleteReference: " . $e->getMessage());
            ApiResponse::error('Erreur base de données lors de la suppression');
        } catch (Exception $e) {
            error_log("Error in deleteReference: " . $e->getMessage());
            ApiResponse::error('Erreur serveur lors de la suppression');
        }
    }

    /**
     * Met à jour une référence (rattachement Type/Sous-type, renommage)
     * et propage le nouveau nom sur les objets liés.
     * Méthode HTTP : POST ou PUT. Entrée JSON : `id`, `type`, `sous_type`, `nom`.
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function update(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
                ApiResponse::error('Méthode non autorisée', 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            $id = isset($data['id']) ? intval($data['id']) : 0;
            $type = isset($data['type']) ? trim($data['type']) : '';
            $sousType = isset($data['sous_type']) ? trim($data['sous_type']) : '';
            $nom = isset($data['nom']) ? trim($data['nom']) : '';

            if (empty($id) || empty($type) || empty($nom)) {
                ApiResponse::error('L\'ID, le type et le nom sont requis');
            }

            $type = mb_convert_case($type, MB_CASE_TITLE, "UTF-8");
            if (!empty($sousType)) {
                $sousType = mb_convert_case($sousType, MB_CASE_TITLE, "UTF-8");
            }
            $nom = mb_convert_case($nom, MB_CASE_TITLE, "UTF-8");

            $this->conn->beginTransaction();

            try {
                $updated = $this->model->update($id, $type, $sousType, $nom);

                if ($updated) {
                    $this->conn->commit();
                    ApiResponse::success([
                        'message' => 'Référence modifiée avec succès.'
                    ]);
                } else {
                    $this->conn->rollBack();
                    ApiResponse::error('Cette combinaison de références existe déjà dans le catalogue.', 409);
                }
            } catch (Exception $e) {
                $this->conn->rollBack();
                throw $e;
            }
        } catch (PDOException $e) {
            error_log("Database Error in updateReference: " . $e->getMessage());
            ApiResponse::error('Erreur base de données lors de la modification de la référence');
        } catch (Exception $e) {
            error_log("Error in updateReference: " . $e->getMessage());
            ApiResponse::error('Erreur serveur lors de la modification');
        }
    }
}
