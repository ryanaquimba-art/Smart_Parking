<?php
date_default_timezone_set('Asia/Manila');

// Pinalitan ng Railway MySQL credentials
define('DB_HOST', 'mysql.railway.internal');
define('DB_USER', 'root');
define('DB_PASS', 'kCeDYsVQYeATnZAcFdtRgKeVJbjCfMHG');
define('DB_NAME', 'railway');
define('DB_PORT', '3306'); // Idinagdag ang port para sigurado

try {
    // Idinagdag ang port= sa connection string
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failure: " . $e->getMessage());
}
?>