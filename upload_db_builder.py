from ftplib import FTP

ftp = FTP('the-truth-publishing.com')
ftp.login('kahlen@the-truth-publishing.com', 'AndassiAthena8')
ftp.cwd('/public_html/vpn.the-truth-publishing.com')

# Create admin directory
try:
    ftp.mkd('admin')
except:
    pass

ftp.cwd('admin')

# Create database-builder directory  
try:
    ftp.mkd('database-builder')
except:
    pass

ftp.cwd('database-builder')

# Create databases directory
try:
    ftp.mkd('databases')
except:
    pass

# Upload setup-builder.php
with open(r'E:\Documents\GitHub\truevault-vpn\admin\database-builder\setup-builder.php', 'rb') as f:
    ftp.storbinary('STOR setup-builder.php', f)

print('UPLOADED')
ftp.quit()
