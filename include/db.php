<?php
// ========================================
// DATABASE CONNECTION (MySQLi + PDO)
// ========================================

$user = "root";
$host = "localhost";
$pass = "";
$db = "dbcargo";

// MySQLi Connection (for existing code)
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("MySQLi Error: " . $conn->connect_error);
}

// Set charset to UTF-8mb4 for proper emoji/unicode support
$conn->set_charset("utf8mb4");

// PDO Connection (for GPS tracking)
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("PDO Error: " . $e->getMessage());
}