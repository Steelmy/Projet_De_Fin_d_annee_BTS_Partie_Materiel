<?php
// Endpoint : Suppression de matériel
require_once 'db_connect.php';

try {
    $code_barre = isset($_POST['code_barre']) ? trim($_POST['code_barre']) : '';
    
    if (empty($code_barre)) {
        ApiResponse::error('Le code-barre du matériel est requis');
    }
    
    // Vérifier que l'objet existe
    $checkStmt = $conn->prepare("SELECT id, Type, Nom, Code_bar, Etat, Caisse_id FROM Objet WHERE Code_bar = :code_barre");
    $checkStmt->execute([':code_barre' => $code_barre]);
    $objet = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$objet) {
        ApiResponse::error('Objet non trouvé');
    }

    // Vérifier que l'objet n'est pas dans une caisse
    if (!empty($objet['Caisse_id'])) {
        ApiResponse::error('Cet objet est actuellement dans une caisse. Veuillez d\'abord le retirer de la caisse avant de pouvoir le supprimer.');
    }

    // Vérifier que l'objet est disponible
    if ($objet['Etat'] !== 'disponible') {
        ApiResponse::error('Cet objet est actuellement ' . $objet['Etat'] . '. Veuillez d\'abord le remettre en état "disponible" avant de pouvoir le supprimer.');
    }
    
    // Supprimer l'objet
    $deleteStmt = $conn->prepare("DELETE FROM Objet WHERE Code_bar = :code_barre");
    $deleteStmt->execute([':code_barre' => $code_barre]);
    
    $logger->info("Matériel supprimé", ['code_barre' => $code_barre, 'type' => $objet['Type'], 'nom' => $objet['Nom']]);
    ApiResponse::success([
        'deleted' => [
            'code_barre' => $objet['Code_bar'],
            'type' => $objet['Type'],
            'nom' => $objet['Nom']
        ]
    ], 'Objet supprimé avec succès');
    
} catch (PDOException $e) {
    ApiResponse::exception($e);
}
