<?php
/**
 * TrueVault VPN - Database Builder Setup
 * Part 13 - Task 13.1
 * Creates builder.db with all required tables
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

// Define builder database path
define('DB_BUILDER', DB_PATH . 'builder.db');

echo "<!DOCTYPE html><html><head><title>Database Builder Setup</title>";
echo "<style>body{font-family:system-ui;background:#0f0f1a;color:#fff;padding:40px;max-width:800px;margin:0 auto}";
echo ".success{color:#00ff88;padding:10px;background:rgba(0,255,136,0.1);border-radius:8px;margin:10px 0}";
echo ".error{color:#ff5050;padding:10px;background:rgba(255,80,80,0.1);border-radius:8px;margin:10px 0}";
echo "h1{color:#00d9ff}h2{color:#888;border-bottom:1px solid #333;padding-bottom:10px}</style></head><body>";

echo "<h1>🗂️ Database Builder Setup</h1>";
echo "<p>Creating builder.db with custom tables schema...</p>";

try {
    // Create builder database
    $db = new SQLite3(DB_BUILDER);
    $db->enableExceptions(true);
    
    echo "<h2>Creating Tables...</h2>";
    
    // TABLE 1: custom_tables
    $db->exec("
        CREATE TABLE IF NOT EXISTS custom_tables (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            table_name TEXT NOT NULL UNIQUE,
            display_name TEXT NOT NULL,
            description TEXT,
            icon TEXT DEFAULT 'table',
            color TEXT DEFAULT '#3b82f6',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            created_by INTEGER,
            is_system INTEGER DEFAULT 0,
            record_count INTEGER DEFAULT 0,
            status TEXT DEFAULT 'active',
            settings TEXT
        )
    ");
    echo "<div class='success'>✅ custom_tables created</div>";
    
    // TABLE 2: custom_fields
    $db->exec("
        CREATE TABLE IF NOT EXISTS custom_fields (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            table_id INTEGER NOT NULL,
            field_name TEXT NOT NULL,
            display_name TEXT NOT NULL,
            field_type TEXT NOT NULL,
            sort_order INTEGER DEFAULT 0,
            is_required INTEGER DEFAULT 0,
            is_unique INTEGER DEFAULT 0,
            default_value TEXT,
            validation_rules TEXT,
            help_text TEXT,
            placeholder TEXT,
            options TEXT,
            settings TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (table_id) REFERENCES custom_tables(id) ON DELETE CASCADE
        )
    ");
    echo "<div class='success'>✅ custom_fields created</div>";
    
    // TABLE 3: table_relationships
    $db->exec("
        CREATE TABLE IF NOT EXISTS table_relationships (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            parent_table_id INTEGER NOT NULL,
            child_table_id INTEGER NOT NULL,
            relationship_type TEXT NOT NULL,
            parent_field TEXT NOT NULL,
            child_field TEXT NOT NULL,
            cascade_delete INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_table_id) REFERENCES custom_tables(id) ON DELETE CASCADE,
            FOREIGN KEY (child_table_id) REFERENCES custom_tables(id) ON DELETE CASCADE
        )
    ");
    echo "<div class='success'>✅ table_relationships created</div>";
    
    // TABLE 4: dataforge_templates
    $db->exec("
        CREATE TABLE IF NOT EXISTS dataforge_templates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            template_key TEXT UNIQUE NOT NULL,
            category TEXT NOT NULL,
            subcategory TEXT,
            name TEXT NOT NULL,
            description TEXT,
            styles TEXT,
            variables TEXT,
            tags TEXT,
            preview_image TEXT,
            usage_count INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<div class='success'>✅ dataforge_templates created</div>";
    
    // Create indexes
    echo "<h2>Creating Indexes...</h2>";
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_custom_tables_status ON custom_tables(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_custom_fields_table ON custom_fields(table_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_relationships_parent ON table_relationships(parent_table_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_relationships_child ON table_relationships(child_table_id)");
    
    echo "<div class='success'>✅ All indexes created</div>";
    
    $db->close();
    
    echo "<h2>✅ Setup Complete!</h2>";
    echo "<p>builder.db has been created with all tables.</p>";
    echo "<p><a href='index.php' style='color:#00d9ff'>→ Go to Database Builder</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
