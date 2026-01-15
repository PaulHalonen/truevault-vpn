# TRUEVAULT VPN - POST-LAUNCH MONITORING GUIDE

**Version:** 1.0.0  
**Created:** January 15, 2026  
**For:** Kah-Len - Keep your VPN business healthy  

---

## 📊 DAILY MONITORING (5-10 MINUTES)

### **Morning Check (Every Day):**

**1. Check Error Logs** (2 minutes)
```bash
# View last 50 errors
tail -n 50 /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/logs/error.log

# Count errors today
grep "$(date +%Y-%m-%d)" error.log | wc -l
```
**What to look for:**
- Spike in errors (>10/day = problem)
- Database connection errors
- PayPal API failures
- Email sending failures

**Action if errors found:**
- Check TROUBLESHOOTING_GUIDE.md
- Fix immediately
- Monitor for recurrence

---

**2. Check New Registrations** (1 minute)
```sql
-- Today's new users
SELECT COUNT(*) as new_users_today FROM users 
WHERE DATE(created_at) = DATE('now');

-- Show newest users
SELECT id, email, tier, created_at FROM users 
ORDER BY created_at DESC LIMIT 5;
```
**What to look for:**
- Normal growth rate
- Any VIP registrations (should be rare!)
- Unusual registration patterns

---

**3. Check Active Subscriptions** (1 minute)
```sql
-- Active paying customers
SELECT COUNT(*) as active_subs FROM users 
WHERE subscription_status = 'active' AND tier != 'vip';

-- Revenue today (estimate)
SELECT 
    SUM(CASE WHEN tier = 'standard' THEN 9.99 ELSE 14.99 END) as monthly_revenue
FROM users WHERE subscription_status = 'active' AND tier != 'vip';
```
**What to look for:**
- Growing subscription count
- No sudden drops (churn)
- Revenue increasing

---

**4. Check Support Tickets** (2 minutes)
```sql
-- Open tickets
SELECT COUNT(*) as open_tickets FROM support_tickets 
WHERE status IN ('open', 'pending');

-- Urgent tickets
SELECT id, subject, priority, created_at FROM support_tickets 
WHERE status = 'open' AND priority IN ('high', 'urgent')
ORDER BY created_at DESC;
```
**What to look for:**
- Any urgent/high priority tickets
- Tickets open > 24 hours
- Pattern in issues (same problem multiple times)

**Action items:**
- Respond to urgent tickets first
- Close resolved tickets
- Update knowledge base if seeing patterns

---

**5. Check Server Status** (1 minute)
```bash
# Ping all servers
ping -c 1 66.94.103.91  # New York
ping -c 1 144.126.133.253  # St. Louis VIP
ping -c 1 66.241.124.4  # Dallas
ping -c 1 66.241.125.247  # Toronto

# Check port 51820 open
nc -zvu 66.94.103.91 51820
```
**What to look for:**
- All servers responding
- Port 51820 accessible
- Response time < 100ms

**Action if server down:**
- Check server provider dashboard
- Restart VPN service if needed
- Notify customers if extended outage

---

**6. Check Email Queue** (1 minute)
```sql
-- Emails pending
SELECT COUNT(*) as pending_emails FROM email_queue 
WHERE status = 'pending';

-- Failed emails today
SELECT COUNT(*) as failed_emails FROM email_log 
WHERE status = 'failed' AND DATE(sent_at) = DATE('now');
```
**What to look for:**
- Queue not building up (>50 = problem)
- Low failure rate (<5%)
- Emails sending consistently

**Action if queue backing up:**
- Check SMTP/Gmail credentials
- Run email processor manually
- Check logs for errors

---

### **Daily Checklist Summary:**
- [ ] Error logs checked (< 10 errors = good)
- [ ] New registrations reviewed
- [ ] Active subscriptions counted
- [ ] Support tickets checked
- [ ] All servers online
- [ ] Email queue healthy

**Time: ~5-10 minutes per day**

---

## 📈 WEEKLY MONITORING (30-60 MINUTES)

### **Monday Morning Review:**

**1. Weekly Statistics** (10 minutes)
```sql
-- Week's highlights
SELECT 
    COUNT(*) as new_users,
    SUM(CASE WHEN tier = 'vip' THEN 1 ELSE 0 END) as new_vips,
    SUM(CASE WHEN subscription_status = 'active' THEN 1 ELSE 0 END) as active_subs
FROM users 
WHERE DATE(created_at) >= DATE('now', '-7 days');

-- Revenue this week (estimate)
SELECT 
    SUM(CASE 
        WHEN tier = 'standard' THEN 9.99 
        WHEN tier = 'pro' THEN 14.99 
        ELSE 0 
    END) as weekly_revenue
FROM users 
WHERE subscription_status = 'active' AND tier != 'vip'
AND DATE(created_at) >= DATE('now', '-7 days');
```

**What to track:**
- Week-over-week growth
- Churn rate (cancellations)
- Average revenue per user
- VIP approvals (should be very rare)

---

**2. Review Failed Payments** (5 minutes)
```sql
-- Failed payments this week
SELECT u.email, u.tier, t.amount, t.status, t.created_at
FROM transactions t
JOIN users u ON t.user_id = u.id
WHERE t.status = 'failed' 
AND DATE(t.created_at) >= DATE('now', '-7 days');
```

**Action items:**
- Check if Day 0, 3, 7 emails sent
- Verify grace period workflow working
- Follow up on high-value customers

---

**3. Support Ticket Analysis** (10 minutes)
```sql
-- Most common ticket categories
SELECT category, COUNT(*) as count 
FROM support_tickets 
WHERE DATE(created_at) >= DATE('now', '-7 days')
GROUP BY category ORDER BY count DESC;

-- Average resolution time
SELECT 
    category,
    AVG(JULIANDAY(resolved_at) - JULIANDAY(created_at)) * 24 as avg_hours
FROM support_tickets 
WHERE resolved_at IS NOT NULL
AND DATE(created_at) >= DATE('now', '-7 days')
GROUP BY category;
```

**What to look for:**
- Patterns in tickets (same issue multiple times)
- Long resolution times (>24 hours)
- Categories needing more KB articles

**Action items:**
- Create KB article for common issues
- Improve documentation
- Fix recurring technical problems

---

**4. Email Performance** (5 minutes)
```sql
-- Email success rate this week
SELECT 
    method,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
    ROUND(SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as success_rate
FROM email_log 
WHERE DATE(sent_at) >= DATE('now', '-7 days')
GROUP BY method;

-- Most sent templates
SELECT 
    template_name,
    COUNT(*) as sent_count
FROM email_queue
WHERE status = 'sent'
AND DATE(sent_at) >= DATE('now', '-7 days')
GROUP BY template_name
ORDER BY sent_count DESC LIMIT 10;
```

**What to look for:**
- Success rate > 95%
- Welcome emails sending consistently
- Payment reminders working
- No template failures

---

**5. Server Performance** (5 minutes)
```sql
-- Devices per server
SELECT 
    s.name,
    s.location,
    COUNT(d.id) as device_count
FROM servers s
LEFT JOIN devices d ON d.server_id = s.id
GROUP BY s.id;

-- VIP server usage (should only be seige235@yahoo.com)
SELECT u.email, d.device_name, s.name as server
FROM devices d
JOIN users u ON d.user_id = u.id
JOIN servers s ON d.server_id = s.id
WHERE s.name LIKE '%St. Louis%';
```

**What to look for:**
- Balanced load across servers
- St. Louis only used by VIP
- No single server overloaded

---

**6. Workflow Performance** (5 minutes)
```sql
-- Workflow executions this week
SELECT 
    workflow_name,
    COUNT(*) as executions,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
FROM workflow_executions
WHERE DATE(started_at) >= DATE('now', '-7 days')
GROUP BY workflow_name;
```

**What to look for:**
- All workflows executing
- Low failure rate (<5%)
- Expected execution counts

**Action if failures:**
- Check automation logs
- Review workflow code
- Test manually

---

### **Weekly Checklist:**
- [ ] Statistics reviewed
- [ ] Failed payments checked
- [ ] Support tickets analyzed
- [ ] Email performance verified
- [ ] Server load balanced
- [ ] Workflows functioning
- [ ] KB articles updated

**Time: ~30-60 minutes per week**

---

## 📅 MONTHLY MONITORING (2-3 HOURS)

### **First of Month Review:**

**1. Monthly Revenue Report** (15 minutes)
```sql
-- Last month's metrics
SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN subscription_status = 'active' THEN 1 ELSE 0 END) as paying_customers,
    SUM(CASE WHEN tier = 'standard' AND subscription_status = 'active' THEN 9.99 ELSE 0 END) +
    SUM(CASE WHEN tier = 'pro' AND subscription_status = 'active' THEN 14.99 ELSE 0 END) as monthly_recurring_revenue,
    SUM(CASE WHEN tier = 'vip' THEN 1 ELSE 0 END) as vip_count
FROM users;

-- New vs churned customers last month
SELECT 
    SUM(CASE WHEN DATE(created_at) >= DATE('now', 'start of month', '-1 month') 
        AND DATE(created_at) < DATE('now', 'start of month') THEN 1 ELSE 0 END) as new_customers,
    SUM(CASE WHEN subscription_status = 'cancelled' 
        AND DATE(updated_at) >= DATE('now', 'start of month', '-1 month')
        AND DATE(updated_at) < DATE('now', 'start of month') THEN 1 ELSE 0 END) as churned_customers
FROM users;
```

**Calculate:**
- **MRR (Monthly Recurring Revenue):** Total from active subscriptions
- **Growth Rate:** (New - Churned) / Total * 100
- **Churn Rate:** Churned / Total * 100
- **ARPU (Average Revenue Per User):** MRR / Paying Customers

**Target Metrics:**
- MRR growing month-over-month
- Churn < 5%
- Growth > 10%

---

**2. Cost Analysis** (10 minutes)

**Monthly Costs:**
```
Contabo Server 1 (New York): $6.75
Contabo Server 2 (St. Louis VIP): $6.15
Fly.io Dallas: ~$5.00
Fly.io Toronto: ~$5.00
Domain: ~$1.00
Total: ~$24/month
```

**Calculate Profit:**
```
Monthly Revenue: $_______ (from query above)
Monthly Costs: $24.00
Profit: $_______ - $24.00 = $_______
Profit Margin: _______%
```

**Target:** Profit margin > 80%

---

**3. User Retention Analysis** (15 minutes)
```sql
-- Cohort retention (users from 30 days ago still active)
SELECT 
    COUNT(*) as users_30_days_ago,
    SUM(CASE WHEN subscription_status = 'active' THEN 1 ELSE 0 END) as still_active,
    ROUND(SUM(CASE WHEN subscription_status = 'active' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as retention_rate
FROM users
WHERE DATE(created_at) = DATE('now', '-30 days');

-- Lifetime value estimate
SELECT 
    tier,
    AVG(JULIANDAY('now') - JULIANDAY(created_at)) as avg_lifetime_days,
    CASE 
        WHEN tier = 'standard' THEN 9.99 * AVG(JULIANDAY('now') - JULIANDAY(created_at)) / 30
        WHEN tier = 'pro' THEN 14.99 * AVG(JULIANDAY('now') - JULIANDAY(created_at)) / 30
        ELSE 0
    END as estimated_ltv
FROM users
WHERE subscription_status = 'active'
GROUP BY tier;
```

**What to track:**
- 30-day retention rate (target: >85%)
- Average customer lifetime
- Lifetime value per tier

---

**4. Support Ticket Review** (20 minutes)

**Monthly ticket stats:**
```sql
SELECT 
    COUNT(*) as total_tickets,
    AVG(JULIANDAY(resolved_at) - JULIANDAY(created_at)) * 24 as avg_resolution_hours,
    SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count
FROM support_tickets
WHERE DATE(created_at) >= DATE('now', 'start of month', '-1 month')
AND DATE(created_at) < DATE('now', 'start of month');
```

**Action items:**
- Review unresolved tickets
- Update KB articles
- Identify product improvements needed
- Track most common issues

---

**5. System Health Check** (20 minutes)

**Database health:**
```bash
# Check database sizes
ls -lh /databases/*.db

# Vacuum databases (optimize)
sqlite3 /databases/users.db "VACUUM;"
sqlite3 /databases/logs.db "VACUUM;"
# ... repeat for all databases
```

**Log rotation:**
```bash
# Archive old logs
mv /logs/error.log /logs/error.log.$(date +%Y%m).backup
touch /logs/error.log
chmod 644 /logs/error.log
```

**Backup verification:**
- Download all databases
- Store in safe location
- Test restore process

---

**6. Security Audit** (15 minutes)

**Check for suspicious activity:**
```sql
-- Failed login attempts
SELECT COUNT(*) as failed_logins
FROM security_events
WHERE event_type = 'failed_login'
AND DATE(created_at) >= DATE('now', 'start of month', '-1 month');

-- Unusual access patterns
SELECT user_id, COUNT(*) as login_count
FROM sessions
WHERE DATE(created_at) >= DATE('now', 'start of month', '-1 month')
GROUP BY user_id
HAVING COUNT(*) > 100;
```

**Action items:**
- Review failed logins
- Block suspicious IPs
- Update security measures

---

**7. Feature Usage Analysis** (15 minutes)

**What features are customers using?**
```sql
-- Port forwarding usage
SELECT COUNT(DISTINCT user_id) as users_with_port_forwarding
FROM port_forwards;

-- Multi-device users
SELECT COUNT(*) as multi_device_users
FROM (
    SELECT user_id, COUNT(*) as device_count
    FROM devices
    GROUP BY user_id
    HAVING COUNT(*) > 1
);

-- Server preferences
SELECT s.name, COUNT(d.id) as connections
FROM devices d
JOIN servers s ON d.server_id = s.id
GROUP BY s.id
ORDER BY connections DESC;
```

**Insights:**
- Which features are popular?
- Which features are unused?
- Server preferences by region?

---

**8. Email Campaign Results** (10 minutes)

**How effective are retention emails?**
```sql
-- Retention offer conversions
SELECT 
    COUNT(*) as retention_emails_sent,
    SUM(CASE WHEN subscription_status = 'active' THEN 1 ELSE 0 END) as came_back
FROM users
WHERE DATE(updated_at) >= DATE('now', 'start of month', '-1 month')
AND DATE(updated_at) < DATE('now', 'start of month')
AND subscription_status = 'cancelled';
```

**Calculate:**
- Win-back rate from retention offers
- Email open rates (if tracking)
- Click-through rates

---

**9. Growth Projections** (10 minutes)

**Based on current trends:**
```
Current MRR: $_______
Current Users: _______
Growth Rate: _______%

Projected next month:
Users: _______ * (1 + growth_rate/100) = _______
MRR: $_______ * (1 + growth_rate/100) = $_______

Projected 6 months:
Users: _______ * (1 + growth_rate/100)^6 = _______
MRR: $_______ * (1 + growth_rate/100)^6 = $_______

Projected 12 months:
Users: _______ * (1 + growth_rate/100)^12 = _______
MRR: $_______ * (1 + growth_rate/100)^12 = $_______
```

---

**10. VIP System Review** (5 minutes)

**Is VIP system still secret?**
- [ ] No VIP on landing page
- [ ] No VIP on pricing page
- [ ] No VIP mentions in support tickets
- [ ] Only authorized VIP users (seige235@yahoo.com)
- [ ] No accidental VIP leaks

**VIP stats:**
```sql
SELECT email, tier, created_at FROM users WHERE tier = 'vip';
```

Should only be seige235@yahoo.com (and any you manually approved)

---

### **Monthly Checklist:**
- [ ] Revenue report generated
- [ ] Costs calculated
- [ ] Profit margin healthy (>80%)
- [ ] Retention rate good (>85%)
- [ ] Support tickets reviewed
- [ ] Databases optimized
- [ ] Logs rotated
- [ ] Backups created
- [ ] Security audited
- [ ] Features analyzed
- [ ] Email campaigns reviewed
- [ ] Growth projected
- [ ] VIP system verified secret

**Time: ~2-3 hours per month**

---

## 🚨 ALERT THRESHOLDS

### **Immediate Action Required:**

**1. Server Down**
- Any server not responding
- Port 51820 unreachable
- **Action:** Fix immediately, notify customers if >1 hour

**2. PayPal Webhook Failure**
- >5 failed webhooks in 1 hour
- **Action:** Check PayPal dashboard, verify credentials

**3. Email Failure Rate High**
- >10% emails failing
- **Action:** Check SMTP/Gmail credentials, test sending

**4. Database Error Spike**
- >50 database errors in 1 hour
- **Action:** Check logs, verify database connections

**5. Support Ticket Overload**
- >10 urgent tickets open
- **Action:** Triage, prioritize, call for backup if needed

---

### **Warning Levels:**

**🟢 GREEN (All Good):**
- Error rate < 10/day
- Email success > 95%
- Server uptime > 99.9%
- Support tickets < 5 open
- Churn rate < 5%

**🟡 YELLOW (Monitor Closely):**
- Error rate 10-50/day
- Email success 90-95%
- Server uptime 95-99.9%
- Support tickets 5-10 open
- Churn rate 5-10%

**🔴 RED (Action Required):**
- Error rate > 50/day
- Email success < 90%
- Server uptime < 95%
- Support tickets > 10 open
- Churn rate > 10%

---

## 📱 MONITORING TOOLS (Optional Setup)

### **Automated Monitoring:**

**1. UptimeRobot (Free):**
- Monitor all 4 VPN servers
- Check every 5 minutes
- Email alert if down
- Track uptime percentage

**2. Google Analytics:**
- Track landing page visits
- Monitor conversion rates
- See user behavior

**3. Server Monitoring Script:**
```bash
#!/bin/bash
# save as: /home/scripts/monitor-vpn.sh

# Check all servers
servers=("66.94.103.91" "144.126.133.253" "66.241.124.4" "66.241.125.247")

for server in "${servers[@]}"; do
    if ! ping -c 1 $server > /dev/null 2>&1; then
        echo "Server $server is DOWN!" | mail -s "VPN Alert: Server Down" your@email.com
    fi
done
```

Run every 5 minutes:
```bash
*/5 * * * * /home/scripts/monitor-vpn.sh
```

---

## 📊 METRICS DASHBOARD (Build This!)

Create simple admin page: `/admin/metrics-dashboard.php`

**Display:**
- Current MRR
- Active users count
- New signups today
- Churn this week
- Open support tickets
- Server status indicators
- Recent errors (last 10)
- Email queue size

**Refresh:** Every 5 minutes automatically

---

## 🎯 MONTHLY GOALS

Set these targets and track monthly:

**Growth Metrics:**
- New users: _____ (target: 10% increase)
- MRR: $_____ (target: 15% increase)
- Churn: _____% (target: <5%)

**Quality Metrics:**
- Support ticket resolution: _____ hours (target: <24)
- Server uptime: _____% (target: >99.9%)
- Email success rate: _____% (target: >95%)

**Business Metrics:**
- Profit margin: _____% (target: >80%)
- Customer lifetime: _____ days (target: >180)
- ARPU: $_____ (target: $12+)

---

## ✅ FINAL MONITORING CHECKLIST

**Daily (5-10 min):**
- [ ] Check errors
- [ ] Review new signups
- [ ] Count active subs
- [ ] Check support tickets
- [ ] Verify servers online
- [ ] Check email queue

**Weekly (30-60 min):**
- [ ] Generate statistics
- [ ] Review failed payments
- [ ] Analyze support tickets
- [ ] Check email performance
- [ ] Balance server load
- [ ] Verify workflows

**Monthly (2-3 hours):**
- [ ] Revenue report
- [ ] Cost analysis
- [ ] Retention analysis
- [ ] Support review
- [ ] System health check
- [ ] Security audit
- [ ] Feature analysis
- [ ] Email campaigns
- [ ] Growth projections
- [ ] VIP system verification

---

**Remember: Consistent monitoring prevents major problems!**

Set calendar reminders:
- Daily check: Every morning 9 AM
- Weekly review: Every Monday 10 AM
- Monthly deep-dive: First Monday of month

**Your business will thank you!** 🚀
