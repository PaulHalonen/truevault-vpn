#!/usr/bin/env python3
"""
TrueVault VPN - Server Password Changer
Runs on vpn.the-truth-publishing.com

Connects to new Contabo server and changes root password to Andassi8

Usage: python3 change-server-password.py <server_ip> <temp_password>

Requirements:
    pip install paramiko
"""

import sys
import time
import paramiko
from paramiko.ssh_exception import AuthenticationException, SSHException

# Target password for all TrueVault servers
TARGET_PASSWORD = "Andassi8"

def change_password(server_ip, temp_password):
    """
    SSH into server with temp password and change to TARGET_PASSWORD
    
    Args:
        server_ip (str): IP address of the server
        temp_password (str): Temporary password from Contabo email
        
    Returns:
        dict: {success: bool, message: str}
    """
    
    print("=" * 50)
    print("TrueVault VPN - Password Changer")
    print("=" * 50)
    print(f"Server IP: {server_ip}")
    print(f"Connecting with temporary password...")
    print()
    
    # Create SSH client
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    
    try:
        # Connect with temp password
        print("Attempting connection...")
        ssh.connect(
            hostname=server_ip,
            username='root',
            password=temp_password,
            timeout=30,
            look_for_keys=False,
            allow_agent=False
        )
        
        print("✅ Connected successfully!")
        print()
        
        # Change password using passwd command
        print("Changing password...")
        
        # Method 1: Using echo to pipe to passwd
        change_cmd = f'echo -e "{TARGET_PASSWORD}\\n{TARGET_PASSWORD}" | passwd root'
        stdin, stdout, stderr = ssh.exec_command(change_cmd)
        
        # Wait for command to complete
        exit_status = stdout.channel.recv_exit_status()
        output = stdout.read().decode('utf-8')
        error = stderr.read().decode('utf-8')
        
        if exit_status == 0:
            print("✅ Password changed successfully!")
            print()
            
            # Verify by reconnecting with new password
            print("Verifying new password...")
            ssh.close()
            time.sleep(2)
            
            ssh2 = paramiko.SSHClient()
            ssh2.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            
            try:
                ssh2.connect(
                    hostname=server_ip,
                    username='root',
                    password=TARGET_PASSWORD,
                    timeout=30,
                    look_for_keys=False,
                    allow_agent=False
                )
                
                print("✅ Password verification successful!")
                print()
                print("=" * 50)
                print("SUCCESS: Server ready for provisioning")
                print(f"Root password is now: {TARGET_PASSWORD}")
                print("=" * 50)
                
                ssh2.close()
                
                return {
                    'success': True,
                    'message': 'Password changed and verified'
                }
                
            except AuthenticationException:
                return {
                    'success': False,
                    'message': 'Password change failed - verification failed'
                }
                
        else:
            print(f"❌ Password change failed")
            print(f"Error: {error}")
            return {
                'success': False,
                'message': f'Command failed: {error}'
            }
            
    except AuthenticationException:
        print("❌ Authentication failed with temporary password")
        return {
            'success': False,
            'message': 'Authentication failed - check temporary password'
        }
        
    except SSHException as e:
        print(f"❌ SSH error: {e}")
        return {
            'success': False,
            'message': f'SSH error: {str(e)}'
        }
        
    except Exception as e:
        print(f"❌ Unexpected error: {e}")
        return {
            'success': False,
            'message': f'Unexpected error: {str(e)}'
        }
        
    finally:
        ssh.close()


def main():
    if len(sys.argv) != 3:
        print("Usage: python3 change-server-password.py <server_ip> <temp_password>")
        print()
        print("Example:")
        print("  python3 change-server-password.py 144.126.133.253 TempPass123")
        sys.exit(1)
    
    server_ip = sys.argv[1]
    temp_password = sys.argv[2]
    
    result = change_password(server_ip, temp_password)
    
    if result['success']:
        sys.exit(0)
    else:
        sys.exit(1)


if __name__ == '__main__':
    main()
