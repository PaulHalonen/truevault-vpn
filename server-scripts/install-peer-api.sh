#!/bin/bash
#
# TrueVault VPN - Peer API Installation Script
# Run this on each VPN server to install the peer management API
#
# Usage:
#   curl -sSL https://vpn.the-truth-publishing.com/downloads/install-peer-api.sh | bash -s -- NY 10.0.0
#   
# Arguments:
#   $1 = Server name (NY, STL, TX, CAN)
#   $2 = Network prefix (10.0.0, 10.0.1, 10.10.1, 10.10.0)

set -e

SERVER_NAME="${1:-TrueVault}"
SERVER_NETWORK="${2:-10.0.0}"
API_SECRET="TrueVault2026SecretKey"
API_PORT="8080"

echo "=========================================="
echo "  TrueVault Peer API Installation"
echo "  Server: $SERVER_NAME"
echo "  Network: $SERVER_NETWORK"
echo "=========================================="
echo ""

# Install dependencies
echo "[1/6] Installing dependencies..."
apt-get update -qq
apt-get install -y -qq python3 python3-pip
pip3 install flask -q

# Create directory
echo "[2/6] Creating directory..."
mkdir -p /opt/truevault

# Download peer_api.py
echo "[3/6] Downloading peer API..."
cat > /opt/truevault/peer_api.py << 'PEER_API_EOF'
#!/usr/bin/env python3
"""
TrueVault VPN - Peer Management API
"""

import subprocess
import os
import json
import re
from datetime import datetime
from flask import Flask, request, jsonify

app = Flask(__name__)

WG_INTERFACE = "wg0"
WG_CONFIG_PATH = "/etc/wireguard/wg0.conf"
API_SECRET = os.environ.get('TRUEVAULT_API_SECRET', 'TrueVault2026SecretKey')
API_PORT = int(os.environ.get('PEER_API_PORT', 8080))
SERVER_NAME = os.environ.get('SERVER_NAME', 'TrueVault')
SERVER_NETWORK = os.environ.get('SERVER_NETWORK', '10.8.0')

def verify_auth(request):
    auth_header = request.headers.get('Authorization', '')
    if not auth_header.startswith('Bearer '):
        return False
    return auth_header[7:] == API_SECRET

def run_wg_command(args):
    try:
        result = subprocess.run(['wg'] + args, capture_output=True, text=True, timeout=10)
        return result.returncode == 0, result.stdout, result.stderr
    except Exception as e:
        return False, '', str(e)

def get_next_ip():
    success, output, _ = run_wg_command(['show', WG_INTERFACE, 'allowed-ips'])
    if not success:
        return f"{SERVER_NETWORK}.2"
    
    used_ips = set()
    for line in output.strip().split('\n'):
        if line:
            parts = line.split('\t')
            if len(parts) >= 2:
                ip_part = parts[1].split('/')[0]
                if ip_part.startswith(SERVER_NETWORK):
                    last_octet = int(ip_part.split('.')[-1])
                    used_ips.add(last_octet)
    
    for i in range(2, 255):
        if i not in used_ips:
            return f"{SERVER_NETWORK}.{i}"
    return None

def add_peer_to_config(public_key, allowed_ip, user_id=None):
    peer_block = f"""
[Peer]
# User: {user_id or 'unknown'} - Added: {datetime.now().isoformat()}
PublicKey = {public_key}
AllowedIPs = {allowed_ip}/32
"""
    with open(WG_CONFIG_PATH, 'r') as f:
        config = f.read()
    
    if public_key in config:
        return False, "Peer already exists"
    
    with open(WG_CONFIG_PATH, 'a') as f:
        f.write(peer_block)
    
    run_wg_command(['set', WG_INTERFACE, 'peer', public_key, 'allowed-ips', f'{allowed_ip}/32'])
    return True, "Peer added"

def remove_peer_from_config(public_key):
    with open(WG_CONFIG_PATH, 'r') as f:
        config = f.read()
    
    pattern = r'\[Peer\][^\[]*PublicKey\s*=\s*' + re.escape(public_key) + r'[^\[]*'
    new_config = re.sub(pattern, '', config, flags=re.IGNORECASE)
    
    if new_config == config:
        return False, "Peer not found"
    
    with open(WG_CONFIG_PATH, 'w') as f:
        f.write(new_config.strip() + '\n')
    
    run_wg_command(['set', WG_INTERFACE, 'peer', public_key, 'remove'])
    return True, "Peer removed"

def get_peers():
    success, output, _ = run_wg_command(['show', WG_INTERFACE, 'dump'])
    if not success:
        return []
    
    peers = []
    lines = output.strip().split('\n')
    for line in lines[1:]:
        parts = line.split('\t')
        if len(parts) >= 5:
            peers.append({
                'public_key': parts[0],
                'endpoint': parts[2] if parts[2] != '(none)' else None,
                'allowed_ips': parts[3],
                'latest_handshake': int(parts[4]) if parts[4] != '0' else None
            })
    return peers

@app.route('/health', methods=['GET'])
def health_check():
    success, _, _ = run_wg_command(['show', WG_INTERFACE])
    return jsonify({
        'status': 'healthy' if success else 'unhealthy',
        'server': SERVER_NAME,
        'interface': WG_INTERFACE,
        'timestamp': datetime.now().isoformat()
    })

@app.route('/peers/add', methods=['POST'])
def add_peer():
    if not verify_auth(request):
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    data = request.get_json()
    if not data or 'public_key' not in data:
        return jsonify({'success': False, 'error': 'public_key required'}), 400
    
    public_key = data['public_key']
    user_id = data.get('user_id')
    allowed_ip = data.get('allowed_ip') or get_next_ip()
    
    if not allowed_ip:
        return jsonify({'success': False, 'error': 'No available IPs'}), 500
    
    success, message = add_peer_to_config(public_key, allowed_ip, user_id)
    return jsonify({
        'success': success,
        'message': message,
        'allowed_ip': allowed_ip if success else None
    })

@app.route('/peers/remove', methods=['POST'])
def remove_peer():
    if not verify_auth(request):
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    data = request.get_json()
    if not data or 'public_key' not in data:
        return jsonify({'success': False, 'error': 'public_key required'}), 400
    
    success, message = remove_peer_from_config(data['public_key'])
    return jsonify({'success': success, 'message': message})

@app.route('/peers/list', methods=['GET'])
def list_peers():
    if not verify_auth(request):
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    return jsonify({'success': True, 'count': len(get_peers()), 'peers': get_peers()})

@app.route('/peers/status', methods=['GET'])
def peer_status():
    if not verify_auth(request):
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    public_key = request.args.get('public_key')
    if not public_key:
        return jsonify({'success': False, 'error': 'public_key required'}), 400
    
    for peer in get_peers():
        if peer['public_key'] == public_key:
            return jsonify({'success': True, 'found': True, 'peer': peer})
    
    return jsonify({'success': True, 'found': False, 'peer': None})

if __name__ == '__main__':
    print(f"Starting TrueVault Peer API on port {API_PORT}")
    print(f"Server: {SERVER_NAME}")
    app.run(host='0.0.0.0', port=API_PORT)
PEER_API_EOF

chmod +x /opt/truevault/peer_api.py

# Create systemd service
echo "[4/6] Creating systemd service..."
cat > /etc/systemd/system/truevault-peer-api.service << EOF
[Unit]
Description=TrueVault VPN Peer Management API
After=network.target wg-quick@wg0.service
Wants=wg-quick@wg0.service

[Service]
Type=simple
User=root
WorkingDirectory=/opt/truevault
Environment="TRUEVAULT_API_SECRET=$API_SECRET"
Environment="PEER_API_PORT=$API_PORT"
Environment="SERVER_NAME=$SERVER_NAME"
Environment="SERVER_NETWORK=$SERVER_NETWORK"
ExecStart=/usr/bin/python3 /opt/truevault/peer_api.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

# Enable and start service
echo "[5/6] Enabling service..."
systemctl daemon-reload
systemctl enable truevault-peer-api
systemctl start truevault-peer-api

# Verify
echo "[6/6] Verifying installation..."
sleep 2
if curl -s http://localhost:$API_PORT/health | grep -q "healthy"; then
    echo ""
    echo "=========================================="
    echo "  ✓ Installation Complete!"
    echo "=========================================="
    echo ""
    echo "  API running on port $API_PORT"
    echo "  Server: $SERVER_NAME"
    echo "  Network: $SERVER_NETWORK"
    echo ""
    echo "  Test: curl http://localhost:$API_PORT/health"
    echo ""
else
    echo ""
    echo "WARNING: Service may not be running correctly"
    echo "Check: systemctl status truevault-peer-api"
    echo ""
fi
