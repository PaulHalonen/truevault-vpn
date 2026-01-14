TruthVault Network Scanner
==========================

Automatically discover devices on your home network for port forwarding.
Detects: IP cameras (Geeni, Wyze, Hikvision), printers, gaming consoles, 
smart TVs, and more!

REQUIREMENTS
------------
- Python 3.8 or higher
- Windows, Mac, or Linux

QUICK START
-----------

*** WINDOWS ***
1. Extract this zip to a folder
2. Double-click: run_scanner.bat
3. Enter your email and auth token when prompted

*** MAC / LINUX ***
1. Extract this zip to a folder
2. Open Terminal
3. Navigate to folder: cd ~/Downloads/truthvault-scanner
4. Make executable: chmod +x run_scanner.sh
5. Run: ./run_scanner.sh
6. Enter your email and auth token when prompted

GETTING YOUR AUTH TOKEN
-----------------------
1. Log in to TruthVault VPN dashboard
2. Go to Scanner page
3. Click "Copy Auth Token" button
4. Paste when the scanner prompts for token

WHAT THE SCANNER DOES
---------------------
1. Scans your local network (192.168.x.x or 10.0.x.x)
2. Identifies devices by MAC address vendor
3. Checks common ports to determine device type
4. Opens a web browser at http://localhost:8888
5. Shows all discovered devices with icons
6. One-click sync to your TruthVault account

SUPPORT
-------
Email: paulhalonen@gmail.com
Website: https://vpn.the-truth-publishing.com
