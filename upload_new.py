"""Upload newly created files"""
import ftplib
import os

FTP_HOST = "the-truth-publishing.com"
FTP_USER = "kahlen@the-truth-publishing.com"
FTP_PASS = "AndassiAthena8"
REMOTE_BASE = "/vpn.the-truth-publishing.com"

files = [
    (r"E:\Documents\GitHub\truevault-vpn\website\api\servers\status.php", "/api/servers/status.php"),
    (r"E:\Documents\GitHub\truevault-vpn\website\dashboard\android-setup.html", "/dashboard/android-setup.html"),
    (r"E:\Documents\GitHub\truevault-vpn\website\dashboard\ios-setup.html", "/dashboard/ios-setup.html"),
    (r"E:\Documents\GitHub\truevault-vpn\website\dashboard\desktop-setup.html", "/dashboard/desktop-setup.html"),
    (r"E:\Documents\GitHub\truevault-vpn\website\api\parental\controls.php", "/api/parental/controls.php"),
]

print("Connecting...")
ftp = ftplib.FTP()
ftp.connect(FTP_HOST, 21, timeout=30)
ftp.login(FTP_USER, FTP_PASS)
print("Connected!")

# Create directories
for d in ['/api/parental']:
    try:
        ftp.mkd(REMOTE_BASE + d)
        print(f"Created {d}")
    except:
        pass

for local, remote in files:
    full_remote = REMOTE_BASE + remote
    print(f"Uploading {os.path.basename(local)}...", end=" ")
    try:
        with open(local, 'rb') as f:
            ftp.storbinary(f'STOR {full_remote}', f)
        print("OK")
    except Exception as e:
        print(f"FAILED: {e}")

ftp.quit()
print("\nDone!")
