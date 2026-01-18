#!/usr/bin/env python3
"""
Check Contabo servers for WireGuard keys
"""

import paramiko
import time

servers = [
    {
        'name': 'New York (Shared)',
        'ip': '66.94.103.91',
        'user': 'root',
        'password': 'Andassi8'
    },
    {
        'name': 'St. Louis (VIP)',
        'ip': '144.126.133.253',
        'user': 'root',
        'password': 'Andassi8'
    }
]

def check_server(server):
    print(f"\n{'='*60}")
    print(f"Checking {server['name']} ({server['ip']})")
    print('='*60)
    
    try:
        # Create SSH client
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        
        # Connect
        print(f"Connecting to {server['ip']}...")
        ssh.connect(
            server['ip'],
            username=server['user'],
            password=server['password'],
            timeout=10
        )
        print("[OK] Connected!")
        
        # Check WireGuard installation
        print("\nChecking WireGuard installation...")
        stdin, stdout, stderr = ssh.exec_command('which wg')
        wg_path = stdout.read().decode().strip()
        if wg_path:
            print(f"[OK] WireGuard installed at: {wg_path}")
        else:
            print("[FAIL] WireGuard not found")
            
        # Check for config files
        print("\nChecking for WireGuard config files...")
        stdin, stdout, stderr = ssh.exec_command('ls -la /etc/wireguard/ 2>/dev/null')
        config_list = stdout.read().decode()
        if config_list:
            print("Config directory contents:")
            print(config_list)
        else:
            print("No /etc/wireguard/ directory found")
            
        # Try to find any .conf files
        print("\nSearching for .conf files...")
        stdin, stdout, stderr = ssh.exec_command('find /etc -name "*.conf" -path "*wireguard*" 2>/dev/null')
        conf_files = stdout.read().decode().strip()
        if conf_files:
            print("Found config files:")
            print(conf_files)
        else:
            print("No WireGuard config files found")
            
        # Check if WireGuard interface is running
        print("\nChecking for active WireGuard interfaces...")
        stdin, stdout, stderr = ssh.exec_command('wg show 2>/dev/null')
        wg_output = stdout.read().decode().strip()
        if wg_output:
            print("Active WireGuard interfaces:")
            print(wg_output)
        else:
            print("No active WireGuard interfaces")
            
        # Check for keys in /root
        print("\nSearching for key files in /root...")
        stdin, stdout, stderr = ssh.exec_command('find /root -name "*key*" -o -name "wg*" 2>/dev/null | head -20')
        key_files = stdout.read().decode().strip()
        if key_files:
            print("Found key-related files:")
            print(key_files)
        else:
            print("No key files found in /root")
            
        # Try to read public key if it exists
        print("\nAttempting to read public key...")
        stdin, stdout, stderr = ssh.exec_command('cat /etc/wireguard/publickey 2>/dev/null || cat /root/publickey 2>/dev/null || echo "No public key file found"')
        pubkey = stdout.read().decode().strip()
        print(f"Public key: {pubkey}")
        
        ssh.close()
        print(f"\n[OK] Disconnected from {server['name']}")
        
        return pubkey if pubkey and pubkey != "No public key file found" else None
        
    except Exception as e:
        print(f"\n[ERROR] Error connecting to {server['name']}: {str(e)}")
        return None

# Check all servers
results = {}
for server in servers:
    pubkey = check_server(server)
    results[server['name']] = pubkey
    time.sleep(1)

# Summary
print("\n" + "="*60)
print("SUMMARY")
print("="*60)
for name, key in results.items():
    print(f"{name}: {key if key else 'NOT FOUND'}")
