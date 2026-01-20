#!/usr/bin/env python3
"""
TrueVault VPN - Delete ONLY Enterprise Directory
Surgical removal of improperly built enterprise module
"""

import ftplib

FTP_HOST = 'the-truth-publishing.com'
FTP_USER = 'kahlen@the-truth-publishing.com'
FTP_PASS = 'AndassiAthena8'
FTP_PATH = '/public_html/vpn.the-truth-publishing.com'

def delete_directory_recursive(ftp, path):
    """Recursively delete directory and all contents"""
    try:
        print(f'  Scanning: {path}/')
        
        items = []
        try:
            ftp.retrlines(f'LIST {path}', items.append)
        except Exception as e:
            print(f'  Error listing {path}: {e}')
            return
        
        for item in items:
            parts = item.split()
            if len(parts) < 9:
                continue
            
            name = ' '.join(parts[8:])
            if name in ['.', '..']:
                continue
            
            full_path = f'{path}/{name}'
            is_dir = item.startswith('d')
            
            if is_dir:
                delete_directory_recursive(ftp, full_path)
            else:
                try:
                    ftp.delete(full_path)
                    print(f'    ✅ Deleted file: {full_path}')
                except Exception as e:
                    print(f'    ❌ Error deleting {full_path}: {e}')
        
        try:
            ftp.rmd(path)
            print(f'  ✅ Deleted directory: {path}/')
        except Exception as e:
            print(f'  ❌ Error removing {path}/: {e}')
            
    except Exception as e:
        print(f'  ❌ Error processing {path}: {e}')

def main():
    print('='*60)
    print('Delete ONLY Enterprise Directory')
    print('='*60)
    print()
    print('This will delete:')
    print('  ❌ /enterprise/')
    print()
    print('This will KEEP:')
    print('  ✅ Everything else (Parts 1-18)')
    print()
    
    confirm = input('Type "DELETE ENTERPRISE" to proceed: ')
    if confirm != 'DELETE ENTERPRISE':
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
        
        print('--- Deleting: /enterprise/ ---')
        delete_directory_recursive(ftp, 'enterprise')
        print()
        
        print('='*60)
        print('✅ Enterprise Deleted!')
        print('='*60)
        print()
        print('Next: Rebuild enterprise properly using checklist')
        print()
        
        ftp.quit()
        
    except Exception as e:
        print(f'❌ FTP Error: {e}')

if __name__ == '__main__':
    main()
