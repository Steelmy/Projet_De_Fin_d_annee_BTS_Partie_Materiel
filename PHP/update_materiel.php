<?php
// Endpoint : Modification de matériel
require_once 'db_connect.php';

try {
    $code_barre = isset($_POST['code_barre']) ? trim($_POST['code_barre']) : '';
    $etat = isset($_POST['etat']) ? trim($_POST['etat']) : '';
    $reserveur_emprunteur = isset($_POST['reserveur_emprunteur']) ? intval($_POST['reserveur_emprunteur']) : 1;
    
    if (empty($code_barre) || empty($etat)) {
        ApiResponse::error('Le code-barre et l\'état sont requis');
    }
    
    // Vérifier que l'objet existe
    $checkStmt = $conn->prepare("SELECT id, Type, Nom, Code_bar, Caisse_id FROM Objet WHERE Code_bar = :code_barre");
    $checkStmt->execute([':code_barre' => $code_barre]);
    $objet = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$objet) {
        ApiResponse::error('Objet non trouvé');
    }

    // Vérifier que l'objet n'est pas dans une caisse
    if (!empty($objet['Caisse_id'])) {
        ApiResponse::error('Cet objet est actuellement dans une caisse. Veuillez d\'abord le retirer de la caisse avant de pouvoir le modifier.');
    }
    
    // Logique de validation selon l'état
    if ($etat === 'disponible') {
        $reserveur_emprunteur = NULL;
    } else {
        if ($reserveur_emprunteur < 1) {
            ApiResponse::error('Veuillez sélectionner un utilisateur pour un objet réservé ou emprunté');
        }
    }
    
    // Mettre à jour l'objet
    $updateStmt = $conn->prepare("
        UPDATE Objet 
        SET Etat = :etat, Emprunteur_id = :reserveur_emprunteur 
        WHERE Code_bar = :code_barre
    ");
    $updateStmt->execute([
        ':code_barre' => $code_barre,
        ':etat' => $etat,
        ':reserveur_emprunteur' => $reserveur_emprunteur
    ]);
    
    $logger->info("Matériel modifié", ['code_barre' => $code_barre, 'etat' => $etat]);
    ApiResponse::success([
        'updated' => [
            'code_barre' => $code_barre,
            'type' => $objet['Type'],
            'nom' => $objet['Nom'],
            'etat' => $etat
        ]
    ], 'Objet modifié avec succès');
    
} catch (PDOException $e) {
    ApiResponse::exception($e);
}
