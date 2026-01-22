#!/usr/bin/env python3
"""FTP Upload Script for TrueVault VPN"""

import ftplib
import os
from pathlib import Path

# FTP Configuration
FTP_HOST = "the-truth-publishing.com"
FTP_USER = "kahlen@the-truth-publishing.com"
FTP_PASS = "AndassiAthena8"
LOCAL_PATH = r"E:\Documents\GitHub\truevault-vpn\website"
REMOTE_PATH = "/vpn.the-truth-publishing.com"

# Files to upload (Part 6A files)
FILES_TO_UPLOAD = [
    ("sw.js", "/sw.js"),
    ("manifest.json", "/manifest.json"),
    ("offline.html", "/offline.html"),
    ("mobile/cameras.php", "/mobile/cameras.php"),
    ("api/motion.php", "/api/motion.php"),
    ("api/motion-detector.php", "/api/motion-detector.php"),
    ("api/cameras.php", "/api/cameras.php"),
    ("api/recordings.php", "/api/recordings.php"),
    ("api/camera-stream.php", "/api/camera-stream.php"),
    ("dashboard/cameras.php", "/dashboard/cameras.php"),
    ("dashboard/recordings.php", "/dashboard/recordings.php"),
    ("dashboard/motion.php", "/dashboard/motion.php"),
    ("includes/CloudBypass.php", "/includes/CloudBypass.php"),
    ("admin/setup-camera-tables.php", "/admin/setup-camera-tables.php"),
]

def ensure_remote_dir(ftp, path):
    """Create remote directory if it doesn't exist"""
    dirs = path.split('/')
    current = ""
    for d in dirs:
        if d:
            current += "/" + d
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
    try:
        # Ensure directory exists
        remote_dir = os.path.dirname(REMOTE_PATH + remote_file)
        ensure_remote_dir(ftp, remote_dir)
        
        # Upload file
        full_remote = REMOTE_PATH + remote_file
        with open(local_file, 'rb') as f:
            ftp.storbinary(f'STOR {full_remote}', f)
        print(f"[OK] Uploaded: {remote_file}")
        return True
    except Exception as e:
        print(f"[FAIL] {remote_file} - {e}")
        return False

def main():
    print("=" * 50)
    print("TrueVault VPN - FTP Upload")
    print("=" * 50)
    
    try:
        # Connect to FTP
        print(f"\nConnecting to {FTP_HOST}...")
        ftp = ftplib.FTP(FTP_HOST)
        ftp.login(FTP_USER, FTP_PASS)
        print(f"[OK] Connected as {FTP_USER}")
        
        # Upload files
        print(f"\nUploading {len(FILES_TO_UPLOAD)} files...")
        success = 0
        failed = 0
        
        for local_rel, remote_rel in FILES_TO_UPLOAD:
            local_full = os.path.join(LOCAL_PATH, local_rel.replace('/', os.sep))
            if os.path.exists(local_full):
                if upload_file(ftp, local_full, remote_rel):
                    success += 1
                else:
                    failed += 1
            else:
                print(f"[SKIP] Not found: {local_rel}")
        
        # Disconnect
        ftp.quit()
        
        print(f"\n" + "=" * 50)
        print(f"Upload Complete!")
        print(f"  Success: {success}")
        print(f"  Failed: {failed}")
        print("=" * 50)
        
    except Exception as e:
        print(f"\n[ERROR] Connection failed: {e}")

if __name__ == "__main__":
    main()
