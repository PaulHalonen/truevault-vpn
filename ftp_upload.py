"""
FTP Upload Script for TrueVault VPN
Uploads all Phase 4 files to server
"""
from ftplib import FTP
import os

# FTP credentials
FTP_HOST = "the-truth-publishing.com"
FTP_USER = "kahlen@the-truth-publishing.com"
FTP_PASS = "AndassiAthena8"
FTP_ROOT = "/public_html/vpn.the-truth-publishing.com"

# Local path
LOCAL_ROOT = r"E:\Documents\GitHub\truevault-vpn\website"

# Files to upload (local path -> remote path)
FILES = [
    ("api/devices/delete.php", "api/devices/delete.php"),
    ("api/devices/get-config.php", "api/devices/get-config.php"),
    ("api/devices/list.php", "api/devices/list.php"),
    ("api/devices/provision.php", "api/devices/provision.php"),
    ("api/devices/switch-server.php", "api/devices/switch-server.php"),
    ("api/servers/list.php", "api/servers/list.php"),
    ("dashboard/devices.html", "dashboard/devices.html"),
    ("dashboard/setup-device.html", "dashboard/setup-device.html"),
    ("configs/config.php", "configs/config.php"),
]

def ensure_dir(ftp, path):
    """Create directory if it doesn't exist"""
    dirs = path.split("/")
    for d in dirs:
        if d:
            try:
                ftp.cwd(d)
            except:
                print(f"  Creating directory: {d}")
                ftp.mkd(d)
                ftp.cwd(d)
    # Go back to root
    ftp.cwd(FTP_ROOT)

def upload_file(ftp, local_path, remote_path):
    """Upload a single file"""
    full_local = os.path.join(LOCAL_ROOT, local_path)
    full_remote = FTP_ROOT + "/" + remote_path
    
    # Ensure remote directory exists
    remote_dir = "/".join(remote_path.split("/")[:-1])
    if remote_dir:
        ensure_dir(ftp, remote_dir)
    
    # Upload file
    print(f"Uploading: {local_path}")
    with open(full_local, "rb") as f:
        ftp.storbinary(f"STOR {full_remote}", f)
    print(f"  [OK] Done")

def main():
    print("=" * 50)
    print("TrueVault VPN - FTP Upload")
    print("=" * 50)
    
    # Connect
    print(f"\nConnecting to {FTP_HOST}...")
    ftp = FTP(FTP_HOST)
    ftp.login(FTP_USER, FTP_PASS)
    print("[OK] Connected\n")
    
    # Upload each file
    for local_path, remote_path in FILES:
        try:
            upload_file(ftp, local_path, remote_path)
        except Exception as e:
            print(f"  [ERROR] {e}")
    
    # Close
    ftp.quit()
    print("\n" + "=" * 50)
    print("Upload complete!")
    print("=" * 50)

if __name__ == "__main__":
    main()
