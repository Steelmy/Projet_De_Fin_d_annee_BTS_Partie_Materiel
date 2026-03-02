<?php
header('Content-Type: application/json');

require_once 'db_connect.php';

try {
    // Récupérer les données
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $objets_ids = isset($_POST['objets_ids']) ? json_decode($_POST['objets_ids'], true) : [];
    
    // Valider le nom
    if (empty($nom)) {
        echo json_encode([
            'success' => false,
            'message' => 'Le nom de la caisse est requis'
        ]);
        exit;
    }
    
    // Valider les IDs objets
    if (!is_array($objets_ids)) {
        echo json_encode([
            'success' => false,
            'message' => 'Format des objets invalide'
        ]);
        exit;
    }
    
    // Vérifier l'unicité du nom
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM Caisse WHERE Nom = :nom");
    $checkStmt->execute([':nom' => $nom]);
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Une caisse avec ce nom existe déjà'
        ]);
        exit;
    }
    
    // Commencer une transaction
    $conn->beginTransaction();
    
    // Insérer la caisse
    $insertStmt = $conn->prepare("
        INSERT INTO Caisse (Nom, Etat, Emprunteur_id)
        VALUES (:nom, 'disponible', NULL)
    ");
    
    $insertStmt->execute([':nom' => $nom]);
    $caisseId = $conn->lastInsertId();
    
    // Lier les objets à la caisse
    if (!empty($objets_ids)) {
        $updateStmt = $conn->prepare("
            UPDATE Objet 
            SET Caisse_id = :caisse_id, Etat = 'réservé'
            WHERE id = :objet_id AND Caisse_id IS NULL
        ");
        
        foreach ($objets_ids as $objet_id) {
            $updateStmt->execute([
                ':caisse_id' => $caisseId,
                ':objet_id' => $objet_id
            ]);
        }
    }
    
    // Valider la transaction
    $conn->commit();
    
    // Récupérer les objets liés pour la réponse
    $stmt = $conn->prepare("SELECT id, Code_bar, Type, Nom, Etat FROM Objet WHERE Caisse_id = ?");
    $stmt->execute([$caisseId]);
    $objets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Caisse ajoutée avec succès',
        'caisse' => [
            'id' => $caisseId,
            'nom' => $nom,
            'contenu' => $objets,
            'nombre_objets' => count($objets)
        ]
    ]);
    
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
}
?>
