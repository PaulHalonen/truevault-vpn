<?php
/**
 * TrueVault VPN - Database Helper (SQLite3 version)
 * Provides database connections using SQLite3 class
 */

class Database {
    private static $connections = [];
    private static $basePath = null;
    
    /**
     * Get the base path for database files
     */
    private static function getBasePath() {
        if (self::$basePath === null) {
            self::$basePath = __DIR__ . '/../../data';
        }
        return self::$basePath;
    }
    
    /**
     * Get a database connection by name
     */
    public static function getConnection($name) {
        if (!isset(self::$connections[$name])) {
            $dbPath = self::getBasePath() . "/$name.db";
            
            if (!file_exists($dbPath)) {
                throw new Exception("Database not found: $name.db - Run setup-databases.php first");
            }
            
            self::$connections[$name] = new SQLite3($dbPath);
            self::$connections[$name]->enableExceptions(true);
        }
        
        return self::$connections[$name];
    }
    
    /**
     * Execute a query and return results as array
     */
    public static function query($dbName, $sql, $params = []) {
        $db = self::getConnection($dbName);
        $stmt = $db->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Failed to prepare statement: " . $db->lastErrorMsg());
        }
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $paramName = is_int($key) ? $key + 1 : $key;
            $type = SQLITE3_TEXT;
            
            if (is_int($value)) {
                $type = SQLITE3_INTEGER;
            } elseif (is_float($value)) {
                $type = SQLITE3_FLOAT;
            } elseif (is_null($value)) {
                $type = SQLITE3_NULL;
            }
            
            $stmt->bindValue($paramName, $value, $type);
        }
        
        $result = $stmt->execute();
        
        if ($result === false) {
            throw new Exception("Query failed: " . $db->lastErrorMsg());
        }
        
        // Fetch all results
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    /**
     * Execute a query and return single row
     */
    public static function queryOne($dbName, $sql, $params = []) {
        $rows = self::query($dbName, $sql, $params);
        return $rows[0] ?? null;
    }
    
    /**
     * Execute an INSERT/UPDATE/DELETE and return affected info
     */
    public static function execute($dbName, $sql, $params = []) {
        $db = self::getConnection($dbName);
        $stmt = $db->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Failed to prepare statement: " . $db->lastErrorMsg());
        }
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $paramName = is_int($key) ? $key + 1 : $key;
            $type = SQLITE3_TEXT;
            
            if (is_int($value)) {
                $type = SQLITE3_INTEGER;
            } elseif (is_float($value)) {
                $type = SQLITE3_FLOAT;
            } elseif (is_null($value)) {
                $type = SQLITE3_NULL;
            }
            
            $stmt->bindValue($paramName, $value, $type);
        }
        
        $result = $stmt->execute();
        
        if ($result === false) {
            throw new Exception("Execute failed: " . $db->lastErrorMsg());
        }
        
        return [
            'lastInsertId' => $db->lastInsertRowID(),
            'changes' => $db->changes()
        ];
    }
    
    /**
     * Get last insert ID
     */
    public static function lastInsertId($dbName) {
        $db = self::getConnection($dbName);
        return $db->lastInsertRowID();
    }
    
    /**
     * Close a specific connection
     */
    public static function close($name) {
        if (isset(self::$connections[$name])) {
            self::$connections[$name]->close();
            unset(self::$connections[$name]);
        }
    }
    
    /**
     * Close all connections
     */
    public static function closeAll() {
        foreach (self::$connections as $name => $db) {
            $db->close();
        }
        self::$connections = [];
    }
    
    /**
     * Check if database exists
     */
    public static function databaseExists($name) {
        return file_exists(self::getBasePath() . "/$name.db");
    }
    
    /**
     * List all available databases
     */
    public static function getDatabaseList() {
        $databases = [];
        $files = glob(self::getBasePath() . '/*.db');
        foreach ($files as $file) {
            $databases[] = basename($file, '.db');
        }
        return $databases;
    }
}

/**
 * Shorthand function for getting database connection
 */
function db($name) {
    return Database::getConnection($name);
}
