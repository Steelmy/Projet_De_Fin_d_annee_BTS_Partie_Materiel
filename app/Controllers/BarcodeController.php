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

    public function generate(): void
    {
        try {
            $count = isset($_GET['count']) ? intval($_GET['count']) : 1;
            $length = isset($_GET['length']) ? intval($_GET['length']) : 13;

            if ($count < 1 || $count > 100) {
                ApiResponse::error('Le nombre de codes-barres doit être entre 1 et 100');
            }
            if ($length < 8 || $length > 20) {
                ApiResponse::error('La longueur du code-barre doit être entre 8 et 20');
            }

            $barcodes = [];
            $totalAttempts = 0;

            for ($i = 0; $i < $count; $i++) {
                $barcode = $this->model->generateUniqueBarcode($length);
                if ($barcode === null) {
                    ApiResponse::error('Impossible de générer un code-barre unique après 100 tentatives');
                }
                $barcodes[] = $barcode;
            }

            ApiResponse::success([
                'barcodes' => $barcodes,
                'count' => count($barcodes)
            ]);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }
}
