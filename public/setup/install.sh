#!/bin/bash
#===============================================================================
# TrueVault VPN - WireGuard Server Auto-Install Script
# Run as root on each VPN server:
#   curl -sSL https://vpn.the-truth-publishing.com/setup/install.sh | bash
#===============================================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║       TrueVault VPN - WireGuard Server Setup              ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════════════╝${NC}"

WG_PORT=51820
WG_INTERFACE="wg0"
API_PORT=8080
SERVER_NETWORK="10.8.0.0/24"
SERVER_IP="10.8.0.1"
API_SECRET="TrueVault2026SecretKey"

PUBLIC_IP=$(curl -s ifconfig.me || curl -s icanhazip.com)
echo -e "${YELLOW}Detected Public IP: ${PUBLIC_IP}${NC}"

# Install WireGuard
echo -e "\n${GREEN}[1/7] Installing WireGuard...${NC}"
if [ -f /etc/debian_version ]; then
    apt-get update -qq
    apt-get install -y wireguard wireguard-tools qrencode iptables curl jq python3
elif [ -f /etc/redhat-release ]; then
    yum install -y epel-release
    yum install -y wireguard-tools qrencode iptables curl jq python3
fi
echo -e "${GREEN}✓ WireGuard installed${NC}"

# Generate Server Keys
echo -e "\n${GREEN}[2/7] Generating server keys...${NC}"
mkdir -p /etc/wireguard/keys
chmod 700 /etc/wireguard/keys
if [ ! -f /etc/wireguard/keys/server_private.key ]; then
    wg genkey | tee /etc/wireguard/keys/server_private.key | wg pubkey > /etc/wireguard/keys/server_public.key
    chmod 600 /etc/wireguard/keys/server_private.key
fi
SERVER_PRIVATE_KEY=$(cat /etc/wireguard/keys/server_private.key)
SERVER_PUBLIC_KEY=$(cat /etc/wireguard/keys/server_public.key)
echo -e "${GREEN}✓ Keys generated${NC}"
echo -e "${YELLOW}  Public Key: ${SERVER_PUBLIC_KEY}${NC}"

# Create WireGuard Config
echo -e "\n${GREEN}[3/7] Creating WireGuard configuration...${NC}"
MAIN_INTERFACE=$(ip route | grep default | awk '{print $5}' | head -1)
cat > /etc/wireguard/${WG_INTERFACE}.conf << EOF
[Interface]
Address = ${SERVER_IP}/24
ListenPort = ${WG_PORT}
PrivateKey = ${SERVER_PRIVATE_KEY}
SaveConfig = false
PostUp = iptables -A FORWARD -i %i -j ACCEPT; iptables -A FORWARD -o %i -j ACCEPT; iptables -t nat -A POSTROUTING -o ${MAIN_INTERFACE} -j MASQUERADE
PostDown = iptables -D FORWARD -i %i -j ACCEPT; iptables -D FORWARD -o %i -j ACCEPT; iptables -t nat -D POSTROUTING -o ${MAIN_INTERFACE} -j MASQUERADE
EOF
chmod 600 /etc/wireguard/${WG_INTERFACE}.conf
echo -e "${GREEN}✓ Configuration created${NC}"

# Enable IP Forwarding
echo -e "\n${GREEN}[4/7] Enabling IP forwarding...${NC}"
sysctl -w net.ipv4.ip_forward=1
echo "net.ipv4.ip_forward = 1" > /etc/sysctl.d/99-wireguard.conf
echo -e "${GREEN}✓ IP forwarding enabled${NC}"

# Create Peer Management API
echo -e "\n${GREEN}[5/7] Creating peer management API...${NC}"
mkdir -p /opt/truevault-vpn
cat > /opt/truevault-vpn/api.py << 'APIEOF'
#!/usr/bin/env python3
import subprocess, json, os
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse

API_PORT = 8080
API_SECRET = os.environ.get('TRUEVAULT_API_SECRET', 'TrueVault2026SecretKey')
WG_INTERFACE = 'wg0'
SERVER_NETWORK = '10.8.0'
USED_IPS_FILE = '/etc/wireguard/used_ips.json'

def get_used_ips():
    if os.path.exists(USED_IPS_FILE):
        with open(USED_IPS_FILE, 'r') as f: return json.load(f)
    return {"ips": [1]}

def save_used_ips(data):
    with open(USED_IPS_FILE, 'w') as f: json.dump(data, f)

def get_next_ip():
    data = get_used_ips()
    for i in range(2, 255):
        if i not in data['ips']:
            data['ips'].append(i)
            save_used_ips(data)
            return f"{SERVER_NETWORK}.{i}"
    return None

def release_ip(ip):
    data = get_used_ips()
    last_octet = int(ip.split('.')[-1])
    if last_octet in data['ips']: data['ips'].remove(last_octet)
    save_used_ips(data)

def add_peer(public_key, allowed_ip):
    cmd = f'wg set {WG_INTERFACE} peer {public_key} allowed-ips {allowed_ip}/32'
    return subprocess.run(cmd, shell=True, capture_output=True).returncode == 0

def remove_peer(public_key):
    cmd = f'wg set {WG_INTERFACE} peer {public_key} remove'
    return subprocess.run(cmd, shell=True, capture_output=True).returncode == 0

def get_server_info():
    with open('/etc/wireguard/keys/server_public.key', 'r') as f: public_key = f.read().strip()
    public_ip = subprocess.run('curl -s ifconfig.me', shell=True, capture_output=True, text=True).stdout.strip()
    return {'public_key': public_key, 'endpoint': f'{public_ip}:51820', 'server_ip': f'{SERVER_NETWORK}.1'}

def list_peers():
    result = subprocess.run(f'wg show {WG_INTERFACE} dump', shell=True, capture_output=True, text=True)
    peers = []
    for line in result.stdout.strip().split('\n')[1:]:
        if line:
            parts = line.split('\t')
            if len(parts) >= 4:
                peers.append({'public_key': parts[0], 'endpoint': parts[2] if parts[2] != '(none)' else None, 'allowed_ips': parts[3]})
    return peers

class APIHandler(BaseHTTPRequestHandler):
    def log_message(self, format, *args): pass
    def send_json(self, data, code=200):
        self.send_response(code)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.end_headers()
        self.wfile.write(json.dumps(data).encode())
    def verify_auth(self):
        auth = self.headers.get('Authorization', '')
        return auth.startswith('Bearer ') and auth[7:] == API_SECRET
    def do_OPTIONS(self):
        self.send_response(200)
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        self.end_headers()
    def do_GET(self):
        path = urlparse(self.path).path
        if path == '/health': return self.send_json({'status': 'ok', 'service': 'truevault-vpn'})
        if path == '/info':
            if not self.verify_auth(): return self.send_json({'error': 'Unauthorized'}, 401)
            return self.send_json(get_server_info())
        if path == '/peers':
            if not self.verify_auth(): return self.send_json({'error': 'Unauthorized'}, 401)
            return self.send_json({'peers': list_peers()})
        self.send_json({'error': 'Not found'}, 404)
    def do_POST(self):
        path = urlparse(self.path).path
        if path == '/peer':
            if not self.verify_auth(): return self.send_json({'error': 'Unauthorized'}, 401)
            length = int(self.headers.get('Content-Length', 0))
            body = json.loads(self.rfile.read(length)) if length else {}
            public_key = body.get('public_key')
            if not public_key: return self.send_json({'error': 'public_key required'}, 400)
            client_ip = get_next_ip()
            if not client_ip: return self.send_json({'error': 'No available IPs'}, 503)
            if add_peer(public_key, client_ip):
                info = get_server_info()
                return self.send_json({'success': True, 'client_ip': client_ip, 'server_public_key': info['public_key'], 'endpoint': info['endpoint'], 'dns': '1.1.1.1, 8.8.8.8'})
            release_ip(client_ip)
            return self.send_json({'error': 'Failed to add peer'}, 500)
        self.send_json({'error': 'Not found'}, 404)
    def do_DELETE(self):
        path = urlparse(self.path).path
        if path.startswith('/peer/'):
            if not self.verify_auth(): return self.send_json({'error': 'Unauthorized'}, 401)
            if remove_peer(path[6:]): return self.send_json({'success': True})
            return self.send_json({'error': 'Failed to remove peer'}, 500)
        self.send_json({'error': 'Not found'}, 404)

if __name__ == '__main__':
    print(f'TrueVault VPN API running on port {API_PORT}')
    HTTPServer(('0.0.0.0', API_PORT), APIHandler).serve_forever()
APIEOF
chmod +x /opt/truevault-vpn/api.py
echo -e "${GREEN}✓ API created${NC}"

# Create Systemd Services
echo -e "\n${GREEN}[6/7] Creating services...${NC}"
systemctl enable wg-quick@${WG_INTERFACE}

cat > /etc/systemd/system/truevault-api.service << EOF
[Unit]
Description=TrueVault VPN API
After=network.target wg-quick@${WG_INTERFACE}.service
[Service]
Type=simple
Environment="TRUEVAULT_API_SECRET=${API_SECRET}"
ExecStart=/usr/bin/python3 /opt/truevault-vpn/api.py
Restart=always
RestartSec=5
[Install]
WantedBy=multi-user.target
EOF
systemctl daemon-reload
systemctl enable truevault-api
echo -e "${GREEN}✓ Services created${NC}"

# Configure Firewall
echo -e "\n${GREEN}[7/7] Configuring firewall...${NC}"
if command -v ufw &> /dev/null; then
    ufw allow ${WG_PORT}/udp
    ufw allow ${API_PORT}/tcp
    ufw --force enable 2>/dev/null || true
fi
echo -e "${GREEN}✓ Firewall configured${NC}"

# Start Services
echo -e "\n${GREEN}Starting services...${NC}"
systemctl start wg-quick@${WG_INTERFACE}
systemctl start truevault-api
sleep 2

# Results
echo -e "\n${GREEN}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                    SETUP COMPLETE!                         ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "Public IP:      ${PUBLIC_IP}"
echo -e "WireGuard Port: ${WG_PORT}"
echo -e "API Port:       ${API_PORT}"
echo -e "Public Key:     ${SERVER_PUBLIC_KEY}"
echo ""
echo -e "${YELLOW}UPDATE DATABASE WITH THIS PUBLIC KEY:${NC}"
echo -e "${GREEN}${SERVER_PUBLIC_KEY}${NC}"
echo ""
echo -e "Test: curl http://${PUBLIC_IP}:${API_PORT}/health"
