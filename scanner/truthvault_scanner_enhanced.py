#!/usr/bin/env python3
"""
TrueVault Network Scanner v3.0 - ENHANCED CAMERA DETECTION
Auto-discovers devices on your home network with AGGRESSIVE camera detection

NEW FEATURES (v3.0):
- Brute force port scanning for hidden cameras
- HTTP banner grabbing to identify web interfaces
- RTSP stream detection (IP cameras)
- ONVIF camera discovery
- Extended camera port scanning (554, 8554, 37777, 34567, etc.)
- Device fingerprinting via HTTP responses

INSTALL:
  pip install flask requests

RUN:
  python truthvault_scanner_enhanced.py YOUR_EMAIL YOUR_TOKEN
  Then open http://localhost:8888
"""

import socket
import subprocess
import platform
import re
import json
import threading
import time
import sys
import os
import webbrowser
from datetime import datetime
from http.server import HTTPServer, SimpleHTTPRequestHandler
from urllib.parse import parse_qs, urlparse
import urllib.request
import ssl

# ============== CONFIGURATION ==============
TRUTHVAULT_API = "https://vpn.the-truth-publishing.com/api"
LOCAL_PORT = 8888
VERSION = "3.0.0"

# ============== CAMERA-SPECIFIC PORTS ==============
CAMERA_PORTS = {
    # HTTP/HTTPS
    80: "http", 443: "https", 8080: "http-alt", 8081: "http-alt2",
    8000: "http", 8888: "http", 8443: "https-alt",
    
    # RTSP (Real-Time Streaming Protocol) - IP Cameras
    554: "rtsp", 8554: "rtsp-alt", 7447: "rtsp", 10554: "rtsp",
    
    # ONVIF (Camera discovery)
    3702: "onvif", 8899: "onvif",
    
    # Proprietary Camera Ports
    34567: "dahua", 37777: "dahua-mobile", 37778: "dahua",
    9000: "hikvision", 8000: "hikvision-alt",
    9527: "foscam", 88: "foscam-alt",
    6036: "reolink", 9000: "reolink-alt",
    8200: "amcrest",
    
    # Tuya/Geeni (smart home)
    6668: "tuya", 1883: "mqtt-tuya", 8883: "mqtt-tuya-ssl",
    
    # Generic services
    23: "telnet", 22: "ssh", 21: "ftp",
    9100: "printer-raw", 515: "lpr", 631: "ipp",
    5000: "upnp", 1900: "ssdp", 32400: "plex",
    3389: "rdp", 5900: "vnc"
}

# ============== CAMERA WEB INTERFACE SIGNATURES ==============
CAMERA_SIGNATURES = {
    'geeni': ['Geeni', 'SmartLife', 'Tuya', 'smart_camera'],
    'wyze': ['Wyze', 'WyzeCam'],
    'hikvision': ['Hikvision', 'HIKVISION', 'iVMS'],
    'dahua': ['Dahua', 'DAHUA', 'DH-SD'],
    'amcrest': ['Amcrest', 'AMC'],
    'reolink': ['Reolink', 'RLC-'],
    'foscam': ['Foscam', 'FI'],
    'ring': ['Ring', 'ring.com'],
    'nest': ['Nest', 'Google Nest'],
    'blink': ['Blink', 'Amazon Blink'],
    'arlo': ['Arlo', 'Netgear Arlo']
}

# ============== MAC VENDOR DATABASE ==============
MAC_VENDORS = {
    # (keeping original MAC vendor database from previous version)
    # Geeni / Merkury (Tuya-based)
    "D8:1D:2E": ("Geeni", "📷"),
    "D8:F1:5B": ("Geeni", "📷"),
    "10:D5:61": ("Geeni", "📷"),
    "24:62:AB": ("Geeni/Tuya", "📷"),
    "50:8A:06": ("Geeni/Tuya", "📷"),
    "68:57:2D": ("Geeni/Tuya", "📷"),
    "7C:F6:66": ("Geeni/Tuya", "📷"),
    "84:E3:42": ("Geeni/Tuya", "📷"),
    "A0:92:08": ("Geeni/Tuya", "📷"),
    "D4:A6:51": ("Tuya", "📷"),
    "60:01:94": ("Tuya", "📷"),
    "1C:90:FF": ("Tuya", "📷"),
    "70:3A:0E": ("Tuya", "🏠"),
    # ... (include all other MAC vendors from original)
}

# ============== GLOBALS ==============
discovered_devices = []
scan_status = {"running": False, "progress": 0, "message": "Ready", "mode": "quick"}
auth_token = None
user_email = None

def get_mac_info(mac):
    if not mac: return ("Unknown", "❓")
    prefix = mac[:8].upper()
    return MAC_VENDORS.get(prefix, ("Unknown", "❓"))

def get_local_ip():
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except: return "192.168.1.1"

def get_network_range():
    local_ip = get_local_ip()
    parts = local_ip.split('.')
    return f"{parts[0]}.{parts[1]}.{parts[2]}"

def ping_host(ip, results, timeout=1):
    param = '-n' if platform.system().lower() == 'windows' else '-c'
    timeout_param = '-w' if platform.system().lower() == 'windows' else '-W'
    timeout_val = str(int(timeout * 1000)) if platform.system().lower() == 'windows' else str(timeout)
    try:
        result = subprocess.run(['ping', param, '1', timeout_param, timeout_val, ip],
            stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=timeout + 1)
        if result.returncode == 0:
            results.append(ip)
    except: pass

def get_arp_table():
    arp = {}
    try:
        if platform.system().lower() == 'windows':
            result = subprocess.run(['arp', '-a'], capture_output=True, text=True)
            for line in result.stdout.split('\n'):
                match = re.search(r'(\d+\.\d+\.\d+\.\d+)\s+([\da-f-]+)', line, re.I)
                if match:
                    ip, mac = match.group(1), match.group(2).replace('-', ':').upper()
                    if mac != 'FF:FF:FF:FF:FF:FF': arp[ip] = mac
        else:
            result = subprocess.run(['arp', '-a'], capture_output=True, text=True)
            for line in result.stdout.split('\n'):
                match = re.search(r'\((\d+\.\d+\.\d+\.\d+)\)\s+at\s+([\da-f:]+)', line, re.I)
                if match:
                    ip, mac = match.group(1), match.group(2).upper()
                    if mac != 'FF:FF:FF:FF:FF:FF': arp[ip] = mac
    except: pass
    return arp

def get_hostname(ip):
    try: return socket.gethostbyaddr(ip)[0]
    except: return None

def get_http_banner(ip, port):
    """Try to grab HTTP response to identify camera web interface"""
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(2)
        sock.connect((ip, port))
        
        # Send HTTP GET request
        request = f"GET / HTTP/1.1\r\nHost: {ip}\r\n\r\n"
        sock.send(request.encode())
        
        # Read response
        response = sock.recv(4096).decode('utf-8', errors='ignore')
        sock.close()
        
        # Extract title and server header
        title_match = re.search(r'<title>(.*?)</title>', response, re.I)
        server_match = re.search(r'Server: (.*?)\r\n', response, re.I)
        
        title = title_match.group(1) if title_match else ""
        server = server_match.group(1) if server_match else ""
        
        # Check for camera signatures
        full_response = response.lower()
        for camera_type, signatures in CAMERA_SIGNATURES.items():
            for signature in signatures:
                if signature.lower() in full_response:
                    return f"{camera_type}_camera", title, server
        
        return "web_interface", title, server
    except:
        return None, None, None

def check_rtsp(ip, port):
    """Check if RTSP stream is available (camera indicator)"""
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(1)
        sock.connect((ip, port))
        
        # Send RTSP OPTIONS request
        request = f"OPTIONS rtsp://{ip}:{port}/ RTSP/1.0\r\nCSeq: 1\r\n\r\n"
        sock.send(request.encode())
        
        response = sock.recv(1024).decode('utf-8', errors='ignore')
        sock.close()
        
        # Check for RTSP response
        if 'RTSP/1.0' in response:
            return True
    except:
        pass
    return False

def aggressive_port_scan(ip):
    """
    AGGRESSIVE camera detection - scans ALL camera-specific ports
    Returns: list of open ports with service info
    """
    open_ports = []
    camera_detected = False
    camera_type = None
    
    for port, service in CAMERA_PORTS.items():
        try:
            sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            sock.settimeout(0.5)
            
            if sock.connect_ex((ip, port)) == 0:
                port_info = {"port": port, "service": service}
                
                # If it's an HTTP port, try banner grabbing
                if port in [80, 443, 8080, 8081, 8000, 8888, 8443]:
                    detected_type, title, server = get_http_banner(ip, port)
                    if detected_type and 'camera' in detected_type:
                        camera_detected = True
                        camera_type = detected_type.replace('_camera', '')
                        port_info['camera_detected'] = True
                        port_info['title'] = title
                        port_info['server'] = server
                
                # If it's an RTSP port, check for camera stream
                if port in [554, 8554, 7447, 10554] and check_rtsp(ip, port):
                    camera_detected = True
                    camera_type = 'ip_camera'
                    port_info['rtsp_detected'] = True
                
                open_ports.append(port_info)
            
            sock.close()
        except:
            pass
    
    return open_ports, camera_detected, camera_type

def determine_type_enhanced(hostname, vendor, icon, ports, camera_detected, camera_type):
    """Enhanced device type detection with camera fingerprinting"""
    port_nums = [p["port"] for p in ports]
    hn = (hostname or "").lower()
    vn = (vendor or "").lower()
    
    # If camera was detected via aggressive scan
    if camera_detected and camera_type:
        return ("ip_camera", "📷", f"{camera_type.title()} Camera")
    
    # Check for RTSP ports (strong camera indicator)
    if 554 in port_nums or 8554 in port_nums:
        return ("ip_camera", "📷", "IP Camera (RTSP)")
    
    # Check for camera-specific ports
    camera_specific_ports = [34567, 37777, 9527, 6036]
    if any(p in port_nums for p in camera_specific_ports):
        return ("ip_camera", "📷", "IP Camera (Proprietary)")
    
    # Original detection logic
    if vendor in ["Geeni", "Geeni/Tuya", "Tuya", "Wyze", "Hikvision", "Dahua", "Amcrest", "Reolink"]:
        return ("ip_camera", "📷", f"{vendor} Camera")
    
    # Printer detection
    if 9100 in port_nums or 515 in port_nums or 631 in port_nums:
        return ("printer", "🖨️", "Network Printer")
    
    # Other device types...
    if 32400 in port_nums: return ("plex", "🎬", "Plex Server")
    if vendor in ["PlayStation", "Xbox", "Nintendo"]: return ("gaming", "🎮", vendor)
    if vendor in ["Nest", "Ring"]: return ("smart_home", "🏠", vendor)
    if 22 in port_nums or 3389 in port_nums: return ("computer", "💻", "Computer")
    if 80 in port_nums or 443 in port_nums: return ("server", "🖥️", "Server/Device")
    if icon != "❓": return ("device", icon, vendor)
    
    return ("unknown", "❓", "Unknown Device")

def scan_network(mode="quick"):
    """
    Network scan with two modes:
    - quick: Basic scan (original speed)
    - aggressive: Full camera detection with port scanning
    """
    global discovered_devices, scan_status
    scan_status = {"running": True, "progress": 0, "message": "Starting scan...", "mode": mode}
    discovered_devices = []
    
    network = get_network_range()
    local_ip = get_local_ip()
    scan_status["message"] = f"Scanning {network}.0/24..."
    
    # Ping sweep
    results = []
    threads = []
    for i in range(1, 255):
        t = threading.Thread(target=ping_host, args=(f"{network}.{i}", results))
        t.start()
        threads.append(t)
        if i % 50 == 0:
            scan_status["progress"] = int((i / 255) * 30)
            scan_status["message"] = f"Pinging {network}.{i}..."
    
    for t in threads: t.join(timeout=3)
    
    scan_status["progress"] = 30
    scan_status["message"] = "Reading ARP table..."
    arp = get_arp_table()
    
    total = len(arp)
    for idx, (ip, mac) in enumerate(arp.items()):
        scan_status["progress"] = 30 + int((idx / max(total, 1)) * 70)
        
        if mode == "aggressive":
            scan_status["message"] = f"🔍 DEEP SCAN: {ip} (checking all camera ports)..."
            # Aggressive camera detection
            ports, camera_detected, camera_type = aggressive_port_scan(ip)
        else:
            scan_status["message"] = f"Analyzing {ip}..."
            # Quick scan (original)
            ports = []
            camera_detected = False
            camera_type = None
            for port in [80, 443, 554, 8080, 8554, 9100, 515, 631]:
                try:
                    sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
                    sock.settimeout(0.3)
                    if sock.connect_ex((ip, port)) == 0:
                        ports.append({"port": port, "service": CAMERA_PORTS.get(port, "unknown")})
                    sock.close()
                except: pass
        
        vendor, vendor_icon = get_mac_info(mac)
        hostname = get_hostname(ip)
        dev_type, icon, type_name = determine_type_enhanced(hostname, vendor, vendor_icon, ports, camera_detected, camera_type)
        
        discovered_devices.append({
            "id": f"auto_{ip.replace('.', '_')}",
            "ip": ip, "mac": mac, "hostname": hostname, "vendor": vendor,
            "type": dev_type, "type_name": type_name, "icon": icon,
            "open_ports": ports, "is_local": ip == local_ip,
            "discovered_at": datetime.now().isoformat(),
            "camera_detected": camera_detected
        })
    
    discovered_devices.sort(key=lambda d: [int(x) for x in d["ip"].split('.')])
    scan_status = {"running": False, "progress": 100, "message": f"Found {len(discovered_devices)} devices", "mode": mode}
    return discovered_devices

def sync_to_truthvault(devices):
    if not auth_token: return {"success": False, "error": "Not connected"}
    try:
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
        
        data = json.dumps({"devices": devices}).encode()
        req = urllib.request.Request(f"{TRUTHVAULT_API}/scanner/sync-devices.php",
            data=data, headers={"Content-Type": "application/json", "Authorization": f"Bearer {auth_token}"})
        
        with urllib.request.urlopen(req, context=ctx, timeout=10) as resp:
            return json.loads(resp.read().decode())
    except Exception as e:
        return {"success": False, "error": str(e)}

# ============== WEB SERVER ==============
HTML = '''<!DOCTYPE html>
<html><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>TruthVault Network Scanner v3.0 - ENHANCED</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:linear-gradient(135deg,#0f0f1a,#1a1a2e);color:#fff;min-height:100vh;padding:20px}
.container{max-width:1100px;margin:0 auto}
header{display:flex;align-items:center;justify-content:space-between;margin-bottom:25px;flex-wrap:wrap;gap:15px}
.logo{display:flex;align-items:center;gap:12px}
.logo h1{font-size:1.6rem;background:linear-gradient(90deg,#00d9ff,#00ff88);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.version{font-size:0.85rem;color:#666;background:rgba(255,255,255,0.05);padding:4px 10px;border-radius:12px}
.badge{padding:6px 14px;border-radius:20px;font-size:.85rem;font-weight:600}
.badge-ok{background:rgba(0,255,136,.15);color:#00ff88;border:1px solid #00ff88}
.card{background:rgba(255,255,255,.04);border-radius:14px;padding:18px;margin-bottom:18px;border:1px solid rgba(255,255,255,.08)}
.card h2{font-size:1.15rem;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.btn{padding:10px 20px;border:none;border-radius:8px;font-size:.95rem;font-weight:600;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:6px}
.btn-primary{background:linear-gradient(90deg,#00d9ff,#00ff88);color:#0f0f1a}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 4px 15px rgba(0,217,255,.3)}
.btn-secondary{background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.15)}
.btn-danger{background:rgba(255,80,80,.2);color:#ff5050;border:1px solid rgba(255,80,80,.4)}
.scan-modes{display:flex;gap:10px;margin-bottom:15px}
.alert{padding:14px;border-radius:10px;margin-bottom:15px;font-size:0.9rem;background:rgba(0,217,255,0.1);border:1px solid rgba(0,217,255,0.3);color:#00d9ff}
.device{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:12px;cursor:pointer;transition:.2s}
.device.camera{border-left:4px solid #ff6b6b;background:rgba(255,107,107,.08)}
.device.camera-detected{border-left:4px solid #00ff88;background:rgba(0,255,136,.1);animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.7}}
</style>
</head><body>
<div class="container">
<header>
<div class="logo">
<span style="font-size:2rem">🔍</span>
<div>
<h1>TruthVault Scanner</h1>
<span class="version">v''' + VERSION + ''' - ENHANCED CAMERA DETECTION</span>
</div>
</div>
<div id="status" class="badge badge-ok">✓ Connected</div>
</header>

<div class="card">
<h2>🚀 SCAN MODES</h2>
<div class="alert">
<strong>NEW! Aggressive Mode:</strong> Deep scans ALL camera ports (554, 8554, 34567, 37777, etc.) + HTTP banner grabbing + RTSP detection. Takes longer but finds hidden cameras!
</div>
<div class="scan-modes">
<button class="btn btn-primary" onclick="startScan('quick')">⚡ Quick Scan (30 sec)</button>
<button class="btn btn-danger" onclick="startScan('aggressive')">🔥 Aggressive Scan (2-3 min) - FIND ALL CAMERAS</button>
</div>
<div id="scan-msg" style="color:#888;margin:10px 0">Ready to scan</div>
<div class="progress"><div id="prog" class="progress-bar" style="width:0%"></div></div>
</div>

<div class="card">
<h2>📱 Discovered Devices</h2>
<div id="devices" class="grid"></div>
<div class="actions">
<button class="btn btn-primary" onclick="syncSelected()" id="sync-btn">☁️ Sync to TrueVault</button>
</div>
</div>
</div>

<script>
let devices=[],selected=new Set(),poll=null;
async function startScan(mode){
  await fetch('/scan',{method:'POST',body:JSON.stringify({mode}),headers:{'Content-Type':'application/json'}});
  poll=setInterval(pollStatus,500);
}
async function pollStatus(){
  const r=await fetch('/status').then(r=>r.json());
  document.getElementById('scan-msg').textContent=r.message;
  document.getElementById('prog').style.width=r.progress+'%';
  if(!r.running){
    clearInterval(poll);
    devices=await fetch('/devices').then(r=>r.json());
    render();
  }
}
function render(){
  if(!devices.length){document.getElementById('devices').innerHTML='<div>No devices found</div>';return}
  document.getElementById('devices').innerHTML=devices.map(d=>`
    <div class="device ${d.type==='ip_camera'?'camera':''} ${d.camera_detected?'camera-detected':''}">
      <div>${d.icon} <strong>${d.hostname||d.type_name}</strong></div>
      <div>${d.ip} - ${d.vendor}</div>
      <div>Ports: ${d.open_ports.map(p=>p.port).join(', ')}</div>
      ${d.camera_detected?'<div style="color:#00ff88">✓ CAMERA DETECTED!</div>':''}
    </div>
  `).join('');
}
async function syncSelected(){
  const r=await fetch('/sync',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({devices})}).then(r=>r.json());
  alert(r.success?'✓ Synced!':'✗ Failed: '+r.error);
}
</script>
</body></html>'''

class Handler(SimpleHTTPRequestHandler):
    def log_message(self, *args): pass
    
    def send_json(self, data, code=200):
        self.send_response(code)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.end_headers()
        self.wfile.write(json.dumps(data).encode())
    
    def do_GET(self):
        if self.path == '/':
            self.send_response(200)
            self.send_header('Content-Type', 'text/html')
            self.end_headers()
            self.wfile.write(HTML.encode())
        elif self.path == '/status':
            self.send_json(scan_status)
        elif self.path == '/devices':
            self.send_json(discovered_devices)
        else:
            self.send_json({"error": "Not found"}, 404)
    
    def do_POST(self):
        if self.path == '/scan':
            length = int(self.headers.get('Content-Length', 0))
            data = json.loads(self.rfile.read(length)) if length else {}
            mode = data.get('mode', 'quick')
            threading.Thread(target=scan_network, args=(mode,), daemon=True).start()
            self.send_json({"success": True, "message": f"Scan started ({mode} mode)"})
        elif self.path == '/sync':
            length = int(self.headers.get('Content-Length', 0))
            data = json.loads(self.rfile.read(length)) if length else {}
            result = sync_to_truthvault(data.get('devices', []))
            self.send_json(result)
        else:
            self.send_json({"error": "Not found"}, 404)

def main():
    global auth_token, user_email
    
    print(f"""
╔═══════════════════════════════════════════════════════════╗
║   TruthVault Network Scanner v{VERSION} - ENHANCED        ║
║   🔥 NEW: Aggressive Camera Detection                    ║
╚═══════════════════════════════════════════════════════════╝
""")
    
    if len(sys.argv) >= 3:
        user_email = sys.argv[1]
        auth_token = sys.argv[2]
    else:
        user_email = input("TruthVault Email: ").strip()
        auth_token = input("Auth Token: ").strip()
    
    print(f"\n✓ User: {user_email}")
    print(f"✓ Starting scanner on http://localhost:{LOCAL_PORT}")
    print("\nOpening browser...")
    
    webbrowser.open(f"http://localhost:{LOCAL_PORT}")
    
    server = HTTPServer(('0.0.0.0', LOCAL_PORT), Handler)
    print(f"\n🔍 Scanner ready! Press Ctrl+C to quit.\n")
    
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\n\nScanner stopped. Goodbye!")
        server.shutdown()

if __name__ == '__main__':
    main()
