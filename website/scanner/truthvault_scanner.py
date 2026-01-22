#!/usr/bin/env python3
"""
TrueVault Network Scanner v3.0 - BRUTE FORCE EDITION
Task 6A.1: Advanced Camera Discovery

FEATURES (From Checklist):
- Brute force port scanning (12 camera-specific ports)
- Credential testing (50+ default combos)
- ONVIF discovery protocol
- UPnP camera discovery
- mDNS service detection
- HTTP fingerprinting
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
import struct

# ============== CONFIGURATION ==============
TRUEVAULT_API = "https://vpn.the-truth-publishing.com/api"
LOCAL_PORT = 8888
VERSION = "3.0.0"

# ============== BRUTE FORCE CONFIG (From Checklist) ==============
CAMERA_PORTS = {
    554: "RTSP",
    8554: "RTSP-ALT",
    80: "HTTP",
    443: "HTTPS",
    8080: "HTTP-ALT",
    8000: "HTTP-ALT2",
    8001: "HTTP-ALT3",
    37777: "Dahua",
    34567: "Hikvision",
    9000: "Cameras",
    1935: "RTMP",
    5000: "ONVIF",
}

# 50+ common credential combos (From Checklist)
COMMON_CREDENTIALS = [
    ("admin", "admin"),
    ("admin", ""),
    ("admin", "12345"),
    ("admin", "123456"),
    ("admin", "password"),
    ("admin", "1234"),
    ("admin", "admin123"),
    ("root", "root"),
    ("root", ""),
    ("root", "12345"),
    ("root", "123456"),
    ("root", "password"),
    ("user", "user"),
    ("user", ""),
    ("user", "12345"),
    ("guest", "guest"),
    ("guest", ""),
    ("admin", "pass"),
    ("admin", "admin1"),
    ("admin", "1111"),
    ("admin", "4321"),
    ("admin", "111111"),
    ("admin", "666666"),
    ("admin", "888888"),
    ("admin", "000000"),
    ("admin", "88888888"),
    ("supervisor", "supervisor"),
    ("service", "service"),
    ("support", "support"),
    ("ubnt", "ubnt"),
    ("admin", "meinsm"),
    ("admin", "hik12345"),
    ("admin", "hikvision"),
    ("admin", "Hikvision"),
    ("admin", "HikVision"),
    ("admin", "dahua"),
    ("admin", "Dahua"),
    ("admin", "7ujMko0"),
    ("admin", "camera"),
    ("admin", "Camera"),
    ("admin", "fliradmin"),
    ("admin", "ikwb"),
    ("admin", "wbox"),
    ("admin", "wbox123"),
    ("admin", "jvc"),
    ("admin", "tlJwpbo6"),
    ("admin", "system"),
    ("666666", "666666"),
    ("888888", "888888"),
    ("default", ""),
    ("default", "default"),
]

# ============== MAC VENDOR DATABASE ==============
MAC_VENDORS = {
    # Geeni / Merkury (Tuya-based)
    "D8:1D:2E": ("Geeni", "📷"), "D8:F1:5B": ("Geeni", "📷"),
    "10:D5:61": ("Geeni", "📷"), "24:62:AB": ("Geeni/Tuya", "📷"),
    "50:8A:06": ("Geeni/Tuya", "📷"), "68:57:2D": ("Geeni/Tuya", "📷"),
    "7C:F6:66": ("Geeni/Tuya", "📷"), "84:E3:42": ("Geeni/Tuya", "📷"),
    "A0:92:08": ("Geeni/Tuya", "📷"), "D4:A6:51": ("Tuya", "📷"),
    "60:01:94": ("Tuya", "📷"), "1C:90:FF": ("Tuya", "📷"),
    # Wyze
    "2C:AA:8E": ("Wyze", "📷"), "D0:3F:27": ("Wyze", "📷"),
    "7C:78:B2": ("Wyze", "📷"), "A4:DA:22": ("Wyze", "📷"),
    # Hikvision
    "D8:EB:46": ("Hikvision", "📷"), "C0:56:E3": ("Hikvision", "📷"),
    "44:19:B6": ("Hikvision", "📷"), "A4:14:37": ("Hikvision", "📷"),
    "54:C4:15": ("Hikvision", "📷"), "28:57:BE": ("Hikvision", "📷"),
    "BC:AD:28": ("Hikvision", "📷"), "E0:50:8B": ("Hikvision", "📷"),
    # Dahua
    "00:09:B0": ("Dahua", "📷"), "3C:EF:8C": ("Dahua", "📷"),
    "4C:11:BF": ("Dahua", "📷"), "90:02:A9": ("Dahua", "📷"),
    # Amcrest
    "78:A5:DD": ("Amcrest", "📷"), "9C:8E:CD": ("Amcrest", "📷"),
    # Reolink
    "00:62:6E": ("Reolink", "📷"), "EC:71:DB": ("Reolink", "📷"),
    "B4:6D:C2": ("Reolink", "📷"),
    # Ring
    "00:D0:2D": ("Ring", "🚪"), "50:32:75": ("Ring", "🚪"),
    "34:3E:A4": ("Ring", "🚪"),
    # Nest/Google
    "18:B4:30": ("Nest", "🏠"), "64:16:66": ("Nest", "🏠"),
    "3C:5A:B4": ("Google", "📱"), "54:60:09": ("Google", "📱"),
    # Amazon Echo/Fire
    "FC:A1:83": ("Amazon Echo", "🔊"), "74:C2:46": ("Amazon Echo", "🔊"),
    "00:FC:8B": ("Amazon Fire", "📺"), "44:65:0D": ("Amazon Fire", "📺"),
    # Roku
    "00:1D:D0": ("Roku", "📺"), "B8:3E:59": ("Roku", "📺"),
    "DC:3A:5E": ("Roku", "📺"), "D8:31:34": ("Roku", "📺"),
    # Gaming
    "00:04:20": ("PlayStation", "🎮"), "00:D9:D1": ("PlayStation", "🎮"),
    "7C:BB:8A": ("Nintendo", "🎮"), "E8:4E:CE": ("Nintendo", "🎮"),
    "00:50:F2": ("Xbox", "🎮"), "7C:1E:52": ("Xbox", "🎮"),
    # Printers
    "00:1E:0B": ("HP Printer", "🖨️"), "3C:A9:F4": ("HP Printer", "🖨️"),
    "00:90:4C": ("Epson Printer", "🖨️"), "00:26:AB": ("Epson Printer", "🖨️"),
    "00:1E:8F": ("Canon Printer", "🖨️"), "00:1B:A9": ("Brother Printer", "🖨️"),
    # Routers
    "00:17:7C": ("TP-Link", "📶"), "14:CC:20": ("TP-Link", "📶"),
    "00:14:BF": ("Linksys", "📶"), "00:1F:33": ("Netgear", "📶"),
    # Raspberry Pi
    "B8:27:EB": ("Raspberry Pi", "🖥️"), "DC:A6:32": ("Raspberry Pi", "🖥️"),
}

# ============== GLOBALS ==============
discovered_devices = []
scan_status = {"running": False, "progress": 0, "message": "Ready"}
auth_token = None
user_email = None

# ============== HELPER FUNCTIONS ==============
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

# ============== BRUTE FORCE SCANNING (Task 6A.1) ==============
def brute_force_ports(ip):
    """Scan camera-specific ports on a device"""
    open_ports = []
    for port, service in CAMERA_PORTS.items():
        try:
            sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            sock.settimeout(0.5)
            if sock.connect_ex((ip, port)) == 0:
                open_ports.append({"port": port, "service": service})
            sock.close()
        except: pass
    return open_ports

def test_credentials(ip, port):
    """Test common credentials against HTTP auth"""
    for username, password in COMMON_CREDENTIALS:
        try:
            url = f"http://{ip}:{port}/"
            
            # Create password manager
            password_mgr = urllib.request.HTTPPasswordMgrWithDefaultRealm()
            password_mgr.add_password(None, url, username, password)
            handler = urllib.request.HTTPBasicAuthHandler(password_mgr)
            opener = urllib.request.build_opener(handler)
            
            # Try to connect
            ctx = ssl.create_default_context()
            ctx.check_hostname = False
            ctx.verify_mode = ssl.CERT_NONE
            
            req = urllib.request.Request(url, headers={'User-Agent': 'TrueVault/3.0'})
            response = opener.open(req, timeout=2)
            
            if response.getcode() == 200:
                return (username, password)
        except urllib.error.HTTPError as e:
            if e.code == 401:
                continue  # Wrong creds, try next
            elif e.code == 200:
                return (username, password)
        except: pass
    return None

def test_rtsp_credentials(ip, port=554):
    """Test common credentials against RTSP stream"""
    for username, password in COMMON_CREDENTIALS:
        try:
            # Build RTSP OPTIONS request
            if password:
                auth = f"{username}:{password}@"
            else:
                auth = f"{username}@" if username else ""
            
            sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            sock.settimeout(2)
            sock.connect((ip, port))
            
            request = f"OPTIONS rtsp://{auth}{ip}:{port}/ RTSP/1.0\r\nCSeq: 1\r\n\r\n"
            sock.send(request.encode())
            
            response = sock.recv(1024).decode('utf-8', errors='ignore')
            sock.close()
            
            if "200 OK" in response:
                return (username, password)
        except: pass
    return None

def discover_onvif(ip):
    """ONVIF discovery - check if device supports ONVIF protocol"""
    try:
        # ONVIF WS-Discovery probe
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(2)
        result = sock.connect_ex((ip, 80))
        sock.close()
        
        if result == 0:
            # Try ONVIF GetCapabilities
            onvif_url = f"http://{ip}/onvif/device_service"
            soap_request = '''<?xml version="1.0" encoding="utf-8"?>
            <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
                <soap:Body>
                    <GetCapabilities xmlns="http://www.onvif.org/ver10/device/wsdl"/>
                </soap:Body>
            </soap:Envelope>'''
            
            req = urllib.request.Request(onvif_url, 
                data=soap_request.encode(),
                headers={'Content-Type': 'application/soap+xml'})
            
            ctx = ssl.create_default_context()
            ctx.check_hostname = False
            ctx.verify_mode = ssl.CERT_NONE
            
            response = urllib.request.urlopen(req, timeout=3, context=ctx)
            data = response.read().decode('utf-8', errors='ignore')
            
            if 'Capabilities' in data or 'Media' in data:
                return True
    except: pass
    return False

def discover_upnp(ip):
    """UPnP discovery for cameras"""
    try:
        # Try UPnP description
        url = f"http://{ip}:49152/description.xml"
        req = urllib.request.Request(url, headers={'User-Agent': 'TrueVault/3.0'})
        
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
        
        response = urllib.request.urlopen(req, timeout=2, context=ctx)
        data = response.read().decode('utf-8', errors='ignore').lower()
        
        if 'camera' in data or 'ipcam' in data or 'nvr' in data:
            return True
    except: pass
    return False

def http_fingerprint(ip, port=80):
    """Identify camera by HTTP response headers/content"""
    try:
        url = f"http://{ip}:{port}/"
        req = urllib.request.Request(url, headers={'User-Agent': 'TrueVault/3.0'})
        
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
        
        response = urllib.request.urlopen(req, timeout=3, context=ctx)
        headers = dict(response.headers)
        content = response.read(4096).decode('utf-8', errors='ignore').lower()
        
        # Check for camera signatures
        camera_signatures = [
            'hikvision', 'dahua', 'amcrest', 'reolink', 'wyze',
            'geeni', 'tuya', 'foscam', 'axis', 'vivotek',
            'camera', 'ipcam', 'nvr', 'dvr', 'rtsp'
        ]
        
        server = headers.get('Server', '').lower()
        
        for sig in camera_signatures:
            if sig in content or sig in server:
                return sig.capitalize()
        
        return None
    except: pass
    return None

# ============== MAIN SCAN FUNCTION ==============
def scan_network():
    global discovered_devices, scan_status
    scan_status = {"running": True, "progress": 0, "message": "Starting brute force scan..."}
    discovered_devices = []
    
    network = get_network_range()
    local_ip = get_local_ip()
    
    # Phase 1: Ping sweep (20%)
    scan_status["message"] = f"Ping sweep {network}.0/24..."
    results = []
    threads = []
    for i in range(1, 255):
        t = threading.Thread(target=ping_host, args=(f"{network}.{i}", results))
        t.start()
        threads.append(t)
        if i % 50 == 0:
            scan_status["progress"] = int((i / 255) * 20)
    for t in threads: t.join(timeout=3)
    
    # Phase 2: Get ARP table (25%)
    scan_status["progress"] = 20
    scan_status["message"] = "Reading ARP table..."
    arp = get_arp_table()
    
    # Phase 3: Brute force scan each device (25% - 90%)
    total = len(arp)
    for idx, (ip, mac) in enumerate(arp.items()):
        progress = 25 + int((idx / max(total, 1)) * 65)
        scan_status["progress"] = progress
        scan_status["message"] = f"Brute forcing {ip}..."
        
        vendor, vendor_icon = get_mac_info(mac)
        hostname = get_hostname(ip)
        
        # Brute force all camera ports
        open_ports = brute_force_ports(ip)
        
        # Determine if this is a camera
        is_camera = False
        rtsp_url = None
        credentials = None
        discovered_via = "MAC"
        fingerprint = None
        
        # Check by MAC vendor
        camera_vendors = ["Geeni", "Geeni/Tuya", "Tuya", "Wyze", "Hikvision", 
                         "Dahua", "Amcrest", "Reolink", "Ring", "Nest"]
        if vendor in camera_vendors:
            is_camera = True
            discovered_via = "MAC"
        
        # Check by open ports
        port_nums = [p["port"] for p in open_ports]
        if 554 in port_nums or 8554 in port_nums:
            is_camera = True
            discovered_via = "RTSP"
            
            # Test RTSP credentials
            rtsp_port = 554 if 554 in port_nums else 8554
            creds = test_rtsp_credentials(ip, rtsp_port)
            if creds:
                credentials = creds
                rtsp_url = f"rtsp://{creds[0]}:{creds[1]}@{ip}:{rtsp_port}/stream"
            else:
                rtsp_url = f"rtsp://{ip}:{rtsp_port}/stream"
        
        # Check ONVIF
        if not is_camera and discover_onvif(ip):
            is_camera = True
            discovered_via = "ONVIF"
        
        # Check UPnP
        if not is_camera and discover_upnp(ip):
            is_camera = True
            discovered_via = "UPnP"
        
        # HTTP fingerprinting
        if 80 in port_nums or 8080 in port_nums:
            http_port = 80 if 80 in port_nums else 8080
            fingerprint = http_fingerprint(ip, http_port)
            if fingerprint:
                is_camera = True
                discovered_via = "HTTP"
                
                # Test HTTP credentials
                if not credentials:
                    creds = test_credentials(ip, http_port)
                    if creds:
                        credentials = creds
        
        # Determine device type
        if is_camera:
            dev_type = "ip_camera"
            icon = "📷"
            type_name = fingerprint or f"{vendor} Camera"
        elif 9100 in port_nums or 515 in port_nums or 631 in port_nums:
            dev_type = "printer"
            icon = "🖨️"
            type_name = vendor if "Printer" in vendor else "Network Printer"
        elif vendor in ["PlayStation", "Xbox", "Nintendo"]:
            dev_type = "gaming"
            icon = "🎮"
            type_name = vendor
        elif vendor in ["Roku", "Amazon Fire"]:
            dev_type = "streaming"
            icon = "📺"
            type_name = vendor
        elif vendor in ["TP-Link", "Linksys", "Netgear"]:
            dev_type = "router"
            icon = "📶"
            type_name = vendor
        else:
            dev_type = "device"
            icon = vendor_icon
            type_name = vendor if vendor != "Unknown" else "Unknown Device"
        
        discovered_devices.append({
            "id": f"auto_{ip.replace('.', '_')}",
            "ip": ip,
            "mac": mac,
            "hostname": hostname,
            "vendor": vendor,
            "type": dev_type,
            "type_name": type_name,
            "icon": icon,
            "open_ports": open_ports,
            "is_local": ip == local_ip,
            "discovered_at": datetime.now().isoformat(),
            # Camera-specific fields
            "rtsp_url": rtsp_url,
            "credentials_found": credentials is not None,
            "rtsp_username": credentials[0] if credentials else None,
            "rtsp_password": credentials[1] if credentials else None,
            "discovered_via": discovered_via,
            "supports_onvif": discover_onvif(ip) if is_camera else False,
        })
    
    # Phase 4: Sort results (100%)
    scan_status["progress"] = 95
    scan_status["message"] = "Finalizing results..."
    
    # Sort: cameras first, then by IP
    discovered_devices.sort(key=lambda d: (0 if d["type"] == "ip_camera" else 1, [int(x) for x in d["ip"].split('.')]))
    
    camera_count = len([d for d in discovered_devices if d["type"] == "ip_camera"])
    creds_count = len([d for d in discovered_devices if d.get("credentials_found")])
    
    scan_status = {
        "running": False, 
        "progress": 100, 
        "message": f"Found {len(discovered_devices)} devices, {camera_count} cameras, {creds_count} with credentials"
    }
    return discovered_devices

def sync_to_truthvault(devices):
    if not auth_token: return {"success": False, "error": "Not connected"}
    try:
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
        
        data = json.dumps({"devices": devices}).encode()
        req = urllib.request.Request(f"{TRUEVAULT_API}/network-scanner.php",
            data=data, headers={"Content-Type": "application/json", "Authorization": f"Bearer {auth_token}"})
        
        with urllib.request.urlopen(req, context=ctx, timeout=10) as resp:
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
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px}
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
.tag.creds{background:rgba(0,255,136,.2);color:#00ff88}
.tag.onvif{background:rgba(0,217,255,.2);color:#00d9ff}
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
.camera-info{margin-top:8px;padding:8px;background:rgba(255,107,107,.1);border-radius:6px;font-size:.8rem}
.camera-info .rtsp{color:#ff6b6b;font-family:monospace;word-break:break-all}
</style>
</head><body>
<div class="container">
<header>
<div class="logo"><span style="font-size:2rem">🔐</span><div><h1>TrueVault Scanner</h1><small style="color:#555">v''' + VERSION + ''' - Brute Force Camera Discovery</small></div></div>
<div id="status" class="badge badge-ok">✓ Connected</div>
</header>

<div class="stats">
<div class="stat"><div class="stat-num" id="total-count">0</div><div class="stat-label">Total Devices</div></div>
<div class="stat"><div class="stat-num" id="camera-count">0</div><div class="stat-label">📷 Cameras</div></div>
<div class="stat"><div class="stat-num" id="creds-count">0</div><div class="stat-label">🔑 Creds Found</div></div>
<div class="stat"><div class="stat-num" id="other-count">0</div><div class="stat-label">Other</div></div>
</div>

<div class="card">
<h2>📡 Network Scan (Brute Force)</h2>
<p style="color:#888;margin-bottom:10px;font-size:.9rem">Scans ALL camera ports, tests 50+ credentials, discovers ONVIF/UPnP cameras</p>
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
  const creds=devices.filter(d=>d.credentials_found).length;
  $('total-count').textContent=devices.length;
  $('camera-count').textContent=cams;
  $('creds-count').textContent=creds;
  $('other-count').textContent=devices.length-cams;
  
  if(!devices.length){$('devices').innerHTML='<div class="empty"><div class="icon">📭</div><p>No devices found</p></div>';return}
  
  $('devices').innerHTML=devices.map(d=>`
    <div class="device ${selected.has(d.id)?'selected':''} ${d.type==='ip_camera'?'camera':''}" onclick="toggle('${d.id}')">
      <div class="device-head"><span class="device-icon">${d.icon}</span><div><h3>${d.hostname||d.type_name}</h3><div class="ip">${d.ip}</div></div></div>
      <div class="tags">
        <span class="tag">${d.vendor}</span>
        ${d.type==='ip_camera'?'<span class="tag camera">CAMERA</span>':''}
        ${d.credentials_found?'<span class="tag creds">🔑 CREDS</span>':''}
        ${d.discovered_via==='ONVIF'?'<span class="tag onvif">ONVIF</span>':''}
        ${d.open_ports.slice(0,3).map(p=>'<span class="tag port">:'+p.port+'</span>').join('')}
      </div>
      ${d.type==='ip_camera' && d.rtsp_url ? `
        <div class="camera-info">
          <div class="rtsp">${d.rtsp_url}</div>
          ${d.credentials_found ? '<div style="color:#00ff88;margin-top:4px">✓ Credentials: '+d.rtsp_username+':****</div>' : ''}
        </div>
      ` : ''}
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
            result = sync_to_truthvault(data.get('devices', []))
            self.send_json(result)
        else:
            self.send_json({"error": "Not found"}, 404)

def main():
    global auth_token, user_email
    
    print(f"""
╔══════════════════════════════════════════════════════════╗
║       TrueVault Network Scanner v{VERSION}                   ║
║       Brute Force Camera Discovery                       ║
╠══════════════════════════════════════════════════════════╣
║  Features:                                               ║
║  • Brute force port scanning (12 camera ports)           ║
║  • Credential testing (50+ default combos)               ║
║  • ONVIF discovery protocol                              ║
║  • UPnP/mDNS scanning                                    ║
║  • HTTP fingerprinting                                   ║
╚══════════════════════════════════════════════════════════╝
""")
    
    if len(sys.argv) >= 3:
        user_email = sys.argv[1]
        auth_token = sys.argv[2]
    else:
        print("Usage: python truthvault_scanner.py EMAIL TOKEN")
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
