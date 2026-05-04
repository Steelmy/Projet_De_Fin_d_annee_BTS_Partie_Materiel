<?php

require_once __DIR__ . '/../Models/Reference.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Box.php';
require_once __DIR__ . '/../Models/Item.php';

class SearchController
{
    private const AUTOCOMPLETE_LIMIT = 10;

    private Reference $referenceModel;
    private User $userModel;
    private Box $boxModel;
    private Item $itemModel;

    public function __construct(PDO $conn)
    {
        $this->referenceModel = new Reference($conn);
        $this->userModel = new User($conn);
        $this->boxModel = new Box($conn);
        $this->itemModel = new Item($conn);
    }

    public function universal(): void
    {
        try {
            $type = $_GET['type'] ?? '';
            $query = isset($_GET['query']) ? trim($_GET['query']) : '';
            $filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';

            switch ($type) {
                case 'user':
                    $rows = $this->userModel->search($query, self::AUTOCOMPLETE_LIMIT);
                    $results = $this->formatUserResults($rows);
                    break;

                case 'caisse':
                    $rows = $this->boxModel->search($query, self::AUTOCOMPLETE_LIMIT);
                    $results = $this->formatCaisseResults($rows);
                    break;

                case 'materiel_type':
                case 'materiel_sous_type':
                case 'materiel_nom':
                    $filterSousType = isset($_GET['filter_sous_type']) ? trim($_GET['filter_sous_type']) : '';
                    $rows = $this->referenceModel->search($type, $query, $filter, $filterSousType);
                    $results = $this->formatReferenceResults($type, $rows);
                    break;

                case 'materiel_code':
                    $filterType = isset($_GET['filter_type']) ? trim($_GET['filter_type']) : '';
                    $filterSousType = isset($_GET['filter_sous_type']) ? trim($_GET['filter_sous_type']) : '';
                    $filterNom = isset($_GET['filter_nom']) ? trim($_GET['filter_nom']) : '';
                    $rows = $this->itemModel->searchByCode($query, self::AUTOCOMPLETE_LIMIT, $filterType, $filterSousType, $filterNom);
                    $results = $this->formatMaterielCodeResults($rows);
                    break;

                default:
                    ApiResponse::error('Type invalide');
            }

            ApiResponse::success(['data' => $results]);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    public function barcodes(): void
    {
        try {
            $query = isset($_GET['query']) ? trim($_GET['query']) : '';
            $type = isset($_GET['type']) ? trim($_GET['type']) : '';
            $sousType = isset($_GET['sous_type']) ? trim($_GET['sous_type']) : '';
            $nom = isset($_GET['nom']) ? trim($_GET['nom']) : '';
            $etat = isset($_GET['etat']) ? trim($_GET['etat']) : '';
            $disponibleOnly = (isset($_GET['disponible_only']) && trim($_GET['disponible_only']) === '1');
            $nonDisponibleOnly = (isset($_GET['non_disponible_only']) && trim($_GET['non_disponible_only']) === '1');

            $results = $this->itemModel->searchBarcodes(
                $query,
                $type,
                $sousType,
                $nom,
                $etat,
                $disponibleOnly,
                $nonDisponibleOnly,
                self::AUTOCOMPLETE_LIMIT
            );

            ApiResponse::success(['results' => $results]);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    private function formatUserResults(array $rows): array
    {
        $results = [];
        foreach ($rows as $row) {
            $results[] = [
                'id' => $row['id'],
                'label' => $row['Prénom'] . ' ' . $row['Nom'],
                'value' => $row['Prénom'] . ' ' . $row['Nom'],
                'meta' => $row
            ];
        }
        return $results;
    }

    private function formatCaisseResults(array $rows): array
    {
        $results = [];
        foreach ($rows as $row) {
            $results[] = [
                'id' => $row['id'],
                'label' => $row['Nom'],
                'value' => $row['Nom'],
                'meta' => $row
            ];
        }
        return $results;
    }

    private function formatMaterielCodeResults(array $rows): array
    {
        $results = [];
        foreach ($rows as $row) {
            $results[] = [
                'id' => $row['id'],
                'label' => $row['Code_bar'] . ' - ' . $row['Nom'],
                'value' => $row['Code_bar'],
                'meta' => $row
            ];
        }
        return $results;
    }

    private function formatReferenceResults(string $type, array $rows): array
    {
        $results = [];
        foreach ($rows as $row) {
            if ($type === 'materiel_type') {
                $results[] = ['id' => $row['Type'], 'label' => $row['Type'], 'value' => $row['Type'], 'meta' => []];
            } elseif ($type === 'materiel_sous_type') {
                $results[] = ['id' => $row['Sous_type'], 'label' => $row['Sous_type'], 'value' => $row['Sous_type'], 'meta' => []];
            } elseif ($type === 'materiel_nom') {
                $results[] = ['id' => $row['Nom'], 'label' => $row['Nom'], 'value' => $row['Nom'], 'meta' => []];
            }
        }
        return $results;
    }
}
