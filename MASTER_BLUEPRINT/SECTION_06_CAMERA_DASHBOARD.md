# SECTION 6: CAMERA DASHBOARD

**Created:** January 15, 2026  
**Status:** Complete Technical Specification  
**Priority:** MEDIUM - Value-Add Feature  
**Complexity:** MEDIUM - Video Streaming  

---

## 📋 TABLE OF CONTENTS

1. [What is Camera Dashboard?](#what-is)
2. [Why This Matters](#why-matters)
3. [How It Works](#how-it-works)
4. [Camera Discovery](#discovery)
5. [Live View](#live-view)
6. [Multi-Camera Grid](#grid)
7. [Recording & Playback](#recording)
8. [Motion Detection](#motion)
9. [Mobile Access](#mobile)
10. [Technical Implementation](#implementation)
11. [Supported Cameras](#supported)
12. [Security](#security)

---

## 📷 WHAT IS CAMERA DASHBOARD?

### **Simple Explanation**

The Camera Dashboard lets you **view and monitor all your IP cameras** through TrueVault VPN:
- ✅ See live video feeds from all cameras
- ✅ View multiple cameras at once (grid view)
- ✅ Access from anywhere (phone, computer, tablet)
- ✅ Get motion detection alerts
- ✅ No monthly cloud fees
- ✅ Private and secure (encrypted tunnel)

### **Integration with Network Scanner**

**Automatic Discovery:**
```
User runs Network Scanner
    ↓
Scanner finds: "Geeni Camera at 192.168.1.112"
    ↓
User enables port forwarding
    ↓
Camera automatically appears in Camera Dashboard
    ↓
User clicks camera to view live feed
    ↓
DONE!
```

**No manual configuration needed!**

---

## 💡 WHY THIS MATTERS

### **The Problem with Traditional Camera Systems**

**Cloud-Based Cameras (Ring, Nest, Arlo):**
- ❌ **Monthly fees:** $3-10 per camera
- ❌ **Privacy concerns:** Your footage on their servers
- ❌ **Data limits:** Only 30-60 days storage
- ❌ **Internet dependent:** No internet = no cameras
- ❌ **Subscription lockout:** Stop paying = lose access

**Example Costs:**
```
3 cameras × $10/month = $30/month
$30 × 12 months = $360/year
$360 × 5 years = $1,800!
```

### **TrueVault Solution**

**Free Camera Monitoring:**
- ✅ **$0/month fees** - No cloud subscriptions
- ✅ **Private storage** - Your footage on your devices
- ✅ **Unlimited storage** - Limited only by your hard drive
- ✅ **Local recording** - Works without internet
- ✅ **Own your data** - Complete privacy

**Cost Comparison:**

| Feature | Ring/Nest | TrueVault |
|---------|-----------|-----------|
| **Monthly Fee** | $10/camera | $0 |
| **Annual Cost (3 cams)** | $360 | $0 |
| **5-Year Cost** | $1,800 | $0 |
| **Storage** | 30-60 days | Unlimited |
| **Privacy** | Cloud (3rd party) | Private (encrypted) |
| **Internet Required** | Yes | No (local recording) |

---

## ⚙️ HOW IT WORKS

### **Complete Workflow**

```
AUTOMATIC CAMERA DISCOVERY
    ↓
[Network Scanner finds cameras]
    ↓
[User enables port forwarding]
    ↓
[Camera appears in dashboard]
    ↓
USER CLICKS CAMERA
    ↓
[Dashboard connects via RTSP]
    ↓
[Streams video through VPN tunnel]
    ↓
[Displays in browser/app]
    ↓
USER SEES LIVE VIDEO
```

### **Architecture**

```
┌─────────────────────────────────────────────┐
│ User's Browser/App                          │
│ (Phone, Computer, Tablet)                   │
└────────────────┬────────────────────────────┘
                 │
                 │ HTTPS (encrypted)
                 ↓
┌─────────────────────────────────────────────┐
│ TrueVault Camera Dashboard                  │
│ (Web interface)                             │
└────────────────┬────────────────────────────┘
                 │
                 │ VPN Tunnel
                 ↓
┌─────────────────────────────────────────────┐
│ User's Home Network                         │
│                                             │
│  📷 Camera 1 (192.168.1.112)               │
│  📷 Camera 2 (192.168.1.113)               │
│  📷 Camera 3 (192.168.1.114)               │
└─────────────────────────────────────────────┘
```

**Key Points:**
- ✅ Video never goes through TrueVault servers
- ✅ Direct encrypted tunnel to home cameras
- ✅ No cloud storage (privacy!)
- ✅ Low latency (direct connection)

---

## 🔍 CAMERA DISCOVERY

### **How Cameras Are Found**

**Step 1: Network Scanner identifies cameras**
```python
# Scanner checks MAC address vendor
mac_prefix = "D8:1D:2E"  # Geeni camera
vendor = "Geeni"
device_type = "IP Camera"

# Scanner checks open ports
if port_554_open:  # RTSP streaming port
    confirmed_camera = True
```

**Step 2: Camera added to database**
```sql
INSERT INTO discovered_devices (
    user_id, device_name, device_type, 
    local_ip, mac_address, rtsp_port
) VALUES (
    5, 'Geeni Camera', 'ip_camera',
    '192.168.1.112', 'D8:1D:2E:12:34:56', 554
);
```

**Step 3: Camera appears in dashboard**
```
Camera Dashboard shows:
- 📷 Geeni Camera (Living Room)
- 📷 Wyze Cam (Front Door)  
- 📷 Hikvision (Backyard)
```

### **Supported Camera Types**

**Budget Cameras ($20-50):**
- ✅ Geeni (Walmart brand)
- ✅ Wyze Cam
- ✅ Yi Home Camera
- ✅ Merkury (Tuya-based)

**Mid-Range ($50-150):**
- ✅ Reolink
- ✅ Amcrest
- ✅ Ring (local mode)
- ✅ Nest (local mode)

**Professional ($150+):**
- ✅ Hikvision
- ✅ Dahua
- ✅ Axis Communications
- ✅ Ubiquiti UniFi

**Requirements:**
- ✅ Must support RTSP protocol
- ✅ Must have local network access
- ✅ Must allow port access (554, 8080)

---

## 📺 LIVE VIEW

### **Single Camera View**

```html
┌─────────────────────────────────────────────────┐
│ 📷 Living Room Camera                           │
├─────────────────────────────────────────────────┤
│                                                 │
│          ┌─────────────────────────┐            │
│          │                         │            │
│          │   [LIVE VIDEO FEED]     │            │
│          │                         │            │
│          │   📹 Recording          │            │
│          │   🔊 Audio On           │            │
│          │                         │            │
│          └─────────────────────────┘            │
│                                                 │
│  ⏸️ Pause  📸 Snapshot  🎙️ Mic  ⚙️ Settings   │
│                                                 │
│  Status: ✅ Connected                           │
│  Quality: 1080p @ 30fps                         │
│  Latency: 245ms                                 │
│                                                 │
└─────────────────────────────────────────────────┘
```

**Features:**
- ✅ Full screen mode
- ✅ Zoom and pan (if camera supports)
- ✅ Audio (if camera has microphone)
- ✅ Two-way audio (if camera supports)
- ✅ Quick snapshots
- ✅ Quality selection (1080p, 720p, 480p)

### **Video Player Implementation**

**Using HLS.js for streaming:**

```html
<video id="camera-feed" controls autoplay></video>

<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script>
const video = document.getElementById('camera-feed');
const hls = new Hls();

// Load camera stream
hls.loadSource('/api/camera-stream.php?camera_id=cam_112');
hls.attachMedia(video);

// Play when ready
hls.on(Hls.Events.MANIFEST_PARSED, function() {
    video.play();
});
</script>
```

---

## 🎛️ MULTI-CAMERA GRID

### **Grid View (2x2, 3x3, 4x4)**

```html
┌───────────────────────────────────────────────────┐
│ Camera Dashboard                    [Grid: 2×2 ▼] │
├───────────────────────────────────────────────────┤
│                                                   │
│  ┌──────────────────┐  ┌──────────────────┐      │
│  │ 📷 Living Room   │  │ 📷 Front Door    │      │
│  │ [LIVE FEED]      │  │ [LIVE FEED]      │      │
│  │                  │  │                  │      │
│  └──────────────────┘  └──────────────────┘      │
│                                                   │
│  ┌──────────────────┐  ┌──────────────────┐      │
│  │ 📷 Backyard      │  │ 📷 Garage        │      │
│  │ [LIVE FEED]      │  │ [LIVE FEED]      │      │
│  │                  │  │                  │      │
│  └──────────────────┘  └──────────────────┘      │
│                                                   │
│  [Fullscreen] [Add Camera] [Settings]            │
│                                                   │
└───────────────────────────────────────────────────┘
```

**Grid Options:**
- 1×1: Single camera (full screen)
- 2×2: 4 cameras
- 3×3: 9 cameras
- 4×4: 16 cameras

**Features:**
- ✅ Click camera to expand to full screen
- ✅ Drag to rearrange cameras
- ✅ Name cameras (Living Room, Front Door, etc.)
- ✅ Show/hide inactive cameras
- ✅ Auto-cycle through cameras

### **Implementation**

```javascript
class CameraGrid {
    constructor(gridSize = '2x2') {
        this.gridSize = gridSize;
        this.cameras = [];
        this.activeStreams = [];
    }
    
    setGridSize(size) {
        // size = '2x2', '3x3', '4x4'
        this.gridSize = size;
        this.renderGrid();
    }
    
    addCamera(cameraId, cameraName, streamUrl) {
        this.cameras.push({
            id: cameraId,
            name: cameraName,
            url: streamUrl,
            active: true
        });
        this.renderGrid();
    }
    
    renderGrid() {
        const container = document.getElementById('camera-grid');
        const [rows, cols] = this.gridSize.split('x').map(Number);
        
        container.style.display = 'grid';
        container.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
        container.style.gridTemplateRows = `repeat(${rows}, 1fr)`;
        
        // Clear existing
        container.innerHTML = '';
        
        // Add cameras
        for (let i = 0; i < rows * cols; i++) {
            const camera = this.cameras[i];
            if (camera) {
                const tile = this.createCameraTile(camera);
                container.appendChild(tile);
            } else {
                const empty = this.createEmptyTile();
                container.appendChild(empty);
            }
        }
    }
    
    createCameraTile(camera) {
        const tile = document.createElement('div');
        tile.className = 'camera-tile';
        tile.innerHTML = `
            <div class="camera-name">${camera.name}</div>
            <video autoplay muted data-camera="${camera.id}"></video>
            <div class="camera-overlay">
                <button onclick="expandCamera('${camera.id}')">⛶ Fullscreen</button>
            </div>
        `;
        
        // Start streaming
        this.startStream(camera.id, camera.url);
        
        return tile;
    }
    
    startStream(cameraId, streamUrl) {
        const video = document.querySelector(`video[data-camera="${cameraId}"]`);
        const hls = new Hls();
        hls.loadSource(streamUrl);
        hls.attachMedia(video);
        
        this.activeStreams.push({ cameraId, hls });
    }
}

// Initialize
const grid = new CameraGrid('2x2');
grid.addCamera('cam_112', 'Living Room', '/stream/cam_112.m3u8');
grid.addCamera('cam_113', 'Front Door', '/stream/cam_113.m3u8');
```

---

## 💾 RECORDING & PLAYBACK

### **Recording Options**

**1. Continuous Recording**
- Camera records 24/7 to local storage
- Automatically overwrites old footage
- Configurable retention (7, 14, 30 days)

**2. Motion-Triggered Recording**
- Only records when motion detected
- Saves storage space
- Gets alerts when motion detected

**3. Scheduled Recording**
- Record only during specific hours
- Example: 10 PM - 6 AM (nighttime only)
- Saves storage and bandwidth

### **Storage Locations**

**Option 1: Local Storage (User's Computer)**
```
C:\Users\John\TruthVault\Recordings\
├── 2026-01-15\
│   ├── living-room-08-00-00.mp4
│   ├── living-room-09-00-00.mp4
│   └── front-door-08-30-00.mp4
```

**Option 2: NAS (Network Attached Storage)**
```
\\NAS\Recordings\
├── Living Room\
│   └── 2026-01-15\
└── Front Door\
    └── 2026-01-15\
```

**Option 3: Cloud Storage (Optional)**
- User's own Dropbox, Google Drive, etc.
- TrueVault doesn't host storage
- User controls their data

### **Playback Interface**

```html
┌─────────────────────────────────────────────────┐
│ 📷 Playback - Living Room Camera                │
├─────────────────────────────────────────────────┤
│                                                 │
│          ┌─────────────────────────┐            │
│          │   [RECORDED VIDEO]      │            │
│          │                         │            │
│          │   Jan 15, 2026          │            │
│          │   8:45 AM               │            │
│          └─────────────────────────┘            │
│                                                 │
│  ◀◀ ⏮️ ⏸️ ⏭️ ▶▶                                │
│  ├──────────●────────────────────┤ 8:45 AM     │
│  8:00 AM                       9:00 AM          │
│                                                 │
│  Timeline:                                      │
│  ┌─┬─┬───┬─┬──────┬─┬─┐                        │
│  8  8:15  8:30  8:45  9:00                     │
│    └─ Motion detected                          │
│                                                 │
│  [Download Clip] [Share] [Delete]              │
│                                                 │
└─────────────────────────────────────────────────┘
```

**Features:**
- ✅ Timeline view with motion markers
- ✅ Jump to motion events
- ✅ Download clips
- ✅ Share clips (generate link)
- ✅ Speed control (0.5x, 1x, 2x, 4x)

---

## 🚨 MOTION DETECTION

### **How It Works**

**Method 1: Camera Built-In Detection**
```
Camera detects motion internally
    ↓
Sends alert to TrueVault API
    ↓
TrueVault sends notification to user
    ↓
User receives push notification or email
```

**Method 2: Software Detection**
```
TrueVault continuously captures frames
    ↓
Compares frame N to frame N-1
    ↓
If > 5% difference = motion detected
    ↓
Triggers recording and alert
```

### **Alert System**

**Alert Types:**
- 📱 **Push notification** (mobile app)
- 📧 **Email** (with snapshot)
- 💬 **SMS** (optional, carrier charges)
- 🔔 **Browser notification** (desktop)

**Alert Example (Push):**
```
┌────────────────────────────────┐
│ TrueVault Camera Alert         │
├────────────────────────────────┤
│ 📷 Front Door Camera           │
│ Motion detected at 8:45 AM     │
│                                │
│ [Thumbnail image]              │
│                                │
│ [View Live] [Dismiss]          │
└────────────────────────────────┘
```

**Alert Settings:**
```html
┌─────────────────────────────────────────────────┐
│ Motion Detection Settings                       │
├─────────────────────────────────────────────────┤
│                                                 │
│ Living Room Camera:                             │
│                                                 │
│ ☑ Enable motion detection                      │
│ ☑ Send push notifications                      │
│ ☑ Send email alerts                            │
│ ☐ Send SMS alerts                              │
│                                                 │
│ Sensitivity: ├─────●───┤ Medium                │
│                                                 │
│ Active hours:                                   │
│ ○ Always                                        │
│ ● Scheduled                                     │
│   From: [10:00 PM ▼] To: [6:00 AM ▼]          │
│                                                 │
│ ☑ Record on motion                             │
│   Pre-record: [5 seconds ▼]                    │
│   Post-record: [30 seconds ▼]                  │
│                                                 │
│        [Save Settings]                          │
│                                                 │
└─────────────────────────────────────────────────┘
```

---

## 📱 MOBILE ACCESS

### **Mobile App**

**Features:**
- ✅ View all cameras on phone
- ✅ Push notifications for motion
- ✅ Two-way audio (talk through camera)
- ✅ PTZ controls (pan, tilt, zoom)
- ✅ Quick snapshots
- ✅ Download recordings

### **Mobile UI**

```
┌───────────────────────┐
│ ☰  TrueVault Cameras  │
├───────────────────────┤
│                       │
│ 🟢 4 Cameras Online   │
│ 🔴 0 Offline          │
│                       │
│ ┌───────────────────┐ │
│ │ 📷 Living Room    │ │
│ │ [Live thumbnail]  │ │
│ │ Last motion: Now  │ │
│ └───────────────────┘ │
│                       │
│ ┌───────────────────┐ │
│ │ 📷 Front Door     │ │
│ │ [Live thumbnail]  │ │
│ │ Last motion: 2m   │ │
│ └───────────────────┘ │
│                       │
│ ┌───────────────────┐ │
│ │ 📷 Backyard       │ │
│ │ [Live thumbnail]  │ │
│ │ No motion today   │ │
│ └───────────────────┘ │
│                       │
│ [+ Add Camera]        │
│                       │
└───────────────────────┘
```

**Tap camera to view full screen:**
```
┌───────────────────────┐
│ ← Living Room    ⚙️   │
├───────────────────────┤
│                       │
│                       │
│   [FULL SCREEN        │
│    VIDEO FEED]        │
│                       │
│                       │
│                       │
├───────────────────────┤
│ 📸 🎙️ 🔊 ⛶          │
└───────────────────────┘
```

---

## 💻 TECHNICAL IMPLEMENTATION

### **Database Schema**

**Table: cameras (in devices.db)**

```sql
CREATE TABLE IF NOT EXISTS cameras (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    
    -- Camera Info
    camera_id TEXT UNIQUE NOT NULL,
    camera_name TEXT NOT NULL,
    location TEXT,                    -- Living Room, Front Door, etc.
    
    -- Connection
    local_ip TEXT NOT NULL,
    rtsp_port INTEGER DEFAULT 554,
    rtsp_username TEXT,
    rtsp_password TEXT,               -- Encrypted
    rtsp_url TEXT,                    -- rtsp://192.168.1.112:554/stream
    
    -- Capabilities
    supports_audio BOOLEAN DEFAULT 0,
    supports_ptz BOOLEAN DEFAULT 0,   -- Pan/Tilt/Zoom
    supports_two_way BOOLEAN DEFAULT 0,
    max_resolution TEXT DEFAULT '1080p',
    
    -- Recording Settings
    recording_enabled BOOLEAN DEFAULT 0,
    recording_mode TEXT DEFAULT 'continuous',  -- continuous, motion, scheduled
    motion_detection BOOLEAN DEFAULT 0,
    motion_sensitivity INTEGER DEFAULT 50,     -- 0-100
    
    -- Storage
    storage_location TEXT,
    retention_days INTEGER DEFAULT 7,
    
    -- Status
    is_online BOOLEAN DEFAULT 1,
    last_seen DATETIME,
    
    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Table: motion_events**

```sql
CREATE TABLE IF NOT EXISTS motion_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    camera_id TEXT NOT NULL,
    
    -- Event Info
    detected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    snapshot_path TEXT,               -- Path to thumbnail image
    video_path TEXT,                  -- Path to recorded clip
    confidence INTEGER DEFAULT 100,    -- 0-100 (if using AI detection)
    
    -- Alert Status
    alert_sent BOOLEAN DEFAULT 0,
    alert_viewed BOOLEAN DEFAULT 0,
    
    FOREIGN KEY (camera_id) REFERENCES cameras(camera_id) ON DELETE CASCADE
);
```

---

### **API Endpoints**

**Endpoint 1: List Cameras**

**URL:** `GET /api/cameras.php?action=list`

**Response:**
```json
{
  "success": true,
  "cameras": [
    {
      "camera_id": "cam_112",
      "camera_name": "Living Room",
      "location": "Living Room",
      "local_ip": "192.168.1.112",
      "rtsp_url": "rtsp://192.168.1.112:554/stream",
      "is_online": true,
      "supports_audio": true,
      "max_resolution": "1080p",
      "recording_enabled": true,
      "motion_detection": true
    }
  ]
}
```

---

**Endpoint 2: Get Camera Stream**

**URL:** `GET /api/camera-stream.php?camera_id=cam_112`

**Implementation:**
```php
<?php
// Proxy RTSP stream to HLS (HTTP Live Streaming)
// Uses FFmpeg to convert RTSP to HLS

$cameraId = $_GET['camera_id'] ?? '';
$camera = getCamera($cameraId);

if (!$camera) {
    http_response_code(404);
    die('Camera not found');
}

// Build RTSP URL
$rtspUrl = buildRTSPUrl($camera);

// Convert to HLS using FFmpeg
$outputPath = "/tmp/streams/{$cameraId}.m3u8";

$cmd = "ffmpeg -rtsp_transport tcp -i '{$rtspUrl}' " .
       "-c:v copy -c:a aac -f hls " .
       "-hls_time 2 -hls_list_size 3 " .
       "-hls_flags delete_segments " .
       "{$outputPath} > /dev/null 2>&1 &";

exec($cmd);

// Serve HLS stream
header('Content-Type: application/vnd.apple.mpegurl');
readfile($outputPath);
```

---

**Endpoint 3: Motion Events**

**URL:** `GET /api/cameras.php?action=motion_events&camera_id=cam_112`

**Response:**
```json
{
  "success": true,
  "events": [
    {
      "id": 1,
      "detected_at": "2026-01-15 08:45:00",
      "snapshot_url": "/recordings/snapshots/cam_112_20260115_084500.jpg",
      "video_url": "/recordings/clips/cam_112_20260115_084500.mp4",
      "alert_viewed": false
    }
  ]
}
```

---

## 📷 SUPPORTED CAMERAS

### **Camera Compatibility**

**Requirements:**
1. ✅ Supports RTSP protocol
2. ✅ Accessible on local network
3. ✅ Known RTSP URL format

**RTSP URL Formats:**

**Geeni/Tuya:**
```
rtsp://192.168.1.112:554/stream
```

**Wyze:**
```
rtsp://192.168.1.113:554/live
```

**Hikvision:**
```
rtsp://admin:password@192.168.1.114:554/Streaming/Channels/101
```

**Dahua:**
```
rtsp://admin:password@192.168.1.115:554/cam/realmonitor?channel=1&subtype=0
```

**Reolink:**
```
rtsp://admin:password@192.168.1.116:554/h264Preview_01_main
```

**Amcrest:**
```
rtsp://admin:password@192.168.1.117:554/cam/realmonitor?channel=1&subtype=1
```

### **Camera Detection Database**

```python
CAMERA_RTSP_FORMATS = {
    "Geeni": "rtsp://{ip}:554/stream",
    "Wyze": "rtsp://{ip}:554/live",
    "Hikvision": "rtsp://{user}:{pass}@{ip}:554/Streaming/Channels/101",
    "Dahua": "rtsp://{user}:{pass}@{ip}:554/cam/realmonitor?channel=1&subtype=0",
    "Reolink": "rtsp://{user}:{pass}@{ip}:554/h264Preview_01_main",
    "Amcrest": "rtsp://{user}:{pass}@{ip}:554/cam/realmonitor?channel=1&subtype=1",
}

def buildRTSPUrl(camera):
    vendor = camera['vendor']
    format_template = CAMERA_RTSP_FORMATS.get(vendor)
    
    if not format_template:
        # Try generic RTSP
        format_template = "rtsp://{ip}:554/stream"
    
    return format_template.format(
        ip=camera['local_ip'],
        user=camera.get('rtsp_username', 'admin'),
        pass=camera.get('rtsp_password', '')
    )
```

---

## 🔒 SECURITY

### **Stream Encryption**

**All camera streams encrypted:**
```
User Browser
    ↓ HTTPS
TrueVault Server (proxy)
    ↓ VPN Tunnel (WireGuard)
Home Camera
```

**Benefits:**
- ✅ No one can intercept video
- ✅ ISP can't see camera feeds
- ✅ Complete privacy

### **Access Control**

**Only authorized users can view cameras:**
```php
// Verify camera belongs to user
function getCamera($cameraId, $userId) {
    global $db;
    
    $stmt = $db->prepare("
        SELECT * FROM cameras 
        WHERE camera_id = ? AND user_id = ?
    ");
    $stmt->execute([$cameraId, $userId]);
    
    return $stmt->fetch();
}

// If camera not found or belongs to different user = Access Denied
```

### **Password Storage**

**Camera passwords encrypted:**
```php
// Encrypt before storing
$encrypted = openssl_encrypt(
    $rtsp_password,
    'AES-256-CBC',
    ENCRYPTION_KEY,
    0,
    ENCRYPTION_IV
);

// Decrypt when needed
$decrypted = openssl_decrypt(
    $encrypted,
    'AES-256-CBC',
    ENCRYPTION_KEY,
    0,
    ENCRYPTION_IV
);
```

### **Rate Limiting**

**Prevent abuse:**
```php
// Limit stream requests
if (getUserStreamCount($userId) > 10) {
    sendError('Too many active streams');
}

// Limit motion alerts
if (getRecentAlerts($cameraId, '5 minutes') > 10) {
    // Suppress alerts (camera might be malfunctioning)
}
```

---

## 🎯 USE CASES

### **Use Case 1: Home Security**

**Setup:**
- 3 cameras: Front door, backyard, garage
- Motion detection enabled 24/7
- Push notifications to phone

**Benefits:**
- See who's at the door from anywhere
- Get alerts if someone in backyard
- Review footage if package stolen

---

### **Use Case 2: Baby Monitor**

**Setup:**
- Camera in baby's room
- Two-way audio enabled
- Motion/sound detection

**Benefits:**
- Check on baby from anywhere in house
- Get alert when baby wakes up
- Talk to baby through camera

---

### **Use Case 3: Pet Monitoring**

**Setup:**
- Camera in living room
- Scheduled recording (only when away)
- Motion alerts

**Benefits:**
- See what dog does when home alone
- Talk to pets through camera
- Catch pet mischief on video

---

### **Use Case 4: Elderly Care**

**Setup:**
- Cameras in common areas
- Fall detection (if supported)
- 24/7 recording

**Benefits:**
- Check on elderly parent remotely
- Get alerts if unusual inactivity
- Review footage if incident occurs

---

**END OF SECTION 6: CAMERA DASHBOARD**

**Next Section:** Section 7 (Parental Controls)  
**Status:** Section 6 Complete ✅  
**Lines:** ~1,400 lines  
**Created:** January 15, 2026 - 3:15 AM CST
