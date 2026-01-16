<?php
/**
 * PesquisaDatabase Class
 * Works with your existing database.php that creates $pdo based on URL
 */

class PesquisaDatabase {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        global $pdo;
        
        // Check if $pdo is already created by config/database.php
        if (isset($pdo) && $pdo instanceof PDO) {
            $this->pdo = $pdo;
            error_log("PesquisaDatabase: Using existing PDO connection");
        } else {
            // Fallback: create our own connection
            error_log("PesquisaDatabase: Creating new PDO connection");
            $this->createConnection();
        }
    }
    
    /**
     * Create a direct connection to esesp_pesquisas database
     */
    private function createConnection() {
        try {
            $host = 'localhost';
            $username = 'root';
            $password = '';
            $dbname = 'esesp_pesquisas';
            
            $this->pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            error_log("PesquisaDatabase: Successfully connected to $dbname");
        } catch (PDOException $e) {
            error_log("PesquisaDatabase connection error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getPDO() {
        return $this->pdo;
    }
    
    /**
     * Execute a query and return all results
     */
    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("fetchAll error: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }
    
    /**
     * Execute a query and return single row
     */
    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("fetchOne error: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }
    
    /**
     * Execute a query and return single value
     */
    public function fetchColumn($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("fetchColumn error: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }
    
    /**
     * Execute an INSERT/UPDATE/DELETE query
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("execute error: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }
    
    /**
     * Insert a record and return last insert ID
     */
    public function insert($table, $data) {
        try {
            $keys = array_keys($data);
            $fields = implode(', ', $keys);
            $placeholders = ':' . implode(', :', $keys);
            
            $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("insert error: " . $e->getMessage() . " | Table: " . $table);
            throw $e;
        }
    }
    
    /**
     * Update a record
     */
    public function update($table, $data, $where, $whereParams = []) {
        try {
            $sets = [];
            $values = [];
            $paramCounter = 1;
            
            // Build SET clause with positional parameters
            foreach ($data as $key => $value) {
                $sets[] = "$key = ?";
                $values[] = $value;
            }
            $setClause = implode(', ', $sets);
            
            // Merge data values with where parameters
            $allParams = array_merge($values, $whereParams);
            
            $sql = "UPDATE $table SET $setClause WHERE $where";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($allParams);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("update error: " . $e->getMessage() . " | Table: " . $table . " | SQL: " . $sql);
            throw $e;
        }
    }
    
    /**
     * Count records
     */
    public function count($table, $where = '1=1', $params = []) {
        try {
            $sql = "SELECT COUNT(*) FROM $table WHERE $where";
            return (int) $this->fetchColumn($sql, $params);
        } catch (PDOException $e) {
            error_log("count error: " . $e->getMessage() . " | Table: " . $table);
            return 0;
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }
    
    /**
     * Call stored procedure
     */
    public function callProcedure($name, $params = []) {
        try {
            $placeholders = implode(',', array_fill(0, count($params), '?'));
            $sql = "CALL $name($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("callProcedure error: " . $e->getMessage() . " | Procedure: " . $name);
            throw $e;
        }
    }
    
    /**
     * Check if a table exists
     */
    public function tableExists($tableName) {
        try {
            $result = $this->pdo->query("SHOW TABLES LIKE '$tableName'");
            return $result->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}