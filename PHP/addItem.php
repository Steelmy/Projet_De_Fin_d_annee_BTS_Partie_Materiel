<?php
// 1. Initialise la connexion à la base de données et charge les classes nécessaires.
require_once __DIR__ . '/core/bootstrap.api.php';
// 2. Crée une instance du contrôleur ItemController et exécute sa méthode store().
createController('ItemController')->store();