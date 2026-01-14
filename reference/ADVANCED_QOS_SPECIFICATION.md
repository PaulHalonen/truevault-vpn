# ADVANCED QoS (QUALITY OF SERVICE) SYSTEM - SPECIFICATION

**Version:** 1.0  
**Date:** January 14, 2026  
**Better Than Traditional QoS**  

---

## 🎯 SYSTEM OVERVIEW

### Why Traditional QoS Fails
**Traditional QoS Problems:**
- ❌ Complicated rules (requires network expertise)
- ❌ Static priorities (can't adapt to usage)
- ❌ No per-device control (affects entire network)
- ❌ Can't throttle specific apps
- ❌ No real-time visibility

**TrueVault Advanced QoS Solution:**
- ✅ Simple drag-and-drop interface
- ✅ AI-powered adaptive prioritization
- ✅ Granular per-device bandwidth control
- ✅ Application-aware throttling
- ✅ Real-time monitoring & graphs
- ✅ Smart presets (Gaming Mode, Work Mode, Family Mode)

---

## 🎮 SMART MODES (One-Click Presets)

### Gaming Mode
```
┌─────────────────────────────────────────────────────────────┐
│ 🎮 Gaming Mode                                               │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Optimized for low latency gaming                            │
│                                                             │
│ Priority Configuration:                                     │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                             │
│ 🥇 CRITICAL (Lowest Latency):                              │
│    • Gaming consoles (Xbox, PlayStation, Nintendo)         │
│    • Gaming PCs (detected gaming traffic)                  │
│    Bandwidth: Unlimited | Priority: 100/100                │
│                                                             │
│ 🥈 HIGH:                                                    │
│    • Voice chat (Discord, TeamSpeak)                       │
│    • Video calls (Zoom, Teams)                             │
│    Bandwidth: 10 Mbps | Priority: 80/100                   │
│                                                             │
│ 🥉 NORMAL:                                                  │
│    • Web browsing                                           │
│    • General apps                                           │
│    Bandwidth: 5 Mbps | Priority: 50/100                    │
│                                                             │
│ 🔽 LOW (Throttled):                                         │
│    • Downloads (Steam, Epic Games)                         │
│    • Updates (Windows, iOS)                                │
│    • Streaming (Netflix, YouTube) - limited to 720p       │
│    Bandwidth: 2 Mbps | Priority: 20/100                    │
│                                                             │
│ [Enable Gaming Mode]  [Customize]                          │
└─────────────────────────────────────────────────────────────┘
```

### Work From Home Mode
```
┌─────────────────────────────────────────────────────────────┐
│ 💼 Work From Home Mode                                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Optimized for video calls and productivity                  │
│                                                             │
│ Priority Configuration:                                     │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                             │
│ 🥇 CRITICAL:                                                │
│    • Video conferencing (Zoom, Teams, Meet)                │
│    • VoIP calls (Skype, WhatsApp)                          │
│    Bandwidth: Unlimited | Priority: 100/100                │
│                                                             │
│ 🥈 HIGH:                                                    │
│    • Work laptops (Dad's MacBook, Mom's Dell)              │
│    • Cloud storage sync (Dropbox, OneDrive)                │
│    Bandwidth: 15 Mbps | Priority: 80/100                   │
│                                                             │
│ 🥉 NORMAL:                                                  │
│    • Web browsing                                           │
│    • Email                                                  │
│    Bandwidth: 5 Mbps | Priority: 50/100                    │
│                                                             │
│ 🔽 LOW:                                                     │
│    • Kids' devices (games, YouTube)                        │
│    • Background updates                                     │
│    • Streaming                                              │
│    Bandwidth: 2 Mbps | Priority: 20/100                    │
│                                                             │
│ [Enable Work Mode]  [Customize]                            │
└─────────────────────────────────────────────────────────────┘
```

### Streaming Mode
```
┌─────────────────────────────────────────────────────────────┐
│ 📺 Streaming Mode                                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Optimized for 4K streaming without buffering                │
│                                                             │
│ Priority Configuration:                                     │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                             │
│ 🥇 CRITICAL:                                                │
│    • Smart TVs (Living Room TV, Bedroom TV)                │
│    • Streaming devices (Roku, Apple TV, Chromecast)        │
│    Bandwidth: Unlimited | Priority: 100/100                │
│                                                             │
│ 🥈 HIGH:                                                    │
│    • Tablets watching streaming                             │
│    • Phones watching streaming                              │
│    Bandwidth: 10 Mbps | Priority: 80/100                   │
│                                                             │
│ 🥉 NORMAL:                                                  │
│    • Web browsing                                           │
│    Bandwidth: 5 Mbps | Priority: 50/100                    │
│                                                             │
│ 🔽 LOW:                                                     │
│    • Background downloads                                   │
│    • System updates                                         │
│    • Non-streaming devices                                  │
│    Bandwidth: 1 Mbps | Priority: 20/100                    │
│                                                             │
│ [Enable Streaming Mode]  [Customize]                       │
└─────────────────────────────────────────────────────────────┘
```

### Family Mode (Balanced)
```
┌─────────────────────────────────────────────────────────────┐
│ 👨‍👩‍👧‍👦 Family Mode (Balanced)                                   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Equal bandwidth for everyone, no priority                   │
│                                                             │
│ Configuration:                                              │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                             │
│ All devices get equal priority (50/100)                     │
│                                                             │
│ Per-Device Bandwidth Limits:                                │
│ • Parents' devices: 10 Mbps each                           │
│ • Kids' devices: 5 Mbps each                               │
│ • IoT devices: 2 Mbps each                                 │
│                                                             │
│ Fair Queue Management:                                      │
│ ✓ No device can monopolize bandwidth                       │
│ ✓ Bandwidth shared equally when congested                  │
│ ✓ Automatic load balancing                                 │
│                                                             │
│ [Enable Family Mode]  [Customize]                          │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎯 DEVICE PRIORITY MANAGEMENT

### Priority Levels Explained
```
CRITICAL (100):  Guaranteed bandwidth, zero throttling
  • Always get requested bandwidth
  • Lowest possible latency
  • Never delayed or deprioritized
  • Use for: Gaming, video calls, mission-critical work

HIGH (80):       Preferred traffic, rarely throttled
  • Gets bandwidth before normal/low traffic
  • Only throttled when network is severely congested
  • Minimal latency increase
  • Use for: Work devices, important apps

NORMAL (50):     Standard traffic, fair share
  • Gets bandwidth when available
  • May be throttled during congestion
  • Average latency
  • Use for: General browsing, email, casual use

LOW (20):        Background traffic, heavily throttled
  • Gets bandwidth only when network is idle
  • Always deprioritized
  • High latency acceptable
  • Use for: Downloads, updates, backups
```

### Drag-and-Drop Priority Interface
```
┌─────────────────────────────────────────────────────────────┐
│ Device Priority Manager                                      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ 🥇 CRITICAL PRIORITY                                        │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ 🎮 Xbox Series X          [10/10 Mbps] [⚙️] [❌]     │   │
│ │ 💻 Dad's Work Laptop      [15/15 Mbps] [⚙️] [❌]     │   │
│ │                                                       │   │
│ │ [+ Add Device]                                        │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                             │
│ 🥈 HIGH PRIORITY                                            │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ 📱 Mom's iPhone           [8/8 Mbps]   [⚙️] [❌]     │   │
│ │ 💻 Sarah's MacBook        [10/10 Mbps] [⚙️] [❌]     │   │
│ │                                                       │   │
│ │ Drag device here to set HIGH priority                │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                             │
│ 🥉 NORMAL PRIORITY                                          │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ 📱 Sarah's iPhone         [5/5 Mbps]   [⚙️] [❌]     │   │
│ │ 📱 Tommy's iPad           [5/5 Mbps]   [⚙️] [❌]     │   │
│ │ 📺 Living Room TV         [8/8 Mbps]   [⚙️] [❌]     │   │
│ │                                                       │   │
│ │ Drag device here to set NORMAL priority              │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                             │
│ 🔽 LOW PRIORITY                                             │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ 🔌 Smart Home Devices     [2/2 Mbps]   [⚙️] [❌]     │   │
│ │ 📷 Security Cameras (4)   [1/1 Mbps each] [⚙️] [❌]  │   │
│ │                                                       │   │
│ │ Drag device here to set LOW priority                 │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                             │
│ [Save Configuration]  [Load Preset]  [Reset]               │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎚️ PER-DEVICE BANDWIDTH CONTROL

### Individual Device Settings
```
┌─────────────────────────────────────────────────────────────┐
│ Device: Xbox Series X                                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Priority Level: 🥇 CRITICAL                                 │
│ [Critical ▼] [High] [Normal] [Low]                         │
│                                                             │
│ Bandwidth Limits:                                           │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                             │
│ Download Speed:                                             │
│ ⚪ Unlimited (recommended for gaming)                      │
│ ⚪ Limited: [10___] Mbps                                   │
│                                                             │
│ Upload Speed:                                               │
│ ⚪ Unlimited (recommended for gaming)                      │
│ ⚪ Limited: [5___] Mbps                                    │
│                                                             │
│ Advanced Options:                                           │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                             │
│ ☑ Guarantee minimum bandwidth (5 Mbps)                     │
│ ☑ Prioritize UDP traffic (gaming packets)                  │
│ ☑ Minimize latency (QoS priority)                          │
│ ☐ Throttle during specific hours                           │
│ ☐ Apply data cap (monthly limit)                           │
│                                                             │
│ Latency Optimization:                                       │
│ ☑ Enable Gaming Mode (lowest latency)                      │
│ ☑ Disable buffering (instant packet delivery)              │
│ ☑ Prioritize over all other traffic                        │
│                                                             │
│ [Save Settings]  [Test Speed]  [Reset]                     │
└─────────────────────────────────────────────────────────────┘
```

### Bandwidth Throttling Controls
```
┌─────────────────────────────────────────────────────────────┐
│ Device: Security Camera (Front Door)                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Priority Level: 🔽 LOW                                      │
│                                                             │
│ Bandwidth Limits (Throttled):                               │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                             │
│ Download Speed: [1___] Mbps                                │
│ ├─────────────────────────────────────────┤               │
│ 0        5        10       15       20   Mbps              │
│                                                             │
│ Upload Speed: [0.5__] Mbps (camera stream)                │
│ ├─────────────────────────────────────────┤               │
│ 0        1         2         3        5   Mbps             │
│                                                             │
│ Throttling Schedule:                                        │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                             │
│ ● Throttle 24/7 (always limited)                          │
│ ⚪ Throttle during peak hours only                         │
│   Peak Hours: [5pm__] to [11pm__]                         │
│ ⚪ Custom schedule                                          │
│                                                             │
│ Network Congestion Behavior:                                │
│ ● Pause uploads when network is busy                      │
│ ⚪ Continue at reduced speed (0.1 Mbps)                    │
│ ⚪ Queue uploads for later                                 │
│                                                             │
│ [Save Settings]  [Apply to All Cameras]                    │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 REAL-TIME MONITORING

### Network Dashboard
```
┌─────────────────────────────────────────────────────────────┐
│ Network Activity - Real-Time                                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Total Bandwidth Usage:                                      │
│ Download: 45.2 / 100 Mbps [████████░░░░░░░░░░░░] 45%      │
│ Upload:    8.3 / 20 Mbps  [████████░░░░░░░░░░░░] 42%      │
│                                                             │
│ Active Devices (7 of 10):                                   │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                             │
│ 🥇 CRITICAL                                                 │
│ 🎮 Xbox Series X                                           │
│    15.2 Mbps ↓ | 2.1 Mbps ↑ | Latency: 12ms ✓             │
│    [████████████████░░░░░░░░░] 60% of allocated            │
│    Status: Gaming - Call of Duty                           │
│                                                             │
│ 💻 Dad's Work Laptop                                       │
│    8.5 Mbps ↓ | 3.2 Mbps ↑ | Latency: 35ms ✓              │
│    [████████████░░░░░░░░░░░░] 50% of allocated            │
│    Status: Zoom call active                                │
│                                                             │
│ 🥈 HIGH                                                     │
│ 💻 Sarah's MacBook                                         │
│    12.3 Mbps ↓ | 0.8 Mbps ↑ | Latency: 28ms ✓             │
│    [████████████████████░░░░] 80% of allocated            │
│    Status: Streaming YouTube                               │
│                                                             │
│ 🥉 NORMAL                                                   │
│ 📱 Sarah's iPhone                                          │
│    3.2 Mbps ↓ | 0.5 Mbps ↑ | Latency: 45ms                │
│    [████████░░░░░░░░░░░░░░░░] 32% of allocated            │
│    Status: Browsing Instagram                              │
│                                                             │
│ 📺 Living Room TV                                          │
│    5.8 Mbps ↓ | 0.1 Mbps ↑ | Latency: 52ms                │
│    [██████████████░░░░░░░░░░] 58% of allocated            │
│    Status: Netflix (1080p)                                 │
│                                                             │
│ 🔽 LOW (Throttled)                                          │
│ 📷 Front Camera                                            │
│    0.5 Mbps ↓ | 0.3 Mbps ↑ | Latency: 120ms               │
│    [████████████████████████] 100% throttled               │
│    Status: Recording (throttled)                           │
│                                                             │
│ 📷 Back Camera                                             │
│    0.5 Mbps ↓ | 0.3 Mbps ↑ | Latency: 115ms               │
│    [████████████████████████] 100% throttled               │
│    Status: Recording (throttled)                           │
│                                                             │
│ [Refresh: 1s]  [Pause]  [Export Graph]                     │
└─────────────────────────────────────────────────────────────┘
```

### Bandwidth History Graph
```
┌─────────────────────────────────────────────────────────────┐
│ Bandwidth Usage - Last Hour                                  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ 100 Mbps ┤                                                 │
│          │              ╭╮                                  │
│  80 Mbps ┤            ╭─╯╰╮                                │
│          │          ╭─╯   ╰╮                               │
│  60 Mbps ┤        ╭─╯      ╰╮                              │
│          │      ╭─╯         ╰╮      ╭╮                     │
│  40 Mbps ┤    ╭─╯            ╰╮   ╭─╯╰╮                    │
│          │  ╭─╯               ╰╮╭─╯   ╰─╮                  │
│  20 Mbps ┤╭─╯                  ╰╯       ╰──────           │
│          ││                                                │
│   0 Mbps ┴┴────────────────────────────────────────────    │
│           5:00   5:15   5:30   5:45   6:00  PM            │
│                                                             │
│ Legend:                                                     │
│ ─── Download  ─ ─ Upload  ╋╋╋ Gaming  ⊓⊓⊓ Streaming       │
│                                                             │
│ Events:                                                     │
│ 5:15 PM - Gaming started (Xbox)                            │
│ 5:32 PM - Zoom call started (Dad's laptop)                │
│ 5:48 PM - Netflix started (Living room TV)                │
│                                                             │
│ [1 Hour] [24 Hours] [7 Days] [30 Days]                    │
└─────────────────────────────────────────────────────────────┘
```

---

## 🤖 AI-POWERED ADAPTIVE QoS

### Smart Traffic Classification
```
┌─────────────────────────────────────────────────────────────┐
│ AI Traffic Classifier                                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ The system automatically detects and classifies traffic:    │
│                                                             │
│ 🎮 Gaming Traffic (Detected):                              │
│    • UDP packets to gaming servers                         │
│    • Small packet size (< 200 bytes)                       │
│    • High frequency (> 60 packets/sec)                     │
│    • Port ranges: 27000-28000, 3074, 3075                 │
│    Action: Prioritize, minimize latency                    │
│                                                             │
│ 📹 Video Streaming (Detected):                             │
│    • TCP connections to CDN servers                        │
│    • Large sustained download (> 5 Mbps)                   │
│    • Domains: *.netflix.com, *.youtube.com, etc.          │
│    Action: Guarantee minimum bandwidth                     │
│                                                             │
│ 💬 Video Calls (Detected):                                 │
│    • WebRTC connections                                     │
│    • Bidirectional traffic (upload + download)            │
│    • Domains: *. zoom.us, *.teams.microsoft.com            │
│    Action: Prioritize both upload and download            │
│                                                             │
│ ⬇️ Downloads (Detected):                                   │
│    • Large file transfers (> 100 MB)                       │
│    • HTTP/HTTPS with Accept-Ranges header                 │
│    • Steam, Epic Games, Windows Update traffic            │
│    Action: Throttle to background priority                │
│                                                             │
│ 🔒 VPN/Encrypted Traffic:                                  │
│    • OpenVPN, WireGuard, HTTPS traffic                     │
│    • Cannot classify content                                │
│    Action: Use device-level rules instead                  │
│                                                             │
│ Settings:                                                   │
│ ☑ Enable AI classification                                 │
│ ☑ Auto-adjust priorities based on detected traffic        │
│ ☑ Learn from usage patterns                                │
│ ☐ Manual classification only                               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Adaptive Bandwidth Allocation
```
Network Congestion Detected!
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

AI Response:
1. ✓ Identified heavy user: Sarah's MacBook (YouTube)
2. ✓ Reduced Sarah's bandwidth: 10 Mbps → 5 Mbps
3. ✓ Allocated freed bandwidth to: Dad's Zoom call
4. ✓ Gaming latency maintained: 12ms (no change)

Result:
• Dad's call quality improved (HD → Full HD)
• Sarah's YouTube downgraded (1080p → 720p)
• Xbox gaming unaffected
• Network congestion resolved

[View Details] [Disable Auto-Adjust]
```

---

## ⚙️ ADVANCED FEATURES

### Application-Specific Rules
```
┌─────────────────────────────────────────────────────────────┐
│ Application Rules                                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Create rules for specific applications:                     │
│                                                             │
│ Rule 1: Steam Downloads                                    │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ Detect: Traffic to *.steampowered.com               │   │
│ │         Port 27015-27050                             │   │
│ │         HTTP headers contain "Steam"                 │   │
│ │                                                       │   │
│ │ Action: Throttle to 2 Mbps                          │   │
│ │         Priority: LOW                                 │   │
│ │         Schedule: Throttle during 5pm-11pm only     │   │
│ │                                                       │   │
│ │ [Edit] [Delete] [Enable/Disable]                    │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                             │
│ Rule 2: Zoom Video Calls                                   │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ Detect: Traffic to *.zoom.us                        │   │
│ │         WebRTC connections                           │   │
│ │         Bidirectional 1-5 Mbps                       │   │
│ │                                                       │   │
│ │ Action: Guarantee 5 Mbps minimum                    │   │
│ │         Priority: CRITICAL                            │   │
│ │         No throttling ever                           │   │
│ │                                                       │   │
│ │ [Edit] [Delete] [Enable/Disable]                    │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                             │
│ [+ Add Rule]  [Import Rules]  [Export Rules]              │
└─────────────────────────────────────────────────────────────┘
```

### Time-Based Throttling
```
┌─────────────────────────────────────────────────────────────┐
│ Scheduled Throttling                                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Device: All Security Cameras (4 cameras)                    │
│                                                             │
│ Throttle Schedule:                                          │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                             │
│ Weekdays (Mon-Fri):                                         │
│ • 8am-5pm: Full speed (1 Mbps each) - Family away         │
│ • 5pm-11pm: Throttled (0.3 Mbps each) - Peak usage        │
│ • 11pm-8am: Paused - No one home, save bandwidth          │
│                                                             │
│ Weekends (Sat-Sun):                                         │
│ • All day: Throttled (0.5 Mbps each) - Family home        │
│                                                             │
│ Override:                                                   │
│ ☑ Temporarily boost to full speed on motion detection     │
│   Duration: 5 minutes after motion                         │
│                                                             │
│ [Save Schedule]  [Apply to Other Devices]                  │
└─────────────────────────────────────────────────────────────┘
```

### Data Cap Management
```
┌─────────────────────────────────────────────────────────────┐
│ Monthly Data Caps                                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Device: Sarah's MacBook                                     │
│                                                             │
│ Data Cap: 100 GB/month                                     │
│ Used: 68.5 GB (69%)                                        │
│ Remaining: 31.5 GB                                          │
│                                                             │
│ [████████████████████░░░░░░░░░] 69%                        │
│                                                             │
│ Projected usage: 95 GB (will stay under cap) ✓            │
│                                                             │
│ Actions when cap reached:                                   │
│ ● Throttle to 1 Mbps (slow but usable)                    │
│ ⚪ Block internet access completely                        │
│ ⚪ Send alert only (no action)                             │
│                                                             │
│ Warnings:                                                   │
│ ☑ Alert at 75% (75 GB)                                     │
│ ☑ Alert at 90% (90 GB)                                     │
│ ☑ Alert at 100% (cap reached)                              │
│                                                             │
│ Reset Date: 1st of each month                              │
│                                                             │
│ [Save Settings]  [View Usage History]                      │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔧 DATABASE SCHEMA

```sql
-- QoS priority settings
CREATE TABLE qos_priorities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    device_id INTEGER NOT NULL,
    priority_level INTEGER DEFAULT 50, -- 0-100 scale
    max_download_mbps INTEGER, -- NULL = unlimited
    max_upload_mbps INTEGER, -- NULL = unlimited
    min_guaranteed_mbps INTEGER, -- Minimum bandwidth guaranteed
    enable_latency_optimization BOOLEAN DEFAULT 0,
    enable_gaming_mode BOOLEAN DEFAULT 0,
    throttle_schedule TEXT, -- JSON: schedule rules
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES user_devices(id)
);

-- Application-specific rules
CREATE TABLE qos_application_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rule_name TEXT NOT NULL,
    detection_method TEXT, -- 'domain', 'port', 'pattern', 'dpi'
    detection_value TEXT, -- e.g., '*.zoom.us', '27015-27050', etc.
    priority_level INTEGER DEFAULT 50,
    max_bandwidth_mbps INTEGER,
    throttle_schedule TEXT, -- JSON
    is_enabled BOOLEAN DEFAULT 1,
    applies_to_devices TEXT, -- JSON array of device IDs, or 'all'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Smart modes
CREATE TABLE qos_modes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    mode_name TEXT, -- 'gaming', 'work', 'streaming', 'family'
    is_active BOOLEAN DEFAULT 0,
    configuration TEXT, -- JSON: complete mode settings
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Bandwidth usage tracking
CREATE TABLE bandwidth_usage (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    device_id INTEGER NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    download_mbps DECIMAL(10,2),
    upload_mbps DECIMAL(10,2),
    latency_ms INTEGER,
    packet_loss_percent DECIMAL(5,2),
    detected_application TEXT, -- AI classification result
    priority_applied INTEGER,
    FOREIGN KEY (device_id) REFERENCES user_devices(id)
);

-- Data caps
CREATE TABLE data_caps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    device_id INTEGER NOT NULL,
    cap_type TEXT, -- 'monthly', 'weekly', 'daily'
    cap_amount_gb INTEGER,
    used_amount_gb DECIMAL(10,2) DEFAULT 0,
    reset_date DATE,
    action_on_exceeded TEXT, -- 'throttle', 'block', 'alert_only'
    throttle_speed_mbps INTEGER,
    alert_at_percent INTEGER DEFAULT 75,
    FOREIGN KEY (device_id) REFERENCES user_devices(id)
);

-- QoS events log
CREATE TABLE qos_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_type TEXT, -- 'throttle', 'prioritize', 'congestion', 'cap_reached'
    device_id INTEGER,
    details TEXT, -- JSON with event details
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES user_devices(id)
);
```

---

## 🚀 API ENDPOINTS

### Priority Management
```
POST   /api/qos/set-priority.php       - Set device priority
GET    /api/qos/get-priority.php       - Get device priority
POST   /api/qos/set-bandwidth-limit.php - Set bandwidth limits
POST   /api/qos/enable-mode.php        - Activate smart mode
```

### Application Rules
```
POST   /api/qos/add-rule.php           - Add application rule
GET    /api/qos/list-rules.php         - List all rules
DELETE /api/qos/delete-rule.php        - Delete rule
PUT    /api/qos/update-rule.php        - Update rule
```

### Monitoring
```
GET    /api/qos/realtime-usage.php     - Get current bandwidth usage
GET    /api/qos/bandwidth-history.php  - Get historical usage data
GET    /api/qos/device-stats.php       - Get per-device statistics
```

### Data Caps
```
POST   /api/qos/set-data-cap.php       - Set monthly data cap
GET    /api/qos/check-usage.php        - Check current usage vs cap
POST   /api/qos/reset-cap.php          - Reset data cap
```

---

**Status:** Complete Specification - Ready for Implementation  
**Priority:** High (solves major pain point)  
**Better Than Traditional QoS:** ✓ Drag-and-drop, AI-powered, per-device control  
**Estimated Implementation Time:** 10-12 days
