<?php
require_once 'db_connect.php';

/**
 * Génère un code-barre unique qui n'existe pas dans la base de données
 * 
 * @param PDO $conn Connexion à la base de données
 * @param int $length Longueur du code-barre (par défaut 13, standard EAN-13)
 * @param int $maxAttempts Nombre maximum de tentatives avant d'abandonner
 * @return array Résultat avec le code-barre généré ou une erreur
 */
function generateUniqueBarcode($conn, $length = 13, $maxAttempts = 100) {
    $attempts = 0;
    
    while ($attempts < $maxAttempts) {
        // Générer un code-barre aléatoire
        $barcode = '';
        for ($i = 0; $i < $length; $i++) {
            $barcode .= mt_rand(0, 9);
        }
        
        // Vérifier si le code-barre existe déjà
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Objet WHERE Code_bar = :code_barre");
        $stmt->execute([':code_barre' => $barcode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si le code-barre n'existe pas, on le retourne
        if ($result['count'] == 0) {
            return [
                'success' => true,
                'code_barre' => $barcode,
                'attempts' => $attempts + 1
            ];
        }
        
        $attempts++;
    }
    
    // Si on a épuisé toutes les tentatives
    return [
        'success' => false,
        'message' => 'Impossible de générer un code-barre unique après ' . $maxAttempts . ' tentatives',
        'attempts' => $attempts
    ];
}

try {
    // Récupérer le nombre de codes-barres à générer (par défaut 1)
    $count = isset($_GET['count']) ? intval($_GET['count']) : 1;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 13;
    
    // Limiter le nombre de codes-barres à générer
    if ($count < 1 || $count > 100) {
        echo json_encode([
            'success' => false,
            'message' => 'Le nombre de codes-barres doit être entre 1 et 100'
        ]);
        exit;
    }
    
    // Limiter la longueur du code-barre
    if ($length < 8 || $length > 20) {
        echo json_encode([
            'success' => false,
            'message' => 'La longueur du code-barre doit être entre 8 et 20'
        ]);
        exit;
    }
    
    $barcodes = [];
    $totalAttempts = 0;
    
    for ($i = 0; $i < $count; $i++) {
        $result = generateUniqueBarcode($conn, $length);
        
        if (!$result['success']) {
            echo json_encode([
                'success' => false,
                'message' => $result['message'],
                'generated' => $i,
                'total_attempts' => $totalAttempts
            ]);
            exit;
        }
        
        $barcodes[] = $result['code_barre'];
        $totalAttempts += $result['attempts'];
    }
    
    echo json_encode([
        'success' => true,
        'barcodes' => $barcodes,
        'count' => count($barcodes),
        'total_attempts' => $totalAttempts
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
}

$conn = null;
?>
