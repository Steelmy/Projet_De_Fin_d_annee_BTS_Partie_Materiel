<?php

/**
 * Lecture et bornage des paramètres de pagination (`page`, `per_page`) d'une requête GET.
 */
class Pagination
{
    /**
     * Lit `page` et `per_page` depuis `$_GET`, bornés à des valeurs sûres.
     *
     * @param int $defaultPerPage Taille de page si `per_page` est absent.
     * @param int $maxPerPage Taille de page maximale acceptée.
     * @return array{0:int, 1:int} `[page, perPage]`, page >= 1.
     */
    public static function fromRequest(int $defaultPerPage, int $maxPerPage): array
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : $defaultPerPage;

        return [max(1, $page), min(max(1, $perPage), $maxPerPage)];
    }

    /**
     * Ramène la page demandée sur la dernière page existante.
     *
     * @param int $page Page demandée.
     * @param int $total Nombre total de lignes.
     * @param int $perPage Taille de page.
     * @return int Page valide (1 si aucune ligne).
     */
    public static function clampPage(int $page, int $total, int $perPage): int
    {
        return min($page, max(1, (int) ceil($total / $perPage)));
    }
}
