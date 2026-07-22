<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'campus_lost_found';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}
?>