<?php
class Database {
    // Detect if we're running locally or on Render
    private $is_local;
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        // Check if we're on localhost
        $this->is_local = (
            $_SERVER['SERVER_NAME'] == 'localhost' || 
            $_SERVER['SERVER_NAME'] == '127.0.0.1' ||
            strpos($_SERVER['SERVER_NAME'], '.onrender.com') === false
        );
        
        if ($this->is_local) {
            // Local development with PostgreSQL
            // First try PostgreSQL, fallback to MySQL if needed
            $this->host = "localhost";
            $this->port = "5432";
            $this->db_name = "ccs_profiling_db";
            $this->username = "postgres";  // Default PostgreSQL user
            $this->password = "";           // Your PostgreSQL password if set
        } else {
            // Production (Render) - PostgreSQL from environment variables
            // Render automatically sets these environment variables
            $this->host = getenv('PGHOST') ?: getenv('DB_HOST') ?: 'localhost';
            $this->port = getenv('PGPORT') ?: getenv('DB_PORT') ?: '5432';
            $this->db_name = getenv('PGDATABASE') ?: getenv('DB_NAME') ?: 'ccs_profiling_db_i8u5';
            $this->username = getenv('PGUSER') ?: getenv('DB_USER') ?: 'ccs_profiling_db_i8u5_user';
            $this->password = getenv('PGPASSWORD') ?: getenv('DB_PASSWORD') ?: '';
        }
    }

    public function getConnection() {
        $this->conn = null;
        try {
            // Use PostgreSQL DSN
            $dsn = "pgsql:host=" . $this->host . 
                   ";port=" . $this->port . 
                   ";dbname=" . $this->db_name;
            
            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            // Set schema to public (PostgreSQL default)
            $this->conn->exec("SET search_path TO public");
            
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
    
    // Helper method to check connection
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                $stmt = $conn->query("SELECT version()");
                $version = $stmt->fetch();
                return "Connected to: " . $version['version'];
            }
        } catch (Exception $e) {
            return "Connection failed: " . $e->getMessage();
        }
        return "Connection failed";
    }
}
?>