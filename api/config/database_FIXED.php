<?php
/**
 * TrueVault VPN - Database Manager
 * Handles all SQLite database connections
 * 
 * UPDATED: January 14, 2026
 * - Changed to flat /data/ structure to match server
 * 
 * Usage:
 *   $db = Database::getConnection('users');
 *   $db = Database::getConnection('billing');
 */

class Database {
    private static $connections = [];
    private static $basePath = null;
    
    // Database paths - FLAT STRUCTURE matching server
    private static $databases = [
        // Core
        'users' => 'users.db',
        'sessions' => 'sessions.db',
        'admin' => 'admin_users.db',
        
        // VPN
        'vpn' => 'vpn.db',
        'servers' => 'servers.db',
        'certificates' => 'certificates.db',
        'identities' => 'identities.db',
        
        // Devices
        'devices' => 'devices.db',
        'cameras' => 'cameras.db',
        'port_forwarding' => 'port_forwarding.db',
        'mesh' => 'mesh.db',
        
        // Billing
        'billing' => 'subscriptions.db',  // Server uses subscriptions.db
        'invoices' => 'payments.db',       // Server uses payments.db
        'payments' => 'payments.db',
        'subscriptions' => 'subscriptions.db',
        
        // CMS
        'cms' => 'pages.db',
        'themes' => 'themes.db',
        'pages' => 'pages.db',
        'settings' => 'settings.db',
        'media' => 'media.db',
        'emails' => 'emails.db',
        
        // Automation
        'automation' => 'automation.db',
        'logs' => 'logs.db',
        'notifications' => 'notifications.db',
        
        // Analytics
        'analytics' => 'analytics.db',
        'bandwidth' => 'bandwidth.db',
        
        // Support
        'support' => 'support.db',
        
        // VIP
        'vip' => 'vip.db',
        'vip_users' => 'vip.db'
    ];
    
    /**
     * Get database base path
     */
    private static function getBasePath() {
        if (self::$basePath === null) {
            // Production path - /data/ folder
            if (file_exists('/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/data')) {
                self::$basePath = '/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/data';
            } 
            // Alternative production path
            elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . '/data')) {
                self::$basePath = $_SERVER['DOCUMENT_ROOT'] . '/data';
            }
            // Local development
            else {
                self::$basePath = dirname(__DIR__, 2) . '/data';
            }
        }
        return self::$basePath;
    }
    
    /**
     * Get PDO connection for a database
     */
    public static function getConnection($dbName) {
        if (!isset(self::$databases[$dbName])) {
            throw new Exception("Unknown database: {$dbName}");
        }
        
        if (!isset(self::$connections[$dbName])) {
            $path = self::getBasePath() . '/' . self::$databases[$dbName];
            
            // Create directory if not exists
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            try {
                $pdo = new PDO("sqlite:{$path}");
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $pdo->exec('PRAGMA foreign_keys = ON');
                self::$connections[$dbName] = $pdo;
            } catch (PDOException $e) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
        
        return self::$connections[$dbName];
    }
    
    /**
     * Execute a query and return result
     */
    public static function query($dbName, $sql, $params = []) {
        $db = self::getConnection($dbName);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Execute a query and return single row
     */
    public static function queryOne($dbName, $sql, $params = []) {
        $db = self::getConnection($dbName);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Execute insert/update/delete
     */
    public static function execute($dbName, $sql, $params = []) {
        $db = self::getConnection($dbName);
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Get last insert ID
     */
    public static function lastInsertId($dbName) {
        return self::getConnection($dbName)->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public static function beginTransaction($dbName) {
        return self::getConnection($dbName)->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public static function commit($dbName) {
        return self::getConnection($dbName)->commit();
    }
    
    /**
     * Rollback transaction
     */
    public static function rollback($dbName) {
        return self::getConnection($dbName)->rollBack();
    }
    
    /**
     * Check if table exists
     */
    public static function tableExists($dbName, $tableName) {
        $result = self::queryOne($dbName, 
            "SELECT name FROM sqlite_master WHERE type='table' AND name=?",
            [$tableName]
        );
        return $result !== false;
    }
    
    /**
     * Get all database paths for backup
     */
    public static function getAllPaths() {
        $paths = [];
        foreach (self::$databases as $name => $path) {
            $paths[$name] = self::getBasePath() . '/' . $path;
        }
        return array_unique($paths);
    }
}
