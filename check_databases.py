import ftplib
import os
import sys

# FTP credentials
FTP_HOST = 'the-truth-publishing.com'
FTP_USER = 'kahlen@the-truth-publishing.com'
FTP_PASS = 'AndassiAthena8'
FTP_PORT = 21

def main():
    print("Checking databases directory...")
    
    ftp = ftplib.FTP()
    ftp.connect(FTP_HOST, FTP_PORT)
    ftp.login(FTP_USER, FTP_PASS)
    
    base = '/public_html/vpn.the-truth-publishing.com'
    
    # Check and create databases directory
    try:
        ftp.cwd(f'{base}/databases')
        print("databases/ directory exists")
    except:
        try:
            ftp.mkd(f'{base}/databases')
            print("Created databases/ directory")
        except Exception as e:
            print(f"Could not create databases/: {e}")
    
    # List contents
    try:
        ftp.cwd(f'{base}/databases')
        files = ftp.nlst()
        print(f"Contents: {files}")
    except Exception as e:
        print(f"Error listing: {e}")
    
    ftp.quit()
    print("Done")

if __name__ == '__main__':
    main()
