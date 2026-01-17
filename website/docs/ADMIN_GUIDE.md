# TrueVault VPN - Admin Guide

**For Business Operators and Administrators**

This guide covers all administrative functions for managing TrueVault VPN.

---

## Table of Contents

1. [Admin Panel Access](#admin-panel-access)
2. [Dashboard Overview](#dashboard-overview)
3. [User Management](#user-management)
4. [VIP Management](#vip-management)
5. [Server Management](#server-management)
6. [Billing & PayPal](#billing--paypal)
7. [Support Tickets](#support-tickets)
8. [System Settings](#system-settings)
9. [Database Management](#database-management)
10. [Monitoring & Logs](#monitoring--logs)
11. [Emergency Procedures](#emergency-procedures)

---

## Admin Panel Access

### URL
```
https://vpn.the-truth-publishing.com/admin/
```

### First-Time Setup

1. Access the admin panel URL
2. Default credentials will be set during installation
3. **IMMEDIATELY change your password after first login**

### Security Notes

- Admin panel is protected by .htaccess
- All admin actions are logged
- Sessions expire after 24 hours of inactivity
- Failed login attempts are logged

---

## Dashboard Overview

The main admin dashboard shows:

### Key Metrics
- **Total Users**: All registered accounts
- **Active Subscriptions**: Paying customers
- **Monthly Revenue**: This month's income
- **Server Status**: Health of all VPN servers

### Recent Activity
- New signups
- Support tickets
- Payment events
- Server alerts

### Quick Actions
- Add new user
- View recent tickets
- Check server status
- Process pending tasks

---

## User Management

### Viewing Users

1. Go to Admin → Users
2. See list of all users with:
   - Email
   - Tier (free/personal/family/dedicated)
   - Status (active/suspended/cancelled)
   - Created date
   - Last login

### Search & Filter

- Search by email
- Filter by tier
- Filter by status
- Sort by date, name, or tier

### User Actions

**View Details:**
- Click user email to see full profile
- View all their devices
- View subscription history
- View login history

**Change Tier:**
- Click "Edit" on user
- Select new tier
- Changes apply immediately

**Suspend User:**
- Click "Suspend" button
- User loses VPN access instantly
- Subscription continues (handle refund separately if needed)

**Reactivate User:**
- Click "Reactivate" on suspended user
- Access restored immediately

**Delete User:**
- Click "Delete" (confirmation required)
- Removes all user data permanently
- Cannot be undone

### Adding Manual Users

1. Click "Add User"
2. Enter email and name
3. Select tier
4. Choose whether to send welcome email
5. User receives password reset link

---

## VIP Management

**IMPORTANT: VIP is completely secret. Users do not know VIP exists until they are granted access.**

### What VIP Gets
- Free unlimited access (bypasses PayPal)
- Unlimited devices
- Access to dedicated St. Louis server
- Priority support
- VIP badge visible only in their dashboard

### Adding a VIP User

1. Go to Admin → Settings → VIP List
2. Enter the email address
3. Click "Add to VIP"
4. That's it - user is now VIP

The user will see their VIP status next time they log in. They'll be pleasantly surprised!

### How VIP Works

- System checks VIP list during login
- If email matches, user gets VIP tier automatically
- PayPal is bypassed completely
- User sees VIP badge in dashboard
- Access to dedicated server enabled

### Removing VIP

1. Go to Admin → Settings → VIP List
2. Click "Remove" next to the email
3. User reverts to their previous tier
4. They'll need to subscribe to continue

### Current VIP Users

**CRITICAL: Never remove seige235@yahoo.com from VIP list**
- This user has a dedicated server (St. Louis: 144.126.133.253)
- Permanent VIP status per business agreement

---

## Server Management

### Current Servers

| Name | IP | Location | Type | Monthly Cost |
|------|-----|----------|------|--------------|
| New York | 66.94.103.91 | USA East | Shared | $6.75 |
| St. Louis | 144.126.133.253 | USA Central | VIP Only | $6.15 |
| Dallas | 66.241.124.4 | USA Central | Shared | ~$5 |
| Toronto | 66.241.125.247 | Canada | Shared | ~$5 |

**Total Monthly Server Cost: ~$23**

### Server Providers

**Contabo (New York, St. Louis):**
- Login: https://my.contabo.com
- Email: paulhalonen@gmail.com

**Fly.io (Dallas, Toronto):**
- Login: https://fly.io/dashboard
- Email: paulhalonen@gmail.com

### Checking Server Status

Via admin panel:
1. Go to Admin → Servers
2. View status, uptime, connections

Via SSH:
```bash
# Connect to server
ssh root@[server-ip]

# Check WireGuard status
wg show

# Check active connections
wg show wg0 peers | wc -l

# Check system resources
htop
```

### Restarting WireGuard

```bash
systemctl restart wg-quick@wg0
```

### Adding a New Server

1. Provision server (Contabo/Fly.io/other)
2. Install WireGuard
3. Configure wg0.conf
4. Add to admin panel: Admin → Servers → Add Server
5. Enter name, IP, public key, location

---

## Billing & PayPal

### PayPal Configuration

**Credentials Location:**
Admin → Settings → PayPal

**Required Fields:**
- Client ID
- Secret Key
- Webhook ID
- Mode (sandbox/live)

### Plan IDs

Each subscription tier needs a PayPal plan:
- Personal: $9.97/month
- Family: $14.97/month
- Dedicated: $39.97/month

Create plans in PayPal Dashboard → Subscriptions → Plans

### Webhook Setup

**Webhook URL:**
```
https://vpn.the-truth-publishing.com/api/billing/webhook.php
```

**Events to Track:**
- BILLING.SUBSCRIPTION.ACTIVATED
- BILLING.SUBSCRIPTION.CANCELLED
- BILLING.SUBSCRIPTION.SUSPENDED
- PAYMENT.SALE.COMPLETED
- PAYMENT.SALE.REFUNDED

### Viewing Transactions

1. Go to Admin → Billing
2. See all transactions with:
   - User email
   - Amount
   - Status
   - Date
   - PayPal transaction ID

### Manual Subscription Management

**Cancel Subscription:**
1. Find user in Admin → Users
2. Click "Manage Subscription"
3. Click "Cancel"
4. Also cancel in PayPal Dashboard if needed

**Issue Refund:**
1. Log into PayPal Dashboard
2. Find the transaction
3. Issue refund through PayPal
4. Webhook updates TrueVault automatically

---

## Support Tickets

### Viewing Tickets

1. Go to Admin → Support
2. See all tickets with:
   - Subject
   - User
   - Priority
   - Status
   - Created date

### Ticket Priorities

- **Low**: General questions
- **Normal**: Standard issues
- **High**: Account/billing problems
- **Urgent**: VIP users, critical issues

### Responding to Tickets

1. Click ticket to view
2. Read message history
3. Type your response
4. Click "Send Response"
5. User receives email notification

### Ticket Status

- **Open**: Needs attention
- **Pending**: Waiting for user reply
- **Resolved**: Issue solved
- **Closed**: No further action needed

### Auto-Categorization

Tickets are automatically categorized:
- **Billing**: Payment, subscription, refund keywords
- **Technical**: Connection, server, speed keywords
- **Account**: Login, password, email keywords

---

## System Settings

### Accessing Settings

Admin → Settings

### Categories

**General:**
- Company name
- Support email
- Dashboard URL

**PayPal:**
- API credentials
- Plan IDs
- Webhook settings

**Email:**
- SMTP settings
- From address
- Admin notification email

**Security:**
- Session timeout
- Max login attempts
- IP blocking

### Changing Settings

1. Navigate to appropriate section
2. Update values
3. Click "Save"
4. Changes apply immediately

**Note:** All settings are database-driven. No code changes needed!

---

## Database Management

### Database Files

Location: `/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/databases/`

| Database | Contents |
|----------|----------|
| main.db | Users, sessions, VIP list |
| devices.db | Devices, configurations |
| billing.db | Subscriptions, transactions |
| servers.db | Server inventory, health |
| admin.db | Admin users, settings |
| logs.db | All logging |
| port_forwards.db | Port forwarding rules |
| support.db | Support tickets |

### Backup Procedure

```bash
# Create backup directory
mkdir -p /home/eybn38fwc55z/backups/$(date +%Y%m%d)

# Copy all databases
cp /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/databases/*.db \
   /home/eybn38fwc55z/backups/$(date +%Y%m%d)/
```

### Restore Procedure

```bash
# Stop any active connections first
# Then restore
cp /home/eybn38fwc55z/backups/YYYYMMDD/*.db \
   /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/databases/
```

### Direct Database Access

```bash
# Open database
sqlite3 databases/main.db

# List tables
.tables

# Query users
SELECT email, tier, status FROM users LIMIT 10;

# Exit
.quit
```

### Common Queries

```sql
-- Count users by tier
SELECT tier, COUNT(*) FROM users GROUP BY tier;

-- Active subscriptions
SELECT COUNT(*) FROM subscriptions WHERE status = 'active';

-- Today's revenue
SELECT SUM(amount) FROM transactions 
WHERE date(created_at) = date('now');

-- VIP users
SELECT email FROM vip_list;
```

---

## Monitoring & Logs

### Log Files

- `/logs/error.log` - PHP errors
- `/logs/access.log` - API access
- `/logs/debug.log` - Debug info (if enabled)

### Database Logs

In `logs.db`:

**security_events:**
- Login attempts
- Failed logins
- IP blocks

**audit_log:**
- Admin actions
- Setting changes
- User modifications

**api_requests:**
- API calls
- Response times
- Errors

### Viewing Logs

Via Admin Panel:
1. Admin → Logs
2. Filter by type, date, user
3. Export as CSV if needed

Via Command Line:
```bash
# Recent errors
tail -100 /logs/error.log

# Security events
sqlite3 databases/logs.db "SELECT * FROM security_events ORDER BY created_at DESC LIMIT 20;"
```

### Alerts

The system sends alerts for:
- Server down
- Payment failures
- High error rates
- Suspicious activity

Alerts go to: paulhalonen@gmail.com (configurable in settings)

---

## Emergency Procedures

### Server Down

1. Check provider status page (Contabo/Fly.io)
2. SSH to server and check services
3. Restart WireGuard: `systemctl restart wg-quick@wg0`
4. If hardware issue, contact provider
5. Consider routing traffic to backup server

### Database Corruption

1. Stop web traffic (maintenance mode)
2. Identify corrupted database
3. Restore from most recent backup
4. Verify data integrity
5. Resume operations

### Security Breach

1. Change all admin passwords immediately
2. Rotate JWT secret in config
3. Check audit logs for compromised actions
4. Notify affected users if data exposed
5. Review and patch vulnerability

### PayPal Issues

1. Check PayPal status page
2. Verify webhook URL is correct
3. Test with PayPal sandbox
4. Contact PayPal support if needed
5. Process manual subscriptions if extended outage

---

## Quick Reference

### Important URLs

| Function | URL |
|----------|-----|
| Admin Panel | /admin/ |
| User Dashboard | /dashboard/ |
| API Base | /api/ |
| Webhook | /api/billing/webhook.php |

### SSH Access

```bash
# Contabo servers
ssh root@66.94.103.91    # New York
ssh root@144.126.133.253 # St. Louis

# Fly.io servers
flyctl ssh console -a truevault-dallas
flyctl ssh console -a truevault-toronto
```

### Useful Commands

```bash
# Check WireGuard
wg show

# Restart WireGuard
systemctl restart wg-quick@wg0

# Check disk space
df -h

# Check memory
free -m

# View active connections
wg show wg0 peers | wc -l
```

---

## Contact Information

### Hosting
- **GoDaddy**: Account 26853687
- **Contabo**: paulhalonen@gmail.com
- **Fly.io**: paulhalonen@gmail.com

### Services
- **PayPal Business**: paulhalonen@gmail.com
- **Domain**: the-truth-publishing.com

---

**Remember: All settings are database-driven. You rarely need to touch code!**
