<?php

require_once __DIR__ . '/../Models/Item.php';

/**
 * Contrôleur des opérations sur les codes-barres
 * (vérification d'unicité, listing pour le générateur).
 */
class BarcodeController
{
    /** @var Item Modèle d'accès aux objets. */
    private Item $model;

    /**
     * @param PDO $conn Connexion PDO active.
     */
    public function __construct(PDO $conn)
    {
        $this->model = new Item($conn);
    }

    /**
     * Vérifie si un code-barres est déjà utilisé en base.
     * Entrée GET : `code_barre`.
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function check(): void
    {
        try {
            $codeBarre = isset($_GET['code_barre']) ? trim($_GET['code_barre']) : '';
            if (empty($codeBarre)) {
                ApiResponse::error('Code-barre requis');
            }

            ApiResponse::success([
                'exists' => $this->model->barcodeExists($codeBarre),
                'code_barre' => $codeBarre
            ]);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    /**
     * Liste les codes-barres existants en BDD avec filtrage optionnel
     * (utilisé par le générateur pour la réimpression).
     * Entrée GET : `type`, `sous_type`, `nom` (tous optionnels).
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function listAll(): void
    {
        try {
            $filterType = isset($_GET['type']) ? trim($_GET['type']) : null;
            $filterSousType = isset($_GET['sous_type']) ? trim($_GET['sous_type']) : null;
            $filterNom = isset($_GET['nom']) ? trim($_GET['nom']) : null;

            $barcodes = $this->model->getAllBarcodes($filterType, $filterSousType, $filterNom);

            ApiResponse::success([
                'barcodes' => $barcodes,
                'count' => count($barcodes)
            ]);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }
}
