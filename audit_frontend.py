import ftplib
from io import BytesIO
import sys
import re

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
print("DASHBOARD devices.html - WHAT API DOES IT CALL?")
print("=" * 70)

content = get_file(base + '/public/dashboard/devices.html')
if content:
    # Find all script tags and API calls
    lines = content.split('\n')
    
    # Look for script section
    in_script = False
    for i, line in enumerate(lines, 1):
        if '<script' in line.lower():
            in_script = True
        if in_script:
            # Look for API calls
            if 'fetch' in line or '/api/' in line or 'async' in line.lower() or 'loaddevice' in line.lower() or 'getdevice' in line.lower():
                print(f"{i}: {line.rstrip()[:120]}")

print("\n\n")
print("=" * 70)  
print("DASHBOARD servers.html - WHAT API DOES IT CALL?")
print("=" * 70)

content = get_file(base + '/public/dashboard/servers.html')
if content:
    lines = content.split('\n')
    in_script = False
    for i, line in enumerate(lines, 1):
        if '<script' in line.lower():
            in_script = True
        if in_script:
            if 'fetch' in line or '/api/' in line or 'loadserver' in line.lower() or 'server' in line.lower():
                print(f"{i}: {line.rstrip()[:120]}")

print("\n\n")
print("=" * 70)
print("CHECKING app.js for API_BASE and fetch calls")
print("=" * 70)

content = get_file(base + '/public/assets/js/app.js')
if content:
    lines = content.split('\n')
    for i, line in enumerate(lines, 1):
        # Look for API base config and fetch calls
        if 'API' in line or 'fetch' in line or 'endpoint' in line.lower() or '/api' in line:
            print(f"{i}: {line.rstrip()[:120]}")

ftp.quit()
