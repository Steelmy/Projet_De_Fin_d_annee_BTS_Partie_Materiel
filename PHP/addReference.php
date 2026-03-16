<?php
require_once 'dbConnect.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ApiResponse::error('Méthode non autorisée', 405);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validation
    $type = isset($data['type']) ? trim($data['type']) : '';
    $sous_type = isset($data['sous_type']) ? trim($data['sous_type']) : ''; // Optional but kept consistent
    $nom = isset($data['nom']) ? trim($data['nom']) : '';
    
    if (empty($type) || empty($nom)) {
        ApiResponse::error('Le type et le nom sont requis');
    }

    // Database is already connected via $conn from dbConnect.php
    // Format variables (e.g. capitalize first letter)
    $type = mb_convert_case($type, MB_CASE_TITLE, "UTF-8");
    if (!empty($sous_type)) {
        $sous_type = mb_convert_case($sous_type, MB_CASE_TITLE, "UTF-8");
    }
    $nom = mb_convert_case($nom, MB_CASE_TITLE, "UTF-8");

    // Start transaction
    $conn->beginTransaction();

    try {
        // Prepare statement
        $stmt = $conn->prepare("
            INSERT IGNORE INTO catalogue_references (Type, Sous_type, Nom) 
            VALUES (:type, :sous_type, :nom)
        ");
        
        $stmt->execute([
            ':type' => $type,
            ':sous_type' => $sous_type,
            ':nom' => $nom
        ]);

        if ($stmt->rowCount() > 0) {
            $message = 'Référence ajoutée avec succès au catalogue.';
        } else {
            $message = 'Cette combinaison de références existe déjà dans le catalogue.';
            // Technically a success if it's already there, just let the user know.
        }

        $conn->commit();
        
        ApiResponse::success([
            'message' => $message,
            'inserted' => $stmt->rowCount() > 0,
            'reference' => [
                'type' => $type,
                'sous_type' => $sous_type,
                'nom' => $nom
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Constraint violation (should be caught by IGNORE, but just in case)
        ApiResponse::error('Cette référence existe déjà', 409);
    }
    error_log("Database Error in addReference.php: " . $e->getMessage());
    ApiResponse::error('Erreur base de données lors de l\'ajout de la référence');
} catch (Exception $e) {
    error_log("Error in addReference.php: " . $e->getMessage());
    ApiResponse::error('Erreur serveur lors de l\'ajout');
}
