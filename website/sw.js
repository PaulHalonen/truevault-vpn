/**
 * TrueVault VPN Service Worker - Task 6A.14
 * Push notifications for motion detection and camera alerts
 */

const CACHE_NAME = 'truevault-v1';
const OFFLINE_URL = '/offline.html';

// Assets to cache for offline use
const PRECACHE_ASSETS = [
    '/',
    '/mobile/cameras.php',
    '/dashboard/cameras.php',
    '/dashboard/recordings.php',
    '/offline.html',
    '/icons/icon-192.png',
    '/icons/icon-512.png'
];

// Install event - cache assets
self.addEventListener('install', (event) => {
    console.log('[SW] Installing service worker...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Caching app shell');
                return cache.addAll(PRECACHE_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating service worker...');
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') return;
    
    // Skip API calls and streams
    if (event.request.url.includes('/api/') || 
        event.request.url.includes('.m3u8') ||
        event.request.url.includes('.ts')) {
        return;
    }
    
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                if (response) {
                    return response;
                }
                
                return fetch(event.request)
                    .then((response) => {
                        // Don't cache non-successful responses
                        if (!response || response.status !== 200) {
                            return response;
                        }
                        
                        // Clone the response
                        const responseToCache = response.clone();
                        
                        caches.open(CACHE_NAME)
                            .then((cache) => {
                                cache.put(event.request, responseToCache);
                            });
                        
                        return response;
                    })
                    .catch(() => {
                        // Return offline page for navigation requests
                        if (event.request.mode === 'navigate') {
                            return caches.match(OFFLINE_URL);
                        }
                    });
            })
    );
});

// Push event - handle incoming push notifications
self.addEventListener('push', (event) => {
    console.log('[SW] Push received');
    
    let data = {
        title: 'TrueVault Alert',
        body: 'New notification',
        icon: '/icons/icon-192.png',
        badge: '/icons/badge-72.png',
        tag: 'truevault-notification',
        data: {}
    };
    
    if (event.data) {
        try {
            const payload = event.data.json();
            data = { ...data, ...payload };
        } catch (e) {
            data.body = event.data.text();
        }
    }
    
    const options = {
        body: data.body,
        icon: data.icon || '/icons/icon-192.png',
        badge: data.badge || '/icons/badge-72.png',
        tag: data.tag || 'truevault-notification',
        data: data.data || {},
        vibrate: [200, 100, 200],
        requireInteraction: data.type === 'motion', // Keep motion alerts visible
        actions: getNotificationActions(data.type)
    };
    
    // Add image for motion detection
    if (data.image) {
        options.image = data.image;
    }
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Get notification actions based on type
function getNotificationActions(type) {
    switch (type) {
        case 'motion':
            return [
                { action: 'view', title: '👁️ View Camera', icon: '/icons/view.png' },
                { action: 'dismiss', title: '❌ Dismiss', icon: '/icons/dismiss.png' }
            ];
        case 'recording':
            return [
                { action: 'play', title: '▶️ Play', icon: '/icons/play.png' },
                { action: 'download', title: '⬇️ Download', icon: '/icons/download.png' }
            ];
        case 'offline':
            return [
                { action: 'check', title: '🔄 Check Status', icon: '/icons/refresh.png' }
            ];
        default:
            return [];
    }
}

// Notification click event
self.addEventListener('notificationclick', (event) => {
    console.log('[SW] Notification clicked:', event.action);
    
    event.notification.close();
    
    const data = event.notification.data || {};
    let url = '/mobile/cameras.php';
    
    // Handle different actions
    switch (event.action) {
        case 'view':
            if (data.camera_id) {
                url = `/mobile/cameras.php?camera=${data.camera_id}`;
            }
            break;
        case 'play':
            if (data.recording_id) {
                url = `/dashboard/recordings.php?recording=${data.recording_id}`;
            }
            break;
        case 'download':
            if (data.recording_id) {
                url = `/api/recordings.php?action=download&recording_id=${data.recording_id}`;
            }
            break;
        case 'check':
            url = '/dashboard/cameras.php';
            break;
        case 'dismiss':
            // Just close the notification
            return;
        default:
            // Default click - open camera view
            if (data.camera_id) {
                url = `/mobile/cameras.php?camera=${data.camera_id}`;
            } else if (data.recording_id) {
                url = `/dashboard/recordings.php?recording=${data.recording_id}`;
            }
    }
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((windowClients) => {
                // Check if already open
                for (const client of windowClients) {
                    if (client.url.includes(url) && 'focus' in client) {
                        return client.focus();
                    }
                }
                
                // Open new window
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
    );
});

// Notification close event
self.addEventListener('notificationclose', (event) => {
    console.log('[SW] Notification closed');
    
    // Track dismissal if needed
    const data = event.notification.data || {};
    if (data.event_id) {
        // Could send analytics or mark as dismissed
        fetch('/api/motion.php?action=acknowledge', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ event_id: data.event_id })
        }).catch(() => {});
    }
});

// Background sync for offline actions
self.addEventListener('sync', (event) => {
    console.log('[SW] Background sync:', event.tag);
    
    if (event.tag === 'sync-recordings') {
        event.waitUntil(syncRecordings());
    } else if (event.tag === 'sync-events') {
        event.waitUntil(syncEvents());
    }
});

// Sync pending recordings
async function syncRecordings() {
    try {
        const cache = await caches.open('truevault-pending');
        const requests = await cache.keys();
        
        for (const request of requests) {
            if (request.url.includes('/api/recordings')) {
                const response = await cache.match(request);
                const data = await response.json();
                
                await fetch(request.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                await cache.delete(request);
            }
        }
    } catch (e) {
        console.error('[SW] Sync recordings failed:', e);
    }
}

// Sync pending event acknowledgments
async function syncEvents() {
    try {
        const cache = await caches.open('truevault-pending');
        const requests = await cache.keys();
        
        for (const request of requests) {
            if (request.url.includes('/api/motion')) {
                const response = await cache.match(request);
                const data = await response.json();
                
                await fetch(request.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                await cache.delete(request);
            }
        }
    } catch (e) {
        console.error('[SW] Sync events failed:', e);
    }
}

// Periodic background sync (if supported)
self.addEventListener('periodicsync', (event) => {
    if (event.tag === 'check-cameras') {
        event.waitUntil(checkCameraStatus());
    }
});

// Check camera status in background
async function checkCameraStatus() {
    try {
        const response = await fetch('/api/cameras.php?action=list');
        const data = await response.json();
        
        if (data.success && data.cameras) {
            const offlineCameras = data.cameras.filter(c => !c.is_online);
            
            if (offlineCameras.length > 0) {
                self.registration.showNotification('Camera Offline', {
                    body: `${offlineCameras.length} camera(s) are offline`,
                    icon: '/icons/icon-192.png',
                    badge: '/icons/badge-72.png',
                    tag: 'camera-offline',
                    data: { type: 'offline' }
                });
            }
        }
    } catch (e) {
        console.error('[SW] Camera check failed:', e);
    }
}

// Message handler for client communication
self.addEventListener('message', (event) => {
    console.log('[SW] Message received:', event.data);
    
    if (event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    } else if (event.data.type === 'CACHE_URLS') {
        event.waitUntil(
            caches.open(CACHE_NAME).then((cache) => {
                return cache.addAll(event.data.urls);
            })
        );
    } else if (event.data.type === 'CLEAR_CACHE') {
        event.waitUntil(
            caches.delete(CACHE_NAME)
        );
    }
});

console.log('[SW] Service worker loaded');
