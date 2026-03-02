<?php
header('Content-Type: application/json');

// Connexion à la base de données via le fichier central
require_once 'db_connect.php';

try {
    
    // Récupérer les paramètres de la requête
    $code_barre = isset($_POST['code_barre']) ? trim($_POST['code_barre']) : '';
    
    // Vérifier que le code-barre est fourni
    if (empty($code_barre)) {
        echo json_encode([
            'success' => false,
            'message' => 'Le code-barre du matériel est requis'
        ]);
        exit;
    }
    
    // Vérifier que l'objet existe
    $checkStmt = $conn->prepare("SELECT id, Type, Nom, Code_bar FROM Objet WHERE Code_bar = :code_barre");
    $checkStmt->execute([':code_barre' => $code_barre]);
    $objet = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$objet) {
        echo json_encode([
            'success' => false,
            'message' => 'Objet non trouvé'
        ]);
        exit;
    }
    
    // Supprimer l'objet
    $deleteStmt = $conn->prepare("DELETE FROM Objet WHERE Code_bar = :code_barre");
    $deleteStmt->execute([':code_barre' => $code_barre]);
    
    // Retourner le succès
    echo json_encode([
        'success' => true,
        'message' => "Objet supprimé avec succès",
        'deleted' => [
            'code_barre' => $objet['Code_bar'],
            'type' => $objet['Type'],
            'nom' => $objet['Nom']
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
}
?>
