<?php

require_once __DIR__ . '/../Models/Item.php';

class BarcodeController
{
    private Item $model;

    public function __construct(PDO $conn)
    {
        $this->model = new Item($conn);
    }

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
     * Liste les codes-barres existants en BDD avec filtrage optionnel.
     * Utilisé par le générateur pour la réimpression.
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
