<?php
/**
 * Database connection class using singleton pattern
 */
class Database {
    private static $instance = null;
    private $conn;
    
    private $host = 'localhost';
    private $user = 'root';
    private $pass = '';
    private $dbname = 'scholar_db';
    
    private function __construct() {
        try {
            $this->conn = new mysqli($this->host, $this->user, $this->pass);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            // Check if database exists
            $result = $this->conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$this->dbname}'");
            
            if ($result->num_rows == 0) {
                // Database doesn't exist, redirect to setup page
                header('Location: /Scholar/database_setup.php?error=no_database');
                exit;
            }
            
            // Select the database
            if (!$this->conn->select_db($this->dbname)) {
                throw new Exception("Unable to select database: " . $this->conn->error);
            }
            
            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Redirect to setup page with error message
            header('Location: /Scholar/database_setup.php?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    public function query($sql) {
        return $this->conn->query($sql);
    }
    
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    public function getLastId() {
        return $this->conn->insert_id;
    }
    
    public function getError() {
        return $this->conn->error;
    }
    
    // Transaction methods
    public function begin_transaction() {
        return $this->conn->begin_transaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollback();
    }
    
    // Prevent cloning of the instance
    private function __clone() {}
    
    // Prevent unserializing of the instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}