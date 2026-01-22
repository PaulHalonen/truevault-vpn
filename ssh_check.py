#!/usr/bin/env python3
"""
SSH Server Check Script - Check WireGuard status on VPN servers
"""

import paramiko
import sys

# Server configurations
SERVERS = {
    'ny': {
        'name': 'New York (Contabo)',
        'host': '66.94.103.91',
        'user': 'root',
        'password': 'Andassi8'
    },
    'stl': {
        'name': 'St. Louis VIP (Contabo)',
        'host': '144.126.133.253',
        'user': 'root',
        'password': 'Andassi8'
    }
}

def ssh_execute(host, user, password, command):
    """Execute command via SSH and return output"""
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    
    try:
        client.connect(host, username=user, password=password, timeout=30)
        stdin, stdout, stderr = client.exec_command(command)
        output = stdout.read().decode('utf-8')
        error = stderr.read().decode('utf-8')
        client.close()
        return output, error
    except Exception as e:
        return None, str(e)

def check_server(server_key):
    """Check a single server"""
    server = SERVERS[server_key]
    print(f"\n{'='*60}")
    print(f"Checking: {server['name']} ({server['host']})")
    print('='*60)
    
    # Check WireGuard installation
    print("\n1. Checking WireGuard installation...")
    output, error = ssh_execute(server['host'], server['user'], server['password'], 
                                 "which wg && wg --version")
    if output:
        print(f"   [OK] WireGuard installed: {output.strip()}")
    else:
        print(f"   [FAIL] WireGuard NOT found: {error}")
        return
    
    # Check WireGuard status
    print("\n2. WireGuard interface status...")
    output, error = ssh_execute(server['host'], server['user'], server['password'], 
                                 "wg show 2>/dev/null || echo 'No active interface'")
    print(output if output else f"   Error: {error}")
    
    # Check config file
    print("\n3. WireGuard config file...")
    output, error = ssh_execute(server['host'], server['user'], server['password'], 
                                 "ls -la /etc/wireguard/ 2>/dev/null && head -20 /etc/wireguard/wg0.conf 2>/dev/null || echo 'No config found'")
    print(output if output else f"   Error: {error}")
    
    # Check server public key
    print("\n4. Server public key...")
    output, error = ssh_execute(server['host'], server['user'], server['password'], 
                                 "cat /etc/wireguard/publickey 2>/dev/null || wg pubkey < /etc/wireguard/privatekey 2>/dev/null || echo 'No key found'")
    print(f"   Public Key: {output.strip() if output else 'Not found'}")
    
    # Check if scripts exist
    print("\n5. Checking for existing scripts...")
    output, error = ssh_execute(server['host'], server['user'], server['password'], 
                                 "ls -la /root/*.sh /opt/*.sh /home/*.sh 2>/dev/null || echo 'No scripts found in common locations'")
    print(output if output else "   No scripts found")
    
    # Check system info
    print("\n6. System info...")
    output, error = ssh_execute(server['host'], server['user'], server['password'], 
                                 "uname -a && free -h | head -2 && df -h / | tail -1")
    print(output if output else f"   Error: {error}")

def main():
    if len(sys.argv) > 1:
        server_key = sys.argv[1].lower()
        if server_key in SERVERS:
            check_server(server_key)
        elif server_key == 'all':
            for key in SERVERS:
                check_server(key)
        else:
            print(f"Unknown server: {server_key}")
            print(f"Available: {', '.join(SERVERS.keys())}, all")
    else:
        print("Usage: python ssh_check.py [ny|stl|all]")
        print("\nChecking all servers...")
        for key in SERVERS:
            check_server(key)

if __name__ == '__main__':
    main()
