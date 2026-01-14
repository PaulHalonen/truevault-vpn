import ftplib
from io import BytesIO
import sys
import os

# Set UTF-8 output
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
    except:
        return None

def search_placeholders(content, filename):
    """Search for placeholder patterns in content"""
    if not content:
        return []
    
    placeholders = []
    lines = content.split('\n')
    for i, line in enumerate(lines, 1):
        lower = line.lower()
        # Common placeholder patterns
        if any(p in lower for p in ['placeholder', 'todo', 'xxx', 'fake', 'mock', 'sample data', 'example data', 'hardcoded', 'hardcode']):
            placeholders.append((filename, i, line.strip()[:120]))
        # Template variables that weren't replaced
        if '{{' in line and '}}' in line:
            placeholders.append((filename, i, line.strip()[:120]))
        # Check for static/fake data patterns
        if 'user@example' in lower or '192.168.1.' in line or "'fake" in lower or '"fake' in lower:
            placeholders.append((filename, i, line.strip()[:120]))
            
    return placeholders

# Files to check on live server
files_to_check = [
    '/public/dashboard/index.html',
    '/public/dashboard/devices.html',
    '/public/dashboard/servers.html',
    '/public/dashboard/billing.html',
    '/public/dashboard/settings.html',
    '/public/dashboard/connect.html',
    '/public/dashboard/cameras.html',
    '/public/assets/js/app.js',
    '/api/vpn/servers.php',
    '/api/devices/list.php',
    '/api/config/database.php',
    '/api/helpers/auth.php',
    '/api/auth/login.php',
    '/api/auth/register.php',
]

print("=" * 60)
print("LIVE SERVER PLACEHOLDER AUDIT")
print("=" * 60)

all_placeholders = []

for filepath in files_to_check:
    full_path = base + filepath
    content = get_file(full_path)
    if content:
        phs = search_placeholders(content, filepath)
        all_placeholders.extend(phs)
        print(f"\n[OK] {filepath} - {len(content)} bytes, {len(phs)} placeholders")
    else:
        print(f"\n[MISSING] {filepath}")

print("\n" + "=" * 60)
print(f"TOTAL PLACEHOLDERS FOUND: {len(all_placeholders)}")
print("=" * 60)

for filepath, line_num, text in all_placeholders:
    print(f"\n{filepath}:{line_num}")
    print(f"  {text}")

# Also check for hardcoded server data
print("\n" + "=" * 60)
print("CHECKING api/vpn/servers.php FOR HARDCODED DATA")
print("=" * 60)

servers_content = get_file(base + '/api/vpn/servers.php')
if servers_content:
    # Look for array definitions with IP addresses
    if "'66.94.103.91'" in servers_content or '"66.94.103.91"' in servers_content:
        print("\n[HARDCODED] Server IPs are hardcoded in PHP file!")
        print("Should read from database instead.")
    
    # Check if it reads from database
    if 'Database::' in servers_content or 'getDatabase' in servers_content:
        print("\n[OK] Uses database functions")
    else:
        print("\n[HARDCODED] Does NOT use database - all data is hardcoded!")
    
    # Show relevant portions
    lines = servers_content.split('\n')
    in_array = False
    for i, line in enumerate(lines, 1):
        if '$servers' in line or "'id'" in line or "'ip'" in line or "'name'" in line:
            print(f"  {i}: {line.strip()[:100]}")

ftp.quit()
print("\n\nAudit complete.")
