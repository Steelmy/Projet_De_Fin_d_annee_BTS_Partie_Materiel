<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

try {
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';
    $nom = isset($_GET['nom']) ? trim($_GET['nom']) : '';
    
    $conditions = [];
    $params = [];

    // Prioriser les résultats qui COMMENCENT par le terme
    // Mais on veut aussi pouvoir filtrer
    
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

    // Nouveau filtre sur l'état (ex: disponible)
    $etat = isset($_GET['etat']) ? trim($_GET['etat']) : '';
    if (!empty($etat)) {
        $conditions[] = "Etat = :etat";
        $params[':etat'] = $etat;
    }
    
    if (count($conditions) > 0) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
        // Si on a une recherche précise (query), on trie par code barre
        // Si on a juste des filtres (Type/Nom) sans query, on peut trier par code barre ou random
        if (!empty($query)) {
             $orderBy = "Code_bar";
        } else {
             // Pas de query : propositions "au hasard" ou par défaut
             $orderBy = "RAND()";
        }
    } else {
        // Aucune condition (tout vide) : Random
        $orderBy = "RAND()";
    }
    
    $sql .= " ORDER BY $orderBy LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'results' => $results
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
