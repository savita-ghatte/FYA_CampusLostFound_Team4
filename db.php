<?php
// db.php - Database connection and automatic initialization

// Suppress database connection errors temporarily during initial connection
$servername = "localhost";
$username = "root";
$password = "";

// 1. Establish connection to MySQL server
$conn = @new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}

// 2. Create database if not exists
$sql_db = "CREATE DATABASE IF NOT EXISTS campus_lost_found";
if (!$conn->query($sql_db)) {
    die("Database creation failed: " . $conn->error);
}

// 3. Select the database
if (!$conn->select_db("campus_lost_found")) {
    die("Database selection failed: " . $conn->error);
}

// Ensure uploads directory exists with full permissions
$uploads_dir = __DIR__ . '/uploads';
if (!is_dir($uploads_dir)) {
    @mkdir($uploads_dir, 0777, true);
}

// 4. Create Tables if they do not exist
$table_queries = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
        username VARCHAR(100) PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL
    )",
    "lost_items" => "CREATE TABLE IF NOT EXISTS lost_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        item_name VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        date_lost DATE NOT NULL,
        location VARCHAR(255) NOT NULL,
        contact VARCHAR(255) NOT NULL,
        image VARCHAR(255) DEFAULT NULL,
        status ENUM('Pending', 'Matched', 'Returned') DEFAULT 'Pending'
    )",
    "found_items" => "CREATE TABLE IF NOT EXISTS found_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        item_name VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        date_found DATE NOT NULL,
        location VARCHAR(255) NOT NULL,
        contact VARCHAR(255) NOT NULL,
        image VARCHAR(255) DEFAULT NULL,
        status ENUM('Pending', 'Claimed', 'Returned') DEFAULT 'Pending'
    )",
    "claims" => "CREATE TABLE IF NOT EXISTS claims (
        claim_id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        colour VARCHAR(100) NOT NULL,
        distinguishing_marks TEXT NOT NULL,
        image VARCHAR(255) DEFAULT NULL,
        claim_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending'
    )"
];

foreach ($table_queries as $name => $query) {
    if (!$conn->query($query)) {
        die("Table creation failed for '$name': " . $conn->error);
    }
}

// Check if users table is missing 'name' column (in case it already exists)
$check_name_col = $conn->query("SHOW COLUMNS FROM users LIKE 'name'");
if ($check_name_col && $check_name_col->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN name VARCHAR(255) NOT NULL AFTER username");
}

// Check if users table is missing 'email' column
$check_email_col = $conn->query("SHOW COLUMNS FROM users LIKE 'email'");
if ($check_email_col && $check_email_col->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL AFTER name");
}

// 5. Seed default users if users table is empty
$check_users = $conn->query("SELECT * FROM users LIMIT 1");
if ($check_users && $check_users->num_rows === 0) {
    $seed_users = [
        'admin' => ['System Administrator', 'admin@college.edu', 'admin123'],
        'CS21B045' => ['Anushka Pardesi', 'cs21b045@college.edu', 'password123'],
        'EC20A012' => ['Harshit Borkar', 'ec20a012@college.edu', 'password123'],
        'ME22B078' => ['Savita Ghatte', 'me22b078@college.edu', 'password123'],
        'EE19B003' => ['Rahul Sharma', 'ee19b003@college.edu', 'password123'],
        'IT21A056' => ['Priya Patel', 'it21a056@college.edu', 'password123']
    ];
    
    $stmt = $conn->prepare("INSERT INTO users (username, name, email, password) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        foreach ($seed_users as $uname => $data) {
            $name = $data[0];
            $email = $data[1];
            $hashed = password_hash($data[2], PASSWORD_DEFAULT);
            $stmt->bind_param("ssss", $uname, $name, $email, $hashed);
            $stmt->execute();
        }
        $stmt->close();
    }
}

// 6. Seed default lost_items if table is empty
$check_lost = $conn->query("SELECT * FROM lost_items LIMIT 1");
if ($check_lost && $check_lost->num_rows === 0) {
    $lost_seeds = [
        ['Grey Backpack', 'A grey canvas backpack with a key ring and laptop inside.', date('Y-m-d', strtotime('-2 days')), 'Library', 'cs21b045@college.edu', 'Pending'],
        ['Black Umbrella', 'Compact foldable umbrella with a wooden handle.', date('Y-m-d', strtotime('-1 days')), 'Main Gate', 'cs21b045@college.edu', 'Pending'],
        ['Scientific Calculator', 'Casio fx-991EX calculator, name sticker on back.', date('Y-m-d', strtotime('-5 days')), 'Exam Hall B', 'ee19b003@college.edu', 'Returned']
    ];
    
    $stmt = $conn->prepare("INSERT INTO lost_items (item_name, description, date_lost, location, contact, status) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        foreach ($lost_seeds as $seed) {
            $stmt->bind_param("ssssss", $seed[0], $seed[1], $seed[2], $seed[3], $seed[4], $seed[5]);
            $stmt->execute();
        }
        $stmt->close();
    }
}

// 7. Seed default found_items if table is empty
$check_found = $conn->query("SELECT * FROM found_items LIMIT 1");
if ($check_found && $check_found->num_rows === 0) {
    $found_seeds = [
        ['Bunch of Keys', 'Car key and three small door keys on a brass ring.', date('Y-m-d', strtotime('-4 days')), 'Canteen', 'ec20a012@college.edu', 'Returned'],
        ['Blue Water Bottle', 'Metallic sky-blue bottle with a minor dent near the cap.', date('Y-m-d', strtotime('-6 days')), 'Sports Ground', 'me22b078@college.edu', 'Pending'],
        ['Wired Earphones', 'White Apple EarPods with lightning connector.', date('Y-m-d', strtotime('-3 days')), 'Cafeteria', 'it21a056@college.edu', 'Pending']
    ];
    
    $stmt = $conn->prepare("INSERT INTO found_items (item_name, description, date_found, location, contact, status) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        foreach ($found_seeds as $seed) {
            $stmt->bind_param("ssssss", $seed[0], $seed[1], $seed[2], $seed[3], $seed[4], $seed[5]);
            $stmt->execute();
        }
        $stmt->close();
    }
}

// 8. Seed default claim if claims table is empty
$check_claims = $conn->query("SELECT * FROM claims LIMIT 1");
if ($check_claims && $check_claims->num_rows === 0) {
    $get_item = $conn->query("SELECT item_id FROM found_items WHERE item_name='Blue Water Bottle' LIMIT 1");
    if ($get_item && $row = $get_item->fetch_assoc()) {
        $item_id = $row['item_id'];
        $colour = 'Sky blue';
        $distinguishing = 'Sticker of room 104 on the base';
        $claim_status = 'Pending';
        
        $stmt = $conn->prepare("INSERT INTO claims (item_id, colour, distinguishing_marks, claim_status) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isss", $item_id, $colour, $distinguishing, $claim_status);
            $stmt->execute();
            $stmt->close();
        }
    }
}
?>