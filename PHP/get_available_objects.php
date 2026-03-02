<?php
header('Content-Type: application/json');

require_once 'db_connect.php';

try {
    // Récupérer tous les objets disponibles (non empruntés/réservés ET pas dans une caisse)
    $stmt = $conn->prepare("
        SELECT 
            id,
            Code_bar,
            Type,
            Nom,
            Etat
        FROM Objet
        WHERE Etat = 'disponible' AND Caisse_id IS NULL
        ORDER BY Type, Nom
    ");
    
    $stmt->execute();
    $objets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'objets' => $objets,
        'total' => count($objets)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
