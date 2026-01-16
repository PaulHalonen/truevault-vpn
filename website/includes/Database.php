<?php
/**
 * TrueVault VPN - Database Helper Class
 * 
 * Provides easy SQLite3 database access with common operations.
 * Uses SQLite3 class (NOT PDO - not available on GoDaddy).
 * 
 * @created January 2026
 * @version 1.0.0
 */

class Database {
    private static $instances = [];
    private $db;
    private $dbName;
    
    /**
     * Database file paths
     */
    private static $databases = [
        'main' => 'main.db',
        'users' => 'users.db',
        'devices' => 'devices.db',
        'servers' => 'servers.db',
        'billing' => 'billing.db',
        'logs' => 'logs.db',
        'support' => 'support.db'
    ];
    
    /**
     * Private constructor - use getInstance()
     */
    private function __construct($dbName) {
        $this->dbName = $dbName;
        $dbPath = $this->getDbPath($dbName);
        
        if (!file_exists($dbPath)) {
            throw new Exception("Database not found: {$dbName}");
        }
        
        $this->db = new SQLite3($dbPath);
        $this->db->enableExceptions(true);
        $this->db->exec('PRAGMA foreign_keys = ON');
        $this->db->busyTimeout(5000);
    }
    
    /**
     * Get database instance (singleton per database)
     */
    public static function getInstance($dbName = 'main') {
        if (!isset(self::$instances[$dbName])) {
            self::$instances[$dbName] = new self($dbName);
        }
        return self::$instances[$dbName];
    }
    
    /**
     * Get full path to database file
     */
    private function getDbPath($dbName) {
        $basePath = defined('DB_PATH') ? DB_PATH : dirname(__DIR__) . '/databases/';
        $filename = self::$databases[$dbName] ?? "{$dbName}.db";
        return $basePath . $filename;
    }
    
    /**
     * Get raw SQLite3 connection
     */
    public function getConnection() {
        return $this->db;
    }
    
    /**
     * Escape string for safe SQL
     */
    public function escape($value) {
        if ($value === null) return 'NULL';
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_int($value) || is_float($value)) return $value;
        return "'" . $this->db->escapeString($value) . "'";
    }
    
    /**
     * Execute raw SQL (INSERT, UPDATE, DELETE)
     */
    public function exec($sql) {
        return $this->db->exec($sql);
    }
    
    /**
     * Query and return all rows as array
     */
    public function queryAll($sql) {
        $result = $this->db->query($sql);
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    /**
     * Query and return single row
     */
    public function queryOne($sql) {
        $result = $this->db->query($sql);
        return $result->fetchArray(SQLITE3_ASSOC) ?: null;
    }
    
    /**
     * Query and return single value
     */
    public function queryValue($sql) {
        $result = $this->db->querySingle($sql);
        return $result;
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->db->lastInsertRowID();
    }
    
    /**
     * Get number of affected rows
     */
    public function changes() {
        return $this->db->changes();
    }
    
    /**
     * Insert a row and return the new ID
     */
    public function insert($table, $data) {
        $columns = array_keys($data);
        $values = array_map([$this, 'escape'], array_values($data));
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $values) . ")";
        
        $this->db->exec($sql);
        return $this->db->lastInsertRowID();
    }
    
    /**
     * Update rows and return affected count
     */
    public function update($table, $data, $where) {
        $sets = [];
        foreach ($data as $column => $value) {
            $sets[] = "{$column} = " . $this->escape($value);
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE {$where}";
        $this->db->exec($sql);
        return $this->db->changes();
    }
    
    /**
     * Delete rows and return affected count
     */
    public function delete($table, $where) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $this->db->exec($sql);
        return $this->db->changes();
    }
    
    /**
     * Check if a record exists
     */
    public function exists($table, $where) {
        $sql = "SELECT 1 FROM {$table} WHERE {$where} LIMIT 1";
        $result = $this->db->querySingle($sql);
        return $result !== false && $result !== null;
    }
    
    /**
     * Count records
     */
    public function count($table, $where = '1=1') {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        return (int) $this->db->querySingle($sql);
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->db->exec('BEGIN TRANSACTION');
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        $this->db->exec('COMMIT');
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->db->exec('ROLLBACK');
    }
    
    /**
     * Close connection
     */
    public function close() {
        if ($this->db) {
            $this->db->close();
            $this->db = null;
            unset(self::$instances[$this->dbName]);
        }
    }
    
    /**
     * Destructor - close connection
     */
    public function __destruct() {
        $this->close();
    }
}
