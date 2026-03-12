<?php
// Endpoint : Ajout de caisse
require_once 'dbConnect.php';

try {
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $objets_ids = isset($_POST['objets_ids']) ? json_decode($_POST['objets_ids'], true) : [];
    
    if (empty($nom)) {
        ApiResponse::error('Le nom de la caisse est requis');
    }
    
    if (!is_array($objets_ids)) {
        ApiResponse::error('Format des objets invalide');
    }
    
    // Vérifier l'unicité du nom
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM Caisse WHERE Nom = :nom");
    $checkStmt->execute([':nom' => $nom]);
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        ApiResponse::error('Une caisse avec ce nom existe déjà');
    }
    
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
            $updateStmt->execute([':caisse_id' => $caisseId, ':objet_id' => $objet_id]);
        }
    }
    
    $conn->commit();
    
    // Récupérer les objets liés
    $stmt = $conn->prepare("SELECT id, Code_bar, Type, Nom, Etat FROM Objet WHERE Caisse_id = ?");
    $stmt->execute([$caisseId]);
    $objets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $logger->info("Caisse ajoutée", ['nom' => $nom, 'id' => $caisseId, 'objets' => count($objets)]);
    ApiResponse::success([
        'caisse' => [
            'id' => $caisseId,
            'nom' => $nom,
            'contenu' => $objets,
            'nombre_objets' => count($objets)
        ]
    ], 'Caisse ajoutée avec succès');
    
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    ApiResponse::exception($e);
}
