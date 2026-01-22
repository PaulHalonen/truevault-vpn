<?php
/**
 * Mobile Camera Interface - Task 6A.13
 * Touch-optimized camera viewing for smartphones
 */

define('TRUEVAULT_INIT', true);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0a0a0f">
    <title>TrueVault Cameras</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
        
        :root {
            --bg: #0a0a0f;
            --bg-card: #12121a;
            --accent: #00d4ff;
            --accent-green: #00ff88;
            --text: #ffffff;
            --text-dim: #666;
            --border: #2a2a3a;
            --danger: #ff4444;
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            min-height: -webkit-fill-available;
            overflow-x: hidden;
            padding-top: var(--safe-top);
            padding-bottom: var(--safe-bottom);
        }
        
        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 56px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            padding-top: var(--safe-top);
            z-index: 100;
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .header-actions {
            display: flex;
            gap: 12px;
        }
        
        .header-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--bg);
            border: 1px solid var(--border);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
        }
        
        .header-btn.active {
            background: var(--accent);
            color: #000;
            border-color: var(--accent);
        }
        
        /* Main Content */
        .main {
            padding-top: calc(56px + var(--safe-top));
            padding-bottom: calc(80px + var(--safe-bottom));
            min-height: 100vh;
        }
        
        /* Camera Grid */
        .camera-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2px;
            padding: 2px;
        }
        
        .camera-grid.grid-2 {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .camera-grid.grid-4 {
            grid-template-columns: repeat(2, 1fr);
        }
        
        /* Camera Card */
        .camera-card {
            position: relative;
            background: #000;
            aspect-ratio: 16/9;
            overflow: hidden;
        }
        
        .camera-card video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .camera-card .overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(transparent 60%, rgba(0,0,0,0.8));
            pointer-events: none;
        }
        
        .camera-card .info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 12px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        .camera-card .name {
            font-size: 14px;
            font-weight: 600;
            text-shadow: 0 1px 3px rgba(0,0,0,0.8);
        }
        
        .camera-card .status {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
        }
        
        .camera-card .status .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent-green);
            animation: pulse 2s infinite;
        }
        
        .camera-card .status .dot.offline {
            background: var(--danger);
            animation: none;
        }
        
        .camera-card .motion-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--danger);
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            animation: flash 0.5s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        @keyframes flash {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        /* Fullscreen View */
        .fullscreen-view {
            position: fixed;
            inset: 0;
            background: #000;
            z-index: 200;
            display: none;
        }
        
        .fullscreen-view.active {
            display: flex;
            flex-direction: column;
        }
        
        .fullscreen-video {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .fullscreen-video video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .fullscreen-controls {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            padding-bottom: calc(20px + var(--safe-bottom));
            background: linear-gradient(transparent, rgba(0,0,0,0.9));
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .fullscreen-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .fullscreen-name {
            font-size: 18px;
            font-weight: 600;
        }
        
        .fullscreen-time {
            font-size: 14px;
            color: var(--text-dim);
        }
        
        .fullscreen-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        
        .action-btn {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            border: none;
            color: #fff;
            font-size: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .action-btn:active {
            transform: scale(0.95);
            background: rgba(255,255,255,0.2);
        }
        
        .action-btn.primary {
            background: var(--accent);
            color: #000;
        }
        
        .action-btn.danger {
            background: var(--danger);
        }
        
        .close-btn {
            position: absolute;
            top: 16px;
            top: calc(16px + var(--safe-top));
            left: 16px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(0,0,0,0.5);
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            z-index: 10;
        }
        
        /* PTZ Controls */
        .ptz-controls {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            flex-direction: column;
            gap: 8px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .fullscreen-view.show-controls .ptz-controls {
            opacity: 1;
        }
        
        .ptz-pad {
            display: grid;
            grid-template-columns: repeat(3, 44px);
            grid-template-rows: repeat(3, 44px);
            gap: 4px;
        }
        
        .ptz-btn {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background: rgba(0,0,0,0.6);
            border: 1px solid var(--border);
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }
        
        .ptz-btn:active {
            background: var(--accent);
            color: #000;
        }
        
        .ptz-btn.center {
            grid-column: 2;
            grid-row: 2;
        }
        
        .zoom-controls {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 64px;
            height: calc(64px + var(--safe-bottom));
            background: var(--bg-card);
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-around;
            align-items: flex-start;
            padding-top: 8px;
            padding-bottom: var(--safe-bottom);
            z-index: 100;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            color: var(--text-dim);
            text-decoration: none;
            font-size: 10px;
            padding: 4px 16px;
        }
        
        .nav-item i {
            font-size: 22px;
        }
        
        .nav-item.active {
            color: var(--accent);
        }
        
        .nav-item .badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--danger);
            color: #fff;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
        }
        
        /* Events Panel */
        .events-panel {
            position: fixed;
            bottom: calc(64px + var(--safe-bottom));
            left: 0;
            right: 0;
            max-height: 50vh;
            background: var(--bg-card);
            border-top: 1px solid var(--border);
            border-radius: 16px 16px 0 0;
            transform: translateY(100%);
            transition: transform 0.3s;
            z-index: 90;
            overflow: hidden;
        }
        
        .events-panel.open {
            transform: translateY(0);
        }
        
        .events-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid var(--border);
        }
        
        .events-header h3 {
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .events-list {
            overflow-y: auto;
            max-height: calc(50vh - 60px);
        }
        
        .event-item {
            display: flex;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
        }
        
        .event-thumb {
            width: 80px;
            height: 45px;
            background: #222;
            border-radius: 6px;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .event-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .event-info {
            flex: 1;
        }
        
        .event-info h4 {
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .event-info p {
            font-size: 12px;
            color: var(--text-dim);
        }
        
        /* Loading */
        .loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: var(--text-dim);
        }
        
        .loading i {
            font-size: 32px;
            margin-bottom: 12px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-dim);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        /* Swipe indicator */
        .swipe-indicator {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            padding: 20px;
            color: rgba(255,255,255,0.5);
            font-size: 24px;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .swipe-indicator.left { left: 0; }
        .swipe-indicator.right { right: 0; }
        
        /* Two-way audio */
        .audio-active {
            position: absolute;
            top: 16px;
            left: 16px;
            background: var(--danger);
            color: #fff;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 6px;
        }
        
        .audio-active.show {
            display: flex;
        }
        
        .audio-active i {
            animation: pulse 1s infinite;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <h1>📷 Cameras</h1>
        <div class="header-actions">
            <button class="header-btn" onclick="toggleGrid()" id="grid-btn">
                <i class="fas fa-th"></i>
            </button>
            <button class="header-btn" onclick="refreshAll()">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        <div id="camera-grid" class="camera-grid">
            <div class="loading">
                <i class="fas fa-spinner"></i>
                <span>Loading cameras...</span>
            </div>
        </div>
    </main>

    <!-- Fullscreen View -->
    <div id="fullscreen-view" class="fullscreen-view">
        <button class="close-btn" onclick="closeFullscreen()">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="audio-active" id="audio-indicator">
            <i class="fas fa-microphone"></i>
            <span>Speaking...</span>
        </div>
        
        <div class="fullscreen-video" onclick="toggleControls()">
            <video id="fullscreen-video" playsinline autoplay muted></video>
        </div>
        
        <!-- PTZ Controls -->
        <div class="ptz-controls" id="ptz-controls">
            <div class="ptz-pad">
                <div></div>
                <button class="ptz-btn" ontouchstart="ptzStart('up')" ontouchend="ptzStop()">
                    <i class="fas fa-chevron-up"></i>
                </button>
                <div></div>
                <button class="ptz-btn" ontouchstart="ptzStart('left')" ontouchend="ptzStop()">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="ptz-btn center" onclick="ptzHome()">
                    <i class="fas fa-home"></i>
                </button>
                <button class="ptz-btn" ontouchstart="ptzStart('right')" ontouchend="ptzStop()">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <div></div>
                <button class="ptz-btn" ontouchstart="ptzStart('down')" ontouchend="ptzStop()">
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div></div>
            </div>
            <div class="zoom-controls">
                <button class="ptz-btn" ontouchstart="ptzStart('zoom_in')" ontouchend="ptzStop()">
                    <i class="fas fa-search-plus"></i>
                </button>
                <button class="ptz-btn" ontouchstart="ptzStart('zoom_out')" ontouchend="ptzStop()">
                    <i class="fas fa-search-minus"></i>
                </button>
            </div>
        </div>
        
        <div class="swipe-indicator left"><i class="fas fa-chevron-left"></i></div>
        <div class="swipe-indicator right"><i class="fas fa-chevron-right"></i></div>
        
        <div class="fullscreen-controls">
            <div class="fullscreen-info">
                <span class="fullscreen-name" id="fs-name">Camera</span>
                <span class="fullscreen-time" id="fs-time">Live</span>
            </div>
            <div class="fullscreen-actions">
                <button class="action-btn" onclick="takeSnapshot()">
                    <i class="fas fa-camera"></i>
                </button>
                <button class="action-btn" onclick="toggleRecording()" id="record-btn">
                    <i class="fas fa-circle"></i>
                </button>
                <button class="action-btn primary" onclick="toggleAudio()" id="audio-btn">
                    <i class="fas fa-microphone"></i>
                </button>
                <button class="action-btn" onclick="toggleMute()" id="mute-btn">
                    <i class="fas fa-volume-up"></i>
                </button>
                <button class="action-btn" onclick="openRecordings()">
                    <i class="fas fa-film"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Events Panel -->
    <div id="events-panel" class="events-panel">
        <div class="events-header">
            <h3><i class="fas fa-bell" style="color: var(--danger)"></i> Motion Events</h3>
            <button class="header-btn" onclick="toggleEvents()" style="width:32px;height:32px;font-size:14px">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="events-list" class="events-list">
            <!-- Events loaded dynamically -->
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="/dashboard/cameras.php" class="nav-item active">
            <i class="fas fa-video"></i>
            <span>Live</span>
        </a>
        <a href="/dashboard/recordings.php" class="nav-item">
            <i class="fas fa-film"></i>
            <span>Recordings</span>
        </a>
        <a href="#" class="nav-item" onclick="toggleEvents(); return false;" style="position:relative">
            <i class="fas fa-bell"></i>
            <span>Events</span>
            <span class="badge" id="event-badge" style="display:none">0</span>
        </a>
        <a href="/dashboard" class="nav-item">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
    </nav>

    <script>
        // State
        let cameras = [];
        let currentCameraIndex = 0;
        let gridMode = 1; // 1, 2, or 4
        let hlsInstances = {};
        let isRecording = false;
        let isMuted = true;
        let isAudioActive = false;
        let controlsTimeout = null;
        let touchStartX = 0;
        let motionEvents = [];
        let mediaRecorder = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadCameras();
            loadMotionEvents();
            setupSwipeGestures();
            
            // Refresh events every 30 seconds
            setInterval(loadMotionEvents, 30000);
            
            // Register service worker for push notifications
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').catch(console.error);
            }
        });

        // Load cameras
        async function loadCameras() {
            try {
                const resp = await fetch('/api/cameras.php?action=list');
                const data = await resp.json();
                
                if (data.success && data.cameras) {
                    cameras = data.cameras;
                    renderCameraGrid();
                } else {
                    showEmptyState();
                }
            } catch (e) {
                console.error('Failed to load cameras:', e);
                showEmptyState();
            }
        }

        // Render camera grid
        function renderCameraGrid() {
            const grid = document.getElementById('camera-grid');
            
            if (cameras.length === 0) {
                showEmptyState();
                return;
            }
            
            grid.innerHTML = cameras.map((cam, index) => `
                <div class="camera-card" onclick="openFullscreen(${index})">
                    <video id="video-${cam.camera_id}" playsinline autoplay muted></video>
                    <div class="overlay"></div>
                    ${cam.has_motion ? '<div class="motion-badge">MOTION</div>' : ''}
                    <div class="info">
                        <div>
                            <div class="name">${cam.camera_name}</div>
                            <div class="status">
                                <span class="dot ${cam.is_online ? '' : 'offline'}"></span>
                                ${cam.is_online ? 'Live' : 'Offline'}
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            // Start streams
            cameras.forEach(cam => {
                if (cam.is_online) {
                    startStream(cam.camera_id, `video-${cam.camera_id}`);
                }
            });
            
            updateGridClass();
        }

        // Show empty state
        function showEmptyState() {
            document.getElementById('camera-grid').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-video-slash"></i>
                    <h3>No Cameras Found</h3>
                    <p>Add cameras in the settings to view them here</p>
                </div>
            `;
        }

        // Start HLS stream
        function startStream(cameraId, videoElementId) {
            const video = document.getElementById(videoElementId);
            if (!video) return;
            
            const streamUrl = `/api/camera-stream.php?action=stream&camera_id=${cameraId}`;
            
            if (Hls.isSupported()) {
                if (hlsInstances[videoElementId]) {
                    hlsInstances[videoElementId].destroy();
                }
                
                const hls = new Hls({
                    enableWorker: true,
                    lowLatencyMode: true,
                    backBufferLength: 30
                });
                
                hls.loadSource(streamUrl);
                hls.attachMedia(video);
                hls.on(Hls.Events.MANIFEST_PARSED, () => {
                    video.play().catch(() => {});
                });
                
                hlsInstances[videoElementId] = hls;
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = streamUrl;
                video.play().catch(() => {});
            }
        }

        // Toggle grid mode
        function toggleGrid() {
            gridMode = gridMode === 1 ? 2 : gridMode === 2 ? 4 : 1;
            updateGridClass();
            
            const btn = document.getElementById('grid-btn');
            btn.innerHTML = gridMode === 1 ? '<i class="fas fa-th"></i>' : 
                           gridMode === 2 ? '<i class="fas fa-th-large"></i>' : 
                           '<i class="fas fa-square"></i>';
        }

        function updateGridClass() {
            const grid = document.getElementById('camera-grid');
            grid.classList.remove('grid-2', 'grid-4');
            if (gridMode === 2) grid.classList.add('grid-2');
            if (gridMode === 4) grid.classList.add('grid-4');
        }

        // Refresh all streams
        function refreshAll() {
            Object.values(hlsInstances).forEach(hls => hls.destroy());
            hlsInstances = {};
            loadCameras();
        }

        // Fullscreen view
        function openFullscreen(index) {
            currentCameraIndex = index;
            const cam = cameras[index];
            
            document.getElementById('fullscreen-view').classList.add('active');
            document.getElementById('fs-name').textContent = cam.camera_name;
            document.getElementById('fs-time').textContent = 'Live';
            
            // Show/hide PTZ based on capability
            document.getElementById('ptz-controls').style.display = cam.supports_ptz ? 'flex' : 'none';
            
            // Start fullscreen stream
            startStream(cam.camera_id, 'fullscreen-video');
            
            // Show controls initially
            showControls();
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }

        function closeFullscreen() {
            document.getElementById('fullscreen-view').classList.remove('active');
            document.getElementById('fullscreen-view').classList.remove('show-controls');
            
            // Stop fullscreen stream
            if (hlsInstances['fullscreen-video']) {
                hlsInstances['fullscreen-video'].destroy();
                delete hlsInstances['fullscreen-video'];
            }
            
            // Stop audio if active
            if (isAudioActive) {
                toggleAudio();
            }
            
            document.body.style.overflow = '';
        }

        function toggleControls() {
            const view = document.getElementById('fullscreen-view');
            if (view.classList.contains('show-controls')) {
                view.classList.remove('show-controls');
            } else {
                showControls();
            }
        }

        function showControls() {
            const view = document.getElementById('fullscreen-view');
            view.classList.add('show-controls');
            
            clearTimeout(controlsTimeout);
            controlsTimeout = setTimeout(() => {
                view.classList.remove('show-controls');
            }, 4000);
        }

        // Swipe gestures for camera switching
        function setupSwipeGestures() {
            const view = document.getElementById('fullscreen-view');
            
            view.addEventListener('touchstart', (e) => {
                touchStartX = e.touches[0].clientX;
            });
            
            view.addEventListener('touchend', (e) => {
                const touchEndX = e.changedTouches[0].clientX;
                const diff = touchStartX - touchEndX;
                
                if (Math.abs(diff) > 100) { // Minimum swipe distance
                    if (diff > 0) {
                        // Swipe left - next camera
                        nextCamera();
                    } else {
                        // Swipe right - previous camera
                        prevCamera();
                    }
                }
            });
        }

        function nextCamera() {
            if (currentCameraIndex < cameras.length - 1) {
                openFullscreen(currentCameraIndex + 1);
            }
        }

        function prevCamera() {
            if (currentCameraIndex > 0) {
                openFullscreen(currentCameraIndex - 1);
            }
        }

        // PTZ Controls
        let ptzInterval = null;

        function ptzStart(direction) {
            const cam = cameras[currentCameraIndex];
            sendPTZ(cam.camera_id, direction);
            
            // Continuous movement while holding
            ptzInterval = setInterval(() => {
                sendPTZ(cam.camera_id, direction);
            }, 200);
        }

        function ptzStop() {
            clearInterval(ptzInterval);
        }

        function ptzHome() {
            const cam = cameras[currentCameraIndex];
            sendPTZ(cam.camera_id, 'home');
        }

        async function sendPTZ(cameraId, action) {
            try {
                await fetch('/api/cameras.php?action=ptz', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ camera_id: cameraId, direction: action })
                });
            } catch (e) {
                console.error('PTZ failed:', e);
            }
        }

        // Camera actions
        async function takeSnapshot() {
            const cam = cameras[currentCameraIndex];
            try {
                const resp = await fetch('/api/cameras.php?action=snapshot', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ camera_id: cam.camera_id })
                });
                const data = await resp.json();
                if (data.success) {
                    showToast('Snapshot saved!');
                }
            } catch (e) {
                showToast('Failed to take snapshot', true);
            }
        }

        async function toggleRecording() {
            const cam = cameras[currentCameraIndex];
            const btn = document.getElementById('record-btn');
            
            isRecording = !isRecording;
            
            try {
                const resp = await fetch('/api/recordings.php?action=' + (isRecording ? 'start' : 'stop'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ camera_id: cam.camera_id })
                });
                const data = await resp.json();
                
                if (data.success) {
                    btn.classList.toggle('danger', isRecording);
                    btn.innerHTML = isRecording ? '<i class="fas fa-stop"></i>' : '<i class="fas fa-circle"></i>';
                    showToast(isRecording ? 'Recording started' : 'Recording stopped');
                }
            } catch (e) {
                isRecording = !isRecording; // Revert
                showToast('Recording failed', true);
            }
        }

        function toggleMute() {
            const video = document.getElementById('fullscreen-video');
            const btn = document.getElementById('mute-btn');
            
            isMuted = !isMuted;
            video.muted = isMuted;
            
            btn.innerHTML = isMuted ? '<i class="fas fa-volume-up"></i>' : '<i class="fas fa-volume-mute"></i>';
        }

        // Two-way audio
        async function toggleAudio() {
            const btn = document.getElementById('audio-btn');
            const indicator = document.getElementById('audio-indicator');
            
            if (isAudioActive) {
                // Stop audio
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    mediaRecorder.stop();
                }
                isAudioActive = false;
                btn.classList.remove('danger');
                indicator.classList.remove('show');
            } else {
                // Start audio capture
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    mediaRecorder = new MediaRecorder(stream);
                    
                    mediaRecorder.ondataavailable = async (e) => {
                        if (e.data.size > 0) {
                            const cam = cameras[currentCameraIndex];
                            const reader = new FileReader();
                            reader.onloadend = async () => {
                                const base64 = reader.result.split(',')[1];
                                await fetch('/api/cameras.php?action=send_audio', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        camera_id: cam.camera_id,
                                        audio_data: base64
                                    })
                                });
                            };
                            reader.readAsDataURL(e.data);
                        }
                    };
                    
                    mediaRecorder.start(500); // Send chunks every 500ms
                    isAudioActive = true;
                    btn.classList.add('danger');
                    indicator.classList.add('show');
                } catch (e) {
                    showToast('Microphone access denied', true);
                }
            }
        }

        function openRecordings() {
            const cam = cameras[currentCameraIndex];
            window.location.href = `/dashboard/recordings.php?camera=${cam.camera_id}`;
        }

        // Motion Events
        async function loadMotionEvents() {
            try {
                const resp = await fetch('/api/motion.php?action=events&limit=20');
                const data = await resp.json();
                
                if (data.success) {
                    motionEvents = data.events;
                    renderEvents();
                    updateEventBadge();
                    updateCameraMotionStatus();
                }
            } catch (e) {
                console.error('Failed to load events:', e);
            }
        }

        function renderEvents() {
            const list = document.getElementById('events-list');
            
            if (motionEvents.length === 0) {
                list.innerHTML = `
                    <div class="empty-state" style="padding:30px">
                        <i class="fas fa-bell-slash"></i>
                        <p>No recent events</p>
                    </div>
                `;
                return;
            }
            
            list.innerHTML = motionEvents.map(evt => `
                <div class="event-item" onclick="viewEvent(${evt.id})">
                    <div class="event-thumb">
                        ${evt.thumbnail_path ? `<img src="${evt.thumbnail_path}" alt="">` : ''}
                    </div>
                    <div class="event-info">
                        <h4>${evt.camera_name || 'Camera'}</h4>
                        <p>${formatEventTime(evt.detected_at)}</p>
                    </div>
                </div>
            `).join('');
        }

        function toggleEvents() {
            document.getElementById('events-panel').classList.toggle('open');
        }

        function updateEventBadge() {
            const badge = document.getElementById('event-badge');
            const unread = motionEvents.filter(e => !e.acknowledged).length;
            
            if (unread > 0) {
                badge.textContent = unread > 9 ? '9+' : unread;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }

        function updateCameraMotionStatus() {
            // Mark cameras with recent motion
            const recentMotionCameras = new Set(
                motionEvents
                    .filter(e => {
                        const eventTime = new Date(e.detected_at);
                        return (Date.now() - eventTime) < 60000; // Last minute
                    })
                    .map(e => e.camera_id)
            );
            
            cameras.forEach(cam => {
                cam.has_motion = recentMotionCameras.has(cam.camera_id);
            });
        }

        function viewEvent(eventId) {
            const event = motionEvents.find(e => e.id === eventId);
            if (event && event.recording_id) {
                window.location.href = `/dashboard/recordings.php?recording=${event.recording_id}`;
            } else if (event) {
                // Open camera fullscreen
                const camIndex = cameras.findIndex(c => c.camera_id === event.camera_id);
                if (camIndex >= 0) {
                    toggleEvents();
                    openFullscreen(camIndex);
                }
            }
        }

        // Utilities
        function formatEventTime(dateStr) {
            const date = new Date(dateStr);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) return 'Just now';
            if (diff < 3600000) return Math.floor(diff / 60000) + ' min ago';
            if (diff < 86400000) return Math.floor(diff / 3600000) + ' hours ago';
            
            return date.toLocaleDateString();
        }

        function showToast(message, isError = false) {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                bottom: 100px;
                left: 50%;
                transform: translateX(-50%);
                background: ${isError ? 'var(--danger)' : 'var(--accent-green)'};
                color: ${isError ? '#fff' : '#000'};
                padding: 12px 24px;
                border-radius: 8px;
                font-weight: 600;
                z-index: 1000;
                animation: fadeIn 0.3s;
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'fadeOut 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 2000);
        }

        // Handle visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Pause streams when app is backgrounded
                Object.values(hlsInstances).forEach(hls => {
                    const video = hls.media;
                    if (video) video.pause();
                });
            } else {
                // Resume when visible
                Object.values(hlsInstances).forEach(hls => {
                    const video = hls.media;
                    if (video) video.play().catch(() => {});
                });
                loadMotionEvents();
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            const fs = document.getElementById('fullscreen-view');
            if (!fs.classList.contains('active')) return;
            
            switch (e.key) {
                case 'Escape':
                    closeFullscreen();
                    break;
                case 'ArrowLeft':
                    prevCamera();
                    break;
                case 'ArrowRight':
                    nextCamera();
                    break;
                case ' ':
                    e.preventDefault();
                    toggleMute();
                    break;
            }
        });
    </script>
    
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(-50%) translateY(20px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </style>
</body>
</html>
