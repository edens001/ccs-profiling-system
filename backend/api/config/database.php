<?php
class Database {
    // Detect if we're running locally or on Render
    private $is_local;
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // Check if we're on localhost
        $this->is_local = (
            $_SERVER['SERVER_NAME'] == 'localhost' || 
            $_SERVER['SERVER_NAME'] == '127.0.0.1' ||
            strpos($_SERVER['SERVER_NAME'], '.onrender.com') === false
        );
        
        if ($this->is_local) {
            // Local development (XAMPP/WAMP)
            $this->host = "localhost";
            $this->db_name = "ccs_profiling_db";
            $this->username = "root";
            $this->password = "";
        } else {
            // Production (Render) - Get from environment variables
            $this->host = getenv('MYSQL_HOST') ?: 'localhost';
            $this->db_name = getenv('MYSQL_DATABASE') ?: 'ccs_profiling_db';
            $this->username = getenv('MYSQL_USER') ?: 'root';
            $this->password = getenv('MYSQL_PASSWORD') ?: '';
        }
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            // Better error handling for debugging
            if ($this->is_local) {
                echo "Connection error: " . $exception->getMessage();
            } else {
                // On production, log error but don't show details to users
                error_log("Database connection error: " . $exception->getMessage());
                echo "Database connection failed. Please try again later.";
            }
        }
        return $this->conn;
    }
}
?>