<?php
/**
 * Self-Service: View Connected Devices
 * See all devices connected to the account
 */

$devices = [];
$deviceLimit = 5; // Default for Personal plan
if ($customerId) {
    // Sample devices
    $devices = [
        ['name' => 'Windows Desktop', 'type' => 'windows', 'ip' => '192.168.1.100', 'last_seen' => '2026-01-24 09:30:00', 'status' => 'online'],
        ['name' => 'iPhone 15', 'type' => 'ios', 'ip' => '192.168.1.105', 'last_seen' => '2026-01-24 08:15:00', 'status' => 'online'],
        ['name' => 'MacBook Pro', 'type' => 'macos', 'ip' => '10.0.0.50', 'last_seen' => '2026-01-23 22:00:00', 'status' => 'offline'],
    ];
}

$deviceIcons = [
    'windows' => '💻', 'macos' => '🍎', 'ios' => '📱', 
    'android' => '🤖', 'linux' => '🐧', 'router' => '📶'
];
?>

<style>
    .device-usage {
        background: rgba(255,255,255,0.03);
        border-radius: 12px;
        padding: 15px 20px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .usage-bar {
        flex: 1;
        height: 8px;
        background: rgba(255,255,255,0.1);
        border-radius: 4px;
        margin: 0 15px;
        overflow: hidden;
    }
    .usage-fill {
        height: 100%;
        background: linear-gradient(90deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
        border-radius: 4px;
    }
    .devices-list { display: flex; flex-direction: column; gap: 12px; }
    .device-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 10px;
    }
    .device-info { display: flex; align-items: center; gap: 12px; }
    .device-icon { font-size: 1.5rem; }
    .device-name { font-weight: 500; }
    .device-meta { font-size: 0.8rem; color: #888; }
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }
    .status-online { background: #00c864; }
    .status-offline { background: #666; }
</style>

<p style="color: #aaa; margin-bottom: 20px;">Devices connected to your TrueVault account:</p>

<div class="device-usage">
    <span style="color: #888; font-size: 0.9rem;">Usage</span>
    <div class="usage-bar">
        <div class="usage-fill" style="width: <?php echo (count($devices) / $deviceLimit) * 100; ?>%;"></div>
    </div>
    <span style="font-weight: 600;"><?php echo count($devices); ?> / <?php echo $deviceLimit; ?></span>
</div>

<?php if (empty($devices)): ?>
<p style="color: #888; text-align: center; padding: 30px;">No devices connected yet.</p>
<?php else: ?>
<div class="devices-list">
    <?php foreach ($devices as $device): ?>
    <div class="device-card">
        <div class="device-info">
            <span class="device-icon"><?php echo $deviceIcons[$device['type']] ?? '📁'; ?></span>
            <div>
                <div class="device-name"><?php echo htmlspecialchars($device['name']); ?></div>
                <div class="device-meta">
                    <span class="status-dot status-<?php echo $device['status']; ?>"></span>
                    <?php echo ucfirst($device['status']); ?> • 
                    Last seen: <?php echo date('M j, g:ia', strtotime($device['last_seen'])); ?>
                </div>
            </div>
        </div>
        <button class="btn btn-secondary btn-sm" onclick="confirmRemove('<?php echo htmlspecialchars($device['name']); ?>')">
            <i class="fas fa-times"></i> Remove
        </button>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<p style="color: #666; font-size: 0.8rem; margin-top: 20px; text-align: center;">
    Need more devices? <a href="/upgrade" style="color: <?php echo $primaryColor; ?>;">Upgrade your plan</a>
</p>

<script>
function confirmRemove(name) {
    if (confirm('Remove device "' + name + '"? You can reconnect it anytime.')) {
        // Submit removal
        alert('Device removed. It will disconnect within 1 minute.');
    }
}
</script>
