# PRODUCTION SERVER CLEANUP PLAN
**Generated:** January 20, 2026 - 1:00 AM CST
**Purpose:** Remove all files NOT in official Master_Checklist Parts 1-11

## 🚨 CRITICAL FINDING

**Master_Checklist INDEX says:** "Total: 11 parts"
**Production server has:** Files from Parts 12-18 (UNAUTHORIZED!)

## OFFICIAL PARTS 1-11 (per INDEX.md)

### PART 1: Environment Setup
**Creates:**
- Folder structure (10 folders)
- /.htaccess
- /configs/config.php
- /databases/.htaccess

### PART 2: All 9 Databases
**Creates:**
- /admin/setup-databases.php
- 9 database files in /databases/

### PART 3: Authentication
**Creates:**
- /includes/Database.php
- /includes/JWT.php
- /includes/Validator.php
- /includes/Auth.php
- /api/auth/register.php
- /api/auth/login.php
- /api/auth/logout.php
- /api/auth/request-reset.php

### PART 4: Device Management
**Creates:**
- /dashboard/setup-device.php
- /api/devices/list.php
- /api/devices/delete.php
- /api/devices/switch-server.php
- /api/devices/generate-config.php

### PART 5: Admin & PayPal
**Creates:**
- /admin/login.php
- /admin/dashboard.php
- /admin/users.php
- /admin/settings.php
- /includes/PayPal.php
- /api/billing/create-subscription.php
- /api/billing/paypal-webhook.php

### PART 6: Advanced Features
**Creates:**
- /dashboard/port-forwarding.php
- /api/port-forwarding/list.php
- /api/port-forwarding/toggle.php
- /api/port-forwarding/delete.php
- /dashboard/parental-controls.php (basic)

### PART 7: Automation
**Creates:**
- /includes/Email.php
- /includes/EmailTemplate.php
- /includes/AutomationEngine.php
- /includes/Workflows.php
- Email templates (19 files)
- Support ticket system

### PART 8: Frontend & Transfer
**Creates:**
- Landing pages (index, pricing, features, etc.)
- User dashboard
- Business transfer wizard

### PART 9: Server Management
**Creates:**
- Server management UI
- Health monitoring
- API integrations

### PART 10: Android App
**Creates:**
- Android app files (separate repo)
- APK in /downloads/

### PART 11: Advanced Parental Controls
**Creates:**
- Enhanced parental controls
- Calendar system
- Gaming controls

---

## ❌ UNAUTHORIZED FILES ON PRODUCTION

### Files from Parts 12-18 (NOT IN INDEX):

**1. /database-builder/** - From PART 13 (NOT OFFICIAL)
   - index.php
   - designer.php
   - data-manager.php
   - api/ subfolder
   - **ACTION:** DELETE ENTIRE DIRECTORY

**2. /forms/** - From PART 14 (NOT OFFICIAL)
   - index.php
   - api.php
   - config.php
   - **ACTION:** DELETE ENTIRE DIRECTORY

**3. /marketing/** - From PART 15 (NOT OFFICIAL)
   - index.php
   - campaigns.php
   - platforms.php
   - templates.php
   - analytics.php
   - config.php
   - **ACTION:** DELETE ENTIRE DIRECTORY

**4. /support/** - May be from PART 7 (need to verify)
   - Check if Part 7 creates /support/ or different location
   - **ACTION:** VERIFY FIRST

**5. /tutorials/** - From PART 17 (NOT OFFICIAL)
   - index.php
   - view.php
   - api.php
   - config.php
   - **ACTION:** DELETE ENTIRE DIRECTORY

**6. /workflows/** - From PART 18 (NOT OFFICIAL)
   - index.php
   - view.php
   - execution.php
   - api.php
   - config.php
   - **ACTION:** DELETE ENTIRE DIRECTORY

**7. /enterprise/** - From PART 20 (NOT OFFICIAL)
   - index.php
   - clients.php
   - projects.php
   - time-tracking.php
   - api.php
   - config.php
   - **ACTION:** DELETE ENTIRE DIRECTORY

**8. Root HTML files** - From PART 12 (need to verify vs PART 8)
   - index.html
   - pricing.html
   - features.html
   - about.html
   - contact.html
   - privacy.html
   - terms.html
   - refund.html
   - **ACTION:** CHECK IF PART 8 CREATES THESE

---

## ✅ CLEANUP SCRIPT

```python
# cleanup_unauthorized_files.py
import ftplib

FTP_HOST = 'the-truth-publishing.com'
FTP_USER = 'kahlen@the-truth-publishing.com'
FTP_PASS = 'AndassiAthena8'
FTP_PATH = '/public_html/vpn.the-truth-publishing.com'

# Directories to DELETE (not in Parts 1-11)
DIRS_TO_DELETE = [
    '/database-builder',
    '/forms',
    '/marketing',
    '/tutorials',
    '/workflows',
    '/enterprise'
]

def delete_directory_recursive(ftp, path):
    \"\"\"Recursively delete a directory\"\"\"
    try:
        # List all files in directory
        items = []
        ftp.retrlines('LIST ' + path, items.append)
        
        for item in items:
            parts = item.split()
            if len(parts) < 9:
                continue
            name = ' '.join(parts[8:])
            if name in ['.', '..']:
                continue
            
            full_path = path + '/' + name
            
            if item.startswith('d'):
                # It's a directory, recurse
                delete_directory_recursive(ftp, full_path)
            else:
                # It's a file, delete it
                ftp.delete(full_path)
                print(f'Deleted file: {full_path}')
        
        # Delete the directory itself
        ftp.rmd(path)
        print(f'Deleted directory: {path}')
        
    except Exception as e:
        print(f'Error deleting {path}: {e}')

def main():
    print('Connecting to FTP...')
    ftp = ftplib.FTP(FTP_HOST)
    ftp.login(FTP_USER, FTP_PASS)
    ftp.cwd(FTP_PATH)
    
    print('\\nDeleting unauthorized directories...')
    for dir_path in DIRS_TO_DELETE:
        print(f'\\n--- Deleting: {dir_path} ---')
        delete_directory_recursive(ftp, dir_path)
    
    print('\\nCleanup complete!')
    ftp.quit()

if __name__ == '__main__':
    confirm = input('This will DELETE unauthorized files from production. Continue? (yes/no): ')
    if confirm.lower() == 'yes':
        main()
    else:
        print('Cancelled.')
```

---

## 📋 VERIFICATION NEEDED

Before running cleanup, verify:
1. Does PART 7 create /support/ directory? (check Part 7 checklist)
2. Does PART 8 create root HTML files? (check Part 8 checklist)
3. Backup production first!

## NEXT STEPS

1. ✅ Read Part 7 checklist to verify /support/
2. ✅ Read Part 8 checklist to verify HTML files
3. ✅ Backup production databases
4. ✅ Run cleanup script
5. ✅ Update BUILD_PROGRESS.md with TRUE status
6. ✅ Update HANDOFF document
7. ✅ Update Master_Checklist checkboxes
8. ✅ Git commit: "Removed unauthorized files not in Parts 1-11"
