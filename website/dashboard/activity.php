<?php
/**
 * TrueVault VPN - Activity Logs Dashboard
 * 
 * PURPOSE: Display user activity history
 * AUTHENTICATION: JWT required
 * 
 * SHOWS:
 * - Device actions (created, deleted, switched)
 * - Login history
 * - Settings changes
 * - Port forwarding changes
 * - Timeline view with details
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load configuration
require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/JWT.php';
require_once __DIR__ . '/../includes/Auth.php';

// Check authentication
try {
    $user = Auth::require();
    $userId = $user['user_id'];
    $userName = $user['first_name'];
} catch (Exception $e) {
    header('Location: /auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - TrueVault VPN</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-title h1 {
            font-size: 28px;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .header-title p {
            color: #64748b;
            font-size: 14px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
        }

        .timeline {
            position: relative;
            padding-left: 40px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 12px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        }

        .timeline-item {
            position: relative;
            padding-bottom: 30px;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-dot {
            position: absolute;
            left: -34px;
            top: 4px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: white;
            border: 3px solid #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .timeline-content {
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            border-left: 3px solid #667eea;
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .timeline-action {
            font-weight: 600;
            color: #1e293b;
            font-size: 15px;
        }

        .timeline-time {
            color: #64748b;
            font-size: 12px;
        }

        .timeline-details {
            color: #475569;
            font-size: 14px;
            line-height: 1.5;
        }

        .timeline-meta {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #94a3b8;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }

        .spinner {
            display: inline-block;
            width: 24px;
            height: 24px;
            border: 3px solid rgba(102, 126, 234, 0.3);
            border-radius: 50%;
            border-top-color: #667eea;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .action-icon {
            display: inline-block;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <?php include __DIR__ . '/../includes/navigation.php'; ?>
        
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <h1>📋 Activity Log</h1>
                    <p>Track all your account activities</p>
                </div>
                <a href="/dashboard/my-devices.php" class="btn btn-secondary">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="card">
            <h2 class="card-title">Recent Activity</h2>
            
            <div id="loading" class="loading">
                <div class="spinner"></div>
                <p style="margin-top: 16px;">Loading activity logs...</p>
            </div>

            <div id="timeline" class="timeline" style="display: none;">
                <!-- Timeline items will be loaded here -->
            </div>

            <div id="empty-state" class="empty-state" style="display: none;">
                <div class="empty-icon">📭</div>
                <h3>No Activity Yet</h3>
                <p>Your activity history will appear here</p>
            </div>
        </div>
    </div>

    <script>
        // Activity action icons mapping
        const actionIcons = {
            'device_created': '➕',
            'device_deleted': '🗑️',
            'device_switched_server': '🔄',
            'login': '🔓',
            'logout': '🚪',
            'password_changed': '🔒',
            'settings_updated': '⚙️',
            'port_forwarding_enabled': '🔌',
            'port_forwarding_disabled': '⏸️',
            'subscription_created': '💳',
            'subscription_cancelled': '❌',
            'payment_received': '✅',
            'payment_failed': '⚠️'
        };

        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            const seconds = Math.floor(diff / 1000);
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);
            const days = Math.floor(hours / 24);

            if (seconds < 60) return 'Just now';
            if (minutes < 60) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
            if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            if (days < 7) return `${days} day${days > 1 ? 's' : ''} ago`;
            
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' at ' +
                   date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        }

        // Get action icon
        function getActionIcon(action) {
            return actionIcons[action] || '📝';
        }

        // Format action text
        function formatAction(action) {
            return action.split('_').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1)
            ).join(' ');
        }

        // Load activity logs
        async function loadActivityLogs() {
            try {
                const token = localStorage.getItem('truevault_token');
                const response = await fetch('/api/activity/list.php', {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });

                const data = await response.json();

                document.getElementById('loading').style.display = 'none';

                if (data.success && data.logs && data.logs.length > 0) {
                    const timeline = document.getElementById('timeline');
                    timeline.style.display = 'block';
                    
                    timeline.innerHTML = data.logs.map(log => `
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <div class="timeline-action">
                                        <span class="action-icon">${getActionIcon(log.action)}</span>
                                        ${formatAction(log.action)}
                                    </div>
                                    <div class="timeline-time">${formatDate(log.created_at)}</div>
                                </div>
                                ${log.details ? `<div class="timeline-details">${log.details}</div>` : ''}
                                <div class="timeline-meta">
                                    IP: ${log.ip_address || 'Unknown'}
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    document.getElementById('empty-state').style.display = 'block';
                }
            } catch (error) {
                console.error('Error loading activity logs:', error);
                document.getElementById('loading').style.display = 'none';
                document.getElementById('empty-state').style.display = 'block';
            }
        }

        // Load on page load
        loadActivityLogs();
    </script>
</body>
</html>
