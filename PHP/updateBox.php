<?php
// Endpoint : Modification de caisse
require_once 'dbConnect.php';

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $nouveauNom = isset($_POST['nouveau_nom']) ? trim($_POST['nouveau_nom']) : '';
    $objets_ids = isset($_POST['objets_ids']) ? json_decode($_POST['objets_ids'], true) : null;
    $etat = isset($_POST['etat']) ? trim($_POST['etat']) : null;
    $emprunteurId = isset($_POST['emprunteur_id']) ? intval($_POST['emprunteur_id']) : null;
    
    if ($id === 0 && empty($nom)) {
        ApiResponse::error('ID ou nom de la caisse requis');
    }
    
    // Vérifier que la caisse existe
    if ($id > 0) {
        $checkStmt = $conn->prepare("SELECT id, Nom, Etat FROM Caisse WHERE id = :id");
        $checkStmt->execute([':id' => $id]);
    } else {
        $checkStmt = $conn->prepare("SELECT id, Nom, Etat FROM Caisse WHERE Nom = :nom");
        $checkStmt->execute([':nom' => $nom]);
    }
    
    $caisse = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$caisse) {
        ApiResponse::error('Caisse non trouvée');
    }
    
    $conn->beginTransaction();
    
    // Préparer les champs à mettre à jour
    $updates = [];
    $params = [':id' => $caisse['id']];
    
    // Mise à jour du nom
    if (!empty($nouveauNom) && $nouveauNom !== $caisse['Nom']) {
        $updates[] = "Nom = :nouveau_nom";
        $params[':nouveau_nom'] = $nouveauNom;
    }
    
    // Mise à jour de l'état et de l'emprunteur
    if ($etat !== null) {
        $updates[] = "Etat = :etat";
        $params[':etat'] = $etat;
        
        if ($etat === 'disponible') {
            $updates[] = "Emprunteur_id = NULL";
        } else {
            if ($emprunteurId <= 0) {
                if ($conn->inTransaction()) $conn->rollBack();
                ApiResponse::error("Un utilisateur doit être sélectionné pour une caisse réservée ou empruntée");
            }
            $updates[] = "Emprunteur_id = :emprunteur_id";
            $params[':emprunteur_id'] = $emprunteurId;
        }
    }
    
    // Mise à jour du contenu (objets)
    if ($objets_ids !== null && is_array($objets_ids)) {
        // Libérer tous les objets actuels
        $freeStmt = $conn->prepare("
            UPDATE Objet SET Caisse_id = NULL, Etat = 'disponible' WHERE Caisse_id = :caisse_id
        ");
        $freeStmt->execute([':caisse_id' => $caisse['id']]);
        
        // Lier les nouveaux objets
        if (!empty($objets_ids)) {
            $linkStmt = $conn->prepare("
                UPDATE Objet SET Caisse_id = :caisse_id, Etat = 'réservé'
                WHERE id = :objet_id AND Caisse_id IS NULL
            ");
            foreach ($objets_ids as $objet_id) {
                $linkStmt->execute([':caisse_id' => $caisse['id'], ':objet_id' => $objet_id]);
            }
        }
    }
    
    // Exécuter la mise à jour de la caisse
    if (!empty($updates)) {
        $sql = "UPDATE Caisse SET " . implode(", ", $updates) . " WHERE id = :id";
        $updateStmt = $conn->prepare($sql);
        $updateStmt->execute($params);
    }
    
    $conn->commit();
    
    $logger->info("Caisse modifiée", ['id' => $caisse['id'], 'nom' => $nouveauNom ?: $caisse['Nom']]);
    ApiResponse::success([
        'updated' => ['id' => $caisse['id'], 'nom' => $nouveauNom ?: $caisse['Nom']]
    ], 'Caisse modifiée avec succès');
    
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    ApiResponse::exception($e);
}
