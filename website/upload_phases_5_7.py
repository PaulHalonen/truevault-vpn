#!/usr/bin/env python3
"""
Upload Phase 5-7 files to TrueVault VPN server
"""
import ftplib
import os

# FTP Configuration
FTP_HOST = "the-truth-publishing.com"
FTP_USER = "kahlen@the-truth-publishing.com"
FTP_PASS = "AndassiAthena8"
FTP_PORT = 21
REMOTE_BASE = "/public_html/vpn.the-truth-publishing.com"

# Files to upload (relative to website/ folder)
FILES_TO_UPLOAD = [
    # Phase 5: PayPal Billing
    ("includes/PayPal.php", "/includes/PayPal.php"),
    ("api/billing/checkout.php", "/api/billing/checkout.php"),
    ("api/billing/plans.php", "/api/billing/plans.php"),
    ("api/billing/subscription.php", "/api/billing/subscription.php"),
    ("api/billing/webhook.php", "/api/billing/webhook.php"),
    
    # Phase 6: Admin CMS
    ("admin/index.html", "/admin/index.html"),
    ("admin/.htaccess", "/admin/.htaccess"),
    ("admin/api/auth.php", "/admin/api/auth.php"),
    ("admin/api/stats.php", "/admin/api/stats.php"),
    ("admin/api/users.php", "/admin/api/users.php"),
    ("admin/api/servers.php", "/admin/api/servers.php"),
    ("admin/api/settings.php", "/admin/api/settings.php"),
    ("admin/api/devices.php", "/admin/api/devices.php"),
    ("admin/api/logs.php", "/admin/api/logs.php"),
    ("admin/api/billing.php", "/admin/api/billing.php"),
    
    # Phase 7: User Dashboard
    ("login.html", "/login.html"),
    ("dashboard/index.html", "/dashboard/index.html"),
    ("dashboard/billing.html", "/dashboard/billing.html"),
    ("dashboard/settings.html", "/dashboard/settings.html"),
    ("dashboard/support.html", "/dashboard/support.html"),
    ("dashboard/payment-success.html", "/dashboard/payment-success.html"),
    ("dashboard/payment-cancel.html", "/dashboard/payment-cancel.html"),
    ("dashboard/.htaccess", "/dashboard/.htaccess"),
    ("api/user/profile.php", "/api/user/profile.php"),
    ("api/user/password.php", "/api/user/password.php"),
    ("api/user/delete.php", "/api/user/delete.php"),
    ("api/support/tickets.php", "/api/support/tickets.php"),
    ("api/auth/login.php", "/api/auth/login.php"),
    ("api/auth/register.php", "/api/auth/register.php"),
    ("api/auth/verify.php", "/api/auth/verify.php"),
]

def upload_files():
    base_path = os.path.dirname(os.path.abspath(__file__))
    
    print(f"Connecting to {FTP_HOST}...")
    ftp = ftplib.FTP()
    ftp.connect(FTP_HOST, FTP_PORT)
    ftp.login(FTP_USER, FTP_PASS)
    print("[OK] Connected!")
    
    uploaded = 0
    failed = 0
    
    for local_file, remote_file in FILES_TO_UPLOAD:
        local_path = os.path.join(base_path, local_file)
        remote_path = REMOTE_BASE + remote_file
        
        try:
            # Create directory if needed
            remote_dir = os.path.dirname(remote_path)
            try:
                ftp.mkd(remote_dir)
            except:
                pass  # Directory might already exist
            
            # Upload file
            with open(local_path, 'rb') as f:
                ftp.storbinary(f'STOR {remote_path}', f)
            print(f"[OK] {remote_file}")
            uploaded += 1
            
        except Exception as e:
            print(f"[FAIL] {remote_file}: {e}")
            failed += 1
    
    ftp.quit()
    print(f"\nUpload Complete: {uploaded} succeeded, {failed} failed")

if __name__ == "__main__":
    upload_files()
