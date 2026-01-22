#!/usr/bin/env python3
"""
Check what's deployed on the VPN servers
"""
import sys
sys.stdout.reconfigure(encoding='utf-8')

import paramiko

SERVERS = {
    'ny': {'host': '66.94.103.91', 'name': 'New York (Contabo)'},
    'stl': {'host': '144.126.133.253', 'name': 'St. Louis VIP (Contabo)'},
}

def ssh_check(host, commands):
    """Execute commands via SSH"""
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    
    try:
        client.connect(host, username='root', password='Andassi8', timeout=30)
        results = []
        for cmd in commands:
            stdin, stdout, stderr = client.exec_command(cmd)
            output = stdout.read().decode('utf-8').strip()
            error = stderr.read().decode('utf-8').strip()
            results.append((cmd, output, error))
        client.close()
        return results
    except Exception as e:
        return [('connection', None, str(e))]

def check_server(key):
    server = SERVERS[key]
    print(f"\n{'='*70}")
    print(f"CHECKING: {server['name']} ({server['host']})")
    print('='*70)
    
    commands = [
        # Check WireGuard interface
        "wg show wg0 2>/dev/null || echo 'WG NOT RUNNING'",
        # Check TrueVault API folder
        "ls /opt/truevault/ 2>/dev/null || echo 'NO /opt/truevault'",
        # Check API service status
        "systemctl is-active truevault-api 2>/dev/null || echo 'SERVICE NOT FOUND'",
        # Check public key
        "cat /etc/wireguard/server_public.key 2>/dev/null || echo 'NO PUBLIC KEY FILE'",
        # Check listening ports
        "ss -tlnp 2>/dev/null | grep -E '8443|51820' || echo 'PORTS NOT LISTENING'",
        # Check peers database
        "ls -la /opt/truevault/peers.db 2>/dev/null || echo 'NO PEERS DB'",
        # Test API endpoint
        "curl -s http://localhost:8443/api/health 2>/dev/null || echo 'API NOT RESPONDING'",
        # Get server info
        "curl -s http://localhost:8443/api/server-info 2>/dev/null || echo 'SERVER-INFO FAILED'",
    ]
    
    results = ssh_check(server['host'], commands)
    
    for cmd, output, error in results:
        # Simplify command display
        short_cmd = cmd.split('||')[0].strip()[:50]
        print(f"\n[{short_cmd}...]")
        if output:
            # Replace problematic characters
            clean = output.replace('\u25cf', '*')
            print(clean[:500])
        if error and 'not found' not in error.lower() and 'NO ' not in output:
            print(f"ERR: {error[:200]}")

if __name__ == '__main__':
    for key in SERVERS:
        check_server(key)
    print("\n" + "="*70)
    print("DONE")
