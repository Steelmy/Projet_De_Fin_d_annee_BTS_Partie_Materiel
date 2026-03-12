<?php
// Endpoint : Vérification de code-barre
require_once 'dbConnect.php';

try {
    $code_barre = isset($_GET['code_barre']) ? trim($_GET['code_barre']) : '';

    if (empty($code_barre)) {
        ApiResponse::error('Code-barre requis');
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Objet WHERE Code_bar = :code_barre");
    $stmt->execute([':code_barre' => $code_barre]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    ApiResponse::success([
        'exists' => ($result['count'] > 0),
        'code_barre' => $code_barre
    ]);

} catch (PDOException $e) {
    ApiResponse::exception($e);
}
