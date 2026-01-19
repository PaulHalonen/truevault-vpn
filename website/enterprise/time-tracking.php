<?php
require_once 'config.php';

$timeEntries = getTimeEntries();
$projects = getProjects(null, 'active');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time Tracking - Enterprise Hub</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1600px; margin: 0 auto; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h1 { font-size: 2.5rem; }
        .back-btn { padding: 0.75rem 1.5rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; text-decoration: none; }
        .section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { background: rgba(255,255,255,0.05); padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid rgba(255,255,255,0.1); }
        .table td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .billable-badge { padding: 0.25rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .billable-yes { background: rgba(0,255,136,0.2); color: #00ff88; }
        .billable-no { background: rgba(255,100,100,0.2); color: #ff6464; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>⏱️ Time Tracking</h1>
        <a href="/enterprise/" class="back-btn">← Dashboard</a>
    </div>

    <div class="section">
        <div class="section-header">
            <h2>Time Entries</h2>
            <button class="btn btn-primary" onclick="showLogTimeForm()">+ Log Time</button>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Project</th>
                    <th>Team Member</th>
                    <th>Description</th>
                    <th>Hours</th>
                    <th>Rate</th>
                    <th>Amount</th>
                    <th>Billable</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($timeEntries as $entry): ?>
                    <tr>
                        <td><?= date('M j, Y', strtotime($entry['entry_date'])) ?></td>
                        <td><?= htmlspecialchars($entry['project_name']) ?></td>
                        <td><?= htmlspecialchars($entry['member_name']) ?></td>
                        <td><?= htmlspecialchars($entry['description']) ?></td>
                        <td><?= number_format($entry['hours'], 2) ?></td>
                        <td>$<?= number_format($entry['hourly_rate'], 2) ?></td>
                        <td>$<?= number_format($entry['hours'] * $entry['hourly_rate'], 2) ?></td>
                        <td>
                            <span class="billable-badge billable-<?= $entry['billable'] ? 'yes' : 'no' ?>">
                                <?= $entry['billable'] ? 'BILLABLE' : 'NON-BILLABLE' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function showLogTimeForm() {
    alert('Log Time Form - To be implemented with modal');
}
</script>
</body>
</html>
