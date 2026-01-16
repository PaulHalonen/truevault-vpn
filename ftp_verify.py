"""
FTP Verify - List all files
"""
from ftplib import FTP

FTP_HOST = "the-truth-publishing.com"
FTP_USER = "kahlen@the-truth-publishing.com"
FTP_PASS = "AndassiAthena8"
FTP_ROOT = "/public_html/vpn.the-truth-publishing.com"

print("Connecting...")
ftp = FTP(FTP_HOST)
ftp.login(FTP_USER, FTP_PASS)
print("Connected!\n")

# Check dashboard folder
print("=== dashboard/ ===")
ftp.cwd(FTP_ROOT + "/dashboard")
for f in ftp.nlst():
    print(f"  {f}")

# Check api/devices folder
print("\n=== api/devices/ ===")
ftp.cwd(FTP_ROOT + "/api/devices")
for f in ftp.nlst():
    print(f"  {f}")

# Check api/servers folder
print("\n=== api/servers/ ===")
ftp.cwd(FTP_ROOT + "/api/servers")
for f in ftp.nlst():
    print(f"  {f}")

ftp.quit()
print("\n[OK] All verified!")
