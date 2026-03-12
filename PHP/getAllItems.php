<?php
// Endpoint : Récupérer tous les matériels
require_once 'dbConnect.php';

try {
    $stmt = $conn->prepare("
        SELECT 
            o.id, o.Code_bar, o.Type, o.Nom, o.Etat,
            u.Prénom, u.Nom AS Nom_utilisateur,
            c.Nom AS Nom_caisse
        FROM Objet o
        LEFT JOIN utilisateurs u ON o.Emprunteur_id = u.id
        LEFT JOIN Caisse c ON o.Caisse_id = c.id
        ORDER BY o.Type, o.Nom
    ");
    $stmt->execute();
    $materiels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ApiResponse::success([
        'data' => $materiels,
        'total' => count($materiels)
    ]);
    
} catch (PDOException $e) {
    ApiResponse::exception($e);
}
