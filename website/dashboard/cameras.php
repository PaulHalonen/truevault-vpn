<?php
/**
 * Camera Dashboard - Task 6A.5
 * Live video streaming with HLS.js, PTZ controls, two-way audio
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/JWT.php';

// Check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Get user's cameras
$db = new SQLite3(DB_PATH . '/devices.db');
$stmt = $db->prepare("SELECT * FROM cameras WHERE user_id = :user_id ORDER BY display_order");
$stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
$result = $stmt->execute();

$cameras = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $cameras[] = $row;
}
$db->close();

$cameraCount = count($cameras);
$onlineCount = count(array_filter($cameras, fn($c) => $c['is_online']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Camera Dashboard - TrueVault</title>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0f1a, #1a1a2e);
            color: #fff;
            min-height: 100vh;
        }
        .header {
            background: rgba(255,255,255,0.03);
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .header h1 {
            font-size: 1.4rem;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stats {
            display: flex;
            gap: 20px;
        }
        .stat {
            text-align: center;
        }
        .stat-num {
            font-size: 1.5rem;
            font-weight: 700;
            color: #00ff88;
        }
        .stat-label {
            font-size: 0.75rem;
            color: #666;
        }
        .container {
            padding: 20px;
            max-width: 1600px;
            margin: 0 auto;
        }
        .controls-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .grid-selector {
            display: flex;
            gap: 8px;
        }
        .grid-btn {
            padding: 8px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.2s;
        }
        .grid-btn:hover, .grid-btn.active {
            background: rgba(0,217,255,0.2);
            border-color: #00d9ff;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-primary {
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            color: #0f0f1a;
        }
        .btn-secondary {
            background: rgba(255,255,255,0.08);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.15);
        }
        
        /* Camera Grid */
        .camera-grid {
            display: grid;
            gap: 15px;
            transition: 0.3s;
        }
        .camera-grid.grid-1x1 { grid-template-columns: 1fr; }
        .camera-grid.grid-2x2 { grid-template-columns: repeat(2, 1fr); }
        .camera-grid.grid-3x3 { grid-template-columns: repeat(3, 1fr); }
        .camera-grid.grid-4x4 { grid-template-columns: repeat(4, 1fr); }
        
        /* Camera Tile */
        .camera-tile {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            transition: 0.2s;
        }
        .camera-tile:hover {
            border-color: #00d9ff;
            transform: scale(1.01);
        }
        .camera-tile.offline {
            opacity: 0.5;
        }
        .camera-header {
            padding: 10px 15px;
            background: rgba(0,0,0,0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .camera-name {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #00ff88;
        }
        .status-dot.offline {
            background: #ff5555;
        }
        .camera-video {
            width: 100%;
            aspect-ratio: 16/9;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .camera-video video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .camera-controls {
            padding: 10px 15px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            background: rgba(0,0,0,0.2);
        }
        .ctrl-btn {
            padding: 6px 12px;
            background: rgba(255,255,255,0.1);
            border: none;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: 0.2s;
        }
        .ctrl-btn:hover {
            background: rgba(0,217,255,0.3);
        }
        .ctrl-btn.active {
            background: #00d9ff;
            color: #000;
        }
        
        /* Full Screen Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 1000;
        }
        .modal.active {
            display: flex;
            flex-direction: column;
        }
        .modal-header {
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255,255,255,0.03);
        }
        .modal-body {
            flex: 1;
            display: flex;
            padding: 20px;
            gap: 20px;
        }
        .modal-video {
            flex: 1;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }
        .modal-video video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .modal-sidebar {
            width: 280px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .sidebar-card {
            background: rgba(255,255,255,0.03);
            border-radius: 10px;
            padding: 15px;
        }
        .sidebar-card h3 {
            font-size: 0.9rem;
            margin-bottom: 12px;
            color: #00d9ff;
        }
        
        /* PTZ Controls */
        .ptz-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 5px;
            max-width: 150px;
            margin: 0 auto;
        }
        .ptz-btn {
            padding: 12px;
            background: rgba(255,255,255,0.1);
            border: none;
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.2rem;
            transition: 0.2s;
        }
        .ptz-btn:hover {
            background: rgba(0,217,255,0.3);
        }
        .ptz-btn:nth-child(1) { grid-column: 2; }
        .ptz-btn:nth-child(5) { grid-column: 2; }
        .zoom-controls {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        /* Two-Way Audio */
        .talk-btn {
            width: 100%;
            padding: 15px;
            background: rgba(255,100,100,0.2);
            border: 2px solid #ff6b6b;
            color: #ff6b6b;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
        }
        .talk-btn:hover, .talk-btn.active {
            background: #ff6b6b;
            color: #fff;
        }
        
        /* Quality Selector */
        .quality-select {
            width: 100%;
            padding: 10px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            border-radius: 6px;
        }
        
        /* Status Info */
        .status-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .status-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
        }
        .status-row span:first-child {
            color: #888;
        }
        .status-row span:last-child {
            color: #00ff88;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state .icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }
        .empty-state h2 {
            margin-bottom: 10px;
            color: #888;
        }
        
        /* Close Button */
        .close-btn {
            background: rgba(255,255,255,0.1);
            border: none;
            color: #fff;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
        }
        .close-btn:hover {
            background: rgba(255,100,100,0.3);
        }
        
        /* Drag and Drop */
        .camera-tile[draggable="true"] {
            cursor: grab;
        }
        .camera-tile.dragging {
            opacity: 0.5;
            border: 2px dashed #00d9ff;
            cursor: grabbing;
        }
        .camera-tile.drag-over {
            border: 2px solid #00ff88;
        }
        
        /* Auto-Cycle Indicator */
        .cycle-indicator {
            background: rgba(0,217,255,0.2);
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.85rem;
            display: none;
        }
        .cycle-indicator.active {
            display: block;
        }
        
        @media (max-width: 768px) {
            .camera-grid.grid-2x2,
            .camera-grid.grid-3x3,
            .camera-grid.grid-4x4 {
                grid-template-columns: 1fr;
            }
            .modal-body {
                flex-direction: column;
            }
            .modal-sidebar {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📷 Camera Dashboard</h1>
        <div class="stats">
            <div class="stat">
                <div class="stat-num"><?= $cameraCount ?></div>
                <div class="stat-label">Cameras</div>
            </div>
            <div class="stat">
                <div class="stat-num"><?= $onlineCount ?></div>
                <div class="stat-label">Online</div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="controls-bar">
            <div class="grid-selector">
                <button class="grid-btn active" data-grid="2x2">2×2</button>
                <button class="grid-btn" data-grid="1x1">1×1</button>
                <button class="grid-btn" data-grid="3x3">3×3</button>
                <button class="grid-btn" data-grid="4x4">4×4</button>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="cycle-indicator" id="cycleIndicator">📹 Cycling...</span>
                <button class="btn btn-secondary" id="cycleBtn" onclick="toggleCycle()">🔄 Auto-Cycle</button>
                <a href="/dashboard/discover-devices.php" class="btn btn-secondary">🔍 Discover Cameras</a>
                <button class="btn btn-primary" onclick="addCamera()">+ Add Camera</button>
            </div>
        </div>
        
        <?php if (empty($cameras)): ?>
        <div class="empty-state">
            <div class="icon">📷</div>
            <h2>No Cameras Found</h2>
            <p>Run the network scanner to discover cameras on your network.</p>
            <br>
            <a href="/dashboard/discover-devices.php" class="btn btn-primary">🔍 Discover Cameras</a>
        </div>
        <?php else: ?>
        <div class="camera-grid grid-2x2" id="cameraGrid">
            <?php foreach ($cameras as $camera): ?>
            <div class="camera-tile <?= $camera['is_online'] ? '' : 'offline' ?>" 
                 draggable="true"
                 data-camera-id="<?= htmlspecialchars($camera['camera_id']) ?>"
                 data-rtsp="<?= htmlspecialchars($camera['rtsp_url'] ?? '') ?>"
                 onclick="expandCamera('<?= htmlspecialchars($camera['camera_id']) ?>')">
                <div class="camera-header">
                    <div class="camera-name">
                        <span class="status-dot <?= $camera['is_online'] ? '' : 'offline' ?>"></span>
                        <?= htmlspecialchars($camera['camera_name']) ?>
                    </div>
                    <span style="color:#666;font-size:0.8rem"><?= htmlspecialchars($camera['location'] ?? '') ?></span>
                </div>
                <div class="camera-video" id="video-<?= htmlspecialchars($camera['camera_id']) ?>">
                    <video autoplay muted playsinline></video>
                </div>
                <div class="camera-controls">
                    <button class="ctrl-btn" onclick="event.stopPropagation();takeSnapshot('<?= htmlspecialchars($camera['camera_id']) ?>')">📸</button>
                    <button class="ctrl-btn" onclick="event.stopPropagation();toggleAudio('<?= htmlspecialchars($camera['camera_id']) ?>')">🔊</button>
                    <?php if ($camera['recording_enabled']): ?>
                    <button class="ctrl-btn active">🔴 REC</button>
                    <?php endif; ?>
                    <?php if ($camera['supports_ptz']): ?>
                    <button class="ctrl-btn">🎯 PTZ</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Full Screen Modal -->
    <div class="modal" id="cameraModal">
        <div class="modal-header">
            <h2 id="modalCameraName">📷 Camera</h2>
            <button class="close-btn" onclick="closeModal()">✕ Close</button>
        </div>
        <div class="modal-body">
            <div class="modal-video">
                <video id="modalVideo" autoplay playsinline></video>
            </div>
            <div class="modal-sidebar">
                <!-- Status Card -->
                <div class="sidebar-card">
                    <h3>📊 Status</h3>
                    <div class="status-info">
                        <div class="status-row">
                            <span>Connection</span>
                            <span id="modalStatus">✅ Connected</span>
                        </div>
                        <div class="status-row">
                            <span>Quality</span>
                            <span id="modalQuality">1080p</span>
                        </div>
                        <div class="status-row">
                            <span>Latency</span>
                            <span id="modalLatency">--ms</span>
                        </div>
                    </div>
                </div>
                
                <!-- Quality Card -->
                <div class="sidebar-card">
                    <h3>⚙️ Quality</h3>
                    <select class="quality-select" id="qualitySelect" onchange="changeQuality(this.value)">
                        <option value="1080">1080p (HD)</option>
                        <option value="720">720p</option>
                        <option value="480">480p</option>
                        <option value="auto">Auto</option>
                    </select>
                </div>
                
                <!-- PTZ Card -->
                <div class="sidebar-card" id="ptzCard" style="display:none;">
                    <h3>🎯 PTZ Controls</h3>
                    <div class="ptz-grid">
                        <button class="ptz-btn" onclick="ptzControl('up')">⬆️</button>
                        <button class="ptz-btn" onclick="ptzControl('left')">⬅️</button>
                        <button class="ptz-btn" onclick="ptzControl('home')">🏠</button>
                        <button class="ptz-btn" onclick="ptzControl('right')">➡️</button>
                        <button class="ptz-btn" onclick="ptzControl('down')">⬇️</button>
                    </div>
                    <div class="zoom-controls">
                        <button class="ctrl-btn" onclick="ptzControl('zoom_in')">🔍+</button>
                        <button class="ctrl-btn" onclick="ptzControl('zoom_out')">🔍-</button>
                    </div>
                </div>
                
                <!-- Two-Way Audio Card -->
                <div class="sidebar-card" id="audioCard" style="display:none;">
                    <h3>🎙️ Two-Way Audio</h3>
                    <button class="talk-btn" id="talkBtn" 
                            onmousedown="startTalking()" 
                            onmouseup="stopTalking()"
                            ontouchstart="startTalking()"
                            ontouchend="stopTalking()">
                        🎤 Hold to Talk
                    </button>
                </div>
                
                <!-- Actions Card -->
                <div class="sidebar-card">
                    <h3>🎬 Actions</h3>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <button class="ctrl-btn" onclick="takeSnapshot(currentCameraId)" style="width:100%">📸 Snapshot</button>
                        <button class="ctrl-btn" onclick="toggleRecording()" style="width:100%">🔴 Start Recording</button>
                        <button class="ctrl-btn" onclick="openSettings()" style="width:100%">⚙️ Settings</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
// Camera data from PHP
const cameras = <?= json_encode($cameras) ?>;
let currentCameraId = null;
let currentHls = null;
let twoWayAudio = null;
let isRecording = false;

// Grid layout switching
document.querySelectorAll('.grid-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.grid-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const grid = document.getElementById('cameraGrid');
        grid.className = 'camera-grid grid-' + btn.dataset.grid;
    });
});

// Initialize camera streams
function initStreams() {
    cameras.forEach(camera => {
        if (camera.is_online) {
            loadCameraStream(camera.camera_id);
        }
    });
}

// Load HLS stream for a camera
function loadCameraStream(cameraId) {
    const container = document.getElementById('video-' + cameraId);
    if (!container) return;
    
    const video = container.querySelector('video');
    const streamUrl = '/api/camera-stream.php?camera_id=' + cameraId;
    
    if (Hls.isSupported()) {
        const hls = new Hls({
            enableWorker: true,
            lowLatencyMode: true
        });
        hls.loadSource(streamUrl);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, () => {
            video.play().catch(e => console.log('Autoplay prevented'));
        });
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = streamUrl;
        video.play().catch(e => console.log('Autoplay prevented'));
    }
}

// Expand camera to full screen
function expandCamera(cameraId) {
    currentCameraId = cameraId;
    const camera = cameras.find(c => c.camera_id === cameraId);
    if (!camera) return;
    
    document.getElementById('modalCameraName').textContent = '📷 ' + camera.camera_name;
    document.getElementById('cameraModal').classList.add('active');
    
    // Show/hide PTZ and audio controls based on camera capabilities
    document.getElementById('ptzCard').style.display = camera.supports_ptz ? 'block' : 'none';
    document.getElementById('audioCard').style.display = camera.supports_two_way ? 'block' : 'none';
    
    // Load stream in modal
    const video = document.getElementById('modalVideo');
    const streamUrl = '/api/camera-stream.php?camera_id=' + cameraId;
    
    if (currentHls) {
        currentHls.destroy();
    }
    
    if (Hls.isSupported()) {
        currentHls = new Hls({
            enableWorker: true,
            lowLatencyMode: true
        });
        currentHls.loadSource(streamUrl);
        currentHls.attachMedia(video);
        currentHls.on(Hls.Events.MANIFEST_PARSED, () => {
            video.play();
            updateLatency();
        });
    }
}

// Close modal
function closeModal() {
    document.getElementById('cameraModal').classList.remove('active');
    if (currentHls) {
        currentHls.destroy();
        currentHls = null;
    }
    stopTalking();
    currentCameraId = null;
}

// Update latency display
function updateLatency() {
    if (!currentHls) return;
    const latency = Math.round(currentHls.latency * 1000) || '--';
    document.getElementById('modalLatency').textContent = latency + 'ms';
    setTimeout(updateLatency, 1000);
}

// Change quality
function changeQuality(quality) {
    document.getElementById('modalQuality').textContent = quality + 'p';
    // Would reload stream with different quality parameter
}

// PTZ Control
async function ptzControl(direction) {
    if (!currentCameraId) return;
    
    await fetch('/api/cameras.php?action=ptz', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            camera_id: currentCameraId,
            ptz_action: direction
        })
    });
}

// Two-Way Audio
async function startTalking() {
    if (!currentCameraId) return;
    
    const btn = document.getElementById('talkBtn');
    btn.classList.add('active');
    btn.textContent = '🎤 Talking...';
    
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        twoWayAudio = new MediaRecorder(stream);
        
        twoWayAudio.ondataavailable = async (e) => {
            const formData = new FormData();
            formData.append('audio', e.data);
            formData.append('camera_id', currentCameraId);
            
            await fetch('/api/cameras.php?action=send_audio', {
                method: 'POST',
                body: formData
            });
        };
        
        twoWayAudio.start(100);
    } catch (e) {
        console.error('Microphone access denied:', e);
        alert('Please allow microphone access to use two-way audio.');
    }
}

function stopTalking() {
    const btn = document.getElementById('talkBtn');
    btn.classList.remove('active');
    btn.textContent = '🎤 Hold to Talk';
    
    if (twoWayAudio) {
        twoWayAudio.stop();
        twoWayAudio.stream.getTracks().forEach(track => track.stop());
        twoWayAudio = null;
    }
}

// Take snapshot
async function takeSnapshot(cameraId) {
    const response = await fetch('/api/cameras.php?action=snapshot', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ camera_id: cameraId })
    });
    
    const result = await response.json();
    if (result.success) {
        alert('Snapshot saved: ' + result.snapshot_url);
    }
}

// Toggle audio on tile
function toggleAudio(cameraId) {
    const container = document.getElementById('video-' + cameraId);
    const video = container.querySelector('video');
    video.muted = !video.muted;
}

// Toggle recording
function toggleRecording() {
    isRecording = !isRecording;
    const btn = event.target;
    btn.textContent = isRecording ? '⏹️ Stop Recording' : '🔴 Start Recording';
    btn.classList.toggle('active', isRecording);
}

// Add camera manually
function addCamera() {
    window.location.href = '/dashboard/discover-devices.php';
}

// Open camera settings
function openSettings() {
    alert('Settings panel coming soon!');
}

// Close modal on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
});

// ============== DRAG TO REARRANGE ==============
let draggedItem = null;

function initDragDrop() {
    const grid = document.getElementById('cameraGrid');
    if (!grid) return;
    
    grid.addEventListener('dragstart', (e) => {
        const tile = e.target.closest('.camera-tile');
        if (!tile) return;
        draggedItem = tile;
        tile.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    });
    
    grid.addEventListener('dragover', (e) => {
        e.preventDefault();
        const tile = e.target.closest('.camera-tile');
        if (tile && tile !== draggedItem) {
            tile.classList.add('drag-over');
        }
    });
    
    grid.addEventListener('dragleave', (e) => {
        const tile = e.target.closest('.camera-tile');
        if (tile) tile.classList.remove('drag-over');
    });
    
    grid.addEventListener('drop', (e) => {
        e.preventDefault();
        const tile = e.target.closest('.camera-tile');
        if (tile && tile !== draggedItem) {
            tile.classList.remove('drag-over');
            const rect = tile.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;
            if (e.clientY < midY) {
                grid.insertBefore(draggedItem, tile);
            } else {
                grid.insertBefore(draggedItem, tile.nextSibling);
            }
            saveCameraOrder();
        }
    });
    
    grid.addEventListener('dragend', (e) => {
        if (draggedItem) {
            draggedItem.classList.remove('dragging');
            draggedItem = null;
        }
        document.querySelectorAll('.drag-over').forEach(t => t.classList.remove('drag-over'));
    });
}

async function saveCameraOrder() {
    const order = Array.from(document.querySelectorAll('.camera-tile'))
        .map(tile => tile.dataset.cameraId);
    
    await fetch('/api/cameras.php?action=save_order', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order })
    });
}

// ============== AUTO-CYCLE ==============
let cycleTimer = null;
let cycleIndex = 0;
let cycleInterval = 10000; // 10 seconds

function toggleCycle() {
    if (cycleTimer) {
        stopCycle();
    } else {
        startCycle();
    }
}

function startCycle() {
    if (cameras.length === 0) return;
    
    cycleIndex = 0;
    document.getElementById('cycleBtn').textContent = '⏹️ Stop Cycle';
    document.getElementById('cycleIndicator').classList.add('active');
    
    showCycleCamera();
    cycleTimer = setInterval(() => {
        cycleIndex = (cycleIndex + 1) % cameras.length;
        showCycleCamera();
    }, cycleInterval);
}

function stopCycle() {
    if (cycleTimer) {
        clearInterval(cycleTimer);
        cycleTimer = null;
    }
    document.getElementById('cycleBtn').textContent = '🔄 Auto-Cycle';
    document.getElementById('cycleIndicator').classList.remove('active');
    closeModal();
}

function showCycleCamera() {
    const camera = cameras[cycleIndex];
    if (!camera) return;
    
    document.getElementById('cycleIndicator').textContent = 
        `📹 ${cycleIndex + 1}/${cameras.length}: ${camera.camera_name}`;
    
    expandCamera(camera.camera_id);
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    initStreams();
    initDragDrop();
});
</script>
</body>
</html>
