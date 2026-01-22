<?php
/**
 * Motion Detection Dashboard - Task 6A.12
 * Configure motion detection, view events, set zones
 */

define('TRUEVAULT_INIT', true);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$pageTitle = 'Motion Detection';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - TrueVault VPN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #12121a;
            --bg-card: #1a1a24;
            --accent: #00d4ff;
            --accent-green: #00ff88;
            --accent-red: #ff4444;
            --text-primary: #ffffff;
            --text-secondary: #888;
            --border: #2a2a3a;
        }
        body { background: var(--bg-primary); color: var(--text-primary); font-family: system-ui, sans-serif; }
        .card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; }
        .btn-primary { background: linear-gradient(135deg, var(--accent), var(--accent-green)); color: #000; font-weight: 600; }
        .btn-secondary { background: var(--bg-secondary); border: 1px solid var(--border); }
        .btn-danger { background: rgba(255, 68, 68, 0.15); color: var(--accent-red); border: 1px solid var(--accent-red); }
        
        /* Toggle Switch */
        .toggle { position: relative; width: 50px; height: 26px; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; inset: 0; background: #333; border-radius: 26px; transition: 0.3s; }
        .toggle-slider:before { content: ''; position: absolute; height: 20px; width: 20px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s; }
        .toggle input:checked + .toggle-slider { background: var(--accent-green); }
        .toggle input:checked + .toggle-slider:before { transform: translateX(24px); }
        
        /* Event Timeline */
        .event-item { position: relative; padding-left: 30px; }
        .event-item::before { content: ''; position: absolute; left: 8px; top: 0; bottom: 0; width: 2px; background: var(--border); }
        .event-item::after { content: ''; position: absolute; left: 4px; top: 6px; width: 10px; height: 10px; background: var(--accent-red); border-radius: 50%; }
        .event-item:last-child::before { display: none; }
        
        /* Zone Editor */
        .zone-canvas { position: relative; background: #000; border-radius: 8px; overflow: hidden; }
        .zone-canvas img { width: 100%; display: block; }
        .zone-overlay { position: absolute; inset: 0; }
        .zone-rect { position: absolute; border: 2px solid var(--accent-green); background: rgba(0, 255, 136, 0.1); cursor: move; }
        .zone-rect.selected { border-color: var(--accent); background: rgba(0, 212, 255, 0.2); }
        .zone-handle { position: absolute; width: 10px; height: 10px; background: white; border: 2px solid var(--accent); }
        
        /* Sensitivity Slider */
        .sensitivity-slider { -webkit-appearance: none; width: 100%; height: 8px; background: linear-gradient(90deg, var(--accent-green), var(--accent), var(--accent-red)); border-radius: 4px; }
        .sensitivity-slider::-webkit-slider-thumb { -webkit-appearance: none; width: 20px; height: 20px; background: white; border-radius: 50%; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.3); }
        
        /* Stats */
        .stat-card { background: rgba(255,255,255,0.03); border-radius: 8px; padding: 15px; text-align: center; }
        .stat-value { font-size: 2rem; font-weight: 700; background: linear-gradient(90deg, var(--accent), var(--accent-green)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body class="min-h-screen">
    <!-- Header -->
    <header class="bg-[#12121a] border-b border-[#2a2a3a] px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="/dashboard" class="text-gray-400 hover:text-white">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-xl font-bold">🏃 Motion Detection</h1>
            </div>
            <div class="flex items-center gap-3">
                <span id="status-badge" class="px-3 py-1 rounded-full text-sm bg-gray-700">
                    <i class="fas fa-circle text-xs mr-1"></i> Loading...
                </span>
            </div>
        </div>
    </header>

    <main class="p-6">
        <div class="max-w-7xl mx-auto">
            <!-- Stats Row -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="stat-card">
                    <div class="stat-value" id="stat-cameras">0</div>
                    <div class="text-sm text-gray-500">Cameras Active</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="stat-events-today">0</div>
                    <div class="text-sm text-gray-500">Events Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="stat-events-week">0</div>
                    <div class="text-sm text-gray-500">Events This Week</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="stat-last-event">--</div>
                    <div class="text-sm text-gray-500">Last Event</div>
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Camera List -->
                <div class="lg:col-span-1">
                    <div class="card p-4">
                        <h2 class="text-lg font-semibold mb-4">
                            <i class="fas fa-video mr-2 text-[#00d4ff]"></i>
                            Cameras
                        </h2>
                        <div id="camera-list" class="space-y-2">
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                <p>Loading cameras...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuration Panel -->
                <div class="lg:col-span-2">
                    <!-- No Camera Selected -->
                    <div id="no-camera-selected" class="card p-8 text-center">
                        <i class="fas fa-mouse-pointer text-5xl text-gray-600 mb-4"></i>
                        <h3 class="text-xl mb-2">Select a Camera</h3>
                        <p class="text-gray-500">Choose a camera from the list to configure motion detection</p>
                    </div>

                    <!-- Camera Configuration -->
                    <div id="camera-config" class="hidden space-y-4">
                        <!-- Camera Header -->
                        <div class="card p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 id="config-camera-name" class="text-lg font-semibold">Camera Name</h2>
                                    <p id="config-camera-location" class="text-sm text-gray-500">Location</p>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span id="detector-status" class="text-sm text-gray-500">
                                        <i class="fas fa-circle text-xs mr-1"></i> Stopped
                                    </span>
                                    <label class="toggle">
                                        <input type="checkbox" id="motion-enabled" onchange="toggleMotion()">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Sensitivity -->
                        <div class="card p-4">
                            <h3 class="font-semibold mb-4">
                                <i class="fas fa-sliders-h mr-2 text-[#00d4ff]"></i>
                                Sensitivity Settings
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <div class="flex justify-between mb-2">
                                        <label class="text-sm">Detection Sensitivity</label>
                                        <span id="sensitivity-value" class="text-sm text-[#00d4ff]">25</span>
                                    </div>
                                    <input type="range" id="sensitivity" min="1" max="100" value="25" 
                                           class="sensitivity-slider" oninput="updateSensitivityLabel()">
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>Less Sensitive</span>
                                        <span>More Sensitive</span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-sm text-gray-400 block mb-1">Cooldown (seconds)</label>
                                        <input type="number" id="cooldown" value="10" min="1" max="300" 
                                               class="w-full bg-[#1a1a24] border border-[#2a2a3a] rounded-lg px-3 py-2">
                                    </div>
                                    <div>
                                        <label class="text-sm text-gray-400 block mb-1">Record Duration (sec)</label>
                                        <input type="number" id="record-duration" value="30" min="5" max="300" 
                                               class="w-full bg-[#1a1a24] border border-[#2a2a3a] rounded-lg px-3 py-2">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Options -->
                        <div class="card p-4">
                            <h3 class="font-semibold mb-4">
                                <i class="fas fa-cog mr-2 text-[#00d4ff]"></i>
                                Detection Options
                            </h3>
                            <div class="space-y-3">
                                <label class="flex items-center justify-between cursor-pointer">
                                    <span>Save snapshots on motion</span>
                                    <input type="checkbox" id="snapshot-enabled" checked class="w-5 h-5 rounded">
                                </label>
                                <label class="flex items-center justify-between cursor-pointer">
                                    <span>Record video on motion</span>
                                    <input type="checkbox" id="recording-enabled" checked class="w-5 h-5 rounded">
                                </label>
                                <label class="flex items-center justify-between cursor-pointer">
                                    <span>Push notifications</span>
                                    <input type="checkbox" id="notification-enabled" checked class="w-5 h-5 rounded">
                                </label>
                            </div>
                        </div>

                        <!-- Zone Editor -->
                        <div class="card p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-semibold">
                                    <i class="fas fa-draw-polygon mr-2 text-[#00d4ff]"></i>
                                    Detection Zones
                                </h3>
                                <button onclick="addZone()" class="btn-secondary px-3 py-1 rounded-lg text-sm">
                                    <i class="fas fa-plus mr-1"></i> Add Zone
                                </button>
                            </div>
                            <div class="zone-canvas mb-4" id="zone-canvas">
                                <img id="zone-preview" src="" alt="Camera Preview">
                                <div class="zone-overlay" id="zone-overlay"></div>
                            </div>
                            <div id="zone-list" class="space-y-2">
                                <p class="text-sm text-gray-500">No zones defined. The entire frame will be monitored.</p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3">
                            <button onclick="saveConfig()" class="btn-primary flex-1 py-3 rounded-lg">
                                <i class="fas fa-save mr-2"></i> Save Configuration
                            </button>
                            <button onclick="testDetection()" class="btn-secondary px-6 py-3 rounded-lg">
                                <i class="fas fa-flask mr-2"></i> Test
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Events -->
            <div class="card p-4 mt-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">
                        <i class="fas fa-history mr-2 text-[#00d4ff]"></i>
                        Recent Motion Events
                    </h2>
                    <div class="flex gap-2">
                        <select id="event-filter-camera" onchange="loadEvents()" 
                                class="bg-[#1a1a24] border border-[#2a2a3a] rounded-lg px-3 py-2 text-sm">
                            <option value="">All Cameras</option>
                        </select>
                        <button onclick="clearOldEvents()" class="btn-danger px-3 py-2 rounded-lg text-sm">
                            <i class="fas fa-trash mr-1"></i> Clear Old
                        </button>
                    </div>
                </div>
                <div id="events-list" class="space-y-3">
                    <p class="text-center text-gray-500 py-4">Loading events...</p>
                </div>
                <div id="events-pagination" class="flex justify-center gap-2 mt-4"></div>
            </div>
        </div>
    </main>

    <!-- Test Result Modal -->
    <div id="test-modal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 hidden">
        <div class="card p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold">Motion Detection Test</h3>
                <button onclick="closeTestModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="test-result" class="text-center py-6">
                <i class="fas fa-spinner fa-spin text-4xl mb-4"></i>
                <p>Running detection test...</p>
            </div>
        </div>
    </div>

    <script>
        // State
        let cameras = [];
        let currentCamera = null;
        let zones = [];
        let events = [];
        let eventsPage = 0;

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadCameras();
            loadStats();
        });

        // Load cameras
        async function loadCameras() {
            try {
                const resp = await fetch('/api/cameras.php?action=list');
                const data = await resp.json();
                if (data.success) {
                    cameras = data.cameras;
                    renderCameraList();
                    populateCameraFilter();
                    loadEvents();
                }
            } catch (e) {
                console.error('Failed to load cameras:', e);
            }
        }

        // Render camera list
        function renderCameraList() {
            const container = document.getElementById('camera-list');
            
            if (cameras.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-video-slash text-2xl mb-2"></i>
                        <p>No cameras found</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = cameras.map(cam => `
                <div class="p-3 rounded-lg cursor-pointer transition-colors ${currentCamera?.camera_id === cam.camera_id ? 'bg-[#00d4ff]/10 border border-[#00d4ff]' : 'bg-[#1a1a24] hover:bg-[#252530]'}"
                     onclick="selectCamera('${cam.camera_id}')">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-[#0a0a0f] flex items-center justify-center">
                                📷
                            </div>
                            <div>
                                <h4 class="font-medium">${cam.camera_name}</h4>
                                <p class="text-xs text-gray-500">${cam.location || cam.local_ip}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            ${cam.motion_detection ? '<span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>' : '<span class="w-2 h-2 bg-gray-600 rounded-full"></span>'}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Select camera
        async function selectCamera(cameraId) {
            currentCamera = cameras.find(c => c.camera_id === cameraId);
            if (!currentCamera) return;
            
            renderCameraList();
            
            // Show config panel
            document.getElementById('no-camera-selected').classList.add('hidden');
            document.getElementById('camera-config').classList.remove('hidden');
            
            // Update header
            document.getElementById('config-camera-name').textContent = currentCamera.camera_name;
            document.getElementById('config-camera-location').textContent = currentCamera.location || currentCamera.local_ip;
            
            // Load motion status
            await loadMotionStatus(cameraId);
            
            // Load zones
            await loadZones(cameraId);
            
            // Load preview image
            loadPreviewImage(cameraId);
        }

        // Load motion status
        async function loadMotionStatus(cameraId) {
            try {
                const resp = await fetch(`/api/motion.php?action=status&camera_id=${cameraId}`);
                const data = await resp.json();
                
                if (data.success) {
                    document.getElementById('motion-enabled').checked = data.enabled;
                    
                    // Update detector status
                    const statusEl = document.getElementById('detector-status');
                    if (data.running) {
                        statusEl.innerHTML = '<i class="fas fa-circle text-green-500 text-xs mr-1"></i> Running';
                    } else if (data.enabled) {
                        statusEl.innerHTML = '<i class="fas fa-circle text-yellow-500 text-xs mr-1"></i> Enabled';
                    } else {
                        statusEl.innerHTML = '<i class="fas fa-circle text-gray-500 text-xs mr-1"></i> Stopped';
                    }
                    
                    // Update config fields
                    if (data.config) {
                        document.getElementById('sensitivity').value = data.config.sensitivity || 25;
                        document.getElementById('cooldown').value = data.config.cooldown || 10;
                        document.getElementById('record-duration').value = data.config.record_duration || 30;
                        document.getElementById('snapshot-enabled').checked = data.config.snapshot_enabled !== false;
                        document.getElementById('recording-enabled').checked = data.config.recording_enabled !== false;
                        document.getElementById('notification-enabled').checked = data.config.notification_enabled !== false;
                        updateSensitivityLabel();
                    }
                }
            } catch (e) {
                console.error('Failed to load motion status:', e);
            }
        }

        // Toggle motion detection
        async function toggleMotion() {
            if (!currentCamera) return;
            
            const enabled = document.getElementById('motion-enabled').checked;
            const action = enabled ? 'enable' : 'disable';
            
            try {
                const resp = await fetch(`/api/motion.php?action=${action}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ camera_id: currentCamera.camera_id })
                });
                const data = await resp.json();
                
                if (data.success) {
                    showToast(data.message, true);
                    // Update camera in list
                    const cam = cameras.find(c => c.camera_id === currentCamera.camera_id);
                    if (cam) cam.motion_detection = enabled;
                    renderCameraList();
                    loadMotionStatus(currentCamera.camera_id);
                    loadStats();
                } else {
                    showToast(data.error || 'Failed to toggle motion detection', false);
                    document.getElementById('motion-enabled').checked = !enabled;
                }
            } catch (e) {
                showToast('Failed to toggle motion detection', false);
                document.getElementById('motion-enabled').checked = !enabled;
            }
        }

        // Save configuration
        async function saveConfig() {
            if (!currentCamera) return;
            
            const config = {
                camera_id: currentCamera.camera_id,
                sensitivity: parseInt(document.getElementById('sensitivity').value),
                cooldown: parseInt(document.getElementById('cooldown').value),
                record_duration: parseInt(document.getElementById('record-duration').value),
                snapshot_enabled: document.getElementById('snapshot-enabled').checked,
                recording_enabled: document.getElementById('recording-enabled').checked,
                notification_enabled: document.getElementById('notification-enabled').checked
            };
            
            try {
                const resp = await fetch('/api/motion.php?action=configure', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(config)
                });
                const data = await resp.json();
                
                if (data.success) {
                    showToast('Configuration saved!', true);
                } else {
                    showToast(data.error || 'Failed to save', false);
                }
            } catch (e) {
                showToast('Failed to save configuration', false);
            }
        }

        // Update sensitivity label
        function updateSensitivityLabel() {
            const value = document.getElementById('sensitivity').value;
            document.getElementById('sensitivity-value').textContent = value;
        }

        // Load zones
        async function loadZones(cameraId) {
            try {
                const resp = await fetch(`/api/motion.php?action=zones&camera_id=${cameraId}`);
                const data = await resp.json();
                
                if (data.success) {
                    zones = data.zones;
                    renderZones();
                }
            } catch (e) {
                console.error('Failed to load zones:', e);
            }
        }

        // Render zones
        function renderZones() {
            const overlay = document.getElementById('zone-overlay');
            const list = document.getElementById('zone-list');
            
            // Clear overlay
            overlay.innerHTML = '';
            
            if (zones.length === 0) {
                list.innerHTML = '<p class="text-sm text-gray-500">No zones defined. The entire frame will be monitored.</p>';
                return;
            }
            
            // Render zone rectangles
            zones.forEach((zone, index) => {
                if (zone.coordinates && zone.coordinates.length >= 2) {
                    const rect = document.createElement('div');
                    rect.className = 'zone-rect';
                    rect.style.left = zone.coordinates[0].x + '%';
                    rect.style.top = zone.coordinates[0].y + '%';
                    rect.style.width = (zone.coordinates[1].x - zone.coordinates[0].x) + '%';
                    rect.style.height = (zone.coordinates[1].y - zone.coordinates[0].y) + '%';
                    rect.innerHTML = `<span class="absolute top-1 left-1 text-xs bg-black/50 px-1 rounded">${zone.zone_name}</span>`;
                    overlay.appendChild(rect);
                }
            });
            
            // Render zone list
            list.innerHTML = zones.map((zone, index) => `
                <div class="flex items-center justify-between p-2 bg-[#1a1a24] rounded-lg">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" ${zone.enabled ? 'checked' : ''} 
                               onchange="toggleZone(${index})" class="w-4 h-4">
                        <span>${zone.zone_name}</span>
                    </div>
                    <button onclick="removeZone(${index})" class="text-red-400 hover:text-red-300">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `).join('');
        }

        // Add zone
        function addZone() {
            const name = prompt('Zone name:', `Zone ${zones.length + 1}`);
            if (!name) return;
            
            zones.push({
                zone_name: name,
                coordinates: [
                    { x: 10 + zones.length * 10, y: 10 },
                    { x: 40 + zones.length * 10, y: 40 }
                ],
                enabled: true,
                sensitivity: 25
            });
            
            renderZones();
            saveZones();
        }

        // Remove zone
        function removeZone(index) {
            zones.splice(index, 1);
            renderZones();
            saveZones();
        }

        // Toggle zone
        function toggleZone(index) {
            zones[index].enabled = !zones[index].enabled;
            saveZones();
        }

        // Save zones
        async function saveZones() {
            if (!currentCamera) return;
            
            try {
                await fetch('/api/motion.php?action=set_zones', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        camera_id: currentCamera.camera_id,
                        zones: zones
                    })
                });
            } catch (e) {
                console.error('Failed to save zones:', e);
            }
        }

        // Load preview image
        async function loadPreviewImage(cameraId) {
            const img = document.getElementById('zone-preview');
            img.src = `/api/cameras.php?action=snapshot&camera_id=${cameraId}&t=${Date.now()}`;
            img.onerror = () => {
                img.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 360"><rect fill="%23111" width="640" height="360"/><text x="320" y="180" fill="%23666" text-anchor="middle">No Preview</text></svg>';
            };
        }

        // Test detection
        async function testDetection() {
            if (!currentCamera) return;
            
            document.getElementById('test-modal').classList.remove('hidden');
            document.getElementById('test-result').innerHTML = `
                <i class="fas fa-spinner fa-spin text-4xl mb-4"></i>
                <p>Capturing frames and analyzing...</p>
            `;
            
            try {
                const resp = await fetch('/api/motion.php?action=test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ camera_id: currentCamera.camera_id })
                });
                const data = await resp.json();
                
                if (data.success) {
                    const icon = data.motion_detected ? 'fa-running text-green-500' : 'fa-check text-gray-500';
                    const message = data.motion_detected 
                        ? `Motion detected! ${data.change_percent}% change`
                        : `No motion detected. ${data.change_percent}% change`;
                    
                    document.getElementById('test-result').innerHTML = `
                        <i class="fas ${icon} text-5xl mb-4"></i>
                        <p class="text-lg">${message}</p>
                        <p class="text-sm text-gray-500 mt-2">Two frames compared 1 second apart</p>
                    `;
                } else {
                    document.getElementById('test-result').innerHTML = `
                        <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                        <p>${data.error || 'Test failed'}</p>
                    `;
                }
            } catch (e) {
                document.getElementById('test-result').innerHTML = `
                    <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                    <p>Test failed: ${e.message}</p>
                `;
            }
        }

        function closeTestModal() {
            document.getElementById('test-modal').classList.add('hidden');
        }

        // Load stats
        async function loadStats() {
            // Count active cameras
            const activeCameras = cameras.filter(c => c.motion_detection).length;
            document.getElementById('stat-cameras').textContent = activeCameras;
            
            // Load event counts
            try {
                const today = new Date().toISOString().split('T')[0];
                const weekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                
                const respToday = await fetch(`/api/motion.php?action=events&date_from=${today}T00:00:00&limit=1000`);
                const dataToday = await respToday.json();
                document.getElementById('stat-events-today').textContent = dataToday.total || 0;
                
                const respWeek = await fetch(`/api/motion.php?action=events&date_from=${weekAgo}T00:00:00&limit=1000`);
                const dataWeek = await respWeek.json();
                document.getElementById('stat-events-week').textContent = dataWeek.total || 0;
                
                if (dataToday.events && dataToday.events.length > 0) {
                    const lastEvent = new Date(dataToday.events[0].detected_at);
                    const minutesAgo = Math.floor((Date.now() - lastEvent) / 60000);
                    if (minutesAgo < 60) {
                        document.getElementById('stat-last-event').textContent = `${minutesAgo}m`;
                    } else {
                        document.getElementById('stat-last-event').textContent = `${Math.floor(minutesAgo/60)}h`;
                    }
                }
            } catch (e) {
                console.error('Failed to load stats:', e);
            }
        }

        // Populate camera filter
        function populateCameraFilter() {
            const select = document.getElementById('event-filter-camera');
            select.innerHTML = '<option value="">All Cameras</option>' +
                cameras.map(c => `<option value="${c.camera_id}">${c.camera_name}</option>`).join('');
        }

        // Load events
        async function loadEvents() {
            const cameraId = document.getElementById('event-filter-camera').value;
            let url = `/api/motion.php?action=events&limit=20&offset=${eventsPage * 20}`;
            if (cameraId) url += `&camera_id=${cameraId}`;
            
            try {
                const resp = await fetch(url);
                const data = await resp.json();
                
                if (data.success) {
                    events = data.events;
                    renderEvents();
                    renderPagination(data.total);
                }
            } catch (e) {
                console.error('Failed to load events:', e);
            }
        }

        // Render events
        function renderEvents() {
            const container = document.getElementById('events-list');
            
            if (events.length === 0) {
                container.innerHTML = '<p class="text-center text-gray-500 py-4">No motion events recorded</p>';
                return;
            }
            
            container.innerHTML = events.map(evt => `
                <div class="event-item py-3">
                    <div class="flex items-start gap-4">
                        <div class="w-24 h-16 bg-[#1a1a24] rounded overflow-hidden flex-shrink-0">
                            ${evt.thumbnail_url 
                                ? `<img src="${evt.thumbnail_url}" class="w-full h-full object-cover">`
                                : '<div class="w-full h-full flex items-center justify-center text-gray-600"><i class="fas fa-image"></i></div>'
                            }
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">${evt.camera_name || 'Camera'}</span>
                                <span class="text-xs px-2 py-0.5 bg-red-500/20 text-red-400 rounded">Motion</span>
                            </div>
                            <p class="text-sm text-gray-500">${formatDateTime(evt.detected_at)}</p>
                            <p class="text-xs text-gray-600 mt-1">
                                Confidence: ${evt.confidence}%
                                ${evt.zone_name ? ` • Zone: ${evt.zone_name}` : ''}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            ${evt.snapshot_url ? `<a href="${evt.snapshot_url}" target="_blank" class="btn-secondary px-2 py-1 rounded text-sm"><i class="fas fa-image"></i></a>` : ''}
                            ${evt.recording_id ? `<a href="/dashboard/recordings.php?id=${evt.recording_id}" class="btn-secondary px-2 py-1 rounded text-sm"><i class="fas fa-play"></i></a>` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Render pagination
        function renderPagination(total) {
            const totalPages = Math.ceil(total / 20);
            const container = document.getElementById('events-pagination');
            
            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }
            
            let html = '';
            for (let i = 0; i < totalPages && i < 10; i++) {
                html += `<button onclick="goToPage(${i})" class="${i === eventsPage ? 'btn-primary' : 'btn-secondary'} px-3 py-1 rounded">${i + 1}</button>`;
            }
            container.innerHTML = html;
        }

        function goToPage(page) {
            eventsPage = page;
            loadEvents();
        }

        // Clear old events
        async function clearOldEvents() {
            const days = prompt('Delete events older than how many days?', '30');
            if (!days) return;
            
            try {
                const resp = await fetch('/api/motion.php?action=clear_events', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ days_old: parseInt(days) })
                });
                const data = await resp.json();
                
                if (data.success) {
                    showToast(data.message, true);
                    loadEvents();
                    loadStats();
                } else {
                    showToast(data.error || 'Failed to clear events', false);
                }
            } catch (e) {
                showToast('Failed to clear events', false);
            }
        }

        // Helpers
        function formatDateTime(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleString('en-US', {
                month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true
            });
        }

        function showToast(message, success) {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 px-4 py-2 rounded-lg text-white ${success ? 'bg-green-600' : 'bg-red-600'} z-50`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    </script>
</body>
</html>
