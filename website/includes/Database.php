<?php
/**
 * Database Helper Class - SQLITE3 VERSION
 * 
 * PURPOSE: Singleton pattern for SQLite3 database connections
 * Manages all 9 database connections efficiently
 * 
 * CRITICAL: Uses SQLite3 class, NOT PDO!
 * 
 * USAGE:
 *   $db = Database::getInstance('users');
 *   $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
 *   $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
 *   $result = $stmt->execute();
 *   $row = $result->fetchArray(SQLITE3_ASSOC);
 * 
 * @created January 2026
 * @version 1.0.0
 */

// Security check
if (!defined('TRUEVAULT_INIT')) {
    http_response_code(403);
    die('Direct access not allowed');
}

class Database {
    
    /**
     * Database instances (singleton pattern)
     * @var SQLite3[]
     */
    private static $instances = [];
    
    /**
     * Database paths mapping
     */
    private static $databases = [
        'users'             => '/databases/users.db',
        'devices'           => '/databases/devices.db',
        'servers'           => '/databases/servers.db',
        'billing'           => '/databases/billing.db',
        'port_forwards'     => '/databases/port_forwards.db',
        'parental_controls' => '/databases/parental_controls.db',
        'admin'             => '/databases/admin.db',
        'logs'              => '/databases/logs.db',
        'themes'            => '/databases/themes.db'
    ];
    
    /**
     * Get database instance (singleton)
     * 
     * @param string $name Database name (users, devices, servers, etc.)
     * @return SQLite3 Database connection
     * @throws Exception If database not found
     */
    public static function getInstance($name) {
        // Validate database name
        if (!isset(self::$databases[$name])) {
            throw new Exception("Unknown database: {$name}");
        }
        
        // Return existing instance if available
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }
        
        // Build full path
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);
        $dbPath = $basePath . self::$databases[$name];
        
        // Check file exists
        if (!file_exists($dbPath)) {
            throw new Exception("Database file not found: {$dbPath}");
        }
        
        // Create new SQLite3 connection
        try {
            $db = new SQLite3($dbPath);
            
            // Enable exceptions
            $db->enableExceptions(true);
            
            // Enable foreign keys
            $db->exec('PRAGMA foreign_keys = ON');
            
            // Set busy timeout (5 seconds)
            $db->busyTimeout(5000);
            
            // Store instance
            self::$instances[$name] = $db;
            
            return $db;
            
        } catch (Exception $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get all database names
     * 
     * @return array List of database names
     */
    public static function getDatabaseNames() {
        return array_keys(self::$databases);
    }
    
    /**
     * Check if database exists
     * 
     * @param string $name Database name
     * @return bool True if database exists
     */
    public static function exists($name) {
        if (!isset(self::$databases[$name])) {
            return false;
        }
        
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);
        $dbPath = $basePath . self::$databases[$name];
        
        return file_exists($dbPath);
    }
    
    /**
     * Close specific database connection
     * 
     * @param string $name Database name
     */
    public static function close($name) {
        if (isset(self::$instances[$name])) {
            self::$instances[$name]->close();
            unset(self::$instances[$name]);
        }
    }
    
    /**
     * Close all database connections
     */
    public static function closeAll() {
        foreach (self::$instances as $name => $db) {
            $db->close();
        }
        self::$instances = [];
    }
    
    /**
     * Begin transaction on specific database
     * 
     * @param string $name Database name
     */
    public static function beginTransaction($name) {
        self::getInstance($name)->exec('BEGIN TRANSACTION');
    }
    
    /**
     * Commit transaction on specific database
     * 
     * @param string $name Database name
     */
    public static function commit($name) {
        self::getInstance($name)->exec('COMMIT');
    }
    
    /**
     * Rollback transaction on specific database
     * 
     * @param string $name Database name
     */
    public static function rollback($name) {
        self::getInstance($name)->exec('ROLLBACK');
    }
    
    /**
     * Get last insert ID from specific database
     * 
     * @param string $name Database name
     * @return int Last insert row ID
     */
    public static function lastInsertId($name) {
        return self::getInstance($name)->lastInsertRowID();
    }
    
    /**
     * Escape string for safe SQL (use prepared statements instead when possible)
     * 
     * @param string $name Database name
     * @param string $value Value to escape
     * @return string Escaped value
     */
    public static function escape($name, $value) {
        return self::getInstance($name)->escapeString($value);
    }
}
