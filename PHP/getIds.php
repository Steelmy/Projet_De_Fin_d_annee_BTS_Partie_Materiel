<?php
// Endpoint : Récupérer les IDs de matériel par type et nom
require_once 'dbConnect.php';

try {
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';
    $nom = isset($_GET['nom']) ? trim($_GET['nom']) : '';
    
    if (empty($type) || empty($nom)) {
        ApiResponse::error('Le type et le nom sont requis');
    }
    
    $stmt = $conn->prepare("
        SELECT id, Code_bar, Etat, Emprunteur_id 
        FROM Objet 
        WHERE Type = :type AND Nom = :nom
        ORDER BY Code_bar
    ");
    $stmt->execute([':type' => $type, ':nom' => $nom]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ApiResponse::success([
        'ids' => $results
    ], count($results) . ' matériel(s) trouvé(s)');
    
} catch (PDOException $e) {
    ApiResponse::exception($e);
}
