-- ========== FORCER L'ENCODAGE UTF-8 ==========
SET NAMES 'utf8mb4';
SET CHARACTER SET utf8mb4;

-- Création de la base de données AVEC le bon encodage
CREATE DATABASE IF NOT EXISTS etrivia_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE etrivia_db;

-- ========== TABLES ==========

-- Catégories (One-to-Many avec clubs)
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Clubs et associations (avec leurs identifiants de connexion)
CREATE TABLE IF NOT EXISTS clubs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    description TEXT,
    banner_url VARCHAR(500),
    category_id INT,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Contacts (One-to-One avec clubs)
CREATE TABLE IF NOT EXISTS club_contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    club_id INT UNIQUE,
    email VARCHAR(150),
    instagram VARCHAR(150),
    website VARCHAR(255),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Événements
CREATE TABLE IF NOT EXISTS events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    date DATETIME NOT NULL,
    location VARCHAR(200)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Table Many-to-Many (Club - Événement)
CREATE TABLE IF NOT EXISTS club_event (
    club_id INT,
    event_id INT,
    PRIMARY KEY (club_id, event_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ========== DONNÉES DE TEST ==========

-- Catégories
INSERT INTO categories (name) VALUES
    ('Sport'), ('Culture'), ('Musique'), ('Informatique'), ('Humanitaire'), ('Cuisine'), ('Jeux');

-- Clubs (mot de passe de démonstration pour tous : club1234)
INSERT INTO clubs (name, username, password_hash, description, category_id) VALUES
    ('Club Informatique', 'club-info', '657472697669612d736565642d323621$f101d6d26b34a8846a6581643efa5059645086d6ac50849b407ccecd46003a55',
     'On code, on joue et on s''entraide autour de l''informatique. Tous niveaux bienvenus.', 4),
    ('Association Sportive', 'asso-sport', '657472697669612d736565642d323621$f101d6d26b34a8846a6581643efa5059645086d6ac50849b407ccecd46003a55',
     'Football, basket, running : des séances ouvertes à tous les étudiants chaque semaine.', 1),
    ('Atelier Musique', 'atelier-musique', '657472697669612d736565642d323621$f101d6d26b34a8846a6581643efa5059645086d6ac50849b407ccecd46003a55',
     'Salle de répétition, jam sessions et concerts étudiants.', 3);

-- Contacts (One-to-One)
INSERT INTO club_contacts (club_id, email, instagram, website) VALUES
    (1, 'info@example.org', '@club_info_usn', NULL),
    (2, 'sport@example.org', '@asso_sport_usn', NULL),
    (3, 'musique@example.org', NULL, NULL);

-- Événements
INSERT INTO events (title, description, date, location) VALUES
    ('Soirée jeux vidéo', 'Tournoi amical, venez avec votre manette.', DATE_ADD(NOW(), INTERVAL 7 DAY), 'Bâtiment Galilée'),
    ('Tournoi de foot', 'Matchs de 20 minutes, équipes de 5.', DATE_ADD(NOW(), INTERVAL 10 DAY), 'Gymnase du campus'),
    ('Concert de fin de semestre', 'Organisé avec le club informatique pour la partie lumières.', DATE_ADD(NOW(), INTERVAL 21 DAY), 'Amphi A');

-- Organisateurs (Many-to-Many)
INSERT INTO club_event (club_id, event_id) VALUES
    (1, 1),
    (2, 2),
    (3, 3),
    (1, 3);
