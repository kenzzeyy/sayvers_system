<?php
// Database Configuration for SAYVERS System
// WAMP Server Configuration

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_NAME', 'sayvers_system');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default WAMP password is empty

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to get database connection
function getDBConnection() {
    global $pdo;
    return $pdo;
}
?>