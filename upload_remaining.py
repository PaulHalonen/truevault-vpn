"""
Upload remaining files - docs and includes
"""

import ftplib
import os

FTP_HOST = "the-truth-publishing.com"
FTP_USER = "kahlen@the-truth-publishing.com"
FTP_PASS = "AndassiAthena8"
REMOTE_BASE = "/vpn.the-truth-publishing.com"

files = [
    (r"E:\Documents\GitHub\truevault-vpn\website\docs\ADMIN_GUIDE.md", "/docs/ADMIN_GUIDE.md"),
    (r"E:\Documents\GitHub\truevault-vpn\website\docs\BUSINESS_TRANSFER.md", "/docs/BUSINESS_TRANSFER.md"),
    (r"E:\Documents\GitHub\truevault-vpn\website\docs\USER_GUIDE.md", "/docs/USER_GUIDE.md"),
    (r"E:\Documents\GitHub\truevault-vpn\website\includes\Auth.php", "/includes/Auth.php"),
    (r"E:\Documents\GitHub\truevault-vpn\website\includes\AutomationEngine.php", "/includes/AutomationEngine.php"),
    (r"E:\Documents\GitHub\truevault-vpn\website\includes\Database.php", "/includes/Database.php"),
    (r"E:\Documents\GitHub\truevault-vpn\website\includes\Email.php", "/includes/Email.php"),
    (r"E:\Documents\GitHub\truevault-vpn\website\includes\EmailTemplate.php", "/includes/EmailTemplate.php"),
    (r"E:\Documents\GitHub\truevault-vpn\website\includes\PayPal.php", "/includes/PayPal.php"),
    (r"E:\Documents\GitHub\truevault-vpn\website\includes\WireGuard.php", "/includes/WireGuard.php"),
    (r"E:\Documents\GitHub\truevault-vpn\website\includes\Workflows.php", "/includes/Workflows.php"),
]

print("Connecting...")
ftp = ftplib.FTP()
ftp.connect(FTP_HOST, 21, timeout=30)
ftp.login(FTP_USER, FTP_PASS)
print("Connected!")

# Create directories
for d in ['/docs', '/includes']:
    try:
        ftp.mkd(REMOTE_BASE + d)
        print(f"Created {d}")
    except:
        print(f"Dir {d} exists")

# Upload files
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
