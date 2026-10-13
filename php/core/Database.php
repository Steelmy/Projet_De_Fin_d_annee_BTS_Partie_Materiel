<?php

/**
 * Gestion de la connexion PDO MySQL.
 *
 * La connexion est mémoïsée pour la durée de la requête HTTP courante :
 * elle n'est pas partagée entre requêtes (philosophie stateless).
 */
class Database
{
    /** @var PDO|null Connexion PDO partagée pour la requête courante. */
    private static ?PDO $connection = null;

    /**
     * Retourne la connexion PDO, en l'instanciant lors du premier appel.
     * Les paramètres sont lus depuis l'environnement (.env) via EnvLoader.
     *
     * @return PDO Connexion configurée en mode exception et fetch assoc.
     * @throws PDOException Si la connexion échoue.
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $host = EnvLoader::get('DB_HOST', 'localhost');
            $dbname = EnvLoader::get('DB_NAME', 'gestion_materiel_db');
            $username = EnvLoader::get('DB_USER', 'root');
            $password = EnvLoader::get('DB_PASS', '');

            self::$connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password
            );
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }

        return self::$connection;
    }

    /**
     * Ferme explicitement la connexion mémoïsée.
     *
     * @return void
     */
    public static function close(): void
    {
        self::$connection = null;
    }
}
