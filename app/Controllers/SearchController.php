<?php

require_once __DIR__ . '/../Models/Reference.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Box.php';
require_once __DIR__ . '/../Models/Item.php';

/**
 * Contrôleur des recherches (autocomplete) sur utilisateurs, caisses,
 * références hiérarchiques et codes-barres.
 *
 * N'effectue aucune SQL : délègue aux modèles puis convertit les lignes
 * en format autocomplete `{id, label, value, meta}`.
 */
class SearchController
{
    private const AUTOCOMPLETE_LIMIT = 10;

    /** @var Reference Modèle d'accès au catalogue de références. */
    private Reference $referenceModel;

    /** @var User Modèle d'accès aux utilisateurs. */
    private User $userModel;

    /** @var Box Modèle d'accès aux caisses. */
    private Box $boxModel;

    /** @var Item Modèle d'accès aux objets. */
    private Item $itemModel;

    /**
     * @param PDO $conn Connexion PDO active.
     */
    public function __construct(PDO $conn)
    {
        $this->referenceModel = new Reference($conn);
        $this->userModel = new User($conn);
        $this->boxModel = new Box($conn);
        $this->itemModel = new Item($conn);
    }

    /**
     * Routeur d'autocomplete universel : choisit le modèle à interroger
     * selon `type`, puis renvoie un tableau `{id, label, value, meta}`.
     *
     * Entrée GET : `type` (`user|caisse|materiel_type|materiel_sous_type|materiel_nom|materiel_code`),
     * `query`, plus filtres contextuels (`filter`, `filter_sous_type`, `filter_type`, `filter_nom`).
     *
     * @return void Réponse JSON via ApiResponse.
     */
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

    /**
     * Recherche dédiée aux codes-barres avec filtres étendus
     * (état, disponibilité). Renvoie les lignes brutes du modèle.
     *
     * Entrée GET : `query`, `type`, `sous_type`, `nom`, `etat`,
     * `disponible_only` ('1'), `non_disponible_only` ('1').
     *
     * @return void Réponse JSON via ApiResponse.
     */
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

    /**
     * Convertit les lignes utilisateurs en items d'autocomplete.
     *
     * @param array<int, array{id:int, Nom:string, Prénom:string}> $rows
     * @return array<int, array{id:int, label:string, value:string, meta:array<string, mixed>}>
     */
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

    /**
     * Convertit les lignes caisses en items d'autocomplete.
     *
     * @param array<int, array{id:int, Nom:string, Etat:string}> $rows
     * @return array<int, array{id:int, label:string, value:string, meta:array<string, mixed>}>
     */
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

    /**
     * Convertit les lignes objets (recherche par code) en items d'autocomplete.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{id:int, label:string, value:string, meta:array<string, mixed>}>
     */
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

    /**
     * Convertit les lignes du catalogue de références en items d'autocomplete
     * en sélectionnant la colonne pertinente selon le niveau interrogé.
     *
     * @param string $type Niveau interrogé (`materiel_type|materiel_sous_type|materiel_nom`).
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{id:string, label:string, value:string, meta:array<string, mixed>}>
     */
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
