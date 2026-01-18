import paramiko
import sys

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('66.94.103.91', username='root', password='Andassi8', timeout=15)

# Check what's on port 8443
stdin, stdout, stderr = ssh.exec_command('netstat -tlnp | grep 8443')
print('Port 8443:', stdout.read().decode())

# Check api.py file exists and size
stdin, stdout, stderr = ssh.exec_command('ls -la /opt/truevault/api.py')
print('API file:', stdout.read().decode())

# Check routes
stdin, stdout, stderr = ssh.exec_command("grep -n 'def ' /opt/truevault/api.py | head -20")
print('Functions in api.py:')
print(stdout.read().decode())

# Try calling locally
stdin, stdout, stderr = ssh.exec_command('curl -s http://127.0.0.1:8443/api/health')
print('Local health:', stdout.read().decode())

stdin, stdout, stderr = ssh.exec_command('curl -s http://127.0.0.1:8443/api/server-info')
print('Local server-info:', stdout.read().decode())

# Check service logs
stdin, stdout, stderr = ssh.exec_command('journalctl -u truevault-api -n 20 --no-pager 2>&1')
print('Service logs:')
print(stdout.read().decode()[:2000])

ssh.close()
