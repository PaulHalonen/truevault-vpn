TruthVault Enhanced Network Scanner v3.0
=========================================

🔥 NEW FEATURES - AGGRESSIVE CAMERA DETECTION!
----------------------------------------------

This enhanced scanner includes powerful new tools to detect hidden cameras and 
cloud-based devices (like Geeni cameras) that traditional network scans miss!

WHAT'S NEW IN V3.0:
-------------------
✅ Brute Force Port Scanning - Scans ALL camera-specific ports
✅ HTTP Banner Grabbing - Identifies camera web interfaces
✅ RTSP Stream Detection - Finds IP camera streams (port 554, 8554, etc.)
✅ ONVIF Discovery - Detects ONVIF-compatible cameras
✅ Extended Port List - Checks 30+ camera-specific ports:
   - Hikvision: 8000, 9000
   - Dahua: 34567, 37777, 37778
   - Foscam: 9527, 88
   - Reolink: 6036, 9000
   - Amcrest: 8200
   - Tuya/Geeni: 6668, 1883, 8883
   - RTSP: 554, 8554, 7447, 10554
   - And many more!

✅ Device Fingerprinting - Analyzes HTTP responses to identify camera brands
✅ Two Scan Modes:
   - Quick Scan (30 seconds) - Basic detection
   - Aggressive Scan (2-3 minutes) - FINDS HIDDEN CAMERAS!


REQUIREMENTS
------------
- Python 3.8 or higher
- Windows, Mac, or Linux


QUICK START
-----------

*** WINDOWS ***
1. Extract this folder to your Desktop
2. Double-click: run_scanner_enhanced.bat
3. Enter your email and auth token when prompted
4. Choose scan mode:
   - Click "Quick Scan" for normal speed (30 sec)
   - Click "Aggressive Scan" to find ALL cameras (2-3 min)

*** MAC/LINUX ***
1. Extract this folder
2. Open Terminal
3. Navigate to folder:
   cd ~/Downloads/scanner
4. Make executable:
   chmod +x run_scanner_enhanced.sh
5. Run:
   ./run_scanner_enhanced.sh
6. Enter your email and auth token
7. Choose scan mode in browser


GETTING YOUR AUTH TOKEN
-----------------------
1. Log in to TruthVault VPN dashboard
2. Go to Port Forwarding tab
3. Click "Copy Auth Token" button
4. Paste when the scanner prompts for token


SCAN MODES EXPLAINED
--------------------

⚡ QUICK SCAN (30 seconds):
- Pings entire network (192.168.x.x)
- Checks common ports (80, 443, 554, 8080)
- Fast basic detection
- Good for most devices

🔥 AGGRESSIVE SCAN (2-3 minutes):
- Everything in Quick Scan, PLUS:
- Scans 30+ camera-specific ports per device
- HTTP banner grabbing on web ports
- RTSP stream detection
- ONVIF discovery
- Device fingerprinting
- **USE THIS IF YOU HAVE GEENI/TUYA CAMERAS!**
- **USE THIS IF YOU SAW "UNKNOWN DEVICES"!**


WHAT THE SCANNER DOES
---------------------
1. Discovers ALL devices on your network
2. Identifies device types by:
   - MAC address vendor lookup
   - Port scanning
   - HTTP banner analysis
   - RTSP detection
3. Detects cameras by:
   - Known camera ports (554, 8554, 34567, etc.)
   - HTTP interface signatures
   - RTSP streaming capability
   - Vendor identification
4. Opens web interface at http://localhost:8888
5. Shows devices with color coding:
   - RED border = Known camera type (by MAC)
   - GREEN border + pulse = Camera DETECTED by aggressive scan!
6. One-click sync to your TruthVault account


CAMERA DETECTION DETAILS
-------------------------

The aggressive scan can identify:

✅ Geeni Cameras (Tuya-based)
   - Checks ports: 6668, 1883, 8883, 80, 8080
   - Looks for "Geeni", "SmartLife", "Tuya" in web interface

✅ Wyze Cameras
   - Checks RTSP streams on 554, 8554
   - Identifies "Wyze" web interface

✅ Hikvision Cameras
   - Checks ports: 8000, 9000, 80
   - Detects "HIKVISION", "iVMS" signatures

✅ Dahua Cameras
   - Checks ports: 34567, 37777, 37778
   - Identifies Dahua web interface

✅ Amcrest Cameras
   - Checks port: 8200
   - Detects "Amcrest", "AMC" signatures

✅ Reolink Cameras
   - Checks ports: 6036, 9000
   - Identifies "Reolink", "RLC-" signatures

✅ Foscam Cameras
   - Checks ports: 9527, 88
   - Detects "Foscam" interface

✅ Ring Doorbells
   - Identifies Ring devices by MAC/interface

✅ Nest Cameras
   - Identifies Nest/Google cameras

✅ Generic IP Cameras
   - Any device with RTSP streams (port 554)
   - Any device with camera-specific ports


WHY USE AGGRESSIVE MODE?
------------------------

Cloud cameras like Geeni don't always respond to basic scans because:
1. They communicate primarily with cloud servers
2. They use non-standard ports
3. Their web interfaces may be hidden
4. MAC addresses might show as "Unknown"

AGGRESSIVE MODE solves this by:
1. Checking ALL possible camera ports
2. Trying to access web interfaces
3. Looking for RTSP streams
4. Analyzing HTTP responses
5. Testing proprietary ports

**If you have Geeni, Tuya, or other cloud cameras, ALWAYS use Aggressive Mode!**


TROUBLESHOOTING
---------------

Scanner shows 0 devices:
  - Windows: Right-click run_scanner_enhanced.bat > Run as Administrator
  - Mac/Linux: Try with sudo: sudo ./run_scanner_enhanced.sh
  - Make sure VPN is DISCONNECTED during scan
  - Firewall may be blocking - allow Python through

Camera still shows as "Unknown":
  - Use AGGRESSIVE SCAN mode
  - Wait the full 2-3 minutes for complete scan
  - Make sure camera is powered on and connected to network
  - Try accessing camera directly via web browser first

Scan takes too long:
  - Aggressive mode is SUPPOSED to take 2-3 minutes
  - It's checking 30+ ports per device
  - Worth the wait to find hidden cameras!

"Permission denied":
  - Mac/Linux: chmod +x run_scanner_enhanced.sh
  - Or try: sudo ./run_scanner_enhanced.sh

Python not found:
  - Windows: Install from https://python.org (check "Add to PATH")
  - Mac: brew install python3
  - Linux: sudo apt install python3 python3-pip


SUPPORTED CAMERA BRANDS
-----------------------

The scanner recognizes these camera manufacturers:

Cloud Cameras:
- Geeni
- Tuya/SmartLife
- Wyze
- Blink
- Ring

Professional IP Cameras:
- Hikvision
- Dahua
- Amcrest
- Reolink
- Foscam

Smart Home Cameras:
- Nest (Google)
- Arlo (Netgear)
- And many more!


TECHNICAL DETAILS
-----------------

Ports Scanned (Aggressive Mode):
- HTTP/HTTPS: 80, 443, 8080, 8081, 8000, 8888, 8443
- RTSP: 554, 8554, 7447, 10554
- ONVIF: 3702, 8899
- Hikvision: 8000, 9000
- Dahua: 34567, 37777, 37778
- Foscam: 9527, 88
- Reolink: 6036, 9000
- Amcrest: 8200
- Tuya/Geeni: 6668, 1883, 8883
- Plus: 22 (SSH), 23 (Telnet), 21 (FTP), etc.

Detection Methods:
1. MAC Address Lookup - 200+ camera vendors
2. Port Fingerprinting - 30+ camera-specific ports
3. HTTP Banner Grabbing - Analyzes web interface
4. RTSP Stream Detection - Tests for video streams
5. Title/Server Analysis - Looks for camera keywords


SUPPORT
-------
Email: paulhalonen@gmail.com
Website: https://vpn.the-truth-publishing.com

If you're having trouble detecting your Geeni camera:
1. Make sure it's connected to WiFi
2. Try accessing it via the Geeni app first
3. Use AGGRESSIVE SCAN mode
4. Email support with scanner results
