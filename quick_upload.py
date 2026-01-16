from ftplib import FTP
ftp = FTP('the-truth-publishing.com')
ftp.login('kahlen@the-truth-publishing.com', 'AndassiAthena8')
ftp.cwd('/public_html/vpn.the-truth-publishing.com/admin')
with open(r'E:\Documents\GitHub\truevault-vpn\website\admin\update-servers.php', 'rb') as f:
    ftp.storbinary('STOR update-servers.php', f)
print('Uploaded update-servers.php!')
ftp.quit()
