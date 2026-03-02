<?php
header('Content-Type: application/json');

    // Connexion à la base de données via le fichier central
    require_once 'db_connect.php';

    try {
    
    // Récupérer les paramètres de la requête
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';
    $nom = isset($_GET['nom']) ? trim($_GET['nom']) : '';
    
    // Vérifier que les paramètres sont fournis
    if (empty($type) || empty($nom)) {
        echo json_encode([
            'success' => false,
            'message' => 'Le type et le nom sont requis',
            'ids' => []
        ]);
        exit;
    }
    
    // Préparer et exécuter la requête
    $stmt = $conn->prepare("
        SELECT id, Code_bar, Etat, Emprunteur_id 
        FROM Objet 
        WHERE Type = :type AND Nom = :nom
        ORDER BY Code_bar
    ");
    
    $stmt->execute([
        ':type' => $type,
        ':nom' => $nom
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // DEBUG: Compter le nombre total d'enregistrements
    $debugStmt = $conn->prepare("SELECT COUNT(*) as total FROM Objet WHERE Type = :type AND Nom = :nom");
    $debugStmt->execute([':type' => $type, ':nom' => $nom]);
    $debugData = $debugStmt->fetch(PDO::FETCH_ASSOC);
    
    // Retourner les résultats
    echo json_encode([
        'success' => true,
        'message' => count($results) . ' matériel(s) trouvé(s)',
        'ids' => $results,
        'debug' => [
            'type_recherche' => $type,
            'nom_recherche' => $nom,
            'nombre_resultats' => $debugData['total']
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage(),
        'ids' => []
    ]);
}
?>
