<?php
/**
 * Recordings Playback Interface - Task 6A.10
 * Video playback with timeline, motion markers, speed control
 */

define('TRUEVAULT_INIT', true);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$pageTitle = 'Recordings';
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
            --text-primary: #ffffff;
            --text-secondary: #888;
            --border: #2a2a3a;
        }
        body { background: var(--bg-primary); color: var(--text-primary); font-family: system-ui, sans-serif; }
        .card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; }
        .btn-primary { background: linear-gradient(135deg, var(--accent), var(--accent-green)); color: #000; font-weight: 600; }
        .btn-secondary { background: var(--bg-secondary); border: 1px solid var(--border); }
        
        /* Video Player */
        .video-container { position: relative; background: #000; border-radius: 8px; overflow: hidden; }
        .video-container video { width: 100%; max-height: 60vh; }
        
        /* Timeline */
        .timeline-container { background: var(--bg-secondary); padding: 15px; border-radius: 8px; margin-top: 15px; }
        .timeline-track { position: relative; height: 40px; background: #1a1a24; border-radius: 4px; cursor: pointer; }
        .timeline-progress { position: absolute; top: 0; left: 0; height: 100%; background: linear-gradient(90deg, var(--accent), var(--accent-green)); border-radius: 4px; }
        .timeline-handle { position: absolute; top: 50%; transform: translate(-50%, -50%); width: 14px; height: 14px; background: #fff; border-radius: 50%; cursor: grab; box-shadow: 0 2px 8px rgba(0,0,0,0.5); }
        .timeline-marker { position: absolute; top: 0; width: 4px; height: 100%; background: #ff4444; border-radius: 2px; cursor: pointer; }
        .timeline-marker:hover { background: #ff6666; }
        .timeline-marker::after { content: attr(data-label); position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); background: #ff4444; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 10px; white-space: nowrap; opacity: 0; transition: opacity 0.2s; }
        .timeline-marker:hover::after { opacity: 1; }
        
        /* Recording List */
        .recording-item { display: flex; gap: 15px; padding: 15px; border-radius: 8px; cursor: pointer; transition: background 0.2s; }
        .recording-item:hover { background: var(--bg-secondary); }
        .recording-item.active { background: var(--bg-secondary); border-left: 3px solid var(--accent); }
        .recording-thumb { width: 160px; height: 90px; background: #222; border-radius: 6px; overflow: hidden; flex-shrink: 0; }
        .recording-thumb img { width: 100%; height: 100%; object-fit: cover; }
        
        /* Speed Control */
        .speed-btn { padding: 6px 12px; border-radius: 6px; transition: all 0.2s; }
        .speed-btn.active { background: var(--accent); color: #000; }
        
        /* Storage Bar */
        .storage-bar { height: 8px; background: var(--bg-secondary); border-radius: 4px; overflow: hidden; }
        .storage-fill { height: 100%; background: linear-gradient(90deg, var(--accent-green), var(--accent)); transition: width 0.3s; }
        .storage-fill.warning { background: linear-gradient(90deg, #ffaa00, #ff6600); }
        .storage-fill.critical { background: linear-gradient(90deg, #ff4444, #ff0000); }
        
        /* Calendar */
        .calendar-day { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 6px; cursor: pointer; }
        .calendar-day:hover { background: var(--bg-secondary); }
        .calendar-day.has-recordings { background: rgba(0, 212, 255, 0.2); }
        .calendar-day.selected { background: var(--accent); color: #000; }
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
                <h1 class="text-xl font-bold">📹 Recordings</h1>
            </div>
            <div class="flex items-center gap-3">
                <div id="storage-info" class="text-sm text-gray-400">
                    <i class="fas fa-database mr-1"></i>
                    <span id="storage-used">0</span> / <span id="storage-max">50</span> GB
                </div>
                <button onclick="showSettings()" class="btn-secondary px-4 py-2 rounded-lg">
                    <i class="fas fa-cog"></i>
                </button>
            </div>
        </div>
    </header>

    <main class="flex h-[calc(100vh-73px)]">
        <!-- Left Sidebar - Recording List -->
        <aside class="w-80 border-r border-[#2a2a3a] overflow-y-auto">
            <!-- Filters -->
            <div class="p-4 border-b border-[#2a2a3a]">
                <select id="camera-filter" onchange="filterRecordings()" class="w-full bg-[#1a1a24] border border-[#2a2a3a] rounded-lg px-3 py-2 mb-3">
                    <option value="">All Cameras</option>
                </select>
                <div class="flex gap-2">
                    <input type="date" id="date-filter" onchange="filterRecordings()" class="flex-1 bg-[#1a1a24] border border-[#2a2a3a] rounded-lg px-3 py-2 text-sm">
                    <button onclick="clearFilters()" class="btn-secondary px-3 py-2 rounded-lg text-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Recording List -->
            <div id="recordings-list" class="p-2">
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-video text-3xl mb-2"></i>
                    <p>Loading recordings...</p>
                </div>
            </div>
        </aside>

        <!-- Main Content - Video Player -->
        <div class="flex-1 p-6 overflow-y-auto">
            <!-- No Selection State -->
            <div id="no-selection" class="flex flex-col items-center justify-center h-full text-gray-500">
                <i class="fas fa-film text-6xl mb-4"></i>
                <h2 class="text-xl mb-2">Select a Recording</h2>
                <p>Choose a recording from the list to start playback</p>
            </div>

            <!-- Video Player -->
            <div id="player-container" class="hidden">
                <!-- Video Info -->
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 id="video-title" class="text-xl font-bold">Front Door Camera</h2>
                        <p id="video-date" class="text-gray-400 text-sm">January 21, 2026 - 8:45 AM</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="shareRecording()" class="btn-secondary px-4 py-2 rounded-lg">
                            <i class="fas fa-share-alt mr-2"></i>Share
                        </button>
                        <button onclick="downloadRecording()" class="btn-secondary px-4 py-2 rounded-lg">
                            <i class="fas fa-download mr-2"></i>Download
                        </button>
                        <button onclick="deleteRecording()" class="btn-secondary px-4 py-2 rounded-lg text-red-400 hover:text-red-300">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <!-- Video -->
                <div class="video-container">
                    <video id="video-player" controls>
                        <source src="" type="video/mp4">
                    </video>
                    
                    <!-- Video Overlay -->
                    <div id="video-overlay" class="absolute inset-0 flex items-center justify-center bg-black/50 hidden">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin text-4xl mb-2"></i>
                            <p>Loading...</p>
                        </div>
                    </div>
                </div>

                <!-- Custom Controls -->
                <div class="timeline-container">
                    <!-- Playback Controls -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <button onclick="skipBack()" class="btn-secondary w-10 h-10 rounded-lg">
                                <i class="fas fa-backward"></i>
                            </button>
                            <button onclick="togglePlay()" id="play-btn" class="btn-primary w-12 h-12 rounded-full">
                                <i class="fas fa-play"></i>
                            </button>
                            <button onclick="skipForward()" class="btn-secondary w-10 h-10 rounded-lg">
                                <i class="fas fa-forward"></i>
                            </button>
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <span id="time-current" class="text-sm font-mono">0:00</span>
                            <span class="text-gray-500">/</span>
                            <span id="time-duration" class="text-sm font-mono">0:00</span>
                        </div>
                        
                        <!-- Speed Control -->
                        <div class="flex items-center gap-1 bg-[#1a1a24] rounded-lg p-1">
                            <button onclick="setSpeed(0.5)" class="speed-btn text-sm" data-speed="0.5">0.5x</button>
                            <button onclick="setSpeed(1)" class="speed-btn text-sm active" data-speed="1">1x</button>
                            <button onclick="setSpeed(2)" class="speed-btn text-sm" data-speed="2">2x</button>
                            <button onclick="setSpeed(4)" class="speed-btn text-sm" data-speed="4">4x</button>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <button onclick="toggleMute()" id="mute-btn" class="btn-secondary w-10 h-10 rounded-lg">
                                <i class="fas fa-volume-up"></i>
                            </button>
                            <button onclick="toggleFullscreen()" class="btn-secondary w-10 h-10 rounded-lg">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Timeline Track -->
                    <div class="timeline-track" id="timeline" onclick="seekTo(event)">
                        <div class="timeline-progress" id="timeline-progress" style="width: 0%"></div>
                        <div class="timeline-handle" id="timeline-handle" style="left: 0%"></div>
                        <!-- Motion markers will be inserted here -->
                    </div>
                    
                    <!-- Time Labels -->
                    <div class="flex justify-between mt-2 text-xs text-gray-500">
                        <span id="timeline-start">0:00</span>
                        <span id="timeline-end">0:00</span>
                    </div>
                </div>

                <!-- Motion Events -->
                <div class="card p-4 mt-4">
                    <h3 class="font-semibold mb-3">
                        <i class="fas fa-running mr-2 text-[#00d4ff]"></i>
                        Motion Events
                    </h3>
                    <div id="motion-events" class="space-y-2">
                        <p class="text-gray-500 text-sm">No motion events in this recording</p>
                    </div>
                </div>

                <!-- Recording Info -->
                <div class="card p-4 mt-4">
                    <h3 class="font-semibold mb-3">
                        <i class="fas fa-info-circle mr-2 text-[#00d4ff]"></i>
                        Recording Details
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Duration</p>
                            <p id="info-duration" class="font-semibold">--:--</p>
                        </div>
                        <div>
                            <p class="text-gray-500">File Size</p>
                            <p id="info-size" class="font-semibold">-- MB</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Mode</p>
                            <p id="info-mode" class="font-semibold">Manual</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Camera</p>
                            <p id="info-camera" class="font-semibold">--</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Share Modal -->
    <div id="share-modal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 hidden">
        <div class="card p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold">Share Recording</h3>
                <button onclick="closeShareModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mb-4">
                <label class="text-sm text-gray-400 mb-2 block">Link expires in:</label>
                <select id="share-expiry" class="w-full bg-[#1a1a24] border border-[#2a2a3a] rounded-lg px-3 py-2">
                    <option value="1">1 hour</option>
                    <option value="24" selected>24 hours</option>
                    <option value="72">3 days</option>
                    <option value="168">1 week</option>
                </select>
            </div>
            <div id="share-link-container" class="hidden mb-4">
                <label class="text-sm text-gray-400 mb-2 block">Share link:</label>
                <div class="flex gap-2">
                    <input type="text" id="share-link" readonly class="flex-1 bg-[#1a1a24] border border-[#2a2a3a] rounded-lg px-3 py-2 text-sm">
                    <button onclick="copyShareLink()" class="btn-primary px-4 py-2 rounded-lg">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
            <button onclick="generateShareLink()" id="generate-link-btn" class="btn-primary w-full py-3 rounded-lg">
                <i class="fas fa-link mr-2"></i>Generate Link
            </button>
        </div>
    </div>

    <!-- Settings Modal -->
    <div id="settings-modal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 hidden">
        <div class="card p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold">Recording Settings</h3>
                <button onclick="closeSettings()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Storage -->
            <div class="mb-6">
                <h4 class="font-semibold mb-2">Storage Usage</h4>
                <div class="storage-bar mb-2">
                    <div id="storage-bar-fill" class="storage-fill" style="width: 0%"></div>
                </div>
                <p class="text-sm text-gray-400">
                    <span id="storage-used-detail">0</span> GB of <span id="storage-max-detail">50</span> GB used
                </p>
            </div>
            
            <!-- Auto Cleanup -->
            <div class="mb-6">
                <h4 class="font-semibold mb-2">Auto Cleanup</h4>
                <p class="text-sm text-gray-400 mb-2">Automatically delete recordings older than:</p>
                <select id="auto-cleanup-days" class="w-full bg-[#1a1a24] border border-[#2a2a3a] rounded-lg px-3 py-2">
                    <option value="7">7 days</option>
                    <option value="14">14 days</option>
                    <option value="30" selected>30 days</option>
                    <option value="60">60 days</option>
                    <option value="90">90 days</option>
                    <option value="0">Never (manual only)</option>
                </select>
            </div>
            
            <!-- Manual Cleanup -->
            <div class="mb-6">
                <h4 class="font-semibold mb-2">Manual Cleanup</h4>
                <button onclick="cleanupNow()" class="btn-secondary w-full py-2 rounded-lg text-red-400">
                    <i class="fas fa-trash mr-2"></i>Delete Old Recordings Now
                </button>
            </div>
            
            <button onclick="closeSettings()" class="btn-primary w-full py-3 rounded-lg">Done</button>
        </div>
    </div>

    <script>
        // State
        let recordings = [];
        let cameras = [];
        let currentRecording = null;
        let motionEvents = [];
        const video = document.getElementById('video-player');

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadCameras();
            loadRecordings();
            loadStorageStats();
            setupVideoEvents();
        });

        // Load cameras for filter
        async function loadCameras() {
            try {
                const resp = await fetch('/api/cameras.php?action=list');
                const data = await resp.json();
                if (data.success) {
                    cameras = data.cameras;
                    const select = document.getElementById('camera-filter');
                    cameras.forEach(cam => {
                        const opt = document.createElement('option');
                        opt.value = cam.camera_id;
                        opt.textContent = cam.camera_name;
                        select.appendChild(opt);
                    });
                }
            } catch (e) {
                console.error('Failed to load cameras:', e);
            }
        }

        // Load recordings
        async function loadRecordings() {
            const cameraId = document.getElementById('camera-filter').value;
            const date = document.getElementById('date-filter').value;
            
            let url = '/api/recordings.php?action=list&limit=100';
            if (cameraId) url += `&camera_id=${cameraId}`;
            if (date) {
                url += `&date_from=${date}T00:00:00&date_to=${date}T23:59:59`;
            }
            
            try {
                const resp = await fetch(url);
                const data = await resp.json();
                if (data.success) {
                    recordings = data.recordings;
                    renderRecordingsList();
                }
            } catch (e) {
                console.error('Failed to load recordings:', e);
            }
        }

        // Render recordings list
        function renderRecordingsList() {
            const container = document.getElementById('recordings-list');
            
            if (recordings.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-video-slash text-3xl mb-2"></i>
                        <p>No recordings found</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = recordings.map(rec => `
                <div class="recording-item ${currentRecording?.id === rec.id ? 'active' : ''}" 
                     onclick="playRecording(${rec.id})">
                    <div class="recording-thumb">
                        ${rec.thumbnail_url 
                            ? `<img src="${rec.thumbnail_url}" alt="Thumbnail" onerror="this.parentElement.innerHTML='<div class=\\'flex items-center justify-center h-full text-gray-600\\'><i class=\\'fas fa-video text-2xl\\'></i></div>'">`
                            : `<div class="flex items-center justify-center h-full text-gray-600"><i class="fas fa-video text-2xl"></i></div>`
                        }
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold truncate">${rec.camera_name || 'Unknown Camera'}</h4>
                        <p class="text-sm text-gray-400">${formatDateTime(rec.start_time)}</p>
                        <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                            <span><i class="fas fa-clock mr-1"></i>${rec.duration_formatted}</span>
                            <span><i class="fas fa-file mr-1"></i>${rec.file_size_mb} MB</span>
                            ${rec.is_recording ? '<span class="text-red-400"><i class="fas fa-circle mr-1 animate-pulse"></i>Recording</span>' : ''}
                        </div>
                        <span class="inline-block mt-2 px-2 py-0.5 bg-[#1a1a24] rounded text-xs capitalize">${rec.recording_mode}</span>
                    </div>
                </div>
            `).join('');
        }

        // Play recording
        async function playRecording(recordingId) {
            const rec = recordings.find(r => r.id === recordingId);
            if (!rec) return;
            
            currentRecording = rec;
            
            // Update UI
            document.getElementById('no-selection').classList.add('hidden');
            document.getElementById('player-container').classList.remove('hidden');
            document.getElementById('video-overlay').classList.remove('hidden');
            
            // Update info
            document.getElementById('video-title').textContent = rec.camera_name || 'Recording';
            document.getElementById('video-date').textContent = formatDateTime(rec.start_time);
            document.getElementById('info-duration').textContent = rec.duration_formatted;
            document.getElementById('info-size').textContent = rec.file_size_mb + ' MB';
            document.getElementById('info-mode').textContent = rec.recording_mode;
            document.getElementById('info-camera').textContent = rec.camera_name || 'Unknown';
            document.getElementById('timeline-end').textContent = rec.duration_formatted;
            
            // Load video
            video.src = rec.video_url;
            video.load();
            
            // Update list highlighting
            renderRecordingsList();
            
            // Load motion events
            loadMotionEvents(rec.camera_id, rec.start_time, rec.end_time);
        }

        // Load motion events for timeline
        async function loadMotionEvents(cameraId, startTime, endTime) {
            try {
                const resp = await fetch(`/api/cameras.php?action=motion_events&camera_id=${cameraId}`);
                const data = await resp.json();
                if (data.success) {
                    // Filter events within recording time range
                    motionEvents = data.events.filter(e => {
                        const eventTime = new Date(e.detected_at);
                        return eventTime >= new Date(startTime) && (!endTime || eventTime <= new Date(endTime));
                    });
                    renderMotionEvents();
                    renderTimelineMarkers();
                }
            } catch (e) {
                console.error('Failed to load motion events:', e);
            }
        }

        // Render motion events list
        function renderMotionEvents() {
            const container = document.getElementById('motion-events');
            
            if (motionEvents.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-sm">No motion events in this recording</p>';
                return;
            }
            
            container.innerHTML = motionEvents.map(evt => {
                const time = calculateEventOffset(evt.detected_at);
                return `
                    <div class="flex items-center justify-between p-2 bg-[#1a1a24] rounded-lg cursor-pointer hover:bg-[#252530]"
                         onclick="jumpToTime(${time})">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-red-500/20 rounded-full flex items-center justify-center">
                                <i class="fas fa-running text-red-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium">Motion Detected</p>
                                <p class="text-xs text-gray-500">${formatTime(evt.detected_at)}</p>
                            </div>
                        </div>
                        <span class="text-sm text-[#00d4ff]">${formatSeconds(time)}</span>
                    </div>
                `;
            }).join('');
        }

        // Render timeline markers
        function renderTimelineMarkers() {
            const timeline = document.getElementById('timeline');
            // Remove existing markers
            timeline.querySelectorAll('.timeline-marker').forEach(m => m.remove());
            
            if (!currentRecording || !currentRecording.duration) return;
            
            motionEvents.forEach(evt => {
                const time = calculateEventOffset(evt.detected_at);
                const percent = (time / currentRecording.duration) * 100;
                
                const marker = document.createElement('div');
                marker.className = 'timeline-marker';
                marker.style.left = `${percent}%`;
                marker.dataset.label = `Motion @ ${formatSeconds(time)}`;
                marker.onclick = (e) => {
                    e.stopPropagation();
                    jumpToTime(time);
                };
                timeline.appendChild(marker);
            });
        }

        // Calculate event offset from recording start
        function calculateEventOffset(eventTime) {
            if (!currentRecording) return 0;
            const start = new Date(currentRecording.start_time);
            const event = new Date(eventTime);
            return Math.max(0, (event - start) / 1000);
        }

        // Setup video events
        function setupVideoEvents() {
            video.addEventListener('loadedmetadata', () => {
                document.getElementById('video-overlay').classList.add('hidden');
                document.getElementById('time-duration').textContent = formatSeconds(video.duration);
            });
            
            video.addEventListener('timeupdate', () => {
                const progress = (video.currentTime / video.duration) * 100;
                document.getElementById('timeline-progress').style.width = `${progress}%`;
                document.getElementById('timeline-handle').style.left = `${progress}%`;
                document.getElementById('time-current').textContent = formatSeconds(video.currentTime);
            });
            
            video.addEventListener('play', () => {
                document.getElementById('play-btn').innerHTML = '<i class="fas fa-pause"></i>';
            });
            
            video.addEventListener('pause', () => {
                document.getElementById('play-btn').innerHTML = '<i class="fas fa-play"></i>';
            });
            
            video.addEventListener('ended', () => {
                document.getElementById('play-btn').innerHTML = '<i class="fas fa-play"></i>';
            });
        }

        // Playback controls
        function togglePlay() {
            if (video.paused) {
                video.play();
            } else {
                video.pause();
            }
        }

        function skipBack() {
            video.currentTime = Math.max(0, video.currentTime - 10);
        }

        function skipForward() {
            video.currentTime = Math.min(video.duration, video.currentTime + 10);
        }

        function setSpeed(speed) {
            video.playbackRate = speed;
            document.querySelectorAll('.speed-btn').forEach(btn => {
                btn.classList.toggle('active', parseFloat(btn.dataset.speed) === speed);
            });
        }

        function toggleMute() {
            video.muted = !video.muted;
            document.getElementById('mute-btn').innerHTML = 
                video.muted ? '<i class="fas fa-volume-mute"></i>' : '<i class="fas fa-volume-up"></i>';
        }

        function toggleFullscreen() {
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else {
                document.querySelector('.video-container').requestFullscreen();
            }
        }

        function seekTo(event) {
            const timeline = document.getElementById('timeline');
            const rect = timeline.getBoundingClientRect();
            const percent = (event.clientX - rect.left) / rect.width;
            video.currentTime = percent * video.duration;
        }

        function jumpToTime(seconds) {
            video.currentTime = seconds;
            if (video.paused) video.play();
        }

        // Share functions
        function shareRecording() {
            document.getElementById('share-modal').classList.remove('hidden');
            document.getElementById('share-link-container').classList.add('hidden');
            document.getElementById('generate-link-btn').classList.remove('hidden');
        }

        function closeShareModal() {
            document.getElementById('share-modal').classList.add('hidden');
        }

        async function generateShareLink() {
            if (!currentRecording) return;
            
            const expiry = document.getElementById('share-expiry').value;
            
            try {
                const resp = await fetch('/api/recordings.php?action=share', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        recording_id: currentRecording.id,
                        expires_in: parseInt(expiry)
                    })
                });
                const data = await resp.json();
                
                if (data.success) {
                    document.getElementById('share-link').value = data.share_url;
                    document.getElementById('share-link-container').classList.remove('hidden');
                    document.getElementById('generate-link-btn').classList.add('hidden');
                } else {
                    alert('Failed to generate link: ' + data.error);
                }
            } catch (e) {
                alert('Failed to generate share link');
            }
        }

        function copyShareLink() {
            const input = document.getElementById('share-link');
            input.select();
            document.execCommand('copy');
            alert('Link copied to clipboard!');
        }

        // Download
        function downloadRecording() {
            if (!currentRecording) return;
            window.location.href = `/api/recordings.php?action=download&recording_id=${currentRecording.id}`;
        }

        // Delete
        async function deleteRecording() {
            if (!currentRecording) return;
            if (!confirm('Are you sure you want to delete this recording? This cannot be undone.')) return;
            
            try {
                const resp = await fetch('/api/recordings.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ recording_id: currentRecording.id })
                });
                const data = await resp.json();
                
                if (data.success) {
                    currentRecording = null;
                    document.getElementById('player-container').classList.add('hidden');
                    document.getElementById('no-selection').classList.remove('hidden');
                    loadRecordings();
                    loadStorageStats();
                } else {
                    alert('Failed to delete: ' + data.error);
                }
            } catch (e) {
                alert('Failed to delete recording');
            }
        }

        // Settings
        function showSettings() {
            document.getElementById('settings-modal').classList.remove('hidden');
        }

        function closeSettings() {
            document.getElementById('settings-modal').classList.add('hidden');
        }

        async function loadStorageStats() {
            try {
                const resp = await fetch('/api/recordings.php?action=storage_stats');
                const data = await resp.json();
                
                if (data.success) {
                    const storage = data.storage;
                    document.getElementById('storage-used').textContent = storage.used_gb;
                    document.getElementById('storage-max').textContent = storage.max_gb;
                    document.getElementById('storage-used-detail').textContent = storage.used_gb;
                    document.getElementById('storage-max-detail').textContent = storage.max_gb;
                    
                    const fill = document.getElementById('storage-bar-fill');
                    fill.style.width = `${storage.percentage_used}%`;
                    fill.classList.remove('warning', 'critical');
                    if (storage.percentage_used >= 90) fill.classList.add('critical');
                    else if (storage.percentage_used >= 70) fill.classList.add('warning');
                }
            } catch (e) {
                console.error('Failed to load storage stats:', e);
            }
        }

        async function cleanupNow() {
            const days = document.getElementById('auto-cleanup-days').value;
            if (!confirm(`Delete all recordings older than ${days} days?`)) return;
            
            try {
                const resp = await fetch('/api/recordings.php?action=cleanup', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ days_old: parseInt(days) })
                });
                const data = await resp.json();
                
                if (data.success) {
                    alert(`Deleted ${data.deleted_count} recordings, freed ${data.freed_gb} GB`);
                    loadRecordings();
                    loadStorageStats();
                }
            } catch (e) {
                alert('Cleanup failed');
            }
        }

        // Filter recordings
        function filterRecordings() {
            loadRecordings();
        }

        function clearFilters() {
            document.getElementById('camera-filter').value = '';
            document.getElementById('date-filter').value = '';
            loadRecordings();
        }

        // Helpers
        function formatDateTime(dateStr) {
            if (!dateStr) return '--';
            const date = new Date(dateStr);
            return date.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }

        function formatTime(dateStr) {
            if (!dateStr) return '--';
            const date = new Date(dateStr);
            return date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
        }

        function formatSeconds(secs) {
            if (!secs || isNaN(secs)) return '0:00';
            const mins = Math.floor(secs / 60);
            const s = Math.floor(secs % 60);
            return `${mins}:${s.toString().padStart(2, '0')}`;
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            
            switch (e.key) {
                case ' ':
                    e.preventDefault();
                    togglePlay();
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    skipBack();
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    skipForward();
                    break;
                case 'f':
                    toggleFullscreen();
                    break;
                case 'm':
                    toggleMute();
                    break;
            }
        });
    </script>
</body>
</html>
