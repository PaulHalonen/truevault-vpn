"""
TrueVault VPN - FTP Upload Script
Uploads all website files to server
"""

import ftplib
import os
from pathlib import Path

# FTP Configuration
FTP_HOST = "the-truth-publishing.com"
FTP_USER = "kahlen@the-truth-publishing.com"
FTP_PASS = "AndassiAthena8"
FTP_PORT = 21

LOCAL_PATH = r"E:\Documents\GitHub\truevault-vpn\website"
REMOTE_PATH = "/vpn.the-truth-publishing.com"

# Track progress
uploaded = 0
failed = []

def ensure_remote_dir(ftp, path):
    """Create remote directory if it doesn't exist"""
    dirs = path.strip('/').split('/')
    current = ''
    for d in dirs:
        current += '/' + d
        try:
            ftp.cwd(current)
        except:
            try:
                ftp.mkd(current)
                print(f"  Created dir: {current}")
            except:
                pass

def upload_file(ftp, local_file, remote_file):
    """Upload a single file"""
    global uploaded, failed
    try:
        # Ensure directory exists
        remote_dir = os.path.dirname(remote_file)
        if remote_dir:
            ensure_remote_dir(ftp, remote_dir)
        
        # Upload file
        with open(local_file, 'rb') as f:
            ftp.storbinary(f'STOR {remote_file}', f)
        uploaded += 1
        return True
    except Exception as e:
        failed.append((local_file, str(e)))
        return False

def main():
    global uploaded, failed
    
    print("=" * 50)
    print("TrueVault VPN - FTP Upload")
    print("=" * 50)
    print(f"Host: {FTP_HOST}")
    print(f"Local: {LOCAL_PATH}")
    print(f"Remote: {REMOTE_PATH}")
    print()
    
    # Connect
    print("Connecting to FTP...")
    ftp = ftplib.FTP()
    ftp.connect(FTP_HOST, FTP_PORT)
    ftp.login(FTP_USER, FTP_PASS)
    print("Connected!")
    print()
    
    # Get list of files to upload
    extensions = ('.php', '.html', '.css', '.js', '.md', '.txt', '.htaccess', '.ini')
    files_to_upload = []
    
    for root, dirs, files in os.walk(LOCAL_PATH):
        # Skip certain directories
        skip_dirs = ['chat_log', '__pycache__', '.git']
        dirs[:] = [d for d in dirs if d not in skip_dirs]
        
        for file in files:
            if file.endswith(extensions) or file == '.htaccess' or file == '.user.ini':
                local_file = os.path.join(root, file)
                relative = os.path.relpath(local_file, LOCAL_PATH)
                remote_file = REMOTE_PATH + '/' + relative.replace('\\', '/')
                files_to_upload.append((local_file, remote_file))
    
    print(f"Found {len(files_to_upload)} files to upload")
    print()
    
    # Upload files
    for i, (local_file, remote_file) in enumerate(files_to_upload, 1):
        rel_path = os.path.relpath(local_file, LOCAL_PATH)
        print(f"[{i}/{len(files_to_upload)}] {rel_path}...", end=" ")
        
        if upload_file(ftp, local_file, remote_file):
            print("OK")
        else:
            print("FAILED")
    
    # Disconnect
    ftp.quit()
    
    # Summary
    print()
    print("=" * 50)
    print(f"UPLOAD COMPLETE")
    print(f"  Uploaded: {uploaded}")
    print(f"  Failed: {len(failed)}")
    
    if failed:
        print()
        print("Failed files:")
        for f, err in failed[:10]:
            print(f"  - {os.path.basename(f)}: {err}")

if __name__ == "__main__":
    main()
