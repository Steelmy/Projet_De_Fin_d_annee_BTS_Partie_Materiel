<?php
header('Content-Type: application/json');

require_once 'db_connect.php';

try {
    // Récupérer les paramètres
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $nouveauNom = isset($_POST['nouveau_nom']) ? trim($_POST['nouveau_nom']) : '';
    $objets_ids = isset($_POST['objets_ids']) ? json_decode($_POST['objets_ids'], true) : null;
    $etat = isset($_POST['etat']) ? trim($_POST['etat']) : null;
    $emprunteurId = isset($_POST['emprunteur_id']) ? intval($_POST['emprunteur_id']) : null;
    
    if ($id === 0 && empty($nom)) {
        echo json_encode([
            'success' => false,
            'message' => 'ID ou nom de la caisse requis'
        ]);
        exit;
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
        echo json_encode([
            'success' => false,
            'message' => 'Caisse non trouvée'
        ]);
        exit;
    }
    
    // Commencer une transaction
    $conn->beginTransaction();
    
    // Préparer les champs à mettre à jour
    $updates = [];
    $params = [':id' => $caisse['id']];
    
    // Mise à jour du nom si fourni
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
            // Si réservé ou emprunté, l'emprunteur est OBLIGATOIRE
            if ($emprunteurId <= 0) {
                // Rollback si transaction déjà commencée (bien que peu probable ici vu l'ordre, mais sécurité)
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                echo json_encode([
                    'success' => false,
                    'message' => "Un utilisateur doit être sélectionné pour une caisse réservée ou empruntée"
                ]);
                exit;
            }
            $updates[] = "Emprunteur_id = :emprunteur_id";
            $params[':emprunteur_id'] = $emprunteurId;
        }
    }
    
    // Mise à jour du contenu (objets) si fourni
    if ($objets_ids !== null && is_array($objets_ids)) {
        // 1. Libérer tous les objets actuels de cette caisse
        $freeStmt = $conn->prepare("
            UPDATE Objet 
            SET Caisse_id = NULL, Etat = 'disponible'
            WHERE Caisse_id = :caisse_id
        ");
        $freeStmt->execute([':caisse_id' => $caisse['id']]);
        
        // 2. Lier les nouveaux objets
        if (!empty($objets_ids)) {
            $linkStmt = $conn->prepare("
                UPDATE Objet 
                SET Caisse_id = :caisse_id, Etat = 'réservé'
                WHERE id = :objet_id AND Caisse_id IS NULL
            ");
            
            foreach ($objets_ids as $objet_id) {
                $linkStmt->execute([
                    ':caisse_id' => $caisse['id'],
                    ':objet_id' => $objet_id
                ]);
            }
        }
    }
    
    // Exécuter la mise à jour de la caisse si nécessaire
    if (!empty($updates)) {
        $sql = "UPDATE Caisse SET " . implode(", ", $updates) . " WHERE id = :id";
        $updateStmt = $conn->prepare($sql);
        $updateStmt->execute($params);
    }
    
    // Valider la transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Caisse modifiée avec succès',
        'updated' => [
            'id' => $caisse['id'],
            'nom' => $nouveauNom ?: $caisse['Nom']
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
