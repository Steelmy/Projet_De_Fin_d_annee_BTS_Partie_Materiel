<?php

require_once __DIR__ . '/../Models/Reference.php';

class ReferenceController
{
    private Reference $model;
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->model = new Reference($conn);
        $this->conn = $conn;
    }

    public function tree(): void
    {
        try {
            $tree = $this->model->getTree();
            ApiResponse::success(['tree' => $tree]);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

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
}
