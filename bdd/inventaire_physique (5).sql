-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 12 mars 2026 à 13:20
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `inventaire_physique`
--
CREATE DATABASE IF NOT EXISTS `inventaire_physique` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `inventaire_physique`;

-- --------------------------------------------------------

--
-- Structure de la table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`) VALUES
(1, 'admin', '$2y$10$l9XgAysNx7iXKm65R.s8ZOa4TzKZoVRIvA5xGgfaQzRnEJXqo8oZu');

-- --------------------------------------------------------

--
-- Structure de la table `caisses`
--

DROP TABLE IF EXISTS `caisses`;
CREATE TABLE IF NOT EXISTS `caisses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Etat` enum('disponible','réservé','emprunté') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'disponible',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `Emprunteur_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_caisse_emprunteur_new` (`Emprunteur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `types`
--

DROP TABLE IF EXISTS `types`;
CREATE TABLE IF NOT EXISTS `types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `types`
--

INSERT INTO `types` (`id`, `nom_type`) VALUES
(1, 'Photo'),
(2, 'Mesure');

-- --------------------------------------------------------

--
-- Structure de la table `sous_types`
--

DROP TABLE IF EXISTS `sous_types`;
CREATE TABLE IF NOT EXISTS `sous_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_type` int NOT NULL,
  `nom_sous_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_sous_type_type` (`id_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sous_types`
--

INSERT INTO `sous_types` (`id`, `id_type`, `nom_sous_type`) VALUES
(1, 1, 'Non défini'),
(2, 2, 'Non défini');

-- --------------------------------------------------------

--
-- Structure de la table `noms_references`
--

DROP TABLE IF EXISTS `noms_references`;
CREATE TABLE IF NOT EXISTS `noms_references` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_sous_type` int NOT NULL,
  `nom_reference` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_nom_sous_type` (`id_sous_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `noms_references`
--

INSERT INTO `noms_references` (`id`, `id_sous_type`, `nom_reference`) VALUES
(1, 1, 'Appareil Sony A7'),
(2, 2, 'Oscilloscope Rigol');

-- --------------------------------------------------------

--
-- Structure de la table `historique`
--

DROP TABLE IF EXISTS `historique`;
CREATE TABLE IF NOT EXISTS `historique` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_materiel` int DEFAULT NULL,
  `id_caisse` int DEFAULT NULL,
  `id_utilisateur` int DEFAULT NULL,
  `Date_start` datetime DEFAULT NULL,
  `Date_end` datetime NOT NULL,
  `Date_retour_reelle` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `retard` int NOT NULL,
  `nom_manuel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `objet_manuel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caisse_manuel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_h_materiel` (`id_materiel`),
  KEY `fk_h_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `historique`
--

INSERT INTO `historique` (`id`, `id_materiel`, `id_caisse`, `id_utilisateur`, `Date_start`, `Date_end`, `Date_retour_reelle`, `created_at`, `retard`, `nom_manuel`, `objet_manuel`, `caisse_manuel`) VALUES
(2, 11, NULL, 2, '2026-03-04 14:00:00', '2026-03-05 08:00:00', NULL, '2026-03-05 11:52:11', 4, NULL, NULL, NULL),
(9, NULL, NULL, 3, '2026-03-09 10:43:00', '2026-03-04 10:43:00', NULL, '2026-03-09 10:44:00', 0, NULL, 'god', NULL),
(21, NULL, NULL, 16, '2026-03-12 11:45:00', '2026-03-15 15:48:00', NULL, '2026-03-12 11:45:59', 0, NULL, 'marteau', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `objets`
--

DROP TABLE IF EXISTS `objets`;
CREATE TABLE IF NOT EXISTS `objets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Code_bar` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_nom_reference` int NOT NULL,
  `Nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Etat` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `Emprunteur_id` int DEFAULT NULL,
  `Caisse_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Code_bar` (`Code_bar`),
  KEY `fk_objet_nom_reference` (`id_nom_reference`),
  KEY `fk_objet_emprunteur` (`Emprunteur_id`),
  KEY `fk_objet_caisse` (`Caisse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `objets`
--

INSERT INTO `objets` (`id`, `Code_bar`, `id_nom_reference`, `Etat`, `created_at`, `updated_at`, `Emprunteur_id`) VALUES
(10, '123456765432', 1, 'emprunté', '2026-03-05 11:52:00', '2026-03-05 11:52:00', NULL),
(11, '854345678865', 2, 'emprunté', '2026-03-05 11:52:00', '2026-03-05 11:52:00', 2);

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `RFID_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Prénom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pin_code` char(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Crea_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `RFID_code` (`RFID_code`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `RFID_code`, `Nom`, `Prénom`, `pin_code`, `Crea_date`) VALUES
(2, 'RFID-002', 'Lefebvre', 'Marie', '5678', '2026-03-05 11:51:47'),
(3, NULL, 'lefèvre', 'jérémy', '', '2026-03-09 10:44:00'),
(16, 'AUTO_a1a6101a', 'Test3', 'Test', '', '2026-03-12 11:45:59');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `caisses`
--
ALTER TABLE `caisses`
  ADD CONSTRAINT `fk_caisse_emprunteur` FOREIGN KEY (`Emprunteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_caisse_emprunteur_new` FOREIGN KEY (`Emprunteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `historique`
--
ALTER TABLE `historique`
  ADD CONSTRAINT `fk_h_materiel` FOREIGN KEY (`id_materiel`) REFERENCES `objets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_h_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `noms_references`
--
ALTER TABLE `noms_references`
  ADD CONSTRAINT `fk_nom_sous_type` FOREIGN KEY (`id_sous_type`) REFERENCES `sous_types` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `objets`
--
ALTER TABLE `objets`
  ADD CONSTRAINT `fk_objet_caisse` FOREIGN KEY (`Caisse_id`) REFERENCES `caisses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_objet_emprunteur` FOREIGN KEY (`Emprunteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_objet_nom_reference` FOREIGN KEY (`id_nom_reference`) REFERENCES `noms_references` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sous_types`
--
ALTER TABLE `sous_types`
  ADD CONSTRAINT `fk_sous_type_type` FOREIGN KEY (`id_type`) REFERENCES `types` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
