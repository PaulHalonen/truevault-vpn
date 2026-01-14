#!/usr/bin/env python3
"""
TruthVault Network Scanner v2.0
Auto-discovers devices on your home network and syncs to TruthVault VPN
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
import urllib.request
import ssl

TRUTHVAULT_API = "https://vpn.the-truth-publishing.com/api"
LOCAL_PORT = 8888
VERSION = "2.0.0"

MAC_VENDORS = {
    "D8:1D:2E": ("Geeni", "📷"), "D8:F1:5B": ("Geeni", "📷"), "10:D5:61": ("Geeni", "📷"),
    "24:62:AB": ("Geeni/Tuya", "📷"), "2C:AA:8E": ("Wyze", "📷"), "D0:3F:27": ("Wyze", "📷"),
    "D8:EB:46": ("Hikvision", "📷"), "C0:56:E3": ("Hikvision", "📷"), "44:19:B6": ("Hikvision", "📷"),
    "00:09:B0": ("Dahua", "📷"), "78:A5:DD": ("Amcrest", "📷"), "00:62:6E": ("Reolink", "📷"),
    "00:D0:2D": ("Ring", "🚪"), "18:B4:30": ("Nest", "🏠"), "FC:A1:83": ("Amazon Echo", "🔊"),
    "00:1D:D0": ("Roku", "📺"), "00:1A:79": ("Apple", "📱"), "00:15:99": ("Samsung", "📺"),
    "00:04:20": ("PlayStation", "🎮"), "7C:BB:8A": ("Nintendo", "🎮"), "00:50:F2": ("Xbox", "🎮"),
    "00:1E:0B": ("HP Printer", "🖨️"), "00:90:4C": ("Epson Printer", "🖨️"), "00:1E:8F": ("Canon Printer", "🖨️"),
    "00:17:7C": ("TP-Link", "📶"), "00:14:BF": ("Linksys", "📶"), "00:1F:33": ("Netgear", "📶"),
    "B8:27:EB": ("Raspberry Pi", "🖥️"),
}

discovered_devices = []
scan_status = {"running": False, "progress": 0, "message": "Ready"}
auth_token = None
user_email = None

def get_mac_info(mac):
    if not mac: return ("Unknown", "❓")
    return MAC_VENDORS.get(mac[:8].upper(), ("Unknown", "❓"))

def get_local_ip():
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except: return "192.168.1.1"

def get_network_range():
    parts = get_local_ip().split('.')
    return f"{parts[0]}.{parts[1]}.{parts[2]}"

def ping_host(ip, results, timeout=1):
    param = '-n' if platform.system().lower() == 'windows' else '-c'
    timeout_param = '-w' if platform.system().lower() == 'windows' else '-W'
    timeout_val = str(int(timeout * 1000)) if platform.system().lower() == 'windows' else str(timeout)
    try:
        result = subprocess.run(['ping', param, '1', timeout_param, timeout_val, ip],
            stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=timeout + 1)
        if result.returncode == 0: results.append(ip)
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

def check_ports(ip):
    ports = {80: "http", 443: "https", 554: "rtsp", 8080: "http-alt", 9100: "printer", 22: "ssh"}
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
    
    if "printer" in vn or 9100 in port_nums: return ("printer", "🖨️", vendor or "Printer")
    if vendor in ["Geeni", "Geeni/Tuya", "Wyze", "Hikvision", "Dahua", "Amcrest", "Reolink"]:
        return ("ip_camera", "📷", f"{vendor} Camera")
    if 554 in port_nums or "camera" in hn: return ("ip_camera", "📷", "IP Camera")
    if vendor in ["PlayStation", "Xbox", "Nintendo"]: return ("gaming", "🎮", vendor)
    if vendor in ["Nest", "Ring", "Amazon Echo"]: return ("smart_home", "🏠", vendor)
    if vendor in ["Roku", "Amazon Fire"]: return ("streaming", "📺", vendor)
    if icon != "❓": return ("device", icon, vendor)
    return ("unknown", "❓", "Unknown Device")

def scan_network():
    global discovered_devices, scan_status
    scan_status = {"running": True, "progress": 0, "message": "Starting scan..."}
    discovered_devices = []
    
    network = get_network_range()
    local_ip = get_local_ip()
    scan_status["message"] = f"Scanning {network}.0/24..."
    
    results, threads = [], []
    for i in range(1, 255):
        t = threading.Thread(target=ping_host, args=(f"{network}.{i}", results))
        t.start()
        threads.append(t)
        if i % 50 == 0:
            scan_status["progress"] = int((i / 255) * 40)
    
    for t in threads: t.join(timeout=3)
    
    scan_status["progress"] = 40
    scan_status["message"] = "Reading ARP table..."
    arp = get_arp_table()
    
    for idx, (ip, mac) in enumerate(arp.items()):
        scan_status["progress"] = 40 + int((idx / max(len(arp), 1)) * 60)
        scan_status["message"] = f"Analyzing {ip}..."
        
        vendor, vendor_icon = get_mac_info(mac)
        try: hostname = socket.gethostbyaddr(ip)[0]
        except: hostname = None
        ports = check_ports(ip)
        dev_type, icon, type_name = determine_type(hostname, vendor, vendor_icon, ports)
        
        discovered_devices.append({
            "id": f"auto_{ip.replace('.', '_')}", "ip": ip, "mac": mac,
            "hostname": hostname, "vendor": vendor, "type": dev_type,
            "type_name": type_name, "icon": icon, "open_ports": ports,
            "is_local": ip == local_ip, "discovered_at": datetime.now().isoformat()
        })
    
    discovered_devices.sort(key=lambda d: [int(x) for x in d["ip"].split('.')])
    scan_status = {"running": False, "progress": 100, "message": f"Found {len(discovered_devices)} devices"}

def sync_to_truthvault(devices):
    if not auth_token: return {"success": False, "error": "Not connected"}
    try:
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
        data = json.dumps({"devices": devices}).encode()
        req = urllib.request.Request(f"{TRUTHVAULT_API}/network-scanner.php",
            data=data, headers={"Content-Type": "application/json", "Authorization": f"Bearer {auth_token}"})
        with urllib.request.urlopen(req, context=ctx, timeout=10) as resp:
            return json.loads(resp.read().decode())
    except Exception as e:
        return {"success": False, "error": str(e)}

HTML = '''<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>TruthVault Network Scanner</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:linear-gradient(135deg,#0f0f1a,#1a1a2e);color:#fff;min-height:100vh;padding:20px}
.container{max-width:1100px;margin:0 auto}
header{display:flex;align-items:center;justify-content:space-between;margin-bottom:25px}
.logo h1{font-size:1.6rem;background:linear-gradient(90deg,#00d9ff,#00ff88);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.badge{padding:6px 14px;border-radius:20px;font-size:.85rem;background:rgba(0,255,136,.15);color:#00ff88;border:1px solid #00ff88}
.card{background:rgba(255,255,255,.04);border-radius:14px;padding:18px;margin-bottom:18px;border:1px solid rgba(255,255,255,.08)}
.btn{padding:10px 20px;border:none;border-radius:8px;font-weight:600;cursor:pointer}
.btn-primary{background:linear-gradient(90deg,#00d9ff,#00ff88);color:#0f0f1a}
.btn-secondary{background:rgba(255,255,255,.08);color:#fff}
.progress{height:5px;background:rgba(255,255,255,.1);border-radius:3px;margin:12px 0}
.progress-bar{height:100%;background:linear-gradient(90deg,#00d9ff,#00ff88);transition:width .3s}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}
.device{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:12px;cursor:pointer}
.device:hover{border-color:#00d9ff}
.device.selected{border-color:#00ff88;background:rgba(0,255,136,.08)}
.device.camera{border-left:3px solid #ff6b6b}
.device-icon{font-size:1.8rem}
.ip{font-family:monospace;color:#00d9ff;font-size:.85rem}
.tags{display:flex;flex-wrap:wrap;gap:5px;margin-top:8px}
.tag{padding:3px 7px;background:rgba(255,255,255,.08);border-radius:4px;font-size:.7rem;color:#999}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:15px}
.stat{background:rgba(255,255,255,.03);border-radius:8px;padding:12px;text-align:center}
.stat-num{font-size:1.8rem;font-weight:700;background:linear-gradient(90deg,#00d9ff,#00ff88);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.toast{position:fixed;bottom:20px;right:20px;padding:12px 18px;border-radius:8px;font-weight:600;z-index:1000}
.toast.ok{background:#00c853}.toast.err{background:#ff5252}
</style></head><body>
<div class="container">
<header><div class="logo"><h1>🔐 TruthVault Scanner v''' + VERSION + '''</h1></div><div id="status" class="badge">✔ Connected</div></header>
<div class="stats">
<div class="stat"><div class="stat-num" id="total-count">0</div><div>Total</div></div>
<div class="stat"><div class="stat-num" id="camera-count">0</div><div>📷 Cameras</div></div>
<div class="stat"><div class="stat-num" id="smart-count">0</div><div>🏠 Smart</div></div>
<div class="stat"><div class="stat-num" id="other-count">0</div><div>Other</div></div>
</div>
<div class="card">
<h2>📡 Network Scan</h2>
<div id="scan-msg" style="color:#888;margin:10px 0">Ready to scan</div>
<div class="progress"><div id="prog" class="progress-bar" style="width:0%"></div></div>
<button id="scan-btn" class="btn btn-primary" onclick="startScan()">🔍 Scan Network</button>
</div>
<div class="card">
<h2>📱 Devices</h2>
<div id="devices" class="grid"></div>
<div style="margin-top:15px;display:flex;gap:8px">
<button class="btn btn-primary" onclick="syncSelected()" id="sync-btn" disabled>☁️ Sync to TruthVault</button>
<button class="btn btn-secondary" onclick="selectCameras()">📷 Cameras</button>
<button class="btn btn-secondary" onclick="selectAll()">✅ All</button>
<button class="btn btn-secondary" onclick="deselectAll()">❌ None</button>
</div>
</div>
</div>
<script>
let devices=[],selected=new Set(),poll=null;
const $=id=>document.getElementById(id);
function toast(m,ok=true){const t=document.createElement('div');t.className='toast '+(ok?'ok':'err');t.textContent=m;document.body.appendChild(t);setTimeout(()=>t.remove(),3000)}
async function startScan(){$('scan-btn').disabled=true;await fetch('/scan',{method:'POST'});poll=setInterval(pollStatus,500)}
async function pollStatus(){const r=await fetch('/status').then(r=>r.json());$('scan-msg').textContent=r.message;$('prog').style.width=r.progress+'%';if(!r.running){clearInterval(poll);$('scan-btn').disabled=false;devices=await fetch('/devices').then(r=>r.json());render()}}
function render(){const cams=devices.filter(d=>d.type==='ip_camera').length;const smart=devices.filter(d=>['smart_home','streaming'].includes(d.type)).length;$('total-count').textContent=devices.length;$('camera-count').textContent=cams;$('smart-count').textContent=smart;$('other-count').textContent=devices.length-cams-smart;$('devices').innerHTML=devices.map(d=>`<div class="device ${selected.has(d.id)?'selected':''} ${d.type==='ip_camera'?'camera':''}" onclick="toggle('${d.id}')"><div style="display:flex;align-items:center;gap:10px"><span class="device-icon">${d.icon}</span><div><h3>${d.hostname||d.type_name}</h3><div class="ip">${d.ip}</div></div></div><div class="tags"><span class="tag">${d.vendor}</span>${d.open_ports.slice(0,3).map(p=>'<span class="tag">:'+p.port+'</span>').join('')}</div></div>`).join('');$('sync-btn').disabled=selected.size===0}
function toggle(id){selected.has(id)?selected.delete(id):selected.add(id);render()}
function selectAll(){devices.forEach(d=>selected.add(d.id));render()}
function deselectAll(){selected.clear();render()}
function selectCameras(){selected.clear();devices.filter(d=>d.type==='ip_camera').forEach(d=>selected.add(d.id));render();toast('Selected '+selected.size+' cameras')}
async function syncSelected(){const sel=devices.filter(d=>selected.has(d.id));if(!sel.length)return;$('sync-btn').disabled=true;const r=await fetch('/sync',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({devices:sel})}).then(r=>r.json());$('sync-btn').disabled=false;r.success?toast('Synced '+sel.length+' devices!'):toast(r.error||'Sync failed',false)}
setTimeout(startScan,500);
</script></body></html>'''

class Handler(SimpleHTTPRequestHandler):
    def log_message(self, *args): pass
    def send_json(self, data, code=200):
        self.send_response(code)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.end_headers()
        self.wfile.write(json.dumps(data).encode())
    def do_GET(self):
        if self.path == '/': self.send_response(200); self.send_header('Content-Type', 'text/html'); self.end_headers(); self.wfile.write(HTML.encode())
        elif self.path == '/status': self.send_json(scan_status)
        elif self.path == '/devices': self.send_json(discovered_devices)
        else: self.send_json({"error": "Not found"}, 404)
    def do_POST(self):
        if self.path == '/scan': threading.Thread(target=scan_network, daemon=True).start(); self.send_json({"success": True})
        elif self.path == '/sync':
            length = int(self.headers.get('Content-Length', 0))
            data = json.loads(self.rfile.read(length)) if length else {}
            self.send_json(sync_to_truthvault(data.get('devices', [])))
        else: self.send_json({"error": "Not found"}, 404)

def main():
    global auth_token, user_email
    print(f"\n{'='*50}\n  TruthVault Network Scanner v{VERSION}\n{'='*50}\n")
    if len(sys.argv) >= 3: user_email, auth_token = sys.argv[1], sys.argv[2]
    else: user_email = input("TruthVault Email: ").strip(); auth_token = input("Auth Token: ").strip()
    print(f"\n✔ User: {user_email}\n✔ Starting on http://localhost:{LOCAL_PORT}\n")
    webbrowser.open(f"http://localhost:{LOCAL_PORT}")
    server = HTTPServer(('0.0.0.0', LOCAL_PORT), Handler)
    print("🔍 Scanner ready! Press Ctrl+C to quit.\n")
    try: server.serve_forever()
    except KeyboardInterrupt: print("\n\nScanner stopped."); server.shutdown()

if __name__ == '__main__': main()
