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

 * @param string $className Nom court de la classe contrôleur (ex. `ItemController`).
 * @return object Instance prête à l'emploi.
 */
function createController(string $className): object
{
    global $conn, $controllersDir;

    $file = $controllersDir . '/' . $className . '.php';
    require_once $file;


    return new $className($conn);
}
