<?php
/**
 * Self-Service: Download VPN Configs
 * Download configuration files for all devices
 */

// Get customer's devices/configs
$configs = [];
if ($customerId) {
    // In production, fetch from devices database
    // For now, provide sample configs
    $configs = [
        ['name' => 'Windows PC', 'type' => 'windows', 'file' => 'truevault-windows.conf'],
        ['name' => 'MacBook', 'type' => 'macos', 'file' => 'truevault-macos.conf'],
        ['name' => 'iPhone', 'type' => 'ios', 'file' => 'truevault-ios.mobileconfig'],
        ['name' => 'Android Phone', 'type' => 'android', 'file' => 'truevault-android.conf'],
        ['name' => 'Linux Server', 'type' => 'linux', 'file' => 'truevault-linux.conf']
    ];
}

$deviceIcons = [
    'windows' => '💻',
    'macos' => '🍎',
    'ios' => '📱',
    'android' => '🤖',
    'linux' => '🐧',
    'router' => '📶'
];
?>

<style>
    .configs-list { display: flex; flex-direction: column; gap: 12px; }
    .config-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 10px;
    }
    .config-info { display: flex; align-items: center; gap: 12px; }
    .config-icon { font-size: 1.5rem; }
    .config-name { font-weight: 500; }
    .config-file { font-size: 0.8rem; color: #888; font-family: monospace; }
    .download-all {
        margin-top: 20px;
        padding: 15px;
        background: rgba(0,212,255,0.1);
        border: 1px solid rgba(0,212,255,0.2);
        border-radius: 10px;
        text-align: center;
    }
</style>

<p style="color: #aaa; margin-bottom: 20px;">Download WireGuard configuration files for your devices:</p>

<?php if (empty($configs)): ?>
<p style="color: #888; text-align: center; padding: 30px;">No configurations found. Please set up your devices first.</p>
<?php else: ?>
<div class="configs-list">
    <?php foreach ($configs as $config): ?>
    <div class="config-item">
        <div class="config-info">
            <span class="config-icon"><?php echo $deviceIcons[$config['type']] ?? '📁'; ?></span>
            <div>
                <div class="config-name"><?php echo htmlspecialchars($config['name']); ?></div>
                <div class="config-file"><?php echo htmlspecialchars($config['file']); ?></div>
            </div>
        </div>
        <a href="/api/download-config.php?file=<?php echo urlencode($config['file']); ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-download"></i> Download
        </a>
    </div>
    <?php endforeach; ?>
</div>

<div class="download-all">
    <p style="margin-bottom: 10px; color: #ccc;">Download all configs in one ZIP file</p>
    <a href="/api/download-config.php?all=1" class="btn btn-primary">
        <i class="fas fa-file-archive"></i> Download All (ZIP)
    </a>
</div>
<?php endif; ?>

<p style="color: #666; font-size: 0.8rem; margin-top: 20px; text-align: center;">
    <i class="fas fa-info-circle"></i> Need setup help? 
    <a href="/help/setup-guide" style="color: <?php echo $primaryColor; ?>;">View Setup Guide</a>
</p>
