<?php
/**
 * Database Helper Class
 * 
 * PURPOSE: Simplified database operations for SQLite3
 * USAGE: Static methods for common database tasks
 * 
 * EXAMPLES:
 * - Database::queryOne('users', "SELECT * FROM users WHERE id = ?", [1])
 * - Database::queryAll('users', "SELECT * FROM users WHERE tier = ?", ['vip'])
 * - Database::execute('users', "INSERT INTO users (email) VALUES (?)", ['test@example.com'])
 * - Database::insert('users', ['email' => 'test@example.com', 'tier' => 'standard'])
 * - Database::update('users', ['status' => 'active'], ['id' => 1])
 * - Database::delete('users', ['id' => 1])
 * 
 * @created January 2026
 * @version 1.0.0
 */

class Database {
    
    /**
     * Get database connection for specific database
     * 
     * @param string $dbName Database name (users, devices, servers, etc.)
     * @return SQLite3 Database connection
     */
    private static function getConnection($dbName) {
        // Map short names to full paths
        $dbMap = [
            'users' => DB_USERS,
            'devices' => DB_DEVICES,
            'servers' => DB_SERVERS,
            'billing' => DB_BILLING,
            'port_forwards' => DB_PORT_FORWARDS,
            'parental_controls' => DB_PARENTAL_CONTROLS,
            'admin' => DB_ADMIN,
            'logs' => DB_LOGS
        ];
        
        if (!isset($dbMap[$dbName])) {
            throw new Exception("Invalid database name: $dbName");
        }
        
        return getDatabase($dbMap[$dbName]);
    }
    
    /**
     * Query single row
     * 
     * @param string $dbName Database name
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array|null Single row or null
     */
    public static function queryOne($dbName, $sql, $params = []) {
        try {
            $db = self::getConnection($dbName);
            
            // Prepare statement
            $stmt = $db->prepare($sql);
            
            // Bind parameters
            foreach ($params as $index => $value) {
                $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
                $stmt->bindValue($index + 1, $value, $type);
            }
            
            // Execute and fetch
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            
            $db->close();
            
            return $row ?: null;
            
        } catch (Exception $e) {
            error_log("Database::queryOne error: " . $e->getMessage());
            throw new Exception("Query failed");
        }
    }
    
    /**
     * Query multiple rows
     * 
     * @param string $dbName Database name
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array Array of rows
     */
    public static function queryAll($dbName, $sql, $params = []) {
        try {
            $db = self::getConnection($dbName);
            
            // Prepare statement
            $stmt = $db->prepare($sql);
            
            // Bind parameters
            foreach ($params as $index => $value) {
                $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
                $stmt->bindValue($index + 1, $value, $type);
            }
            
            // Execute and fetch all
            $result = $stmt->execute();
            $rows = [];
            
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $rows[] = $row;
            }
            
            $db->close();
            
            return $rows;
            
        } catch (Exception $e) {
            error_log("Database::queryAll error: " . $e->getMessage());
            throw new Exception("Query failed");
        }
    }
    
    /**
     * Execute query (INSERT, UPDATE, DELETE)
     * 
     * @param string $dbName Database name
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return bool Success status
     */
    public static function execute($dbName, $sql, $params = []) {
        try {
            $db = self::getConnection($dbName);
            
            // Prepare statement
            $stmt = $db->prepare($sql);
            
            // Bind parameters
            foreach ($params as $index => $value) {
                $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
                $stmt->bindValue($index + 1, $value, $type);
            }
            
            // Execute
            $result = $stmt->execute();
            
            $db->close();
            
            return (bool)$result;
            
        } catch (Exception $e) {
            error_log("Database::execute error: " . $e->getMessage());
            throw new Exception("Execution failed");
        }
    }
    
    /**
     * Insert row (simplified)
     * 
     * @param string $dbName Database name
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int Last insert ID
     */
    public static function insert($dbName, $table, $data) {
        try {
            // Build SQL
            $columns = array_keys($data);
            $placeholders = array_fill(0, count($data), '?');
            
            $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $db = self::getConnection($dbName);
            
            // Prepare statement
            $stmt = $db->prepare($sql);
            
            // Bind values
            $index = 1;
            foreach ($data as $value) {
                $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
                $stmt->bindValue($index++, $value, $type);
            }
            
            // Execute
            $stmt->execute();
            $lastId = $db->lastInsertRowID();
            
            $db->close();
            
            return $lastId;
            
        } catch (Exception $e) {
            error_log("Database::insert error: " . $e->getMessage());
            throw new Exception("Insert failed");
        }
    }
    
    /**
     * Update rows (simplified)
     * 
     * @param string $dbName Database name
     * @param string $table Table name
     * @param array $data Associative array of column => value to update
     * @param array $where Associative array of column => value for WHERE clause
     * @return bool Success status
     */
    public static function update($dbName, $table, $data, $where) {
        try {
            // Build SET clause
            $setClauses = [];
            foreach ($data as $column => $value) {
                $setClauses[] = "$column = ?";
            }
            
            // Build WHERE clause
            $whereClauses = [];
            foreach ($where as $column => $value) {
                $whereClauses[] = "$column = ?";
            }
            
            $sql = "UPDATE $table SET " . implode(', ', $setClauses) . " WHERE " . implode(' AND ', $whereClauses);
            
            $db = self::getConnection($dbName);
            
            // Prepare statement
            $stmt = $db->prepare($sql);
            
            // Bind SET values
            $index = 1;
            foreach ($data as $value) {
                $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
                $stmt->bindValue($index++, $value, $type);
            }
            
            // Bind WHERE values
            foreach ($where as $value) {
                $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
                $stmt->bindValue($index++, $value, $type);
            }
            
            // Execute
            $result = $stmt->execute();
            
            $db->close();
            
            return (bool)$result;
            
        } catch (Exception $e) {
            error_log("Database::update error: " . $e->getMessage());
            throw new Exception("Update failed");
        }
    }
    
    /**
     * Delete rows (simplified)
     * 
     * @param string $dbName Database name
     * @param string $table Table name
     * @param array $where Associative array of column => value for WHERE clause
     * @return bool Success status
     */
    public static function delete($dbName, $table, $where) {
        try {
            // Build WHERE clause
            $whereClauses = [];
            foreach ($where as $column => $value) {
                $whereClauses[] = "$column = ?";
            }
            
            $sql = "DELETE FROM $table WHERE " . implode(' AND ', $whereClauses);
            
            $db = self::getConnection($dbName);
            
            // Prepare statement
            $stmt = $db->prepare($sql);
            
            // Bind values
            $index = 1;
            foreach ($where as $value) {
                $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
                $stmt->bindValue($index++, $value, $type);
            }
            
            // Execute
            $result = $stmt->execute();
            
            $db->close();
            
            return (bool)$result;
            
        } catch (Exception $e) {
            error_log("Database::delete error: " . $e->getMessage());
            throw new Exception("Delete failed");
        }
    }
}
?>