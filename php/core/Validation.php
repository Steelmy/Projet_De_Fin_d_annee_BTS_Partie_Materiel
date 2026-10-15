<?php

class Validation
{
    /** Taille des colonnes VARCHAR(100) `nom_type`, `nom_sous_type` et `nom_reference`. */
    public const NAME_MAX_LENGTH = 100;

    /** Limite choisie pour les commentaires (la colonne TEXT accepte 65 535 octets). */
    public const COMMENT_MAX_LENGTH = 2000;

    /**
     * Refuse la requête (HTTP 400) si un champ dépasse `$max` caractères.
     * Compte en caractères et non en octets, comme MariaDB pour un VARCHAR utf8mb4.
     *
     * @param array<string, string> $fields Valeurs indexées par le libellé affiché à l'utilisateur.
     */
    public static function maxLength(array $fields, int $max): void
    {
        foreach ($fields as $label => $value) {
            if (mb_strlen($value, 'UTF-8') > $max) {
                ApiResponse::error("Le champ « $label » ne doit pas dépasser $max caractères.");
            }
        }
    }
}
