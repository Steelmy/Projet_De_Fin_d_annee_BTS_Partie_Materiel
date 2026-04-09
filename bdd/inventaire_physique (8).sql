-- phpMyAdmin SQL Dump
-- version 5.2.2deb1+deb13u1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 09, 2026 at 09:00 AM
-- Server version: 11.8.6-MariaDB-0+deb13u1 from Debian
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventaire_physique`
--
CREATE DATABASE IF NOT EXISTS `inventaire_physique` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `inventaire_physique`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`) VALUES
(1, 'admin', '$2y$10$l9XgAysNx7iXKm65R.s8ZOa4TzKZoVRIvA5xGgfaQzRnEJXqo8oZu');

-- --------------------------------------------------------

--
-- Table structure for table `caisses`
--

CREATE TABLE `caisses` (
  `id` int(11) NOT NULL,
  `Nom` varchar(150) NOT NULL,
  `Etat` enum('disponible','réservé','emprunté') NOT NULL DEFAULT 'disponible',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Emprunteur_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `commentaires`
--

CREATE TABLE `commentaires` (
  `id` int(11) NOT NULL,
  `com_user` text NOT NULL,
  `com_admin` text NOT NULL,
  `created_at` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `historique`
--

CREATE TABLE `historique` (
  `id` int(11) NOT NULL,
  `id_materiel` int(11) DEFAULT NULL,
  `id_caisse` int(11) DEFAULT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `Date_start` datetime DEFAULT NULL,
  `Date_end` datetime NOT NULL,
  `Date_retour_reelle` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `retard` int(11) NOT NULL,
  `objet_manuel` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `historique`
--

INSERT INTO `historique` (`id`, `id_materiel`, `id_caisse`, `id_utilisateur`, `Date_start`, `Date_end`, `Date_retour_reelle`, `created_at`, `retard`, `objet_manuel`) VALUES
(30, NULL, NULL, 19, '2026-04-02 14:41:00', '2026-04-03 14:41:00', NULL, '2026-04-02 14:41:54', 0, NULL),
(31, NULL, NULL, 19, '2026-04-02 14:58:00', '2026-04-03 14:58:00', NULL, '2026-04-02 14:58:22', 0, NULL),
(34, 18, NULL, 19, '2026-04-03 16:23:05', '2026-04-04 14:23:04', '2026-04-03 16:24:04', '2026-04-03 16:23:05', 0, NULL),
(35, 18, NULL, 19, '2026-04-03 16:34:44', '2026-04-04 14:34:44', NULL, '2026-04-03 16:34:44', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `noms_references`
--

CREATE TABLE `noms_references` (
  `id` int(11) NOT NULL,
  `id_sous_type` int(11) NOT NULL,
  `nom_reference` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `noms_references`
--

INSERT INTO `noms_references` (`id`, `id_sous_type`, `nom_reference`, `created_at`) VALUES
(5, 5, 'Perceuse À Percussion Makita', '2026-03-30 13:28:06'),
(6, 5, 'Scie Sauteuse Bosch Professional', '2026-03-30 13:28:29'),
(7, 6, '5M Stanley', '2026-03-30 13:30:00'),
(9, 8, 'Clé Facom 15 Pouces', '2026-04-02 14:07:17'),
(10, 9, 'Cruciforme', '2026-04-02 14:13:38'),
(11, 6, '6M', '2026-04-03 16:27:26');

-- --------------------------------------------------------

--
-- Table structure for table `objets`
--

CREATE TABLE `objets` (
  `id` int(11) NOT NULL,
  `Code_bar` varchar(100) NOT NULL,
  `id_nom_reference` int(11) NOT NULL,
  `Nom` varchar(255) DEFAULT NULL,
  `Etat` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Emprunteur_id` int(11) DEFAULT NULL,
  `Caisse_id` int(11) DEFAULT NULL,
  `id_com` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `objets`
--

INSERT INTO `objets` (`id`, `Code_bar`, `id_nom_reference`, `Nom`, `Etat`, `created_at`, `updated_at`, `Emprunteur_id`, `Caisse_id`, `id_com`) VALUES
(14, '2040500500141', 5, 'Perceuse À Percussion Makita', 'réservé', '2026-03-30 13:31:54', '2026-04-03 16:29:22', 19, NULL, NULL),
(15, '2040500600155', 6, 'Scie Sauteuse Bosch Professional', 'disponible', '2026-03-30 13:32:08', '2026-03-30 13:32:08', NULL, NULL, NULL),
(16, '2050600700167', 7, '5M Stanley', 'disponible', '2026-03-30 13:32:18', '2026-03-30 13:32:18', NULL, NULL, NULL),
(18, '2070800900181', 9, 'Clé Facom 15 Pouces', 'emprunté', '2026-04-02 14:07:53', '2026-04-03 16:34:44', NULL, NULL, NULL),
(19, '2070901000193', 10, 'Cruciforme', 'disponible', '2026-04-02 14:13:54', '2026-04-02 16:17:19', 19, NULL, NULL),
(20, '2070901000209', 10, 'Cruciforme', 'disponible', '2026-04-02 14:13:54', '2026-04-02 14:13:54', NULL, NULL, NULL),
(21, '2070901000216', 10, 'Cruciforme', 'disponible', '2026-04-02 14:13:54', '2026-04-02 14:13:54', NULL, NULL, NULL),
(22, '2070901000223', 10, 'Cruciforme', 'disponible', '2026-04-02 14:13:54', '2026-04-02 14:13:54', NULL, NULL, NULL),
(23, '2070901000230', 10, 'Cruciforme', 'disponible', '2026-04-02 14:13:54', '2026-04-02 14:13:54', NULL, NULL, NULL),
(24, '2070901000247', 10, 'Cruciforme', 'disponible', '2026-04-02 14:13:54', '2026-04-02 14:13:54', NULL, NULL, NULL),
(25, '2070901000254', 10, 'Cruciforme', 'disponible', '2026-04-02 14:13:54', '2026-04-02 14:13:54', NULL, NULL, NULL),
(26, '2070901000261', 10, 'Cruciforme', 'disponible', '2026-04-02 14:13:54', '2026-04-02 14:13:54', NULL, NULL, NULL),
(27, '2070901000278', 10, 'Cruciforme', 'disponible', '2026-04-02 14:13:54', '2026-04-02 14:13:54', NULL, NULL, NULL),
(28, '2070901000285', 10, 'Cruciforme', 'disponible', '2026-04-02 14:13:54', '2026-04-02 14:13:54', NULL, NULL, NULL),
(29, '2050601100294', 11, '6M', 'disponible', '2026-04-03 16:27:53', '2026-04-03 16:27:53', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sous_types`
--

CREATE TABLE `sous_types` (
  `id` int(11) NOT NULL,
  `id_type` int(11) NOT NULL,
  `nom_sous_type` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sous_types`
--

INSERT INTO `sous_types` (`id`, `id_type`, `nom_sous_type`, `created_at`) VALUES
(5, 4, 'Non défini', '2026-03-30 13:28:06'),
(6, 5, 'Mètre Ruban', '2026-03-30 13:30:00'),
(8, 7, 'Clé À Molette', '2026-04-02 14:07:17'),
(9, 7, 'Tournevis', '2026-04-02 14:13:38');

-- --------------------------------------------------------

--
-- Table structure for table `types`
--

CREATE TABLE `types` (
  `id` int(11) NOT NULL,
  `nom_type` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `types`
--

INSERT INTO `types` (`id`, `nom_type`, `created_at`) VALUES
(4, 'Électroportatif', '2026-03-30 13:28:06'),
(5, 'Mesure', '2026-03-30 13:30:00'),
(7, 'Outil À Main', '2026-04-02 14:07:17');

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `RFID_code` varchar(255) DEFAULT NULL,
  `Nom` varchar(100) NOT NULL,
  `Prénom` varchar(100) NOT NULL,
  `pin_code` char(4) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `RFID_code`, `Nom`, `Prénom`, `pin_code`, `created_at`) VALUES
(19, '041770BA4C1C90', 'Baillemont', 'Enzo', '0201', '2026-02-12 15:59:30'),
(20, '04121FF25C6A80', 'Lefevre', 'Jérémy', '2006', '2026-03-16 14:17:49'),
(21, '04133C5AA66F80', 'Nzau-lukundakio', 'Jérémie', '6767', '2026-03-16 16:52:09'),
(22, '045C4E5AA66F80', 'Collot', 'Kais', '3031', '2026-03-16 17:06:10'),
(23, '5CDEFFDE', 'Berry', 'Liam', '6113', '2026-03-19 11:46:03'),
(24, 'D92906B3', 'Legrand', 'Jean-pierre', '1234', '2026-04-02 11:03:07'),
(25, '044AB47A4C1C90', 'Watteel', 'Alban', '0000', '2026-04-02 14:17:10'),
(26, 'BACC88BE', 'Brognard', 'Théo', '0987', '2026-04-03 16:25:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_username` (`username`);

--
-- Indexes for table `caisses`
--
ALTER TABLE `caisses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_caisse_emprunteur_new` (`Emprunteur_id`);

--
-- Indexes for table `commentaires`
--
ALTER TABLE `commentaires`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `historique`
--
ALTER TABLE `historique`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_h_materiel` (`id_materiel`),
  ADD KEY `fk_h_utilisateur` (`id_utilisateur`);

--
-- Indexes for table `noms_references`
--
ALTER TABLE `noms_references`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_nom_sous_type` (`id_sous_type`);

--
-- Indexes for table `objets`
--
ALTER TABLE `objets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `Code_bar` (`Code_bar`),
  ADD KEY `fk_objet_nom_reference` (`id_nom_reference`),
  ADD KEY `fk_objet_emprunteur` (`Emprunteur_id`),
  ADD KEY `fk_objet_caisse` (`Caisse_id`),
  ADD KEY `fk_id_commentaire` (`id_com`);

--
-- Indexes for table `sous_types`
--
ALTER TABLE `sous_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sous_type_type` (`id_type`);

--
-- Indexes for table `types`
--
ALTER TABLE `types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `RFID_code` (`RFID_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `caisses`
--
ALTER TABLE `caisses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `commentaires`
--
ALTER TABLE `commentaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `historique`
--
ALTER TABLE `historique`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `noms_references`
--
ALTER TABLE `noms_references`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `objets`
--
ALTER TABLE `objets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `sous_types`
--
ALTER TABLE `sous_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `types`
--
ALTER TABLE `types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `caisses`
--
ALTER TABLE `caisses`
  ADD CONSTRAINT `fk_caisse_emprunteur` FOREIGN KEY (`Emprunteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_caisse_emprunteur_new` FOREIGN KEY (`Emprunteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `historique`
--
ALTER TABLE `historique`
  ADD CONSTRAINT `fk_h_materiel` FOREIGN KEY (`id_materiel`) REFERENCES `objets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_h_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `noms_references`
--
ALTER TABLE `noms_references`
  ADD CONSTRAINT `fk_nom_sous_type` FOREIGN KEY (`id_sous_type`) REFERENCES `sous_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `objets`
--
ALTER TABLE `objets`
  ADD CONSTRAINT `fk_id_com` FOREIGN KEY (`id_com`) REFERENCES `commentaires` (`id`),
  ADD CONSTRAINT `fk_objet_caisse` FOREIGN KEY (`Caisse_id`) REFERENCES `caisses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_objet_emprunteur` FOREIGN KEY (`Emprunteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_objet_nom_reference` FOREIGN KEY (`id_nom_reference`) REFERENCES `noms_references` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sous_types`
--
ALTER TABLE `sous_types`
  ADD CONSTRAINT `fk_sous_type_type` FOREIGN KEY (`id_type`) REFERENCES `types` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
