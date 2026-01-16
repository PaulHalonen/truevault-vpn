"""
FTP Upload - Single file retry
"""
from ftplib import FTP
import os

FTP_HOST = "the-truth-publishing.com"
FTP_USER = "kahlen@the-truth-publishing.com"
FTP_PASS = "AndassiAthena8"
FTP_ROOT = "/public_html/vpn.the-truth-publishing.com"
LOCAL_ROOT = r"E:\Documents\GitHub\truevault-vpn\website"

print("Connecting...")
ftp = FTP(FTP_HOST)
ftp.login(FTP_USER, FTP_PASS)
print("Connected!")

# Go to correct directory
ftp.cwd(FTP_ROOT + "/api/devices")
print("Current dir:", ftp.pwd())

# Upload
local_file = os.path.join(LOCAL_ROOT, "api/devices/delete.php")
print(f"Uploading {local_file}...")
with open(local_file, "rb") as f:
    ftp.storbinary("STOR delete.php", f)
print("Done!")

ftp.quit()
