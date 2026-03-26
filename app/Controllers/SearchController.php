<?php

require_once __DIR__ . '/../Models/Reference.php';

class SearchController
{
    private PDO $conn;
    private Reference $referenceModel;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
        $this->referenceModel = new Reference($conn);
    }

    public function universal(): void
    {
        try {
            $type = isset($_GET['type']) ? $_GET['type'] : '';
            $query = isset($_GET['query']) ? trim($_GET['query']) : '';
            $filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';
            $limit = 10;

            $results = [];

            switch ($type) {
                case 'user':
                    $results = $this->searchUsers($query, $limit);
                    break;

                case 'caisse':
                    $results = $this->searchCaisses($query, $limit);
                    break;

                case 'materiel_type':
                case 'materiel_sous_type':
                case 'materiel_nom':
                    $filterSousType = isset($_GET['filter_sous_type']) ? trim($_GET['filter_sous_type']) : '';
                    $rows = $this->referenceModel->search($type, $query, $filter, $filterSousType);
                    $results = $this->formatReferenceResults($type, $rows);
                    break;

                case 'materiel_code':
                    $results = $this->searchMaterielCode($query, $limit);
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
            $disponibleOnly = isset($_GET['disponible_only']) ? trim($_GET['disponible_only']) : '';

            $conditions = [];
            $params = [];

            if (!empty($query)) {
                $conditions[] = "o.Code_bar LIKE :query";
                $params[':query'] = $query . '%';
            }
            if (!empty($type)) {
                $conditions[] = "t.nom_type = :type";
                $params[':type'] = $type;
            }
            if (!empty($sousType)) {
                $conditions[] = "st.nom_sous_type = :sous_type";
                $params[':sous_type'] = $sousType;
            }
            if (!empty($nom)) {
                $conditions[] = "nr.nom_reference = :nom";
                $params[':nom'] = $nom;
            }
            if (!empty($etat)) {
                $conditions[] = "o.Etat = :etat";
                $params[':etat'] = $etat;
            }
            if ($disponibleOnly === '1') {
                $conditions[] = "o.Etat = 'disponible'";
                $conditions[] = "o.Caisse_id IS NULL";
            }

            $sql = "
                SELECT o.Code_bar, t.nom_type AS Type, st.nom_sous_type AS Sous_type, nr.nom_reference AS Nom
                FROM objets o
                LEFT JOIN noms_references nr ON o.id_nom_reference = nr.id
                LEFT JOIN sous_types st ON nr.id_sous_type = st.id
                LEFT JOIN types t ON st.id_type = t.id
            ";
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(' AND ', $conditions);
            }
            $sql .= " ORDER BY o.Code_bar LIMIT 10";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();

            ApiResponse::success(['results' => $results]);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    private function searchUsers(string $query, int $limit): array
    {
        $sql = "SELECT id, Nom, Prénom FROM utilisateurs";
        $params = [];

        if (!empty($query)) {
            $sql .= " WHERE (Nom LIKE :q_start OR Prénom LIKE :q_start)";
            $params[':q_start'] = $query . '%';
        }
        $sql .= " ORDER BY Nom, Prénom LIMIT $limit";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

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

    private function searchCaisses(string $query, int $limit): array
    {
        $sql = "SELECT id, Nom, Etat FROM caisses";
        $params = [];

        if (!empty($query)) {
            $sql .= " WHERE Nom LIKE :q_start";
            $params[':q_start'] = $query . '%';
        }
        $sql .= " ORDER BY Nom LIMIT $limit";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

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

    private function searchMaterielCode(string $query, int $limit): array
    {
        $conditions = [];
        $params = [];

        if (!empty($query)) {
            $conditions[] = "o.Code_bar LIKE :q_start";
            $params[':q_start'] = $query . '%';
        }

        $filterType = isset($_GET['filter_type']) ? trim($_GET['filter_type']) : '';
        $filterSousType = isset($_GET['filter_sous_type']) ? trim($_GET['filter_sous_type']) : '';
        $filterNom = isset($_GET['filter_nom']) ? trim($_GET['filter_nom']) : '';

        if (!empty($filterType)) {
            $conditions[] = "t.nom_type = :f_type";
            $params[':f_type'] = $filterType;
        }
        if (!empty($filterSousType)) {
            $conditions[] = "st.nom_sous_type = :f_sous_type";
            $params[':f_sous_type'] = $filterSousType;
        }
        if (!empty($filterNom)) {
            $conditions[] = "nr.nom_reference = :f_nom";
            $params[':f_nom'] = $filterNom;
        }

        $sql = "
            SELECT o.id, o.Code_bar, nr.nom_reference AS Nom, t.nom_type AS Type, st.nom_sous_type AS Sous_type
            FROM objets o
            LEFT JOIN noms_references nr ON o.id_nom_reference = nr.id
            LEFT JOIN sous_types st ON nr.id_sous_type = st.id
            LEFT JOIN types t ON st.id_type = t.id
        ";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY o.Code_bar LIMIT $limit";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

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
