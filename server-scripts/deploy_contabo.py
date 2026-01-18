import paramiko
import time

def setup_and_deploy(host, server_name, subnet_base, api_secret):
    user = 'root'
    password = 'Andassi8'
    
    # Read local api.py
    api_path = r'E:\Documents\GitHub\truevault-vpn\server-scripts\api.py'
    with open(api_path, 'r') as f:
        api_content = f.read()
    
    print(f'Deploying API to {server_name} ({host})...')
    
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(host, username=user, password=password, timeout=15)
    
    # Install python3-venv first
    print('  Installing python3-venv...')
    stdin, stdout, stderr = ssh.exec_command('apt update && apt install -y python3-venv python3-pip')
    out = stdout.read().decode()
    print('  python3-venv installed')
    
    # Create directory
    print('  Creating /opt/truevault...')
    ssh.exec_command('mkdir -p /opt/truevault')
    
    # Upload api.py via SFTP
    sftp = ssh.open_sftp()
    with sftp.file('/opt/truevault/api.py', 'w') as f:
        f.write(api_content)
    print('  Uploaded api.py')
    
    # Create .env file
    env_content = f'''SERVER_NAME={server_name}
SERVER_IP={host}
WG_PORT=51820
API_PORT=8443
SUBNET_BASE={subnet_base}
DNS=1.1.1.1, 1.0.0.1
API_SECRET={api_secret}
DB_PATH=/opt/truevault/peers.db
'''
    with sftp.file('/opt/truevault/.env', 'w') as f:
        f.write(env_content)
    print('  Created .env')
    sftp.close()
    
    # Create venv and install packages
    print('  Creating virtual environment and installing packages...')
    stdin, stdout, stderr = ssh.exec_command(
        'cd /opt/truevault && '
        'rm -rf venv && '
        'python3 -m venv venv && '
        '/opt/truevault/venv/bin/pip install --upgrade pip && '
        '/opt/truevault/venv/bin/pip install flask qrcode pillow'
    )
    out = stdout.read().decode()
    err = stderr.read().decode()
    if 'Successfully' in out:
        print('  Packages installed successfully')
    else:
        print(f'  Install output: ...{out[-200:]}')
    
    # Create systemd service
    service_content = '''[Unit]
Description=TrueVault VPN Key Management API
After=network.target wg-quick@wg0.service

[Service]
Type=simple
User=root
WorkingDirectory=/opt/truevault
EnvironmentFile=/opt/truevault/.env
ExecStart=/opt/truevault/venv/bin/python /opt/truevault/api.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
'''
    stdin, stdout, stderr = ssh.exec_command(f'cat > /etc/systemd/system/truevault-api.service << \'ENDSERVICE\'\n{service_content}ENDSERVICE')
    stdout.read()
    print('  Created systemd service')
    
    # Reload, enable and start
    print('  Starting API service...')
    ssh.exec_command('systemctl daemon-reload')
    ssh.exec_command('systemctl enable truevault-api')
    stdin, stdout, stderr = ssh.exec_command('systemctl restart truevault-api')
    stdout.read()
    
    # Wait and check status
    time.sleep(3)
    stdin, stdout, stderr = ssh.exec_command('systemctl is-active truevault-api')
    status = stdout.read().decode().strip()
    print(f'  Service status: {status}')
    
    # Open firewall
    ssh.exec_command('ufw allow 8443/tcp 2>/dev/null || iptables -A INPUT -p tcp --dport 8443 -j ACCEPT 2>/dev/null || true')
    print('  Firewall configured')
    
    # Test API
    stdin, stdout, stderr = ssh.exec_command('curl -s http://localhost:8443/api/health')
    health = stdout.read().decode()
    print(f'  Health check: {health[:100]}')
    
    ssh.close()
    return status == 'active'

if __name__ == '__main__':
    print('='*60)
    ny_ok = setup_and_deploy(
        '66.94.103.91', 
        'new-york', 
        '10.8.0',
        'TrueVault2026NYSecretKey32Chars!'
    )
    print('='*60)
    print()
    
    print('='*60)
    stl_ok = setup_and_deploy(
        '144.126.133.253',
        'st-louis',
        '10.8.1', 
        'TrueVault2026STLSecretKey32Char!'
    )
    print('='*60)
    
    print()
    print('DEPLOYMENT SUMMARY:')
    print(f'  New York:   {"SUCCESS" if ny_ok else "FAILED"}')
    print(f'  St. Louis:  {"SUCCESS" if stl_ok else "FAILED"}')
