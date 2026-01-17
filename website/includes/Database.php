<?php
/**
 * TrueVault VPN - Database Helper Class
 * Uses native SQLite3 (available on GoDaddy)
 */

class Database {
    private static $instances = [];
    private $db;
    private $dbName;
    
    private static $databases = [
        'main' => 'main.db',
        'users' => 'main.db',
        'devices' => 'devices.db',
        'servers' => 'servers.db',
        'billing' => 'billing.db',
        'logs' => 'logs.db',
        'support' => 'support.db',
        'admin' => 'admin.db',
        'port_forwards' => 'port_forwards.db'
    ];
    
    public function __construct($dbName = 'main') {
        $this->dbName = $dbName;
        $dbPath = $this->getDbPath($dbName);
        
        // Create databases directory if needed
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Create/open database (SQLite3 creates if not exists)
        $this->db = new SQLite3($dbPath);
        $this->db->enableExceptions(true);
        $this->db->exec('PRAGMA foreign_keys = ON');
        $this->db->busyTimeout(5000);
    }
    
    private function getDbPath($dbName) {
        $basePath = dirname(__DIR__) . '/databases/';
        $filename = self::$databases[$dbName] ?? "{$dbName}.db";
        return $basePath . $filename;
    }
    
    public function getConnection() {
        return $this->db;
    }
    
    public function escape($value) {
        if ($value === null) return 'NULL';
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_int($value) || is_float($value)) return $value;
        return "'" . $this->db->escapeString($value) . "'";
    }
    
    public function exec($sql) {
        return $this->db->exec($sql);
    }
    
    public function query($sql) {
        return $this->db->query($sql);
    }
    
    public function queryAll($sql) {
        $result = $this->db->query($sql);
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    public function queryOne($sql) {
        $result = $this->db->query($sql);
        return $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
    }
    
    public function queryValue($sql) {
        return $this->db->querySingle($sql);
    }
    
    public function lastInsertId() {
        return $this->db->lastInsertRowID();
    }
    
    public function changes() {
        return $this->db->changes();
    }
    
    public function prepare($sql) {
        return new DatabaseStatement($this->db, $sql);
    }
    
    public function insert($table, $data) {
        $columns = array_keys($data);
        $values = array_map([$this, 'escape'], array_values($data));
        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
        $this->db->exec($sql);
        return $this->db->lastInsertRowID();
    }
    
    public function update($table, $data, $where) {
        $sets = [];
        foreach ($data as $col => $val) {
            $sets[] = "{$col} = " . $this->escape($val);
        }
        $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE {$where}";
        $this->db->exec($sql);
        return $this->db->changes();
    }
    
    public function delete($table, $where) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $this->db->exec($sql);
        return $this->db->changes();
    }
    
    public function close() {
        if ($this->db) {
            $this->db->close();
            $this->db = null;
        }
    }
}

/**
 * Simple prepared statement wrapper for SQLite3
 */
class DatabaseStatement {
    private $stmt;
    private $db;
    
    public function __construct($db, $sql) {
        $this->db = $db;
        $this->stmt = $db->prepare($sql);
    }
    
    public function execute($params = []) {
        $this->stmt->reset();
        $this->stmt->clear();
        foreach ($params as $i => $value) {
            $index = is_int($i) ? $i + 1 : $i;
            if ($value === null) {
                $this->stmt->bindValue($index, null, SQLITE3_NULL);
            } elseif (is_int($value)) {
                $this->stmt->bindValue($index, $value, SQLITE3_INTEGER);
            } else {
                $this->stmt->bindValue($index, $value, SQLITE3_TEXT);
            }
        }
        return $this->stmt->execute();
    }
    
    public function fetch() {
        $result = $this->stmt->execute();
        return $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
    }
    
    public function fetchAll() {
        $result = $this->stmt->execute();
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    public function fetchColumn() {
        $result = $this->stmt->execute();
        $row = $result ? $result->fetchArray(SQLITE3_NUM) : null;
        return $row ? $row[0] : null;
    }
}
