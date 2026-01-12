#!/bin/bash
#===============================================================================
# TrueVault VPN - WireGuard Server Auto-Install Script
# Run as root on each VPN server
#===============================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║       TrueVault VPN - WireGuard Server Setup              ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════════════╝${NC}"

# Configuration
WG_PORT=51820
WG_INTERFACE="wg0"
API_PORT=8080
SERVER_NETWORK="10.8.0.0/24"
SERVER_IP="10.8.0.1"

# Detect server info
PUBLIC_IP=$(curl -s ifconfig.me || curl -s icanhazip.com)
echo -e "${YELLOW}Detected Public IP: ${PUBLIC_IP}${NC}"

#===============================================================================
# 1. Install WireGuard
#===============================================================================
echo -e "\n${GREEN}[1/6] Installing WireGuard...${NC}"

if [ -f /etc/debian_version ]; then
    # Debian/Ubuntu
    apt-get update -qq
    apt-get install -y wireguard wireguard-tools qrencode iptables curl jq
elif [ -f /etc/redhat-release ]; then
    # CentOS/RHEL
    yum install -y epel-release
    yum install -y wireguard-tools qrencode iptables curl jq
else
    echo -e "${RED}Unsupported OS${NC}"
    exit 1
fi

echo -e "${GREEN}✓ WireGuard installed${NC}"

#===============================================================================
# 2. Generate Server Keys
#===============================================================================
echo -e "\n${GREEN}[2/6] Generating server keys...${NC}"

mkdir -p /etc/wireguard/keys
chmod 700 /etc/wireguard/keys

# Generate keys if they don't exist
if [ ! -f /etc/wireguard/keys/server_private.key ]; then
    wg genkey | tee /etc/wireguard/keys/server_private.key | wg pubkey > /etc/wireguard/keys/server_public.key
    chmod 600 /etc/wireguard/keys/server_private.key
fi

SERVER_PRIVATE_KEY=$(cat /etc/wireguard/keys/server_private.key)
SERVER_PUBLIC_KEY=$(cat /etc/wireguard/keys/server_public.key)

echo -e "${GREEN}✓ Server keys generated${NC}"
echo -e "${YELLOW}  Public Key: ${SERVER_PUBLIC_KEY}${NC}"

#===============================================================================
# 3. Create WireGuard Configuration
#===============================================================================
echo -e "\n${GREEN}[3/6] Creating WireGuard configuration...${NC}"

# Detect main network interface
MAIN_INTERFACE=$(ip route | grep default | awk '{print $5}' | head -1)
echo -e "${YELLOW}  Network Interface: ${MAIN_INTERFACE}${NC}"

cat > /etc/wireguard/${WG_INTERFACE}.conf << EOF
[Interface]
Address = ${SERVER_IP}/24
ListenPort = ${WG_PORT}
PrivateKey = ${SERVER_PRIVATE_KEY}
SaveConfig = false

# NAT and forwarding
PostUp = iptables -A FORWARD -i %i -j ACCEPT; iptables -A FORWARD -o %i -j ACCEPT; iptables -t nat -A POSTROUTING -o ${MAIN_INTERFACE} -j MASQUERADE
PostDown = iptables -D FORWARD -i %i -j ACCEPT; iptables -D FORWARD -o %i -j ACCEPT; iptables -t nat -D POSTROUTING -o ${MAIN_INTERFACE} -j MASQUERADE

# Peers will be added dynamically via API
EOF

chmod 600 /etc/wireguard/${WG_INTERFACE}.conf
echo -e "${GREEN}✓ WireGuard configuration created${NC}"

#===============================================================================
# 4. Enable IP Forwarding
#===============================================================================
echo -e "\n${GREEN}[4/6] Enabling IP forwarding...${NC}"

# Enable immediately
sysctl -w net.ipv4.ip_forward=1
sysctl -w net.ipv6.conf.all.forwarding=1

# Make persistent
cat > /etc/sysctl.d/99-wireguard.conf << EOF
net.ipv4.ip_forward = 1
net.ipv6.conf.all.forwarding = 1
EOF

echo -e "${GREEN}✓ IP forwarding enabled${NC}"

#===============================================================================
# 5. Create Peer Management API
#===============================================================================
echo -e "\n${GREEN}[5/6] Creating peer management API...${NC}"

mkdir -p /opt/truevault-vpn
cat > /opt/truevault-vpn/api.py << 'APIEOF'
#!/usr/bin/env python3
"""
TrueVault VPN - Peer Management API
Handles adding/removing WireGuard peers dynamically
"""

import subprocess
import json
import os
import secrets
import hashlib
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import parse_qs, urlparse

# Configuration
API_PORT = 8080
API_SECRET = os.environ.get('TRUEVAULT_API_SECRET', 'truevault-secret-key-change-me')
WG_INTERFACE = 'wg0'
SERVER_NETWORK = '10.8.0'
USED_IPS_FILE = '/etc/wireguard/used_ips.json'

def get_used_ips():
    """Load used IP addresses"""
    if os.path.exists(USED_IPS_FILE):
        with open(USED_IPS_FILE, 'r') as f:
            return json.load(f)
    return {"ips": [1]}  # 1 is reserved for server

def save_used_ips(data):
    """Save used IP addresses"""
    with open(USED_IPS_FILE, 'w') as f:
        json.dump(data, f)

def get_next_ip():
    """Get next available IP address"""
    data = get_used_ips()
    for i in range(2, 255):
        if i not in data['ips']:
            data['ips'].append(i)
            save_used_ips(data)
            return f"{SERVER_NETWORK}.{i}"
    return None

def release_ip(ip):
    """Release an IP address"""
    data = get_used_ips()
    last_octet = int(ip.split('.')[-1])
    if last_octet in data['ips']:
        data['ips'].remove(last_octet)
        save_used_ips(data)

def add_peer(public_key, allowed_ip):
    """Add a peer to WireGuard"""
    cmd = f'wg set {WG_INTERFACE} peer {public_key} allowed-ips {allowed_ip}/32'
    result = subprocess.run(cmd, shell=True, capture_output=True, text=True)
    return result.returncode == 0

def remove_peer(public_key):
    """Remove a peer from WireGuard"""
    cmd = f'wg set {WG_INTERFACE} peer {public_key} remove'
    result = subprocess.run(cmd, shell=True, capture_output=True, text=True)
    return result.returncode == 0

def get_server_info():
    """Get server public key and endpoint"""
    with open('/etc/wireguard/keys/server_public.key', 'r') as f:
        public_key = f.read().strip()
    
    # Get public IP
    result = subprocess.run('curl -s ifconfig.me', shell=True, capture_output=True, text=True)
    public_ip = result.stdout.strip()
    
    return {
        'public_key': public_key,
        'endpoint': f'{public_ip}:51820',
        'server_ip': f'{SERVER_NETWORK}.1'
    }

def list_peers():
    """List all connected peers"""
    result = subprocess.run(f'wg show {WG_INTERFACE} dump', shell=True, capture_output=True, text=True)
    peers = []
    for line in result.stdout.strip().split('\n')[1:]:  # Skip header
        if line:
            parts = line.split('\t')
            if len(parts) >= 4:
                peers.append({
                    'public_key': parts[0],
                    'endpoint': parts[2] if parts[2] != '(none)' else None,
                    'allowed_ips': parts[3],
                    'last_handshake': parts[4] if len(parts) > 4 else '0',
                    'transfer_rx': parts[5] if len(parts) > 5 else '0',
                    'transfer_tx': parts[6] if len(parts) > 6 else '0'
                })
    return peers

class APIHandler(BaseHTTPRequestHandler):
    def log_message(self, format, *args):
        pass  # Suppress logging
    
    def send_json(self, data, code=200):
        self.send_response(code)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.end_headers()
        self.wfile.write(json.dumps(data).encode())
    
    def verify_auth(self):
        """Verify API authentication"""
        auth_header = self.headers.get('Authorization', '')
        if auth_header.startswith('Bearer '):
            token = auth_header[7:]
            return token == API_SECRET
        return False
    
    def do_OPTIONS(self):
        self.send_response(200)
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        self.end_headers()
    
    def do_GET(self):
        parsed = urlparse(self.path)
        
        if parsed.path == '/health':
            self.send_json({'status': 'ok', 'service': 'truevault-vpn'})
            return
        
        if parsed.path == '/info':
            if not self.verify_auth():
                self.send_json({'error': 'Unauthorized'}, 401)
                return
            self.send_json(get_server_info())
            return
        
        if parsed.path == '/peers':
            if not self.verify_auth():
                self.send_json({'error': 'Unauthorized'}, 401)
                return
            self.send_json({'peers': list_peers()})
            return
        
        self.send_json({'error': 'Not found'}, 404)
    
    def do_POST(self):
        parsed = urlparse(self.path)
        
        if parsed.path == '/peer':
            if not self.verify_auth():
                self.send_json({'error': 'Unauthorized'}, 401)
                return
            
            content_length = int(self.headers.get('Content-Length', 0))
            body = json.loads(self.rfile.read(content_length)) if content_length else {}
            
            public_key = body.get('public_key')
            if not public_key:
                self.send_json({'error': 'public_key required'}, 400)
                return
            
            # Get next available IP
            client_ip = get_next_ip()
            if not client_ip:
                self.send_json({'error': 'No available IPs'}, 503)
                return
            
            # Add peer
            if add_peer(public_key, client_ip):
                server_info = get_server_info()
                self.send_json({
                    'success': True,
                    'client_ip': client_ip,
                    'server_public_key': server_info['public_key'],
                    'endpoint': server_info['endpoint'],
                    'dns': '1.1.1.1, 8.8.8.8'
                })
            else:
                release_ip(client_ip)
                self.send_json({'error': 'Failed to add peer'}, 500)
            return
        
        self.send_json({'error': 'Not found'}, 404)
    
    def do_DELETE(self):
        parsed = urlparse(self.path)
        
        if parsed.path.startswith('/peer/'):
            if not self.verify_auth():
                self.send_json({'error': 'Unauthorized'}, 401)
                return
            
            public_key = parsed.path[6:]  # Remove '/peer/'
            if remove_peer(public_key):
                self.send_json({'success': True})
            else:
                self.send_json({'error': 'Failed to remove peer'}, 500)
            return
        
        self.send_json({'error': 'Not found'}, 404)

if __name__ == '__main__':
    server = HTTPServer(('0.0.0.0', API_PORT), APIHandler)
    print(f'TrueVault VPN API running on port {API_PORT}')
    server.serve_forever()
APIEOF

chmod +x /opt/truevault-vpn/api.py
echo -e "${GREEN}✓ Peer management API created${NC}"

#===============================================================================
# 6. Create Systemd Services
#===============================================================================
echo -e "\n${GREEN}[6/6] Creating systemd services...${NC}"

# WireGuard service (use built-in)
systemctl enable wg-quick@${WG_INTERFACE}

# API service
cat > /etc/systemd/system/truevault-api.service << EOF
[Unit]
Description=TrueVault VPN Peer Management API
After=network.target wg-quick@${WG_INTERFACE}.service
Requires=wg-quick@${WG_INTERFACE}.service

[Service]
Type=simple
Environment="TRUEVAULT_API_SECRET=${API_SECRET:-truevault-secret-key-change-me}"
ExecStart=/usr/bin/python3 /opt/truevault-vpn/api.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable truevault-api

echo -e "${GREEN}✓ Systemd services created${NC}"

#===============================================================================
# 7. Configure Firewall
#===============================================================================
echo -e "\n${GREEN}[7/7] Configuring firewall...${NC}"

# Allow WireGuard and API ports
if command -v ufw &> /dev/null; then
    ufw allow ${WG_PORT}/udp
    ufw allow ${API_PORT}/tcp
    ufw --force enable
elif command -v firewall-cmd &> /dev/null; then
    firewall-cmd --permanent --add-port=${WG_PORT}/udp
    firewall-cmd --permanent --add-port=${API_PORT}/tcp
    firewall-cmd --reload
fi

echo -e "${GREEN}✓ Firewall configured${NC}"

#===============================================================================
# Start Services
#===============================================================================
echo -e "\n${GREEN}Starting services...${NC}"

systemctl start wg-quick@${WG_INTERFACE}
systemctl start truevault-api

sleep 2

#===============================================================================
# Verification
#===============================================================================
echo -e "\n${GREEN}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                    SETUP COMPLETE!                         ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}Server Information:${NC}"
echo -e "  Public IP:      ${PUBLIC_IP}"
echo -e "  WireGuard Port: ${WG_PORT}"
echo -e "  API Port:       ${API_PORT}"
echo -e "  Public Key:     ${SERVER_PUBLIC_KEY}"
echo -e "  VPN Network:    ${SERVER_NETWORK}/24"
echo ""
echo -e "${YELLOW}Service Status:${NC}"
systemctl is-active wg-quick@${WG_INTERFACE} && echo -e "  WireGuard: ${GREEN}Running${NC}" || echo -e "  WireGuard: ${RED}Not Running${NC}"
systemctl is-active truevault-api && echo -e "  API:       ${GREEN}Running${NC}" || echo -e "  API:       ${RED}Not Running${NC}"
echo ""
echo -e "${YELLOW}Test Commands:${NC}"
echo -e "  curl http://${PUBLIC_IP}:${API_PORT}/health"
echo -e "  wg show"
echo ""
echo -e "${GREEN}Save this public key for the database:${NC}"
echo -e "${SERVER_PUBLIC_KEY}"
