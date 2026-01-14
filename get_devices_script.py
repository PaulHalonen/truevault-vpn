import ftplib
from io import BytesIO
import sys

if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8')

ftp = ftplib.FTP('the-truth-publishing.com')
ftp.login('kahlen@the-truth-publishing.com', 'AndassiAthena8')
base = '/public_html/vpn.the-truth-publishing.com'

def get_file(path):
    buffer = BytesIO()
    try:
        ftp.retrbinary(f'RETR {path}', buffer.write)
        return buffer.getvalue().decode('utf-8', errors='ignore')
    except:
        return None

# Get devices.html script section
content = get_file(base + '/public/dashboard/devices.html')
if content:
    # Find script section
    script_start = content.find('<script>')
    script_end = content.find('</script>', script_start)
    if script_start > 0:
        script_content = content[script_start:script_end+9]
        print("=" * 70)
        print("DEVICES.HTML SCRIPT SECTION (Full)")
        print("=" * 70)
        print(script_content)

ftp.quit()
