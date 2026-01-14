import ftplib
from io import BytesIO
import sys
import json

if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8')

ftp = ftplib.FTP('the-truth-publishing.com')
ftp.login('kahlen@the-truth-publishing.com', 'AndassiAthena8')
base = '/public_html/vpn.the-truth-publishing.com'

def get_file(path):
    buffer = BytesIO()
    try:
        ftp.retrbinary(f'RETR {path}', buffer.write)
        return buffer.getvalue().decode('utf-8', errors='ignore')
    except Exception as e:
        return None

print("=" * 70)
print("DEEP DIVE: API AND JAVASCRIPT ANALYSIS")
print("=" * 70)

# Check app.js - what APIs does it call?
print("\n[1] ANALYZING app.js - API CALLS")
print("-" * 50)
app_js = get_file(base + '/public/assets/js/app.js')
if app_js:
    # Find all fetch calls and API paths
    lines = app_js.split('\n')
    for i, line in enumerate(lines, 1):
        if '/api/' in line or 'fetch(' in line:
            print(f"  {i}: {line.strip()[:100]}")

# Check servers.php - what does it actually return?
print("\n\n[2] ANALYZING api/vpn/servers.php")
print("-" * 50)
servers_php = get_file(base + '/api/vpn/servers.php')
if servers_php:
    print(servers_php[:2000])

# Check devices/list.php
print("\n\n[3] ANALYZING api/devices/list.php")
print("-" * 50)
devices_php = get_file(base + '/api/devices/list.php')
if devices_php:
    print(devices_php[:2000])

# Check if there's a devices/add.php
print("\n\n[4] CHECKING api/devices/add.php")
print("-" * 50)
add_php = get_file(base + '/api/devices/add.php')
if add_php:
    print("EXISTS - first 50 lines:")
    for i, line in enumerate(add_php.split('\n')[:50], 1):
        print(f"  {i}: {line}")
else:
    print("FILE DOES NOT EXIST ON SERVER!")

# Check database.php class
print("\n\n[5] ANALYZING api/config/database.php")
print("-" * 50)
db_php = get_file(base + '/api/config/database.php')
if db_php:
    print(db_php[:3000])

ftp.quit()
