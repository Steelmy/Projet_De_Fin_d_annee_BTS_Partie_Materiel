<?php
// Endpoint : Récupérer toutes les caisses
require_once 'dbConnect.php';

try {
    $stmt = $conn->prepare("
        SELECT c.id, c.Nom, c.Etat, c.created_at, c.updated_at, c.Emprunteur_id,
               u.Prénom, u.Nom AS Nom_utilisateur
        FROM Caisse c
        LEFT JOIN utilisateurs u ON c.Emprunteur_id = u.id
        ORDER BY c.Nom
    ");
    $stmt->execute();
    $caisses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pour chaque caisse, récupérer ses objets
    foreach ($caisses as &$caisse) {
        $stmt = $conn->prepare("
            SELECT id, Code_bar, Type, Nom, Etat FROM Objet WHERE Caisse_id = ? ORDER BY Type, Nom
        ");
        $stmt->execute([$caisse['id']]);
        $caisse['Contenu'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $caisse['nombre_objets'] = count($caisse['Contenu']);
    }
    
    ApiResponse::success([
        'data' => $caisses,
        'total' => count($caisses)
    ]);
    
} catch (PDOException $e) {
    ApiResponse::exception($e);
}
