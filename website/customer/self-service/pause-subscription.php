<?php
/**
 * Self-Service: Pause Subscription
 * Temporarily pause VPN service (up to 30 days)
 */

$pauseMessage = '';
$pauseType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pause_days'])) {
    $days = intval($_POST['pause_days']);
    if ($days >= 7 && $days <= 30) {
        // In production, this would pause the subscription
        $resumeDate = date('F j, Y', strtotime("+$days days"));
        $pauseMessage = "Subscription paused! Service will automatically resume on $resumeDate.";
        $pauseType = 'success';
    } else {
        $pauseMessage = "Please select a valid pause duration (7-30 days).";
        $pauseType = 'error';
    }
}
?>

<style>
    .pause-options {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin: 20px 0;
    }
    .pause-option {
        padding: 20px;
        background: rgba(255,255,255,0.03);
        border: 2px solid rgba(255,255,255,0.08);
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .pause-option:hover { border-color: <?php echo $primaryColor; ?>40; }
    .pause-option.selected { border-color: <?php echo $primaryColor; ?>; background: rgba(0,212,255,0.1); }
    .pause-option .days { font-size: 1.8rem; font-weight: 700; color: <?php echo $primaryColor; ?>; }
    .pause-option .label { color: #888; font-size: 0.85rem; margin-top: 5px; }
    .info-box {
        background: rgba(0,212,255,0.1);
        border: 1px solid rgba(0,212,255,0.2);
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
    }
</style>

<p style="color: #aaa; margin-bottom: 20px;">Need a break? Pause your subscription without losing your account:</p>

<?php if ($pauseMessage): ?>
<div class="alert alert-<?php echo $pauseType; ?>" style="padding: 15px; border-radius: 10px; margin-bottom: 20px; 
    background: <?php echo $pauseType === 'success' ? 'rgba(0,200,100,0.15)' : 'rgba(255,80,80,0.15)'; ?>; 
    border: 1px solid <?php echo $pauseType === 'success' ? 'rgba(0,200,100,0.3)' : 'rgba(255,80,80,0.3)'; ?>; 
    color: <?php echo $pauseType === 'success' ? '#00c864' : '#ff5050'; ?>;">
    <i class="fas <?php echo $pauseType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
    <?php echo $pauseMessage; ?>
</div>
<?php else: ?>

<div class="info-box">
    <h4 style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-info-circle" style="color: <?php echo $primaryColor; ?>;"></i> How pausing works
    </h4>
    <ul style="margin-left: 20px; color: #ccc; line-height: 1.8; font-size: 0.9rem;">
        <li>Your VPN access will be temporarily disabled</li>
        <li>Billing will pause for the selected duration</li>
        <li>Your account, settings, and data are preserved</li>
        <li>Service resumes automatically - no action needed</li>
    </ul>
</div>

<form method="POST" id="pauseForm">
    <h4 style="margin-bottom: 15px; color: #ccc;">Select pause duration:</h4>
    
    <div class="pause-options">
        <label class="pause-option" onclick="selectPause(7)">
            <input type="radio" name="pause_days" value="7" style="display: none;">
            <div class="days">7</div>
            <div class="label">days</div>
        </label>
        <label class="pause-option" onclick="selectPause(14)">
            <input type="radio" name="pause_days" value="14" style="display: none;">
            <div class="days">14</div>
            <div class="label">days</div>
        </label>
        <label class="pause-option" onclick="selectPause(30)">
            <input type="radio" name="pause_days" value="30" style="display: none;">
            <div class="days">30</div>
            <div class="label">days (max)</div>
        </label>
    </div>
    
    <p id="resumeDate" style="color: #888; text-align: center; margin-bottom: 20px;"></p>
    
    <div style="text-align: center;">
        <button type="submit" class="btn btn-warning" id="pauseBtn" disabled>
            <i class="fas fa-pause"></i> Pause My Subscription
        </button>
    </div>
</form>
<?php endif; ?>

<script>
function selectPause(days) {
    document.querySelectorAll('.pause-option').forEach(o => o.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    document.querySelector(`input[value="${days}"]`).checked = true;
    document.getElementById('pauseBtn').disabled = false;
    
    const resume = new Date();
    resume.setDate(resume.getDate() + days);
    document.getElementById('resumeDate').textContent = 
        'Service will resume on ' + resume.toLocaleDateString('en-US', {month: 'long', day: 'numeric', year: 'numeric'});
}
</script>
