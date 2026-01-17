# TrueVault VPN - User Guide

**Welcome to TrueVault VPN!**

This guide will help you get started and make the most of your VPN service.

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Setting Up Your First Device](#setting-up-your-first-device)
3. [Installing WireGuard](#installing-wireguard)
4. [Connecting to VPN](#connecting-to-vpn)
5. [Switching Servers](#switching-servers)
6. [Port Forwarding](#port-forwarding)
7. [Network Scanner](#network-scanner)
8. [Camera Dashboard](#camera-dashboard)
9. [Parental Controls](#parental-controls)
10. [Troubleshooting](#troubleshooting)
11. [FAQ](#faq)

---

## Getting Started

### Creating Your Account

1. Visit https://vpn.the-truth-publishing.com
2. Click "Sign Up" or "Get Started"
3. Enter your email address and create a password
4. Check your email for verification link
5. Click the verification link to activate your account
6. Choose your subscription plan

### Subscription Plans

| Plan | Price | Devices | Features |
|------|-------|---------|----------|
| Personal | $9.97/mo | 3 devices | All servers, standard support |
| Family | $14.97/mo | 10 devices | All servers, priority support, parental controls |
| Dedicated | $39.97/mo | Unlimited | Dedicated server, 24/7 support |

---

## Setting Up Your First Device

TrueVault uses a simple 2-click setup process:

### Step 1: Add a Device

1. Log in to your dashboard
2. Click "Add Device" or go to Device Setup
3. Enter a name for your device (e.g., "My iPhone", "Work Laptop")
4. Select your preferred server location
5. Click "Generate Configuration"

### Step 2: Install Configuration

**For Mobile (iPhone/Android):**
- Scan the QR code with the WireGuard app
- The configuration imports automatically

**For Desktop (Windows/Mac/Linux):**
- Click "Download Config File"
- Import the .conf file into WireGuard

That's it! Your device is ready to connect.

---

## Installing WireGuard

WireGuard is the VPN protocol we use. It's fast, secure, and easy to use.

### iPhone/iPad

1. Open the App Store
2. Search for "WireGuard"
3. Download and install the free app
4. Open WireGuard and tap the + button
5. Choose "Create from QR code" or "Import from file"

### Android

1. Open Google Play Store
2. Search for "WireGuard"
3. Download and install the free app
4. Open WireGuard and tap the + button
5. Choose "Scan from QR code" or "Import from file"

**Android Tip:** If your config downloads as `.conf.txt`, rename it to just `.conf`

### Windows

1. Visit https://www.wireguard.com/install/
2. Download the Windows installer
3. Run the installer
4. Open WireGuard from Start menu
5. Click "Import tunnel(s) from file"
6. Select your downloaded .conf file

### Mac

1. Open the App Store
2. Search for "WireGuard"
3. Download and install the free app
4. Open WireGuard from Applications
5. Click "Import tunnel(s) from file"
6. Select your downloaded .conf file

### Linux

```bash
# Ubuntu/Debian
sudo apt install wireguard

# Fedora
sudo dnf install wireguard-tools

# Import config
sudo cp your-config.conf /etc/wireguard/
sudo wg-quick up your-config
```

---

## Connecting to VPN

### Activating Your VPN

1. Open the WireGuard app
2. Find your TrueVault configuration
3. Toggle the switch to "ON"
4. You're now protected!

### Verifying Connection

- Visit https://whatismyipaddress.com
- Your IP should show the VPN server location
- If it shows your real location, the VPN is not active

### Disconnecting

Simply toggle the switch to "OFF" in WireGuard.

---

## Switching Servers

You can switch between our server locations anytime:

### Available Servers

| Location | Best For |
|----------|----------|
| New York | General use, East Coast content |
| Dallas | Streaming services, low latency |
| Toronto | Canadian content, privacy |
| St. Louis | Dedicated users only |

### How to Switch

1. Go to your Dashboard → Devices
2. Find the device you want to switch
3. Click "Change Server"
4. Select your new server
5. Download the new configuration
6. Import into WireGuard (replaces old config)

---

## Port Forwarding

Port forwarding allows external access to devices on your network through the VPN.

### Common Uses

- Access IP cameras remotely
- Host game servers
- Remote desktop access
- Home automation systems

### Setting Up a Port Forward

1. Go to Dashboard → Port Forwarding
2. Click "Add New Rule"
3. Select the device (or enter IP manually)
4. Enter the external port (the port others connect to)
5. Enter the internal port (the port your device uses)
6. Choose protocol (TCP, UDP, or Both)
7. Click "Create Rule"

### Example: IP Camera

- Device: Geeni Camera (192.168.1.100)
- External Port: 8080
- Internal Port: 80
- Protocol: TCP

Now you can access your camera at: `your-vpn-ip:8080`

---

## Network Scanner

The network scanner helps you discover devices on your home network.

### Running a Scan

1. Download the scanner from your Dashboard
2. Extract the zip file
3. Run the scanner:
   - Windows: Double-click `run_scanner.bat`
   - Mac/Linux: Run `./run_scanner.sh`
4. Enter your email and auth token when prompted
5. View discovered devices in your browser

### What Gets Discovered

- IP Cameras (Geeni, Wyze, Hikvision, etc.)
- Printers (HP, Epson, Canon, Brother)
- Gaming consoles (PlayStation, Xbox, Nintendo)
- Smart home devices
- Streaming devices (Roku, Fire TV)

### Adding Devices to Port Forwarding

After scanning, you can one-click add any device to port forwarding directly from the scanner results.

---

## Camera Dashboard

The camera dashboard provides a central view of all your IP cameras.

### Features

- View all discovered cameras
- Check port forwarding status
- Quick setup for remote access
- Connection testing

### Accessing Your Camera Remotely

1. Set up port forwarding for your camera
2. Note your VPN server's IP address
3. Use the format: `http://vpn-ip:external-port`
4. Enter your camera's username/password

---

## Parental Controls

Control what content can be accessed through your VPN.

### Enabling Parental Controls

1. Go to Dashboard → Parental Controls
2. Toggle "Enable Filtering" to ON
3. Configure your settings

### Blocking Categories

Block entire categories of content:
- Adult content
- Gambling
- Violence
- Social media
- Streaming
- Gaming

### Blocking Specific Domains

Add specific websites to your blocklist:
1. Enter the domain (e.g., `example.com`)
2. Click "Add to Blocklist"
3. The site is now blocked

### Viewing Blocked Requests

The blocked requests log shows:
- What was blocked
- When it was blocked
- Which device tried to access it

---

## Troubleshooting

### VPN Won't Connect

1. **Check your internet connection** - Make sure you have internet without VPN
2. **Restart WireGuard** - Close and reopen the app
3. **Re-import configuration** - Delete and re-add your config
4. **Check for conflicts** - Disable other VPNs or firewalls
5. **Try a different server** - Generate a new config for another location

### Slow Speeds

1. **Try a closer server** - Distance affects speed
2. **Check your base speed** - Disconnect VPN and run a speed test
3. **Try Dallas server** - Optimized for streaming
4. **Restart your router** - Clears network issues

### Can't Access Certain Websites

1. **Check parental controls** - You may have blocked the site
2. **Try a different server** - Some sites block certain IPs
3. **Clear browser cache** - Old data can cause issues

### Mobile App Issues

**iPhone:**
- Go to Settings → General → VPN
- Delete old TrueVault entries
- Re-import your configuration

**Android:**
- Check if config downloaded as `.conf.txt`
- Rename to `.conf` and try again
- Use our TrueVault Helper app for automatic fixing

### Port Forwarding Not Working

1. **Check your device's local IP** - It may have changed
2. **Verify ports are correct** - Internal vs external
3. **Check firewall** - Your device may be blocking connections
4. **Test locally first** - Make sure the device works on your network

---

## FAQ

### Is my data logged?

No. TrueVault operates a strict no-logs policy. We don't track your browsing activity, connection times, or IP addresses.

### Can I use TrueVault on multiple devices?

Yes! Depending on your plan:
- Personal: 3 devices
- Family: 10 devices
- Dedicated: Unlimited

### What's the difference between servers?

- **New York**: Best for East Coast US content
- **Dallas**: Optimized for streaming (Netflix, etc.)
- **Toronto**: Access Canadian content, extra privacy
- **St. Louis**: Reserved for dedicated plans

### How do I cancel my subscription?

1. Go to Dashboard → Billing
2. Click "Manage Subscription"
3. Select "Cancel Subscription"
4. Your access continues until the billing period ends

### Can I get a refund?

Yes, we offer a 30-day money-back guarantee. Contact support@vpn.the-truth-publishing.com.

### How do I contact support?

- Email: support@vpn.the-truth-publishing.com
- Dashboard: Submit a support ticket
- Response time: Usually within 24 hours

---

## Need More Help?

- **Email:** support@vpn.the-truth-publishing.com
- **Dashboard:** Submit a support ticket
- **Knowledge Base:** Check our FAQ section

Thank you for choosing TrueVault VPN!
