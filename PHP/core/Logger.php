<?php

/**
 * Logger applicatif fichier avec rotation quotidienne.
 *
 * Niveaux : `DEBUG`, `INFO`, `WARNING`, `ERROR`.
 * Format : `[YYYY-MM-DD HH:MM:SS] [LEVEL] [endpoint] message {contexte JSON}`.
 * Fichier cible : `<logDir>/app-YYYY-MM-DD.log`.
 */
class Logger
{
    /** @var array<string, int> Niveaux ordonnés (gravité croissante). */
    private const LEVELS = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];

    /** @var string Dossier où sont écrits les fichiers de log. */
    private string $logDir;

    /** @var string Niveau minimum (en majuscules) à écrire. */
    private string $minLevel;

    /** @var string Nom du script appelant, ajouté à chaque ligne. */
    private string $endpoint;

    /**
     * @param string $logDir Dossier de logs (créé automatiquement s'il n'existe pas).
     * @param string $minLevel Niveau minimum à écrire (`DEBUG|INFO|WARNING|ERROR`).
     */
    public function __construct(string $logDir, string $minLevel = 'INFO')
    {
        $this->logDir = rtrim($logDir, '/');
        $this->minLevel = strtoupper($minLevel);
        $this->endpoint = basename($_SERVER['SCRIPT_NAME'] ?? 'cli');

        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    /**
     * Écrit une ligne de log si le niveau atteint le seuil minimum.
     *
     * @param string $level Niveau du message (`DEBUG|INFO|WARNING|ERROR`).
     * @param string $message Message à journaliser.
     * @param array<string, mixed> $context Contexte JSON-encodé en suffixe.
     * @return void
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $level = strtoupper($level);

        if (!isset(self::LEVELS[$level]) || self::LEVELS[$level] < self::LEVELS[$this->minLevel]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logLine = "[$timestamp] [$level] [{$this->endpoint}] $message$contextStr" . PHP_EOL;

        $logFile = $this->logDir . '/app-' . date('Y-m-d') . '.log';
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log de niveau DEBUG.
     *
     * @param string $message Message.
     * @param array<string, mixed> $context Contexte additionnel.
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Log de niveau INFO.
     *
     * @param string $message Message.
     * @param array<string, mixed> $context Contexte additionnel.
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * Log de niveau WARNING.
     *
     * @param string $message Message.
     * @param array<string, mixed> $context Contexte additionnel.
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    /**
     * Log de niveau ERROR.
     *
     * @param string $message Message.
     * @param array<string, mixed> $context Contexte additionnel.
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * Compte les occurrences `[ERROR]` dans le log du jour
     * dont le timestamp est dans la fenêtre `$minutes` la plus récente.
     *
     * @param int $minutes Largeur de la fenêtre temporelle, en minutes.
     * @return int Nombre d'erreurs récentes.
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

            preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches);
            if (!empty($matches[1]) && strtotime($matches[1]) >= $threshold) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Retourne le dossier de logs configuré.
     *
     * @return string Chemin absolu du dossier.
     */
    public function getLogDir(): string
    {
        return $this->logDir;
    }
}
