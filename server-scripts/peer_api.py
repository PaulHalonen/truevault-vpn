#!/usr/bin/env python3
"""
TrueVault VPN - Peer Management API
Runs on each WireGuard server to manage peer connections

This API allows the main TrueVault server to:
- Add new peers (when user pays/registers)
- Remove peers (when user cancels/defaults)
- List current peers
- Check peer status

INSTALL:
  pip3 install flask

RUN:
  python3 peer_api.py
  # Or with systemd for production

API ENDPOINTS:
  POST /peers/add     - Add a new peer
  POST /peers/remove  - Remove a peer
  GET  /peers/list    - List all peers
  GET  /peers/status  - Check peer status
  GET  /health        - Health check
"""

import subprocess
import os
import json
import re
import hashlib
import hmac
from datetime import datetime
from flask import Flask, request, jsonify

app = Flask(__name__)

# Configuration
WG_INTERFACE = "wg0"
WG_CONFIG_PATH = "/etc/wireguard/wg0.conf"
API_SECRET = os.environ.get('TRUEVAULT_API_SECRET', 'TrueVault2026SecretKey')
API_PORT = int(os.environ.get('PEER_API_PORT', 8080))

# Server-specific settings (set via environment)
SERVER_NAME = os.environ.get('SERVER_NAME', 'TrueVault')
SERVER_NETWORK = os.environ.get('SERVER_NETWORK', '10.8.0')  # e.g., 10.8.0 for 10.8.0.x

def verify_auth(request):
    """Verify API authentication"""
    auth_header = request.headers.get('Authorization', '')
    if not auth_header.startswith('Bearer '):
        return False
    
    token = auth_header[7:]
    return token == API_SECRET

def run_wg_command(args):
    """Run a WireGuard command"""
    try:
        result = subprocess.run(
            ['wg'] + args,
            capture_output=True,
            text=True,
            timeout=10
        )
        return result.returncode == 0, result.stdout, result.stderr
    except Exception as e:
        return False, '', str(e)

def get_next_ip():
    """Get the next available IP address"""
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
    """Add a peer to the WireGuard config file"""
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
    """Remove a peer from the WireGuard config file"""
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
    """Get list of all peers"""
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
                'latest_handshake': int(parts[4]) if parts[4] != '0' else None,
                'transfer_rx': int(parts[5]) if len(parts) > 5 else 0,
                'transfer_tx': int(parts[6]) if len(parts) > 6 else 0
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
    
    allowed_ip = data.get('allowed_ip')
    if not allowed_ip:
        allowed_ip = get_next_ip()
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
    
    public_key = data['public_key']
    success, message = remove_peer_from_config(public_key)
    
    return jsonify({
        'success': success,
        'message': message
    })

@app.route('/peers/list', methods=['GET'])
def list_peers():
    if not verify_auth(request):
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    peers = get_peers()
    
    return jsonify({
        'success': True,
        'count': len(peers),
        'peers': peers
    })

@app.route('/peers/status', methods=['GET'])
def peer_status():
    if not verify_auth(request):
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    public_key = request.args.get('public_key')
    if not public_key:
        return jsonify({'success': False, 'error': 'public_key required'}), 400
    
    peers = get_peers()
    for peer in peers:
        if peer['public_key'] == public_key:
            return jsonify({
                'success': True,
                'found': True,
                'peer': peer
            })
    
    return jsonify({
        'success': True,
        'found': False,
        'peer': None
    })

if __name__ == '__main__':
    print(f"Starting TrueVault Peer API on port {API_PORT}")
    print(f"Server: {SERVER_NAME}")
    print(f"Interface: {WG_INTERFACE}")
    app.run(host='0.0.0.0', port=API_PORT)
