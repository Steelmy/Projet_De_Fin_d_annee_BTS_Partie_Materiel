<?php

/**
 * Contrôleur de health check : agrège l'état de la BDD, du disque,
 * du nombre d'erreurs récentes et de la taille du log courant.
 */
class MonitorController
{
    /** @var PDO Connexion PDO active. */
    private PDO $conn;

    /** @var Logger Logger applicatif partagé. */
    private Logger $logger;

    /**
     * @param PDO $conn Connexion PDO active.
     * @param Logger $logger Logger applicatif partagé.
     */
    public function __construct(PDO $conn, Logger $logger)
    {
        $this->conn = $conn;
        $this->logger = $logger;
    }

    /**
     * Calcule et renvoie l'état de santé de l'application.
     *
     * Statut global :
     *   - `ok` par défaut
     *   - `degraded` si la BDD est en erreur ou si > 10 erreurs sur la dernière heure
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function health(): void
    {
        $health = [
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'checks' => []
        ];

        try {
            $this->conn->query("SELECT 1");
            $health['checks']['database'] = ['status' => 'ok', 'message' => 'Connexion active'];
        } catch (Exception $e) {
            $health['status'] = 'degraded';
            $health['checks']['database'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        $freeSpace = disk_free_space('/');
        $totalSpace = disk_total_space('/');
        $usedPercent = round((1 - $freeSpace / $totalSpace) * 100, 1);
        $health['checks']['disk'] = [
            'status' => $usedPercent > 90 ? 'warning' : 'ok',
            'used_percent' => $usedPercent,
            'free_gb' => round($freeSpace / (1024 * 1024 * 1024), 2)
        ];

        $recentErrors = $this->logger->countRecentErrors(60);
        $health['checks']['errors'] = [
            'status' => $recentErrors > 10 ? 'warning' : 'ok',
            'count_last_hour' => $recentErrors
        ];

        if ($recentErrors > 10) {
            $health['status'] = 'degraded';
            $health['alerts'] = ["$recentErrors erreurs détectées dans la dernière heure"];
        }

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
