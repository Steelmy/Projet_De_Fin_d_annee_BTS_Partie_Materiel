<?php

class MonitorController
{
    private PDO $conn;
    private Logger $logger;

    public function __construct(PDO $conn, Logger $logger)
    {
        $this->conn = $conn;
        $this->logger = $logger;
    }

    public function health(): void
    {
        $health = [
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'checks' => []
        ];

        // Vérification connexion BDD
        try {
            $this->conn->query("SELECT 1");
            $health['checks']['database'] = ['status' => 'ok', 'message' => 'Connexion active'];
        } catch (Exception $e) {
            $health['status'] = 'degraded';
            $health['checks']['database'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // Espace disque
        $freeSpace = disk_free_space('/');
        $totalSpace = disk_total_space('/');
        $usedPercent = round((1 - $freeSpace / $totalSpace) * 100, 1);
        $health['checks']['disk'] = [
            'status' => $usedPercent > 90 ? 'warning' : 'ok',
            'used_percent' => $usedPercent,
            'free_gb' => round($freeSpace / (1024 * 1024 * 1024), 2)
        ];

        // Erreurs recentes
        $recentErrors = $this->logger->countRecentErrors(60);
        $health['checks']['errors'] = [
            'status' => $recentErrors > 10 ? 'warning' : 'ok',
            'count_last_hour' => $recentErrors
        ];

        if ($recentErrors > 10) {
            $health['status'] = 'degraded';
            $health['alerts'] = ["$recentErrors erreurs détectées dans la dernière heure"];
        }

        // Taille des logs
        $logDir = $this->logger->getLogDir();
        $logFile = $logDir . '/app-' . date('Y-m-d') . '.log';
        $health['checks']['logs'] = [
            'status' => 'ok',
            'today_file' => basename($logFile),
            'today_size_kb' => file_exists($logFile) ? round(filesize($logFile) / 1024, 2) : 0
        ];

        $this->logger->debug('Health check effectué');
        ApiResponse::success($health);
    }
}
