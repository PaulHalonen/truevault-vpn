import paramiko

def fix_server(host, name):
    print(f'Fixing {name} ({host})...')
    
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(host, username='root', password='Andassi8', timeout=15)
    
    # Kill whatever's on 8443
    print('  Killing processes on port 8443...')
    ssh.exec_command('fuser -k 8443/tcp 2>/dev/null || true')
    ssh.exec_command('pkill -f "python.*api.py" 2>/dev/null || true')
    
    import time
    time.sleep(2)
    
    # Restart service
    print('  Restarting truevault-api service...')
    stdin, stdout, stderr = ssh.exec_command('systemctl restart truevault-api')
    stdout.read()
    
    time.sleep(3)
    
    # Check status
    stdin, stdout, stderr = ssh.exec_command('systemctl is-active truevault-api')
    status = stdout.read().decode().strip()
    print(f'  Service status: {status}')
    
    # Test endpoints
    stdin, stdout, stderr = ssh.exec_command('curl -s http://127.0.0.1:8443/api/health')
    health = stdout.read().decode()
    print(f'  Health: {health}')
    
    stdin, stdout, stderr = ssh.exec_command('curl -s http://127.0.0.1:8443/api/server-info')
    info = stdout.read().decode()
    print(f'  Server Info: {info}')
    
    ssh.close()
    return 'online' in health

# Fix both servers
print('='*60)
ny = fix_server('66.94.103.91', 'New York')
print('='*60)
print()
print('='*60)
stl = fix_server('144.126.133.253', 'St. Louis')
print('='*60)

print()
print('RESULTS:')
print(f'  New York:   {"OK" if ny else "FAILED"}')
print(f'  St. Louis:  {"OK" if stl else "FAILED"}')
