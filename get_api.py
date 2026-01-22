#!/usr/bin/env python3
"""Get full api.py from server"""
import sys
sys.stdout.reconfigure(encoding='utf-8')
import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('66.94.103.91', username='root', password='Andassi8', timeout=30)
stdin, stdout, stderr = client.exec_command("cat /opt/truevault/api.py")
content = stdout.read().decode('utf-8')
client.close()

# Save to local file
with open(r'E:\Documents\GitHub\truevault-vpn\server-scripts\api.py', 'w', encoding='utf-8') as f:
    f.write(content)

print(f"Saved api.py ({len(content)} bytes)")
print("\n" + "="*60)
print("CONTENT:")
print("="*60)
print(content)
