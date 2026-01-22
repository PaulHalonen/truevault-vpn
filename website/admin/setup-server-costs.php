<?php
/**
 * Setup Server Costs Table
 * 
 * Creates the server_costs table for tracking monthly server expenses
 * 
 * @created January 23, 2026
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Setup Server Costs Table</h1><pre>";

try {
    $serversDb = Database::getInstance('servers');
    
    // Create server_costs table
    echo "Creating server_costs table...\n";
    $serversDb->exec("
        CREATE TABLE IF NOT EXISTS server_costs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            server_id INTEGER NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            currency TEXT DEFAULT 'USD',
            billing_month TEXT NOT NULL,
            billing_date DATE,
            bandwidth_gb DECIMAL(10,2),
            user_count INTEGER,
            uptime_hours DECIMAL(10,2),
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
        )
    ");
    echo "✓ server_costs table created\n";
    
    // Create indexes
    $serversDb->exec("CREATE INDEX IF NOT EXISTS idx_costs_server ON server_costs(server_id)");
    $serversDb->exec("CREATE INDEX IF NOT EXISTS idx_costs_month ON server_costs(billing_month)");
    echo "✓ Indexes created\n";
    
    // Insert initial cost data for January 2026
    echo "\nPopulating initial cost data...\n";
    
    // NY Contabo - $6.75
    $serversDb->exec("
        INSERT OR IGNORE INTO server_costs (server_id, amount, currency, billing_month, billing_date, notes)
        VALUES (1, 6.75, 'USD', '2026-01', '2026-01-25', 'Initial setup - VPS 10 SSD + US East location')
    ");
    echo "✓ NY Contabo: \$6.75/month\n";
    
    // STL Contabo - $6.15
    $serversDb->exec("
        INSERT OR IGNORE INTO server_costs (server_id, amount, currency, billing_month, billing_date, notes)
        VALUES (2, 6.15, 'USD', '2026-01', '2026-01-25', 'Dedicated server - VPS 10 SSD + US Central location')
    ");
    echo "✓ STL Contabo (Dedicated): \$6.15/month\n";
    
    // Dallas Fly.io - ~$5
    $serversDb->exec("
        INSERT OR IGNORE INTO server_costs (server_id, amount, currency, billing_month, billing_date, notes)
        VALUES (3, 5.00, 'USD', '2026-01', '2026-01-25', 'Fly.io shared CPU - streaming optimized')
    ");
    echo "✓ Dallas Fly.io: ~\$5.00/month\n";
    
    // Toronto Fly.io - ~$5
    $serversDb->exec("
        INSERT OR IGNORE INTO server_costs (server_id, amount, currency, billing_month, billing_date, notes)
        VALUES (4, 5.00, 'USD', '2026-01', '2026-01-25', 'Fly.io shared CPU - Canadian streaming')
    ");
    echo "✓ Toronto Fly.io: ~\$5.00/month\n";
    
    // Calculate total
    echo "\n=============================\n";
    echo "TOTAL MONTHLY COST: \$22.90\n";
    echo "=============================\n";
    
    echo "\n✅ Server costs table setup complete!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
