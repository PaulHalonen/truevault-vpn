#!/usr/bin/env python3
"""
TrueVault Network Scanner v2.0
Auto-discovers devices on your home network and syncs to TrueVault VPN

FEATURES:
- Auto-connects to your TrueVault account
- Scans for IP cameras (Geeni, Wyze, Hikvision, etc.)
- Detects smart home devices, gaming consoles, printers
- One-click sync to port forwarding

INSTALL:
  pip install flask requests

RUN:
  python truevault_scanner.py YOUR_EMAIL YOUR_TOKEN
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
TRUEVAULT_API = "https://vpn.the-truth-publishing.com/api"
LOCAL_PORT = 8888
VERSION = "2.0.0"

# ============== MAC VENDOR DATABASE ==============
MAC_VENDORS = {
    # Geeni / Merkury (Tuya-based)
    "D8:1D:2E": ("Geeni", "📷"), "D8:F1:5B": ("Geeni", "📷"), "10:D5:61": ("Geeni", "📷"),
    "24:62:AB": ("Geeni/Tuya", "📷"), "50:8A:06": ("Geeni/Tuya", "📷"), "68:57:2D": ("Geeni/Tuya", "📷"),
    "7C:F6:66": ("Geeni/Tuya", "📷"), "84:E3:42": ("Geeni/Tuya", "📷"), "A0:92:08": ("Geeni/Tuya", "📷"),
    "D4:A6:51": ("Tuya", "📷"), "60:01:94": ("Tuya", "📷"), "1C:90:FF": ("Tuya", "📷"),
    # Wyze
    "2C:AA:8E": ("Wyze", "📷"), "D0:3F:27": ("Wyze", "📷"), "7C:78:B2": ("Wyze", "📷"), "A4:DA:22": ("Wyze", "📷"),
    # Hikvision
    "D8:EB:46": ("Hikvision", "📷"), "C0:56:E3": ("Hikvision", "📷"), "44:19:B6": ("Hikvision", "📷"),
    "A4:14:37": ("Hikvision", "📷"), "54:C4:15": ("Hikvision", "📷"), "28:57:BE": ("Hikvision", "📷"),
    # Dahua
    "00:09:B0": ("Dahua", "📷"), "3C:EF:8C": ("Dahua", "📷"), "4C:11:BF": ("Dahua", "📷"),
    # Amcrest / Reolink
    "78:A5:DD": ("Amcrest", "📷"), "9C:8E:CD": ("Amcrest", "📷"),
    "00:62:6E": ("Reolink", "📷"), "EC:71:DB": ("Reolink", "📷"), "B4:6D:C2": ("Reolink", "📷"),
    # Ring / Nest
    "00:D0:2D": ("Ring", "🚪"), "50:32:75": ("Ring", "🚪"), "34:3E:A4": ("Ring", "🚪"),
    "18:B4:30": ("Nest", "🏠"), "64:16:66": ("Nest", "🏠"),
    # Google / Amazon
    "3C:5A:B4": ("Google", "📱"), "54:60:09": ("Google", "📱"), "F4:F5:D8": ("Google", "📱"),
    "FC:A1:83": ("Amazon Echo", "🔊"), "74:C2:46": ("Amazon Echo", "🔊"), "84:D6:D0": ("Amazon Echo", "🔊"),
    "00:FC:8B": ("Amazon Fire", "📺"), "44:65:0D": ("Amazon Fire", "📺"),
    # Roku
    "00:1D:D0": ("Roku", "📺"), "B8:3E:59": ("Roku", "📺"), "DC:3A:5E": ("Roku", "📺"),
    # Apple / Samsung
    "00:1A:79": ("Apple", "📱"), "00:1B:63": ("Apple", "📱"), "A4:83:E7": ("Apple", "📱"),
    "00:15:99": ("Samsung", "📺"), "00:1A:8A": ("Samsung", "📺"), "94:63:D1": ("Samsung", "📱"),
    # Gaming
    "00:04:20": ("PlayStation", "🎮"), "00:D9:D1": ("PlayStation", "🎮"), "28:3F:69": ("PlayStation", "🎮"),
    "7C:BB:8A": ("Nintendo", "🎮"), "E8:4E:CE": ("Nintendo", "🎮"), "98:41:5C": ("Nintendo", "🎮"),
    "00:50:F2": ("Xbox", "🎮"), "7C:1E:52": ("Xbox", "🎮"), "60:45:BD": ("Xbox", "🎮"),
    # Printers
    "00:1E:0B": ("HP Printer", "🖨️"), "3C:A9:F4": ("HP Printer", "🖨️"), "00:17:A4": ("HP Printer", "🖨️"),
    "00:90:4C": ("Epson Printer", "🖨️"), "00:26:AB": ("Epson Printer", "🖨️"),
    "00:1E:8F": ("Canon Printer", "🖨️"), "00:BB:C1": ("Canon Printer", "🖨️"),
    "00:1B:A9": ("Brother Printer", "🖨️"), "00:80:77": ("Brother Printer", "🖨️"),
    # Routers
    "00:17:7C": ("TP-Link", "📶"), "14:CC:20": ("TP-Link", "📶"), "50:C7:BF": ("TP-Link", "📶"),
    "00:14:BF": ("Linksys", "📶"), "00:18:F8": ("Linksys", "📶"),
    "00:1F:33": ("Netgear", "📶"), "00:22:3F": ("Netgear", "📶"), "20:4E:7F": ("Netgear", "📶"),
    # Raspberry Pi
    "B8:27:EB": ("Raspberry Pi", "🖥️"), "DC:A6:32": ("Raspberry Pi", "🖥️"), "E4:5F:01": ("Raspberry Pi", "🖥️"),
}

# ============== GLOBALS ==============
discovered_devices = []
scan_status = {"running": False, "progress": 0, "message": "Ready"}
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

def check_ports(ip):
    ports = {80: "http", 443: "https", 554: "rtsp", 8080: "http-alt", 8554: "rtsp-alt",
             8000: "http", 8888: "http", 9100: "printer-raw", 515: "lpr", 631: "ipp",
             5000: "upnp", 32400: "plex", 8096: "jellyfin", 3389: "rdp", 5900: "vnc", 22: "ssh"}
    open_ports = []
    for port, svc in ports.items():
        try:
            sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            sock.settimeout(0.3)
            if sock.connect_ex((ip, port)) == 0:
                open_ports.append({"port": port, "service": svc})
            sock.close()
        except: pass
    return open_ports

def determine_type(hostname, vendor, icon, ports):
    port_nums = [p["port"] for p in ports]
    hn = (hostname or "").lower()
    vn = (vendor or "").lower()
    
    if "printer" in vn or 9100 in port_nums or 515 in port_nums or 631 in port_nums:
        return ("printer", "🖨️", vendor if "printer" in vn.lower() else "Network Printer")
    if vendor in ["Geeni", "Geeni/Tuya", "Tuya", "Wyze", "Hikvision", "Dahua", "Amcrest", "Reolink"]:
        return ("ip_camera", "📷", f"{vendor} Camera")
    if 554 in port_nums or 8554 in port_nums or "cam" in hn:
        return ("ip_camera", "📷", "IP Camera")
    if 32400 in port_nums: return ("plex", "🎬", "Plex Server")
    if 8096 in port_nums: return ("jellyfin", "🎬", "Jellyfin Server")
    if vendor in ["PlayStation", "Xbox", "Nintendo"]: return ("gaming", "🎮", vendor)
    if vendor in ["Nest", "Ring", "Amazon Echo"]: return ("smart_home", "🏠", vendor)
    if vendor in ["Roku", "Amazon Fire"]: return ("streaming", "📺", vendor)
    if vendor in ["TP-Link", "Linksys", "Netgear"]: return ("router", "📶", vendor)
    if 22 in port_nums or 3389 in port_nums or 5900 in port_nums: return ("computer", "💻", "Computer")
    if 80 in port_nums or 443 in port_nums: return ("server", "🖥️", "Server/Device")
    if icon != "❓": return ("device", icon, vendor)
    return ("unknown", "❓", "Unknown Device")

def scan_network():
    global discovered_devices, scan_status
    scan_status = {"running": True, "progress": 0, "message": "Starting scan..."}
    discovered_devices = []
    
    network = get_network_range()
    local_ip = get_local_ip()
    scan_status["message"] = f"Scanning {network}.0/24..."
    
    results = []
    threads = []
    for i in range(1, 255):
        t = threading.Thread(target=ping_host, args=(f"{network}.{i}", results))
        t.start()
        threads.append(t)
        if i % 50 == 0:
            scan_status["progress"] = int((i / 255) * 40)
            scan_status["message"] = f"Pinging {network}.{i}..."
    
    for t in threads: t.join(timeout=3)
    
    scan_status["progress"] = 40
    scan_status["message"] = "Reading ARP table..."
    arp = get_arp_table()
    
    total = len(arp)
    for idx, (ip, mac) in enumerate(arp.items()):
        scan_status["progress"] = 40 + int((idx / max(total, 1)) * 60)
        scan_status["message"] = f"Analyzing {ip}..."
        
        vendor, vendor_icon = get_mac_info(mac)
        hostname = get_hostname(ip)
        ports = check_ports(ip)
        dev_type, icon, type_name = determine_type(hostname, vendor, vendor_icon, ports)
        
        discovered_devices.append({
            "id": f"auto_{ip.replace('.', '_')}",
            "ip": ip, "mac": mac, "hostname": hostname, "vendor": vendor,
            "type": dev_type, "type_name": type_name, "icon": icon,
            "open_ports": ports, "is_local": ip == local_ip,
            "discovered_at": datetime.now().isoformat()
        })
    
    discovered_devices.sort(key=lambda d: [int(x) for x in d["ip"].split('.')])
    scan_status = {"running": False, "progress": 100, "message": f"Found {len(discovered_devices)} devices"}
    return discovered_devices

def sync_to_truevault(devices):
    if not auth_token: return {"success": False, "error": "Not authenticated"}
    try:
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
        
        data = json.dumps({"devices": devices}).encode()
        req = urllib.request.Request(f"{TRUEVAULT_API}/cameras/sync.php",
            data=data, headers={"Content-Type": "application/json", "Authorization": f"Bearer {auth_token}"})
        
        with urllib.request.urlopen(req, context=ctx, timeout=15) as resp:
            return json.loads(resp.read().decode())
    except Exception as e:
        return {"success": False, "error": str(e)}

# ============== WEB SERVER ==============
HTML = '''<!DOCTYPE html>
<html><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>TrueVault Network Scanner</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:linear-gradient(135deg,#0f0f1a,#1a1a2e);color:#fff;min-height:100vh;padding:20px}
.container{max-width:1100px;margin:0 auto}
header{display:flex;align-items:center;justify-content:space-between;margin-bottom:25px;flex-wrap:wrap;gap:15px}
.logo{display:flex;align-items:center;gap:12px}
.logo h1{font-size:1.6rem;background:linear-gradient(90deg,#00d9ff,#00ff88);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.badge{padding:6px 14px;border-radius:20px;font-size:.85rem;font-weight:600}
.badge-ok{background:rgba(0,255,136,.15);color:#00ff88;border:1px solid #00ff88}
.card{background:rgba(255,255,255,.04);border-radius:14px;padding:18px;margin-bottom:18px;border:1px solid rgba(255,255,255,.08)}
.card h2{font-size:1.15rem;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.btn{padding:10px 20px;border:none;border-radius:8px;font-size:.95rem;font-weight:600;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:6px}
.btn-primary{background:linear-gradient(90deg,#00d9ff,#00ff88);color:#0f0f1a}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 4px 15px rgba(0,217,255,.3)}
.btn-primary:disabled{opacity:.4;cursor:not-allowed;transform:none}
.btn-secondary{background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.15)}
.progress{height:5px;background:rgba(255,255,255,.1);border-radius:3px;overflow:hidden;margin:12px 0}
.progress-bar{height:100%;background:linear-gradient(90deg,#00d9ff,#00ff88);transition:width .3s}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}
.device{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:12px;cursor:pointer;transition:.2s}
.device:hover{background:rgba(255,255,255,.07);border-color:#00d9ff}
.device.selected{border-color:#00ff88;background:rgba(0,255,136,.08)}
.device.camera{border-left:3px solid #ff6b6b}
.device-head{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.device-icon{font-size:1.8rem}
.device h3{font-size:.95rem;color:#fff}
.device .ip{font-family:monospace;color:#00d9ff;font-size:.85rem}
.tags{display:flex;flex-wrap:wrap;gap:5px;margin-top:8px}
.tag{padding:3px 7px;background:rgba(255,255,255,.08);border-radius:4px;font-size:.7rem;color:#999}
.tag.port{color:#00ff88}
.tag.camera{background:rgba(255,107,107,.2);color:#ff6b6b}
.empty{text-align:center;padding:35px;color:#555}
.empty .icon{font-size:3.5rem;margin-bottom:12px}
.actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:15px}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:15px}
.stat{background:rgba(255,255,255,.03);border-radius:8px;padding:12px;text-align:center}
.stat-num{font-size:1.8rem;font-weight:700;background:linear-gradient(90deg,#00d9ff,#00ff88);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.stat-label{font-size:.75rem;color:#666;margin-top:2px}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
.scanning{animation:pulse 1s infinite}
.toast{position:fixed;bottom:20px;right:20px;padding:12px 18px;border-radius:8px;font-weight:600;z-index:1000;animation:slideIn .3s}
.toast.ok{background:#00c853}.toast.err{background:#ff5252}
@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}
</style>
</head><body>
<div class="container">
<header>
<div class="logo"><span style="font-size:2rem">🔍</span><div><h1>TrueVault Scanner</h1><small style="color:#555">v''' + VERSION + '''</small></div></div>
<div id="status" class="badge badge-ok">✓ Connected</div>
</header>

<div class="stats">
<div class="stat"><div class="stat-num" id="total-count">0</div><div class="stat-label">Total Devices</div></div>
<div class="stat"><div class="stat-num" id="camera-count">0</div><div class="stat-label">📷 Cameras</div></div>
<div class="stat"><div class="stat-num" id="smart-count">0</div><div class="stat-label">🏠 Smart Home</div></div>
<div class="stat"><div class="stat-num" id="other-count">0</div><div class="stat-label">Other</div></div>
</div>

<div class="card">
<h2>📡 Network Scan</h2>
<div id="scan-msg" style="color:#888;margin-bottom:10px">Ready to scan your network</div>
<div class="progress"><div id="prog" class="progress-bar" style="width:0%"></div></div>
<button id="scan-btn" class="btn btn-primary" onclick="startScan()">🔍 Scan Network</button>
</div>

<div class="card">
<h2>📱 Discovered Devices</h2>
<div id="devices" class="grid"><div class="empty"><div class="icon">🔍</div><p>Click "Scan Network" to discover devices</p></div></div>
<div class="actions">
<button class="btn btn-primary" onclick="syncSelected()" id="sync-btn" disabled>☁️ Sync to TrueVault</button>
<button class="btn btn-secondary" onclick="selectCameras()">📷 Select Cameras</button>
<button class="btn btn-secondary" onclick="selectAll()">✅ All</button>
<button class="btn btn-secondary" onclick="deselectAll()">❌ None</button>
</div>
</div>
</div>

<script>
let devices=[],selected=new Set(),poll=null;
const $=id=>document.getElementById(id);

function toast(m,ok=true){const t=document.createElement('div');t.className='toast '+(ok?'ok':'err');t.textContent=m;document.body.appendChild(t);setTimeout(()=>t.remove(),3000)}

async function startScan(){
  $('scan-btn').disabled=true;$('scan-btn').innerHTML='🔄 Scanning...';$('scan-btn').classList.add('scanning');
  await fetch('/scan',{method:'POST'});
  poll=setInterval(pollStatus,500);
}

async function pollStatus(){
  const r=await fetch('/status').then(r=>r.json());
  $('scan-msg').textContent=r.message;$('prog').style.width=r.progress+'%';
  if(!r.running){
    clearInterval(poll);$('scan-btn').disabled=false;$('scan-btn').innerHTML='🔍 Scan Network';$('scan-btn').classList.remove('scanning');
    devices=await fetch('/devices').then(r=>r.json());
    render();
  }
}

function render(){
  const cams=devices.filter(d=>d.type==='ip_camera').length;
  const smart=devices.filter(d=>['smart_home','streaming'].includes(d.type)).length;
  $('total-count').textContent=devices.length;
  $('camera-count').textContent=cams;
  $('smart-count').textContent=smart;
  $('other-count').textContent=devices.length-cams-smart;
  
  if(!devices.length){$('devices').innerHTML='<div class="empty"><div class="icon">🔭</div><p>No devices found</p></div>';return}
  
  $('devices').innerHTML=devices.map(d=>`
    <div class="device ${selected.has(d.id)?'selected':''} ${d.type==='ip_camera'?'camera':''}" onclick="toggle('${d.id}')">
      <div class="device-head"><span class="device-icon">${d.icon}</span><div><h3>${d.hostname||d.type_name}</h3><div class="ip">${d.ip}</div></div></div>
      <div class="tags">
        <span class="tag">${d.vendor}</span>
        ${d.type==='ip_camera'?'<span class="tag camera">CAMERA</span>':''}
        ${d.open_ports.slice(0,3).map(p=>'<span class="tag port">:'+p.port+'</span>').join('')}
      </div>
    </div>
  `).join('');
  $('sync-btn').disabled=selected.size===0;
}

function toggle(id){selected.has(id)?selected.delete(id):selected.add(id);render()}
function selectAll(){devices.forEach(d=>selected.add(d.id));render()}
function deselectAll(){selected.clear();render()}
function selectCameras(){selected.clear();devices.filter(d=>d.type==='ip_camera').forEach(d=>selected.add(d.id));render();toast('Selected '+selected.size+' cameras')}

async function syncSelected(){
  const sel=devices.filter(d=>selected.has(d.id));
  if(!sel.length){toast('Select devices first',false);return}
  $('sync-btn').disabled=true;$('sync-btn').innerHTML='⏳ Syncing...';
  const r=await fetch('/sync',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({devices:sel})}).then(r=>r.json());
  $('sync-btn').disabled=false;$('sync-btn').innerHTML='☁️ Sync to TrueVault';
  if(r.success){toast('Synced '+sel.length+' devices!')}else{toast(r.error||'Sync failed',false)}
}

setTimeout(startScan,500);
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
            threading.Thread(target=scan_network, daemon=True).start()
            self.send_json({"success": True, "message": "Scan started"})
        elif self.path == '/sync':
            length = int(self.headers.get('Content-Length', 0))
            data = json.loads(self.rfile.read(length)) if length else {}
            result = sync_to_truevault(data.get('devices', []))
            self.send_json(result)
        else:
            self.send_json({"error": "Not found"}, 404)

def main():
    global auth_token, user_email
    
    print(f"""
╔═══════════════════════════════════════════════════════════╗
║       TrueVault Network Scanner v{VERSION}                ║
║       Discover devices on your home network               ║
╚═══════════════════════════════════════════════════════════╝
""")
    
    if len(sys.argv) >= 3:
        user_email = sys.argv[1]
        auth_token = sys.argv[2]
    else:
        print("Usage: python truevault_scanner.py EMAIL TOKEN")
        print("Or run without args for manual entry\n")
        user_email = input("TrueVault Email: ").strip()
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
