<?php

/**
 * NOTE: The following comments are for educational/debugging purposes and may not cover all edge cases.
 * db.php - Handles database connection for the backend.
 */

// Database connection details
$host = 'localhost';
$dbname = 'task_app';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

// PDO connection options
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

function getConnection() {
    global $dsn, $username, $password, $options;
    try {
        return new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        throw new Exception("Connection failed: " . $e->getMessage());
    }
}

// For backward compatibility
try {
    $conn = getConnection();
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
