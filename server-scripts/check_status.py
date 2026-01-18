import urllib.request
import json

servers = [
    ('New York', 'http://66.94.103.91:8443/api/server-info'),
    ('St. Louis VIP', 'http://144.126.133.253:8443/api/server-info')
]

print('='*60)
print('TRUEVAULT VPN - SERVER STATUS CHECK')
print('='*60)
print()

for name, url in servers:
    try:
        r = urllib.request.urlopen(url, timeout=10)
        data = json.loads(r.read().decode())
        print(f'{name}:')
        print(f'  Status: ONLINE')
        print(f'  IP: {data["ip"]}')
        print(f'  Port: {data["port"]}')
        print(f'  Public Key: {data["public_key"]}')
        print(f'  Subnet: {data["subnet"]}')
        print()
    except Exception as e:
        print(f'{name}: ERROR - {e}')
        print()

print('='*60)
