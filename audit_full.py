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

print("=" * 70)
print("FULL APP.JS ANALYSIS")
print("=" * 70)

app_js = get_file(base + '/public/assets/js/app.js')
if app_js:
    lines = app_js.split('\n')
    print(f"Total lines: {len(lines)}")
    
    # Find all API calls
    print("\nALL API ENDPOINTS CALLED:")
    print("-" * 50)
    for i, line in enumerate(lines, 1):
        if '/api/' in line:
            print(f"  {i}: {line.strip()}")
    
    # Find device-related functions
    print("\n\nDEVICE-RELATED FUNCTIONS:")
    print("-" * 50)
    in_device_func = False
    for i, line in enumerate(lines, 1):
        lower = line.lower()
        if 'device' in lower and ('function' in lower or 'async' in lower or '=>' in line):
            in_device_func = True
            print(f"\n--- Found at line {i} ---")
        if in_device_func:
            print(f"  {i}: {line.rstrip()}")
            if line.strip() == '}':
                in_device_func = False
                if i > 50:
                    break
    
    # Find server-related functions  
    print("\n\nSERVER-RELATED FUNCTIONS:")
    print("-" * 50)
    for i, line in enumerate(lines, 1):
        lower = line.lower()
        if 'server' in lower and ('function' in lower or 'async' in lower or 'load' in lower or 'fetch' in lower):
            print(f"  {i}: {line.strip()}")

print("\n\n" + "=" * 70)
print("CHECKING WHAT'S IN THE DATABASES ON SERVER")
print("=" * 70)

# Check database.php for DatabaseManager class
db_php = get_file(base + '/api/config/database.php')
if db_php:
    if 'DatabaseManager' in db_php:
        print("\n[FOUND] DatabaseManager class exists!")
        # Show the DatabaseManager code
        lines = db_php.split('\n')
        in_manager = False
        for i, line in enumerate(lines, 1):
            if 'class DatabaseManager' in line:
                in_manager = True
            if in_manager:
                print(f"  {i}: {line}")
                if line.strip().startswith('class ') and 'DatabaseManager' not in line:
                    break
    else:
        print("\n[NOT FOUND] DatabaseManager class - devices/list.php will FAIL!")

print("\n\n" + "=" * 70)
print("COMPARING LOCAL vs SERVER FILES")
print("=" * 70)

# List what exists on server but not captured yet
print("\nFILES THAT MAY DIFFER:")

# Check api/devices directory
print("\n/api/devices/ on SERVER:")
ftp.cwd(base + '/api/devices')
for item in sorted(ftp.nlst()):
    if item not in ['.', '..']:
        print(f"  {item}")

# Check api/vpn directory
print("\n/api/vpn/ on SERVER:")
ftp.cwd(base + '/api/vpn')
for item in sorted(ftp.nlst()):
    if item not in ['.', '..']:
        print(f"  {item}")

# Check api/users directory
print("\n/api/users/ on SERVER:")
ftp.cwd(base + '/api/users')
for item in sorted(ftp.nlst()):
    if item not in ['.', '..']:
        print(f"  {item}")

ftp.quit()
