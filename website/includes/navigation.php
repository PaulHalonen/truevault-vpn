<?php
/**
 * TrueVault VPN - Dashboard Navigation Component
 * 
 * PURPOSE: Universal navigation menu for all dashboard pages
 * USAGE: Include this file at the top of any dashboard page
 * 
 * FEATURES:
 * - Active page highlighting
 * - Tier badge display
 * - Logout link
 * - Admin link (if admin)
 * - Responsive design
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// This file should be included, not accessed directly
if (!defined('TRUEVAULT_INIT')) {
    die('Direct access not permitted');
}

// Get current page
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Define navigation items
$navItems = [
    'my-devices' => ['icon' => '📱', 'label' => 'My Devices', 'url' => '/dashboard/my-devices.php'],
    'billing' => ['icon' => '💳', 'label' => 'Billing', 'url' => '/dashboard/billing.php'],
    'port-forwarding' => ['icon' => '🔌', 'label' => 'Port Forwarding', 'url' => '/dashboard/port-forwarding.php'],
    'analytics' => ['icon' => '📊', 'label' => 'Analytics', 'url' => '/dashboard/analytics.php'],
    'activity' => ['icon' => '📋', 'label' => 'Activity', 'url' => '/dashboard/activity.php'],
    'settings' => ['icon' => '⚙️', 'label' => 'Settings', 'url' => '/dashboard/settings.php'],
];
?>
<style>
.nav-container {
    margin-bottom: 24px;
}

.nav-menu {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
    background: rgba(255, 255, 255, 0.95);
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.nav-item {
    padding: 14px 20px;
    border-radius: 8px;
    text-decoration: none;
    text-align: center;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
    background: rgba(255, 255, 255, 0.5);
    color: #475569;
    border: 2px solid transparent;
}

.nav-item:hover {
    background: rgba(102, 126, 234, 0.1);
    border-color: rgba(102, 126, 234, 0.3);
    transform: translateY(-2px);
}

.nav-item.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: transparent;
}

.nav-item-icon {
    font-size: 20px;
    display: block;
    margin-bottom: 4px;
}

.nav-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(255, 255, 255, 0.95);
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 18px;
}

.user-details {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 15px;
}

.tier-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    display: inline-block;
}

.tier-standard {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
}

.tier-pro {
    background: rgba(168, 85, 247, 0.1);
    color: #a855f7;
}

.tier-vip {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #92400e;
}

.tier-admin {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.nav-links {
    display: flex;
    gap: 12px;
}

.nav-btn {
    padding: 10px 18px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s;
}

.nav-btn-admin {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.nav-btn-admin:hover {
    background: rgba(239, 68, 68, 0.2);
}

.nav-btn-logout {
    background: rgba(100, 116, 139, 0.1);
    color: #64748b;
    border: 1px solid rgba(100, 116, 139, 0.2);
}

.nav-btn-logout:hover {
    background: rgba(100, 116, 139, 0.2);
}

@media (max-width: 768px) {
    .nav-menu {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .nav-actions {
        flex-direction: column;
        gap: 16px;
    }
    
    .nav-links {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="nav-container">
    <!-- User Info Bar -->
    <div class="nav-actions">
        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper(substr($userName ?? 'U', 0, 1)) ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($userName ?? 'User') ?></div>
                <span class="tier-badge tier-<?= htmlspecialchars($userTier ?? 'standard') ?>">
                    <?= strtoupper(htmlspecialchars($userTier ?? 'standard')) ?>
                </span>
            </div>
        </div>
        
        <div class="nav-links">
            <?php if (isset($userTier) && $userTier === 'admin'): ?>
                <a href="/admin/dashboard.php" class="nav-btn nav-btn-admin">
                    🛡️ Admin Panel
                </a>
            <?php endif; ?>
            <a href="/auth/logout.php" class="nav-btn nav-btn-logout">
                🚪 Logout
            </a>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <div class="nav-menu">
        <?php foreach ($navItems as $key => $item): ?>
            <a 
                href="<?= $item['url'] ?>" 
                class="nav-item <?= $currentPage === $key ? 'active' : '' ?>"
            >
                <span class="nav-item-icon"><?= $item['icon'] ?></span>
                <?= $item['label'] ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
