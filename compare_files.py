import ftplib
import sys
import os

if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8')

# Get local files
local_base = r'E:\Documents\GitHub\truevault-vpn'
local_files = set()

for root, dirs, files in os.walk(local_base):
    # Skip .git and reference directories
    dirs[:] = [d for d in dirs if d not in ['.git', 'reference', 'node_modules', '__pycache__']]
    for f in files:
        if not f.endswith('.py') and not f.endswith('.ps1') and not f.startswith('.'):
            rel_path = os.path.relpath(os.path.join(root, f), local_base)
            local_files.add(rel_path.replace('\\', '/'))

# Get server files
ftp = ftplib.FTP('the-truth-publishing.com')
ftp.login('kahlen@the-truth-publishing.com', 'AndassiAthena8')
base = '/public_html/vpn.the-truth-publishing.com'

server_files = set()

def list_ftp_recursive(path, prefix=''):
    try:
        ftp.cwd(path)
        items = ftp.nlst()
        for item in items:
            if item in ['.', '..']:
                continue
            full_path = f'{path}/{item}'
            rel_path = f'{prefix}{item}' if prefix else item
            
            # Try to enter as directory
            try:
                ftp.cwd(full_path)
                ftp.cwd('..')
                # It's a directory
                if item not in ['.well-known', 'error_log']:
                    list_ftp_recursive(full_path, f'{rel_path}/')
            except:
                # It's a file
                if not item.startswith('.') and item != 'error_log':
                    server_files.add(rel_path)
    except Exception as e:
        pass

list_ftp_recursive(base)
ftp.quit()

# Compare
print("=" * 70)
print("FILES COMPARISON: LOCAL vs SERVER")
print("=" * 70)

# Important directories to check
important_dirs = ['api/', 'public/dashboard/', 'public/assets/', 'dashboard/']

print("\n### FILES ON LOCAL BUT NOT ON SERVER ###")
print("(These need to be uploaded)")
local_only = local_files - server_files
for f in sorted(local_only):
    if any(f.startswith(d) for d in important_dirs):
        print(f"  + {f}")

print("\n\n### FILES ON SERVER BUT NOT IN LOCAL REPO ###")
print("(These might be outdated/different)")
server_only = server_files - local_files
for f in sorted(server_only):
    if any(f.startswith(d) for d in important_dirs):
        print(f"  - {f}")

print("\n\n### KEY API FILES COMPARISON ###")
key_files = [
    'api/devices/add.php',
    'api/devices/list.php', 
    'api/devices/remove.php',
    'api/devices/register.php',
    'api/vpn/servers.php',
    'api/vpn/connect.php',
    'api/config/database.php',
    'api/helpers/auth.php',
    'public/dashboard/devices.html',
    'public/dashboard/servers.html',
    'dashboard/devices.html',
]

for f in key_files:
    on_local = f in local_files or f.replace('public/', '') in local_files
    on_server = f in server_files
    
    if on_local and on_server:
        status = "BOTH (need to check if same)"
    elif on_local and not on_server:
        status = "LOCAL ONLY - NEEDS UPLOAD"
    elif not on_local and on_server:
        status = "SERVER ONLY - may be outdated"
    else:
        status = "NEITHER"
    
    print(f"  {f}: {status}")
