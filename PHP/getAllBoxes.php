<?php
// Endpoint : Récupérer toutes les caisses
require_once 'dbConnect.php';

try {
    $stmt = $conn->prepare("
        SELECT c.id, c.Nom, c.Etat, c.created_at, c.updated_at, c.Emprunteur_id,
               u.Prénom, u.Nom AS Nom_utilisateur
        FROM caisses c
        LEFT JOIN utilisateurs u ON c.Emprunteur_id = u.id
        ORDER BY c.Nom
    ");
    $stmt->execute();
    $caisses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pour chaque caisse, récupérer ses objets
    foreach ($caisses as &$caisse) {
        $stmtItems = $conn->prepare("
            SELECT id, Code_bar, Type, Sous_type, Nom, Etat FROM objets WHERE Caisse_id = ? ORDER BY Type, Sous_type, Nom
        ");
        $stmtItems->execute([$caisse['id']]);
        $caisse['Contenu'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        $caisse['nombre_objets'] = count($caisse['Contenu']);
    }
    
    ApiResponse::success([
        'data' => $caisses,
        'total' => count($caisses)
    ]);
    
} catch (PDOException $e) {
    ApiResponse::exception($e);
}
