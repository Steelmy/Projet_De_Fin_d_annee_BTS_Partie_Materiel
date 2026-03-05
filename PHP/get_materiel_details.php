<?php
header('Content-Type: application/json');

// Connexion à la base de données via le fichier central
require_once 'db_connect.php';

try {

    // Récupérer le code-barre
    $codeBarre = isset($_GET['code_barre']) ? trim($_GET['code_barre']) : '';

    if (empty($codeBarre)) {
        echo json_encode([
            'success' => false,
            'message' => 'Code-barre requis'
        ]);
        exit;
    }

    // Récupérer les détails de l'objet
    $stmt = $conn->prepare("
        SELECT 
            o.id,
            o.Code_bar,
            o.Type,
            o.Nom,
            o.Etat,
            o.Emprunteur_id,
            o.Caisse_id,
            o.created_at,
            o.updated_at,
            u.Nom as user_nom,
            u.Prénom as user_prenom,
            c.Nom as caisse_nom
        FROM Objet o
        LEFT JOIN utilisateurs u ON o.Emprunteur_id = u.id
        LEFT JOIN Caisse c ON o.Caisse_id = c.id
        WHERE o.Code_bar = :code_barre
    ");
    
    $stmt->execute([':code_barre' => $codeBarre]);
    $objet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$objet) {
        echo json_encode([
            'success' => false,
            'message' => 'Objet non trouvé'
        ]);
        exit;
    }

    // Formater la réponse
    $response = [
        'success' => true,
        'materiel' => [
            'id' => $objet['id'],
            'code_barre' => $objet['Code_bar'],
            'type_materiel' => $objet['Type'],
            'nom_materiel' => $objet['Nom'],
            'etat' => $objet['Etat'],
            'caisse_id' => $objet['Caisse_id'],
            'caisse_nom' => $objet['caisse_nom'],
            'created_at' => $objet['created_at'],
            'updated_at' => $objet['updated_at']
        ]
    ];

    // Ajouter les infos utilisateur si applicable
    if ($objet['Etat'] !== 'disponible' && $objet['user_nom']) {
        $response['materiel']['utilisateur'] = [
            'id' => $objet['Emprunteur_id'],
            'nom_complet' => $objet['user_prenom'] . ' ' . $objet['user_nom']
        ];
    }

    echo json_encode($response);

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn = null;
?>
