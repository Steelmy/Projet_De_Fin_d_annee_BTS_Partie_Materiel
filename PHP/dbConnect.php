<?php
/**
 * dbConnect.php — Wrapper rétrocompatible
 * 
 * Ce fichier est conservé pour compatibilité avec les endpoints existants.
 * Il délègue au bootstrap.php qui gère :
 *   - Chargement des variables d'environnement (.env)
 *   - Initialisation du logger
 *   - Connexion PDO via Database::getConnection()
 *   - Headers JSON
 * 
 * Les credentials ne sont plus en dur ici (voir .env).
 */
require_once __DIR__ . '/core/bootstrap.php';
