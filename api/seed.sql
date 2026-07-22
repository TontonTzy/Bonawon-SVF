-- Seed Data Script for St. Vincent Ferrer Parish
-- Run this script in phpMyAdmin after schema.sql

USE `svf_parish_db`;

-- Clear existing data if any
TRUNCATE TABLE `announcements`;
TRUNCATE TABLE `events`;

-- Insert Initial Announcements
INSERT INTO `announcements` (`title`, `category`, `description`, `image`, `link`) VALUES
('Join us for Daily Rosary this May', 'PARISH ANNOUNCEMENT', 'We invite all parishioners to pray the Holy Rosary every day this May at the parish church at 5:00 PM.', 'images/mamamarylindogon.jpg', '#'),
('Parish Pastoral Council Meeting', 'PARISH ANNOUNCEMENT', 'Monthly meeting of all PPC officers, ministry heads, and BEC leaders at the Parish Hall.', 'images/St. Vincent Ferrer Parish - Bonawon, Siaton, Negros Oriental.png', '#'),
('Sacrament of Confirmation Registration', 'SACRAMENTAL ANNOUNCEMENT', 'Registration for the upcoming Sacrament of Confirmation is now open at the Parish Office.', 'images/logos/svf-bonawon-logo.png', '#');

-- Insert Initial Events
INSERT INTO `events` (`title`, `category`, `date_str`, `day`, `month`, `year`, `dow`, `time`, `description`, `location`) VALUES
('Feast Day Celebration of St. Vincent Ferrer', 'PARISH FIESTA', 'APR 05', 5, 'APR', 2026, 'SUN', '6:00 AM - 5:00 PM', 'Annual Parish Fiesta in honor of our patron St. Vincent Ferrer, featuring solemn pontifical mass, procession, and community festivities.', 'Parish Church & Grounds'),
('Flores de Mayo Devotion & Rosary', 'MARIAN DEVOTION', 'MAY 01', 1, 'MAY', 2026, 'FRI', '5:00 PM Daily', 'Daily praying of the Holy Rosary and flower offering by parish children throughout the month of May.', 'Parish Main Sanctuary'),
('Parish Youth Conference 2026', 'YOUTH MINISTRY', 'JUL 18', 18, 'JUL', 2026, 'SAT', '8:00 AM - 4:00 PM', 'Gathering of parish youth for spiritual renewal, leadership workshops, praise & worship, and fellowship.', 'Parish Pastoral Center'),
('BEC Anniversary Celebration', 'PARISH EVENT', 'SEP 19', 19, 'SEP', 2026, 'SAT', '8:00 AM', 'Community service, Thanksgiving Mass, and fellowship with all Basic Ecclesial Communities.', 'Bonawon Gymnasium'),
('All Saints Day & Blessing of Graves', 'LITURGICAL CELEBRATION', 'NOV 01', 1, 'NOV', 2026, 'SUN', '6:00 AM • 9:00 AM • 4:00 PM', 'Solemn Masses and blessing of graves in commemoration of all saints and departed loved ones.', 'Parish Church & Cemetery'),
('Misa de Gallo (Simbang Gabi)', 'CHRISTMAS SEASON', 'DEC 16', 16, 'DEC', 2026, 'WED', '4:00 AM Daily', 'Traditional nine-day dawn Masses preparing our hearts for the birth of our Lord Jesus Christ.', 'Parish Main Sanctuary');
