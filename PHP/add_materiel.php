<?php
header('Content-Type: application/json');

// Connexion à la base de données via le fichier central
require_once 'db_connect.php';

try {
    
    // Récupérer les paramètres
    $type = isset($_POST['type_materiel']) ? trim($_POST['type_materiel']) : '';
    $nom = isset($_POST['nom_materiel']) ? trim($_POST['nom_materiel']) : '';
    $nombre = isset($_POST['nombre']) ? intval($_POST['nombre']) : 0;
    $codesBarres = isset($_POST['codes_barres']) ? json_decode($_POST['codes_barres'], true) : [];
    
    // Vérifier que les paramètres sont fournis
    if (empty($type) || empty($nom) || $nombre <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Tous les champs sont requis et le nombre doit être supérieur à 0'
        ]);
        exit;
    }
    
    $idsAjoutes = [];
    
    // Ajouter le nombre demandé de matériels
    for ($i = 0; $i < $nombre; $i++) {
        // Déterminer le code-barre à utiliser
        if (isset($codesBarres[$i]) && !empty(trim($codesBarres[$i]))) {
            // Utiliser le code-barre fourni par l'utilisateur
            $codeBarre = trim($codesBarres[$i]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Le code-barre est obligatoire pour tous les matériels (manquant pour l'article #".($i+1).")"
            ]);
            exit;
        }

        // Vérifier que le code-barre est unique
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Objet WHERE Code_bar = :code");
        $checkStmt->execute([':code' => $codeBarre]);
        
        if ($checkStmt->fetchColumn() > 0) {
            echo json_encode([
                'success' => false,
                'message' => "Le code-barre '$codeBarre' existe déjà. Veuillez en scanner un autre."
            ]);
            exit;
        }
        
        // Insérer l'objet avec code-barre
        $insertStmt = $conn->prepare("
            INSERT INTO Objet (Type, Nom, Etat, Emprunteur_id, Code_bar)
            VALUES (:type, :nom, 'disponible', NULL, :code_barre)
        ");
        
        $insertStmt->execute([
            ':type' => $type,
            ':nom' => $nom,
            ':code_barre' => $codeBarre
        ]);
        
        // Récupérer l'ID généré (maintenant un simple entier auto-incrémenté)
        $generatedId = $conn->lastInsertId();
        $idsAjoutes[] = $generatedId;
    }
    
    echo json_encode([
        'success' => true,
        'message' => "$nombre matériel(s) ajouté(s) avec succès",
        'ids_ajoutes' => $idsAjoutes
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
}
?>
