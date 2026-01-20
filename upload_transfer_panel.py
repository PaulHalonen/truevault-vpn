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

# Base paths
LOCAL_BASE = 'admin/transfer'
REMOTE_BASE = '/public_html/vpn.the-truth-publishing.com/admin/transfer'

# Files to upload for Transfer Admin Panel
files_to_upload = [
    ('admin/transfer/styles.css', '/public_html/vpn.the-truth-publishing.com/admin/transfer/styles.css'),
    ('admin/transfer/index.php', '/public_html/vpn.the-truth-publishing.com/admin/transfer/index.php'),
    ('admin/transfer/verify.php', '/public_html/vpn.the-truth-publishing.com/admin/transfer/verify.php'),
    ('admin/transfer/process-transfer.php', '/public_html/vpn.the-truth-publishing.com/admin/transfer/process-transfer.php'),
    ('admin/transfer/rollback.php', '/public_html/vpn.the-truth-publishing.com/admin/transfer/rollback.php'),
]

def upload_file(ftp, local_path, remote_path):
    """Upload a single file to FTP server"""
    try:
        # Create remote directory if needed
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
                        ftp.cwd(current)
                    except:
                        try:
                            ftp.mkd(current)
                            print(f"  Created directory: {current}")
                        except:
                            pass
        
        # Upload the file
        with open(local_path, 'rb') as f:
            ftp.storbinary(f'STOR {remote_path}', f)
        print(f"  OK: {os.path.basename(local_path)}")
        return True
    except Exception as e:
        print(f"  FAIL: {os.path.basename(local_path)} - {str(e)}")
        return False

def main():
    print("=" * 50)
    print("TrueVault VPN - Transfer Panel Upload")
    print("=" * 50)
    print()
    
    # Connect to FTP
    print("Connecting to FTP server...")
    try:
        ftp = ftplib.FTP()
        ftp.connect(FTP_HOST, FTP_PORT)
        ftp.login(FTP_USER, FTP_PASS)
        print(f"Connected to {FTP_HOST}")
        print()
    except Exception as e:
        print(f"FAIL: Could not connect - {str(e)}")
        return
    
    # Upload files
    print("Uploading Transfer Admin Panel files...")
    print("-" * 40)
    
    success = 0
    failed = 0
    
    for local_path, remote_path in files_to_upload:
        if os.path.exists(local_path):
            if upload_file(ftp, local_path, remote_path):
                success += 1
            else:
                failed += 1
        else:
            print(f"  SKIP: {local_path} (not found)")
            failed += 1
    
    # Close connection
    ftp.quit()
    
    print("-" * 40)
    print(f"Upload complete: {success} success, {failed} failed")
    print()
    print("Access at:")
    print("  https://vpn.the-truth-publishing.com/admin/transfer/")
    print()

if __name__ == '__main__':
    main()
