<?php
/**
 * Self-Service: Regenerate WireGuard Keys
 * Generate new encryption keys for security
 */

$regenerateMessage = '';
$regenerateType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_regenerate'])) {
    // In production, this would regenerate keys
    $regenerateMessage = "New keys generated successfully! Download your updated configuration files.";
    $regenerateType = 'success';
}
?>

<style>
    .warning-box {
        background: rgba(255,180,0,0.1);
        border: 1px solid rgba(255,180,0,0.3);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .warning-box h4 { color: #ffb400; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
    .warning-box ul { margin-left: 20px; color: #ccc; line-height: 1.8; }
    .confirm-box {
        background: rgba(255,255,255,0.03);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
    }
    .confirm-checkbox {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin: 20px 0;
        color: #ccc;
    }
</style>

<p style="color: #aaa; margin-bottom: 20px;">Generate new WireGuard encryption keys for enhanced security:</p>

<?php if ($regenerateMessage): ?>
<div class="alert alert-<?php echo $regenerateType; ?>" style="padding: 15px; border-radius: 10px; margin-bottom: 20px; background: rgba(0,200,100,0.15); border: 1px solid rgba(0,200,100,0.3); color: #00c864;">
    <i class="fas fa-check-circle"></i> <?php echo $regenerateMessage; ?>
    <div style="margin-top: 15px;">
        <a href="?action=download_configs" class="btn btn-primary">
            <i class="fas fa-download"></i> Download New Configs
        </a>
    </div>
</div>
<?php else: ?>

<div class="warning-box">
    <h4><i class="fas fa-exclamation-triangle"></i> Important Information</h4>
    <ul>
        <li>All your current configuration files will become invalid</li>
        <li>You'll need to download and install new configs on all devices</li>
        <li>Active VPN connections will be disconnected</li>
        <li>This action cannot be undone</li>
    </ul>
</div>

<div class="confirm-box">
    <h4 style="margin-bottom: 15px;">When should you regenerate keys?</h4>
    <ul style="text-align: left; color: #888; line-height: 1.8; margin: 0 0 20px 20px;">
        <li>You suspect your keys may have been compromised</li>
        <li>You shared a config file accidentally</li>
        <li>Routine security maintenance (every 6-12 months)</li>
        <li>You want a fresh start with new encryption</li>
    </ul>
    
    <form method="POST">
        <label class="confirm-checkbox">
            <input type="checkbox" id="understand" required>
            I understand my current configs will stop working
        </label>
        
        <button type="submit" name="confirm_regenerate" class="btn btn-primary" id="regenBtn" disabled>
            <i class="fas fa-key"></i> Generate New Keys
        </button>
    </form>
</div>
<?php endif; ?>

<script>
document.getElementById('understand')?.addEventListener('change', function() {
    document.getElementById('regenBtn').disabled = !this.checked;
});
</script>
