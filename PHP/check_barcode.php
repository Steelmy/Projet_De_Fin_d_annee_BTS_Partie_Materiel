<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

try {
    $code_barre = isset($_GET['code_barre']) ? trim($_GET['code_barre']) : '';

    if (empty($code_barre)) {
        echo json_encode(['success' => false, 'message' => 'Code-barre requis']);
        exit;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Objet WHERE Code_bar = :code_barre");
    $stmt->execute([':code_barre' => $code_barre]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $exists = ($result['count'] > 0);

    echo json_encode([
        'success' => true,
        'exists' => $exists,
        'code_barre' => $code_barre
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
