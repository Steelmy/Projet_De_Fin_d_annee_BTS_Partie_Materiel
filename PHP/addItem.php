<?php
// Endpoint : Ajout de matériel
require_once 'dbConnect.php';

try {
    $type = isset($_POST['type_materiel']) ? trim($_POST['type_materiel']) : '';
    $nom = isset($_POST['nom_materiel']) ? trim($_POST['nom_materiel']) : '';
    $nombre = isset($_POST['nombre']) ? intval($_POST['nombre']) : 0;
    $codesBarres = isset($_POST['codes_barres']) ? json_decode($_POST['codes_barres'], true) : [];
    
    if (empty($type) || empty($nom) || $nombre <= 0) {
        ApiResponse::error('Tous les champs sont requis et le nombre doit être supérieur à 0');
    }
    
    $idsAjoutes = [];
    
    for ($i = 0; $i < $nombre; $i++) {
        if (isset($codesBarres[$i]) && !empty(trim($codesBarres[$i]))) {
            $codeBarre = trim($codesBarres[$i]);
        } else {
            ApiResponse::error("Le code-barre est obligatoire pour tous les matériels (manquant pour l'article #".($i+1).")");
        }

        // Vérifier unicité
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Objet WHERE Code_bar = :code");
        $checkStmt->execute([':code' => $codeBarre]);
        
        if ($checkStmt->fetchColumn() > 0) {
            ApiResponse::error("Le code-barre '$codeBarre' existe déjà. Veuillez en scanner un autre.");
        }
        
        // Insérer l'objet
        $insertStmt = $conn->prepare("
            INSERT INTO Objet (Type, Nom, Etat, Emprunteur_id, Code_bar)
            VALUES (:type, :nom, 'disponible', NULL, :code_barre)
        ");
        $insertStmt->execute([':type' => $type, ':nom' => $nom, ':code_barre' => $codeBarre]);
        $idsAjoutes[] = $conn->lastInsertId();
    }
    
    $logger->info("Matériel ajouté", ['type' => $type, 'nom' => $nom, 'nombre' => $nombre]);
    ApiResponse::success([
        'ids_ajoutes' => $idsAjoutes
    ], "$nombre matériel(s) ajouté(s) avec succès");
    
} catch (PDOException $e) {
    ApiResponse::exception($e);
}
