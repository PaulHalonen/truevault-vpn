

# 🖥️ SERVER INFRASTRUCTURE

## VPN SERVERS:
| # | Name | IP | Provider | Access |
|---|------|-----|----------|--------|
| 1 | New York | 66.94.103.91 | Contabo | ALL users |
| 2 | St. Louis | 144.126.133.253 | Contabo | seige235@yahoo.com ONLY |
| 3 | Dallas | 66.241.124.4 | Fly.io | ALL users (limited) |
| 4 | Toronto | 66.241.125.247 | Fly.io | ALL users (limited) |

## SERVER RULES:
- St. Louis is DEDICATED to seige235@yahoo.com - invisible to others
- Port forwarding ONLY on Contabo (NY + dedicated)
- Fly.io = NO port forwarding, NO gaming, NO torrents
- Fly.io = streaming only (Netflix, etc.)

## SSH ACCESS:
- Contabo root password: `Andassi8`
- Login: `paulhalonen@gmail.com` / `Asasasas4!`

---

# 📂 FILE LOCATIONS

## LOCAL:
```
E:\Documents\GitHub\truevault-vpn\
├── Master_Checklist/     ← FOLLOW THESE
├── MASTER_BLUEPRINT/     ← REFERENCE THESE
├── website/              ← BUILD HERE
├── chat_log.txt          ← APPEND TO THIS
└── HANDOFF_*.md          ← READ THESE FIRST
```

## SERVER:
```
FTP Host: the-truth-publishing.com
FTP User: kahlen@the-truth-publishing.com
FTP Pass: AndassiAthena8
Path: /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/
```

---

# 💰 PLAN RESTRICTIONS

## Basic ($9.97):
- 3 VPN devices + 3 network devices
- 1 camera max
- 2 port forwards max
- NY Contabo only for port forward

## Family ($14.97):
- 5 VPN devices + 5 network devices
- 2 cameras max
- 5 port forwards max
- NY Contabo only for port forward

## Dedicated ($29.97):
- 99 devices total
- Unlimited cameras
- Unlimited port forwards
- Own dedicated server + all shared

---

# 📝 CHAT LOG RULES

1. **Append every 15 minutes** during work
2. **Include user requests** (not just Claude's work)
3. **Backup if > 30KB**: `chat_log_backup_[date].txt`
4. **Never overwrite** - append or backup only
5. **Format**:
```
============================================================
SESSION: [Date] - [Time]
============================================================

USER REQUEST:
"[exact user message]"

CLAUDE ACTION:
[what was done]

FILES CREATED/MODIFIED:
- [file1]
- [file2]

============================================================
```

---

# ⚠️ COMMON MISTAKES TO AVOID

1. ❌ Writing large files in one message (causes crash)
2. ❌ Testing during build phase
3. ❌ Skipping checklist tasks
4. ❌ Forgetting to backup chat log
5. ❌ Overwriting files instead of appending

---

# ✅ SESSION START CHECKLIST

Before doing ANY work:
- [ ] Read this handoff completely
- [ ] Check chat_log.txt for last work done
- [ ] Open MASTER_CHECKLIST_PART9.md
- [ ] Find the next incomplete task
- [ ] Work in small chunks (1 file at a time)
- [ ] Commit to Git every 2-3 files

---

**END OF HANDOFF**
**Next Task: MASTER_CHECKLIST_PART9.md → Task 9.8.3**
