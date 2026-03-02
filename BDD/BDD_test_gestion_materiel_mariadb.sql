-- Création de la base de données
CREATE DATABASE IF NOT EXISTS gestion_materiel_db;
USE gestion_materiel_db;

-- Création de la table utilisateurs
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    RFID_code VARCHAR(100),
    Nom VARCHAR(100) NOT NULL,
    Prénom VARCHAR(100) NOT NULL,
    Crea_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des utilisateurs
INSERT INTO utilisateurs (RFID_code, Nom, Prénom) VALUES
('RFID123456789', 'Dupont', 'Marie'),
('RFID987654321', 'Martin', 'Pierre'),
('RFID456789123', 'Bernard', 'Sophie'),
('RFID789123456', 'Petit', 'Lucas'),
('RFID321654987', 'Durand', 'Emma');

-- Suppression des anciennes tables si elles existent (ordre inversé pour les FK)
DROP TABLE IF EXISTS Objet;
DROP TABLE IF EXISTS Caisse;

-- Création de la table Caisse (AVANT Objet car Objet la référence)
-- MODIFICATION: Suppression de la colonne Contenu JSON
CREATE TABLE Caisse (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Nom VARCHAR(150) NOT NULL,
    Etat ENUM('disponible', 'réservé', 'emprunté') NOT NULL DEFAULT 'disponible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    Emprunteur_id INT DEFAULT NULL,
    FOREIGN KEY (Emprunteur_id) REFERENCES utilisateurs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Création de la table Objet
-- MODIFICATION: Ajout de la colonne Caisse_id avec foreign key
CREATE TABLE Objet (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Code_bar VARCHAR(50) UNIQUE NOT NULL,
    Type VARCHAR(100) NOT NULL,
    Nom VARCHAR(150) NOT NULL,
    Etat ENUM('disponible', 'réservé', 'emprunté') NOT NULL DEFAULT 'disponible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    Emprunteur_id INT DEFAULT NULL,
    Caisse_id INT DEFAULT NULL,  -- NOUVELLE COLONNE
    FOREIGN KEY (Emprunteur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (Caisse_id) REFERENCES Caisse(id) ON DELETE SET NULL  -- NOUVELLE FOREIGN KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Insertion de données d'exemple pour la table Objet
INSERT INTO Objet (Code_bar, Type, Nom, Etat, Emprunteur_id, Caisse_id) VALUES
('BC3847562910', 'Ordinateur', 'Dell Latitude 5520', 'disponible', NULL, NULL),
('BC7291048563', 'Tablette', 'iPad Pro 12.9"', 'réservé', 2, NULL),
('BC5624891037', 'Projecteur', 'Epson EB-X41', 'emprunté', 3, NULL),
('BC8915734062', 'Câble HDMI', 'Câble HDMI 2m', 'disponible', NULL, NULL),
('BC4638207591', 'Clavier', 'Logitech K380', 'disponible', NULL, NULL),
('BC1234567890', 'Souris', 'Logitech MX Master 3', 'disponible', NULL, NULL),
('BC9876543210', 'Webcam', 'Logitech C920', 'disponible', NULL, NULL),
('BC5555666677', 'Écran', 'Dell U2720Q', 'disponible', NULL, NULL);

-- Insertion de données d'exemple pour la table Caisse
INSERT INTO Caisse (Nom, Etat, Emprunteur_id) VALUES
('Caisse Audio Conférence', 'disponible', NULL),
('Caisse Vidéo', 'emprunté', 4);

-- Lier les objets aux caisses (remplace l'ancien JSON Contenu)
-- Caisse Audio Conférence: BC8915734062, BC4638207591, BC5555666677
UPDATE Objet SET Caisse_id = 1, Etat = 'réservé' WHERE Code_bar IN ('BC8915734062', 'BC4638207591', 'BC5555666677');

-- Caisse Vidéo: BC9876543210, BC1234567890
UPDATE Objet SET Caisse_id = 2, Etat = 'réservé' WHERE Code_bar IN ('BC9876543210', 'BC1234567890');
