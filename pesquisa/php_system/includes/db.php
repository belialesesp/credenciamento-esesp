<?php
/**
 * includes/db.php - Classe de Conexão com MySQL usando PDO
 * SIMPLIFICADO: Igual ao app que funciona
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Construtor privado (Singleton pattern)
     */
    private function __construct() {
        try {
            // Conectar EXATAMENTE como o outro app que funciona
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
        } catch (PDOException $e) {
            // Erro detalhado apenas em modo debug
            if (DEBUG_MODE) {
                die("<div style='font-family: Arial; padding: 20px; background: #fee; border-left: 4px solid #f00;'>
                    <h3>❌ Erro de Conexão MySQL</h3>
                    <p><strong>Mensagem:</strong> " . $e->getMessage() . "</p>
                    <p><strong>Host:</strong> " . DB_HOST . "</p>
                    <p><strong>Database:</strong> " . DB_NAME . "</p>
                    <p><strong>User:</strong> " . DB_USER . "</p>
                    <p><strong>Password:</strong> " . (empty(DB_PASS) ? '(vazia)' : '(definida)') . "</p>
                    <hr>
                    <p><strong>Seu outro app funciona com 'localhost'.</strong></p>
                    <p>Verifique se o banco <code>esesp_pesquisas</code> existe no MySQL.</p>
                    <p>Se não existir, crie via phpMyAdmin ou rode o script SQL de instalação.</p>
                    </div>");
            } else {
                error_log("Database connection error: " . $e->getMessage());
                die("Erro ao conectar com o banco de dados.");
            }
        }
    }
    
    /**
     * Obter instância única do banco (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obter conexão PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Executar query com prepared statements
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage() . " | SQL: " . $sql);
            
            if (DEBUG_MODE) {
                throw $e;
            } else {
                throw new Exception("Erro ao executar consulta no banco de dados.");
            }
        }
    }
    
    /**
     * Buscar todos os resultados
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Buscar um único resultado
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Buscar uma coluna específica
     */
    public function fetchColumn($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Inserir dados e retornar ID
     */
    public function insert($table, $data) {
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        
        $this->query($sql, $values);
        return $this->lastInsertId();
    }
    
    /**
     * Atualizar dados
     */
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $set[] = "$column = ?";
            $values[] = $value;
        }
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $set),
            $where
        );
        
        $allParams = array_merge($values, $whereParams);
        $stmt = $this->query($sql, $allParams);
        
        return $stmt->rowCount();
    }
    
    /**
     * Deletar dados
     */
    public function delete($table, $where, $params = []) {
        $sql = sprintf("DELETE FROM %s WHERE %s", $table, $where);
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Obter último ID inserido
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Iniciar transação
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit de transação
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback de transação
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    /**
     * Verificar se tabela existe
     */
    public function tableExists($table) {
        $sql = "SHOW TABLES LIKE ?";
        $result = $this->fetchOne($sql, [$table]);
        return !empty($result);
    }
    
    /**
     * Contar registros
     */
    public function count($table, $where = '1=1', $params = []) {
        $sql = "SELECT COUNT(*) FROM $table WHERE $where";
        return (int) $this->fetchColumn($sql, $params);
    }
    
    /**
     * Impedir clonagem
     */
    private function __clone() {}
    
    /**
     * Impedir unserialize
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}