import ftplib
import os

host = 'the-truth-publishing.com'
user = 'kahlen@the-truth-publishing.com'
password = 'AndassiAthena8'
base_path = r'E:\Documents\GitHub\truevault-vpn\website'
remote_base = '/public_html/vpn.the-truth-publishing.com'

files_to_upload = [
    # Admin interfaces
    ('admin/theme-manager.php', '/admin/theme-manager.php'),
    ('admin/site-settings.php', '/admin/site-settings.php'),
    ('admin/page-builder.php', '/admin/page-builder.php'),
    ('admin/navigation-editor.php', '/admin/navigation-editor.php'),
    ('admin/media-library.php', '/admin/media-library.php'),
    ('admin/setup-pages.php', '/admin/setup-pages.php'),
    # Theme API
    ('api/themes/get-colors.php', '/api/themes/get-colors.php'),
    # Page APIs
    ('api/pages/add-section.php', '/api/pages/add-section.php'),
    ('api/pages/toggle-visibility.php', '/api/pages/toggle-visibility.php'),
    ('api/pages/delete-section.php', '/api/pages/delete-section.php'),
    ('api/pages/reorder-sections.php', '/api/pages/reorder-sections.php'),
    ('api/pages/update-section.php', '/api/pages/update-section.php'),
    ('api/pages/get-section.php', '/api/pages/get-section.php'),
    # Frontend
    ('render-page.php', '/render-page.php'),
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
print('\nAll 14 files uploaded successfully!')
