<?php
// Endpoint : Récupérer les objets disponibles (pour ajout/modification de caisse)
require_once 'dbConnect.php';

try {
    $stmt = $conn->prepare("
        SELECT id, Code_bar, Type, Nom, Etat
        FROM Objet
        WHERE Etat = 'disponible' AND Caisse_id IS NULL
        ORDER BY Type, Nom
    ");
    $stmt->execute();
    $objets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ApiResponse::success([
        'objets' => $objets,
        'total' => count($objets)
    ]);
    
} catch (PDOException $e) {
    ApiResponse::exception($e);
}
