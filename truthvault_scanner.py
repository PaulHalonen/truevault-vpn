#!/usr/bin/env python3
"""
TruthVault Network Scanner v3.0 - BRUTE FORCE EDITION
Task 6A.1: Advanced camera discovery with brute force scanning

FEATURES:
- Brute force port scanning on ALL devices
- ONVIF camera discovery protocol
- UPnP device discovery
- mDNS/Bonjour service detection
- HTTP fingerprinting for camera web interfaces
- Safe credential testing (non-destructive)
- Auto-connects to your TruthVault account

INSTALL:
  pip install flask requests zeroconf

RUN:
  python truthvault_scanner.py YOUR_EMAIL YOUR_TOKEN
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
import struct
import xml.etree.ElementTree as ET
from datetime import datetime
from http.server import HTTPServer, SimpleHTTPRequestHandler
from urllib.parse import parse_qs, urlparse
import urllib.request
import ssl
from concurrent.futures import ThreadPoolExecutor, as_completed

# ============== CONFIGURATION ==============
TRUTHVAULT_API = "https://vpn.the-truth-publishing.com/api"
LOCAL_PORT = 8888
VERSION = "3.0.0"
SCAN_TIMEOUT = 0.5
MAX_THREADS = 50

# ============== BRUTE FORCE PORT LIST ==============
CAMERA_PORTS = {
    554: "RTSP",           # Standard RTSP
    8554: "RTSP-ALT",      # Alternative RTSP
    80: "HTTP",            # Web interface
    443: "HTTPS",          # Secure web
    8080: "HTTP-ALT",      # Alternative HTTP
    8000: "HTTP-ALT2",     # Another alternative
    8001: "HTTP-ALT3",     # Yet another
    8008: "HTTP-ALT4",     # Google devices
    8443: "HTTPS-ALT",     # Alternative HTTPS
    37777: "Dahua",        # Dahua cameras
    34567: "Hikvision",    # Hikvision cameras
    9000: "Cameras",       # Generic camera port
    1935: "RTMP",          # Streaming
    5000: "ONVIF",         # ONVIF discovery
    3702: "WS-Discovery",  # Web Services Discovery
    6668: "Tuya",          # Tuya local protocol
    7474: "Ezviz",         # Ezviz cameras
    5353: "mDNS",          # Multicast DNS
    1900: "UPnP",          # UPnP discovery
    49152: "UPnP-ALT",     # UPnP alternative
    9100: "Printer",       # Printer raw
    515: "LPR",            # Line Printer
    631: "IPP",            # Internet Printing Protocol
}

# ============== DEFAULT CREDENTIALS ==============
COMMON_CREDENTIALS = [
    ("admin", "admin"),
    ("admin", ""),
    ("admin", "12345"),
    ("admin", "123456"),
    ("admin", "password"),
    ("admin", "admin123"),
    ("admin", "1234"),
    ("root", "root"),
    ("root", ""),
    ("root", "12345"),
    ("root", "pass"),
    ("root", "admin"),
    ("user", "user"),
    ("user", ""),
    ("guest", "guest"),
    ("default", "default"),
    # Hikvision defaults
    ("admin", "Hikvision"),
    ("admin", "hik12345"),
    ("admin", "12345"),
    # Dahua defaults
    ("admin", "admin"),
    ("admin", ""),
    ("888888", "888888"),
    ("666666", "666666"),
    # Reolink defaults
    ("admin", ""),
    # Amcrest defaults
    ("admin", "admin"),
    # Foscam defaults
    ("admin", ""),
    ("admin", "admin"),
    # TP-Link defaults
    ("admin", "admin"),
    # D-Link defaults
    ("admin", ""),
    ("admin", "admin"),
    # Netgear defaults
    ("admin", "password"),
    # Ubiquiti defaults
    ("ubnt", "ubnt"),
    # Generic
    ("administrator", "administrator"),
    ("supervisor", "supervisor"),
    ("service", "service"),
]

# ============== RTSP URL PATTERNS ==============
RTSP_PATHS = [
    "/",
    "/live",
    "/stream",
    "/stream1",
    "/stream0",
    "/cam/realmonitor",
    "/h264_stream",
    "/live/ch0",
    "/live/ch00_0",
    "/live/main",
    "/live/sub",
    "/ch0_0.h264",
    "/ch1_0.h264",
    "/11",
    "/12",
    "/Streaming/Channels/1",
    "/Streaming/Channels/101",
    "/Streaming/Channels/102",
    "/onvif1",
    "/onvif2",
    "/media/video1",
    "/videoMain",
    "/video.mjpg",
    "/mjpg/video.mjpg",
    "/img/video.mjpeg",
    "/cgi-bin/mjpg/video.cgi",
]

# ============== HTTP CAMERA SIGNATURES ==============
HTTP_SIGNATURES = {
    "hikvision": ["Hikvision", "HIKVISION", "hikvision", "webComponents", "isSecureMode"],
    "dahua": ["Dahua", "DAHUA", "DH_", "WebLoginEx", "loginEx.js"],
    "reolink": ["Reolink", "reolink", "REOLINK"],
    "amcrest": ["Amcrest", "AMCREST", "amcrest"],
    "foscam": ["Foscam", "FOSCAM", "foscam", "IPCamera"],
    "axis": ["AXIS", "Axis", "axis-cgi"],
    "uniview": ["Uniview", "UNIVIEW", "UNV"],
    "geeni": ["Geeni", "GEENI", "Tuya", "TUYA"],
    "wyze": ["Wyze", "WYZE", "wyze"],
    "ring": ["Ring", "RING", "ring.com"],
    "nest": ["Nest", "NEST", "nest.com", "google"],
    "tp-link": ["TP-LINK", "TP-Link", "tplink", "TAPO", "Tapo"],
    "ezviz": ["EZVIZ", "Ezviz", "ezviz"],
}

# ============== MAC VENDOR DATABASE ==============
MAC_VENDORS = {
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
    "18:69:D8": ("Tuya", "📷"),
    "00:1D:54": ("Tuya", "📷"),
    # Wyze
    "2C:AA:8E": ("Wyze", "📷"),
    "D0:3F:27": ("Wyze", "📷"),
    "7C:78:B2": ("Wyze", "📷"),
    "A4:DA:22": ("Wyze", "📷"),
    # Hikvision
    "D8:EB:46": ("Hikvision", "📷"),
    "C0:56:E3": ("Hikvision", "📷"),
    "44:19:B6": ("Hikvision", "📷"),
    "A4:14:37": ("Hikvision", "📷"),
    "54:C4:15": ("Hikvision", "📷"),
    "28:57:BE": ("Hikvision", "📷"),
    "BC:AD:28": ("Hikvision", "📷"),
    "E0:50:8B": ("Hikvision", "📷"),
    # Dahua
    "00:09:B0": ("Dahua", "📷"),
    "3C:EF:8C": ("Dahua", "📷"),
    "4C:11:BF": ("Dahua", "📷"),
    "90:02:A9": ("Dahua", "📷"),
    # Amcrest  
    "78:A5:DD": ("Amcrest", "📷"),
    "9C:8E:CD": ("Amcrest", "📷"),
    # Reolink
    "00:62:6E": ("Reolink", "📷"),
    "EC:71:DB": ("Reolink", "📷"),
    "B4:6D:C2": ("Reolink", "📷"),
    # Ring
    "00:D0:2D": ("Ring", "🚪"),
    "50:32:75": ("Ring", "🚪"),
    "34:3E:A4": ("Ring", "🚪"),
    "90:48:9A": ("Ring", "🚪"),
    # Nest/Google
    "18:B4:30": ("Nest", "🏠"),
    "64:16:66": ("Nest", "🏠"),
    "3C:5A:B4": ("Google", "📱"),
    "54:60:09": ("Google", "📱"),
    "F4:F5:D8": ("Google", "📱"),
    # Amazon Echo/Fire
    "FC:A1:83": ("Amazon Echo", "🔊"),
    "74:C2:46": ("Amazon Echo", "🔊"),
    "84:D6:D0": ("Amazon Echo", "🔊"),
    "F0:27:2D": ("Amazon Echo", "🔊"),
    "00:FC:8B": ("Amazon Fire", "📺"),
    "44:65:0D": ("Amazon Fire", "📺"),
    # Roku
    "00:1D:D0": ("Roku", "📺"),
    "B8:3E:59": ("Roku", "📺"),
    "DC:3A:5E": ("Roku", "📺"),
    "D8:31:34": ("Roku", "📺"),
    # Apple
    "00:1A:79": ("Apple", "📱"),
    "00:1B:63": ("Apple", "📱"),
    "00:1C:B3": ("Apple", "📱"),
    "00:03:93": ("Apple", "📱"),
    "A4:83:E7": ("Apple", "📱"),
    "F0:99:BF": ("Apple", "📱"),
    # Samsung
    "00:15:99": ("Samsung", "📺"),
    "00:1A:8A": ("Samsung", "📺"),
    "00:21:4C": ("Samsung", "📺"),
    "94:63:D1": ("Samsung", "📱"),
    "CC:07:AB": ("Samsung", "📱"),
    # Gaming
    "00:04:20": ("PlayStation", "🎮"),
    "00:09:BF": ("PlayStation", "🎮"),
    "00:15:C1": ("PlayStation", "🎮"),
    "00:D9:D1": ("PlayStation", "🎮"),
    "28:3F:69": ("PlayStation", "🎮"),
    "7C:BB:8A": ("Nintendo", "🎮"),
    "00:1E:A9": ("Nintendo", "🎮"),
    "00:16:56": ("Nintendo", "🎮"),
    "E8:4E:CE": ("Nintendo", "🎮"),
    "98:41:5C": ("Nintendo", "🎮"),
    "00:50:F2": ("Xbox", "🎮"),
    "7C:1E:52": ("Xbox", "🎮"),
    "00:25:AE": ("Xbox", "🎮"),
    "60:45:BD": ("Xbox", "🎮"),
    # Printers
    "00:1E:0B": ("HP Printer", "🖨️"),
    "3C:A9:F4": ("HP Printer", "🖨️"),
    "00:17:A4": ("HP Printer", "🖨️"),
    "00:90:4C": ("Epson Printer", "🖨️"),
    "00:26:AB": ("Epson Printer", "🖨️"),
    "00:1E:8F": ("Canon Printer", "🖨️"),
    "00:1B:A9": ("Brother Printer", "🖨️"),
    # Routers
    "00:17:7C": ("TP-Link", "📶"),
    "14:CC:20": ("TP-Link", "📶"),
    "50:C7:BF": ("TP-Link", "📶"),
    "00:14:BF": ("Linksys", "📶"),
    "00:18:F8": ("Linksys", "📶"),
    "00:1F:33": ("Netgear", "📶"),
    "00:22:3F": ("Netgear", "📶"),
    "20:4E:7F": ("Netgear", "📶"),
    # Raspberry Pi
    "B8:27:EB": ("Raspberry Pi", "🖥️"),
    "DC:A6:32": ("Raspberry Pi", "🖥️"),
    "E4:5F:01": ("Raspberry Pi", "🖥️"),
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

# ============== BRUTE FORCE PORT SCANNING ==============
def check_port(ip, port, timeout=SCAN_TIMEOUT):
    """Check if a specific port is open"""
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(timeout)
        result = sock.connect_ex((ip, port))
        sock.close()
        return result == 0
    except:
        return False

def brute_force_scan(ip):
    """Scan all camera-related ports on a device"""
    open_ports = []
    for port, service in CAMERA_PORTS.items():
        if check_port(ip, port):
            open_ports.append({"port": port, "service": service})
    return open_ports

def scan_ports_threaded(ip, ports):
    """Threaded port scanning for speed"""
    open_ports = []
    with ThreadPoolExecutor(max_workers=20) as executor:
        futures = {executor.submit(check_port, ip, port): (port, service) 
                   for port, service in ports.items()}
        for future in as_completed(futures):
            port, service = futures[future]
            try:
                if future.result():
                    open_ports.append({"port": port, "service": service})
            except:
                pass
    return open_ports

# ============== ONVIF DISCOVERY ==============
def discover_onvif(timeout=3):
    """Discover ONVIF cameras using WS-Discovery"""
    discovered = []
    
    # WS-Discovery multicast address
    MULTICAST_IP = "239.255.255.250"
    MULTICAST_PORT = 3702
    
    # WS-Discovery probe message
    probe_message = """<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" 
               xmlns:wsa="http://schemas.xmlsoap.org/ws/2004/08/addressing" 
               xmlns:tns="http://schemas.xmlsoap.org/ws/2005/04/discovery">
    <soap:Header>
        <wsa:Action>http://schemas.xmlsoap.org/ws/2005/04/discovery/Probe</wsa:Action>
        <wsa:MessageID>urn:uuid:""" + str(time.time()) + """</wsa:MessageID>
        <wsa:To>urn:schemas-xmlsoap-org:ws:2005:04:discovery</wsa:To>
    </soap:Header>
    <soap:Body>
        <tns:Probe>
            <tns:Types>tds:Device</tns:Types>
        </tns:Probe>
    </soap:Body>
</soap:Envelope>"""
    
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM, socket.IPPROTO_UDP)
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        sock.settimeout(timeout)
        
        # Send probe
        sock.sendto(probe_message.encode(), (MULTICAST_IP, MULTICAST_PORT))
        
        # Collect responses
        start_time = time.time()
        while time.time() - start_time < timeout:
            try:
                data, addr = sock.recvfrom(65535)
                ip = addr[0]
                
                # Parse response for device info
                response = data.decode('utf-8', errors='ignore')
                
                # Extract XAddrs (ONVIF service URL)
                xaddr_match = re.search(r'<[\w:]*XAddrs[^>]*>([^<]+)', response)
                xaddr = xaddr_match.group(1) if xaddr_match else None
                
                discovered.append({
                    "ip": ip,
                    "protocol": "onvif",
                    "xaddr": xaddr,
                    "raw_response": response[:500]
                })
            except socket.timeout:
                break
            except:
                continue
        
        sock.close()
    except Exception as e:
        print(f"ONVIF discovery error: {e}")
    
    return discovered

# ============== UPnP DISCOVERY ==============
def discover_upnp(timeout=3):
    """Discover UPnP devices"""
    discovered = []
    
    SSDP_ADDR = "239.255.255.250"
    SSDP_PORT = 1900
    
    # M-SEARCH message for all devices
    search_message = """M-SEARCH * HTTP/1.1\r
Host: 239.255.255.250:1900\r
Man: "ssdp:discover"\r
ST: ssdp:all\r
MX: 2\r
\r
"""
    
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM, socket.IPPROTO_UDP)
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        sock.settimeout(timeout)
        
        sock.sendto(search_message.encode(), (SSDP_ADDR, SSDP_PORT))
        
        start_time = time.time()
        while time.time() - start_time < timeout:
            try:
                data, addr = sock.recvfrom(65535)
                ip = addr[0]
                
                response = data.decode('utf-8', errors='ignore')
                
                # Parse response headers
                headers = {}
                for line in response.split('\r\n'):
                    if ':' in line:
                        key, value = line.split(':', 1)
                        headers[key.upper().strip()] = value.strip()
                
                discovered.append({
                    "ip": ip,
                    "protocol": "upnp",
                    "server": headers.get("SERVER", ""),
                    "location": headers.get("LOCATION", ""),
                    "st": headers.get("ST", ""),
                    "usn": headers.get("USN", "")
                })
            except socket.timeout:
                break
            except:
                continue
        
        sock.close()
    except Exception as e:
        print(f"UPnP discovery error: {e}")
    
    return discovered

# ============== mDNS DISCOVERY ==============
def discover_mdns(timeout=3):
    """Discover mDNS/Bonjour services"""
    discovered = []
    
    # Try to use zeroconf if available
    try:
        from zeroconf import Zeroconf, ServiceBrowser
        
        class MDNSListener:
            def __init__(self):
                self.devices = []
            
            def add_service(self, zc, type_, name):
                info = zc.get_service_info(type_, name)
                if info:
                    self.devices.append({
                        "name": name,
                        "type": type_,
                        "ip": socket.inet_ntoa(info.addresses[0]) if info.addresses else None,
                        "port": info.port,
                        "properties": dict(info.properties) if info.properties else {}
                    })
            
            def remove_service(self, zc, type_, name):
                pass
            
            def update_service(self, zc, type_, name):
                pass
        
        zc = Zeroconf()
        listener = MDNSListener()
        
        # Search for common camera services
        services = [
            "_rtsp._tcp.local.",
            "_http._tcp.local.",
            "_https._tcp.local.",
            "_onvif._tcp.local.",
            "_camera._tcp.local.",
            "_ipp._tcp.local.",
        ]
        
        browsers = []
        for service in services:
            try:
                browser = ServiceBrowser(zc, service, listener)
                browsers.append(browser)
            except:
                pass
        
        time.sleep(timeout)
        
        discovered = listener.devices
        zc.close()
        
    except ImportError:
        # Fallback: simple mDNS query
        pass
    
    return discovered

# ============== HTTP FINGERPRINTING ==============
def fingerprint_http(ip, port=80, timeout=2):
    """Identify camera by HTTP response"""
    try:
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
        
        protocol = "https" if port in [443, 8443] else "http"
        url = f"{protocol}://{ip}:{port}/"
        
        req = urllib.request.Request(url, headers={
            'User-Agent': 'Mozilla/5.0 TruthVault Scanner'
        })
        
        with urllib.request.urlopen(req, context=ctx, timeout=timeout) as resp:
            content = resp.read(8192).decode('utf-8', errors='ignore')
            headers = dict(resp.getheaders())
            
            # Check signatures
            for brand, signatures in HTTP_SIGNATURES.items():
                for sig in signatures:
                    if sig in content or sig in str(headers):
                        return {
                            "brand": brand,
                            "port": port,
                            "server": headers.get("Server", ""),
                            "content_type": headers.get("Content-Type", "")
                        }
            
            # Generic camera detection
            camera_keywords = ["camera", "stream", "video", "live", "rtsp", "onvif", "ptz"]
            for keyword in camera_keywords:
                if keyword.lower() in content.lower():
                    return {
                        "brand": "generic_camera",
                        "port": port,
                        "server": headers.get("Server", ""),
                        "keyword": keyword
                    }
    except:
        pass
    
    return None

# ============== CREDENTIAL TESTING ==============
def test_rtsp_credentials(ip, port=554, timeout=2):
    """Test RTSP credentials (safe, read-only)"""
    working_creds = []
    
    for username, password in COMMON_CREDENTIALS[:10]:  # Limit to top 10 to be safe
        for path in RTSP_PATHS[:5]:  # Limit paths
            try:
                sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
                sock.settimeout(timeout)
                sock.connect((ip, port))
                
                # Build RTSP OPTIONS request (read-only, safe)
                if password:
                    auth = f"{username}:{password}"
                else:
                    auth = username
                
                url = f"rtsp://{auth}@{ip}:{port}{path}"
                request = f"OPTIONS {url} RTSP/1.0\r\nCSeq: 1\r\n\r\n"
                
                sock.send(request.encode())
                response = sock.recv(1024).decode('utf-8', errors='ignore')
                sock.close()
                
                if "200 OK" in response:
                    working_creds.append({
                        "username": username,
                        "password": password,
                        "path": path,
                        "url": f"rtsp://{ip}:{port}{path}"
                    })
                    return working_creds  # Return first working credential
                    
            except:
                pass
    
    return working_creds

def test_http_credentials(ip, port=80, timeout=2):
    """Test HTTP credentials (safe, read-only)"""
    working_creds = []
    
    for username, password in COMMON_CREDENTIALS[:10]:
        try:
            ctx = ssl.create_default_context()
            ctx.check_hostname = False
            ctx.verify_mode = ssl.CERT_NONE
            
            protocol = "https" if port in [443, 8443] else "http"
            url = f"{protocol}://{ip}:{port}/"
            
            # Create password manager
            password_mgr = urllib.request.HTTPPasswordMgrWithDefaultRealm()
            password_mgr.add_password(None, url, username, password)
            auth_handler = urllib.request.HTTPBasicAuthHandler(password_mgr)
            opener = urllib.request.build_opener(auth_handler, 
                                                  urllib.request.HTTPSHandler(context=ctx))
            
            req = urllib.request.Request(url)
            with opener.open(req, timeout=timeout) as resp:
                if resp.status == 200:
                    working_creds.append({
                        "username": username,
                        "password": password,
                        "url": url
                    })
                    return working_creds
        except urllib.error.HTTPError as e:
            if e.code != 401:  # Not auth error
                pass
        except:
            pass
    
    return working_creds

# ============== PING SWEEP ==============
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

# ============== DEVICE TYPE DETECTION ==============
def determine_type(hostname, vendor, icon, ports, http_info, rtsp_creds):
    port_nums = [p["port"] for p in ports]
    hn = (hostname or "").lower()
    vn = (vendor or "").lower()
    
    # Camera detection priority
    camera_vendors = ["geeni", "tuya", "wyze", "hikvision", "dahua", "amcrest", 
                      "reolink", "foscam", "axis", "uniview", "ring", "nest", "ezviz"]
    
    # Check HTTP fingerprint first
    if http_info:
        brand = http_info.get("brand", "")
        if brand in camera_vendors or brand == "generic_camera":
            return ("ip_camera", "📷", f"{brand.title()} Camera")
    
    # Check RTSP credentials found
    if rtsp_creds:
        return ("ip_camera", "📷", "IP Camera (RTSP)")
    
    # Check vendor
    for cam_vendor in camera_vendors:
        if cam_vendor in vn:
            return ("ip_camera", "📷", f"{vendor} Camera")
    
    # Check ports
    if 554 in port_nums or 8554 in port_nums:
        return ("ip_camera", "📷", "IP Camera (RTSP Port)")
    
    if 37777 in port_nums:
        return ("ip_camera", "📷", "Dahua Camera")
    
    if 34567 in port_nums:
        return ("ip_camera", "📷", "Hikvision Camera")
    
    if 6668 in port_nums:
        return ("ip_camera", "📷", "Tuya/Geeni Camera")
    
    # Check hostname
    if "camera" in hn or "cam" in hn or "ipc" in hn:
        return ("ip_camera", "📷", "IP Camera")
    
    # Printer detection
    if 9100 in port_nums or 515 in port_nums or 631 in port_nums:
        return ("printer", "🖨️", "Network Printer")
    if "printer" in vn or "print" in hn:
        return ("printer", "🖨️", vendor)
    
    # Media servers
    if 32400 in port_nums: return ("plex", "🎬", "Plex Server")
    if 8096 in port_nums: return ("jellyfin", "🎬", "Jellyfin Server")
    
    # Gaming
    if vendor in ["PlayStation", "Xbox", "Nintendo"]: return ("gaming", "🎮", vendor)
    
    # Smart home
    if vendor in ["Nest", "Ring", "Amazon Echo"]: return ("smart_home", "🏠", vendor)
    
    # Streaming
    if vendor in ["Roku", "Amazon Fire"]: return ("streaming", "📺", vendor)
    
    # Router
    if vendor in ["TP-Link", "Linksys", "Netgear"]: return ("router", "📶", vendor)
    
    # Computer
    if 22 in port_nums or 3389 in port_nums or 5900 in port_nums: 
        return ("computer", "💻", "Computer")
    
    # Server
    if 80 in port_nums or 443 in port_nums: 
        return ("server", "🖥️", "Server/Device")
    
    if icon != "❓": return ("device", icon, vendor)
    return ("unknown", "❓", "Unknown Device")

# ============== MAIN SCAN FUNCTION ==============
def scan_network():
    global discovered_devices, scan_status
    scan_status = {"running": True, "progress": 0, "message": "Starting advanced scan..."}
    discovered_devices = []
    
    network = get_network_range()
    local_ip = get_local_ip()
    
    # Phase 1: ONVIF Discovery (5%)
    scan_status["message"] = "Running ONVIF discovery..."
    scan_status["progress"] = 2
    onvif_devices = discover_onvif(timeout=2)
    print(f"ONVIF found: {len(onvif_devices)} devices")
    
    # Phase 2: UPnP Discovery (10%)
    scan_status["message"] = "Running UPnP discovery..."
    scan_status["progress"] = 5
    upnp_devices = discover_upnp(timeout=2)
    print(f"UPnP found: {len(upnp_devices)} devices")
    
    # Phase 3: mDNS Discovery (15%)
    scan_status["message"] = "Running mDNS discovery..."
    scan_status["progress"] = 10
    mdns_devices = discover_mdns(timeout=2)
    print(f"mDNS found: {len(mdns_devices)} devices")
    
    # Phase 4: Ping sweep (40%)
    scan_status["message"] = f"Pinging {network}.0/24..."
    scan_status["progress"] = 15
    
    ping_results = []
    threads = []
    for i in range(1, 255):
        t = threading.Thread(target=ping_host, args=(f"{network}.{i}", ping_results))
        t.start()
        threads.append(t)
        if i % 50 == 0:
            scan_status["progress"] = 15 + int((i / 255) * 25)
    
    for t in threads: t.join(timeout=3)
    
    # Phase 5: Get ARP table (45%)
    scan_status["message"] = "Reading ARP table..."
    scan_status["progress"] = 40
    arp = get_arp_table()
    
    # Add ONVIF/UPnP discovered IPs to ARP if not present
    for dev in onvif_devices + upnp_devices:
        ip = dev.get("ip")
        if ip and ip not in arp:
            arp[ip] = "00:00:00:00:00:00"  # Placeholder MAC
    
    # Phase 6: Analyze each device (90%)
    total = len(arp)
    for idx, (ip, mac) in enumerate(arp.items()):
        progress = 45 + int((idx / max(total, 1)) * 45)
        scan_status["progress"] = progress
        scan_status["message"] = f"Analyzing {ip} ({idx+1}/{total})..."
        
        vendor, vendor_icon = get_mac_info(mac)
        hostname = get_hostname(ip)
        
        # Brute force port scan
        ports = scan_ports_threaded(ip, CAMERA_PORTS)
        
        # HTTP fingerprinting
        http_info = None
        for port_info in ports:
            if port_info["service"] in ["HTTP", "HTTP-ALT", "HTTP-ALT2", "HTTP-ALT3", "HTTPS", "HTTPS-ALT"]:
                http_info = fingerprint_http(ip, port_info["port"])
                if http_info:
                    break
        
        # RTSP credential testing (only if RTSP port found)
        rtsp_creds = []
        for port_info in ports:
            if port_info["service"] in ["RTSP", "RTSP-ALT"]:
                rtsp_creds = test_rtsp_credentials(ip, port_info["port"])
                if rtsp_creds:
                    break
        
        # Determine device type
        dev_type, icon, type_name = determine_type(hostname, vendor, vendor_icon, ports, http_info, rtsp_creds)
        
        # Check if found via discovery protocols
        discovery_protocol = None
        for dev in onvif_devices:
            if dev.get("ip") == ip:
                discovery_protocol = "onvif"
                if dev_type != "ip_camera":
                    dev_type, icon, type_name = "ip_camera", "📷", "ONVIF Camera"
                break
        
        discovered_devices.append({
            "id": f"auto_{ip.replace('.', '_')}",
            "ip": ip,
            "mac": mac,
            "hostname": hostname,
            "vendor": vendor,
            "type": dev_type,
            "type_name": type_name,
            "icon": icon,
            "open_ports": ports,
            "http_fingerprint": http_info,
            "rtsp_credentials": rtsp_creds,
            "discovery_protocol": discovery_protocol,
            "is_local": ip == local_ip,
            "discovered_at": datetime.now().isoformat()
        })
    
    # Sort by IP
    discovered_devices.sort(key=lambda d: [int(x) for x in d["ip"].split('.')])
    
    # Count cameras
    camera_count = len([d for d in discovered_devices if d["type"] == "ip_camera"])
    
    scan_status = {
        "running": False, 
        "progress": 100, 
        "message": f"Found {len(discovered_devices)} devices ({camera_count} cameras)"
    }
    
    return discovered_devices

# ============== SYNC TO TRUTHVAULT ==============
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

# ============== WEB SERVER ==============
HTML = '''<!DOCTYPE html>
<html><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>TruthVault Network Scanner v3.0</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:linear-gradient(135deg,#0f0f1a,#1a1a2e);color:#fff;min-height:100vh;padding:20px}
.container{max-width:1200px;margin:0 auto}
header{display:flex;align-items:center;justify-content:space-between;margin-bottom:25px;flex-wrap:wrap;gap:15px}
.logo{display:flex;align-items:center;gap:12px}
.logo h1{font-size:1.6rem;background:linear-gradient(90deg,#00d9ff,#00ff88);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.badge{padding:6px 14px;border-radius:20px;font-size:.85rem;font-weight:600}
.badge-ok{background:rgba(0,255,136,.15);color:#00ff88;border:1px solid #00ff88}
.badge-no{background:rgba(255,100,100,.15);color:#ff6464;border:1px solid #ff6464}
.badge-brute{background:rgba(255,165,0,.15);color:#ffa500;border:1px solid #ffa500}
.card{background:rgba(255,255,255,.04);border-radius:14px;padding:18px;margin-bottom:18px;border:1px solid rgba(255,255,255,.08)}
.card h2{font-size:1.15rem;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.btn{padding:10px 20px;border:none;border-radius:8px;font-size:.95rem;font-weight:600;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:6px}
.btn-primary{background:linear-gradient(90deg,#00d9ff,#00ff88);color:#0f0f1a}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 4px 15px rgba(0,217,255,.3)}
.btn-primary:disabled{opacity:.4;cursor:not-allowed;transform:none}
.btn-secondary{background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.15)}
.btn-warning{background:rgba(255,165,0,.15);color:#ffa500;border:1px solid rgba(255,165,0,.4)}
.progress{height:6px;background:rgba(255,255,255,.1);border-radius:3px;overflow:hidden;margin:12px 0}
.progress-bar{height:100%;background:linear-gradient(90deg,#00d9ff,#00ff88);transition:width .3s}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px}
.device{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:14px;cursor:pointer;transition:.2s}
.device:hover{background:rgba(255,255,255,.07);border-color:#00d9ff}
.device.selected{border-color:#00ff88;background:rgba(0,255,136,.08)}
.device.camera{border-left:3px solid #ff6b6b}
.device.has-creds{border-left:3px solid #00ff88}
.device-head{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.device-icon{font-size:1.8rem}
.device h3{font-size:.95rem;color:#fff}
.device .ip{font-family:monospace;color:#00d9ff;font-size:.85rem}
.tags{display:flex;flex-wrap:wrap;gap:5px;margin-top:8px}
.tag{padding:3px 8px;background:rgba(255,255,255,.08);border-radius:4px;font-size:.7rem;color:#999}
.tag.port{color:#00ff88}
.tag.camera{background:rgba(255,107,107,.2);color:#ff6b6b}
.tag.creds{background:rgba(0,255,136,.2);color:#00ff88}
.tag.onvif{background:rgba(0,217,255,.2);color:#00d9ff}
.empty{text-align:center;padding:35px;color:#555}
.empty .icon{font-size:3.5rem;margin-bottom:12px}
.actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:15px}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:15px}
.stat{background:rgba(255,255,255,.03);border-radius:8px;padding:12px;text-align:center}
.stat-num{font-size:1.8rem;font-weight:700;background:linear-gradient(90deg,#00d9ff,#00ff88);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.stat-label{font-size:.75rem;color:#666;margin-top:2px}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
.scanning{animation:pulse 1s infinite}
.toast{position:fixed;bottom:20px;right:20px;padding:12px 18px;border-radius:8px;font-weight:600;z-index:1000;animation:slideIn .3s}
.toast.ok{background:#00c853}.toast.err{background:#ff5252}
@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}
.scan-phases{font-size:.8rem;color:#666;margin-top:8px}
.scan-phases span{margin-right:15px}
.scan-phases .active{color:#00d9ff}
.scan-phases .done{color:#00ff88}
</style>
</head><body>
<div class="container">
<header>
<div class="logo"><span style="font-size:2rem">🔍</span><div><h1>TruthVault Scanner</h1><small style="color:#555">v''' + VERSION + ''' BRUTE FORCE</small></div></div>
<div><span class="badge badge-brute">⚡ Advanced Mode</span> <span id="status" class="badge badge-ok">✔ Connected</span></div>
</header>

<div class="stats">
<div class="stat"><div class="stat-num" id="total-count">0</div><div class="stat-label">Total Devices</div></div>
<div class="stat"><div class="stat-num" id="camera-count">0</div><div class="stat-label">📷 Cameras</div></div>
<div class="stat"><div class="stat-num" id="creds-count">0</div><div class="stat-label">🔓 With Credentials</div></div>
<div class="stat"><div class="stat-num" id="onvif-count">0</div><div class="stat-label">📡 ONVIF</div></div>
<div class="stat"><div class="stat-num" id="other-count">0</div><div class="stat-label">Other</div></div>
</div>

<div class="card">
<h2>🔍 Advanced Network Scan</h2>
<div id="scan-msg" style="color:#888;margin-bottom:10px">Ready for brute force scan</div>
<div class="progress"><div id="prog" class="progress-bar" style="width:0%"></div></div>
<div class="scan-phases">
<span id="phase-onvif">📡 ONVIF</span>
<span id="phase-upnp">🔌 UPnP</span>
<span id="phase-mdns">📢 mDNS</span>
<span id="phase-ping">🏓 Ping</span>
<span id="phase-ports">🔓 Ports</span>
<span id="phase-creds">🔑 Creds</span>
</div>
<div style="margin-top:15px">
<button id="scan-btn" class="btn btn-primary" onclick="startScan()">⚡ Brute Force Scan</button>
</div>
</div>

<div class="card">
<h2>📱 Discovered Devices</h2>
<div id="devices" class="grid"><div class="empty"><div class="icon">🔍</div><p>Click "Brute Force Scan" to discover ALL devices</p></div></div>
<div class="actions">
<button class="btn btn-primary" onclick="syncSelected()" id="sync-btn" disabled>☁️ Sync to TruthVault</button>
<button class="btn btn-secondary" onclick="selectCameras()">📷 Select Cameras</button>
<button class="btn btn-secondary" onclick="selectWithCreds()">🔓 With Credentials</button>
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
  $('scan-btn').disabled=true;$('scan-btn').innerHTML='⚡ Scanning...';$('scan-btn').classList.add('scanning');
  resetPhases();
  await fetch('/scan',{method:'POST'});
  poll=setInterval(pollStatus,500);
}

function resetPhases(){
  ['onvif','upnp','mdns','ping','ports','creds'].forEach(p=>{
    $('phase-'+p).className='';
  });
}

function updatePhases(progress){
  const phases=[
    {id:'onvif',start:0,end:5},
    {id:'upnp',start:5,end:10},
    {id:'mdns',start:10,end:15},
    {id:'ping',start:15,end:40},
    {id:'ports',start:40,end:80},
    {id:'creds',start:80,end:100}
  ];
  phases.forEach(p=>{
    if(progress>=p.end) $('phase-'+p.id).className='done';
    else if(progress>=p.start) $('phase-'+p.id).className='active';
  });
}

async function pollStatus(){
  const r=await fetch('/status').then(r=>r.json());
  $('scan-msg').textContent=r.message;
  $('prog').style.width=r.progress+'%';
  updatePhases(r.progress);
  if(!r.running){
    clearInterval(poll);
    $('scan-btn').disabled=false;
    $('scan-btn').innerHTML='⚡ Brute Force Scan';
    $('scan-btn').classList.remove('scanning');
    devices=await fetch('/devices').then(r=>r.json());
    render();
  }
}

function render(){
  const cams=devices.filter(d=>d.type==='ip_camera');
  const withCreds=devices.filter(d=>d.rtsp_credentials&&d.rtsp_credentials.length>0);
  const onvif=devices.filter(d=>d.discovery_protocol==='onvif');
  $('total-count').textContent=devices.length;
  $('camera-count').textContent=cams.length;
  $('creds-count').textContent=withCreds.length;
  $('onvif-count').textContent=onvif.length;
  $('other-count').textContent=devices.length-cams.length;
  
  if(!devices.length){$('devices').innerHTML='<div class="empty"><div class="icon">🔭</div><p>No devices found</p></div>';return}
  
  $('devices').innerHTML=devices.map(d=>{
    const hasCreds=d.rtsp_credentials&&d.rtsp_credentials.length>0;
    const isOnvif=d.discovery_protocol==='onvif';
    return `
    <div class="device ${selected.has(d.id)?'selected':''} ${d.type==='ip_camera'?'camera':''} ${hasCreds?'has-creds':''}" onclick="toggle('${d.id}')">
      <div class="device-head"><span class="device-icon">${d.icon}</span><div><h3>${d.hostname||d.type_name}</h3><div class="ip">${d.ip}</div></div></div>
      <div class="tags">
        <span class="tag">${d.vendor}</span>
        ${d.type==='ip_camera'?'<span class="tag camera">CAMERA</span>':''}
        ${hasCreds?'<span class="tag creds">🔓 CREDS FOUND</span>':''}
        ${isOnvif?'<span class="tag onvif">ONVIF</span>':''}
        ${d.open_ports.slice(0,4).map(p=>'<span class="tag port">:'+p.port+'</span>').join('')}
      </div>
      ${hasCreds?'<div style="margin-top:8px;font-size:.75rem;color:#00ff88">RTSP: '+d.rtsp_credentials[0].url+'</div>':''}
    </div>
  `}).join('');
  $('sync-btn').disabled=selected.size===0;
}

function toggle(id){selected.has(id)?selected.delete(id):selected.add(id);render()}
function selectAll(){devices.forEach(d=>selected.add(d.id));render()}
function deselectAll(){selected.clear();render()}
function selectCameras(){selected.clear();devices.filter(d=>d.type==='ip_camera').forEach(d=>selected.add(d.id));render();toast('Selected '+selected.size+' cameras')}
function selectWithCreds(){selected.clear();devices.filter(d=>d.rtsp_credentials&&d.rtsp_credentials.length>0).forEach(d=>selected.add(d.id));render();toast('Selected '+selected.size+' devices with credentials')}

async function syncSelected(){
  const sel=devices.filter(d=>selected.has(d.id));
  if(!sel.length){toast('Select devices first',false);return}
  $('sync-btn').disabled=true;$('sync-btn').innerHTML='⏳ Syncing...';
  const r=await fetch('/sync',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({devices:sel})}).then(r=>r.json());
  $('sync-btn').disabled=false;$('sync-btn').innerHTML='☁️ Sync to TruthVault';
  if(r.success){toast('Synced '+sel.length+' devices!')}else{toast(r.error||'Sync failed',false)}
}

// Auto-scan on load
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
╔══════════════════════════════════════════════════════════════╗
║       TruthVault Network Scanner v{VERSION}                     ║
║       🔥 BRUTE FORCE EDITION 🔥                              ║
║       Discover ALL devices on your network                   ║
╠══════════════════════════════════════════════════════════════╣
║  Features:                                                   ║
║  • ONVIF camera discovery                                    ║
║  • UPnP device discovery                                     ║
║  • mDNS/Bonjour detection                                    ║
║  • Brute force port scanning                                 ║
║  • HTTP fingerprinting                                       ║
║  • Safe credential testing                                   ║
╚══════════════════════════════════════════════════════════════╝
""")
    
    # Get credentials from args or prompt
    if len(sys.argv) >= 3:
        user_email = sys.argv[1]
        auth_token = sys.argv[2]
    else:
        print("Usage: python truthvault_scanner.py EMAIL TOKEN")
        print("Or run without args for manual entry\n")
        user_email = input("TruthVault Email: ").strip()
        auth_token = input("Auth Token: ").strip()
    
    print(f"\n✔ User: {user_email}")
    print(f"✔ Starting scanner on http://localhost:{LOCAL_PORT}")
    print("\nOpening browser...")
    
    # Open browser
    webbrowser.open(f"http://localhost:{LOCAL_PORT}")
    
    # Start server
    server = HTTPServer(('0.0.0.0', LOCAL_PORT), Handler)
    print(f"\n🔍 Scanner ready! Press Ctrl+C to quit.\n")
    
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\n\nScanner stopped. Goodbye!")
        server.shutdown()

if __name__ == '__main__':
    main()
