# TrueVault VPN - Business Transfer Guide

**30-Minute Business Handoff Process**

This guide enables complete business transfer to a new owner with minimal technical knowledge required.

---

## What's Being Transferred

### Digital Assets
- ✅ Complete website and codebase
- ✅ All 8 SQLite databases
- ✅ Admin panel with full control
- ✅ 4 VPN servers (operational)
- ✅ Domain/subdomain access
- ✅ All user accounts and subscriptions
- ✅ Network scanner tool
- ✅ Complete documentation

### Account Access
- GoDaddy hosting account
- Contabo VPS accounts (2 servers)
- Fly.io accounts (2 servers)
- PayPal business integration
- Email accounts

### Revenue Stream
- Active paying subscribers
- Recurring monthly revenue
- Established customer base

---

## 30-Minute Transfer Checklist

### Phase 1: Admin Access (5 minutes)

**Step 1.1: Change Admin Credentials**
1. Log into admin panel: `https://vpn.the-truth-publishing.com/admin/`
2. Go to Settings → Admin Account
3. Change admin email to new owner's email
4. Change admin password
5. Save changes

**Step 1.2: Verify Access**
1. Log out
2. Log in with new credentials
3. Confirm full admin access

---

### Phase 2: PayPal Configuration (10 minutes)

**Step 2.1: Option A - Use New Owner's PayPal**
1. New owner creates PayPal Business account (if needed)
2. In PayPal Developer Dashboard:
   - Create new App
   - Copy Client ID and Secret
   - Create subscription plans matching current pricing
3. In TrueVault Admin:
   - Go to Settings → PayPal
   - Enter new Client ID
   - Enter new Secret
   - Update Plan IDs
4. Update webhook:
   - PayPal Dashboard → Webhooks → Add
   - URL: `https://vpn.the-truth-publishing.com/api/billing/webhook.php`
   - Select all billing events
   - Copy Webhook ID to TrueVault settings

**Step 2.2: Option B - Transfer PayPal Account**
1. Change PayPal account email to new owner
2. Update password
3. Update bank/card information
4. No changes needed in TrueVault settings

---

### Phase 3: Email Configuration (5 minutes)

**Step 3.1: Update Email Settings**
1. Go to Admin → Settings → Email
2. Update SMTP credentials (if changing email provider)
3. Update admin notification email
4. Test email sending with "Send Test Email" button

**Step 3.2: Update Contact Information**
1. Go to Admin → Settings → General
2. Update support email address
3. Update company name (if desired)
4. Save changes

---

### Phase 4: Security Reset (5 minutes)

**Step 4.1: Change JWT Secret**
1. Access server via FTP or SSH
2. Edit `/includes/config.php`
3. Change `JWT_SECRET` to a new random string (32+ characters)
4. Save file

**Step 4.2: Verify Security**
1. All existing user sessions will be invalidated
2. Users will need to log in again (normal behavior)

---

### Phase 5: Server Access (5 minutes)

**Step 5.1: Contabo Accounts**
- Login: https://my.contabo.com
- Email: paulhalonen@gmail.com (transfer to new owner)
- Change password after transfer
- Contains: New York and St. Louis servers

**Step 5.2: Fly.io Accounts**
- Login: https://fly.io/dashboard
- Email: paulhalonen@gmail.com (transfer to new owner)
- Change password after transfer
- Contains: Dallas and Toronto servers

**Step 5.3: SSH Access**
1. Change root passwords on all servers:
```bash
ssh root@66.94.103.91     # New York
passwd

ssh root@144.126.133.253  # St. Louis
passwd
```
2. For Fly.io, use `flyctl ssh console`

---

## Account Credentials Reference

### GoDaddy Hosting
- **Username:** 26853687
- **Password:** [Current password - change after transfer]
- **Contains:** All website files, databases

### Contabo VPS
- **Login URL:** https://my.contabo.com
- **Email:** paulhalonen@gmail.com
- **Password:** [Current password - change after transfer]
- **Monthly Cost:** $12.90

### Fly.io
- **Login URL:** https://fly.io/dashboard
- **Email:** paulhalonen@gmail.com
- **Password:** [Current password - change after transfer]
- **Monthly Cost:** ~$10

### PayPal
- **Business Email:** paulhalonen@gmail.com
- **Used For:** All subscription payments
- **Fees:** ~2.9% + $0.30 per transaction

---

## Server Details

### Server 1: New York (Contabo)
- **IP:** 66.94.103.91
- **Type:** Cloud VPS 10 SSD
- **Cost:** $6.75/month
- **Use:** General users, East Coast

### Server 2: St. Louis (Contabo) - VIP ONLY
- **IP:** 144.126.133.253
- **Type:** Cloud VPS 10 SSD
- **Cost:** $6.15/month
- **Use:** Dedicated to seige235@yahoo.com
- ⚠️ **NEVER assign to regular users**

### Server 3: Dallas (Fly.io)
- **IP:** 66.241.124.4
- **Type:** shared-1x-cpu@256MB
- **Cost:** ~$5/month
- **Use:** Streaming optimized

### Server 4: Toronto (Fly.io)
- **IP:** 66.241.125.247
- **Type:** shared-1x-cpu@256MB
- **Cost:** ~$5/month
- **Use:** Canadian content

**Total Monthly Server Cost:** ~$23

---

## Database-Driven Architecture

**Why This Matters:**

Everything is stored in databases, not code. New owner changes settings through admin panel - no programming required.

| Setting | How to Change |
|---------|---------------|
| PayPal credentials | Admin → Settings → PayPal |
| Email settings | Admin → Settings → Email |
| Server list | Admin → Servers |
| VIP list | Admin → Settings → VIP List |
| Pricing | PayPal plans + Admin settings |
| Business info | Admin → Settings → General |

**Files New Owner Should NEVER Edit:**
- PHP files (unless bug fix needed)
- Database files directly
- Server configurations

**Everything goes through Admin Panel!**

---

## VIP System - CRITICAL

### What VIP Is
A secret tier for special users. Completely hidden from public.

### Current VIP User
**seige235@yahoo.com**
- NEVER remove from VIP list
- Has dedicated server (St. Louis)
- Permanent free access
- Part of business agreement

### Managing VIP
- Admin → Settings → VIP List
- Add emails to grant VIP
- Users discover VIP status when they log in (surprise!)
- No public advertising of VIP tier

---

## Monthly Operating Costs

| Item | Cost |
|------|------|
| GoDaddy Hosting | ~$12/month |
| Contabo Server 1 | $6.75/month |
| Contabo Server 2 | $6.15/month |
| Fly.io Servers | ~$10/month |
| **Total** | **~$35/month** |

PayPal fees: 2.9% + $0.30 per transaction

---

## Revenue Information

### Pricing
- Personal: $9.97/month
- Family: $14.97/month
- Dedicated: $39.97/month

### Break-Even
- ~4 Personal subscriptions cover costs
- Or ~3 Family subscriptions

### Scaling
- Current capacity: ~1,200 users
- Add servers easily via Fly.io (~$5/month each)
- Contabo offers larger VPS options

---

## Post-Transfer Tasks

### Immediate (First Day)
- [ ] Change all admin passwords
- [ ] Update PayPal credentials
- [ ] Update email settings
- [ ] Change JWT secret
- [ ] Test full user registration flow
- [ ] Test VPN connection
- [ ] Test support ticket system

### First Week
- [ ] Review all admin settings
- [ ] Understand database structure
- [ ] Test port forwarding
- [ ] Test network scanner
- [ ] Review VIP list (keep seige235@yahoo.com!)
- [ ] Set up database backup schedule

### Ongoing
- [ ] Monitor user signups
- [ ] Handle support tickets
- [ ] Weekly database backups
- [ ] Monthly server maintenance
- [ ] Quarterly security review

---

## Support for New Owner

### Documentation Included
- `USER_GUIDE.md` - Customer-facing help
- `ADMIN_GUIDE.md` - Administrative functions
- `BUSINESS_TRANSFER.md` - This document
- `MASTER_BLUEPRINT/` - Technical specifications
- `Master_Checklist/` - Build documentation

### Where to Get Help

**Technical Issues:**
- GoDaddy support for hosting
- Contabo support for VPS issues
- Fly.io community/support
- PayPal developer support

**Code Issues:**
- All code is documented
- Master Blueprint contains specifications
- Original developer may offer paid support

---

## Final Handoff Checklist

### Seller Provides
- [ ] Admin panel access
- [ ] GoDaddy credentials
- [ ] Contabo credentials
- [ ] Fly.io credentials
- [ ] PayPal access (or buyer sets up own)
- [ ] All documentation
- [ ] Server SSH access
- [ ] Any additional notes

### Buyer Confirms
- [ ] Can log into admin panel
- [ ] Can access all hosting accounts
- [ ] Can access server providers
- [ ] PayPal receiving payments
- [ ] Email system working
- [ ] VPN connections working
- [ ] Support system working
- [ ] Understands VIP system

### Final Steps
- [ ] Seller removes own access
- [ ] Buyer changes all passwords
- [ ] Transfer officially complete!

---

## Key Reminders

1. **Everything is database-driven** - Use admin panel, not code
2. **Never remove seige235@yahoo.com from VIP** - Business agreement
3. **VIP is secret** - Users don't know it exists until granted
4. **Backup databases weekly** - Simple file copy
5. **Server costs are low** - ~$35/month total
6. **PayPal handles subscriptions** - Automated billing

---

**Congratulations on your new VPN business!**

The system is designed for one-person operation with minimal technical knowledge. Everything you need is in the admin panel.

Good luck! 🚀
