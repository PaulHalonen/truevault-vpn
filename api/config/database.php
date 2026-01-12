<?php
/**
 * TrueVault VPN - Database Manager
 * Handles connections to all SQLite databases
 * 
 * Usage:
 *   $db = DatabaseManager::getInstance();
 *   $users = $db->getConnection('core', 'users');
 *   $users->query("SELECT * FROM users");
 */

class DatabaseManager {
    private static $instance = null;
    private $connections = [];
    private $basePath;
    
    // Database structure
    private $databases = [
        'core' => ['users', 'sessions', 'admin'],
        'vpn' => ['servers', 'connections', 'certificates', 'identities', 'routing'],
        'devices' => ['discovered', 'cameras', 'port_forwarding', 'mesh_network'],
        'billing' => ['subscriptions', 'invoices', 'payments', 'transactions'],
        'cms' => ['pages', 'themes', 'templates', 'media'],
        'automation' => ['workflows', 'tasks', 'logs', 'emails'],
        'analytics' => ['usage', 'bandwidth', 'events']
    ];
    
    private function __construct() {
        $this->basePath = __DIR__ . '/../databases';
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get a database connection
     * 
     * @param string $folder The database folder (core, vpn, devices, etc.)
     * @param string $name The database name (users, servers, etc.)
     * @return PDO The database connection
     */
    public function getConnection($folder, $name) {
        $key = "$folder/$name";
        
        if (!isset($this->connections[$key])) {
            $this->connections[$key] = $this->createConnection($folder, $name);
        }
        
        return $this->connections[$key];
    }
    
    /**
     * Create a new database connection
     */
    private function createConnection($folder, $name) {
        $dbPath = "{$this->basePath}/{$folder}/{$name}.db";
        
        if (!file_exists($dbPath)) {
            throw new Exception("Database not found: $dbPath. Run setup-databases.php first.");
        }
        
        try {
            $pdo = new PDO("sqlite:$dbPath");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Enable foreign keys
            $pdo->exec('PRAGMA foreign_keys = ON');
            
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("Failed to connect to database $folder/$name: " . $e->getMessage());
        }
    }
    
    // Convenience methods for common databases
    
    public function users() {
        return $this->getConnection('core', 'users');
    }
    
    public function sessions() {
        return $this->getConnection('core', 'sessions');
    }
    
    public function admin() {
        return $this->getConnection('core', 'admin');
    }
    
    public function servers() {
        return $this->getConnection('vpn', 'servers');
    }
    
    public function connections() {
        return $this->getConnection('vpn', 'connections');
    }
    
    public function certificates() {
        return $this->getConnection('vpn', 'certificates');
    }
    
    public function identities() {
        return $this->getConnection('vpn', 'identities');
    }
    
    public function routing() {
        return $this->getConnection('vpn', 'routing');
    }
    
    public function discovered() {
        return $this->getConnection('devices', 'discovered');
    }
    
    public function cameras() {
        return $this->getConnection('devices', 'cameras');
    }
    
    public function portForwarding() {
        return $this->getConnection('devices', 'port_forwarding');
    }
    
    public function meshNetwork() {
        return $this->getConnection('devices', 'mesh_network');
    }
    
    public function subscriptions() {
        return $this->getConnection('billing', 'subscriptions');
    }
    
    public function invoices() {
        return $this->getConnection('billing', 'invoices');
    }
    
    public function payments() {
        return $this->getConnection('billing', 'payments');
    }
    
    public function transactions() {
        return $this->getConnection('billing', 'transactions');
    }
    
    public function pages() {
        return $this->getConnection('cms', 'pages');
    }
    
    public function themes() {
        return $this->getConnection('cms', 'themes');
    }
    
    public function templates() {
        return $this->getConnection('cms', 'templates');
    }
    
    public function media() {
        return $this->getConnection('cms', 'media');
    }
    
    public function workflows() {
        return $this->getConnection('automation', 'workflows');
    }
    
    public function tasks() {
        return $this->getConnection('automation', 'tasks');
    }
    
    public function logs() {
        return $this->getConnection('automation', 'logs');
    }
    
    public function emails() {
        return $this->getConnection('automation', 'emails');
    }
    
    public function usage() {
        return $this->getConnection('analytics', 'usage');
    }
    
    public function bandwidth() {
        return $this->getConnection('analytics', 'bandwidth');
    }
    
    public function events() {
        return $this->getConnection('analytics', 'events');
    }
    
    /**
     * Close all database connections
     */
    public function closeAll() {
        $this->connections = [];
    }
    
    /**
     * Get list of all available databases
     */
    public function getDatabaseList() {
        return $this->databases;
    }
    
    /**
     * Check if a database exists
     */
    public function databaseExists($folder, $name) {
        $dbPath = "{$this->basePath}/{$folder}/{$name}.db";
        return file_exists($dbPath);
    }
    
    /**
     * Get database file path
     */
    public function getDatabasePath($folder, $name) {
        return "{$this->basePath}/{$folder}/{$name}.db";
    }
    
    /**
     * Execute a query on multiple databases (for cross-database operations)
     */
    public function multiQuery($queries) {
        $results = [];
        foreach ($queries as $key => $query) {
            list($folder, $name) = explode('/', $query['database']);
            $db = $this->getConnection($folder, $name);
            $stmt = $db->prepare($query['sql']);
            $stmt->execute($query['params'] ?? []);
            $results[$key] = $stmt->fetchAll();
        }
        return $results;
    }
}

// Helper function for quick access
function db() {
    return DatabaseManager::getInstance();
}
