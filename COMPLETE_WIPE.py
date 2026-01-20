#!/usr/bin/env python3
"""
TrueVault VPN - COMPLETE SERVER WIPE
Delete EVERYTHING from production server
Start completely fresh - 5th time's the charm
"""

import ftplib
import sys

FTP_HOST = 'the-truth-publishing.com'
FTP_USER = 'kahlen@the-truth-publishing.com'
FTP_PASS = 'AndassiAthena8'
FTP_PATH = '/public_html/vpn.the-truth-publishing.com'

# KEEP ONLY THESE (uploaded files from users, if any)
KEEP_FILES = [
    # Add any user-uploaded files here if needed
]

def delete_everything(ftp, path):
    """Delete EVERYTHING in the directory"""
    try:
        items = []
        ftp.retrlines(f'LIST {path}', items.append)
        
        for item in items:
            parts = item.split()
            if len(parts) < 9:
                continue
            
            name = ' '.join(parts[8:])
            if name in ['.', '..']:
                continue
            
            # Check if we should keep this
            if name in KEEP_FILES:
                print(f'  ⏭️  KEEPING: {name}')
                continue
            
            full_path = f'{path}/{name}' if path != '.' else name
            is_dir = item.startswith('d')
            
            if is_dir:
                # Recurse into directory
                delete_everything(ftp, full_path)
                try:
                    ftp.rmd(full_path)
                    print(f'  ✅ Deleted directory: {full_path}/')
                except Exception as e:
                    print(f'  ❌ Error deleting directory {full_path}: {e}')
            else:
                # Delete file
                try:
                    ftp.delete(full_path)
                    print(f'  ✅ Deleted file: {full_path}')
                except Exception as e:
                    print(f'  ❌ Error deleting file {full_path}: {e}')
                    
    except Exception as e:
        print(f'  ❌ Error processing {path}: {e}')

def main():
    print('='*70)
    print(' WARNING: COMPLETE SERVER WIPE')
    print('='*70)
    print()
    print('This will DELETE EVERYTHING from:')
    print(f'  Server: {FTP_HOST}')
    print(f'  Path: {FTP_PATH}')
    print()
    print('This is the 5TH rebuild. We will do it RIGHT this time.')
    print()
    print('⚠️  THIS CANNOT BE UNDONE ⚠️')
    print()
    
    confirm1 = input('Type "DELETE EVERYTHING" to proceed: ')
    if confirm1 != 'DELETE EVERYTHING':
        print('Cancelled.')
        return
    
    print()
    confirm2 = input('Are you ABSOLUTELY SURE? Type "YES DELETE ALL": ')
    if confirm2 != 'YES DELETE ALL':
        print('Cancelled.')
        return
    
    print()
    print('Connecting to FTP...')
    
    try:
        ftp = ftplib.FTP(FTP_HOST)
        ftp.login(FTP_USER, FTP_PASS)
        ftp.cwd(FTP_PATH)
        print('✅ Connected to production server')
        print()
        
        print('🔥 DELETING EVERYTHING... 🔥')
        print()
        delete_everything(ftp, '.')
        
        print()
        print('='*70)
        print('✅ SERVER WIPED CLEAN')
        print('='*70)
        print()
        print('The server is now empty.')
        print('Ready to rebuild from Part 1, Step 1.')
        print()
        
        ftp.quit()
        
    except Exception as e:
        print(f'❌ FTP Error: {e}')
        sys.exit(1)

if __name__ == '__main__':
    main()
