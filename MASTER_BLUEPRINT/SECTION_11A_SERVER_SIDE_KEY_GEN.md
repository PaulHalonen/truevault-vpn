# SECTION 11-A: SERVER-SIDE KEY GENERATION

**Added:** January 17, 2026  
**Status:** CRITICAL - Required for Device Setup  
**Location:** Deploy to ALL 4 VPN Servers  

---

## 🔄 CORRECT KEY GENERATION FLOW

**IMPORTANT:** Keys are generated SERVER-SIDE, NOT browser-side.

### User Flow:

```
┌─────────────────────────────────────────────────────────────────┐
│ STEP 1: User clicks "Add Device" in dashboard                   │
├─────────────────────────────────────────────────────────────────┤
│ STEP 2: Popup appears:                                          │
│         - Device Name: [laptop____________]                     │
│         - Select Server: [New York ▼]                           │
│         - [Create Device]                                       │
├─────────────────────────────────────────────────────────────────┤
│ STEP 3: Dashboard sends request to VPN SERVER (not PHP backend) │
│         POST https://66.94.103.91:8443/api/create-peer          │
│         {                                                       │
│           "user_id": 123,                                       │
│           "device_name": "laptop",                              │
│           "auth_token": "jwt_token_here"                        │
│         }                                                       │
├─────────────────────────────────────────────────────────────────┤
│ STEP 4: VPN Server generates keypair:                           │
│         $ wg genkey | tee /tmp/privatekey | wg pubkey           │
│         Private: cK3bLz8N9xR2mQ5vP7wY1uT4sE6hJ0iD...            │
│         Public:  aB2cD4eF6gH8iJ0kL2mN4oP6qR8sT0uV...            │
├─────────────────────────────────────────────────────────────────┤
│ STEP 5: VPN Server allocates IP and adds peer:                  │
│         $ wg set wg0 peer <public_key> allowed-ips 10.8.0.15/32 │
├─────────────────────────────────────────────────────────────────┤
│ STEP 6: VPN Server creates config file and returns:             │
│         {                                                       │
│           "success": true,                                      │
│           "config": "[Interface]\nPrivateKey=...",              │
│           "assigned_ip": "10.8.0.15",                           │
│           "qr_code": "data:image/png;base64,...",               │
│           "public_key": "aB2cD4eF..."                           │
│         }                                                       │
├─────────────────────────────────────────────────────────────────┤
│ STEP 7: Dashboard shows:                                        │
│         ✓ Device "laptop" created!                              │
│         [Download Config] [Show QR Code]                        │
│         (QR code displayed for mobile scanning)                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🖥️ SERVER API REQUIREMENTS

Each VPN server MUST run a key management API on port 8443.

### Endpoints Required:

| Endpoint | Method | Purpose |
|----------|--------|---------|
| /api/create-peer | POST | Generate keys, add peer, return config |
| /api/remove-peer | POST | Remove peer from WireGuard |
| /api/list-peers | GET | List all connected peers |
| /api/health | GET | Server health check |
| /api/server-info | GET | Get server public key and info |

### Authentication:

All API calls must include:
- `Authorization: Bearer <jwt_token>` header
- Token validated against main TrueVault database
- OR use shared API secret between PHP backend and server

---

## 📦 SERVER SCRIPT: /opt/truevault/api.py

```python
#!/usr/bin/env python3
"""
TrueVault VPN - Server-Side Key Generation API
Deploy to: /opt/truevault/api.py
Port: 8443 (HTTPS) or 8080 (HTTP internal)
"""

from flask import Flask, request, jsonify
import subprocess
import os
import json
import sqlite3
from datetime import datetime
import base64
import io

app = Flask(__name__)

# Configuration - set via environment or config file
CONFIG = {
    'server_name': os.environ.get('SERVER_NAME', 'vpn-server'),
    'server_ip': os.environ.get('SERVER_IP', '0.0.0.0'),
    'server_port': int(os.environ.get('WG_PORT', 51820)),
    'api_port': int(os.environ.get('API_PORT', 8443)),
    'subnet_base': os.environ.get('SUBNET_BASE', '10.8.0'),
    'dns_servers': os.environ.get('DNS', '1.1.1.1, 1.0.0.1'),
    'api_secret': os.environ.get('API_SECRET', 'CHANGE_THIS_SECRET'),
    'db_path': '/opt/truevault/peers.db'
}

def init_db():
    """Initialize local peer tracking database"""
    conn = sqlite3.connect(CONFIG['db_path'])
    c = conn.cursor()
    c.execute('''
        CREATE TABLE IF NOT EXISTS peers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_name TEXT NOT NULL,
            public_key TEXT NOT NULL UNIQUE,
            private_key TEXT NOT NULL,
            assigned_ip TEXT NOT NULL UNIQUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_handshake DATETIME,
            is_active BOOLEAN DEFAULT 1
        )
    ''')
    conn.commit()
    conn.close()

def verify_auth(request):
    """Verify API authentication"""
    auth_header = request.headers.get('Authorization', '')
    if auth_header.startswith('Bearer '):
        token = auth_header[7:]
        # Option 1: Verify against shared secret
        if token == CONFIG['api_secret']:
            return True
        # Option 2: Validate JWT against main server (implement as needed)
    return False

def get_next_ip():
    """Get next available IP address"""
    conn = sqlite3.connect(CONFIG['db_path'])
    c = conn.cursor()
    c.execute('SELECT assigned_ip FROM peers WHERE is_active = 1')
    used_ips = [row[0] for row in c.fetchall()]
    conn.close()
    
    base = CONFIG['subnet_base']
    for i in range(2, 255):  # .1 is server, .2-254 for clients
        ip = f"{base}.{i}"
        if ip not in used_ips:
            return ip
    return None

def generate_keypair():
    """Generate WireGuard keypair"""
    # Generate private key
    result = subprocess.run(['wg', 'genkey'], capture_output=True, text=True)
    private_key = result.stdout.strip()
    
    # Derive public key
    result = subprocess.run(['wg', 'pubkey'], input=private_key, capture_output=True, text=True)
    public_key = result.stdout.strip()
    
    return private_key, public_key

def get_server_public_key():
    """Get this server's public key"""
    result = subprocess.run(['wg', 'show', 'wg0', 'public-key'], capture_output=True, text=True)
    return result.stdout.strip()

def add_peer_to_wireguard(public_key, allowed_ip):
    """Add peer to WireGuard interface"""
    cmd = ['wg', 'set', 'wg0', 'peer', public_key, 'allowed-ips', f'{allowed_ip}/32']
    result = subprocess.run(cmd, capture_output=True, text=True)
    return result.returncode == 0

def remove_peer_from_wireguard(public_key):
    """Remove peer from WireGuard interface"""
    cmd = ['wg', 'set', 'wg0', 'peer', public_key, 'remove']
    result = subprocess.run(cmd, capture_output=True, text=True)
    return result.returncode == 0

def generate_config(private_key, assigned_ip, server_public_key):
    """Generate WireGuard client configuration"""
    config = f"""[Interface]
PrivateKey = {private_key}
Address = {assigned_ip}/32
DNS = {CONFIG['dns_servers']}

[Peer]
PublicKey = {server_public_key}
Endpoint = {CONFIG['server_ip']}:{CONFIG['server_port']}
AllowedIPs = 0.0.0.0/0, ::/0
PersistentKeepalive = 25
"""
    return config

def generate_qr_code(config_text):
    """Generate QR code as base64 PNG"""
    try:
        import qrcode
        qr = qrcode.QRCode(version=1, box_size=10, border=4)
        qr.add_data(config_text)
        qr.make(fit=True)
        img = qr.make_image(fill_color="black", back_color="white")
        
        buffer = io.BytesIO()
        img.save(buffer, format='PNG')
        buffer.seek(0)
        
        return 'data:image/png;base64,' + base64.b64encode(buffer.read()).decode()
    except ImportError:
        return None  # qrcode library not installed

# ============== API ENDPOINTS ==============

@app.route('/api/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    wg_status = subprocess.run(['wg', 'show', 'wg0'], capture_output=True)
    return jsonify({
        'status': 'online' if wg_status.returncode == 0 else 'degraded',
        'server': CONFIG['server_name'],
        'timestamp': datetime.utcnow().isoformat()
    })

@app.route('/api/server-info', methods=['GET'])
def server_info():
    """Get server information including public key"""
    return jsonify({
        'name': CONFIG['server_name'],
        'ip': CONFIG['server_ip'],
        'port': CONFIG['server_port'],
        'public_key': get_server_public_key(),
        'dns': CONFIG['dns_servers']
    })

@app.route('/api/create-peer', methods=['POST'])
def create_peer():
    """
    Create new peer - generates keys, adds to WireGuard, returns config
    
    Request:
    {
        "user_id": 123,
        "device_name": "laptop"
    }
    
    Response:
    {
        "success": true,
        "config": "...",
        "assigned_ip": "10.8.0.15",
        "public_key": "...",
        "qr_code": "data:image/png;base64,..."
    }
    """
    if not verify_auth(request):
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    data = request.get_json()
    user_id = data.get('user_id')
    device_name = data.get('device_name', 'device')
    
    if not user_id:
        return jsonify({'success': False, 'error': 'user_id required'}), 400
    
    # Get next available IP
    assigned_ip = get_next_ip()
    if not assigned_ip:
        return jsonify({'success': False, 'error': 'No IPs available'}), 503
    
    # Generate keypair
    private_key, public_key = generate_keypair()
    
    # Add peer to WireGuard
    if not add_peer_to_wireguard(public_key, assigned_ip):
        return jsonify({'success': False, 'error': 'Failed to add peer'}), 500
    
    # Store in local database
    conn = sqlite3.connect(CONFIG['db_path'])
    c = conn.cursor()
    c.execute('''
        INSERT INTO peers (user_id, device_name, public_key, private_key, assigned_ip)
        VALUES (?, ?, ?, ?, ?)
    ''', (user_id, device_name, public_key, private_key, assigned_ip))
    conn.commit()
    conn.close()
    
    # Generate config
    server_public_key = get_server_public_key()
    config = generate_config(private_key, assigned_ip, server_public_key)
    
    # Generate QR code
    qr_code = generate_qr_code(config)
    
    return jsonify({
        'success': True,
        'config': config,
        'assigned_ip': assigned_ip,
        'public_key': public_key,
        'qr_code': qr_code,
        'server_name': CONFIG['server_name']
    })

@app.route('/api/remove-peer', methods=['POST'])
def remove_peer():
    """Remove a peer from WireGuard"""
    if not verify_auth(request):
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    data = request.get_json()
    public_key = data.get('public_key')
    
    if not public_key:
        return jsonify({'success': False, 'error': 'public_key required'}), 400
    
    # Remove from WireGuard
    if not remove_peer_from_wireguard(public_key):
        return jsonify({'success': False, 'error': 'Failed to remove peer'}), 500
    
    # Mark inactive in database
    conn = sqlite3.connect(CONFIG['db_path'])
    c = conn.cursor()
    c.execute('UPDATE peers SET is_active = 0 WHERE public_key = ?', (public_key,))
    conn.commit()
    conn.close()
    
    return jsonify({'success': True})

@app.route('/api/list-peers', methods=['GET'])
def list_peers():
    """List all peers on this server"""
    if not verify_auth(request):
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    # Get from WireGuard
    result = subprocess.run(['wg', 'show', 'wg0', 'dump'], capture_output=True, text=True)
    
    peers = []
    for line in result.stdout.strip().split('\n')[1:]:  # Skip header
        parts = line.split('\t')
        if len(parts) >= 4:
            peers.append({
                'public_key': parts[0],
                'endpoint': parts[2] if parts[2] != '(none)' else None,
                'allowed_ips': parts[3],
                'last_handshake': parts[4] if len(parts) > 4 else None,
                'transfer_rx': parts[5] if len(parts) > 5 else '0',
                'transfer_tx': parts[6] if len(parts) > 6 else '0'
            })
    
    return jsonify({'success': True, 'peers': peers})

if __name__ == '__main__':
    init_db()
    
    # Use HTTPS in production with proper certificates
    app.run(
        host='0.0.0.0',
        port=CONFIG['api_port'],
        debug=False
    )
```

---

## 🔧 SERVER SETUP SCRIPT: /opt/truevault/setup.sh

```bash
#!/bin/bash
# TrueVault VPN Server Setup Script
# Run as root on each VPN server

set -e

echo "=========================================="
echo "TrueVault VPN Server Setup"
echo "=========================================="

# Get server configuration
read -p "Server Name (e.g., new-york): " SERVER_NAME
read -p "Server Public IP: " SERVER_IP
read -p "API Secret (shared with PHP backend): " API_SECRET

# Create directory
mkdir -p /opt/truevault
cd /opt/truevault

# Install dependencies
apt update
apt install -y python3 python3-pip python3-venv wireguard

# Create Python virtual environment
python3 -m venv venv
source venv/bin/activate
pip install flask qrcode pillow

# Enable IP forwarding
echo "net.ipv4.ip_forward=1" >> /etc/sysctl.conf
sysctl -p

# Generate WireGuard server keys
umask 077
wg genkey | tee /etc/wireguard/server_private.key | wg pubkey > /etc/wireguard/server_public.key
SERVER_PRIVATE=$(cat /etc/wireguard/server_private.key)
SERVER_PUBLIC=$(cat /etc/wireguard/server_public.key)

echo ""
echo "Server Public Key: $SERVER_PUBLIC"
echo "SAVE THIS KEY - Add to database!"
echo ""

# Determine subnet based on server
case $SERVER_NAME in
    "new-york")
        SUBNET_BASE="10.8.0"
        ;;
    "st-louis")
        SUBNET_BASE="10.8.1"
        ;;
    "dallas")
        SUBNET_BASE="10.8.2"
        ;;
    "toronto")
        SUBNET_BASE="10.8.3"
        ;;
    *)
        SUBNET_BASE="10.8.0"
        ;;
esac

# Create WireGuard config
cat > /etc/wireguard/wg0.conf << EOF
[Interface]
PrivateKey = $SERVER_PRIVATE
Address = ${SUBNET_BASE}.1/24
ListenPort = 51820
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE

# Peers managed dynamically via API
EOF

chmod 600 /etc/wireguard/wg0.conf

# Create environment file
cat > /opt/truevault/.env << EOF
SERVER_NAME=$SERVER_NAME
SERVER_IP=$SERVER_IP
WG_PORT=51820
API_PORT=8443
SUBNET_BASE=$SUBNET_BASE
DNS=1.1.1.1, 1.0.0.1
API_SECRET=$API_SECRET
EOF

# Create systemd service for API
cat > /etc/systemd/system/truevault-api.service << EOF
[Unit]
Description=TrueVault VPN Key Management API
After=network.target wg-quick@wg0.service

[Service]
Type=simple
WorkingDirectory=/opt/truevault
EnvironmentFile=/opt/truevault/.env
ExecStart=/opt/truevault/venv/bin/python /opt/truevault/api.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

# Configure firewall
ufw allow 51820/udp  # WireGuard
ufw allow 8443/tcp   # API
ufw allow 22/tcp     # SSH
ufw --force enable

# Enable and start services
systemctl enable wg-quick@wg0
systemctl start wg-quick@wg0
systemctl enable truevault-api
systemctl start truevault-api

echo ""
echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "Server Public Key: $SERVER_PUBLIC"
echo "API Endpoint: http://$SERVER_IP:8443"
echo "WireGuard Port: 51820"
echo ""
echo "Add this to your database:"
echo "  Name: $SERVER_NAME"
echo "  IP: $SERVER_IP"
echo "  Public Key: $SERVER_PUBLIC"
echo "  API Secret: $API_SECRET"
echo ""
echo "Test with: curl http://$SERVER_IP:8443/api/health"
echo ""
```

---

## 📱 ANDROID HELPER APP NOTES

For Android, the config is downloaded as `config.txt` (not `.conf` due to Android restrictions).

The Android helper app must:
1. Read `config.txt` from Downloads folder
2. Rename/convert to `.conf`
3. Import into WireGuard app via Intent

OR use WireGuard's built-in QR scanner (preferred method).

---

## 🔗 PHP BACKEND INTEGRATION

The PHP backend acts as a **proxy** to the VPN servers:

```php
<?php
// /api/devices/create.php

// 1. Authenticate user (JWT)
// 2. Validate request
// 3. Select best server (or user-selected)
// 4. Call VPN server API to create peer
// 5. Store device record in local database
// 6. Return config to user

function createDevice($userId, $deviceName, $serverId) {
    $server = getServer($serverId);
    
    // Call VPN server API
    $response = callServerAPI($server['ip_address'], '/api/create-peer', [
        'user_id' => $userId,
        'device_name' => $deviceName
    ], $server['api_secret']);
    
    if ($response['success']) {
        // Store in our database
        $stmt = $db->prepare("
            INSERT INTO devices (user_id, name, server_id, public_key, assigned_ip)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $deviceName,
            $serverId,
            $response['public_key'],
            $response['assigned_ip']
        ]);
        
        return [
            'success' => true,
            'config' => $response['config'],
            'qr_code' => $response['qr_code'],
            'device_id' => $db->lastInsertId()
        ];
    }
    
    return ['success' => false, 'error' => $response['error']];
}
```

---

**END OF SECTION 11-A**
