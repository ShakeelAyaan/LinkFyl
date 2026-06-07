<?php
/**
 * Database Class
 * Handles all database connections and queries
 */

class Database {
    private $conn;
    private $statement;
    
    public function __construct() {
        $this->connect();
    }
    
    /**
     * Connect to database
     */
    private function connect() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->conn->connect_error) {
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed: ' . $this->conn->connect_error
            ]));
        }
        
        $this->conn->set_charset("utf8mb4");
    }
    
    /**
     * Prepare a query
     */
    public function query($sql) {
        $this->statement = $this->conn->prepare($sql);
        
        if (!$this->statement) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        
        return $this;
    }
    
    /**
     * Bind values to query
     */
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = MYSQLI_TYPE_LONG;
                    break;
                case is_float($value):
                    $type = MYSQLI_TYPE_DOUBLE;
                    break;
                case is_string($value):
                    $type = MYSQLI_TYPE_STRING;
                    break;
                default:
                    $type = MYSQLI_TYPE_STRING;
            }
        }
        
        $this->statement->bind_param($type, $value);
        return $this;
    }
    
    /**
     * Execute the prepared statement
     */
    public function execute() {
        try {
            return $this->statement->execute();
        } catch (Exception $e) {
            throw new Exception("Execute failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get single row result
     */
    public function single() {
        $result = $this->statement->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Get all results
     */
    public function resultSet() {
        $result = $this->statement->get_result();
        $rows = [];
        
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    /**
     * Get row count
     */
    public function rowCount() {
        return $this->statement->affected_rows;
    }
    
    /**
     * Get last insert ID
     */
    public function lastId() {
        return $this->conn->insert_id;
    }
    
    /**
     * Escape string
     */
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->conn->begin_transaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->conn->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->conn->rollback();
    }
    
    /**
     * Close connection
     */
    public function close() {
        $this->conn->close();
    }
}
?>
