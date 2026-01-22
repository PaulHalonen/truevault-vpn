#!/usr/bin/env python3
"""
Check deployed scripts on VPN servers
"""
import sys
sys.stdout.reconfigure(encoding='utf-8')

import paramiko

SERVERS = {
    'ny': {'host': '66.94.103.91', 'name': 'New York'},
    'stl': {'host': '144.126.133.253', 'name': 'St. Louis VIP'},
}

def ssh_exec(host, cmd):
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        client.connect(host, username='root', password='Andassi8', timeout=30)
        stdin, stdout, stderr = client.exec_command(cmd)
        out = stdout.read().decode('utf-8').strip()
        client.close()
        return out
    except Exception as e:
        return f"ERROR: {e}"

print("="*70)
print("CHECKING VPN SERVER DEPLOYMENTS")
print("="*70)

for key, srv in SERVERS.items():
    print(f"\n>>> {srv['name']} ({srv['host']})")
    print("-"*50)
    
    # Check WireGuard status
    wg = ssh_exec(srv['host'], "wg show wg0 2>/dev/null | head -5")
    print(f"WireGuard: {wg[:200] if wg else 'NOT RUNNING'}")
    
    # Check API files
    files = ssh_exec(srv['host'], "ls -la /opt/truevault/ 2>/dev/null")
    print(f"\n/opt/truevault/:\n{files[:500] if files else 'NOT FOUND'}")
    
    # Check API script content (first 50 lines)
    api = ssh_exec(srv['host'], "head -50 /opt/truevault/api.py 2>/dev/null")
    print(f"\napi.py (first 50 lines):\n{api[:1000] if api else 'NOT FOUND'}")
    
    # Check if API is running
    port = ssh_exec(srv['host'], "ss -tlnp | grep 8443")
    print(f"\nPort 8443: {port if port else 'NOT LISTENING'}")
    
    # Test API health endpoint
    health = ssh_exec(srv['host'], "curl -s http://localhost:8443/api/health 2>/dev/null")
    print(f"API Health: {health if health else 'NO RESPONSE'}")
    
    # Test create-peer endpoint exists
    info = ssh_exec(srv['host'], "curl -s http://localhost:8443/api/server-info 2>/dev/null")
    print(f"Server Info: {info if info else 'NO RESPONSE'}")

print("\n" + "="*70)
print("DONE")
