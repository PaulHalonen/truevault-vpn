TrueVault Network Scanner v2.0
==============================
BRUTE FORCE CAMERA DISCOVERY

Automatically discover ALL devices on your home network including:
- IP cameras (Geeni, Wyze, Hikvision, Dahua, Amcrest, Reolink, Ring)
- Smart home devices (Nest, Echo, etc.)
- Printers, gaming consoles, streaming devices
- AND MORE!

NEW IN v2.0:
- ONVIF camera auto-discovery
- UPnP/mDNS scanning
- Brute force port scanning (ALL camera ports)
- Credential testing (50+ default password combos)
- HTTP fingerprinting for camera detection
- RTSP URL auto-generation

REQUIREMENTS
------------
- Python 3.8 or higher
- Windows, Mac, or Linux

QUICK START
-----------

*** WINDOWS ***
1. Extract this zip to a folder (Desktop, Documents, etc.)
2. Double-click: run_scanner.bat
3. Enter your email and auth token when prompted

*** MAC ***
1. Extract this zip to a folder
2. Open Terminal (Applications > Utilities > Terminal)
3. Navigate to folder:
   cd ~/Downloads/truthvault-scanner
   (or wherever you extracted it)
4. Make executable:
   chmod +x run_scanner.sh
5. Run:
   ./run_scanner.sh
6. Enter your email and auth token when prompted

*** LINUX ***
1. Extract this zip to a folder
2. Open Terminal
3. Navigate to folder:
   cd ~/Downloads/truthvault-scanner
4. Make executable:
   chmod +x run_scanner.sh
5. Run:
   ./run_scanner.sh
6. Enter your email and auth token when prompted

GETTING YOUR AUTH TOKEN
-----------------------
1. Log in to TrueVault VPN dashboard
2. Go to Port Forwarding > Discover tab
3. Click the blue "Copy Auth Token" button
4. Paste when the scanner prompts for token

WHAT THE SCANNER DOES
---------------------
1. Scans your local network (192.168.x.x or 10.0.x.x)
2. Identifies devices by MAC address vendor
3. BRUTE FORCE scans ALL camera ports on every device
4. Uses ONVIF/UPnP/mDNS protocols to find cameras
5. Tests 50+ default credential combinations
6. HTTP fingerprints to identify camera brands
7. Opens a web browser at http://localhost:8888
8. Shows all discovered devices with icons
9. One-click sync to your TrueVault account

CAMERA DISCOVERY FEATURES
-------------------------
- ONVIF Discovery: Finds cameras using ONVIF WS-Discovery
- UPnP Scanning: Discovers media devices via SSDP
- mDNS/Bonjour: Finds network services
- Port Scanning: Tests ports 554, 8554, 37777, 34567, etc.
- HTTP Fingerprinting: Identifies Hikvision, Dahua, etc.
- Credential Testing: Tests admin/admin, root/root, etc.

SUPPORTED CAMERAS
-----------------
- Geeni (Tuya-based)
- Wyze
- Hikvision
- Dahua
- Amcrest
- Reolink
- Ring (local mode)
- Nest (local mode)
- Any ONVIF-compatible camera
- Any camera with RTSP support

TROUBLESHOOTING
---------------

"Python not found" (Windows):
  - Install from https://python.org
  - IMPORTANT: Check "Add Python to PATH" during install
  - Restart your computer after installing

"Python not found" (Mac):
  - Install Homebrew: /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
  - Then: brew install python3

"Python not found" (Linux):
  - Ubuntu/Debian: sudo apt install python3 python3-pip
  - Fedora: sudo dnf install python3 python3-pip
  - Arch: sudo pacman -S python python-pip

"Permission denied" (Mac/Linux):
  - Run: chmod +x run_scanner.sh
  - Then try again: ./run_scanner.sh

"Can't open file" error:
  - Make sure you EXTRACTED the zip first!
  - Don't run from inside the zip file

Scanner shows 0 devices:
  - Windows: Right-click run_scanner.bat > Run as Administrator
  - Mac/Linux: Try with sudo: sudo ./run_scanner.sh
  - Make sure VPN is DISCONNECTED during scan
  - Firewall may be blocking - allow Python through

Scanner shows 0 cameras:
  - Cameras may not support RTSP (cloud-only cameras)
  - Try enabling RTSP in camera's app settings
  - Some cameras need firmware update for RTSP

Credentials not found:
  - Camera may have non-default password
  - Manual setup required in TrueVault dashboard

SECURITY NOTE
-------------
This scanner only tests DEFAULT credentials.
It does NOT crack or brute force your camera passwords.
It is safe and non-destructive.

SUPPORT
-------
Email: paulhalonen@gmail.com
Website: https://vpn.the-truth-publishing.com
