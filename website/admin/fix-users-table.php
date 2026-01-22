<?php
/**
 * Quick Fix: Add vip_server_id column to users table
 */
define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

header('Content-Type: text/plain');

try {
    $db = new SQLite3(DB_USERS);
    $db->enableExceptions(true);
    
    // Add vip_server_id column if it doesn't exist
    $db->exec("ALTER TABLE users ADD COLUMN vip_server_id INTEGER");
    
    echo "SUCCESS: Added vip_server_id column to users table\n";
    
    $db->close();
} catch (Exception $e) {
    // Column might already exist
    if (strpos($e->getMessage(), 'duplicate column') !== false) {
        echo "Column already exists - OK\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
