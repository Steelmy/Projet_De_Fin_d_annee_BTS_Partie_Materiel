<?php
header('Content-Type: application/json');

require_once 'db_connect.php';

try {
    // Récupérer les paramètres
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    
    if ($id === 0 && empty($nom)) {
        echo json_encode([
            'success' => false,
            'message' => 'ID ou nom de la caisse requis'
        ]);
        exit;
    }
    
    // Vérifier que la caisse existe
    if ($id > 0) {
        $checkStmt = $conn->prepare("SELECT id, Nom FROM Caisse WHERE id = :id");
        $checkStmt->execute([':id' => $id]);
    } else {
        $checkStmt = $conn->prepare("SELECT id, Nom FROM Caisse WHERE Nom = :nom");
        $checkStmt->execute([':nom' => $nom]);
    }
    
    $caisse = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$caisse) {
        echo json_encode([
            'success' => false,
            'message' => 'Caisse non trouvée'
        ]);
        exit;
    }
    
    // Commencer une transaction
    $conn->beginTransaction();
    
    // Libérer les objets de la caisse (la foreign key ON DELETE SET NULL fait déjà ça)
    // Mais on doit remettre leur état à "disponible"
    $freeStmt = $conn->prepare("
        UPDATE Objet 
        SET Etat = 'disponible'
        WHERE Caisse_id = :caisse_id
    ");
    $freeStmt->execute([':caisse_id' => $caisse['id']]);
    
    // Supprimer la caisse (les objets seront automatiquement libérés grâce à ON DELETE SET NULL)
    $deleteStmt = $conn->prepare("DELETE FROM Caisse WHERE id = :id");
    $deleteStmt->execute([':id' => $caisse['id']]);
    
    // Valider la transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Caisse supprimée avec succès',
        'deleted' => [
            'id' => $caisse['id'],
            'nom' => $caisse['Nom']
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
