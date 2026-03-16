<?php
// php/getReferenceTree.php
// Ce script retourne l'arbre complet des Types -> Sous-types -> Noms
// depuis la table catalogue_references pour alimenter les menus déroulants
require_once 'dbConnect.php';

try {
    $stmt = $conn->prepare("
        SELECT Type, Sous_type, Nom 
        FROM catalogue_references 
        ORDER BY Type ASC, Sous_type ASC, Nom ASC
    ");
    $stmt->execute();
    $references = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Construire l'arbre
    $tree = [];
    foreach ($references as $ref) {
        $type = $ref['Type'];
        $sousType = $ref['Sous_type'] ?: ''; // Gérer NULL comme chaîne vide
        $nom = $ref['Nom'];
        
        if (!isset($tree[$type])) {
            $tree[$type] = [];
        }
        
        if (!isset($tree[$type][$sousType])) {
            $tree[$type][$sousType] = [];
        }
        
        $tree[$type][$sousType][] = $nom;
    }
    
    ApiResponse::success(['tree' => $tree]);

} catch (PDOException $e) {
    ApiResponse::exception($e);
}
?>
