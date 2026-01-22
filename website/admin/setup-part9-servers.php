<?php
/**
 * Part 9 Setup - Server Configuration
 * 
 * This script sets up:
 * 1. API secrets for VPN servers in system_settings
 * 2. Server records with correct public keys and API ports
 * 
 * Run once: https://vpn.the-truth-publishing.com/admin/setup-part9-servers.php
 * 
 * @created January 22, 2026
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<html><head><title>Part 9 Server Setup</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#0f0;padding:20px;} .ok{color:#0f0;} .err{color:#f00;} h1,h2{color:#0ff;}</style>";
echo "</head><body>";
echo "<h1>🔧 Part 9: Server Configuration Setup</h1>";
echo "<pre>";

try {
    // ========================================
    // STEP 1: Add API Secrets to system_settings
    // ========================================
    echo "\n<h2>STEP 1: Setting up API secrets...</h2>\n";
    
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
    echo "<span class='ok'>✓ system_settings table ready</span>\n";
    
    // API Secrets for each server
    $secrets = [
        ['key' => 'server_api_secret_1', 'value' => 'TrueVault2026NYSecretKey32Chars!', 'desc' => 'New York server API secret'],
        ['key' => 'server_api_secret_2', 'value' => 'TrueVault2026STLSecretKey32Char!', 'desc' => 'St. Louis VIP server API secret'],
        ['key' => 'server_api_secret_3', 'value' => 'TrueVault2025-FlyAPI-Secret!', 'desc' => 'Dallas (Fly.io) server API secret'],
        ['key' => 'server_api_secret_4', 'value' => 'TrueVault2025-FlyAPI-Secret!', 'desc' => 'Toronto (Fly.io) server API secret'],
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
        echo "<span class='ok'>✓ Added {$s['key']}</span>\n";
    }
    
    // ========================================
    // STEP 2: Setup Servers Table
    // ========================================
    echo "\n<h2>STEP 2: Setting up servers table...</h2>\n";
    
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
    echo "<span class='ok'>✓ servers table ready</span>\n";
    
    // All 4 VPN Servers - VERIFIED January 22, 2026
    $servers = [
        [
            'id' => 1,
            'name' => 'New York',
            'location' => 'New York, USA',
            'country_code' => 'US',
            'ip_address' => '66.94.103.91',
            'listen_port' => 51820,
            'api_port' => 8443,  // Contabo uses 8443
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
            'api_port' => 8443,  // Contabo uses 8443
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
            'api_port' => 8080,  // Fly.io uses 8080
            'public_key' => 'dFEz/d9TKfddk0Z6aMN03uO+j0GgQwXSR/+Ay+IXXmk=',
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
            'api_port' => 8080,  // Fly.io uses 8080
            'public_key' => 'O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=',
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
    
    foreach ($servers as $s) {
        // Check if server exists
        $stmt = $serversDb->prepare("SELECT id FROM servers WHERE id = :id");
        $stmt->bindValue(':id', $s['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        $exists = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($exists) {
            // Update existing
            $stmt = $serversDb->prepare("
                UPDATE servers SET
                    name = :name,
                    location = :location,
                    country_code = :country_code,
                    ip_address = :ip_address,
                    listen_port = :listen_port,
                    api_port = :api_port,
                    public_key = :public_key,
                    endpoint = :endpoint,
                    provider = :provider,
                    vip_only = :vip_only,
                    dedicated_user_email = :dedicated_email,
                    streaming_optimized = :streaming,
                    ip_pool_start = :pool_start,
                    ip_pool_end = :pool_end,
                    monthly_cost = :cost,
                    updated_at = datetime('now')
                WHERE id = :id
            ");
            echo "<span class='ok'>✓ Updating: {$s['name']}</span>\n";
        } else {
            // Insert new
            $stmt = $serversDb->prepare("
                INSERT INTO servers (
                    id, name, location, country_code, ip_address, listen_port, api_port,
                    public_key, endpoint, provider, vip_only, dedicated_user_email,
                    streaming_optimized, ip_pool_start, ip_pool_end, monthly_cost
                ) VALUES (
                    :id, :name, :location, :country_code, :ip_address, :listen_port, :api_port,
                    :public_key, :endpoint, :provider, :vip_only, :dedicated_email,
                    :streaming, :pool_start, :pool_end, :cost
                )
            ");
            echo "<span class='ok'>✓ Inserting: {$s['name']}</span>\n";
        }
        
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
    }
    
    // ========================================
    // STEP 3: Verify Setup
    // ========================================
    echo "\n<h2>STEP 3: Verification...</h2>\n";
    
    $stmt = $serversDb->prepare("SELECT * FROM servers ORDER BY id");
    $result = $stmt->execute();
    
    echo "\n<span style='color:#ff0'>SERVERS IN DATABASE:</span>\n";
    echo str_repeat("-", 100) . "\n";
    printf("%-3s | %-20s | %-18s | %-6s | %-18s | %-8s | %-4s\n", 
        "ID", "Name", "IP Address", "VIP", "Public Key", "Provider", "Port");
    echo str_repeat("-", 100) . "\n";
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        printf("%-3s | %-20s | %-18s | %-6s | %-18s | %-8s | %-4s\n",
            $row['id'],
            $row['name'],
            $row['ip_address'],
            $row['vip_only'] ? 'YES' : 'NO',
            substr($row['public_key'], 0, 16) . '...',
            $row['provider'],
            $row['api_port']
        );
    }
    
    echo str_repeat("-", 100) . "\n";
    
    // Check API secrets
    echo "\n<span style='color:#ff0'>API SECRETS:</span>\n";
    $stmt = $adminDb->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'server_api_secret%'");
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        echo "  {$row['setting_key']}: " . substr($row['setting_value'], 0, 20) . "...\n";
    }
    
    echo "\n<h2 style='color:#0f0'>✅ SETUP COMPLETE!</h2>\n";
    echo "\nNOTES:\n";
    echo "- Contabo servers (NY, STL) use API port 8443\n";
    echo "- Fly.io servers (Dallas, Toronto) use API port 8080\n";
    echo "- St. Louis server is VIP-only for seige235@yahoo.com\n";
    echo "- All servers have server-side key generation\n";
    
} catch (Exception $e) {
    echo "<span class='err'>ERROR: " . htmlspecialchars($e->getMessage()) . "</span>\n";
}

echo "</pre></body></html>";
