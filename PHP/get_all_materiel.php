<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

try {
    // Récupérer tous les objets avec infos utilisateur ET caisse (via JOIN)
    $stmt = $conn->prepare("
        SELECT 
            o.id,
            o.Code_bar,
            o.Type,
            o.Nom,
            o.Etat,
            u.Prénom,
            u.Nom AS Nom_utilisateur,
            c.Nom AS Nom_caisse
        FROM Objet o
        LEFT JOIN utilisateurs u ON o.Emprunteur_id = u.id
        LEFT JOIN Caisse c ON o.Caisse_id = c.id
        ORDER BY o.Type, o.Nom
    ");
    $stmt->execute();
    $materiels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $materiels,
        'total' => count($materiels)
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn = null;
?>
