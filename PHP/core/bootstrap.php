<?php
/**
 * Bootstrap — Point d'entrée unique pour tous les endpoints PHP
 * 
 * Principe DRY : Remplace le combo header() + require_once 'dbConnect.php' 
 *                présent dans chaque fichier PHP.
 * Principe KISS : Un seul require_once pour initialiser toute l'infrastructure.
 * Statelessness : Aucun état persistant entre les requêtes HTTP.
 */

// Headers JSON
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Charger les classes core
require_once __DIR__ . '/EnvLoader.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ApiResponse.php';

// Charger les variables d'environnement
$envPath = dirname(__DIR__, 2) . '/.env';
if (file_exists($envPath)) {
    EnvLoader::load($envPath);
}

// Initialiser le logger
$logDir = dirname(__DIR__, 2) . '/logs';
$logger = new Logger($logDir, EnvLoader::get('LOG_LEVEL', 'INFO'));

// Initialiser la connexion BDD
try {
    $conn = Database::getConnection();
} catch (PDOException $e) {
    $logger->error('Échec connexion BDD', ['error' => $e->getMessage()]);
    ApiResponse::error('Erreur de connexion à la base de données', 500);
}

// Log de la requête entrante (niveau DEBUG uniquement)
$logger->debug('Requête entrante', [
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
    'endpoint' => basename($_SERVER['SCRIPT_NAME'] ?? 'unknown')
]);
