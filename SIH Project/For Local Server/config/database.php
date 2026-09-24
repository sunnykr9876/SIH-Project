<?php
// htdocs/config/database.php

// Define a universal project root path that works on XAMPP and Linux servers
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

$host = 'localhost';
$db   = 'oop'; // Replace with your DB name
$user = 'root';                 // Replace with your DB user
$pass = '';                     // Replace with your DB password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>