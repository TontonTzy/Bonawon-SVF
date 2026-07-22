-- Aiven Cloud MySQL Migration Script
-- Import this script into your Aiven MySQL database (e.g. using mysql CLI, DBeaver, or MySQL Workbench)

-- 1. Announcements Table
CREATE TABLE IF NOT EXISTS `announcements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL DEFAULT 'PARISH ANNOUNCEMENT',
    `description` TEXT NOT NULL,
    `image` VARCHAR(500) DEFAULT 'images/mamamarylindogon.jpg',
    `link` VARCHAR(500) DEFAULT '#',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Events Table
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL DEFAULT 'PARISH EVENT',
    `date_str` VARCHAR(20) NOT NULL,
    `day` INT NOT NULL,
    `month` VARCHAR(10) NOT NULL,
    `year` INT NOT NULL DEFAULT 2026,
    `dow` VARCHAR(10) NOT NULL,
    `time` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Contact Messages Table
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('unread', 'read', 'archived') DEFAULT 'unread',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Initial Seed Data (Announcements)
INSERT INTO `announcements` (`title`, `category`, `description`, `image`, `link`) VALUES
('Join us for Daily Rosary this May', 'PARISH ANNOUNCEMENT', 'We invite all parishioners to pray the Holy Rosary every day this May at the parish church at 5:00 PM.', 'images/mamamarylindogon.jpg', '#'),
('Parish Pastoral Council Meeting', 'PARISH ANNOUNCEMENT', 'Monthly meeting of all PPC officers, ministry heads, and BEC leaders at the Parish Hall.', 'images/St. Vincent Ferrer Parish - Bonawon, Siaton, Negros Oriental.png', '#'),
('Sacrament of Confirmation Registration', 'SACRAMENTAL ANNOUNCEMENT', 'Registration for the upcoming Sacrament of Confirmation is now open at the Parish Office.', 'images/logos/svf-bonawon-logo.png', '#');

-- Insert Initial Seed Data (Events)
INSERT INTO `events` (`title`, `category`, `date_str`, `day`, `month`, `year`, `dow`, `time`, `description`, `location`) VALUES
('Feast Day Celebration of St. Vincent Ferrer', 'PARISH FIESTA', 'APR 05', 5, 'APR', 2026, 'SUN', '6:00 AM - 5:00 PM', 'Annual Parish Fiesta in honor of our patron St. Vincent Ferrer, featuring solemn pontifical mass, procession, and community festivities.', 'Parish Church & Grounds'),
('Flores de Mayo Devotion & Rosary', 'MARIAN DEVOTION', 'MAY 01', 1, 'MAY', 2026, 'FRI', '5:00 PM Daily', 'Daily praying of the Holy Rosary and flower offering by parish children throughout the month of May.', 'Parish Main Sanctuary'),
('Parish Youth Conference 2026', 'YOUTH MINISTRY', 'JUL 18', 18, 'JUL', 2026, 'SAT', '8:00 AM - 4:00 PM', 'Gathering of parish youth for spiritual renewal, leadership workshops, praise & worship, and fellowship.', 'Parish Pastoral Center'),
('BEC Anniversary Celebration', 'PARISH EVENT', 'SEP 19', 19, 'SEP', 2026, 'SAT', '8:00 AM', 'Community service, Thanksgiving Mass, and fellowship with all Basic Ecclesial Communities.', 'Bonawon Gymnasium'),
('All Saints Day & Blessing of Graves', 'LITURGICAL CELEBRATION', 'NOV 01', 1, 'NOV', 2026, 'SUN', '6:00 AM • 9:00 AM • 4:00 PM', 'Solemn Masses and blessing of graves in commemoration of all saints and departed loved ones.', 'Parish Church & Cemetery'),
('Misa de Gallo (Simbang Gabi)', 'CHRISTMAS SEASON', 'DEC 16', 16, 'DEC', 2026, 'WED', '4:00 AM Daily', 'Traditional nine-day dawn Masses preparing our hearts for the birth of our Lord Jesus Christ.', 'Parish Main Sanctuary');
