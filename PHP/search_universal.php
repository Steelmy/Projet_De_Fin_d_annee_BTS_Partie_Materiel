<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

try {
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    $filter = isset($_GET['filter']) ? trim($_GET['filter']) : ''; // Nouveau paramètre de filtrage
    $limit = 10;

    $results = [];
    $table = '';
    $fields = '';
    $orderBy = '';
    $where = '';
    $params = [];

    // Configuration selon le type
    switch ($type) {
        case 'user':
            $table = 'utilisateurs';
            $fields = 'id, Nom, Prénom';
            // Recherche sur Nom ou Prénom
            if (!empty($query)) {
                $where = "(Nom LIKE :q_start OR Prénom LIKE :q_start)";
                $orderBy = "Nom, Prénom";
                $params[':q_start'] = $query . '%';
            } else {
                $orderBy = "RAND()";
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
                $orderBy = "RAND()";
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
                $orderBy = "RAND()";
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
             
             // Filtrage par TYPE si fourni
             if (!empty($filter)) {
                 $conditions[] = "Type = :filter";
                 $params[':filter'] = $filter;
             }

             if (count($conditions) > 0) {
                 $where = implode(' AND ', $conditions);
                 $orderBy = "Nom";
             } else {
                 $orderBy = "RAND()";
             }
             break;
        
        case 'materiel_code':
            $table = 'Objet';
            $fields = 'id, Code_bar, Nom, Type';
            $conditions = [];

            // 1. Filtrage par Query (chiffres seulement)
            if (!empty($query)) {
                $conditions[] = "Code_bar LIKE :q_start";
                $params[':q_start'] = $query . '%';
            }

            // 2. Filtrage Contextuel (Type et Nom)
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

            // 3. Construction du WHERE et ORDER BY
            if (count($conditions) > 0) {
                $where = implode(' AND ', $conditions);
                // Si on a une query, on trie par pertinence code-barre, sinon random ou ID
                $orderBy = !empty($query) ? "Code_bar" : "RAND()";
            } else {
                $orderBy = "RAND()";
            }
             break;

        default:
            echo json_encode(['success' => false, 'error' => 'Type invalide']);
            exit;
    }

    // Construction de la requête
    $sql = "SELECT $fields FROM $table";
    if (!empty($where)) {
        $sql .= " WHERE $where";
    }
    $sql .= " ORDER BY $orderBy LIMIT $limit";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatage des données pour le frontend
    foreach ($rows as $row) {
        $item = [];
        
        if ($type === 'user') {
            $item = [
                'id' => $row['id'],
                'label' => $row['Prénom'] . ' ' . $row['Nom'],
                'value' => $row['Prénom'] . ' ' . $row['Nom'], 
                'meta' => $row
            ];
        } elseif ($type === 'caisse') {
            $item = [
                'id' => $row['id'],
                'label' => $row['Nom'],
                'value' => $row['Nom'],
                'meta' => $row
            ];
        } elseif ($type === 'materiel_type') {
            $item = [
                'id' => $row['Type'],
                'label' => $row['Type'],
                'value' => $row['Type'],
                'meta' => []
            ];
        } elseif ($type === 'materiel_nom') {
             $item = [
                 'id' => $row['Nom'],
                 'label' => $row['Nom'],
                 'value' => $row['Nom'],
                 'meta' => []
             ];
         } elseif ($type === 'materiel_code') {
            $item = [
                'id' => $row['id'],
                'label' => $row['Code_bar'] . ' - ' . $row['Nom'],
                'value' => $row['Code_bar'],
                'meta' => $row
            ];
        }

        $results[] = $item;
    }

    echo json_encode([
        'success' => true,
        'data' => $results
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
