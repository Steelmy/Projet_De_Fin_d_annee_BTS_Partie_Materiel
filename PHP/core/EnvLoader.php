<?php
/**
 * EnvLoader — Charge les variables d'environnement depuis un fichier .env
 * 
 * Principe SOLID : Single Responsibility — ne fait que le chargement des variables d'environnement.
 * Principe KISS : Simple parse ligne par ligne, pas de librairie externe.
 */
class EnvLoader
{
    private static bool $loaded = false;

    /**
     * Charge le fichier .env et peuple $_ENV et putenv()
     */
    public static function load(string $path): void
    {
        if (self::$loaded) return;

        if (!file_exists($path)) {
            throw new RuntimeException("Fichier .env non trouvé : $path");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Ignorer les commentaires
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Séparer clé=valeur
            $pos = strpos($line, '=');
            if ($pos === false) continue;

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            // Retirer les guillemets éventuels
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = $value;
            putenv("$key=$value");
        }

        self::$loaded = true;
    }

    /**
     * Récupère une variable d'environnement avec valeur par défaut
     */
    public static function get(string $key, string $default = ''): string
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}
