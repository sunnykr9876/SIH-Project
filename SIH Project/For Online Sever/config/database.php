<?php
// htdocs/config/database.php

// 1. FORCE PHP TO USE INDIAN STANDARD TIME (Fixes the dashboard dates)
date_default_timezone_set('Asia/Kolkata');

// REPLACE THESE 4 LINES WITH YOUR EXACT FREEHOSTPRO CREDENTIALS!
$host = 'localhost';        // e.g., sql123.freehostpro.com
$dbname = 'oop';   // e.g., epiz_1234567_st_scholarship
$username = 'root';// e.g., epiz_1234567
$password = '';// e.g., Your hosting password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // 2. FORCE MYSQL TO USE INDIAN STANDARD TIME (+05:30)
    $pdo->exec("SET time_zone = '+05:30';");
    
} catch (PDOException $e) {
    die("<div style='padding: 20px; background: #ffebee; color: #c62828;'>
            <h3>System Fault: Database Connection Failed</h3>
            <p>Your database.php file has the wrong FreeHostPro credentials.</p>
            <small>" . htmlspecialchars($e->getMessage()) . "</small>
         </div>");
}
?>