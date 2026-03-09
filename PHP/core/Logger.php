<?php
/**
 * Logger — Système de logging avec rotation quotidienne
 * 
 * Principe SOLID : Single Responsibility — ne fait que du logging.
 * Principe KISS : Écriture fichier simple, pas de dépendance externe.
 * 
 * Niveaux : DEBUG, INFO, WARNING, ERROR
 * Format : [YYYY-MM-DD HH:MM:SS] [LEVEL] [endpoint] message {contexte JSON}
 */
class Logger
{
    private const LEVELS = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];

    private string $logDir;
    private string $minLevel;
    private string $endpoint;

    public function __construct(string $logDir, string $minLevel = 'INFO')
    {
        $this->logDir = rtrim($logDir, '/');
        $this->minLevel = strtoupper($minLevel);
        $this->endpoint = basename($_SERVER['SCRIPT_NAME'] ?? 'cli');

        // Créer le dossier de logs si nécessaire
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    /**
     * Log un message avec un niveau donné
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $level = strtoupper($level);

        // Vérifier le niveau minimum
        if (!isset(self::LEVELS[$level]) || self::LEVELS[$level] < self::LEVELS[$this->minLevel]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logLine = "[$timestamp] [$level] [{$this->endpoint}] $message$contextStr" . PHP_EOL;

        $logFile = $this->logDir . '/app-' . date('Y-m-d') . '.log';
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * Retourne le nombre d'erreurs dans les logs récents (monitoring)
     */
    public function countRecentErrors(int $minutes = 60): int
    {
        $logFile = $this->logDir . '/app-' . date('Y-m-d') . '.log';
        if (!file_exists($logFile)) return 0;

        $count = 0;
        $threshold = time() - ($minutes * 60);
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos($line, '[ERROR]') === false) continue;

            // Extraire le timestamp
            preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches);
            if (!empty($matches[1]) && strtotime($matches[1]) >= $threshold) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Retourne le chemin du répertoire de logs
     */
    public function getLogDir(): string
    {
        return $this->logDir;
    }
}
