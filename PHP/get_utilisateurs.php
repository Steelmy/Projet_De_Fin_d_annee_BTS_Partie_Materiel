<?php
header('Content-Type: application/json');

// Connexion à la base de données via le fichier central
require_once 'db_connect.php';

try {

    // Récupérer tous les utilisateurs
    $stmt = $conn->prepare("SELECT id, Nom, Prénom FROM utilisateurs ORDER BY Nom, Prénom");
    $stmt->execute();
    
    $utilisateurs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $utilisateurs[] = [
            'id' => $row['id'],
            'nom' => $row['Nom'],
            'prenom' => $row['Prénom'],
            'full_name' => $row['Prénom'] . ' ' . $row['Nom']
        ];
    }

    echo json_encode([
        'success' => true,
        'utilisateurs' => $utilisateurs
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn = null;
?>
