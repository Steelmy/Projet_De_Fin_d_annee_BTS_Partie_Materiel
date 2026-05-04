<?php
/**
 * Bootstrap API : charge l'infrastructure de base (cf. bootstrap.php)
 * puis expose la factory `createController()` aux endpoints.
 */

require_once __DIR__ . '/bootstrap.php';

$controllersDir = dirname(__DIR__, 2) . '/app/Controllers';

/**
 * Factory d'instanciation d'un contrôleur avec ses dépendances.
 *
 * Les contrôleurs listés dans `$needsLogger` reçoivent en plus la dépendance Logger.
 *
 * @param string $className Nom court de la classe contrôleur (ex. `ItemController`).
 * @return object Instance prête à l'emploi.
 */
function createController(string $className): object
{
    global $conn, $logger, $controllersDir;

    $file = $controllersDir . '/' . $className . '.php';
    require_once $file;

    $needsLogger = ['ItemController', 'BoxController', 'MonitorController'];

    if (in_array($className, $needsLogger)) {
        return new $className($conn, $logger);
    }

    return new $className($conn);
}
