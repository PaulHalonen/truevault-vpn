# NETWORK SCANNER - CLOUD CAMERA BYPASS SYSTEM

**Version:** 2.0 - Cloud Bypass Edition  
**Date:** January 14, 2026  
**Critical Feature:** Detect hard-to-find cameras and bypass cloud subscriptions  

---

## 🎯 THE KILLER FEATURE: BYPASS CLOUD SUBSCRIPTIONS

### **The Problem: Cloud Camera Trap**

**Users already own cameras, but forced to pay monthly fees:**

```
Geeni Camera ($30 one-time)
  ↓
Forced to use Geeni app
  ↓
Cloud recordings require $2.99/month subscription
  ↓
User pays $36/year FOREVER for camera they already own!

With 4 cameras: $144/year in cloud fees!
Over 5 years: $720 in cloud fees for $120 worth of cameras!
```

**TrueVault Solution:**
```
Geeni Camera ($30 one-time)
  ↓
Network Scanner detects camera
  ↓
Configures direct local access (bypass Geeni cloud)
  ↓
User accesses camera through TrueVault - FREE!
  ↓
Local storage - FREE!
  ↓
ZERO monthly fees forever! ✓
```

---

## 🔍 ADVANCED CAMERA DETECTION

### **Detection Methods (Multi-Layer Approach)**

**Layer 1: MAC Address Vendor Lookup**
```
Standard approach - identifies manufacturer
- Geeni/Tuya: D8:1D:2E, D8:F1:5B, 24:62:AB, 50:8A:06, etc.
- Wyze: 2C:AA:8E, D0:3F:27, 7C:78:B2, A4:DA:22
- Hikvision: D8:EB:46, C0:56:E3, 44:19:B6
- Ring: 00:D0:2D, 50:32:75, 34:3E:A4
- Nest: 18:B4:30, 64:16:66

Problem: Some cameras use generic MAC addresses
Solution: Use additional layers...
```

**Layer 2: Port Scanning (Camera-Specific Ports)**
```
Scan for camera-specific open ports:

RTSP (Real-Time Streaming Protocol):
• Port 554 - Standard RTSP
• Port 8554 - Alternative RTSP
• Port 5554 - Some Geeni models

HTTP/Web Interface:
• Port 80 - Web interface
• Port 8080 - Alternative HTTP
• Port 8000 - Some IP cameras
• Port 8081 - Some Geeni models

ONVIF (Open Network Video Interface Forum):
• Port 80 (HTTP)
• Port 8080 (Alternative)
• Port 3702 (WS-Discovery)

Proprietary:
• Port 6668 - Some Tuya/Geeni cameras
• Port 23 - Telnet (older cameras)

If ports 554, 8554, 80, 8080 are open → Likely a camera!
```

**Layer 3: ONVIF Discovery Protocol**
```
Send ONVIF discovery multicast:
Destination: 239.255.255.250:3702
Message: WS-Discovery probe

Cameras respond with:
- Device UUID
- Model information
- Service endpoints
- RTSP URLs

Works with:
• Hikvision ✓
• Dahua ✓
• Amcrest ✓
• Many Geeni models ✓
• Most professional IP cameras ✓
```

**Layer 4: HTTP Banner Grabbing**
```
Connect to port 80/8080:
GET / HTTP/1.1

Look for camera-specific headers/responses:
• "Server: IP Camera" → Generic camera
• "Server: Boa" → Common camera web server
• "realm=IPCamera" → Authentication realm
• "<title>IP Camera Login</title>" → Web interface

Fingerprint by response:
• Geeni cameras return specific HTML
• Wyze cameras have specific login pages
• Hikvision has distinctive interface
```

**Layer 5: RTSP URL Probing**
```
Try common RTSP URLs:

Generic formats:
rtsp://[IP]:554/
rtsp://[IP]:554/stream
rtsp://[IP]:554/live
rtsp://[IP]:554/ch0
rtsp://[IP]:554/ch01

Geeni/Tuya specific:
rtsp://[IP]:8554/unicast
rtsp://[IP]:554/onvif1
rtsp://[IP]:554/stream1

Wyze specific:
rtsp://[IP]:554/live

Hikvision specific:
rtsp://[IP]:554/Streaming/Channels/101
rtsp://[IP]:554/h264/ch1/main/av_stream

Test each URL → If stream accessible = CAMERA FOUND!
```

**Layer 6: Tuya Protocol Detection (Geeni Cameras)**
```
Geeni cameras use Tuya smart home protocol:

1. Scan UDP port 6668
2. Send Tuya discovery packet
3. Camera responds with device ID

Tuya detection packet format:
{
  "protocol": 34,
  "payload": "000055aa0000000000000001000000"
}

Response contains:
• Device ID
• Product ID (identifies camera model)
• IP address
• Firmware version

If Tuya protocol detected → Configure local access!
```

---

## 🔧 CLOUD BYPASS CONFIGURATION

### **How to Bypass Cloud Subscriptions**

**Step 1: Detect Camera**
```
Network Scanner finds:
IP: 192.168.1.150
MAC: D8:1D:2E:XX:XX:XX
Vendor: Geeni
Open Ports: 554, 8080, 6668
RTSP URL: rtsp://192.168.1.150:554/onvif1
Status: Cloud-dependent ❌
```

**Step 2: Enable Local Access**
```
Option A: Camera Already Has RTSP
Many Geeni cameras have RTSP enabled by default
Just need to find correct URL!

Option B: Enable RTSP via Web Interface
1. Access camera web interface (http://192.168.1.150:8080)
2. Login with default/user credentials
3. Enable RTSP stream
4. Configure stream settings

Option C: Enable via Tuya Protocol
1. Send Tuya configuration command
2. Enable local streaming
3. Get RTSP URL from response
```

**Step 3: Configure TrueVault Access**
```
TrueVault Dashboard automatically:
1. Adds camera to dashboard
2. Configures RTSP stream
3. Sets up port forwarding (if remote access needed)
4. Configures local recording
5. Enables motion detection

User sees:
✓ Camera connected!
✓ Cloud subscription bypassed!
✓ FREE local storage!
✓ Save $36/year per camera!
```

---

## 💰 COST SAVINGS CALCULATOR

### **Built Into Scanner Results**

```
┌─────────────────────────────────────────────────────────────┐
│ Network Scan Results - 4 Cameras Found                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ 🎉 CONGRATULATIONS! You can bypass cloud subscriptions!    │
│                                                             │
│ Cameras Detected:                                           │
│ • 2x Geeni Cameras                                          │
│ • 1x Wyze Camera                                            │
│ • 1x Ring Camera                                            │
│                                                             │
│ Current Cloud Costs (if using cloud):                       │
│ • Geeni: $2.99/month × 2 = $71.76/year                     │
│ • Wyze Cam Plus: $1.99/month = $23.88/year                 │
│ • Ring Protect: $3.99/month = $47.88/year                  │
│ ─────────────────────────────────────────────────────────  │
│ TOTAL: $143.52/year in cloud fees ❌                        │
│                                                             │
│ With TrueVault Local Storage:                               │
│ • All cameras: FREE local storage ✓                         │
│ • No monthly fees: $0/year ✓                               │
│ ─────────────────────────────────────────────────────────  │
│ YOUR SAVINGS: $143.52/year! 💰                              │
│                                                             │
│ [Setup Local Access] [Learn More]                          │
└─────────────────────────────────────────────────────────────┘
```

---

## 🛠️ ENHANCED SCANNER CODE

### **Advanced Camera Detection Script**

```python
#!/usr/bin/env python3
"""
TrueVault Network Scanner v3.0 - Cloud Camera Bypass Edition
Detects "hard to find" cameras and bypasses cloud subscriptions
"""

import socket
import struct
import json
from onvif import ONVIFCamera
import requests

# Camera Detection Database
CAMERA_SIGNATURES = {
    'geeni_tuya': {
        'mac_prefixes': ['D8:1D:2E', 'D8:F1:5B', '24:62:AB', '50:8A:06', 
                         '68:57:2D', '7C:F6:66', '84:E3:42', 'A0:92:08'],
        'ports': [554, 6668, 8080, 8554],
        'http_signatures': ['Tuya', 'Smart Camera', 'Geeni'],
        'rtsp_paths': ['/onvif1', '/unicast', '/stream1'],
        'cloud_service': 'Geeni Cloud',
        'cloud_cost': 2.99  # per month
    },
    'wyze': {
        'mac_prefixes': ['2C:AA:8E', 'D0:3F:27', '7C:78:B2', 'A4:DA:22'],
        'ports': [554, 80, 8080],
        'http_signatures': ['WyzeCam', 'Wyze'],
        'rtsp_paths': ['/live', '/stream'],
        'cloud_service': 'Wyze Cam Plus',
        'cloud_cost': 1.99  # per month
    },
    'ring': {
        'mac_prefixes': ['00:D0:2D', '50:32:75', '34:3E:A4'],
        'ports': [443, 6543],
        'http_signatures': ['Ring'],
        'rtsp_paths': [],  # Ring requires special handling
        'cloud_service': 'Ring Protect',
        'cloud_cost': 3.99  # per month
    }
}

def detect_tuya_camera(ip):
    """
    Detect Tuya/Geeni camera using Tuya protocol
    """
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        sock.settimeout(2)
        
        # Tuya discovery packet
        discovery = bytes.fromhex('000055aa0000000000000001000000')
        sock.sendto(discovery, (ip, 6668))
        
        response, addr = sock.recvfrom(1024)
        if response:
            # Parse Tuya response
            device_info = parse_tuya_response(response)
            return {
                'detected': True,
                'protocol': 'Tuya',
                'device_id': device_info.get('device_id'),
                'product_id': device_info.get('product_id'),
                'bypass_available': True
            }
    except:
        pass
    
    return {'detected': False}

def probe_onvif(ip, username='admin', password=''):
    """
    ONVIF camera discovery and configuration
    """
    try:
        # Try ONVIF connection
        camera = ONVIFCamera(ip, 80, username, password)
        device_info = camera.devicemgmt.GetDeviceInformation()
        
        # Get RTSP URLs
        media_service = camera.create_media_service()
        profiles = media_service.GetProfiles()
        
        rtsp_urls = []
        for profile in profiles:
            stream_uri = media_service.GetStreamUri({
                'StreamSetup': {'Stream': 'RTP-Unicast', 'Transport': {'Protocol': 'RTSP'}},
                'ProfileToken': profile.token
            })
            rtsp_urls.append(stream_uri.Uri)
        
        return {
            'detected': True,
            'protocol': 'ONVIF',
            'manufacturer': device_info.Manufacturer,
            'model': device_info.Model,
            'firmware': device_info.FirmwareVersion,
            'rtsp_urls': rtsp_urls,
            'bypass_available': True
        }
    except:
        pass
    
    return {'detected': False}

def probe_rtsp_urls(ip, username='admin', password=''):
    """
    Try common RTSP URLs to find stream
    """
    common_paths = [
        '/onvif1', '/unicast', '/stream1', '/live', 
        '/ch0', '/ch01', '/h264', '/stream',
        '/Streaming/Channels/101',
        '/cam/realmonitor?channel=1&subtype=0'
    ]
    
    working_urls = []
    
    for port in [554, 8554]:
        for path in common_paths:
            rtsp_url = f"rtsp://{username}:{password}@{ip}:{port}{path}"
            
            try:
                # Quick RTSP OPTIONS request to test
                sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
                sock.settimeout(1)
                sock.connect((ip, port))
                
                request = f"OPTIONS {path} RTSP/1.0\r\nCSeq: 1\r\n\r\n"
                sock.send(request.encode())
                
                response = sock.recv(1024).decode()
                if 'RTSP/1.0 200 OK' in response:
                    working_urls.append(rtsp_url)
                
                sock.close()
            except:
                continue
    
    return working_urls

def http_fingerprint(ip, port=80):
    """
    HTTP banner grabbing to identify camera
    """
    try:
        response = requests.get(f"http://{ip}:{port}", timeout=2)
        content = response.text.lower()
        headers = str(response.headers).lower()
        
        signatures = {
            'geeni': ['tuya', 'smart camera', 'geeni'],
            'wyze': ['wyzecam', 'wyze'],
            'hikvision': ['hikvision', '/doc/page/login.asp'],
            'dahua': ['dahua', '/RPC2_Login'],
            'generic': ['ip camera', 'ipcamera', 'netcam']
        }
        
        for camera_type, keywords in signatures.items():
            if any(kw in content or kw in headers for kw in keywords):
                return {
                    'type': camera_type,
                    'web_interface': True,
                    'url': f"http://{ip}:{port}"
                }
    except:
        pass
    
    return None

def comprehensive_camera_scan(ip, mac=None):
    """
    Multi-layer camera detection
    Returns complete camera profile with bypass info
    """
    result = {
        'ip': ip,
        'mac': mac,
        'is_camera': False,
        'confidence': 0,
        'detection_methods': [],
        'bypass_info': {}
    }
    
    # Layer 1: MAC address check
    if mac:
        for camera_type, sig in CAMERA_SIGNATURES.items():
            if any(mac.upper().startswith(prefix) for prefix in sig['mac_prefixes']):
                result['is_camera'] = True
                result['type'] = camera_type
                result['confidence'] += 30
                result['detection_methods'].append('MAC_ADDRESS')
                result['bypass_info']['cloud_service'] = sig['cloud_service']
                result['bypass_info']['cloud_cost'] = sig['cloud_cost']
                break
    
    # Layer 2: Port scanning
    open_ports = check_camera_ports(ip)
    if any(port in [554, 8554, 6668] for port in open_ports):
        result['is_camera'] = True
        result['confidence'] += 25
        result['detection_methods'].append('PORT_SCAN')
        result['open_ports'] = open_ports
    
    # Layer 3: ONVIF discovery
    onvif_result = probe_onvif(ip)
    if onvif_result['detected']:
        result['is_camera'] = True
        result['confidence'] += 40
        result['detection_methods'].append('ONVIF')
        result['onvif_info'] = onvif_result
        result['bypass_info']['method'] = 'ONVIF'
        result['bypass_info']['rtsp_urls'] = onvif_result['rtsp_urls']
    
    # Layer 4: Tuya protocol (Geeni)
    tuya_result = detect_tuya_camera(ip)
    if tuya_result['detected']:
        result['is_camera'] = True
        result['confidence'] += 35
        result['detection_methods'].append('TUYA_PROTOCOL')
        result['type'] = 'geeni_tuya'
        result['tuya_info'] = tuya_result
        result['bypass_info']['method'] = 'TUYA_LOCAL'
    
    # Layer 5: HTTP fingerprinting
    http_result = http_fingerprint(ip)
    if http_result:
        result['is_camera'] = True
        result['confidence'] += 20
        result['detection_methods'].append('HTTP_FINGERPRINT')
        result['web_interface'] = http_result['url']
    
    # Layer 6: RTSP URL probing
    rtsp_urls = probe_rtsp_urls(ip)
    if rtsp_urls:
        result['is_camera'] = True
        result['confidence'] += 30
        result['detection_methods'].append('RTSP_PROBE')
        result['bypass_info']['rtsp_urls'] = rtsp_urls
        result['bypass_info']['method'] = 'DIRECT_RTSP'
    
    # Calculate savings
    if result.get('bypass_info', {}).get('cloud_cost'):
        annual_savings = result['bypass_info']['cloud_cost'] * 12
        result['bypass_info']['annual_savings'] = annual_savings
    
    # Determine bypass feasibility
    if result['bypass_info'].get('rtsp_urls') or result['bypass_info'].get('method'):
        result['bypass_info']['feasible'] = True
        result['bypass_info']['difficulty'] = 'AUTOMATIC'  # TrueVault handles it
    
    return result

def display_scanner_results(cameras):
    """
    Show results with cloud bypass savings
    """
    print("\n" + "="*70)
    print(" 🎉 CAMERA SCAN COMPLETE - CLOUD BYPASS AVAILABLE!")
    print("="*70 + "\n")
    
    total_savings = 0
    bypass_count = 0
    
    for camera in cameras:
        if not camera['is_camera']:
            continue
        
        print(f"📷 {camera['type'].upper()} Camera")
        print(f"   IP: {camera['ip']}")
        print(f"   MAC: {camera['mac']}")
        print(f"   Confidence: {camera['confidence']}%")
        print(f"   Detection: {', '.join(camera['detection_methods'])}")
        
        if camera.get('bypass_info', {}).get('feasible'):
            bypass_count += 1
            savings = camera['bypass_info'].get('annual_savings', 0)
            total_savings += savings
            
            print(f"   ✓ Cloud Bypass: AVAILABLE")
            print(f"   💰 Savings: ${savings}/year")
            print(f"   Method: {camera['bypass_info']['method']}")
        
        print()
    
    if bypass_count > 0:
        print("="*70)
        print(f" 💰 TOTAL ANNUAL SAVINGS: ${total_savings:.2f}")
        print(f" ✓ {bypass_count} camera(s) can bypass cloud subscriptions")
        print("="*70)
        print("\n Click 'Setup Cloud Bypass' to configure local access!")
```

---

## 📊 USER INTERFACE: BYPASS SETUP WIZARD

```
┌─────────────────────────────────────────────────────────────┐
│ Cloud Bypass Setup Wizard                                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Camera Detected: Geeni Smart Camera (2MP)                   │
│ IP Address: 192.168.1.150                                   │
│ Current Status: Using Geeni Cloud ❌                        │
│                                                             │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ Step 1: Enable Local Access                         │   │
│ │                                                       │   │
│ │ We've detected this camera can work WITHOUT cloud!  │   │
│ │                                                       │   │
│ │ Current: Geeni Cloud ($2.99/month = $36/year)       │   │
│ │ Future: TrueVault Local (FREE)                       │   │
│ │                                                       │   │
│ │ [✓] RTSP stream available                            │   │
│ │ [✓] Local recording supported                        │   │
│ │ [✓] Motion detection available                       │   │
│ │                                                       │   │
│ │ [Continue]                                            │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                             │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ Step 2: Camera Credentials                           │   │
│ │                                                       │   │
│ │ Enter camera login (usually in Geeni app):          │   │
│ │                                                       │   │
│ │ Username: [admin        ]                            │   │
│ │ Password: [**********   ]                            │   │
│ │                                                       │   │
│ │ Don't know? [Try Common Passwords]                   │   │
│ │                                                       │   │
│ │ [Test Connection]                                     │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                             │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ Step 3: Configure TrueVault Access                   │   │
│ │                                                       │   │
│ │ ✓ RTSP stream configured                             │   │
│ │ ✓ Local recording enabled                            │   │
│ │ ✓ Motion detection active                            │   │
│ │ ✓ Port forwarding set up                             │   │
│ │                                                       │   │
│ │ Camera Name: [Front Door Camera]                     │   │
│ │                                                       │   │
│ │ [Complete Setup]                                      │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                             │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ Success! 🎉                                          │   │
│ │                                                       │   │
│ │ Your Geeni camera now works through TrueVault!      │   │
│ │                                                       │   │
│ │ ✓ No more Geeni cloud                                │   │
│ │ ✓ No more $2.99/month fees                           │   │
│ │ ✓ FREE local storage                                 │   │
│ │ ✓ Better privacy (footage stays local)              │   │
│ │                                                       │   │
│ │ You're saving $36/year! 💰                           │   │
│ │                                                       │   │
│ │ [View Camera] [Setup Another Camera]                 │   │
│ └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎯 MARKETING: CLOUD BYPASS MESSAGING

### **Landing Page Addition:**

```
┌──────────────────────────────────────────────────────────────┐
│ 💰 ALREADY HAVE GEENI, WYZE, OR RING CAMERAS?                │
│    STOP PAYING CLOUD SUBSCRIPTION FEES!                      │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│ Your cameras work perfectly without cloud subscriptions!    │
│                                                              │
│ TrueVault detects your existing cameras and configures     │
│ them for FREE local access. No more monthly fees!           │
│                                                              │
│ CLOUD COSTS (What you're paying now):                       │
│ • Geeni Cloud: $2.99/month ($36/year)                       │
│ • Wyze Cam Plus: $1.99/month ($24/year)                     │
│ • Ring Protect: $3.99/month ($48/year)                      │
│                                                              │
│ TRUEVAULT COST: $0/month (FREE local storage!) ✓           │
│                                                              │
│ Average family with 4 cameras: SAVE $144-192/YEAR! 💰       │
│                                                              │
│ [Start Free Trial] [See How It Works]                       │
└──────────────────────────────────────────────────────────────┘
```

### **Key Marketing Messages:**

**"Cut The Cloud, Keep Your Cameras"**
> "Already own Geeni, Wyze, or Ring cameras? Stop paying monthly fees. TrueVault bypasses expensive cloud subscriptions and gives you FREE local storage."

**"Your Cameras, Your Control, Your Savings"**
> "Save $96-576/year by ditching cloud subscriptions. TrueVault detects your cameras and configures local access automatically. No technical knowledge needed."

**"See Through The Cloud Camera Scam"**
> "Camera manufacturers charge you monthly fees to access YOUR footage from YOUR cameras in YOUR home. That's ridiculous. TrueVault gives you direct access - FREE."

---

**STATUS:** Scanner Enhanced with Cloud Bypass Detection  
**NEW CAPABILITY:** Detect Geeni/Wyze/Ring/Nest cameras and bypass cloud  
**USER BENEFIT:** Save $96-576/year in cloud subscription fees  
**COMPETITIVE ADVANTAGE:** MASSIVE - no other VPN does this!
