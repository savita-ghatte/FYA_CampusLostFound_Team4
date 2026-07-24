CREATE DATABASE IF NOT EXISTS campus_lost_found;
USE campus_lost_found;

CREATE TABLE IF NOT EXISTS users (
    username VARCHAR(100) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS lost_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    date_lost DATE NOT NULL,
    location VARCHAR(255) NOT NULL,
    contact VARCHAR(255) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('Pending', 'Matched', 'Returned') DEFAULT 'Pending'
);

CREATE TABLE IF NOT EXISTS found_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    date_found DATE NOT NULL,
    location VARCHAR(255) NOT NULL,
    contact VARCHAR(255) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('Pending', 'Claimed', 'Returned') DEFAULT 'Pending'
);

CREATE TABLE IF NOT EXISTS claims (
    claim_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    colour VARCHAR(100) NOT NULL,
    distinguishing_marks TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    claim_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending'
);

-- Seed a default user: username = admin, password = admin123
INSERT INTO users (username, password) VALUES ('admin', '$2y$10$tM/6W.284Pj5nL2B/y57qOWl2kX6zxe5U8NfC8o32iFj9X8gR2yB6L')
ON DUPLICATE KEY UPDATE username=username;
