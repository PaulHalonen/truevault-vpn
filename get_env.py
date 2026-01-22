#!/usr/bin/env python3
"""Get .env files from servers"""
import sys
sys.stdout.reconfigure(encoding='utf-8')
import paramiko

SERVERS = [
    ('66.94.103.91', 'New York'),
    ('144.126.133.253', 'St. Louis VIP'),
]

for host, name in SERVERS:
    print(f"\n{name} ({host}) .env:")
    print("-"*50)
    
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(host, username='root', password='Andassi8', timeout=30)
    stdin, stdout, stderr = client.exec_command("cat /opt/truevault/.env")
    content = stdout.read().decode('utf-8')
    client.close()
    print(content)
