#!/usr/bin/env python3
"""
TrueVault VPN - Server-Side Key Generation API
Deploy to: /opt/truevault/api.py

Endpoints:
  GET  /api/health       - Health check
  GET  /api/server-info  - Get server public key
  POST /api/create-peer  - Generate keys, add peer, return config
  POST /api/remove-peer  - Remove peer from WireGuard
  GET  /api/list-peers   - List all connected peers
"""

from flask import Flask, request, jsonify
import subprocess
import os
import sqlite3
from datetime import datetime
import base64
import io

app = Flask(__name__)

# Configuration from environment variables
CONFIG = {
    'server_name': os.environ.get('SERVER_NAME', 'vpn-server'),
    'server_ip': os.environ.get('SERVER_IP', '0.0.0.0'),
    'server_port': int(os.environ.get('WG_PORT', '51820')),
    'api_port': int(os.environ.get('API_PORT', '8443')),
    'subnet_base': os.environ.get('SUBNET_BASE', '10.8.0'),
    'dns_servers': os.environ.get('DNS', '1.1.1.1, 1.0.0.1'),
    'api_secret': os.environ.get('API_SECRET', 'CHANGE_THIS_SECRET'),
    'db_path': os.environ.get('DB_PATH', '/opt/truevault/peers.db')
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
    print(f"Database initialized at {CONFIG['db_path']}")

def verify_auth(req):
    """Verify API authentication via Bearer token"""
    auth_header = req.headers.get('Authorization', '')
    if auth_header.startswith('Bearer '):
        token = auth_header[7:]
        if token == CONFIG['api_secret']:
            return True
    return False

def get_next_ip():
    """Get next available IP address in subnet"""
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
    """Generate WireGuard keypair using wg commands"""
    try:
        # Generate private key
        result = subprocess.run(['wg', 'genkey'], capture_output=True, text=True, check=True)
        private_key = result.stdout.strip()
        
        # Derive public key from private key
        result = subprocess.run(['wg', 'pubkey'], input=private_key, capture_output=True, text=True, check=True)
        public_key = result.stdout.strip()
        
        return private_key, public_key
    except subprocess.CalledProcessError as e:
        print(f"Error generating keypair: {e}")
        return None, None

def get_server_public_key():
    """Get this server's WireGuard public key"""
    try:
        result = subprocess.run(['wg', 'show', 'wg0', 'public-key'], capture_output=True, text=True, check=True)
        return result.stdout.strip()
    except subprocess.CalledProcessError:
        # Try reading from file
        try:
            with open('/etc/wireguard/server_public.key', 'r') as f:
                return f.read().strip()
        except:
            return None

def add_peer_to_wireguard(public_key, allowed_ip):
    """Add peer to WireGuard interface"""
    try:
        cmd = ['wg', 'set', 'wg0', 'peer', public_key, 'allowed-ips', f'{allowed_ip}/32']
        result = subprocess.run(cmd, capture_output=True, text=True)
        if result.returncode != 0:
            print(f"Error adding peer: {result.stderr}")
        return result.returncode == 0
    except Exception as e:
        print(f"Exception adding peer: {e}")
        return False

def remove_peer_from_wireguard(public_key):
    """Remove peer from WireGuard interface"""
    try:
        cmd = ['wg', 'set', 'wg0', 'peer', public_key, 'remove']
        result = subprocess.run(cmd, capture_output=True, text=True)
        return result.returncode == 0
    except Exception as e:
        print(f"Exception removing peer: {e}")
        return False

def generate_config(private_key, assigned_ip, server_public_key):
    """Generate WireGuard client configuration file"""
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
    """Generate QR code as base64 PNG image"""
    try:
        import qrcode
        from PIL import Image
        
        qr = qrcode.QRCode(
            version=1,
            error_correction=qrcode.constants.ERROR_CORRECT_L,
            box_size=10,
            border=4,
        )
        qr.add_data(config_text)
        qr.make(fit=True)
        
        img = qr.make_image(fill_color="black", back_color="white")
        
        buffer = io.BytesIO()
        img.save(buffer, format='PNG')
        buffer.seek(0)
        
        return 'data:image/png;base64,' + base64.b64encode(buffer.read()).decode()
    except ImportError:
        print("qrcode or pillow not installed - QR generation disabled")
        return None
    except Exception as e:
        print(f"Error generating QR code: {e}")
        return None


# ============== API ENDPOINTS ==============

@app.route('/api/health', methods=['GET'])
def health_check():
    """Health check endpoint - no auth required"""
    try:
        wg_status = subprocess.run(['wg', 'show', 'wg0'], capture_output=True)
        is_online = wg_status.returncode == 0
    except:
        is_online = False
    
    return jsonify({
        'status': 'online' if is_online else 'degraded',
        'server': CONFIG['server_name'],
        'ip': CONFIG['server_ip'],
        'port': CONFIG['server_port'],
        'timestamp': datetime.utcnow().isoformat() + 'Z'
    })

@app.route('/api/server-info', methods=['GET'])
def server_info():
    """Get server information including public key - no auth required"""
    public_key = get_server_public_key()
    
    return jsonify({
        'name': CONFIG['server_name'],
        'ip': CONFIG['server_ip'],
        'port': CONFIG['server_port'],
        'public_key': public_key,
        'dns': CONFIG['dns_servers'],
        'subnet': CONFIG['subnet_base'] + '.0/24'
    })

@app.route('/api/create-peer', methods=['POST'])
def create_peer():
    """
    Create new peer - generates keys, adds to WireGuard, returns config
    
    Request Body:
    {
        "user_id": 123,
        "device_name": "laptop"
    }
    
    Response:
    {
        "success": true,
        "config": "[Interface]\n...",
        "assigned_ip": "10.8.0.15",
        "public_key": "abc123...",
        "qr_code": "data:image/png;base64,..."
    }
    """
    # Verify authentication
    if not verify_auth(request):
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    # Parse request
    try:
        data = request.get_json()
    except:
        return jsonify({'success': False, 'error': 'Invalid JSON'}), 400
    
    user_id = data.get('user_id')
    device_name = data.get('device_name', 'device')
    
    if not user_id:
        return jsonify({'success': False, 'error': 'user_id required'}), 400
    
    # Get next available IP
    assigned_ip = get_next_ip()
    if not assigned_ip:
        return jsonify({'success': False, 'error': 'No IPs available on this server'}), 503
    
    # Generate keypair
    private_key, public_key = generate_keypair()
    if not private_key or not public_key:
        return jsonify({'success': False, 'error': 'Failed to generate keypair'}), 500
    
    # Add peer to WireGuard
    if not add_peer_to_wireguard(public_key, assigned_ip):
        return jsonify({'success': False, 'error': 'Failed to add peer to WireGuard'}), 500
    
    # Store in local database
    try:
        conn = sqlite3.connect(CONFIG['db_path'])
        c = conn.cursor()
        c.execute('''
            INSERT INTO peers (user_id, device_name, public_key, private_key, assigned_ip)
            VALUES (?, ?, ?, ?, ?)
        ''', (user_id, device_name, public_key, private_key, assigned_ip))
        conn.commit()
        peer_id = c.lastrowid
        conn.close()
    except sqlite3.IntegrityError as e:
        return jsonify({'success': False, 'error': f'Database error: {e}'}), 500
    
    # Generate config file
    server_public_key = get_server_public_key()
    if not server_public_key:
        return jsonify({'success': False, 'error': 'Server public key not found'}), 500
    
    config = generate_config(private_key, assigned_ip, server_public_key)
    
    # Generate QR code
    qr_code = generate_qr_code(config)
    
    return jsonify({
        'success': True,
        'peer_id': peer_id,
        'config': config,
        'assigned_ip': assigned_ip,
        'public_key': public_key,
        'qr_code': qr_code,
        'server_name': CONFIG['server_name'],
        'server_ip': CONFIG['server_ip']
    })

@app.route('/api/remove-peer', methods=['POST'])
def remove_peer():
    """
    Remove a peer from WireGuard
    
    Request Body:
    {
        "public_key": "abc123..."
    }
    """
    if not verify_auth(request):
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    try:
        data = request.get_json()
    except:
        return jsonify({'success': False, 'error': 'Invalid JSON'}), 400
    
    public_key = data.get('public_key')
    
    if not public_key:
        return jsonify({'success': False, 'error': 'public_key required'}), 400
    
    # Remove from WireGuard
    if not remove_peer_from_wireguard(public_key):
        return jsonify({'success': False, 'error': 'Failed to remove peer from WireGuard'}), 500
    
    # Mark inactive in database
    try:
        conn = sqlite3.connect(CONFIG['db_path'])
        c = conn.cursor()
        c.execute('UPDATE peers SET is_active = 0 WHERE public_key = ?', (public_key,))
        affected = c.rowcount
        conn.commit()
        conn.close()
    except Exception as e:
        print(f"Database error: {e}")
    
    return jsonify({
        'success': True,
        'message': 'Peer removed',
        'rows_affected': affected
    })

@app.route('/api/list-peers', methods=['GET'])
def list_peers():
    """List all peers currently on this server"""
    if not verify_auth(request):
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    # Get from WireGuard
    try:
        result = subprocess.run(['wg', 'show', 'wg0', 'dump'], capture_output=True, text=True)
        
        peers = []
        lines = result.stdout.strip().split('\n')
        
        # First line is server info, rest are peers
        for line in lines[1:]:
            parts = line.split('\t')
            if len(parts) >= 4:
                peers.append({
                    'public_key': parts[0],
                    'preshared_key': parts[1] if parts[1] != '(none)' else None,
                    'endpoint': parts[2] if parts[2] != '(none)' else None,
                    'allowed_ips': parts[3],
                    'last_handshake': int(parts[4]) if len(parts) > 4 and parts[4] != '0' else None,
                    'transfer_rx': int(parts[5]) if len(parts) > 5 else 0,
                    'transfer_tx': int(parts[6]) if len(parts) > 6 else 0
                })
        
        return jsonify({
            'success': True,
            'peer_count': len(peers),
            'peers': peers
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/get-config', methods=['POST'])
def get_config():
    """
    Regenerate config for existing peer (if they lost it)
    
    Request Body:
    {
        "public_key": "abc123..."
    }
    """
    if not verify_auth(request):
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    try:
        data = request.get_json()
    except:
        return jsonify({'success': False, 'error': 'Invalid JSON'}), 400
    
    public_key = data.get('public_key')
    
    if not public_key:
        return jsonify({'success': False, 'error': 'public_key required'}), 400
    
    # Get peer from database
    conn = sqlite3.connect(CONFIG['db_path'])
    c = conn.cursor()
    c.execute('SELECT private_key, assigned_ip FROM peers WHERE public_key = ? AND is_active = 1', (public_key,))
    row = c.fetchone()
    conn.close()
    
    if not row:
        return jsonify({'success': False, 'error': 'Peer not found'}), 404
    
    private_key, assigned_ip = row
    server_public_key = get_server_public_key()
    
    config = generate_config(private_key, assigned_ip, server_public_key)
    qr_code = generate_qr_code(config)
    
    return jsonify({
        'success': True,
        'config': config,
        'qr_code': qr_code
    })


# ============== MAIN ==============

if __name__ == '__main__':
    print("=" * 50)
    print("TrueVault VPN - Key Generation API")
    print("=" * 50)
    print(f"Server Name: {CONFIG['server_name']}")
    print(f"Server IP: {CONFIG['server_ip']}")
    print(f"WireGuard Port: {CONFIG['server_port']}")
    print(f"API Port: {CONFIG['api_port']}")
    print(f"Subnet: {CONFIG['subnet_base']}.0/24")
    print("=" * 50)
    
    # Initialize database
    init_db()
    
    # Get and display server public key
    pub_key = get_server_public_key()
    if pub_key:
        print(f"Server Public Key: {pub_key}")
    else:
        print("WARNING: Could not get server public key!")
    
    print("=" * 50)
    print(f"Starting API on port {CONFIG['api_port']}...")
    print("=" * 50)
    
    # Run Flask app
    app.run(
        host='0.0.0.0',
        port=CONFIG['api_port'],
        debug=False,
        threaded=True
    )
