"""
Complete re-upload of all TrueVault files
"""
import ftplib
import os
import time

FTP_HOST = "the-truth-publishing.com"
FTP_USER = "kahlen@the-truth-publishing.com"
FTP_PASS = "AndassiAthena8"
LOCAL_PATH = r"E:\Documents\GitHub\truevault-vpn\website"
REMOTE_PATH = "/vpn.the-truth-publishing.com"

def upload_all():
    print("Connecting to FTP...")
    ftp = ftplib.FTP()
    ftp.connect(FTP_HOST, 21, timeout=60)
    ftp.login(FTP_USER, FTP_PASS)
    ftp.set_pasv(True)
    print("Connected!\n")
    
    extensions = ('.php', '.html', '.css', '.js', '.md', '.txt', '.htaccess', '.ini')
    skip_dirs = ['chat_log', '__pycache__', '.git']
    
    uploaded = 0
    failed = []
    
    for root, dirs, files in os.walk(LOCAL_PATH):
        dirs[:] = [d for d in dirs if d not in skip_dirs]
        
        for filename in files:
            if not filename.endswith(extensions) and filename not in ['.htaccess', '.user.ini']:
                continue
                
            local_file = os.path.join(root, filename)
            rel_path = os.path.relpath(local_file, LOCAL_PATH).replace('\\', '/')
            remote_file = REMOTE_PATH + '/' + rel_path
            remote_dir = os.path.dirname(remote_file)
            
            # Create directory
            try:
                ftp.cwd('/')
                for part in remote_dir.strip('/').split('/'):
                    try:
                        ftp.cwd(part)
                    except:
                        ftp.mkd(part)
                        ftp.cwd(part)
            except Exception as e:
                pass
            
            # Upload file
            print(f"[{uploaded+1}] {rel_path}...", end=" ", flush=True)
            try:
                ftp.cwd('/')
                with open(local_file, 'rb') as f:
                    ftp.storbinary(f'STOR {remote_file}', f)
                print("OK")
                uploaded += 1
            except Exception as e:
                print(f"FAILED: {e}")
                failed.append(rel_path)
            
            # Brief pause to avoid overwhelming server
            if uploaded % 20 == 0:
                time.sleep(0.5)
    
    ftp.quit()
    
    print(f"\n{'='*50}")
    print(f"COMPLETE: {uploaded} files uploaded")
    if failed:
        print(f"FAILED: {len(failed)} files")
        for f in failed[:10]:
            print(f"  - {f}")

if __name__ == "__main__":
    upload_all()
