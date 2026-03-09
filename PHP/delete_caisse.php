<?php
// Endpoint : Suppression de caisse
require_once 'db_connect.php';

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    
    if ($id === 0 && empty($nom)) {
        ApiResponse::error('ID ou nom de la caisse requis');
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
        ApiResponse::error('Caisse non trouvée');
    }
    
    $conn->beginTransaction();
    
    // Remettre les objets en état disponible
    $freeStmt = $conn->prepare("
        UPDATE Objet SET Etat = 'disponible' WHERE Caisse_id = :caisse_id
    ");
    $freeStmt->execute([':caisse_id' => $caisse['id']]);
    
    // Supprimer la caisse
    $deleteStmt = $conn->prepare("DELETE FROM Caisse WHERE id = :id");
    $deleteStmt->execute([':id' => $caisse['id']]);
    
    $conn->commit();
    
    $logger->info("Caisse supprimée", ['id' => $caisse['id'], 'nom' => $caisse['Nom']]);
    ApiResponse::success([
        'deleted' => ['id' => $caisse['id'], 'nom' => $caisse['Nom']]
    ], 'Caisse supprimée avec succès');
    
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    ApiResponse::exception($e);
}
