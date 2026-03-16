<?php
/**
 * Bootstrap API — Initialise l'infrastructure et instancie les controllers
 *
 * Ce fichier est inclus par les endpoints API (php/*.php).
 * Il charge le bootstrap de base puis rend disponibles les controllers.
 */
require_once __DIR__ . '/bootstrap.php';

// Autoload des controllers
$controllersDir = dirname(__DIR__, 2) . '/app/Controllers';

/**
 * Factory pour instancier un controller avec ses dépendances
 */
function createController(string $className): object
{
    global $conn, $logger, $controllersDir;

    $file = $controllersDir . '/' . $className . '.php';
    require_once $file;

    // Les controllers qui nécessitent le logger
    $needsLogger = ['ItemController', 'BoxController', 'MonitorController'];

    if (in_array($className, $needsLogger)) {
        return new $className($conn, $logger);
    }

    return new $className($conn);
}
