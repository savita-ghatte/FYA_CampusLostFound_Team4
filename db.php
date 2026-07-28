<?php
// ===============================
// Campus Lost & Found
// Database Connection
// ===============================

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "campus_lost_found";

// Create connection
$conn = new mysqli($host, $user, $pass);

// Check connection
if ($conn->connect_error) {
    die("Connection Failed : " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (!$conn->query($sql)) {
    die("Database Error : " . $conn->error);
}

// Select database
$conn->select_db($dbname);

// Set UTF-8
$conn->set_charset("utf8mb4");


// ===============================
// USERS TABLE
// ===============================

$conn->query("
CREATE TABLE IF NOT EXISTS users
(
    username VARCHAR(30) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL
)
");


// ===============================
// LOST ITEMS
// ===============================

$conn->query("
CREATE TABLE IF NOT EXISTS lost_items
(
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    description TEXT,
    date_lost DATE,
    location VARCHAR(100),
    contact VARCHAR(100),
    image VARCHAR(255),
    status ENUM('Pending','Matched','Returned')
    DEFAULT 'Pending'
)
");


// ===============================
// FOUND ITEMS
// ===============================

$conn->query("
CREATE TABLE IF NOT EXISTS found_items
(
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    description TEXT,
    date_found DATE,
    location VARCHAR(100),
    contact VARCHAR(100),
    image VARCHAR(255),
    status ENUM('Pending','Claimed','Returned')
    DEFAULT 'Pending'
)
");


// ===============================
// CLAIMS TABLE
// ===============================

$conn->query("
CREATE TABLE IF NOT EXISTS claims
(
    claim_id INT AUTO_INCREMENT PRIMARY KEY,

    item_id INT NOT NULL,

    colour VARCHAR(100),

    distinguishing_marks TEXT,

    image VARCHAR(255),

    claim_status ENUM
    (
        'Pending',
        'Approved',
        'Rejected'
    )
    DEFAULT 'Pending',

    FOREIGN KEY (item_id)
    REFERENCES found_items(item_id)
    ON DELETE CASCADE
)
");


// ===============================
// CREATE ADMIN
// ===============================

$result = $conn->query("SELECT username FROM users WHERE username='admin'");

if($result->num_rows==0)
{
    $password = password_hash("admin123", PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
    INSERT INTO users
    (
        username,
        name,
        email,
        password
    )
    VALUES
    (
        ?,?,?,?
    )
    ");

    $username = "admin";
    $name = "Administrator";
    $email = "admin@college.edu";

    $stmt->bind_param(
        "ssss",
        $username,
        $name,
        $email,
        $password
    );

    $stmt->execute();
}
?>