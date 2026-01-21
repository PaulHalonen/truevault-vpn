# PART 6A: FULL CAMERA DASHBOARD - THE FLAGSHIP FEATURE
**Created:** January 20, 2026 - 4:30 AM CST  
**Updated:** January 21, 2026 - 3:15 AM CST  
**Priority:** 🚨 CRITICAL - THIS IS THE SELLING FEATURE  
**Time:** 24-30 hours (4-5 days)  
**Status:** NOT STARTED  
**Blueprint Reference:** SECTION_06_CAMERA_DASHBOARD.md

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

**Verification:**
- [ ] Scanner finds cameras missed by basic scan
- [ ] Detects Geeni cameras in cloud mode
- [ ] Detects Ring cameras in local mode
- [ ] Detects Wyze cameras
- [ ] Finds ONVIF cameras
- [ ] Safe (doesn't crash cameras)

---

#### **Task 6A.2: Cloud Bypass - Geeni/Tuya Cameras**
**File:** `/includes/CloudBypass.php`  
**Lines:** ~250 lines

- [ ] Create CloudBypass.php helper class
- [ ] Geeni/Tuya local API discovery
- [ ] Extract local encryption keys
- [ ] Generate local RTSP URLs
- [ ] Bypass Geeni cloud completely

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
**File:** Add to `/includes/CloudBypass.php`  
**Lines:** +150 lines

- [ ] Detect Wyze cameras
- [ ] Check if RTSP firmware enabled
- [ ] Provide RTSP firmware flash instructions
- [ ] Generate RTSP URL after flash

- [ ] Upload and test

---

#### **Task 6A.4: Cloud Bypass - Ring Cameras**
**File:** Add to `/includes/CloudBypass.php`  
**Lines:** +120 lines

- [ ] Detect Ring cameras
- [ ] Enable local mode (if available)
- [ ] ONVIF discovery for Ring
- [ ] Generate local access URL

- [ ] Upload and test

---

### **Section 2: Live Video Streaming Interface (6-8 hours)**

#### **Task 6A.5: Create Live Video Player**
**File:** `/dashboard/cameras.php` (update existing)  
**Lines:** +400 lines  
**Blueprint Reference:** Section 5 - Live View

- [ ] Update camera dashboard with live streaming
- [ ] Integrate HLS.js for video playback
- [ ] Add video player controls
- [ ] Add quality selection (1080p, 720p, 480p)
- [ ] Add full screen mode
- [ ] Add snapshot button
- [ ] Add audio toggle (if camera has microphone)
- [ ] **Add two-way audio** (talk through camera) ← FROM BLUEPRINT
- [ ] **Add PTZ controls** (pan/tilt/zoom) ← FROM BLUEPRINT

**Video Player Interface (From Blueprint):**
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

**Two-Way Audio Implementation:**
```javascript
// Two-way audio - talk through camera
class TwoWayAudio {
    constructor(cameraId) {
        this.cameraId = cameraId;
        this.mediaRecorder = null;
        this.audioStream = null;
    }
    
    async startTalking() {
        // Get microphone access
        this.audioStream = await navigator.mediaDevices.getUserMedia({ audio: true });
        
        // Create media recorder
        this.mediaRecorder = new MediaRecorder(this.audioStream);
        
        // Send audio chunks to camera
        this.mediaRecorder.ondataavailable = (e) => {
            this.sendAudioToCamera(e.data);
        };
        
        this.mediaRecorder.start(100); // 100ms chunks
    }
    
    stopTalking() {
        if (this.mediaRecorder) {
            this.mediaRecorder.stop();
        }
        if (this.audioStream) {
            this.audioStream.getTracks().forEach(track => track.stop());
        }
    }
    
    async sendAudioToCamera(audioBlob) {
        const formData = new FormData();
        formData.append('audio', audioBlob);
        formData.append('camera_id', this.cameraId);
        
        await fetch('/api/cameras.php?action=send_audio', {
            method: 'POST',
            body: formData
        });
    }
}

// Usage
const twoWay = new TwoWayAudio('cam_112');
document.getElementById('talk-btn').addEventListener('mousedown', () => twoWay.startTalking());
document.getElementById('talk-btn').addEventListener('mouseup', () => twoWay.stopTalking());
```

**PTZ Controls Implementation:**
```javascript
// PTZ (Pan-Tilt-Zoom) Controls
class PTZController {
    constructor(cameraId) {
        this.cameraId = cameraId;
    }
    
    async pan(direction) {
        // direction: 'left' or 'right'
        await this.sendCommand('pan', direction);
    }
    
    async tilt(direction) {
        // direction: 'up' or 'down'
        await this.sendCommand('tilt', direction);
    }
    
    async zoom(direction) {
        // direction: 'in' or 'out'
        await this.sendCommand('zoom', direction);
    }
    
    async goToPreset(presetId) {
        await this.sendCommand('preset', presetId);
    }
    
    async sendCommand(action, value) {
        await fetch('/api/cameras.php?action=ptz', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                camera_id: this.cameraId,
                ptz_action: action,
                ptz_value: value
            })
        });
    }
}

// PTZ UI Controls
const ptzHtml = `
<div class="ptz-controls">
    <div class="ptz-grid">
        <button class="ptz-btn" onclick="ptz.tilt('up')">⬆️</button>
        <button class="ptz-btn" onclick="ptz.pan('left')">⬅️</button>
        <button class="ptz-btn ptz-home" onclick="ptz.goToPreset(1)">🏠</button>
        <button class="ptz-btn" onclick="ptz.pan('right')">➡️</button>
        <button class="ptz-btn" onclick="ptz.tilt('down')">⬇️</button>
    </div>
    <div class="zoom-controls">
        <button onclick="ptz.zoom('in')">🔍+</button>
        <button onclick="ptz.zoom('out')">🔍-</button>
    </div>
</div>
`;
```

**HLS.js Integration (From Blueprint):**
```html
<video id="camera-feed" controls autoplay></video>

<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script>
const video = document.getElementById('camera-feed');
const hls = new Hls();

// Load camera stream - MATCHING BLUEPRINT API ENDPOINT
hls.loadSource('/api/camera-stream.php?camera_id=cam_112');
hls.attachMedia(video);

hls.on(Hls.Events.MANIFEST_PARSED, function() {
    video.play();
});
</script>
```

- [ ] Upload and test with real camera

---

#### **Task 6A.6: Create Multi-Camera Grid View**
**File:** Add to `/dashboard/cameras.php`  
**Lines:** +350 lines  
**Blueprint Reference:** Section 6 - Multi-Camera Grid

- [ ] Add grid layout selector (1x1, 2x2, 3x3, 4x4)
- [ ] Display multiple streams simultaneously
- [ ] Auto-layout based on camera count
- [ ] Click camera to expand full screen
- [ ] **Drag to rearrange cameras** ← FROM BLUEPRINT
- [ ] **Auto-cycle through cameras** ← FROM BLUEPRINT
- [ ] Optimize bandwidth (lower quality for grid)
- [ ] Name cameras (Living Room, Front Door, etc.)
- [ ] Show/hide inactive cameras

**Grid View UI (From Blueprint):**
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

**Drag to Rearrange Implementation:**
```javascript
// Drag and drop camera tiles
class DraggableCameraGrid {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.cameras = [];
        this.draggedItem = null;
        
        this.init();
    }
    
    init() {
        // Enable drag and drop on all camera tiles
        this.container.addEventListener('dragstart', (e) => this.handleDragStart(e));
        this.container.addEventListener('dragover', (e) => this.handleDragOver(e));
        this.container.addEventListener('drop', (e) => this.handleDrop(e));
        this.container.addEventListener('dragend', (e) => this.handleDragEnd(e));
    }
    
    handleDragStart(e) {
        this.draggedItem = e.target.closest('.camera-tile');
        this.draggedItem.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    }
    
    handleDragOver(e) {
        e.preventDefault();
        const tile = e.target.closest('.camera-tile');
        if (tile && tile !== this.draggedItem) {
            const rect = tile.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;
            if (e.clientY < midY) {
                tile.parentNode.insertBefore(this.draggedItem, tile);
            } else {
                tile.parentNode.insertBefore(this.draggedItem, tile.nextSibling);
            }
        }
    }
    
    handleDrop(e) {
        e.preventDefault();
        this.saveCameraOrder();
    }
    
    handleDragEnd(e) {
        this.draggedItem.classList.remove('dragging');
        this.draggedItem = null;
    }
    
    async saveCameraOrder() {
        const order = Array.from(this.container.querySelectorAll('.camera-tile'))
            .map(tile => tile.dataset.cameraId);
        
        await fetch('/api/cameras.php?action=save_order', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order })
        });
    }
}
```

**Auto-Cycle Through Cameras Implementation:**
```javascript
// Auto-cycle through cameras (like a security monitor)
class CameraCarousel {
    constructor(cameras, intervalSeconds = 10) {
        this.cameras = cameras;
        this.interval = intervalSeconds * 1000;
        this.currentIndex = 0;
        this.timer = null;
        this.isRunning = false;
    }
    
    start() {
        if (this.isRunning) return;
        this.isRunning = true;
        this.showCamera(this.currentIndex);
        this.timer = setInterval(() => this.next(), this.interval);
        document.getElementById('cycle-btn').textContent = '⏹️ Stop Cycle';
    }
    
    stop() {
        if (!this.isRunning) return;
        this.isRunning = false;
        clearInterval(this.timer);
        this.timer = null;
        document.getElementById('cycle-btn').textContent = '🔄 Auto-Cycle';
    }
    
    toggle() {
        if (this.isRunning) {
            this.stop();
        } else {
            this.start();
        }
    }
    
    next() {
        this.currentIndex = (this.currentIndex + 1) % this.cameras.length;
        this.showCamera(this.currentIndex);
    }
    
    showCamera(index) {
        const camera = this.cameras[index];
        // Expand this camera to full view
        expandCamera(camera.id);
        
        // Update indicator
        document.getElementById('cycle-indicator').textContent = 
            `Camera ${index + 1} of ${this.cameras.length}: ${camera.name}`;
    }
    
    setInterval(seconds) {
        this.interval = seconds * 1000;
        if (this.isRunning) {
            this.stop();
            this.start();
        }
    }
}

// Usage
const carousel = new CameraCarousel(cameras, 10); // 10 seconds per camera
document.getElementById('cycle-btn').addEventListener('click', () => carousel.toggle());
```

**Camera Grid Class (From Blueprint):**
```javascript
class CameraGrid {
    constructor(gridSize = '2x2') {
        this.gridSize = gridSize;
        this.cameras = [];
        this.activeStreams = [];
    }
    
    setGridSize(size) {
        // size = '1x1', '2x2', '3x3', '4x4'
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
        
        container.innerHTML = '';
        
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
        tile.draggable = true; // Enable drag
        tile.dataset.cameraId = camera.id;
        tile.innerHTML = `
            <div class="camera-name">${camera.name}</div>
            <video autoplay muted data-camera="${camera.id}"></video>
            <div class="camera-overlay">
                <button onclick="expandCamera('${camera.id}')">⛶ Fullscreen</button>
            </div>
        `;
        
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
```

- [ ] Upload and test with 4+ cameras

---

#### **Task 6A.7: Create Camera Streaming API**
**File:** `/api/camera-stream.php` ← MATCHING BLUEPRINT  
**Lines:** ~200 lines  
**Blueprint Reference:** Section 10 - Technical Implementation

- [ ] Create streaming endpoint (MATCHING BLUEPRINT URL)
- [ ] Connect to camera RTSP
- [ ] Convert to HLS format using FFmpeg
- [ ] Serve video chunks
- [ ] Handle errors gracefully

**API Endpoint (From Blueprint):**
```
GET /api/camera-stream.php?camera_id=cam_112
```

**Implementation:**
```php
<?php
/**
 * Camera Stream API
 * URL: /api/camera-stream.php?camera_id=xxx
 * 
 * Converts RTSP to HLS for browser playback
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

$cameraId = $_GET['camera_id'] ?? '';

// Get camera from database
$camera = Database::getRow('devices', 
    "SELECT * FROM cameras WHERE camera_id = :id",
    [':id' => $cameraId]
);

if (!$camera) {
    http_response_code(404);
    die(json_encode(['error' => 'Camera not found']));
}

// Build RTSP URL
$rtspUrl = buildRTSPUrl($camera);

// Output directory for HLS segments
$outputDir = "/tmp/streams/{$cameraId}/";
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$playlist = "{$outputDir}stream.m3u8";

// Check if stream already running
$pidFile = "{$outputDir}ffmpeg.pid";
if (!file_exists($pidFile) || !isProcessRunning(file_get_contents($pidFile))) {
    // Start FFmpeg process
    $cmd = "ffmpeg -rtsp_transport tcp -i '{$rtspUrl}' " .
           "-c:v copy -c:a aac -f hls " .
           "-hls_time 2 -hls_list_size 3 " .
           "-hls_flags delete_segments " .
           "{$playlist} > /dev/null 2>&1 & echo $!";
    
    $pid = exec($cmd);
    file_put_contents($pidFile, $pid);
    
    // Wait for playlist to be created
    sleep(2);
}

// Serve HLS playlist
if (file_exists($playlist)) {
    header('Content-Type: application/vnd.apple.mpegurl');
    header('Access-Control-Allow-Origin: *');
    readfile($playlist);
} else {
    http_response_code(503);
    echo json_encode(['error' => 'Stream not ready']);
}

function buildRTSPUrl($camera) {
    $user = $camera['rtsp_username'] ?? 'admin';
    $pass = $camera['rtsp_password'] ?? '';
    $ip = $camera['local_ip'];
    $port = $camera['rtsp_port'] ?? 554;
    
    if ($pass) {
        return "rtsp://{$user}:{$pass}@{$ip}:{$port}/live";
    }
    return "rtsp://{$ip}:{$port}/live";
}

function isProcessRunning($pid) {
    return file_exists("/proc/{$pid}");
}
```

- [ ] Upload and test

---

#### **Task 6A.8: Create Main Cameras API**
**File:** `/api/cameras.php` ← MATCHING BLUEPRINT  
**Lines:** ~350 lines  
**Blueprint Reference:** Section 10 - API Endpoints

This single file handles ALL camera actions via `?action=` parameter:

- [ ] `action=list` - List all cameras
- [ ] `action=motion_events` - Get motion events
- [ ] `action=ptz` - PTZ control
- [ ] `action=send_audio` - Two-way audio
- [ ] `action=save_order` - Save grid order
- [ ] `action=snapshot` - Take snapshot

**API Endpoints (From Blueprint):**
```
GET  /api/cameras.php?action=list
GET  /api/cameras.php?action=motion_events&camera_id=cam_112
POST /api/cameras.php?action=ptz
POST /api/cameras.php?action=send_audio
POST /api/cameras.php?action=save_order
POST /api/cameras.php?action=snapshot
```

**Implementation:**
```php
<?php
/**
 * Cameras API - All camera actions
 * Matches SECTION_06 Blueprint
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        listCameras();
        break;
    case 'motion_events':
        getMotionEvents();
        break;
    case 'ptz':
        handlePTZ();
        break;
    case 'send_audio':
        handleTwoWayAudio();
        break;
    case 'save_order':
        saveCameraOrder();
        break;
    case 'snapshot':
        takeSnapshot();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

// List all cameras (From Blueprint)
function listCameras() {
    $userId = getCurrentUserId();
    
    $cameras = Database::getAll('devices',
        "SELECT * FROM cameras WHERE user_id = :user_id ORDER BY display_order",
        [':user_id' => $userId]
    );
    
    echo json_encode([
        'success' => true,
        'cameras' => array_map(function($cam) {
            return [
                'camera_id' => $cam['camera_id'],
                'camera_name' => $cam['camera_name'],
                'location' => $cam['location'],
                'local_ip' => $cam['local_ip'],
                'rtsp_url' => "rtsp://{$cam['local_ip']}:{$cam['rtsp_port']}/stream",
                'is_online' => (bool)$cam['is_online'],
                'supports_audio' => (bool)$cam['supports_audio'],
                'supports_ptz' => (bool)$cam['supports_ptz'],
                'supports_two_way' => (bool)$cam['supports_two_way'],
                'max_resolution' => $cam['max_resolution'],
                'recording_enabled' => (bool)$cam['recording_enabled'],
                'motion_detection' => (bool)$cam['motion_detection']
            ];
        }, $cameras)
    ]);
}

// Get motion events (From Blueprint)
function getMotionEvents() {
    $cameraId = $_GET['camera_id'] ?? '';
    
    $events = Database::getAll('devices',
        "SELECT * FROM motion_events WHERE camera_id = :id ORDER BY detected_at DESC LIMIT 50",
        [':id' => $cameraId]
    );
    
    echo json_encode([
        'success' => true,
        'events' => array_map(function($evt) {
            return [
                'id' => $evt['id'],
                'detected_at' => $evt['detection_time'],
                'snapshot_url' => "/recordings/snapshots/{$evt['thumbnail']}",
                'video_url' => $evt['recording_id'] ? "/recordings/clips/{$evt['recording_id']}.mp4" : null,
                'alert_viewed' => (bool)$evt['notified']
            ];
        }, $events)
    ]);
}

// PTZ Control
function handlePTZ() {
    $input = json_decode(file_get_contents('php://input'), true);
    $cameraId = $input['camera_id'] ?? '';
    $action = $input['ptz_action'] ?? '';
    $value = $input['ptz_value'] ?? '';
    
    $camera = Database::getRow('devices',
        "SELECT * FROM cameras WHERE camera_id = :id",
        [':id' => $cameraId]
    );
    
    if (!$camera || !$camera['supports_ptz']) {
        echo json_encode(['success' => false, 'error' => 'PTZ not supported']);
        return;
    }
    
    // Send ONVIF PTZ command
    $result = sendONVIFCommand($camera, $action, $value);
    
    echo json_encode(['success' => $result]);
}

// Two-way audio
function handleTwoWayAudio() {
    $cameraId = $_POST['camera_id'] ?? '';
    $audioFile = $_FILES['audio'] ?? null;
    
    if (!$audioFile) {
        echo json_encode(['success' => false, 'error' => 'No audio data']);
        return;
    }
    
    $camera = Database::getRow('devices',
        "SELECT * FROM cameras WHERE camera_id = :id",
        [':id' => $cameraId]
    );
    
    if (!$camera || !$camera['supports_two_way']) {
        echo json_encode(['success' => false, 'error' => 'Two-way audio not supported']);
        return;
    }
    
    // Stream audio to camera via ONVIF backchannel
    $result = streamAudioToCamera($camera, $audioFile['tmp_name']);
    
    echo json_encode(['success' => $result]);
}

// Save camera order
function saveCameraOrder() {
    $input = json_decode(file_get_contents('php://input'), true);
    $order = $input['order'] ?? [];
    
    foreach ($order as $index => $cameraId) {
        Database::update('devices', 'cameras',
            ['display_order' => $index],
            "camera_id = :id",
            [':id' => $cameraId]
        );
    }
    
    echo json_encode(['success' => true]);
}

// Take snapshot
function takeSnapshot() {
    $input = json_decode(file_get_contents('php://input'), true);
    $cameraId = $input['camera_id'] ?? '';
    
    $camera = Database::getRow('devices',
        "SELECT * FROM cameras WHERE camera_id = :id",
        [':id' => $cameraId]
    );
    
    if (!$camera) {
        echo json_encode(['success' => false, 'error' => 'Camera not found']);
        return;
    }
    
    // Capture frame using FFmpeg
    $rtspUrl = buildRTSPUrl($camera);
    $filename = "snapshot_{$cameraId}_" . time() . ".jpg";
    $outputPath = "/recordings/snapshots/{$filename}";
    
    $cmd = "ffmpeg -rtsp_transport tcp -i '{$rtspUrl}' -frames:v 1 -q:v 2 '{$outputPath}' 2>&1";
    exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0) {
        echo json_encode([
            'success' => true,
            'snapshot_url' => "/recordings/snapshots/{$filename}"
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Snapshot failed']);
    }
}
```

- [ ] Upload and test all endpoints

---

### **Section 3: Recording & Playback (5-6 hours)**

#### **Task 6A.9: Create Recording System**
**File:** `/api/recordings.php`  
**Lines:** ~250 lines  
**Blueprint Reference:** Section 7 - Recording & Playback

- [ ] Start recording API
- [ ] Stop recording API
- [ ] List recordings API
- [ ] Delete recording API
- [ ] Storage management
- [ ] Continuous/Motion/Scheduled recording modes

**Recording Modes (From Blueprint):**
1. **Continuous Recording** - 24/7 to local storage
2. **Motion-Triggered Recording** - Only when motion detected
3. **Scheduled Recording** - Specific hours only

**Recording Database Table (From Blueprint):**
```sql
CREATE TABLE camera_recordings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    camera_id TEXT NOT NULL,
    user_id INTEGER NOT NULL,
    filename TEXT NOT NULL,
    file_size INTEGER,
    duration INTEGER,
    start_time TEXT,
    end_time TEXT,
    thumbnail TEXT,
    recording_mode TEXT DEFAULT 'manual',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (camera_id) REFERENCES cameras(camera_id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

- [ ] Upload and test

---

#### **Task 6A.10: Create Playback Interface**
**File:** `/dashboard/recordings.php`  
**Lines:** +300 lines  
**Blueprint Reference:** Section 7 - Playback Interface

- [ ] List all recordings
- [ ] Thumbnail previews
- [ ] Video playback with timeline
- [ ] **Timeline view with motion markers** ← FROM BLUEPRINT
- [ ] **Jump to motion events** ← FROM BLUEPRINT
- [ ] **Speed control (0.5x, 1x, 2x, 4x)** ← FROM BLUEPRINT
- [ ] **Share clips (generate link)** ← FROM BLUEPRINT
- [ ] Download recording
- [ ] Delete recording
- [ ] Storage usage display

**Playback UI (From Blueprint):**
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
│  ◀◀ ⏮️ ⏸️ ⏭️ ▶▶   Speed: [1x ▼]             │
│  ├──────────●────────────────────┤ 8:45 AM     │
│  8:00 AM                       9:00 AM          │
│                                                 │
│  Timeline:                                      │
│  ┌─┬─┬───┬─┬──────┬─┬─┐                        │
│  8  8:15  8:30  8:45  9:00                     │
│    └─ Motion detected (click to jump)          │
│                                                 │
│  [Download Clip] [Share] [Delete]              │
│                                                 │
└─────────────────────────────────────────────────┘
```

**Speed Control Implementation:**
```javascript
// Playback speed control
class PlaybackController {
    constructor(videoElement) {
        this.video = videoElement;
        this.speeds = [0.5, 1, 2, 4];
        this.currentSpeedIndex = 1; // Default 1x
    }
    
    setSpeed(speed) {
        this.video.playbackRate = speed;
        document.getElementById('speed-display').textContent = `${speed}x`;
    }
    
    cycleSpeed() {
        this.currentSpeedIndex = (this.currentSpeedIndex + 1) % this.speeds.length;
        this.setSpeed(this.speeds[this.currentSpeedIndex]);
    }
    
    skipForward(seconds = 10) {
        this.video.currentTime += seconds;
    }
    
    skipBackward(seconds = 10) {
        this.video.currentTime -= seconds;
    }
    
    jumpToMotionEvent(timestamp) {
        // Convert timestamp to video position
        const position = this.timestampToPosition(timestamp);
        this.video.currentTime = position;
    }
}

// Speed selector UI
const speedHtml = `
<select id="speed-selector" onchange="playback.setSpeed(this.value)">
    <option value="0.5">0.5x</option>
    <option value="1" selected>1x</option>
    <option value="2">2x</option>
    <option value="4">4x</option>
</select>
`;
```

**Share Clips Implementation:**
```javascript
// Share clip functionality
async function shareClip(recordingId, startTime, endTime) {
    // Generate shareable link
    const response = await fetch('/api/recordings.php?action=create_share', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            recording_id: recordingId,
            start_time: startTime,
            end_time: endTime
        })
    });
    
    const data = await response.json();
    
    if (data.success) {
        // Show share modal
        showShareModal(data.share_url, data.expires_at);
    }
}

function showShareModal(url, expiresAt) {
    const modal = document.getElementById('share-modal');
    modal.innerHTML = `
        <div class="share-content">
            <h3>📤 Share Recording</h3>
            <p>Anyone with this link can view the clip:</p>
            <input type="text" value="${url}" readonly id="share-url">
            <button onclick="copyShareUrl()">📋 Copy Link</button>
            <p class="expires">Link expires: ${expiresAt}</p>
        </div>
    `;
    modal.style.display = 'block';
}

function copyShareUrl() {
    const input = document.getElementById('share-url');
    input.select();
    document.execCommand('copy');
    alert('Link copied to clipboard!');
}
```

**Timeline with Motion Markers:**
```javascript
// Timeline with motion event markers
class VideoTimeline {
    constructor(videoElement, timelineElement) {
        this.video = videoElement;
        this.timeline = timelineElement;
        this.motionEvents = [];
    }
    
    async loadMotionEvents(recordingId) {
        const response = await fetch(`/api/recordings.php?action=motion_markers&id=${recordingId}`);
        const data = await response.json();
        this.motionEvents = data.events;
        this.renderMarkers();
    }
    
    renderMarkers() {
        const duration = this.video.duration;
        
        this.motionEvents.forEach(event => {
            const position = (event.timestamp / duration) * 100;
            
            const marker = document.createElement('div');
            marker.className = 'motion-marker';
            marker.style.left = `${position}%`;
            marker.title = `Motion at ${formatTime(event.timestamp)}`;
            marker.onclick = () => {
                this.video.currentTime = event.timestamp;
            };
            
            this.timeline.appendChild(marker);
        });
    }
}

// CSS for motion markers
const timelineCss = `
.timeline-container {
    position: relative;
    height: 40px;
    background: #333;
    border-radius: 4px;
}

.motion-marker {
    position: absolute;
    width: 8px;
    height: 100%;
    background: #ff4444;
    cursor: pointer;
    opacity: 0.8;
}

.motion-marker:hover {
    opacity: 1;
    transform: scaleX(1.5);
}
`;
```

- [ ] Upload and test

---

### **Section 4: Motion Detection & Alerts (4-5 hours)**

#### **Task 6A.11: Create Motion Detection System**
**File:** `/api/motion-detection.php`  
**Lines:** ~250 lines  
**Blueprint Reference:** Section 8 - Motion Detection

- [ ] Enable/disable motion detection
- [ ] Set sensitivity level (0-100)
- [ ] Configure detection zones (polygon coordinates)
- [ ] **Email alerts** ← FROM BLUEPRINT
- [ ] **Push notifications** ← FROM BLUEPRINT (MISSING BEFORE)
- [ ] **SMS alerts** ← FROM BLUEPRINT (MISSING BEFORE)
- [ ] Auto-recording on motion
- [ ] Pre-record buffer (5 seconds before motion)
- [ ] Post-record buffer (30 seconds after motion)

**Alert Types (From Blueprint):**
- 📱 **Push notification** (mobile app)
- 📧 **Email** (with snapshot)
- 💬 **SMS** (optional, carrier charges)
- 🔔 **Browser notification** (desktop)

**Motion Detection Settings (From Blueprint):**
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

**Push Notification Implementation:**
```php
// Push notification service
class PushNotificationService {
    
    public function sendMotionAlert($userId, $camera, $snapshotUrl) {
        // Get user's push subscription
        $subscription = Database::getRow('users',
            "SELECT push_subscription FROM users WHERE id = :id",
            [':id' => $userId]
        );
        
        if (!$subscription['push_subscription']) {
            return false;
        }
        
        $payload = json_encode([
            'title' => 'Motion Detected!',
            'body' => "📷 {$camera['camera_name']} detected movement",
            'icon' => '/images/camera-alert.png',
            'image' => $snapshotUrl,
            'data' => [
                'camera_id' => $camera['camera_id'],
                'action' => 'view_camera'
            ]
        ]);
        
        // Send via Web Push
        $this->sendWebPush($subscription['push_subscription'], $payload);
        
        return true;
    }
    
    private function sendWebPush($subscription, $payload) {
        // Use web-push library
        $webPush = new \Minishlink\WebPush\WebPush([
            'VAPID' => [
                'subject' => 'mailto:admin@truevault.com',
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY
            ]
        ]);
        
        $webPush->sendOneNotification(
            \Minishlink\WebPush\Subscription::create(json_decode($subscription, true)),
            $payload
        );
    }
}
```

**SMS Alert Implementation:**
```php
// SMS alert service (using Twilio)
class SMSAlertService {
    
    public function sendMotionAlert($userId, $camera) {
        // Get user's phone number
        $user = Database::getRow('users',
            "SELECT phone_number, sms_alerts_enabled FROM users WHERE id = :id",
            [':id' => $userId]
        );
        
        if (!$user['sms_alerts_enabled'] || !$user['phone_number']) {
            return false;
        }
        
        $message = "🚨 TrueVault Alert: Motion detected on {$camera['camera_name']} at " . date('g:i A');
        
        // Send via Twilio
        $this->sendTwilioSMS($user['phone_number'], $message);
        
        return true;
    }
    
    private function sendTwilioSMS($to, $message) {
        $sid = TWILIO_SID;
        $token = TWILIO_TOKEN;
        $from = TWILIO_PHONE;
        
        $client = new \Twilio\Rest\Client($sid, $token);
        
        $client->messages->create($to, [
            'from' => $from,
            'body' => $message
        ]);
    }
}
```

**Motion Detection Database (Updated):**
```sql
CREATE TABLE motion_detection_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    camera_id TEXT NOT NULL,
    enabled INTEGER DEFAULT 0,
    sensitivity INTEGER DEFAULT 50,
    detection_zones TEXT,
    
    -- Alert settings
    alert_email INTEGER DEFAULT 1,
    alert_push INTEGER DEFAULT 0,
    alert_sms INTEGER DEFAULT 0,
    alert_browser INTEGER DEFAULT 1,
    
    -- Schedule
    schedule_enabled INTEGER DEFAULT 0,
    schedule_start TEXT,
    schedule_end TEXT,
    
    -- Recording settings
    auto_record INTEGER DEFAULT 1,
    pre_record_seconds INTEGER DEFAULT 5,
    post_record_seconds INTEGER DEFAULT 30,
    
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (camera_id) REFERENCES cameras(camera_id)
);
```

- [ ] Upload and test all alert types

---

#### **Task 6A.12: Create Motion Detection UI**
**File:** Add to `/dashboard/cameras.php`  
**Lines:** +200 lines

- [ ] Motion detection toggle
- [ ] Sensitivity slider
- [ ] Zone drawing tool (draw rectangles on video)
- [ ] Alert type checkboxes (email, push, SMS, browser)
- [ ] Schedule settings
- [ ] Motion events log
- [ ] Test alert button

**Zone Drawing Interface:**
```javascript
// Draw detection zones on video feed
class ZoneDrawer {
    constructor(videoElement, canvasElement) {
        this.video = videoElement;
        this.canvas = canvasElement;
        this.ctx = canvasElement.getContext('2d');
        this.zones = [];
        this.isDrawing = false;
        this.currentZone = null;
        
        this.init();
    }
    
    init() {
        this.canvas.addEventListener('mousedown', (e) => this.startDrawing(e));
        this.canvas.addEventListener('mousemove', (e) => this.drawing(e));
        this.canvas.addEventListener('mouseup', (e) => this.endDrawing(e));
    }
    
    startDrawing(e) {
        this.isDrawing = true;
        const rect = this.canvas.getBoundingClientRect();
        this.currentZone = {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top,
            width: 0,
            height: 0
        };
    }
    
    drawing(e) {
        if (!this.isDrawing) return;
        
        const rect = this.canvas.getBoundingClientRect();
        this.currentZone.width = (e.clientX - rect.left) - this.currentZone.x;
        this.currentZone.height = (e.clientY - rect.top) - this.currentZone.y;
        
        this.redraw();
    }
    
    endDrawing(e) {
        if (this.currentZone && this.currentZone.width > 10 && this.currentZone.height > 10) {
            this.zones.push({...this.currentZone});
        }
        this.isDrawing = false;
        this.currentZone = null;
        this.redraw();
    }
    
    redraw() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Draw existing zones
        this.ctx.strokeStyle = '#00ff00';
        this.ctx.lineWidth = 2;
        this.ctx.fillStyle = 'rgba(0, 255, 0, 0.2)';
        
        this.zones.forEach(zone => {
            this.ctx.fillRect(zone.x, zone.y, zone.width, zone.height);
            this.ctx.strokeRect(zone.x, zone.y, zone.width, zone.height);
        });
        
        // Draw current zone being drawn
        if (this.currentZone) {
            this.ctx.strokeStyle = '#ff0000';
            this.ctx.strokeRect(
                this.currentZone.x, 
                this.currentZone.y, 
                this.currentZone.width, 
                this.currentZone.height
            );
        }
    }
    
    clearZones() {
        this.zones = [];
        this.redraw();
    }
    
    async saveZones(cameraId) {
        await fetch('/api/motion-detection.php?action=save_zones', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                camera_id: cameraId,
                zones: this.zones
            })
        });
    }
}
```

- [ ] Upload and test

---

### **Section 5: Mobile Access (3-4 hours)** ← NEW SECTION FROM BLUEPRINT

#### **Task 6A.13: Create Mobile Camera Interface**
**File:** `/dashboard/cameras-mobile.php`  
**Lines:** ~300 lines  
**Blueprint Reference:** Section 9 - Mobile Access

- [ ] Mobile-optimized camera list
- [ ] Touch-friendly video player
- [ ] Swipe between cameras
- [ ] Push notification setup
- [ ] Quick snapshot button
- [ ] Two-way audio with tap-to-talk

**Mobile UI (From Blueprint):**
```html
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

**Full Screen Mobile View:**
```html
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
│ 📸  🎙️  🔊  ⛶        │
│Snap Talk Audio Full   │
└───────────────────────┘
```

**Mobile Touch Controls:**
```javascript
// Touch-friendly camera controls
class MobileCameraControls {
    constructor(container) {
        this.container = container;
        this.initTouchEvents();
    }
    
    initTouchEvents() {
        // Swipe left/right to change cameras
        let touchStartX = 0;
        let touchEndX = 0;
        
        this.container.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        this.container.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            this.handleSwipe(touchStartX, touchEndX);
        });
        
        // Double tap to fullscreen
        let lastTap = 0;
        this.container.addEventListener('touchend', (e) => {
            const currentTime = new Date().getTime();
            const tapLength = currentTime - lastTap;
            if (tapLength < 300 && tapLength > 0) {
                this.toggleFullscreen();
            }
            lastTap = currentTime;
        });
        
        // Long press for talk
        let pressTimer;
        this.container.addEventListener('touchstart', (e) => {
            if (e.target.id === 'talk-btn') {
                pressTimer = setTimeout(() => this.startTalking(), 200);
            }
        });
        
        this.container.addEventListener('touchend', (e) => {
            clearTimeout(pressTimer);
            if (e.target.id === 'talk-btn') {
                this.stopTalking();
            }
        });
    }
    
    handleSwipe(startX, endX) {
        const diff = startX - endX;
        if (Math.abs(diff) > 50) {
            if (diff > 0) {
                this.nextCamera();
            } else {
                this.previousCamera();
            }
        }
    }
}
```

- [ ] Upload and test on mobile devices

---

#### **Task 6A.14: Setup Push Notification Service Worker**
**File:** `/js/camera-service-worker.js`  
**Lines:** ~100 lines

- [ ] Service worker registration
- [ ] Push notification handling
- [ ] Click-to-open camera
- [ ] Badge updates for unviewed alerts

```javascript
// Service worker for camera push notifications
self.addEventListener('push', function(event) {
    const data = event.data.json();
    
    const options = {
        body: data.body,
        icon: data.icon || '/images/camera-icon.png',
        image: data.image, // Snapshot image
        badge: '/images/badge.png',
        vibrate: [200, 100, 200],
        data: data.data,
        actions: [
            { action: 'view', title: '👁️ View Live' },
            { action: 'dismiss', title: '❌ Dismiss' }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    
    if (event.action === 'view') {
        const cameraId = event.notification.data.camera_id;
        event.waitUntil(
            clients.openWindow(`/dashboard/cameras.php?camera=${cameraId}`)
        );
    }
});
```

- [ ] Upload and test push notifications

---

### **Section 6: Database Schema (From Blueprint)**

#### **Task 6A.15: Create/Update Camera Database Tables**
**File:** Update `/databases/devices.db`  
**Blueprint Reference:** Section 10 - Database Schema

- [ ] Create cameras table (matching blueprint)
- [ ] Create motion_events table (matching blueprint)
- [ ] Create motion_detection_settings table
- [ ] Create camera_recordings table
- [ ] Create share_links table
- [ ] Add indexes for performance

**Cameras Table (From Blueprint - EXACT):**
```sql
CREATE TABLE IF NOT EXISTS cameras (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    
    -- Camera Info
    camera_id TEXT UNIQUE NOT NULL,
    camera_name TEXT NOT NULL,
    location TEXT,
    
    -- Connection
    local_ip TEXT NOT NULL,
    rtsp_port INTEGER DEFAULT 554,
    rtsp_username TEXT,
    rtsp_password TEXT,
    rtsp_url TEXT,
    
    -- Capabilities
    supports_audio INTEGER DEFAULT 0,
    supports_ptz INTEGER DEFAULT 0,
    supports_two_way INTEGER DEFAULT 0,
    max_resolution TEXT DEFAULT '1080p',
    
    -- Recording Settings
    recording_enabled INTEGER DEFAULT 0,
    recording_mode TEXT DEFAULT 'continuous',
    motion_detection INTEGER DEFAULT 0,
    motion_sensitivity INTEGER DEFAULT 50,
    
    -- Storage
    storage_location TEXT,
    retention_days INTEGER DEFAULT 7,
    
    -- Display
    display_order INTEGER DEFAULT 0,
    
    -- Status
    is_online INTEGER DEFAULT 1,
    last_seen TEXT,
    
    -- Metadata
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Indexes
CREATE INDEX idx_cameras_user ON cameras(user_id);
CREATE INDEX idx_cameras_online ON cameras(is_online);
```

**Motion Events Table (From Blueprint - EXACT):**
```sql
CREATE TABLE IF NOT EXISTS motion_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    camera_id TEXT NOT NULL,
    
    -- Event Info
    detection_time TEXT DEFAULT CURRENT_TIMESTAMP,
    thumbnail TEXT,
    recording_id INTEGER,
    confidence INTEGER DEFAULT 100,
    
    -- Alert Status
    notified INTEGER DEFAULT 0,
    viewed INTEGER DEFAULT 0,
    
    FOREIGN KEY (camera_id) REFERENCES cameras(camera_id) ON DELETE CASCADE
);

-- Index for quick lookups
CREATE INDEX idx_motion_camera ON motion_events(camera_id);
CREATE INDEX idx_motion_time ON motion_events(detection_time);
```

**Share Links Table (NEW):**
```sql
CREATE TABLE IF NOT EXISTS recording_shares (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    recording_id INTEGER NOT NULL,
    share_token TEXT UNIQUE NOT NULL,
    created_by INTEGER NOT NULL,
    expires_at TEXT NOT NULL,
    view_count INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recording_id) REFERENCES camera_recordings(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

- [ ] Run SQL to create/update tables
- [ ] Verify all tables exist
- [ ] Test with sample data

---

## 🎯 FINAL VERIFICATION - PART 6A (COMPLETE)

### **Camera Discovery:**
- [ ] Scanner finds cameras via brute force
- [ ] Detects Geeni cameras in cloud mode
- [ ] Detects Wyze cameras
- [ ] Detects Ring cameras
- [ ] ONVIF discovery works
- [ ] Cloud bypass successful for Geeni
- [ ] All cameras appear in dashboard

### **Live Streaming:**
- [ ] Single camera live view works
- [ ] Multi-camera grid (1x1, 2x2, 3x3, 4x4)
- [ ] Video quality selection (1080p, 720p, 480p)
- [ ] Full screen mode
- [ ] Snapshot capture
- [ ] **Two-way audio works** ← ADDED
- [ ] **PTZ controls work** ← ADDED
- [ ] Low latency (<500ms)
- [ ] Handles connection errors

### **Multi-Camera Grid:**
- [ ] Grid layout selector works
- [ ] Click to expand camera
- [ ] **Drag to rearrange works** ← ADDED
- [ ] **Auto-cycle through cameras works** ← ADDED
- [ ] Camera names displayed
- [ ] Show/hide inactive cameras

### **Recording & Playback:**
- [ ] Can start recording
- [ ] Can stop recording
- [ ] Recordings saved to disk
- [ ] Can play recordings
- [ ] **Timeline with motion markers** ← ADDED
- [ ] **Speed control (0.5x, 1x, 2x, 4x)** ← ADDED
- [ ] **Share clips works** ← ADDED
- [ ] Can download recordings
- [ ] Can delete recordings
- [ ] Storage management works

### **Motion Detection & Alerts:**
- [ ] Can enable/disable
- [ ] Sensitivity adjustment works
- [ ] Detection zones configurable
- [ ] **Email alerts send** ← CONFIRMED
- [ ] **Push notifications send** ← ADDED
- [ ] **SMS alerts send** ← ADDED
- [ ] **Browser notifications work** ← ADDED
- [ ] Auto-recording on motion
- [ ] Motion events logged
- [ ] Thumbnail capture works

### **Mobile Access:**
- [ ] Mobile-optimized UI works
- [ ] Touch controls work
- [ ] Swipe between cameras
- [ ] Push notifications setup
- [ ] Service worker registered

### **API Endpoints (Matching Blueprint):**
- [ ] GET /api/cameras.php?action=list
- [ ] GET /api/cameras.php?action=motion_events
- [ ] GET /api/camera-stream.php?camera_id=xxx
- [ ] POST /api/cameras.php?action=ptz
- [ ] POST /api/cameras.php?action=send_audio
- [ ] POST /api/cameras.php?action=snapshot
- [ ] POST /api/cameras.php?action=save_order

---

## 📊 UPDATED TIME ESTIMATE

**Part 6A Total:** 24-30 hours (4-5 days)

**Breakdown:**
- Section 1: Brute Force Scanner (6-8 hrs)
- Section 2: Live Streaming + PTZ + Two-Way Audio (6-8 hrs)
- Section 3: Recording & Playback + Speed + Share (5-6 hrs)
- Section 4: Motion Detection + All Alert Types (4-5 hrs)
- Section 5: Mobile Access (3-4 hrs)
- Section 6: Database Schema (1 hr)

---

## 🚀 THIS IS THE SELLING POINT!

**Marketing Headline:**
"Stop Paying Ring $360/Year - Use Your Cameras FREE Forever"

**ALL Features Now Included:**
- ✅ Bypass cloud subscriptions
- ✅ Direct local access
- ✅ Zero monthly fees
- ✅ Unlimited storage
- ✅ Complete privacy
- ✅ Works with Geeni, Wyze, Ring, Nest
- ✅ **Two-way audio (talk through camera)**
- ✅ **PTZ controls (pan/tilt/zoom)**
- ✅ **Multi-camera grid with drag-to-arrange**
- ✅ **Auto-cycle through cameras**
- ✅ **Playback speed control**
- ✅ **Share clips with friends**
- ✅ **Push notifications**
- ✅ **SMS alerts**
- ✅ **Mobile-optimized interface**

**THIS WILL SELL!** 🎯
