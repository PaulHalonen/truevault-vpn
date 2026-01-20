# PART 6A: FULL CAMERA DASHBOARD - THE FLAGSHIP FEATURE
**Created:** January 20, 2026 - 4:30 AM CST
**Priority:** 🚨 CRITICAL - THIS IS THE SELLING FEATURE
**Time:** 18-22 hours (3-4 days)
**Status:** NOT STARTED

---

## 🎯 VISION: CAMERA LIBERATION PLATFORM

**The Problem:**
- Ring charges $10/month per camera
- Nest charges $6-12/month per camera  
- Geeni/Wyze lock features behind cloud
- Users pay $300-600/year for features cameras ALREADY HAVE

**TrueVault Solution:**
- Brute force discover ALL cameras on network
- Bypass cloud completely
- Direct local access through VPN tunnel
- Zero monthly fees
- Full features unlocked

**Example Savings:**
```
3 Ring cameras × $10/month = $30/month
Annual: $360
5 Years: $1,800
10 Years: $3,600

TrueVault: $0 forever
```

---

## 🔓 CLOUD BYPASS STRATEGY

### **How Cloud Cameras Work:**

**Normal Flow (With Cloud):**
```
Camera → Manufacturer Cloud → User's Phone
         (Geeni/Ring/Nest servers)
         (Monthly fees required)
```

**TrueVault Flow (Cloud Bypass):**
```
Camera → User's Home Network → VPN Tunnel → User's Phone/Browser
         (Local RTSP/ONVIF)      (Encrypted)
         (Zero fees!)
```

### **Discovery Methods:**

**1. Standard Discovery (Part 6 - Already Built):**
- Scan network for known camera MAC addresses
- Check RTSP port 554
- Identify by vendor (Geeni, Wyze, Hikvision)

**2. Brute Force Discovery (NEW - Part 6A):**
- Scan ALL devices on network
- Test common camera ports (554, 8080, 80, 443, 8000, 8554)
- Try default credentials (admin/admin, admin/12345, etc.)
- ONVIF device discovery
- UPnP device discovery
- mDNS/Bonjour service discovery
- Detect camera by HTTP response patterns

**3. Cloud Camera Liberation (NEW - Part 6A):**
- Geeni/Tuya: Find local API endpoint (bypass cloud)
- Wyze: Enable RTSP firmware (free unlock)
- Ring: Local mode activation
- Nest: ONVIF discovery
- Generic: Brute force RTSP credentials

---

## 📋 TASK CHECKLIST - PART 6A

### **Section 1: Advanced Scanner - Brute Force Discovery (6-8 hours)**

#### **Task 6A.1: Update Network Scanner with Brute Force**
**File:** `truthvault_scanner.py` (update existing)
**Lines:** +400 lines

- [ ] Add brute force port scanning
- [ ] Test common camera ports on ALL devices
- [ ] Implement credential testing (safe, non-destructive)
- [ ] Add ONVIF discovery protocol
- [ ] Add UPnP camera discovery
- [ ] Add mDNS service detection
- [ ] Detect cameras by HTTP fingerprinting

**Brute Force Port List:**
```python
CAMERA_PORTS = {
    554: "RTSP",           # Standard RTSP
    8554: "RTSP-ALT",      # Alternative RTSP
    80: "HTTP",            # Web interface
    443: "HTTPS",          # Secure web
    8080: "HTTP-ALT",      # Alternative HTTP
    8000: "HTTP-ALT2",     # Another alternative
    8001: "HTTP-ALT3",     # Yet another
    37777: "Dahua",        # Dahua cameras
    34567: "Hikvision",    # Hikvision cameras
    9000: "Cameras",       # Generic camera port
    1935: "RTMP",          # Streaming
    5000: "ONVIF",         # ONVIF discovery
}
```

**Default Credential Testing:**
```python
COMMON_CREDENTIALS = [
    ("admin", "admin"),
    ("admin", ""),
    ("admin", "12345"),
    ("admin", "password"),
    ("root", "root"),
    ("root", "12345"),
    ("admin", "1234"),
    ("user", "user"),
    # ... 50+ common combos
]
```

**ONVIF Discovery:**
```python
# WS-Discovery for ONVIF cameras
# Finds cameras even if cloud-locked
def discover_onvif_cameras():
    # Broadcast ONVIF discovery request
    # Parse responses
    # Extract camera capabilities
    # Return local access URLs
```

**Verification:**
- [ ] Scanner finds cameras missed by basic scan
- [ ] Detects Geeni cameras in cloud mode
- [ ] Detects Ring cameras in local mode
- [ ] Detects Wyze cameras
- [ ] Finds ONVIF cameras
- [ ] Safe (doesn't crash cameras)

---

#### **Task 6A.2: Cloud Bypass - Geeni/Tuya Cameras**
**File:** `includes/CloudBypass.php`
**Lines:** ~250 lines

- [ ] Create CloudBypass.php helper class
- [ ] Geeni/Tuya local API discovery
- [ ] Extract local encryption keys
- [ ] Generate local RTSP URLs
- [ ] Bypass Geeni cloud completely

**Geeni/Tuya Local API:**
```php
class CloudBypass {
    // Tuya/Geeni cameras have local API
    // Discover local IP and key
    public function getTuyaLocalAccess($device_id) {
        // 1. Find camera on local network
        // 2. Extract device key from app backup
        // 3. Generate local API endpoint
        // 4. Return RTSP URL (bypassing cloud)
    }
}
```

**How Geeni Bypass Works:**
```
1. Scanner finds Geeni camera MAC
2. Detect if camera is cloud-only mode
3. Query local Tuya protocol (port 6668)
4. Extract camera's local key
5. Generate RTSP URL: rtsp://192.168.1.x:8554/stream
6. Connect directly (cloud bypassed!)
```

- [ ] Upload and test with real Geeni camera

---

#### **Task 6A.3: Cloud Bypass - Wyze Cameras**
**File:** Add to `CloudBypass.php`
**Lines:** +150 lines

- [ ] Detect Wyze cameras
- [ ] Check if RTSP firmware enabled
- [ ] Provide RTSP firmware flash instructions
- [ ] Generate RTSP URL after flash

**Wyze RTSP Unlock:**
```php
public function getWyzeRTSP($camera_ip) {
    // Check if RTSP firmware installed
    $rtsp_enabled = $this->checkWyzeRTSP($camera_ip);
    
    if (!$rtsp_enabled) {
        // Return instructions to flash RTSP firmware
        return [
            'status' => 'needs_flash',
            'instructions' => [
                '1. Download Wyze RTSP firmware',
                '2. Flash via microSD card',
                '3. Camera reboots with RTSP enabled'
            ],
            'firmware_url' => 'https://support.wyze.com/hc/en-us/articles/360026245231'
        ];
    }
    
    // RTSP enabled, return URL
    return [
        'status' => 'ready',
        'rtsp_url' => "rtsp://$camera_ip/live",
        'credentials' => 'admin/admin'
    ];
}
```

- [ ] Upload and test

---

#### **Task 6A.4: Cloud Bypass - Ring Cameras**
**File:** Add to `CloudBypass.php`
**Lines:** +120 lines

- [ ] Detect Ring cameras
- [ ] Enable local mode (if available)
- [ ] ONVIF discovery for Ring
- [ ] Generate local access URL

**Ring Local Mode:**
```php
public function getRingLocalAccess($camera_ip) {
    // Ring has limited local access via ONVIF
    // Requires Ring account credentials
    
    // 1. Check if camera supports local mode
    // 2. User provides Ring credentials (one-time)
    // 3. Extract ONVIF credentials
    // 4. Connect via ONVIF (bypassing cloud)
}
```

- [ ] Upload and test

---

### **Section 2: Live Video Streaming Interface (5-6 hours)**

#### **Task 6A.5: Create Live Video Player**
**File:** `/dashboard/cameras.php` (update existing)
**Lines:** +350 lines

- [ ] Update camera dashboard with live streaming
- [ ] Integrate HLS.js for video playback
- [ ] Add video player controls
- [ ] Add quality selection
- [ ] Add full screen mode
- [ ] Add snapshot button

**Video Player Interface:**
```html
<div class="camera-player">
    <video id="camera-feed" controls autoplay></video>
    
    <div class="player-controls">
        <button id="play-pause">⏸️ Pause</button>
        <button id="snapshot">📸 Snapshot</button>
        <button id="fullscreen">⛶ Full Screen</button>
        <select id="quality">
            <option value="1080">1080p</option>
            <option value="720">720p</option>
            <option value="480">480p</option>
        </select>
    </div>
    
    <div class="player-stats">
        <span>Status: ✅ Connected</span>
        <span>Quality: 1080p @ 30fps</span>
        <span>Latency: 245ms</span>
    </div>
</div>
```

**HLS.js Integration:**
```javascript
// Load HLS.js library
const hls = new Hls();

// Get camera stream URL from API
const streamUrl = '/api/cameras/stream.php?camera_id=' + cameraId;

// Load and play
hls.loadSource(streamUrl);
hls.attachMedia(video);
hls.on(Hls.Events.MANIFEST_PARSED, () => {
    video.play();
});
```

- [ ] Upload and test with real camera

---

#### **Task 6A.6: Create Multi-Camera Grid View**
**File:** Add to `/dashboard/cameras.php`
**Lines:** +280 lines

- [ ] Add grid layout selector (2x2, 3x3, 4x4)
- [ ] Display multiple streams simultaneously
- [ ] Auto-layout based on camera count
- [ ] Click camera to expand full screen
- [ ] Optimize bandwidth (lower quality for grid)

**Grid Layouts:**
```html
<!-- 2x2 Grid (4 cameras) -->
<div class="camera-grid grid-2x2">
    <div class="camera-cell">
        <video id="cam1"></video>
        <span class="cam-label">Living Room</span>
    </div>
    <div class="camera-cell">
        <video id="cam2"></video>
        <span class="cam-label">Front Door</span>
    </div>
    <div class="camera-cell">
        <video id="cam3"></video>
        <span class="cam-label">Backyard</span>
    </div>
    <div class="camera-cell">
        <video id="cam4"></video>
        <span class="cam-label">Garage</span>
    </div>
</div>
```

**Auto Quality Adjustment:**
```javascript
// Lower quality in grid view to save bandwidth
if (gridView) {
    quality = '480p';  // 4 streams at 480p
} else {
    quality = '1080p'; // Single stream at 1080p
}
```

- [ ] Upload and test with 4+ cameras

---

#### **Task 6A.7: Create Camera Streaming API**
**File:** `/api/cameras/stream.php`
**Lines:** ~200 lines

- [ ] Create streaming endpoint
- [ ] Connect to camera RTSP
- [ ] Convert to HLS format
- [ ] Serve video chunks
- [ ] Handle errors gracefully

**RTSP to HLS Conversion:**
```php
<?php
// Get camera details
$camera = $db->getCameraById($_GET['camera_id']);

// Build RTSP URL
$rtsp_url = "rtsp://{$camera['username']}:{$camera['password']}@{$camera['local_ip']}:{$camera['rtsp_port']}/live";

// Use FFmpeg to convert RTSP → HLS
$output_dir = "/tmp/camera_{$camera['id']}/";
$playlist = "{$output_dir}stream.m3u8";

$command = "ffmpeg -rtsp_transport tcp -i '$rtsp_url' "
         . "-c:v copy -c:a copy "
         . "-f hls -hls_time 2 -hls_list_size 3 "
         . "-hls_flags delete_segments "
         . "$playlist";

// Start FFmpeg process
exec($command . " > /dev/null 2>&1 &");

// Return HLS playlist URL
echo json_encode([
    'stream_url' => "/tmp/camera_{$camera['id']}/stream.m3u8"
]);
```

- [ ] Upload and test

---

### **Section 3: Recording & Playback (4-5 hours)**

#### **Task 6A.8: Create Recording System**
**File:** `/api/cameras/record.php`
**Lines:** ~180 lines

- [ ] Start recording API
- [ ] Stop recording API
- [ ] List recordings API
- [ ] Delete recording API
- [ ] Storage management

**Recording Database Table:**
```sql
CREATE TABLE camera_recordings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    camera_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    filename TEXT NOT NULL,
    file_size INTEGER,
    duration INTEGER, -- seconds
    start_time TEXT,
    end_time TEXT,
    thumbnail TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (camera_id) REFERENCES cameras(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

**Recording Logic:**
```php
// Start recording
public function startRecording($camera_id) {
    $camera = $this->getCamera($camera_id);
    $filename = "recording_" . time() . ".mp4";
    $output = "/recordings/{$filename}";
    
    // FFmpeg command for recording
    $command = "ffmpeg -i '$rtsp_url' -c copy '$output'";
    
    // Start in background
    exec($command . " > /dev/null 2>&1 &", $output, $pid);
    
    // Save to database
    $this->db->insert('camera_recordings', [
        'camera_id' => $camera_id,
        'user_id' => $_SESSION['user_id'],
        'filename' => $filename,
        'start_time' => date('Y-m-d H:i:s'),
        'process_id' => $pid
    ]);
}
```

- [ ] Upload and test

---

#### **Task 6A.9: Create Playback Interface**
**File:** `/dashboard/recordings.php`
**Lines:** ~220 lines

- [ ] List all recordings
- [ ] Thumbnail previews
- [ ] Video playback
- [ ] Download recording
- [ ] Delete recording
- [ ] Storage usage display

**Recordings UI:**
```html
<div class="recordings-list">
    <?php foreach ($recordings as $rec): ?>
    <div class="recording-card">
        <img src="<?= $rec['thumbnail'] ?>" class="recording-thumb">
        <div class="recording-info">
            <h4><?= $rec['camera_name'] ?></h4>
            <p><?= date('M j, Y g:i A', strtotime($rec['start_time'])) ?></p>
            <p>Duration: <?= gmdate('H:i:s', $rec['duration']) ?></p>
            <p>Size: <?= formatBytes($rec['file_size']) ?></p>
        </div>
        <div class="recording-actions">
            <button onclick="playRecording(<?= $rec['id'] ?>)">▶️ Play</button>
            <button onclick="downloadRecording(<?= $rec['id'] ?>)">⬇️ Download</button>
            <button onclick="deleteRecording(<?= $rec['id'] ?>)">🗑️ Delete</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
```

- [ ] Upload and test

---

### **Section 4: Motion Detection (3-4 hours)**

#### **Task 6A.10: Create Motion Detection System**
**File:** `/api/cameras/motion-detection.php`
**Lines:** ~200 lines

- [ ] Enable/disable motion detection
- [ ] Set sensitivity level
- [ ] Configure detection zones
- [ ] Email alerts
- [ ] Auto-recording on motion

**Motion Detection Database:**
```sql
CREATE TABLE motion_detection_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    camera_id INTEGER NOT NULL,
    enabled INTEGER DEFAULT 0,
    sensitivity INTEGER DEFAULT 50, -- 1-100
    detection_zones TEXT, -- JSON polygon coordinates
    alert_email INTEGER DEFAULT 1,
    alert_push INTEGER DEFAULT 0,
    auto_record INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (camera_id) REFERENCES cameras(id)
);

CREATE TABLE motion_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    camera_id INTEGER NOT NULL,
    detection_time TEXT,
    thumbnail TEXT,
    recording_id INTEGER,
    notified INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (camera_id) REFERENCES cameras(id)
);
```

**Motion Detection Logic:**
```python
# Using OpenCV for motion detection
import cv2
import numpy as np

def detect_motion(camera_stream):
    # Get two consecutive frames
    frame1 = get_frame(camera_stream)
    frame2 = get_frame(camera_stream)
    
    # Calculate difference
    diff = cv2.absdiff(frame1, frame2)
    gray = cv2.cvtColor(diff, cv2.COLOR_BGR2GRAY)
    blur = cv2.GaussianBlur(gray, (5,5), 0)
    _, thresh = cv2.threshold(blur, sensitivity, 255, cv2.THRESH_BINARY)
    
    # Find contours (motion areas)
    contours, _ = cv2.findContours(thresh, cv2.RETR_TREE, cv2.CHAIN_APPROX_SIMPLE)
    
    # Check if motion detected
    for contour in contours:
        if cv2.contourArea(contour) > min_area:
            # Motion detected!
            trigger_alert()
            start_recording()
```

- [ ] Upload and test

---

#### **Task 6A.11: Create Motion Detection UI**
**File:** Add to `/dashboard/cameras.php`
**Lines:** +150 lines

- [ ] Motion detection toggle
- [ ] Sensitivity slider
- [ ] Zone drawing tool (draw rectangles on video)
- [ ] Alert settings
- [ ] Motion events log

**Zone Drawing Interface:**
```html
<div class="motion-zones">
    <canvas id="zone-canvas"></canvas>
    <button onclick="addZone()">+ Add Zone</button>
    <button onclick="clearZones()">Clear All</button>
    <button onclick="saveZones()">Save Zones</button>
</div>

<script>
// Draw detection zones on video
const canvas = document.getElementById('zone-canvas');
const ctx = canvas.getContext('2d');

// User draws rectangles
canvas.addEventListener('mousedown', startDrawing);
canvas.addEventListener('mousemove', drawing);
canvas.addEventListener('mouseup', endDrawing);

// Save zones as JSON polygons
function saveZones() {
    const zones = JSON.stringify(detectionZones);
    fetch('/api/cameras/save-zones.php', {
        method: 'POST',
        body: { camera_id, zones }
    });
}
</script>
```

- [ ] Upload and test

---

## 🎯 FINAL VERIFICATION - PART 6A

**Camera Discovery:**
- [ ] Scanner finds cameras via brute force
- [ ] Detects Geeni cameras in cloud mode
- [ ] Detects Wyze cameras
- [ ] Detects Ring cameras
- [ ] ONVIF discovery works
- [ ] Cloud bypass successful for Geeni
- [ ] All cameras appear in dashboard

**Live Streaming:**
- [ ] Single camera live view works
- [ ] Multi-camera grid (2x2, 3x3, 4x4)
- [ ] Video quality selection
- [ ] Full screen mode
- [ ] Snapshot capture
- [ ] Low latency (<500ms)
- [ ] Handles connection errors

**Recording:**
- [ ] Can start recording
- [ ] Can stop recording
- [ ] Recordings saved to disk
- [ ] Can play recordings
- [ ] Can download recordings
- [ ] Can delete recordings
- [ ] Storage management works

**Motion Detection:**
- [ ] Can enable/disable
- [ ] Sensitivity adjustment works
- [ ] Detection zones configurable
- [ ] Email alerts send
- [ ] Auto-recording on motion
- [ ] Motion events logged
- [ ] Thumbnail capture works

**Cloud Bypass:**
- [ ] Geeni cameras work without cloud
- [ ] Wyze RTSP firmware instructions clear
- [ ] Ring local mode enabled
- [ ] Users save $300-600/year
- [ ] Zero monthly fees confirmed

---

## 💰 VALUE PROPOSITION

**Before TrueVault:**
- 3 cameras × $10/month = $360/year
- Limited storage (30-60 days)
- Privacy concerns (cloud storage)
- Subscription required

**After TrueVault:**
- $0/year for camera monitoring
- Unlimited storage (own hardware)
- Complete privacy (local storage)
- No subscription ever

**Savings:** $360/year × 10 years = **$3,600!**

---

## 📊 TIME ESTIMATE

**Part 6A Total:** 18-22 hours (3-4 days)

**Breakdown:**
- Section 1: Brute Force Scanner (6-8 hrs)
- Section 2: Live Streaming (5-6 hrs)
- Section 3: Recording & Playback (4-5 hrs)
- Section 4: Motion Detection (3-4 hrs)

**Updated Total Project:** 168-202 hours (21-25 days)

---

## 🚀 THIS IS THE SELLING POINT!

**Marketing Headline:**
"Stop Paying Ring $360/Year - Use Your Cameras FREE Forever"

**Key Features:**
- ✅ Bypass cloud subscriptions
- ✅ Direct local access
- ✅ Zero monthly fees
- ✅ Unlimited storage
- ✅ Complete privacy
- ✅ Works with Geeni, Wyze, Ring, Nest

**THIS WILL SELL!** 🎯

