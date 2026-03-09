<?php
/**
 * Monitor — Endpoint de health check et monitoring
 * 
 * Retourne l'état de santé de l'application :
 * - Connexion BDD
 * - Espace disque
 * - Erreurs récentes dans les logs
 * - Dernier timestamp d'activité
 */
require_once 'db_connect.php';

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// 1. Vérification connexion BDD
try {
    $conn->query("SELECT 1");
    $health['checks']['database'] = ['status' => 'ok', 'message' => 'Connexion active'];
} catch (Exception $e) {
    $health['status'] = 'degraded';
    $health['checks']['database'] = ['status' => 'error', 'message' => $e->getMessage()];
}

// 2. Espace disque
$freeSpace = disk_free_space('/');
$totalSpace = disk_total_space('/');
$usedPercent = round((1 - $freeSpace / $totalSpace) * 100, 1);
$health['checks']['disk'] = [
    'status' => $usedPercent > 90 ? 'warning' : 'ok',
    'used_percent' => $usedPercent,
    'free_gb' => round($freeSpace / (1024 * 1024 * 1024), 2)
];

// 3. Erreurs récentes dans les logs (dernière heure)
$recentErrors = $logger->countRecentErrors(60);
$health['checks']['errors'] = [
    'status' => $recentErrors > 10 ? 'warning' : 'ok',
    'count_last_hour' => $recentErrors
];

// Alerte si trop d'erreurs
if ($recentErrors > 10) {
    $health['status'] = 'degraded';
    $health['alerts'] = ["$recentErrors erreurs détectées dans la dernière heure"];
}

// 4. Taille des logs
$logDir = $logger->getLogDir();
$logFile = $logDir . '/app-' . date('Y-m-d') . '.log';
$health['checks']['logs'] = [
    'status' => 'ok',
    'today_file' => basename($logFile),
    'today_size_kb' => file_exists($logFile) ? round(filesize($logFile) / 1024, 2) : 0
];

$logger->debug('Health check effectué');
ApiResponse::success($health);
