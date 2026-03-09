<?php
/**
 * Database — Gestion de la connexion PDO
 * 
 * Principe SOLID : Single Responsibility — ne gère que la connexion BDD.
 * Principe KISS : Une seule méthode statique pour obtenir la connexion.
 * Statelessness : Chaque requête HTTP recrée la connexion, pas de persistance entre requêtes.
 */
class Database
{
    private static ?PDO $connection = null;

    /**
     * Retourne une connexion PDO configurée via les variables d'environnement
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
     * Ferme la connexion (appelé en fin de requête si nécessaire)
     */
    public static function close(): void
    {
        self::$connection = null;
    }
}
