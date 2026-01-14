import ftplib
from io import BytesIO
import sys

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

# Get full devices/list.php
print("=" * 70)
print("FULL api/devices/list.php")
print("=" * 70)
content = get_file(base + '/api/devices/list.php')
if content:
    print(content)

print("\n\n")
print("=" * 70)
print("FULL api/devices/index.php")
print("=" * 70)
content = get_file(base + '/api/devices/index.php')
if content:
    print(content)

print("\n\n")
print("=" * 70)
print("FULL api/users/devices.php")
print("=" * 70)
content = get_file(base + '/api/users/devices.php')
if content:
    print(content)

ftp.quit()
