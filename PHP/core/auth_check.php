<?php
session_start();

$isHeartbeat = isset($_SERVER['HTTP_X_SESSION_GUARD']) && $_SERVER['HTTP_X_SESSION_GUARD'] === '1';

// 1. Vérification de l'existence de la session admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: /login.php"); // Chemin absolu pour fonctionner de partout
    exit();
}

// 2. Gestion de l'expiration par inactivité (15 minutes)
$delai_inactivite = 900; 
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $delai_inactivite)) {
    session_unset();
    session_destroy();
    header("Location: /login.php?msg=expire");
    exit();
}

// 3. Mise à jour du timestamp d'activité
if (!$isHeartbeat) {
    $_SESSION['last_activity'] = time();
} else {
    // Mode Heartbeat: on s'arrête ici avec un succès, sans mettre à jour l'activité
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit();
}
?>
