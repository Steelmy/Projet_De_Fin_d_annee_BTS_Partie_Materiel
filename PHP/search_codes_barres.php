<?php
// Endpoint : Recherche de codes-barres
require_once 'db_connect.php';

try {
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';
    $nom = isset($_GET['nom']) ? trim($_GET['nom']) : '';
    
    $conditions = [];
    $params = [];
    
    $sql = "SELECT Code_bar, Type, Nom FROM Objet";
    
    if (!empty($query)) {
        $conditions[] = "Code_bar LIKE :query";
        $params[':query'] = $query . '%';
    }
    
    if (!empty($type)) {
        $conditions[] = "Type = :type";
        $params[':type'] = $type;
    }
    
    if (!empty($nom)) {
        $conditions[] = "Nom = :nom";
        $params[':nom'] = $nom;
    }

    // Filtre par état
    $etat = isset($_GET['etat']) ? trim($_GET['etat']) : '';
    if (!empty($etat)) {
        $conditions[] = "Etat = :etat";
        $params[':etat'] = $etat;
    }

    // Filtre "disponible uniquement"
    $disponibleOnly = isset($_GET['disponible_only']) ? trim($_GET['disponible_only']) : '';
    if ($disponibleOnly === '1') {
        $conditions[] = "Etat = 'disponible'";
        $conditions[] = "Caisse_id IS NULL";
    }
    
    if (count($conditions) > 0) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
        $orderBy = "Code_bar";
    } else {
        $orderBy = "Code_bar";
    }
    
    $sql .= " ORDER BY $orderBy LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ApiResponse::success(['results' => $results]);

} catch (PDOException $e) {
    ApiResponse::exception($e);
}
