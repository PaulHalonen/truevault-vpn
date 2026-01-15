# SECTION 10: SERVER MANAGEMENT

**Created:** January 15, 2026  
**Status:** Complete Technical Specification  
**Priority:** CRITICAL - Infrastructure  
**Complexity:** HIGH - Multi-Provider Management  

---

## 📋 TABLE OF CONTENTS

1. [Server Inventory](#inventory)
2. [Contabo Servers](#contabo)
3. [Fly.io Servers](#flyio)
4. [WireGuard Setup](#wireguard)
5. [Server Monitoring](#monitoring)
6. [Adding Servers](#adding-servers)
7. [Cost Management](#costs)
8. [Backup & Failover](#backup)
9. [Bandwidth Management](#bandwidth)
10. [SSH Access](#ssh)
11. [Troubleshooting](#troubleshooting)

---

## 🖥️ SERVER INVENTORY

### **Current Infrastructure**

**4 WireGuard Servers:**

```
┌─────────────────────────────────────────────────────────┐
│ SERVER 1: NEW YORK (US-EAST)                            │
│ Provider: Contabo                                        │
│ IP: 66.94.103.91:51820                                   │
│ Purpose: Shared - High Traffic                           │
│ Users: ~400-500                                          │
│ Bandwidth: Limited (fair use)                            │
│ Cost: $6.75/month                                        │
├──────────────────────────────────────────────────────────┤
│ SERVER 2: ST. LOUIS (US-CENTRAL)                        │
│ Provider: Contabo                                        │
│ IP: 144.126.133.253:51820                                │
│ Purpose: VIP ONLY - seige235@yahoo.com                   │
│ Users: 1 (dedicated)                                     │
│ Bandwidth: Limited (fair use)                            │
│ Cost: $6.15/month                                        │
├──────────────────────────────────────────────────────────┤
│ SERVER 3: DALLAS (US-CENTRAL)                           │
│ Provider: Fly.io                                         │
│ IP: 66.241.124.4:51820                                   │
│ Purpose: Shared - Streaming Optimized                    │
│ Users: ~250-300                                          │
│ Bandwidth: Limited (fair use)                            │
│ Cost: ~$5/month (usage-based)                            │
├──────────────────────────────────────────────────────────┤
│ SERVER 4: TORONTO (CANADA)                              │
│ Provider: Fly.io                                         │
│ IP: 66.241.125.247:51820                                 │
│ Purpose: Shared - Canadian Content                       │
│ Users: ~150-200                                          │
│ Bandwidth: Limited (fair use)                            │
│ Cost: ~$5/month (usage-based)                            │
└──────────────────────────────────────────────────────────┘

TOTAL COST: ~$23/month
TOTAL CAPACITY: ~1,200 users
```

### **Server Database**

**Table: servers**

```sql
CREATE TABLE IF NOT EXISTS servers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    -- Basic Info
    name TEXT NOT NULL UNIQUE,
    location TEXT NOT NULL,
    country_code TEXT,
    
    -- Connection Details
    ip_address TEXT NOT NULL,
    port INTEGER DEFAULT 51820,
    public_key TEXT NOT NULL,
    endpoint TEXT NOT NULL,  -- ip:port
    
    -- Provider Info
    provider TEXT,  -- contabo, flyio, etc
    provider_id TEXT,  -- vmi2990026, etc
    
    -- Configuration
    is_active BOOLEAN DEFAULT 1,
    is_visible BOOLEAN DEFAULT 1,  -- Show to users
    max_users INTEGER DEFAULT 500,
    current_users INTEGER DEFAULT 0,
    
    -- Access Control
    access_level TEXT DEFAULT 'public',  -- public, vip, specific
    vip_email TEXT,  -- If dedicated to one VIP
    allowed_users TEXT,  -- JSON array of user IDs
    
    -- Features
    streaming_optimized BOOLEAN DEFAULT 0,
    port_forwarding BOOLEAN DEFAULT 1,
    
    -- Cost Tracking
    monthly_cost DECIMAL(10,2),
    currency TEXT DEFAULT 'USD',
    
    -- Performance
    bandwidth_limit TEXT,  -- e.g., "1TB", "unlimited"
    bandwidth_used BIGINT DEFAULT 0,  -- bytes
    uptime_percentage DECIMAL(5,2),
    
    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_checked DATETIME
);
```

**Initial Data:**

```sql
INSERT INTO servers (name, location, country_code, ip_address, port, public_key, endpoint, provider, provider_id, max_users, access_level, monthly_cost, streaming_optimized) VALUES
('New York', 'US-East', 'US', '66.94.103.91', 51820, 
'[NEW_YORK_PUBLIC_KEY]',
'66.94.103.91:51820',
'contabo', 'vmi2990026', 500, 'public', 6.75, 0),

('St. Louis', 'US-Central', 'US', '144.126.133.253', 51820,
'[ST_LOUIS_PUBLIC_KEY]',
'144.126.133.253:51820',
'contabo', 'vmi2990005', 1, 'vip', 6.15, 0),

('Dallas', 'US-Central', 'US', '66.241.124.4', 51820,
'[DALLAS_PUBLIC_KEY]',
'66.241.124.4:51820',
'flyio', 'truevault-dallas', 300, 'public', 5.00, 1),

('Toronto', 'Canada', 'CA', '66.241.125.247', 51820,
'[TORONTO_PUBLIC_KEY]',
'66.241.125.247:51820',
'flyio', 'truevault-toronto', 200, 'public', 5.00, 0);

-- VIP-specific entry
UPDATE servers 
SET vip_email = 'seige235@yahoo.com',
    is_visible = 0  -- Hidden from regular users
WHERE name = 'St. Louis';
```

---

## 🏢 CONTABO SERVERS

### **Account Information**

```
Login: paulhalonen@gmail.com
Password: Asasasas4!
URL: https://my.contabo.com

Balance: $0.00
Monthly Cost: $12.90 ($6.75 + $6.15)
Payment Method: Credit Card (auto-pay)
Next Billing: Jan 25, 2026
```

### **Server 1: New York (vmi2990026)**

**Specifications:**
```
Plan: Cloud VPS 10 SSD (no setup)
Host System: 21597
Region: US-east
IP Address: 66.94.103.91
MAC Address: 00:50:56:5f:37:1f
IPv6: 2605:a142:2299:0026:0000:0000:0000:0001/64
OS: Linux (Ubuntu 22.04)
Disk Space: 150 GB SSD
RAM: 10 GB
CPU: 4 vCores
Creation Date: Dec 25, 2025
Monthly Price: $6.75
```

**VNC Access:**
```
Enabled: Yes
URL: 154.53.39.97:63031
```

**Management:**
```php
<?php
// File: /includes/contabo.php

class ContaboAPI {
    private $clientId;
    private $clientSecret;
    private $apiUrl = 'https://api.contabo.com/v1';
    
    public function __construct() {
        // Credentials from database
        $config = getServerConfig('contabo');
        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
    }
    
    private function getAccessToken() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->apiUrl}/auth/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->clientId}:{$this->clientSecret}");
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
    
    public function getServerStatus($serverId) {
        $token = $this->getAccessToken();
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->apiUrl}/compute/instances/{$serverId}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$token}",
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function restartServer($serverId) {
        $token = $this->getAccessToken();
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->apiUrl}/compute/instances/{$serverId}/actions/restart");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$token}",
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function getUsageStats($serverId) {
        $token = $this->getAccessToken();
        
        // Get bandwidth, CPU, RAM usage
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->apiUrl}/compute/instances/{$serverId}/stats");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$token}",
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
```

### **Server 2: St. Louis (vmi2990005)**

**Specifications:**
```
Plan: Cloud VPS 10 SSD (no setup)
Host System: 22638
Region: US-central
IP Address: 144.126.133.253
MAC Address: 00:50:56:5f:37:1c
IPv6: 2605:a140:2299:0005:0000:0000:0000:0001/64
OS: Linux (Ubuntu 22.04)
Disk Space: 150 GB SSD
RAM: 10 GB
CPU: 4 vCores
Creation Date: Dec 25, 2025
Monthly Price: $6.15

DEDICATED TO: seige235@yahoo.com
ACCESS: VIP ONLY
```

**VNC Access:**
```
Enabled: Yes
URL: 207.244.248.38:63098
```

---

## ☁️ FLY.IO SERVERS

### **Account Information**

```
Login: paulhalonen@gmail.com
Password: Asasasas4!
URL: https://fly.io/dashboard

Billing: Usage-based (~$5 per server/month)
Payment Method: Credit Card (auto-pay)
```

### **Server 3: Dallas (truevault-dallas)**

**Specifications:**
```
App Name: truevault-dallas
Region: dfw (Dallas, Texas)
Machine Size: shared-1x-cpu@256MB
Instances: 1
Status: Running

IP Address: 66.241.124.4 (Shared IPv4)
Release IP: 137.66.58.225 (v4)

Services:
  - WireGuard: Port 51820
  - HTTP: Port 8443

Features: Streaming optimized
Purpose: Netflix, Hulu, streaming services
```

**Configuration:**

**File: fly.toml (Dallas)**
```toml
app = "truevault-dallas"
primary_region = "dfw"

[build]
  image = "wireguard/wireguard:latest"

[[services]]
  internal_port = 51820
  protocol = "udp"
  
  [[services.ports]]
    port = 51820

[[services]]
  internal_port = 8080
  protocol = "tcp"
  
  [[services.ports]]
    port = 8443

[env]
  TZ = "America/Chicago"
  SERVERURL = "66.241.124.4"
  SERVERPORT = "51820"
  PEERS = "500"
  PEERDNS = "1.1.1.1"
  INTERNAL_SUBNET = "10.13.13.0"

[[vm]]
  cpu_kind = "shared"
  cpus = 1
  memory_mb = 256
```

### **Server 4: Toronto (truevault-toronto)**

**Specifications:**
```
App Name: truevault-toronto
Region: yyz (Toronto, Canada)
Machine Size: shared-1x-cpu@256MB
Instances: 1
Status: Running

IP Address: 66.241.125.247 (Shared IPv4)
Release IP: 37.16.6.139 (v4)

Services:
  - WireGuard: Port 51820
  - HTTP: Port 8080

Purpose: Canadian content (CBC, TSN, etc)
```

**File: fly.toml (Toronto)**
```toml
app = "truevault-toronto"
primary_region = "yyz"

[build]
  image = "wireguard/wireguard:latest"

[[services]]
  internal_port = 51820
  protocol = "udp"
  
  [[services.ports]]
    port = 51820

[[services]]
  internal_port = 8080
  protocol = "tcp"
  
  [[services.ports]]
    port = 8080

[env]
  TZ = "America/Toronto"
  SERVERURL = "66.241.125.247"
  SERVERPORT = "51820"
  PEERS = "200"
  PEERDNS = "1.1.1.1"
  INTERNAL_SUBNET = "10.13.14.0"

[[vm]]
  cpu_kind = "shared"
  cpus = 1
  memory_mb = 256
```

### **Fly.io Management**

```php
<?php
// File: /includes/flyio.php

class FlyioAPI {
    private $apiToken;
    private $apiUrl = 'https://api.fly.io/graphql';
    
    public function __construct() {
        $config = getServerConfig('flyio');
        $this->apiToken = $config['api_token'];
    }
    
    public function getAppStatus($appName) {
        $query = '
            query($appName: String!) {
                app(name: $appName) {
                    name
                    status
                    deployed
                    currentRelease {
                        version
                        createdAt
                    }
                    machines {
                        nodes {
                            id
                            state
                            region
                            instanceId
                        }
                    }
                }
            }
        ';
        
        return $this->graphqlRequest($query, ['appName' => $appName]);
    }
    
    public function restartApp($appName) {
        $query = '
            mutation($input: RestartAppInput!) {
                restartApp(input: $input) {
                    app {
                        name
                        status
                    }
                }
            }
        ';
        
        $input = ['appName' => $appName];
        return $this->graphqlRequest($query, ['input' => $input]);
    }
    
    public function getAppMetrics($appName) {
        // Get CPU, memory, network metrics
        $query = '
            query($appName: String!) {
                app(name: $appName) {
                    metrics {
                        cpu
                        memory
                        network {
                            inBytes
                            outBytes
                        }
                    }
                }
            }
        ';
        
        return $this->graphqlRequest($query, ['appName' => $appName]);
    }
    
    private function graphqlRequest($query, $variables = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'query' => $query,
            'variables' => $variables
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "Authorization: Bearer {$this->apiToken}"
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
```

---

## 🔧 WIREGUARD SETUP

### **Installation Script**

**Run on new server:**

```bash
#!/bin/bash
# File: setup-wireguard.sh

set -e

echo "Installing WireGuard..."

# Update system
apt update && apt upgrade -y

# Install WireGuard
apt install -y wireguard wireguard-tools

# Enable IP forwarding
echo "net.ipv4.ip_forward=1" >> /etc/sysctl.conf
echo "net.ipv6.conf.all.forwarding=1" >> /etc/sysctl.conf
sysctl -p

# Generate server keys
cd /etc/wireguard
umask 077
wg genkey | tee server_private.key | wg pubkey > server_public.key

SERVER_PRIVATE=$(cat server_private.key)
SERVER_PUBLIC=$(cat server_public.key)

echo "Server Public Key: $SERVER_PUBLIC"
echo "Save this key in your database!"

# Create WireGuard config
cat > /etc/wireguard/wg0.conf << EOF
[Interface]
PrivateKey = $SERVER_PRIVATE
Address = 10.8.0.1/24
ListenPort = 51820
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE

# Peers will be added dynamically
EOF

# Set permissions
chmod 600 /etc/wireguard/wg0.conf

# Enable and start WireGuard
systemctl enable wg-quick@wg0
systemctl start wg-quick@wg0

# Allow port through firewall
ufw allow 51820/udp
ufw allow OpenSSH
ufw --force enable

echo "WireGuard setup complete!"
echo "Server Public Key: $SERVER_PUBLIC"
```

### **Dynamic Peer Management**

**Add peer to server:**

```php
<?php
function addPeerToServer($serverId, $deviceId, $publicKey, $allowedIPs) {
    $server = getServer($serverId);
    
    // SSH to server and add peer
    $sshCommand = "
        wg set wg0 peer {$publicKey} allowed-ips {$allowedIPs}
    ";
    
    $result = executeSSH($server['ip_address'], $sshCommand);
    
    if ($result['success']) {
        // Update database
        updateDeviceServer($deviceId, $serverId);
        
        // Log action
        logServerAction($serverId, "Added peer: {$publicKey}");
        
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => $result['error']];
}

function removePeerFromServer($serverId, $publicKey) {
    $server = getServer($serverId);
    
    // SSH to server and remove peer
    $sshCommand = "
        wg set wg0 peer {$publicKey} remove
    ";
    
    $result = executeSSH($server['ip_address'], $sshCommand);
    
    if ($result['success']) {
        logServerAction($serverId, "Removed peer: {$publicKey}");
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => $result['error']];
}
```

---

## 📊 SERVER MONITORING

### **Health Check System**

```php
<?php
// File: /cron/check-servers.php

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/servers.php';

// Run every 5 minutes via cron

function checkAllServers() {
    global $db_main;
    
    $servers = $db_main->query("SELECT * FROM servers WHERE is_active = 1")->fetchAll();
    
    foreach ($servers as $server) {
        $status = checkServerHealth($server);
        updateServerStatus($server['id'], $status);
        
        if (!$status['online']) {
            alertAdmin($server, $status);
        }
    }
}

function checkServerHealth($server) {
    $endpoint = $server['endpoint'];
    $ip = $server['ip_address'];
    $port = $server['port'];
    
    $checks = [
        'ping' => pingServer($ip),
        'wireguard' => checkWireGuardPort($ip, $port),
        'ssh' => checkSSH($ip),
        'load' => getServerLoad($server),
        'disk' => getDiskUsage($server),
        'bandwidth' => getBandwidthUsage($server)
    ];
    
    $online = $checks['ping'] && $checks['wireguard'];
    
    return [
        'online' => $online,
        'checks' => $checks,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

function pingServer($ip) {
    $command = "ping -c 1 -W 2 {$ip}";
    exec($command, $output, $returnCode);
    return ($returnCode === 0);
}

function checkWireGuardPort($ip, $port) {
    // Check if UDP port is responding
    $socket = @fsockopen("udp://{$ip}", $port, $errno, $errstr, 2);
    if ($socket) {
        fclose($socket);
        return true;
    }
    return false;
}

function getServerLoad($server) {
    // SSH and get load average
    $command = "uptime | awk -F'load average:' '{print \$2}'";
    $result = executeSSH($server['ip_address'], $command);
    
    if ($result['success']) {
        $load = trim($result['output']);
        return $load;
    }
    
    return 'unknown';
}

function getDiskUsage($server) {
    $command = "df -h / | awk 'NR==2 {print \$5}' | sed 's/%//'";
    $result = executeSSH($server['ip_address'], $command);
    
    if ($result['success']) {
        return intval(trim($result['output']));
    }
    
    return 0;
}

function updateServerStatus($serverId, $status) {
    global $db_main;
    
    $stmt = $db_main->prepare("
        UPDATE servers 
        SET last_checked = CURRENT_TIMESTAMP,
            uptime_percentage = ?
        WHERE id = ?
    ");
    
    $uptime = $status['online'] ? 100 : 0;
    $stmt->execute([$uptime, $serverId]);
    
    // Log status
    $stmt = $db_main->prepare("
        INSERT INTO server_logs (server_id, status, details, checked_at)
        VALUES (?, ?, ?, CURRENT_TIMESTAMP)
    ");
    
    $stmt->execute([
        $serverId,
        $status['online'] ? 'online' : 'offline',
        json_encode($status['checks'])
    ]);
}

function alertAdmin($server, $status) {
    $subject = "ALERT: Server Down - {$server['name']}";
    $message = "
        Server: {$server['name']}
        Location: {$server['location']}
        IP: {$server['ip_address']}
        Status: OFFLINE
        
        Failed checks:
        - Ping: " . ($status['checks']['ping'] ? 'OK' : 'FAILED') . "
        - WireGuard: " . ($status['checks']['wireguard'] ? 'OK' : 'FAILED') . "
        
        Time: " . $status['timestamp'] . "
        
        Action required: Check server status immediately.
    ";
    
    sendAdminEmail($subject, $message);
    
    // Also send SMS if critical
    if (!$status['checks']['ping']) {
        sendSMS('+1234567890', "CRITICAL: {$server['name']} is offline!");
    }
}

// Run checks
checkAllServers();
```

### **Cron Setup**

```bash
# Add to crontab
*/5 * * * * php /path/to/vpn/cron/check-servers.php
```

---

## ➕ ADDING NEW SERVERS

### **Step-by-Step Process**

**1. Provision Server**

Choose provider:
- **Contabo:** Good for dedicated VPS
- **Fly.io:** Good for quick deployment
- **DigitalOcean:** Alternative option
- **AWS/Google Cloud:** Enterprise option

**2. Install WireGuard**

```bash
# SSH to new server
ssh root@NEW_SERVER_IP

# Run setup script
curl -O https://vpn.the-truth-publishing.com/scripts/setup-wireguard.sh
chmod +x setup-wireguard.sh
./setup-wireguard.sh
```

**3. Add to Database**

```php
<?php
// Admin panel: Add Server

$stmt = $db_main->prepare("
    INSERT INTO servers (
        name, location, country_code,
        ip_address, port, public_key, endpoint,
        provider, provider_id, max_users,
        access_level, monthly_cost
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    'San Francisco',  // name
    'US-West',        // location
    'US',             // country_code
    '123.45.67.89',   // ip_address
    51820,            // port
    $serverPublicKey, // public_key
    '123.45.67.89:51820',  // endpoint
    'digitalocean',   // provider
    'droplet-12345',  // provider_id
    500,              // max_users
    'public',         // access_level
    10.00             // monthly_cost
]);
```

**4. Test Connection**

```php
<?php
// Test new server
$result = checkServerHealth($newServer);

if ($result['online']) {
    echo "Server is online and ready!";
    
    // Make visible to users
    $stmt = $db_main->prepare("UPDATE servers SET is_visible = 1 WHERE id = ?");
    $stmt->execute([$newServerId]);
} else {
    echo "Server check failed: " . json_encode($result);
}
```

---

## 💰 COST MANAGEMENT

### **Monthly Cost Tracking**

```sql
CREATE TABLE IF NOT EXISTS server_costs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    server_id INTEGER NOT NULL,
    
    -- Cost Details
    amount DECIMAL(10,2) NOT NULL,
    currency TEXT DEFAULT 'USD',
    
    -- Billing Period
    billing_month TEXT NOT NULL,  -- YYYY-MM
    billing_date DATE,
    
    -- Usage Stats
    bandwidth_gb DECIMAL(10,2),
    user_count INTEGER,
    uptime_hours DECIMAL(10,2),
    
    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
);
```

**Monthly Report:**

```php
<?php
function generateCostReport($month = null) {
    global $db_main;
    
    if (!$month) {
        $month = date('Y-m');
    }
    
    $stmt = $db_main->prepare("
        SELECT s.name, s.provider, sc.amount, sc.bandwidth_gb, sc.user_count
        FROM server_costs sc
        JOIN servers s ON s.id = sc.server_id
        WHERE sc.billing_month = ?
        ORDER BY sc.amount DESC
    ");
    $stmt->execute([$month]);
    
    $costs = $stmt->fetchAll();
    
    $totalCost = array_sum(array_column($costs, 'amount'));
    
    return [
        'month' => $month,
        'servers' => $costs,
        'total_cost' => $totalCost,
        'avg_per_server' => $totalCost / count($costs)
    ];
}
```

---

## 🔄 BACKUP & FAILOVER

### **Automatic Failover**

```php
<?php
// If primary server goes down, redirect users to backup

function handleServerFailover($failedServerId) {
    global $db_main;
    
    // Get all users on failed server
    $stmt = $db_main->prepare("
        SELECT * FROM devices WHERE current_server_id = ?
    ");
    $stmt->execute([$failedServerId]);
    $devices = $stmt->fetchAll();
    
    // Find backup server (lowest load)
    $backupServer = findBackupServer($failedServerId);
    
    if (!$backupServer) {
        alertAdmin("No backup server available!");
        return;
    }
    
    // Migrate users
    foreach ($devices as $device) {
        migrateDeviceToServer($device['id'], $backupServer['id']);
        
        // Notify user
        notifyUserServerChange(
            $device['user_id'],
            $failedServerId,
            $backupServer['id']
        );
    }
    
    logEvent("Failover complete: {$failedServerId} -> {$backupServer['id']}");
}

function findBackupServer($excludeId) {
    global $db_main;
    
    $stmt = $db_main->prepare("
        SELECT * FROM servers 
        WHERE is_active = 1 
        AND id != ?
        AND access_level = 'public'
        AND current_users < max_users
        ORDER BY current_users ASC
        LIMIT 1
    ");
    $stmt->execute([$excludeId]);
    
    return $stmt->fetch();
}
```

---

## 📶 BANDWIDTH MANAGEMENT

### **Tracking Usage**

```php
<?php
function trackBandwidth($serverId) {
    $server = getServer($serverId);
    
    // SSH to server and get bandwidth stats
    $command = "
        vnstat --json | jq -r '.interfaces[0].traffic.total'
    ";
    
    $result = executeSSH($server['ip_address'], $command);
    
    if ($result['success']) {
        $data = json_decode($result['output'], true);
        
        $bytesRx = $data['rx'] ?? 0;
        $bytesTx = $data['tx'] ?? 0;
        $totalGB = ($bytesRx + $bytesTx) / 1073741824;  // Convert to GB
        
        // Update database
        updateServerBandwidth($serverId, $totalGB);
        
        return $totalGB;
    }
    
    return 0;
}

function checkBandwidthLimits($serverId) {
    $server = getServer($serverId);
    $usage = $server['bandwidth_used'] / 1073741824;  // GB
    
    // Contabo: ~1TB fair use
    // Fly.io: Unlimited but costs increase with usage
    
    if ($server['provider'] === 'contabo' && $usage > 900) {
        // Approaching 1TB limit
        alertAdmin("Server {$server['name']} approaching bandwidth limit: {$usage}GB");
    }
    
    if ($usage > 0.85 * getProviderLimit($server['provider'])) {
        // At 85% of limit - consider adding more servers
        return true;
    }
    
    return false;
}
```

---

## 🔑 SSH ACCESS

### **SSH Key Management**

```bash
# Generate SSH key for admin access
ssh-keygen -t ed25519 -C "admin@truthvault.com" -f ~/.ssh/truthvault_admin

# Add public key to all servers
cat ~/.ssh/truthvault_admin.pub | ssh root@66.94.103.91 "cat >> ~/.ssh/authorized_keys"
cat ~/.ssh/truthvault_admin.pub | ssh root@144.126.133.253 "cat >> ~/.ssh/authorized_keys"
```

### **SSH Helper Functions**

```php
<?php
function executeSSH($host, $command, $user = 'root') {
    $keyPath = '/path/to/private/key';
    
    $sshCommand = sprintf(
        "ssh -i %s -o StrictHostKeyChecking=no %s@%s '%s'",
        escapeshellarg($keyPath),
        escapeshellarg($user),
        escapeshellarg($host),
        escapeshellarg($command)
    );
    
    exec($sshCommand, $output, $returnCode);
    
    return [
        'success' => ($returnCode === 0),
        'output' => implode("\n", $output),
        'code' => $returnCode
    ];
}
```

---

## 🔧 TROUBLESHOOTING

### **Common Issues**

**Server Not Responding:**
```bash
# Check if server is up
ping 66.94.103.91

# Check WireGuard status
ssh root@66.94.103.91 "wg show"

# Restart WireGuard
ssh root@66.94.103.91 "systemctl restart wg-quick@wg0"
```

**High CPU/Memory:**
```bash
# Check system resources
ssh root@66.94.103.91 "top -bn1 | head -20"

# Check WireGuard peers
ssh root@66.94.103.91 "wg show wg0 | grep peer | wc -l"

# If overloaded, migrate some users to another server
```

**Bandwidth Issues:**
```bash
# Check current bandwidth
ssh root@66.94.103.91 "vnstat"

# Check per-peer bandwidth (if installed)
ssh root@66.94.103.91 "iftop -i wg0"
```

**Port Forwarding Not Working:**
```bash
# Check iptables rules
ssh root@66.94.103.91 "iptables -t nat -L -n -v"

# Check if port is listening
ssh root@66.94.103.91 "netstat -tuln | grep 8080"
```

---

**END OF SECTION 10: SERVER MANAGEMENT**

**Next Section:** Section 11 (WireGuard Configuration)  
**Status:** Section 10 Complete ✅  
**Lines:** ~1,500 lines  
**Created:** January 15, 2026 - 5:25 AM CST
