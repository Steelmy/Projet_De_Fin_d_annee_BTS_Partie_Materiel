<?php
// Endpoint : Détails d'une caisse
require_once 'dbConnect.php';

try {
    $nom = isset($_GET['nom']) ? trim($_GET['nom']) : '';
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (empty($nom) && $id === 0) {
        ApiResponse::error('Nom ou ID de la caisse requis');
    }
    
    // Récupérer les détails de la caisse
    if ($id > 0) {
        $stmt = $conn->prepare("
            SELECT c.id, c.Nom, c.Etat, c.created_at, c.updated_at, c.Emprunteur_id,
                   u.Prénom, u.Nom as user_nom
            FROM Caisse c
            LEFT JOIN utilisateurs u ON c.Emprunteur_id = u.id
            WHERE c.id = :id
        ");
        $stmt->execute([':id' => $id]);
    } else {
        $stmt = $conn->prepare("
            SELECT c.id, c.Nom, c.Etat, c.created_at, c.updated_at, c.Emprunteur_id,
                   u.Prénom, u.Nom as user_nom
            FROM Caisse c
            LEFT JOIN utilisateurs u ON c.Emprunteur_id = u.id
            WHERE c.Nom = :nom
        ");
        $stmt->execute([':nom' => $nom]);
    }
    
    $caisse = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$caisse) {
        ApiResponse::error('Caisse non trouvée');
    }
    
    // Récupérer les objets de la caisse
    $stmtObj = $conn->prepare("
        SELECT id, Code_bar, Type, Nom, Etat FROM Objet WHERE Caisse_id = :id ORDER BY Type, Nom
    ");
    $stmtObj->execute([':id' => $caisse['id']]);
    $contenu = $stmtObj->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'caisse' => [
            'id' => $caisse['id'],
            'nom' => $caisse['Nom'],
            'contenu' => $contenu,
            'nombre_objets' => count($contenu),
            'etat' => $caisse['Etat'],
            'created_at' => $caisse['created_at'],
            'updated_at' => $caisse['updated_at'],
            'emprunteur_id' => $caisse['Emprunteur_id']
        ]
    ];
    
    // Ajouter les infos utilisateur si applicable
    if ($caisse['Etat'] !== 'disponible' && $caisse['Prénom']) {
        $response['caisse']['utilisateur'] = [
            'id' => $caisse['Emprunteur_id'],
            'nom_complet' => $caisse['Prénom'] . ' ' . $caisse['user_nom']
        ];
    }
    
    ApiResponse::success($response);
    
} catch (PDOException $e) {
    ApiResponse::exception($e);
}
