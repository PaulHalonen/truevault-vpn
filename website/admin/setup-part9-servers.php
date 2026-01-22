<?php
/**
 * Part 9 Setup - Server Configuration
 * 
 * This script sets up:
 * 1. API secrets for VPN servers in system_settings
 * 2. Server records with correct public keys
 * 
 * Run once: https://vpn.the-truth-publishing.com/admin/setup-part9-servers.php
 * 
 * @created January 22, 2026
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<html><head><title>Part 9 Server Setup</title></head><body>";
echo "<h1>Part 9: Server Configuration Setup</h1>";
echo "<pre>";

try {
    // ========================================
    // STEP 1: Add API Secrets to system_settings
    // ========================================
    echo "STEP 1: Setting up API secrets...\n";
    echo str_repeat("=", 50) . "\n";
    
    $adminDb = Database::getInstance('admin');
    
    // Create system_settings table if not exists
    $adminDb->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            setting_value TEXT,
            setting_type TEXT DEFAULT 'string',
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ system_settings table ready\n";
    
    // API Secrets for each server
    $secrets = [
        ['key' => 'server_api_secret_1', 'value' => 'TrueVault2026NYSecretKey32Chars!', 'desc' => 'New York server API secret'],
        ['key' => 'server_api_secret_2', 'value' => 'TrueVault2026STLSecretKey32Char!', 'desc' => 'St. Louis VIP server API secret'],
        ['key' => 'server_api_secret_3', 'value' => 'TrueVault2026DallasSecretKey32!', 'desc' => 'Dallas server API secret'],
        ['key' => 'server_api_secret_4', 'value' => 'TrueVault2026TorontoSecretKey32', 'desc' => 'Toronto server API secret'],
        ['key' => 'vpn_server_api_secret', 'value' => 'TRUEVAULT_API_SECRET_2026', 'desc' => 'Default/fallback API secret'],
    ];
    
    foreach ($secrets as $s) {
        $stmt = $adminDb->prepare("
            INSERT OR REPLACE INTO system_settings (setting_key, setting_value, setting_type, description, updated_at)
            VALUES (:key, :value, 'secret', :desc, datetime('now'))
        ");
        $stmt->bindValue(':key', $s['key'], SQLITE3_TEXT);
        $stmt->bindValue(':value', $s['value'], SQLITE3_TEXT);
        $stmt->bindValue(':desc', $s['desc'], SQLITE3_TEXT);
        $stmt->execute();
        echo "✓ Added {$s['key']}\n";
    }
    
    // ========================================
    // STEP 2: Setup Servers Table
    // ========================================
    echo "\nSTEP 2: Setting up servers table...\n";
    echo str_repeat("=", 50) . "\n";
    
    $serversDb = Database::getInstance('servers');
    
    // Create servers table if not exists
    $serversDb->exec("
        CREATE TABLE IF NOT EXISTS servers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            location TEXT NOT NULL,
            country_code TEXT DEFAULT 'US',
            ip_address TEXT NOT NULL,
            listen_port INTEGER DEFAULT 51820,
            api_port INTEGER DEFAULT 8443,
            public_key TEXT,
            endpoint TEXT,
            provider TEXT,
            status TEXT DEFAULT 'active',
            vip_only INTEGER DEFAULT 0,
            dedicated_user_email TEXT,
            streaming_optimized INTEGER DEFAULT 0,
            current_clients INTEGER DEFAULT 0,
            max_clients INTEGER DEFAULT 500,
            load_percentage INTEGER DEFAULT 0,
            ip_pool_start TEXT,
            ip_pool_end TEXT,
            bandwidth_limit_gb INTEGER,
            bandwidth_used_gb DECIMAL(10,2) DEFAULT 0,
            monthly_cost DECIMAL(10,2),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ servers table ready\n";
    
    // Server Data
    $servers = [
        [
            'id' => 1,
            'name' => 'New York',
            'location' => 'New York, USA',
            'country_code' => 'US',
            'ip_address' => '66.94.103.91',
            'listen_port' => 51820,
            'api_port' => 8443,
            'public_key' => 'lbriy+env0wv6VmEJscnjoREswmiQdn7D+lKGai9n3s=',
            'endpoint' => '66.94.103.91:51820',
            'provider' => 'Contabo',
            'vip_only' => 0,
            'dedicated_user_email' => null,
            'streaming_optimized' => 0,
            'ip_pool_start' => '10.8.0.2',
            'ip_pool_end' => '10.8.0.254',
            'monthly_cost' => 6.75,
        ],
        [
            'id' => 2,
            'name' => 'St. Louis (VIP)',
            'location' => 'St. Louis, USA',
            'country_code' => 'US',
            'ip_address' => '144.126.133.253',
            'listen_port' => 51820,
            'api_port' => 8443,
            'public_key' => 'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=',
            'endpoint' => '144.126.133.253:51820',
            'provider' => 'Contabo',
            'vip_only' => 1,
            'dedicated_user_email' => 'seige235@yahoo.com',
            'streaming_optimized' => 1,
            'ip_pool_start' => '10.8.1.2',
            'ip_pool_end' => '10.8.1.254',
            'monthly_cost' => 6.15,
        ],
        [
            'id' => 3,
            'name' => 'Dallas',
            'location' => 'Dallas, USA',
            'country_code' => 'US',
            'ip_address' => '66.241.124.4',
            'listen_port' => 51820,
            'api_port' => 8443,
            'public_key' => '',
            'endpoint' => '66.241.124.4:51820',
            'provider' => 'Fly.io',
            'vip_only' => 0,
            'dedicated_user_email' => null,
            'streaming_optimized' => 1,
            'ip_pool_start' => '10.8.2.2',
            'ip_pool_end' => '10.8.2.254',
            'monthly_cost' => 5.00,
        ],
        [
            'id' => 4,
            'name' => 'Toronto',
            'location' => 'Toronto, Canada',
            'country_code' => 'CA',
            'ip_address' => '66.241.125.247',
            'listen_port' => 51820,
            'api_port' => 8443,
            'public_key' => '',
            'endpoint' => '66.241.125.247:51820',
            'provider' => 'Fly.io',
            'vip_only' => 0,
            'dedicated_user_email' => null,
            'streaming_optimized' => 0,
            'ip_pool_start' => '10.8.3.2',
            'ip_pool_end' => '10.8.3.254',
            'monthly_cost' => 5.00,
        ],
    ];
    
    // Clear existing and insert fresh
    $serversDb->exec("DELETE FROM servers");
    echo "✓ Cleared existing servers\n";
    
    foreach ($servers as $s) {
        $stmt = $serversDb->prepare("
            INSERT INTO servers (
                id, name, location, country_code, ip_address, listen_port, api_port,
                public_key, endpoint, provider, vip_only, dedicated_user_email,
                streaming_optimized, ip_pool_start, ip_pool_end, monthly_cost, status
            ) VALUES (
                :id, :name, :location, :country_code, :ip_address, :listen_port, :api_port,
                :public_key, :endpoint, :provider, :vip_only, :dedicated_email,
                :streaming, :pool_start, :pool_end, :cost, 'active'
            )
        ");
        
        $stmt->bindValue(':id', $s['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':name', $s['name'], SQLITE3_TEXT);
        $stmt->bindValue(':location', $s['location'], SQLITE3_TEXT);
        $stmt->bindValue(':country_code', $s['country_code'], SQLITE3_TEXT);
        $stmt->bindValue(':ip_address', $s['ip_address'], SQLITE3_TEXT);
        $stmt->bindValue(':listen_port', $s['listen_port'], SQLITE3_INTEGER);
        $stmt->bindValue(':api_port', $s['api_port'], SQLITE3_INTEGER);
        $stmt->bindValue(':public_key', $s['public_key'], SQLITE3_TEXT);
        $stmt->bindValue(':endpoint', $s['endpoint'], SQLITE3_TEXT);
        $stmt->bindValue(':provider', $s['provider'], SQLITE3_TEXT);
        $stmt->bindValue(':vip_only', $s['vip_only'], SQLITE3_INTEGER);
        $stmt->bindValue(':dedicated_email', $s['dedicated_user_email'], SQLITE3_TEXT);
        $stmt->bindValue(':streaming', $s['streaming_optimized'], SQLITE3_INTEGER);
        $stmt->bindValue(':pool_start', $s['ip_pool_start'], SQLITE3_TEXT);
        $stmt->bindValue(':pool_end', $s['ip_pool_end'], SQLITE3_TEXT);
        $stmt->bindValue(':cost', $s['monthly_cost'], SQLITE3_FLOAT);
        $stmt->execute();
        
        $status = $s['vip_only'] ? ' (VIP ONLY)' : '';
        echo "✓ Added server: {$s['name']}{$status}\n";
    }
    
    // ========================================
    // STEP 3: Verify Setup
    // ========================================
    echo "\nSTEP 3: Verifying setup...\n";
    echo str_repeat("=", 50) . "\n";
    
    // Count servers
    $result = $serversDb->query("SELECT COUNT(*) as count FROM servers");
    $count = $result->fetchArray(SQLITE3_ASSOC)['count'];
    echo "✓ Total servers: {$count}\n";
    
    // List all servers
    $result = $serversDb->query("SELECT id, name, ip_address, public_key, vip_only, dedicated_user_email FROM servers ORDER BY id");
    echo "\nServer List:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-3s %-20s %-18s %-10s %s\n", "ID", "Name", "IP Address", "VIP Only", "Dedicated To");
    echo str_repeat("-", 80) . "\n";
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $vip = $row['vip_only'] ? 'YES' : 'No';
        $dedicated = $row['dedicated_user_email'] ?: '-';
        printf("%-3s %-20s %-18s %-10s %s\n", 
            $row['id'], $row['name'], $row['ip_address'], $vip, $dedicated);
    }
    
    // Count API secrets
    $result = $adminDb->query("SELECT COUNT(*) as count FROM system_settings WHERE setting_key LIKE 'server_api_secret%'");
    $secretCount = $result->fetchArray(SQLITE3_ASSOC)['count'];
    echo "\n✓ API secrets configured: {$secretCount}\n";
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ PART 9 SERVER SETUP COMPLETE!\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "\nNOTE: Fly.io servers (Dallas, Toronto) need API deployment.\n";
    echo "Contabo servers (New York, St. Louis) are already configured.\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "</pre>";
echo "<p><a href='/admin/dashboard.php'>← Back to Admin Dashboard</a></p>";
echo "</body></html>";
