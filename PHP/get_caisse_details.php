<?php
header('Content-Type: application/json');

require_once 'db_connect.php';

try {
    // Récupérer le nom de la caisse
    $nom = isset($_GET['nom']) ? trim($_GET['nom']) : '';
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (empty($nom) && $id === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Nom ou ID de la caisse requis'
        ]);
        exit;
    }
    
    // Récupérer les détails de la caisse
    if ($id > 0) {
        $stmt = $conn->prepare("
            SELECT 
                c.id,
                c.Nom,
                c.Etat,
                c.created_at,
                c.updated_at,
                c.Emprunteur_id,
                u.Prénom,
                u.Nom as user_nom
            FROM Caisse c
            LEFT JOIN utilisateurs u ON c.Emprunteur_id = u.id
            WHERE c.id = :id
        ");
        $stmt->execute([':id' => $id]);
    } else {
        $stmt = $conn->prepare("
            SELECT 
                c.id,
                c.Nom,
                c.Etat,
                c.created_at,
                c.updated_at,
                c.Emprunteur_id,
                u.Prénom,
                u.Nom as user_nom
            FROM Caisse c
            LEFT JOIN utilisateurs u ON c.Emprunteur_id = u.id
            WHERE c.Nom = :nom
        ");
        $stmt->execute([':nom' => $nom]);
    }
    
    $caisse = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$caisse) {
        echo json_encode([
            'success' => false,
            'message' => 'Caisse non trouvée'
        ]);
        exit;
    }
    
    // Récupérer les objets de la caisse
    $stmtObj = $conn->prepare("
        SELECT id, Code_bar, Type, Nom, Etat 
        FROM Objet 
        WHERE Caisse_id = :id
        ORDER BY Type, Nom
    ");
    $stmtObj->execute([':id' => $caisse['id']]);
    $caisse['Contenu'] = $stmtObj->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater la réponse
    $response = [
        'success' => true,
        'caisse' => [
            'id' => $caisse['id'],
            'nom' => $caisse['Nom'],
            'contenu' => $caisse['Contenu'],
            'nombre_objets' => count($caisse['Contenu']),
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
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
