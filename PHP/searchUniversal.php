<?php
// Endpoint : Recherche universelle (types, noms, utilisateurs, caisses, codes)
require_once 'dbConnect.php';

try {
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    $filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';
    $limit = 10;

    $results = [];
    $table = '';
    $fields = '';
    $orderBy = '';
    $where = '';
    $params = [];

    // Configuration selon le type (KISS : switch simple)
    switch ($type) {
        case 'user':
            $table = 'utilisateurs';
            $fields = 'id, Nom, Prénom';
            if (!empty($query)) {
                $where = "(Nom LIKE :q_start OR Prénom LIKE :q_start)";
                $orderBy = "Nom, Prénom";
                $params[':q_start'] = $query . '%';
            } else {
                $orderBy = "Nom, Prénom";
            }
            break;

        case 'caisse':
            $table = 'Caisse';
            $fields = 'id, Nom, Etat';
            if (!empty($query)) {
                $where = "Nom LIKE :q_start";
                $orderBy = "Nom";
                $params[':q_start'] = $query . '%';
            } else {
                $orderBy = "Nom";
            }
            break;

        case 'materiel_type':
            $table = 'Objet';
            $fields = 'DISTINCT Type';
            if (!empty($query)) {
                $where = "Type LIKE :q_start";
                $orderBy = "Type";
                $params[':q_start'] = $query . '%';
            } else {
                $orderBy = "Type";
            }
            break;

        case 'materiel_nom':
            $table = 'Objet';
            $fields = 'DISTINCT Nom';
            $conditions = [];
            if (!empty($query)) {
                $conditions[] = "Nom LIKE :q_start";
                $params[':q_start'] = $query . '%';
            }
            if (!empty($filter)) {
                $conditions[] = "Type = :filter";
                $params[':filter'] = $filter;
            }
            if (count($conditions) > 0) {
                $where = implode(' AND ', $conditions);
                $orderBy = "Nom";
            } else {
                $orderBy = "Nom";
            }
            break;
        
        case 'materiel_code':
            $table = 'Objet';
            $fields = 'id, Code_bar, Nom, Type';
            $conditions = [];
            if (!empty($query)) {
                $conditions[] = "Code_bar LIKE :q_start";
                $params[':q_start'] = $query . '%';
            }
            $filterType = isset($_GET['filter_type']) ? trim($_GET['filter_type']) : '';
            $filterNom = isset($_GET['filter_nom']) ? trim($_GET['filter_nom']) : '';
            if (!empty($filterType)) {
                $conditions[] = "Type = :f_type";
                $params[':f_type'] = $filterType;
            }
            if (!empty($filterNom)) {
                $conditions[] = "Nom = :f_nom";
                $params[':f_nom'] = $filterNom;
            }
            if (count($conditions) > 0) {
                $where = implode(' AND ', $conditions);
                $orderBy = "Code_bar";
            } else {
                $orderBy = "Code_bar";
            }
            break;

        default:
            ApiResponse::error('Type invalide');
    }

    // Construction de la requête (DRY : un seul endroit)
    $sql = "SELECT $fields FROM $table";
    if (!empty($where)) {
        $sql .= " WHERE $where";
    }
    $sql .= " ORDER BY $orderBy LIMIT $limit";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatage selon le type (KISS : mapping simple)
    foreach ($rows as $row) {
        $item = [];
        
        if ($type === 'user') {
            $item = ['id' => $row['id'], 'label' => $row['Prénom'] . ' ' . $row['Nom'], 'value' => $row['Prénom'] . ' ' . $row['Nom'], 'meta' => $row];
        } elseif ($type === 'caisse') {
            $item = ['id' => $row['id'], 'label' => $row['Nom'], 'value' => $row['Nom'], 'meta' => $row];
        } elseif ($type === 'materiel_type') {
            $item = ['id' => $row['Type'], 'label' => $row['Type'], 'value' => $row['Type'], 'meta' => []];
        } elseif ($type === 'materiel_nom') {
            $item = ['id' => $row['Nom'], 'label' => $row['Nom'], 'value' => $row['Nom'], 'meta' => []];
        } elseif ($type === 'materiel_code') {
            $item = ['id' => $row['id'], 'label' => $row['Code_bar'] . ' - ' . $row['Nom'], 'value' => $row['Code_bar'], 'meta' => $row];
        }
        $results[] = $item;
    }

    ApiResponse::success(['data' => $results]);

} catch (PDOException $e) {
    ApiResponse::exception($e);
}
