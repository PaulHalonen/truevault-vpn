<?php
require_once 'config.php';
requireAdminLogin();

$admin = getCurrentAdmin();
$stats = getDashboardStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; }
        .container { display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar { width: 260px; background: rgba(255,255,255,0.05); border-right: 1px solid rgba(255,255,255,0.1); padding: 2rem 0; }
        .sidebar-logo { padding: 0 1.5rem; margin-bottom: 2rem; }
        .sidebar-logo h1 { font-size: 1.3rem; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .sidebar-nav { list-style: none; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #ccc; text-decoration: none; transition: 0.3s; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(0,217,255,0.1); color: #00d9ff; border-left: 3px solid #00d9ff; }
        .sidebar-nav .icon { font-size: 1.2rem; }
        .sidebar-footer { margin-top: auto; padding: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-info { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #00d9ff, #00ff88); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #000; }
        .user-details { flex: 1; }
        .user-name { font-weight: 600; font-size: 0.9rem; }
        .user-role { font-size: 0.75rem; color: #666; }
        .btn-logout { width: 100%; padding: 0.5rem; background: rgba(255,100,100,0.2); border: 1px solid rgba(255,100,100,0.4); color: #ff6464; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: 0.3s; }
        .btn-logout:hover { background: rgba(255,100,100,0.3); }
        
        /* Main content */
        .main-content { flex: 1; padding: 2rem; overflow-y: auto; }
        .page-header { margin-bottom: 2rem; }
        .page-header h2 { font-size: 2rem; margin-bottom: 0.5rem; }
        .page-header p { color: #888; }
        
        /* Stats grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 1.5rem; }
        .stat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .stat-icon { font-size: 2rem; }
        .stat-label { color: #888; font-size: 0.85rem; margin-bottom: 0.5rem; }
        .stat-value { font-size: 2rem; font-weight: 700; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        /* Quick actions */
        .quick-actions { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; }
        .quick-actions h3 { margin-bottom: 1.5rem; }
        .actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .action-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 1.5rem; text-align: center; cursor: pointer; transition: 0.3s; text-decoration: none; color: inherit; display: block; }
        .action-card:hover { transform: translateY(-3px); border-color: #00d9ff; background: rgba(0,217,255,0.1); }
        .action-icon { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .action-label { font-weight: 600; }
        
        /* Recent activity */
        .activity-section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; }
        .activity-section h3 { margin-bottom: 1.5rem; }
        .activity-list { list-style: none; }
        .activity-item { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .activity-item:last-child { border-bottom: none; }
        .activity-time { color: #666; font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <h1>🔒 TrueVault VPN</h1>
            <p style="color: #666; font-size: 0.85rem;">Admin Panel</p>
        </div>
        
        <ul class="sidebar-nav">
            <li><a href="/admin/index.php" class="active"><span class="icon">📊</span> Dashboard</a></li>
            <li><a href="/admin/users.php"><span class="icon">👥</span> Users</a></li>
            <li><a href="/admin/devices.php"><span class="icon">📱</span> Devices</a></li>
            <li><a href="/admin/payments.php"><span class="icon">💰</span> Payments</a></li>
            <li><a href="/admin/tickets.php"><span class="icon">🎫</span> Support Tickets</a></li>
            <li><a href="/admin/settings.php"><span class="icon">⚙️</span> Settings</a></li>
            <li><a href="/database-builder/"><span class="icon">🗄️</span> Database Builder</a></li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($admin['name'], 0, 1)) ?></div>
                <div class="user-details">
                    <div class="user-name"><?= htmlspecialchars($admin['name']) ?></div>
                    <div class="user-role"><?= ucfirst($admin['role']) ?></div>
                </div>
            </div>
            <button onclick="window.location.href='/admin/logout.php'" class="btn-logout">Logout</button>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h2>Dashboard</h2>
            <p>Welcome back, <?= htmlspecialchars($admin['name']) ?>!</p>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">👥</div>
                </div>
                <div class="stat-label">Total Users</div>
                <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
                <div style="color: #00ff88; font-size: 0.85rem; margin-top: 0.5rem;">
                    <?= $stats['active_users'] ?> active
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">📱</div>
                </div>
                <div class="stat-label">Total Devices</div>
                <div class="stat-value"><?= number_format($stats['total_devices']) ?></div>
                <div style="color: #00ff88; font-size: 0.85rem; margin-top: 0.5rem;">
                    <?= $stats['active_devices'] ?> active
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">💰</div>
                </div>
                <div class="stat-label">Monthly Revenue</div>
                <div class="stat-value"><?= formatCurrency($stats['monthly_revenue']) ?></div>
                <div style="color: #888; font-size: 0.85rem; margin-top: 0.5rem;">
                    Total: <?= formatCurrency($stats['total_revenue']) ?>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">🎫</div>
                </div>
                <div class="stat-label">Open Tickets</div>
                <div class="stat-value"><?= $stats['open_tickets'] ?></div>
                <div style="color: #ff9966; font-size: 0.85rem; margin-top: 0.5rem;">
                    Needs attention
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3>Quick Actions</h3>
            <div class="actions-grid">
                <a href="/admin/users.php?action=create" class="action-card">
                    <div class="action-icon">➕</div>
                    <div class="action-label">Add User</div>
                </a>
                <a href="/admin/devices.php" class="action-card">
                    <div class="action-icon">📱</div>
                    <div class="action-label">Manage Devices</div>
                </a>
                <a href="/admin/payments.php" class="action-card">
                    <div class="action-icon">💳</div>
                    <div class="action-label">View Payments</div>
                </a>
                <a href="/admin/tickets.php" class="action-card">
                    <div class="action-icon">📧</div>
                    <div class="action-label">Support Tickets</div>
                </a>
                <a href="/admin/settings.php" class="action-card">
                    <div class="action-icon">⚙️</div>
                    <div class="action-label">Settings</div>
                </a>
                <a href="/database-builder/" class="action-card">
                    <div class="action-icon">🗄️</div>
                    <div class="action-label">Database Builder</div>
                </a>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="activity-section">
            <h3>Recent Activity</h3>
            <ul class="activity-list">
                <?php
                $db = getAdminDB();
                $stmt = $db->prepare("
                    SELECT al.*, au.name as admin_name
                    FROM activity_log al
                    LEFT JOIN admin_users au ON al.admin_id = au.id
                    ORDER BY al.created_at DESC
                    LIMIT 10
                ");
                $stmt->execute();
                $activities = $stmt->fetchAll();
                
                if (empty($activities)):
                ?>
                    <li class="activity-item">
                        <div style="color: #666;">No recent activity</div>
                    </li>
                <?php else: ?>
                    <?php foreach ($activities as $activity): ?>
                        <li class="activity-item">
                            <div>
                                <strong><?= htmlspecialchars($activity['admin_name'] ?? 'System') ?></strong>
                                <?= htmlspecialchars(str_replace('_', ' ', $activity['action'])) ?>
                                <?php if ($activity['entity_type']): ?>
                                    (<?= htmlspecialchars($activity['entity_type']) ?> #<?= $activity['entity_id'] ?>)
                                <?php endif; ?>
                            </div>
                            <div class="activity-time"><?= formatDate($activity['created_at'], 'M j, Y g:i A') ?></div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
</body>
</html>
