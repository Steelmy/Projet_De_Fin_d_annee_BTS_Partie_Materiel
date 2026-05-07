<?php
/**
 * Bootstrap de base pour les endpoints API.
 *
 * Initialise headers JSON, vérifie la session admin (via le projet voisin
 * `IHM_admin/auth_check.php`), charge `.env`, instancie `$conn`.
 *
 * Variables exposées au fichier appelant :
 *   - `$conn` : connexion PDO active.
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once $_SERVER['DOCUMENT_ROOT'] . '/IHM_admin/auth_check.php';

require_once __DIR__ . '/EnvLoader.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ApiResponse.php';

$envPath = dirname(__DIR__, 2) . '/.env';
if (file_exists($envPath)) {
    EnvLoader::load($envPath);
}



try {
    $conn = Database::getConnection();
} catch (PDOException $e) {
    ApiResponse::error('Erreur de connexion à la base de données', 500);
}

