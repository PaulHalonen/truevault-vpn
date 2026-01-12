<?php
/**
 * TrueVault VPN - Database Manager
 * Handles connections to all 21 SQLite databases
 * 
 * Usage:
 *   $db = Database::getConnection('users');
 *   $db->query("SELECT * FROM users");
 */

class Database {
    private static $connections = [];
    private static $basePath = null;
    
    // All available databases
    private static $databases = [
        'users',           // User accounts
        'admin_users',     // Admin accounts
        'subscriptions',   // Subscription plans
        'payments',        // Payment history
        'vpn',             // VPN servers and connections
        'certificates',    // SSL/VPN certificates
        'devices',         // User devices
        'identities',      // Regional identities
        'mesh',            // Mesh networking
        'cameras',         // IP cameras
        'themes',          // UI themes
        'pages',           // CMS pages
        'emails',          // Email templates
        'media',           // Media files
        'logs',            // System logs
        'settings',        // System settings
        'automation',      // Workflow automation
        'notifications',   // User notifications
        'analytics',       // Analytics data
        'bandwidth',       // Bandwidth usage
        'support'          // Support tickets
    ];
    
    /**
     * Get database base path
     */
    private static function getBasePath() {
        if (self::$basePath === null) {
            self::$basePath = __DIR__ . '/../../data';
        }
        return self::$basePath;
    }
    
    /**
     * Get a database connection by name
     * 
     * @param string $name The database name (users, vpn, themes, etc.)
     * @return PDO The database connection
     */
    public static function getConnection($name) {
        if (!isset(self::$connections[$name])) {
            self::$connections[$name] = self::createConnection($name);
        }
        return self::$connections[$name];
    }
    
    /**
     * Create a new database connection
     */
    private static function createConnection($name) {
        $basePath = self::getBasePath();
        $dbPath = "$basePath/$name.db";
        
        // Create data directory if it doesn't exist
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }
        
        try {
            $pdo = new PDO("sqlite:$dbPath");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Enable foreign keys
            $pdo->exec('PRAGMA foreign_keys = ON');
            
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("Failed to connect to database $name: " . $e->getMessage());
        }
    }
    
    /**
     * Get list of all available databases
     */
    public static function getDatabaseList() {
        return self::$databases;
    }
    
    /**
     * Check if a database exists
     */
    public static function databaseExists($name) {
        $dbPath = self::getBasePath() . "/$name.db";
        return file_exists($dbPath);
    }
    
    /**
     * Get database file path
     */
    public static function getDatabasePath($name) {
        return self::getBasePath() . "/$name.db";
    }
    
    /**
     * Close a specific connection
     */
    public static function close($name) {
        if (isset(self::$connections[$name])) {
            self::$connections[$name] = null;
            unset(self::$connections[$name]);
        }
    }
    
    /**
     * Close all database connections
     */
    public static function closeAll() {
        self::$connections = [];
    }
    
    /**
     * Get database status for all databases
     */
    public static function getStatus() {
        $status = [];
        foreach (self::$databases as $name) {
            $status[$name] = [
                'exists' => self::databaseExists($name),
                'path' => self::getDatabasePath($name)
            ];
        }
        return $status;
    }
}

// Alias for convenience
function db($name) {
    return Database::getConnection($name);
}
