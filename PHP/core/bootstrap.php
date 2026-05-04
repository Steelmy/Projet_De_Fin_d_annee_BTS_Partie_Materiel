<?php
/**
 * Bootstrap de base pour les endpoints API.
 *
 * Initialise headers JSON, vérifie la session admin (via le projet voisin
 * `IHM_admin/auth_check.php`), charge `.env`, instancie `$logger` et `$conn`,
 * puis journalise la requête entrante en niveau DEBUG.
 *
 * Variables exposées au fichier appelant :
 *   - `$logger` : instance Logger.
 *   - `$conn` : connexion PDO active.
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once $_SERVER['DOCUMENT_ROOT'] . '/IHM_admin/auth_check.php';

require_once __DIR__ . '/EnvLoader.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ApiResponse.php';

$envPath = dirname(__DIR__, 2) . '/.env';
if (file_exists($envPath)) {
    EnvLoader::load($envPath);
}

$logDir = dirname(__DIR__, 2) . '/logs';
$logger = new Logger($logDir, EnvLoader::get('LOG_LEVEL', 'INFO'));

try {
    $conn = Database::getConnection();
} catch (PDOException $e) {
    $logger->error('Échec connexion BDD', ['error' => $e->getMessage()]);
    ApiResponse::error('Erreur de connexion à la base de données', 500);
}

$logger->debug('Requête entrante', [
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
    'endpoint' => basename($_SERVER['SCRIPT_NAME'] ?? 'unknown')
]);
