# TRUEVAULT VPN - COMPLETE AUTOMATED SYSTEM BLUEPRINT
## One-Man Operation Design
**Created:** January 14, 2026 - 12:45 AM CST
**Philosophy:** If it requires manual work, automate it or eliminate it.

---

# 🎯 THE GOLDEN RULES

1. **USERS DO EVERYTHING THEMSELVES** - Self-service is mandatory
2. **SYSTEM HANDLES ALL ROUTINE TASKS** - Zero daily admin work
3. **ADMIN ONLY FOR TRUE EMERGENCIES** - Maybe 5 min/day checking email
4. **NO UNNECESSARY EMAILS** - Only payment receipts and critical alerts
5. **2 CLICKS MAXIMUM** - For any user action
6. **INSTANT EVERYTHING** - No waiting, no processing, no "we'll email you"

---

# 📊 WHAT THE ADMIN'S DAY LOOKS LIKE

## Daily (5 minutes)
- Check email for critical alerts (usually empty)
- Glance at admin dashboard (optional)

## Weekly (15 minutes)
- Review revenue stats
- Check server health trends
- Respond to any support tickets (rare)

## Monthly (1 hour)
- Review costs vs revenue
- Plan server capacity
- Update pricing if needed

## That's It.
Everything else is automated.

---

# 🗺️ COMPLETE SITE MAP

```
MARKETING/PUBLIC (5 pages total - keep it simple)
├── / .......................... Landing page (pricing, features, CTA)
├── /login ..................... Login form
├── /register .................. 7-day free trial signup
├── /forgot-password ........... Password reset request
└── /reset-password ............ Password reset form

USER DASHBOARD (6 pages total - everything they need)
├── /dashboard/ ................ Home (status, quick actions)
├── /dashboard/devices ......... THE MAIN PAGE (add/switch/manage devices)
├── /dashboard/servers ......... View servers + status indicators
├── /dashboard/account ......... Profile, password, 2FA
├── /dashboard/billing ......... Plan, upgrade, cancel, invoices
└── /dashboard/help ............ FAQ + troubleshooting + contact form

ADMIN DASHBOARD (6 pages total - monitoring only)
├── /admin/ .................... Admin login
├── /admin/dashboard ........... Stats overview (auto-refreshing)
├── /admin/users ............... User list (search, view, rarely edit)
├── /admin/servers ............. Server health + restart buttons
├── /admin/payments ............ Transaction log + refund button
└── /admin/settings ............ Site settings + theme editor

TOTAL: 17 PAGES
(Not 50+ pages. Keep it manageable.)
```

---

# 👤 COMPLETE USER JOURNEY (100% Self-Service)

## Stage 1: Discovery → Signup (60 seconds)

```
┌─────────────────────────────────────────────────────────────────┐
│                         LANDING PAGE                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│   🛡️ TrueVault VPN                                              │
│   Your Privacy. Your Keys. Your Control.                         │
│                                                                  │
│   ┌─────────────────────────────────────────────────────────┐   │
│   │                                                          │   │
│   │   [  Start 7-Day Free Trial  ]  ← BIG GREEN BUTTON      │   │
│   │                                                          │   │
│   │   No credit card required • Cancel anytime               │   │
│   │                                                          │   │
│   └─────────────────────────────────────────────────────────┘   │
│                                                                  │
│   ────────────────────────────────────────────────────────────   │
│                                                                  │
│   PRICING (simple - 3 plans)                                     │
│                                                                  │
│   ┌───────────────┐ ┌───────────────┐ ┌───────────────┐         │
│   │   Personal    │ │    Family     │ │   Business    │         │
│   │    $9.99/mo   │ │   $14.99/mo   │ │   $29.99/mo   │         │
│   │               │ │               │ │               │         │
│   │  • 3 devices  │ │  • 10 devices │ │  • Unlimited  │         │
│   │  • 3 servers  │ │  • All servers│ │  • All servers│         │
│   │               │ │               │ │  • Dedicated  │         │
│   │ [Start Trial] │ │ [Start Trial] │ │ [Contact Us]  │         │
│   └───────────────┘ └───────────────┘ └───────────────┘         │
│                                                                  │
│   ────────────────────────────────────────────────────────────   │
│                                                                  │
│   FAQ (Expandable - answers 90% of questions)                    │
│                                                                  │
│   ▶ What is a VPN?                                               │
│   ▶ How do I set it up?                                          │
│   ▶ What devices work with TrueVault?                            │
│   ▶ Can I cancel anytime?                                        │
│   ▶ Is my data safe?                                             │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

## Stage 2: Registration (30 seconds)

```
User clicks "Start 7-Day Free Trial"

┌─────────────────────────────────────────────┐
│  Start Your Free Trial                      │
├─────────────────────────────────────────────┤
│                                             │
│  Email                                      │
│  [_________________________________]        │
│                                             │
│  Password                                   │
│  [_________________________________]        │
│                                             │
│  First Name (optional)                      │
│  [_________________________________]        │
│                                             │
│  [    Create Account    ]                   │
│                                             │
│  ✓ No credit card required                  │
│  ✓ 7 days free, cancel anytime              │
│                                             │
│  Already have an account? Log in            │
│                                             │
└─────────────────────────────────────────────┘

WHAT HAPPENS ON SUBMIT:
1. Validate email format
2. Check email not already registered
3. Create user account
4. Create trial subscription (7 days)
5. Generate JWT token
6. Redirect to /dashboard/devices
7. Show "Welcome! Add your first device" modal

NO EMAIL VERIFICATION REQUIRED TO START.
(Optional: verify later for account recovery)
```

## Stage 3: First Device (30 seconds) - THE 2-CLICK FLOW

```
User lands on /dashboard/devices with welcome modal:

┌─────────────────────────────────────────────────────────────────┐
│  Welcome to TrueVault! 🎉                                  [X]  │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Let's protect your first device.                                │
│  It only takes 30 seconds!                                       │
│                                                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                                                            │  │
│  │  1️⃣  Name your device                                      │  │
│  │  [  My Phone_________________ ]                            │  │
│  │                                                            │  │
│  │  2️⃣  Pick a server                                         │  │
│  │                                                            │  │
│  │  ○ 🇨🇦 Canada           Best for: Canadian banking         │  │
│  │  ● 🇺🇸 Texas            Best for: US streaming, banking    │  │
│  │  ○ 🇺🇸 New York         Best for: US East Coast            │  │
│  │                                                            │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                  │
│  [        Add Device & Download Config        ]                  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

USER CLICKS BUTTON:

┌─────────────────────────────────────────────────────────────────┐
│  ✅ Your device is ready!                                  [X]  │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                                                            │  │
│  │  📄 MyPhone-Texas.conf                                     │  │
│  │                                                            │  │
│  │  [     ⬇️  Download Config File     ]                      │  │
│  │                                                            │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                  │
│  Next steps:                                                     │
│  1. Download the file above                                      │
│  2. Open WireGuard on your device                                │
│  3. Import the file                                              │
│  4. Turn it ON ✓                                                 │
│                                                                  │
│  Need WireGuard?                                                 │
│  [iPhone] [Android] [Windows] [Mac]                              │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

TOTAL TIME: 30 seconds
TOTAL CLICKS: 2
EMAILS SENT: 0
```

## Stage 4: Daily Usage

```
USER DASHBOARD - Simple and Clean

┌─────────────────────────────────────────────────────────────────┐
│  🛡️ TrueVault                              [Account] [Logout]   │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  MY DEVICES                                                      │
│                                                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  📱 My Phone                                               │  │
│  │  Connected to: 🇺🇸 Texas                                    │  │
│  │                                                            │  │
│  │  [Switch Server] [Download Config] [Remove]                │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  💻 My Laptop                                              │  │
│  │  Connected to: 🇨🇦 Canada                                   │  │
│  │                                                            │  │
│  │  [Switch Server] [Download Config] [Remove]                │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                  │
│  [  + Add New Device  ]                                          │
│                                                                  │
│  ─────────────────────────────────────────────────────────────  │
│                                                                  │
│  📊 YOUR PLAN                                                    │
│  Trial • 5 days remaining                                        │
│  Devices: 2 of 3 used                                            │
│                                                                  │
│  [  Upgrade Now - $9.99/month  ]                                 │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

THAT'S IT. No clutter. No confusion.
```

## Stage 5: Switching Servers (15 seconds)

```
User needs US banking, currently on Canada.
Clicks [Switch Server]:

┌─────────────────────────────────────────────┐
│  Switch Server                         [X]  │
├─────────────────────────────────────────────┤
│                                             │
│  📱 My Phone                                │
│  Currently: 🇨🇦 Canada                       │
│                                             │
│  Select new server:                         │
│                                             │
│  ○ 🇨🇦 Canada         (current)             │
│  ● 🇺🇸 Texas                                 │
│  ○ 🇺🇸 New York                              │
│                                             │
│  [     Switch to Texas     ]                │
│                                             │
└─────────────────────────────────────────────┘

CLICK → New config ready → Download → Import → Done.
15 seconds. No waiting. No emails.
```

## Stage 6: Trial Ending (Automated)

```
TIMELINE:
─────────────────────────────────────────────────────────────────

Day 1-4: Nothing. Let them use the service.

Day 5:   IN-APP NOTIFICATION (not email)
         ┌────────────────────────────────────────────┐
         │ ⏰ Your trial ends in 2 days               │
         │ [Upgrade Now] [Remind Me Later]            │
         └────────────────────────────────────────────┘

Day 6:   IN-APP NOTIFICATION (more prominent)
         ┌────────────────────────────────────────────┐
         │ ⚠️ LAST DAY: Trial ends tomorrow           │
         │ [Upgrade Now - Keep Your Devices Protected]│
         └────────────────────────────────────────────┘
         
         ALSO: One email
         Subject: "Your TrueVault trial ends tomorrow"
         Body: Simple, one-button "Upgrade Now"

Day 7:   TRIAL EXPIRES
         - Subscription status → 'expired'
         - Remove WireGuard peers from all servers
         - User sees:
         
         ┌────────────────────────────────────────────────────┐
         │  Your trial has ended                              │
         │                                                    │
         │  Your devices are no longer protected.             │
         │  Upgrade now to restore access:                    │
         │                                                    │
         │  [  Upgrade - $9.99/month  ]                       │
         │                                                    │
         │  Your settings are saved for 30 days.              │
         └────────────────────────────────────────────────────┘

Day 14:  Win-back email (automated)
         "We miss you! Here's 20% off your first month"

Day 30:  Final win-back email
         "Last chance: Your account will be deleted in 7 days"

Day 37:  Account data purged (GDPR compliance)

ALL AUTOMATED. ZERO ADMIN WORK.
```

## Stage 7: Upgrading (60 seconds)

```
User clicks "Upgrade Now"

┌─────────────────────────────────────────────────────────────────┐
│  Choose Your Plan                                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐    │
│  │    Personal     │ │     Family      │ │    Business     │    │
│  │    $9.99/mo     │ │    $14.99/mo    │ │    $29.99/mo    │    │
│  │                 │ │                 │ │                 │    │
│  │  ✓ 3 devices    │ │  ✓ 10 devices   │ │  ✓ Unlimited    │    │
│  │  ✓ All servers  │ │  ✓ All servers  │ │  ✓ All servers  │    │
│  │                 │ │  ✓ Priority     │ │  ✓ Dedicated IP │    │
│  │                 │ │                 │ │  ✓ Priority     │    │
│  │                 │ │                 │ │                 │    │
│  │  [  Select  ]   │ │  [  Select  ]   │ │  [  Select  ]   │    │
│  └─────────────────┘ └─────────────────┘ └─────────────────┘    │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

User clicks "Select" on Personal:

┌─────────────────────────────────────────────────────────────────┐
│  Complete Your Purchase                                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Personal Plan - $9.99/month                                     │
│                                                                  │
│  [        Pay with PayPal        ]  ← One button                │
│                                                                  │
│  ✓ Cancel anytime                                                │
│  ✓ Instant activation                                            │
│  ✓ 30-day money-back guarantee                                   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

Click → PayPal popup → Confirm → Done

WHAT HAPPENS AUTOMATICALLY:
1. PayPal processes payment
2. Webhook hits our server
3. Subscription activated instantly
4. Receipt email sent (auto-generated by PayPal + our system)
5. Device limits increased
6. User sees "Welcome to Personal!" message

NO ADMIN INVOLVEMENT.
```

## Stage 8: Ongoing (Monthly Renewal)

```
FULLY AUTOMATED VIA PAYPAL SUBSCRIPTIONS:

Day before renewal:
- PayPal charges card automatically
- If success: webhook updates our database
- If failed: PayPal retries automatically

Failed payment flow:
─────────────────────────────────────────────────────────────────

Day 0:   Payment failed
         - PayPal sends us webhook
         - We update status to 'payment_failed'
         - IN-APP notification shown
         - Email: "Payment failed - please update your payment method"
         
Day 3:   Still failed
         - Email: "Action required: Update payment to avoid service interruption"
         
Day 7:   Still failed
         - Suspend access (remove peers from servers)
         - Email: "Service suspended - update payment to restore"
         
Day 14:  Still failed
         - Final email: "Account will be cancelled"
         
Day 21:  Cancel subscription, start win-back

ALL AUTOMATED. ZERO ADMIN WORK.
```

## Stage 9: Self-Service Cancellation

```
User goes to /dashboard/billing, clicks "Cancel Plan"

┌─────────────────────────────────────────────────────────────────┐
│  We're sorry to see you go 😢                                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Before you go, would you tell us why?                           │
│                                                                  │
│  ○ Too expensive                                                 │
│  ○ Not using it enough                                           │
│  ○ Found a better alternative                                    │
│  ○ Technical issues                                              │
│  ○ Other                                                         │
│                                                                  │
│  ─────────────────────────────────────────────────────────────   │
│                                                                  │
│  🎁 WAIT! Here's a special offer:                                │
│                                                                  │
│  Stay for 50% off your next 3 months!                            │
│  $9.99 → $4.99/month                                             │
│                                                                  │
│  [  Accept Offer  ]  [  Cancel Anyway  ]                         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

If "Cancel Anyway":

┌─────────────────────────────────────────────────────────────────┐
│  Cancellation Confirmed                                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Your plan will remain active until: February 14, 2026           │
│  After that, your devices will be disconnected.                  │
│                                                                  │
│  Changed your mind? You can reactivate anytime.                  │
│                                                                  │
│  [  Back to Dashboard  ]                                         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

WHAT HAPPENS AUTOMATICALLY:
1. Cancel PayPal subscription via API
2. Mark subscription as 'cancelled' in our DB
3. Schedule deactivation for end of billing period
4. Send confirmation email
5. Start win-back campaign after cancellation date

ALL AUTOMATED. ZERO ADMIN WORK.
```

---

# ⚙️ AUTOMATION WORKFLOWS

## All Automated Processes

| Workflow | Trigger | What Happens | Admin Action |
|----------|---------|--------------|--------------|
| New Signup | User registers | Create account, trial sub, log | None |
| Add Device | User clicks button | Generate keys, add peer, return config | None |
| Switch Server | User clicks button | Remove old peer, add new, return config | None |
| Trial Day 5 | Cron job | Show in-app notification | None |
| Trial Day 6 | Cron job | Show notification + send email | None |
| Trial Day 7 | Cron job | Expire trial, remove peers | None |
| Payment Success | PayPal webhook | Activate subscription | None |
| Payment Failed | PayPal webhook | Send reminder, retry | None |
| Day 7 Overdue | Cron job | Suspend access | None |
| User Cancels | User action | Schedule deactivation, send email | None |
| Win-back Day 14 | Cron job | Send discount email | None |
| Server Health | Cron (5 min) | Check status, restart if needed | Alert only if down >10min |
| Daily Stats | Cron (midnight) | Generate report, email admin | Read email (optional) |

---

# 🖥️ ADMIN DASHBOARD (Monitoring Only)

## What Admin Sees

```
┌─────────────────────────────────────────────────────────────────┐
│  🛡️ TrueVault Admin                                    [Logout] │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  TODAY'S STATS                          Last updated: Just now   │
│                                                                  │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐│
│  │     12      │ │     3       │ │   $149.85   │ │     0       ││
│  │ New Signups │ │ Conversions │ │  Revenue    │ │  Issues     ││
│  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘│
│                                                                  │
│  ─────────────────────────────────────────────────────────────   │
│                                                                  │
│  SERVER STATUS                                                   │
│                                                                  │
│  🟢 Canada (Toronto)     │ 23% load │ 45 users │ Healthy        │
│  🟢 Texas (Dallas)       │ 31% load │ 67 users │ Healthy        │
│  🟢 New York             │ 18% load │ 34 users │ Healthy        │
│  🟢 VIP Dedicated        │  5% load │  1 user  │ Healthy        │
│                                                                  │
│  ─────────────────────────────────────────────────────────────   │
│                                                                  │
│  RECENT ACTIVITY (auto-updating)                                 │
│                                                                  │
│  12:45 AM │ New signup: j***@gmail.com                          │
│  12:42 AM │ Payment received: $14.99 from m***@yahoo.com        │
│  12:38 AM │ Device added: iPhone → Texas                        │
│  12:35 AM │ Server switch: Laptop → Canada                      │
│  12:30 AM │ Trial expired: d***@hotmail.com                     │
│                                                                  │
│  ─────────────────────────────────────────────────────────────   │
│                                                                  │
│  NEEDS ATTENTION                            (Usually empty)      │
│                                                                  │
│  ✓ Nothing requires your attention                               │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

THE GOAL: This screen should almost always show
"Nothing requires your attention"
```

## When Admin IS Needed

Only these situations require admin:

1. **Server down >10 minutes** → Get SMS/email alert → Investigate
2. **Refund request** → View in payments → Click refund button
3. **Support ticket** → Read, respond (rare if FAQ is good)
4. **Add new server** → When scaling up (monthly check)

That's it. Everything else is automated.

---

# 📧 EMAIL TEMPLATES (Minimal)

## Emails We Send (Only Essential)

| Email | When | Content |
|-------|------|---------|
| Trial Ending | Day 6 | "Trial ends tomorrow. Upgrade to continue." |
| Payment Receipt | After payment | "Thanks! Here's your receipt." |
| Payment Failed | Day 0 | "Payment failed. Please update." |
| Payment Urgent | Day 3 | "Update payment to avoid interruption." |
| Service Suspended | Day 7 | "Service suspended. Update payment to restore." |
| Cancellation Confirmed | On cancel | "Cancelled. Service active until [date]." |
| Win-back | Day 14 post-cancel | "We miss you! Here's 20% off." |

## Emails We DON'T Send

- ❌ Welcome email (they're already in the dashboard)
- ❌ Setup instructions (shown in-app)
- ❌ Tips and tricks (unnecessary)
- ❌ Weekly newsletters (annoying)
- ❌ Feature announcements (show in-app)
- ❌ "How's it going?" check-ins (annoying)

**Rule: If it can be shown in-app, don't email it.**

---

# 🗄️ SIMPLIFIED DATABASE STRUCTURE

## Only What We Need

```
databases/
├── users.db
│   └── users (id, email, password_hash, first_name, status, created_at)
│   └── user_devices (id, user_id, name, server_id, public_key, assigned_ip)
│   └── device_server_history (id, device_id, from_server, to_server, timestamp)
│
├── servers.db
│   └── vpn_servers (id, name, display_name, flag, ip, port, public_key, status, load)
│
├── billing.db
│   └── subscriptions (id, user_id, plan, status, trial_ends, period_start, period_end)
│   └── payments (id, user_id, paypal_id, amount, status, created_at)
│
├── vip.db
│   └── vip_users (id, email, dedicated_server_id, bypass_payment)
│
├── themes.db
│   └── themes (id, name, is_active)
│   └── theme_variables (id, theme_id, category, name, value)
│
└── automation.db
    └── scheduled_tasks (id, workflow, context, execute_at, status)
    └── email_log (id, recipient, template, sent_at)
    └── activity_log (id, user_id, action, details, created_at)

THAT'S IT. 6 databases. Simple.
```

---

# 🔌 API ENDPOINTS (Minimal Set)

## Public (No Auth)
```
POST /api/auth/register      - Create account
POST /api/auth/login         - Login
POST /api/auth/forgot        - Request password reset
POST /api/auth/reset         - Reset password
```

## User Dashboard (Auth Required)
```
GET  /api/user/profile       - Get current user
PUT  /api/user/profile       - Update profile
POST /api/user/password      - Change password

GET  /api/servers/available  - List available servers (for dropdown)

GET  /api/devices            - List user's devices
POST /api/devices            - Add new device (THE 2-CLICK FLOW)
POST /api/devices/switch     - Switch device to different server
DELETE /api/devices/:id      - Remove device

GET  /api/billing/plan       - Get current subscription
POST /api/billing/checkout   - Create PayPal order
POST /api/billing/complete   - Complete payment
POST /api/billing/cancel     - Cancel subscription
GET  /api/billing/invoices   - List payment history
```

## Webhooks (External)
```
POST /api/webhooks/paypal    - PayPal payment events
```

## Admin (Admin Auth Required)
```
GET  /api/admin/stats        - Dashboard statistics
GET  /api/admin/users        - List users (paginated)
GET  /api/admin/users/:id    - Single user details
POST /api/admin/users/:id/refund - Process refund
GET  /api/admin/servers      - Server status
POST /api/admin/servers/:id/restart - Restart server
GET  /api/admin/payments     - Payment log
GET  /api/admin/logs         - Activity log
GET  /api/admin/theme        - Get theme settings
PUT  /api/admin/theme        - Update theme settings
```

## Internal (Cron Jobs)
```
GET  /api/cron/process       - Process all scheduled tasks
GET  /api/cron/health        - Server health checks
GET  /api/cron/cleanup       - Clean old data
```

**TOTAL: ~25 endpoints. Not 100+. Keep it manageable.**

---

# ⏰ CRON JOBS (5 Total)

```bash
# Run every 5 minutes - Process scheduled tasks and health checks
*/5 * * * * curl -s https://vpn.the-truth-publishing.com/api/cron/process

# Run every hour - Clean expired sessions
0 * * * * curl -s https://vpn.the-truth-publishing.com/api/cron/cleanup

# Run at midnight - Generate daily stats email
0 0 * * * curl -s https://vpn.the-truth-publishing.com/api/cron/daily-report
```

That's it. 3 cron jobs handle everything.

---

# 🎯 WHAT SUCCESS LOOKS LIKE

## For Users
- Sign up in 30 seconds
- Add device in 30 seconds
- Switch servers in 15 seconds
- Never need to contact support
- Everything just works

## For Admin (You)
- Check email in the morning: 5 minutes
- Usually zero issues
- Revenue grows automatically
- Scale by adding servers (not employees)
- Sleep at night knowing it runs itself

---

# 📋 IMPLEMENTATION PRIORITY

## Phase 1: Core (Week 1)
1. Landing page with pricing
2. Registration (7-day trial)
3. Login
4. Device management (add/switch/remove)
5. Server selection

## Phase 2: Billing (Week 2)
1. PayPal checkout integration
2. Subscription management
3. Payment webhooks
4. Trial expiration handling

## Phase 3: Automation (Week 3)
1. Scheduled task processor
2. Email templates (minimal set)
3. Trial expiration workflow
4. Payment failure workflow

## Phase 4: Admin (Week 4)
1. Admin dashboard
2. User list
3. Payment log
4. Server status
5. Theme editor

## Phase 5: Polish (Week 5)
1. Testing all flows
2. Error handling
3. Mobile responsiveness
4. Performance optimization

## Launch Checklist
- [ ] All user flows work end-to-end
- [ ] PayPal integration tested with sandbox
- [ ] All automated emails send correctly
- [ ] Server health checks working
- [ ] Admin can see stats and process refunds
- [ ] VIP user (seige235@yahoo.com) works correctly

---

# 💡 FINAL PRINCIPLE

**If you find yourself doing something manually more than once, automate it.**

The goal is a business that runs itself while you sleep.

---

**END OF BLUEPRINT**
