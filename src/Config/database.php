<?php
// Database configuration - use environment variables or set defaults
define('DB_SERVER', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USERNAME', $_ENV['DB_USER'] ?? 'root');
define('DB_PASSWORD', $_ENV['DB_PASS'] ?? ''); // Set your DB password via environment variable
define('DB_NAME', $_ENV['DB_NAME'] ?? 'subtrack_db');

class Database {
    private $pdo;

    public function __construct() {
        try {
            $this->pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e){
            die("ERROR: Could not connect. " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->pdo;
    }
}

// For backward compatibility with existing code
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>