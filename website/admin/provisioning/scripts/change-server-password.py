#!/usr/bin/env python3
"""
TrueVault VPN - Server Password Change Script
Uses Paramiko SSH library to change root password on VPN servers

Usage: python3 change-server-password.py <IP> <CURRENT_PASS> <NEW_PASS>

Created: January 23, 2026
"""

import sys
import time

try:
    import paramiko
except ImportError:
    print("ERROR: paramiko not installed. Run: pip install paramiko")
    sys.exit(1)

def change_password(host, current_password, new_password, username='root', port=22):
    """
    Change root password on remote server via SSH
    """
    print(f"Connecting to {host}...")
    
    # Create SSH client
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    
    try:
        # Connect with current password
        client.connect(
            hostname=host,
            port=port,
            username=username,
            password=current_password,
            timeout=30,
            allow_agent=False,
            look_for_keys=False
        )
        print(f"Connected to {host}")
        
        # Change password using chpasswd
        # This is more reliable than passwd which requires interactive input
        command = f'echo "{username}:{new_password}" | chpasswd'
        
        stdin, stdout, stderr = client.exec_command(command)
        
        # Wait for command to complete
        exit_status = stdout.channel.recv_exit_status()
        
        if exit_status != 0:
            error = stderr.read().decode('utf-8')
            print(f"ERROR: Password change failed: {error}")
            return False
        
        print(f"Password changed successfully for {username}@{host}")
        
        # Verify new password works
        print("Verifying new password...")
        client.close()
        
        time.sleep(2)  # Wait for password change to propagate
        
        # Try to connect with new password
        verify_client = paramiko.SSHClient()
        verify_client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        
        try:
            verify_client.connect(
                hostname=host,
                port=port,
                username=username,
                password=new_password,
                timeout=30,
                allow_agent=False,
                look_for_keys=False
            )
            print("Verification successful - new password works!")
            verify_client.close()
            return True
            
        except paramiko.AuthenticationException:
            print("WARNING: Verification failed - new password may not work!")
            return False
            
    except paramiko.AuthenticationException:
        print(f"ERROR: Authentication failed for {host}")
        return False
        
    except paramiko.SSHException as e:
        print(f"ERROR: SSH error: {e}")
        return False
        
    except Exception as e:
        print(f"ERROR: Connection failed: {e}")
        return False
        
    finally:
        client.close()


def main():
    if len(sys.argv) < 4:
        print("Usage: python3 change-server-password.py <IP> <CURRENT_PASS> <NEW_PASS>")
        print("")
        print("Example:")
        print("  python3 change-server-password.py 66.94.103.91 oldpass newpass123")
        sys.exit(1)
    
    host = sys.argv[1]
    current_password = sys.argv[2]
    new_password = sys.argv[3]
    
    # Validate new password
    if len(new_password) < 8:
        print("ERROR: New password must be at least 8 characters")
        sys.exit(1)
    
    print(f"\nChanging password on: {host}")
    print("=" * 40)
    
    success = change_password(host, current_password, new_password)
    
    if success:
        print("\n✓ Password change complete!")
        sys.exit(0)
    else:
        print("\n✗ Password change failed!")
        sys.exit(1)


if __name__ == '__main__':
    main()
