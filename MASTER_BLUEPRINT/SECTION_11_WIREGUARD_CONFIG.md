# SECTION 11: WIREGUARD CONFIGURATION

**Created:** January 15, 2026  
**Status:** Complete Technical Specification  
**Priority:** CRITICAL - Core VPN Protocol  
**Complexity:** HIGH - Cryptographic Implementation  

---

## 📋 TABLE OF CONTENTS

1. [WireGuard Overview](#overview)
2. [Key Generation](#key-generation)
3. [Configuration Files](#config-files)
4. [Server Configuration](#server-config)
5. [Client Configuration](#client-config)
6. [IP Allocation](#ip-allocation)
7. [DNS Configuration](#dns)
8. [Platform-Specific Setup](#platforms)
9. [QR Code Generation](#qr-codes)
10. [Connection Management](#connections)
11. [Security Best Practices](#security)

---

## 🔐 WIREGUARD OVERVIEW

### **What is WireGuard?**

**Modern VPN Protocol:**
- ✅ Extremely fast (faster than OpenVPN, IPSec)
- ✅ Simple code (~4,000 lines vs OpenVPN's 100,000+)
- ✅ State-of-the-art cryptography (Curve25519, ChaCha20, Poly1305)
- ✅ Built into Linux kernel (5.6+)
- ✅ Cross-platform (Windows, Mac, iOS, Android, Linux)
- ✅ Low overhead (perfect for mobile)

### **How It Works**

```
CLIENT                          SERVER
  ↓                              ↓
[Generate Key Pair]         [Generate Key Pair]
  ↓                              ↓
[Public Key] -------→     [Public Key]
  ↓                              ↓
[Config File]              [Add Peer]
  ↓                              ↓
[Connect] ---------------→ [Authenticate]
  ↓                              ↓
[Encrypted Tunnel Established]
  ↓                              ↓
[Traffic flows through tunnel]
```

### **Key Pairs**

**Every device has:**
- **Private Key:** Secret, never shared (stays on device)
- **Public Key:** Shared with server (identifies device)

**Server has:**
- **Private Key:** Secret (stays on server)
- **Public Key:** Shared with all clients

**Cryptography:**
```
Device generates:
Private Key → (Curve25519 algorithm) → Public Key

Server recognizes:
Public Key → Authenticates device → Allows connection
```

---

## 🔑 KEY GENERATION

### **Browser-Side Key Generation**

**Why browser-side?**
- ✅ Instant results (no server wait)
- ✅ More secure (keys never sent to server until encrypted)
- ✅ Better user experience
- ✅ Reduces server load

**Using TweetNaCl.js:**

```html
<!-- Include library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tweetnacl/1.0.3/nacl.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tweetnacl-util/0.15.1/nacl-util.min.js"></script>

<script>
// Generate WireGuard-compatible keys
function generateWireGuardKeys() {
    // Generate key pair
    const keyPair = nacl.box.keyPair();
    
    // Convert to base64 (WireGuard format)
    const privateKey = nacl.util.encodeBase64(keyPair.secretKey);
    const publicKey = nacl.util.encodeBase64(keyPair.publicKey);
    
    return {
        privateKey: privateKey,
        publicKey: publicKey
    };
}

// Example usage
const keys = generateWireGuardKeys();
console.log('Private Key:', keys.privateKey);
console.log('Public Key:', keys.publicKey);
</script>
```

### **Server-Side Key Generation**

**PHP Alternative (if browser fails):**

```php
<?php
function generateWireGuardKeys() {
    // Generate private key
    $privateKey = exec('wg genkey');
    
    // Derive public key from private key
    $publicKey = exec("echo {$privateKey} | wg pubkey");
    
    return [
        'private_key' => trim($privateKey),
        'public_key' => trim($publicKey)
    ];
}

// Example
$keys = generateWireGuardKeys();
echo "Private Key: {$keys['private_key']}\n";
echo "Public Key: {$keys['public_key']}\n";
```

### **Pre-Shared Keys (Optional)**

**Additional layer of security:**

```javascript
// Generate pre-shared key (PSK)
function generatePSK() {
    const psk = nacl.randomBytes(32);
    return nacl.util.encodeBase64(psk);
}

// Use PSK for extra security
const psk = generatePSK();
```

---

## 📄 CONFIGURATION FILES

### **Client Config Format**

**Standard WireGuard .conf file:**

```ini
[Interface]
PrivateKey = <CLIENT_PRIVATE_KEY>
Address = 10.8.0.15/32
DNS = 1.1.1.1, 1.0.0.1

[Peer]
PublicKey = <SERVER_PUBLIC_KEY>
PresharedKey = <OPTIONAL_PSK>
Endpoint = 66.94.103.91:51820
AllowedIPs = 0.0.0.0/0, ::/0
PersistentKeepalive = 25
```

### **Configuration Breakdown**

**[Interface] Section:**
```ini
[Interface]
# Client's private key (NEVER share this!)
PrivateKey = cGxhY2Vob2xkZXJfcHJpdmF0ZV9rZXk=

# Client's VPN IP address
# Format: 10.8.0.X/32 (X = unique number per device)
Address = 10.8.0.15/32

# DNS servers to use while connected
# 1.1.1.1 = Cloudflare DNS (fast, privacy-focused)
# 8.8.8.8 = Google DNS (alternative)
DNS = 1.1.1.1, 1.0.0.1
```

**[Peer] Section:**
```ini
[Peer]
# Server's public key (identifies server)
PublicKey = c2VydmVyX3B1YmxpY19rZXlfcGxhY2Vob2xkZXI=

# Optional: Pre-shared key for quantum resistance
PresharedKey = cHNrX3BsYWNlaG9sZGVy

# Server's public IP and port
Endpoint = 66.94.103.91:51820

# Route ALL traffic through VPN
# 0.0.0.0/0 = All IPv4 traffic
# ::/0 = All IPv6 traffic
AllowedIPs = 0.0.0.0/0, ::/0

# Keep connection alive (important for mobile)
# Sends keepalive packet every 25 seconds
PersistentKeepalive = 25
```

### **Split Tunnel Config**

**Only route VPN traffic (not all traffic):**

```ini
[Interface]
PrivateKey = <CLIENT_PRIVATE_KEY>
Address = 10.8.0.15/32
DNS = 1.1.1.1

[Peer]
PublicKey = <SERVER_PUBLIC_KEY>
Endpoint = 66.94.103.91:51820
# Only route VPN network traffic
AllowedIPs = 10.8.0.0/24
PersistentKeepalive = 25
```

---

## 🖥️ SERVER CONFIGURATION

### **Base Server Config**

**File: /etc/wireguard/wg0.conf**

```ini
[Interface]
# Server's private key
PrivateKey = <SERVER_PRIVATE_KEY>

# Server's VPN IP (always 10.8.0.1)
Address = 10.8.0.1/24

# Port to listen on
ListenPort = 51820

# Enable IP forwarding and NAT
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE; ip6tables -A FORWARD -i wg0 -j ACCEPT; ip6tables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE; ip6tables -D FORWARD -i wg0 -j ACCEPT; ip6tables -t nat -D POSTROUTING -o eth0 -j MASQUERADE

# Peers added below (one per device)
# Format:
# [Peer]
# PublicKey = <DEVICE_PUBLIC_KEY>
# AllowedIPs = <DEVICE_VPN_IP>/32
```

### **Adding Peers Dynamically**

**Method 1: Direct wg command (recommended):**

```bash
# Add peer
wg set wg0 peer <DEVICE_PUBLIC_KEY> allowed-ips <DEVICE_VPN_IP>/32

# Save to config
wg-quick save wg0
```

**Method 2: Edit config file:**

```bash
# Append to /etc/wireguard/wg0.conf
cat >> /etc/wireguard/wg0.conf << EOF

[Peer]
# Device: iPhone (user: john@email.com)
PublicKey = dXNlcl9wdWJsaWNfa2V5XzE=
AllowedIPs = 10.8.0.15/32
EOF

# Restart WireGuard
systemctl restart wg-quick@wg0
```

### **PHP Server Management**

```php
<?php
function addPeerToServer($serverId, $devicePublicKey, $deviceIP) {
    $server = getServer($serverId);
    
    // Build command
    $command = sprintf(
        "wg set wg0 peer %s allowed-ips %s/32",
        escapeshellarg($devicePublicKey),
        escapeshellarg($deviceIP)
    );
    
    // Execute via SSH
    $result = executeSSH($server['ip_address'], $command);
    
    if ($result['success']) {
        // Save config
        executeSSH($server['ip_address'], "wg-quick save wg0");
        
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => $result['output']];
}

function removePeerFromServer($serverId, $devicePublicKey) {
    $server = getServer($serverId);
    
    $command = sprintf(
        "wg set wg0 peer %s remove",
        escapeshellarg($devicePublicKey)
    );
    
    $result = executeSSH($server['ip_address'], $command);
    
    if ($result['success']) {
        executeSSH($server['ip_address'], "wg-quick save wg0");
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => $result['output']];
}

function listServerPeers($serverId) {
    $server = getServer($serverId);
    
    // Get WireGuard status
    $result = executeSSH($server['ip_address'], "wg show wg0");
    
    if ($result['success']) {
        return parseWireGuardOutput($result['output']);
    }
    
    return [];
}

function parseWireGuardOutput($output) {
    $peers = [];
    $lines = explode("\n", $output);
    
    $currentPeer = null;
    
    foreach ($lines as $line) {
        if (preg_match('/peer: (.+)/', $line, $matches)) {
            $currentPeer = ['public_key' => $matches[1]];
            $peers[] = &$currentPeer;
        } elseif ($currentPeer) {
            if (preg_match('/allowed ips: (.+)/', $line, $matches)) {
                $currentPeer['allowed_ips'] = $matches[1];
            } elseif (preg_match('/latest handshake: (.+)/', $line, $matches)) {
                $currentPeer['last_handshake'] = $matches[1];
            } elseif (preg_match('/transfer: (.+) received, (.+) sent/', $line, $matches)) {
                $currentPeer['rx'] = $matches[1];
                $currentPeer['tx'] = $matches[2];
            }
        }
    }
    
    return $peers;
}
```

---

## 💻 CLIENT CONFIGURATION

### **Generate Config for User**

```php
<?php
function generateClientConfig($deviceId) {
    global $db_devices;
    
    // Get device details
    $device = getDevice($deviceId);
    $user = getUser($device['user_id']);
    $server = getServer($device['current_server_id']);
    
    // Generate config
    $config = "[Interface]\n";
    $config .= "PrivateKey = {$device['private_key']}\n";
    $config .= "Address = {$device['vpn_ip']}/32\n";
    $config .= "DNS = 1.1.1.1, 1.0.0.1\n";
    $config .= "\n";
    $config .= "[Peer]\n";
    $config .= "PublicKey = {$server['public_key']}\n";
    $config .= "Endpoint = {$server['endpoint']}\n";
    $config .= "AllowedIPs = 0.0.0.0/0, ::/0\n";
    $config .= "PersistentKeepalive = 25\n";
    
    return $config;
}

function downloadConfigFile($deviceId) {
    $device = getDevice($deviceId);
    $config = generateClientConfig($deviceId);
    
    // Generate filename
    $filename = "TrueVault-{$device['name']}.conf";
    
    // Set headers for download
    header('Content-Type: application/x-wireguard-profile');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header('Content-Length: ' . strlen($config));
    
    echo $config;
    exit;
}
```

### **API Endpoint**

```php
<?php
// File: /api/download-config.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/wireguard.php';

// Check authentication
$user = authenticateUser();
if (!$user) {
    http_response_code(401);
    die('Unauthorized');
}

// Get device ID
$deviceId = $_GET['device_id'] ?? null;

if (!$deviceId) {
    http_response_code(400);
    die('Missing device_id');
}

// Verify device belongs to user
$device = getDevice($deviceId);
if ($device['user_id'] !== $user['id']) {
    http_response_code(403);
    die('Forbidden');
}

// Download config
downloadConfigFile($deviceId);
```

---

## 🔢 IP ALLOCATION

### **IP Address Pool**

**Structure:**
```
10.8.0.0/24 network

10.8.0.1      → Server (gateway)
10.8.0.2-254  → Clients (253 available IPs)
10.8.0.255    → Broadcast (reserved)
```

**Per-Server Subnets:**
```
New York:   10.8.0.0/24   (10.8.0.1 - 10.8.0.254)
St. Louis:  10.8.1.0/24   (10.8.1.1 - 10.8.1.254)
Dallas:     10.8.2.0/24   (10.8.2.1 - 10.8.2.254)
Toronto:    10.8.3.0/24   (10.8.3.1 - 10.8.3.254)
```

### **Allocate IP to Device**

```php
<?php
function allocateIPAddress($serverId) {
    global $db_devices;
    
    $server = getServer($serverId);
    
    // Get subnet for this server
    // New York = 10.8.0.x, St. Louis = 10.8.1.x, etc
    $subnet = getServerSubnet($serverId);
    
    // Find next available IP
    $stmt = $db_devices->prepare("
        SELECT vpn_ip FROM devices 
        WHERE current_server_id = ?
        ORDER BY vpn_ip DESC
        LIMIT 1
    ");
    $stmt->execute([$serverId]);
    $lastIP = $stmt->fetchColumn();
    
    if ($lastIP) {
        // Increment last octet
        $parts = explode('.', $lastIP);
        $lastOctet = intval($parts[3]);
        
        if ($lastOctet >= 254) {
            throw new Exception("No available IPs on server");
        }
        
        $parts[3] = $lastOctet + 1;
        $newIP = implode('.', $parts);
    } else {
        // First device on this server
        $newIP = "{$subnet}.2";  // .1 is server
    }
    
    return $newIP;
}

function getServerSubnet($serverId) {
    // Map server ID to subnet
    $subnets = [
        1 => '10.8.0',  // New York
        2 => '10.8.1',  // St. Louis
        3 => '10.8.2',  // Dallas
        4 => '10.8.3',  // Toronto
    ];
    
    return $subnets[$serverId] ?? '10.8.0';
}
```

---

## 🌐 DNS CONFIGURATION

### **DNS Options**

**Public DNS Servers:**
```
Cloudflare:   1.1.1.1, 1.0.0.1         (Fast, privacy-focused)
Google:       8.8.8.8, 8.8.4.4         (Reliable, fast)
Quad9:        9.9.9.9, 149.112.112.112 (Security-focused)
OpenDNS:      208.67.222.222           (Content filtering)
```

**Ad-Blocking DNS:**
```
AdGuard:      94.140.14.14, 94.140.15.15
NextDNS:      45.90.28.0, 45.90.30.0
```

### **DNS Configuration**

**In client config:**
```ini
[Interface]
PrivateKey = ...
Address = 10.8.0.15/32
# DNS servers to use
DNS = 1.1.1.1, 1.0.0.1
```

**User-selectable DNS:**

```php
<?php
function setDNS($deviceId, $dnsProvider) {
    $dnsServers = [
        'cloudflare' => '1.1.1.1, 1.0.0.1',
        'google' => '8.8.8.8, 8.8.4.4',
        'quad9' => '9.9.9.9, 149.112.112.112',
        'adguard' => '94.140.14.14, 94.140.15.15'
    ];
    
    $dns = $dnsServers[$dnsProvider] ?? $dnsServers['cloudflare'];
    
    // Update device settings
    updateDeviceSetting($deviceId, 'dns_servers', $dns);
    
    return $dns;
}
```

---

## 📱 PLATFORM-SPECIFIC SETUP

### **iOS / iPhone**

**Method 1: QR Code (Easiest)**
```
1. Download WireGuard from App Store
2. Open WireGuard app
3. Tap "+" → "Create from QR code"
4. Scan QR code from dashboard
5. Tap "Allow" to add VPN profile
6. Done! Toggle to connect
```

**Method 2: Manual Import**
```
1. Download .conf file from dashboard
2. Share file to WireGuard app
3. App automatically imports config
4. Tap "Allow" to add profile
5. Toggle to connect
```

### **Android**

**Method 1: QR Code**
```
1. Install WireGuard from Play Store
2. Open app → "+" → "Scan from QR code"
3. Scan QR code
4. Toggle to connect
```

**Method 2: File Import**
```
1. Download .conf file
2. Open with WireGuard app
3. Import and connect
```

### **Windows**

**Installation:**
```
1. Download WireGuard installer from dashboard
2. Run installer (requires admin)
3. Open WireGuard app
4. Click "Add Tunnel" → "Import from file"
5. Select downloaded .conf file
6. Click "Activate"
```

**Alternative: One-click installer:**
```php
<?php
// Generate Windows installer with embedded config
function generateWindowsInstaller($deviceId) {
    $config = generateClientConfig($deviceId);
    
    // Create temp config file
    $configFile = sys_get_temp_dir() . '/wireguard.conf';
    file_put_contents($configFile, $config);
    
    // Generate installer with embedded config
    // (using WireGuard's MSI package)
    $installerUrl = 'https://download.wireguard.com/windows-client/wireguard-installer.exe';
    
    // Download and customize installer
    // ... (implementation details)
    
    return $installerPath;
}
```

### **macOS**

**Installation:**
```
1. Download WireGuard from App Store
2. Open WireGuard
3. Click "+" → "Add Tunnel from File"
4. Select .conf file
5. Toggle to connect
```

### **Linux**

**Ubuntu/Debian:**
```bash
# Install WireGuard
sudo apt update
sudo apt install wireguard

# Download config
wget https://vpn.the-truth-publishing.com/api/download-config.php?device_id=123 -O /etc/wireguard/wg0.conf

# Start VPN
sudo wg-quick up wg0

# Auto-start on boot
sudo systemctl enable wg-quick@wg0
```

**Command-line connection:**
```bash
# Connect
wg-quick up wg0

# Disconnect
wg-quick down wg0

# Status
wg show
```

---

## 📲 QR CODE GENERATION

### **Generate QR Code**

```php
<?php
require_once 'vendor/autoload.php';
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

function generateConfigQRCode($deviceId) {
    // Get config
    $config = generateClientConfig($deviceId);
    
    // Create QR code
    $qrCode = new QrCode($config);
    $qrCode->setSize(400);
    $qrCode->setMargin(20);
    
    // Write to PNG
    $writer = new PngWriter();
    $result = $writer->write($qrCode);
    
    // Return base64 image
    return 'data:image/png;base64,' . base64_encode($result->getString());
}

// API endpoint
if (isset($_GET['device_id'])) {
    $deviceId = intval($_GET['device_id']);
    
    // Verify ownership
    verifyDeviceOwnership($deviceId, $currentUser['id']);
    
    $qrImage = generateConfigQRCode($deviceId);
    
    echo json_encode([
        'success' => true,
        'qr_code' => $qrImage
    ]);
}
```

### **Display QR Code**

```html
<!-- In dashboard -->
<div class="qr-code-container">
    <h3>📱 Scan with WireGuard App</h3>
    <img id="qr-code" src="" alt="QR Code">
    <p>Open WireGuard app → Scan QR Code</p>
</div>

<script>
// Load QR code
fetch(`/api/generate-qr.php?device_id=${deviceId}`)
    .then(r => r.json())
    .then(data => {
        document.getElementById('qr-code').src = data.qr_code;
    });
</script>
```

---

## 🔌 CONNECTION MANAGEMENT

### **Connection States**

```
DISCONNECTED → CONNECTING → CONNECTED
                    ↓
              [Handshake]
                    ↓
              [Authenticated]
                    ↓
              [Tunnel Active]
```

### **Track Connections**

```php
<?php
function checkDeviceConnection($deviceId) {
    $device = getDevice($deviceId);
    $server = getServer($device['current_server_id']);
    
    // Get WireGuard status from server
    $result = executeSSH($server['ip_address'], "wg show wg0");
    
    if ($result['success']) {
        $peers = parseWireGuardOutput($result['output']);
        
        foreach ($peers as $peer) {
            if ($peer['public_key'] === $device['public_key']) {
                // Device found - check handshake
                $lastHandshake = $peer['last_handshake'] ?? null;
                
                if ($lastHandshake && $lastHandshake !== 'never') {
                    // Connected (handshake within last 3 minutes)
                    $handshakeTime = strtotime($lastHandshake);
                    $isConnected = (time() - $handshakeTime) < 180;
                    
                    return [
                        'connected' => $isConnected,
                        'last_seen' => $lastHandshake,
                        'rx' => $peer['rx'] ?? '0',
                        'tx' => $peer['tx'] ?? '0'
                    ];
                }
            }
        }
    }
    
    return [
        'connected' => false,
        'last_seen' => null,
        'rx' => '0',
        'tx' => '0'
    ];
}

// Real-time connection status API
// File: /api/connection-status.php
$deviceId = $_GET['device_id'] ?? null;
$status = checkDeviceConnection($deviceId);

echo json_encode($status);
```

### **Auto-Reconnect**

**Client-side logic:**
```javascript
// Monitor connection status
setInterval(async () => {
    const status = await fetch(`/api/connection-status.php?device_id=${deviceId}`)
        .then(r => r.json());
    
    if (!status.connected) {
        console.log('Connection lost - attempting reconnect');
        // WireGuard automatically reconnects
        // Just update UI
        updateConnectionUI('disconnected');
    } else {
        updateConnectionUI('connected', status.last_seen);
    }
}, 10000); // Check every 10 seconds
```

---

## 🔒 SECURITY BEST PRACTICES

### **Key Management**

**✅ DO:**
- Generate keys client-side when possible
- Store private keys encrypted
- Never log private keys
- Rotate keys periodically (every 90 days)
- Use unique keys per device

**❌ DON'T:**
- Share private keys between devices
- Send private keys over unencrypted connections
- Store private keys in plain text
- Reuse keys after device deletion

### **Network Security**

```ini
# Always use these settings:
[Interface]
# Strong DNS
DNS = 1.1.1.1, 1.0.0.1

[Peer]
# Route all traffic (prevent leaks)
AllowedIPs = 0.0.0.0/0, ::/0

# Keep connection alive (prevent timeout)
PersistentKeepalive = 25
```

### **Server Hardening**

```bash
# Firewall rules
ufw default deny incoming
ufw default allow outgoing
ufw allow 51820/udp
ufw allow 22/tcp  # SSH only
ufw enable

# Disable unnecessary services
systemctl disable bluetooth
systemctl disable cups

# Enable automatic security updates
apt install unattended-upgrades
dpkg-reconfigure -plow unattended-upgrades
```

---

**END OF SECTION 11: WIREGUARD CONFIGURATION**

**Next Section:** Section 12 (User Dashboard)  
**Status:** Section 11 Complete ✅  
**Lines:** ~1,300 lines  
**Created:** January 15, 2026 - 5:55 AM CST
