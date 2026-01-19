import ftplib
import os

host = 'the-truth-publishing.com'
user = 'kahlen@the-truth-publishing.com'
password = 'AndassiAthena8'
base_path = r'E:\Documents\GitHub\truevault-vpn\website'
remote_base = '/public_html/vpn.the-truth-publishing.com'

files_to_upload = [
    # Admin interfaces
    ('admin/setup-servers-database.php', '/admin/setup-servers-database.php'),
    ('admin/server-management.php', '/admin/server-management.php'),
    # Server Manager helper
    ('includes/ServerManager.php', '/includes/ServerManager.php'),
    # Server APIs
    ('api/servers/list.php', '/api/servers/list.php'),
    ('api/servers/health-check.php', '/api/servers/health-check.php'),
]

print('Connecting to FTP server...')
ftp = ftplib.FTP(host, user, password)
print('Connected successfully!')

for local_file, remote_file in files_to_upload:
    local_path = os.path.join(base_path, local_file)
    remote_path = remote_base + remote_file
    
    # Create directory if needed
    remote_dir = os.path.dirname(remote_path)
    try:
        ftp.cwd(remote_dir)
    except:
        # Directory doesn't exist, create it
        parts = remote_dir.split('/')
        current = ''
        for part in parts:
            if part:
                current += '/' + part
                try:
                    ftp.mkd(current)
                    print(f'  Created directory: {current}')
                except:
                    pass
        ftp.cwd(remote_dir)
    
    print(f'Uploading {local_file}...')
    with open(local_path, 'rb') as f:
        ftp.storbinary(f'STOR {os.path.basename(remote_path)}', f)
    print(f'  OK - Uploaded')

ftp.quit()
print('\nAll 5 files uploaded successfully!')
