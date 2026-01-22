#!/usr/bin/env python3
"""Check Fly.io servers"""
import sys
sys.stdout.reconfigure(encoding='utf-8')
import urllib.request
import socket

FLYIO_SERVERS = [
    ('66.241.124.4', 'Dallas (Fly.io)'),
    ('66.241.125.247', 'Toronto (Fly.io)'),
]

print("="*70)
print("CHECKING FLY.IO SERVERS")
print("="*70)

for ip, name in FLYIO_SERVERS:
    print(f"\n>>> {name} ({ip})")
    print("-"*50)
    
    # Check if port 8443 (API) is open
    print("Checking port 8443 (API)...")
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(5)
        result = sock.connect_ex((ip, 8443))
        sock.close()
        if result == 0:
            print("  Port 8443: OPEN")
        else:
            print(f"  Port 8443: CLOSED (error {result})")
    except Exception as e:
        print(f"  Port 8443: ERROR - {e}")
    
    # Check if port 51820 (WireGuard) is open (UDP, harder to check)
    print("Checking port 51820 (WireGuard UDP)...")
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        sock.settimeout(2)
        sock.sendto(b'test', (ip, 51820))
        # UDP doesn't give feedback like TCP
        print("  Port 51820: Packet sent (UDP - can't verify if open)")
        sock.close()
    except Exception as e:
        print(f"  Port 51820: ERROR - {e}")
    
    # Try to hit the API health endpoint
    print("Checking API health endpoint...")
    try:
        url = f"http://{ip}:8443/api/health"
        req = urllib.request.Request(url, headers={'User-Agent': 'TrueVault-Check/1.0'})
        response = urllib.request.urlopen(req, timeout=10)
        data = response.read().decode('utf-8')
        print(f"  API Response: {data}")
    except urllib.error.URLError as e:
        print(f"  API: FAILED - {e.reason}")
    except socket.timeout:
        print("  API: TIMEOUT")
    except Exception as e:
        print(f"  API: ERROR - {e}")

    # Try port 8080 (Fly.io sometimes uses this)
    print("Checking port 8080 (alt)...")
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(5)
        result = sock.connect_ex((ip, 8080))
        sock.close()
        if result == 0:
            print("  Port 8080: OPEN")
        else:
            print(f"  Port 8080: CLOSED")
    except Exception as e:
        print(f"  Port 8080: ERROR - {e}")

print("\n" + "="*70)
print("DONE - Fly.io servers need deployment if API not responding")
print("="*70)
