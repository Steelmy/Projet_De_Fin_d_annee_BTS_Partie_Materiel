<?php

/**
 * Charge les variables d'environnement depuis un fichier `.env`
 * et les expose via `$_ENV`, `getenv()` et la méthode `get`.
 *
 * Le fichier `.env` est parsé une seule fois (memoïsation par flag statique).
 */
class EnvLoader
{
    /** @var bool true si `load()` a déjà été exécuté pendant cette requête. */
    private static bool $loaded = false;

    /**
     * Parse un fichier `.env` (lignes `KEY=VALUE`, commentaires `#` ignorés)
     * et peuple `$_ENV` ainsi que `putenv`. No-op après le premier appel.
     *
     * @param string $path Chemin absolu du fichier `.env`.
     * @return void
     * @throws RuntimeException Si le fichier n'existe pas.
     */
    public static function load(string $path): void
    {
        if (self::$loaded) return;

        if (!file_exists($path)) {
            throw new RuntimeException("Fichier .env non trouvé : $path");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            $pos = strpos($line, '=');
            if ($pos === false) continue;

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

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
     * Récupère une variable d'environnement avec valeur par défaut.
     *
     * @param string $key Nom de la variable.
     * @param string $default Valeur retournée si la variable est absente ou vide.
     * @return string Valeur de la variable ou `$default`.
     */
    public static function get(string $key, string $default = ''): string
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}
