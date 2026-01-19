import ftplib
import os
import sys

# Set UTF-8 encoding for Windows console
if sys.platform == 'win32':
    import codecs
    sys.stdout = codecs.getwriter('utf-8')(sys.stdout.buffer, 'strict')

# FTP credentials
FTP_HOST = 'the-truth-publishing.com'
FTP_USER = 'kahlen@the-truth-publishing.com'
FTP_PASS = 'AndassiAthena8'
FTP_PORT = 21

# Files to upload for Part 11
files_to_upload = [
    ('website/admin/setup-parental-advanced.php', '/public_html/vpn.the-truth-publishing.com/admin/setup-parental-advanced.php'),
    ('website/api/parental/schedules.php', '/public_html/vpn.the-truth-publishing.com/api/parental/schedules.php'),
    ('website/api/parental/windows.php', '/public_html/vpn.the-truth-publishing.com/api/parental/windows.php'),
    ('website/api/parental/gaming.php', '/public_html/vpn.the-truth-publishing.com/api/parental/gaming.php'),
    ('website/api/parental/whitelist.php', '/public_html/vpn.the-truth-publishing.com/api/parental/whitelist.php'),
    ('website/api/parental/stats.php', '/public_html/vpn.the-truth-publishing.com/api/parental/stats.php'),
    ('website/dashboard/parental-calendar.php', '/public_html/vpn.the-truth-publishing.com/dashboard/parental-calendar.php'),
]

def upload_file(ftp, local_path, remote_path):
    """Upload a single file to FTP server"""
    try:
        # Create remote directory if needed
        remote_dir = os.path.dirname(remote_path)
        dirs = remote_dir.split('/')
        current = ''
        for d in dirs:
            if not d:
                continue
            current += '/' + d
            try:
                ftp.mkd(current)
            except:
                pass
        
        # Upload file
        with open(local_path, 'rb') as f:
            ftp.storbinary(f'STOR {remote_path}', f)
        
        print(f'[OK] Uploaded: {local_path}')
        return True
        
    except Exception as e:
        print(f'[FAIL] {local_path}: {str(e)}')
        return False

def main():
    print('=' * 50)
    print('PART 11 FTP UPLOAD')
    print('=' * 50)
    print()
    
    base_dir = r'E:\Documents\GitHub\truevault-vpn'
    os.chdir(base_dir)
    
    print(f'Connecting to {FTP_HOST}...')
    try:
        ftp = ftplib.FTP()
        ftp.connect(FTP_HOST, FTP_PORT)
        ftp.login(FTP_USER, FTP_PASS)
        print(f'[OK] Connected as {FTP_USER}')
        print()
        
        success = 0
        failed = 0
        
        for local_path, remote_path in files_to_upload:
            full_local = os.path.join(base_dir, local_path)
            if os.path.exists(full_local):
                if upload_file(ftp, full_local, remote_path):
                    success += 1
                else:
                    failed += 1
            else:
                print(f'[FAIL] File not found: {local_path}')
                failed += 1
        
        ftp.quit()
        
        print()
        print('=' * 50)
        print(f'UPLOAD COMPLETE: {success} succeeded, {failed} failed')
        print('=' * 50)
        
    except Exception as e:
        print(f'[FAIL] Connection error: {str(e)}')

if __name__ == '__main__':
    main()
