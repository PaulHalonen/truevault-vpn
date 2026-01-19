

class Database {
    
    /**
     * PDO instance for themes.db (singleton)
     * Used by Theme, Content, PageBuilder classes
     */
    private static $pdoInstance = null;
    private static $pdoConnections = [];
    
    /**
     * Get PDO instance (for themes.db and other databases)
     * Singleton pattern
     * 
     * @return Database Database instance
     */
    public static function getInstance() {
        if (self::$pdoInstance === null) {
            self::$pdoInstance = new self();
        }
        return self::$pdoInstance;
    }
    
    /**
     * Get PDO connection for specific database
     * Used by Theme, Content, PageBuilder helper classes
     * 
     * @param string $dbName Database name (themes, users, etc.)
     * @return PDO PDO connection
     */
    public function getConnection($dbName) {
        // Return cached connection if exists
        if (isset(self::$pdoConnections[$dbName])) {
            return self::$pdoConnections[$dbName];
        }
        
        // Map short names to full paths
        $dbMap = [
            'users' => DB_USERS,
            'devices' => DB_DEVICES,
            'servers' => DB_SERVERS,
            'billing' => DB_BILLING,
            'port_forwards' => DB_PORT_FORWARDS,
            'parental_controls' => DB_PARENTAL_CONTROLS,
            'admin' => DB_ADMIN,
            'logs' => DB_LOGS,
            'themes' => DB_THEMES
        ];
        
        if (!isset($dbMap[$dbName])) {
            throw new Exception("Invalid database name: $dbName");
        }
        
        // Create PDO connection
        try {
            $pdo = new PDO('sqlite:' . $dbMap[$dbName]);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Cache connection
            self::$pdoConnections[$dbName] = $pdo;
            
            return $pdo;
            
        } catch (PDOException $e) {
            error_log("Database PDO connection error: " . $e->getMessage());
            throw new Exception("Failed to connect to database: $dbName");
        }
    }
    
    /**
     * Get database connection for specific database (SQLite3 version)
     * 
     * @param string $dbName Database name (users, devices, servers, etc.)
     * @return SQLite3 Database connection
     */
    private static function getSQLite3Connection($dbName) {
        // Map short names to full paths
        $dbMap = [
            'users' => DB_USERS,
            'devices' => DB_DEVICES,
            'servers' => DB_SERVERS,
            'billing' => DB_BILLING,
            'port_forwards' => DB_PORT_FORWARDS,
            'parental_controls' => DB_PARENTAL_CONTROLS,
            'admin' => DB_ADMIN,
            'logs' => DB_LOGS,
            'themes' => DB_THEMES
        ];
        
        if (!isset($dbMap[$dbName])) {
            throw new Exception("Invalid database name: $dbName");
        }
        
        return getDatabase($dbMap[$dbName]);
    }
