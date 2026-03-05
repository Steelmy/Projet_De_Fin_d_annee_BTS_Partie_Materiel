<?php
header('Content-Type: application/json');

// Connexion à la base de données via le fichier central
require_once 'db_connect.php';

try {
    
    // Récupérer les paramètres
    $code_barre = isset($_POST['code_barre']) ? trim($_POST['code_barre']) : '';
    $etat = isset($_POST['etat']) ? trim($_POST['etat']) : '';
    $reserveur_emprunteur = isset($_POST['reserveur_emprunteur']) ? intval($_POST['reserveur_emprunteur']) : 1;
    
    // Vérifier que les paramètres sont fournis
    if (empty($code_barre) || empty($etat)) {
        echo json_encode([
            'success' => false,
            'message' => 'Le code-barre et l\'état sont requis'
        ]);
        exit;
    }
    
    // Vérifier que l'objet existe
    $checkStmt = $conn->prepare("SELECT id, Type, Nom, Code_bar, Caisse_id FROM Objet WHERE Code_bar = :code_barre");
    $checkStmt->execute([':code_barre' => $code_barre]);
    $objet = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$objet) {
        echo json_encode([
            'success' => false,
            'message' => 'Objet non trouvé'
        ]);
        exit;
    }

    // Vérifier que l'objet n'est pas dans une caisse
    if (!empty($objet['Caisse_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Cet objet est actuellement dans une caisse. Veuillez d\'abord le retirer de la caisse avant de pouvoir le modifier.'
        ]);
        exit;
    }
    
    // Logique de validation selon l'état
    if ($etat === 'disponible') {
        // Si disponible, mettre Emprunteur_id à NULL
        $reserveur_emprunteur = NULL;
    } else {
        // Si réservé ou emprunté, vérifier qu'un utilisateur est sélectionné
        // Accepter tous les IDs >= 1 (1 est un utilisateur valide)
        if ($reserveur_emprunteur < 1) {
            echo json_encode([
                'success' => false,
                'message' => 'Veuillez sélectionner un utilisateur pour un objet réservé ou emprunté'
            ]);
            exit;
        }
    }
    
    // Mettre à jour l'objet
    $updateStmt = $conn->prepare("
        UPDATE Objet 
        SET Etat = :etat, 
            Emprunteur_id = :reserveur_emprunteur 
        WHERE Code_bar = :code_barre
    ");
    
    $updateStmt->execute([
        ':code_barre' => $code_barre,
        ':etat' => $etat,
        ':reserveur_emprunteur' => $reserveur_emprunteur
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Objet modifié avec succès',
        'updated' => [
            'code_barre' => $code_barre,
            'type' => $objet['Type'],
            'nom' => $objet['Nom'],
            'etat' => $etat
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
}
?>
