<?php
/**
 * TrueVault VPN - Database Builder Setup
 * Creates builder.db with schema for custom table management
 * 
 * This creates the metadata database that tracks user-created tables,
 * their fields, and relationships between tables.
 */

// Prevent direct access
if (!defined('TRUEVAULT_ADMIN')) {
    define('TRUEVAULT_ADMIN', true);
}

// Set database path
$db_path = __DIR__ . '/databases/builder.db';
$db_dir = dirname($db_path);

// Create databases directory if it doesn't exist
if (!file_exists($db_dir)) {
    mkdir($db_dir, 0755, true);
}

try {
    // Open/create database using SQLite3 (NOT PDO)
    $db = new SQLite3($db_path);
    
    // Enable foreign keys
    $db->exec('PRAGMA foreign_keys = ON');
    
    // TABLE 1: custom_tables
    // Registry of all user-created tables
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
    
    // TABLE 2: custom_fields
    // Field definitions for each table
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
    
    // TABLE 3: table_relationships
    // Relationships between tables (foreign keys)
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
    
    // Create indexes for performance
    $db->exec("CREATE INDEX IF NOT EXISTS idx_custom_tables_name ON custom_tables(table_name)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_custom_tables_status ON custom_tables(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_custom_fields_table ON custom_fields(table_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_custom_fields_order ON custom_fields(sort_order)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_relationships_parent ON table_relationships(parent_table_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_relationships_child ON table_relationships(child_table_id)");
    
    // Insert sample table for demonstration
    $existing = $db->querySingle("SELECT COUNT(*) FROM custom_tables WHERE table_name = 'demo_customers'");
    
    if ($existing == 0) {
        // Create demo table
        $db->exec("
            INSERT INTO custom_tables 
            (table_name, display_name, description, icon, color, is_system, record_count)
            VALUES 
            ('demo_customers', 'Demo Customers', 'Sample customer database for testing', '👥', '#3b82f6', 0, 0)
        ");
        
        $table_id = $db->lastInsertRowID();
        
        // Add sample fields
        $fields = [
            ['name', 'Customer Name', 'text', 0, 1, 0],
            ['email', 'Email Address', 'email', 1, 1, 1],
            ['phone', 'Phone Number', 'phone', 2, 0, 0],
            ['status', 'Status', 'dropdown', 3, 1, 0],
            ['created', 'Created Date', 'datetime', 4, 0, 0]
        ];
        
        foreach ($fields as $idx => $field) {
            $options = '';
            if ($field[2] == 'dropdown') {
                $options = json_encode(['Active', 'Inactive', 'Pending']);
            }
            
            $db->exec("
                INSERT INTO custom_fields 
                (table_id, field_name, display_name, field_type, sort_order, is_required, is_unique, options)
                VALUES 
                ($table_id, '{$field[0]}', '{$field[1]}', '{$field[2]}', {$field[3]}, {$field[4]}, {$field[5]}, '$options')
            ");
        }
    }
    
    $db->close();
    
    // Success message
    $message = "✅ Database builder setup complete!";
    $details = [
        "Database: builder.db created",
        "Tables: custom_tables, custom_fields, table_relationships",
        "Indexes: 6 indexes created for performance",
        "Demo: Sample 'demo_customers' table added",
        "Status: Ready for use"
    ];
    
} catch (Exception $e) {
    $message = "❌ Setup failed: " . $e->getMessage();
    $details = [];
}

// If called via web, show result
if (php_sapi_name() !== 'cli') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Builder Setup</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
                color: #fff;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                max-width: 600px;
                background: rgba(255,255,255,0.05);
                border: 1px solid rgba(255,255,255,0.1);
                border-radius: 12px;
                padding: 40px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            }
            h1 {
                font-size: 2rem;
                margin-bottom: 20px;
                color: #00d9ff;
            }
            .message {
                font-size: 1.3rem;
                margin-bottom: 30px;
                padding: 20px;
                background: rgba(0,217,255,0.1);
                border-left: 4px solid #00d9ff;
                border-radius: 8px;
            }
            .details {
                background: rgba(255,255,255,0.03);
                border-radius: 8px;
                padding: 20px;
            }
            .details ul {
                list-style: none;
            }
            .details li {
                padding: 10px 0;
                border-bottom: 1px solid rgba(255,255,255,0.05);
                color: #aaa;
            }
            .details li:last-child {
                border-bottom: none;
            }
            .details li:before {
                content: "✓ ";
                color: #00ff88;
                font-weight: bold;
                margin-right: 10px;
            }
            .btn {
                display: inline-block;
                margin-top: 30px;
                padding: 12px 30px;
                background: linear-gradient(90deg, #00d9ff, #00ff88);
                color: #0f0f1a;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                transition: all 0.2s;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(0,217,255,0.3);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🗂️ Database Builder Setup</h1>
            
            <div class="message">
                <?php echo $message; ?>
            </div>
            
            <?php if (!empty($details)): ?>
            <div class="details">
                <ul>
                    <?php foreach ($details as $detail): ?>
                        <li><?php echo $detail; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <a href="index.php" class="btn">Go to Database Builder →</a>
        </div>
    </body>
    </html>
    <?php
}
?>
