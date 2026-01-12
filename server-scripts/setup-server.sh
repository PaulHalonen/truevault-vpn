#!/bin/bash
# TrueVault VPN - Server Setup Script
# Run this on each WireGuard server to set up the peer management API

set -e

echo "=========================================="
echo "TrueVault VPN Server Setup"
echo "=========================================="

# Configuration (set these for each server)
SERVER_NAME="${1:-TrueVault}"
SERVER_NETWORK="${2:-10.0.0}"
API_PORT="${3:-8080}"

echo "Server Name: $SERVER_NAME"
echo "Network: $SERVER_NETWORK.0/24"
echo "API Port: $API_PORT"

# Create directory
mkdir -p /opt/truevault

# Install dependencies
echo ""
echo "Installing dependencies..."
apt-get update -qq
apt-get install -y python3 python3-pip -qq
pip3 install flask -q

# Copy peer API script
echo "Installing peer API..."
cat > /opt/truevault/peer_api.py << 'PEER_API_SCRIPT'
#!/usr/bin/env python3
"""TrueVault Peer Management API"""
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
    return jsonify({'status': 'healthy' if success else 'unhealthy', 'server': SERVER_NAME})

@app.route('/peers/add', methods=['POST'])
def add_peer():
    if not verify_auth(request):
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    data = request.get_json()
    if not data or 'public_key' not in data:
        return jsonify({'success': False, 'error': 'public_key required'}), 400
    
    allowed_ip = data.get('allowed_ip') or get_next_ip()
    if not allowed_ip:
        return jsonify({'success': False, 'error': 'No available IPs'}), 500
    
    success, message = add_peer_to_config(data['public_key'], allowed_ip, data.get('user_id'))
    return jsonify({'success': success, 'message': message, 'allowed_ip': allowed_ip if success else None})

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
    return jsonify({'success': True, 'peers': get_peers()})

if __name__ == '__main__':
    print(f"Starting TrueVault Peer API - {SERVER_NAME}")
    app.run(host='0.0.0.0', port=int(os.environ.get('PEER_API_PORT', 8080)))
PEER_API_SCRIPT

chmod +x /opt/truevault/peer_api.py

# Create systemd service
echo "Creating systemd service..."
cat > /etc/systemd/system/truevault-peer-api.service << EOF
[Unit]
Description=TrueVault VPN Peer Management API
After=network.target wg-quick@wg0.service

[Service]
Type=simple
User=root
WorkingDirectory=/opt/truevault
Environment="TRUEVAULT_API_SECRET=TrueVault2026SecretKey"
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
echo "Starting service..."
systemctl daemon-reload
systemctl enable truevault-peer-api
systemctl restart truevault-peer-api

# Check status
sleep 2
if systemctl is-active --quiet truevault-peer-api; then
    echo ""
    echo "=========================================="
    echo "✓ TrueVault Peer API is running!"
    echo "=========================================="
    echo "API URL: http://$(hostname -I | awk '{print $1}'):$API_PORT"
    echo ""
    echo "Test with:"
    echo "  curl http://localhost:$API_PORT/health"
else
    echo ""
    echo "✗ Service failed to start. Check logs:"
    echo "  journalctl -u truevault-peer-api -f"
fi
