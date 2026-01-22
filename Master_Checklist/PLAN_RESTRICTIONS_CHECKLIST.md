# MASTER CHECKLIST - PLAN RESTRICTIONS ADDENDUM

**Blueprint Reference:** SECTION_25_PLAN_RESTRICTIONS.md  
**Created:** January 22, 2026  
**Priority:** CRITICAL - Must be enforced in code  

---

## TASK PR.1: Update Plan Limits in Database

### PR.1.1 Update subscription_plans table
- [ ] Basic plan: vpn_devices=3, network_devices=3, max_cameras=1, port_forward_limit=2
- [ ] Family plan: vpn_devices=5, network_devices=5, max_cameras=2, port_forward_limit=5  
- [ ] Dedicated plan: vpn_devices=99, network_devices=99, max_cameras=99, port_forward_limit=99

### PR.1.2 Add port_forwarding_allowed column to servers table
- [ ] NY Contabo: port_forwarding_allowed=1
- [ ] St. Louis: port_forwarding_allowed=1
- [ ] Dallas Fly.io: port_forwarding_allowed=0
- [ ] Toronto Fly.io: port_forwarding_allowed=0

### PR.1.3 Add high_bandwidth_allowed column to servers table
- [ ] NY Contabo: high_bandwidth_allowed=1
- [ ] St. Louis: high_bandwidth_allowed=1
- [ ] Dallas Fly.io: high_bandwidth_allowed=0
- [ ] Toronto Fly.io: high_bandwidth_allowed=0

---

## TASK PR.2: Server Visibility Rules

### PR.2.1 Update servers.php API
- [ ] Hide servers with dedicated_user_email from ALL other users
- [ ] Show dedicated server ONLY to the assigned email
- [ ] Admin panel can see all servers for management

### PR.2.2 Visibility Logic
```
IF server.dedicated_user_email IS NOT NULL:
    IF user.email == server.dedicated_user_email:
        SHOW server
    ELSE:
        HIDE server (even for VIPs)
ELSE:
    SHOW server to all users
```

---

## TASK PR.3: Port Forwarding Restrictions

### PR.3.1 Enforce server restrictions
- [ ] Check server.port_forwarding_allowed before enabling
- [ ] Block port forwarding on Dallas (Fly.io)
- [ ] Block port forwarding on Toronto (Fly.io)
- [ ] Allow port forwarding on NY Contabo
- [ ] Allow port forwarding on dedicated servers

### PR.3.2 Enforce plan limits
- [ ] Basic: max 2 port forwarding devices
- [ ] Family: max 5 port forwarding devices
- [ ] Dedicated: unlimited

### PR.3.3 Enforce camera limits
- [ ] Basic: max 1 camera
- [ ] Family: max 2 cameras
- [ ] Dedicated: unlimited

---

## TASK PR.4: High Bandwidth Service Blocking

### PR.4.1 Block on Fly.io servers
- [ ] Detect Xbox/PlayStation traffic patterns
- [ ] Detect P2P/torrent traffic patterns
- [ ] Return error if user tries to use on Dallas/Toronto
- [ ] Show message: "Gaming/P2P only available on US-East server"

### PR.4.2 User notification
- [ ] When user selects Dallas/Toronto with Xbox device, warn them
- [ ] Suggest switching to NY server for gaming

---

## TASK PR.5: Network Device Selection

### PR.5.1 Scanner shows all devices
- [ ] Display all discovered devices
- [ ] Show checkboxes for selection
- [ ] Enforce selection limit based on plan

### PR.5.2 Selection logic
- [ ] Basic: user can select 3 of discovered devices
- [ ] Family: user can select 5 of discovered devices
- [ ] Dedicated: no limit

### PR.5.3 Swap functionality
- [ ] User can deselect a device
- [ ] User can select a different device
- [ ] Changes take effect immediately

---

## ✅ VERIFICATION CHECKLIST

- [ ] Basic user cannot see St. Louis server
- [ ] Family user cannot see St. Louis server
- [ ] seige235@yahoo.com CAN see St. Louis server
- [ ] seige235@yahoo.com can ALSO see NY, Dallas, Toronto
- [ ] Port forwarding blocked on Dallas/Toronto
- [ ] Xbox device blocked on Dallas/Toronto
- [ ] Camera limits enforced per plan
- [ ] Network device limits enforced per plan

---

**STATUS:** ⬜ NOT STARTED  
**Last Updated:** January 22, 2026
