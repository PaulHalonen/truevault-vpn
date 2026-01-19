<?php
require_once 'config.php';

$clients = getClients(false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - Enterprise Hub</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1600px; margin: 0 auto; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h1 { font-size: 2.5rem; }
        .back-btn { padding: 0.75rem 1.5rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; text-decoration: none; }
        .section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; }
        .clients-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }
        .client-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 1.5rem; transition: 0.3s; }
        .client-card:hover { transform: translateY(-3px); border-color: #00d9ff; }
        .client-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .client-name { font-size: 1.4rem; font-weight: 700; color: #00d9ff; }
        .status-badge { padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .status-active { background: rgba(0,255,136,0.2); color: #00ff88; }
        .status-inactive { background: rgba(255,100,100,0.2); color: #ff6464; }
        .client-info { color: #888; font-size: 0.9rem; line-height: 1.8; }
        .client-info strong { color: #fff; }
        .client-actions { display: flex; gap: 0.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .btn-small { padding: 0.5rem 1rem; font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>👥 Clients</h1>
        <a href="/enterprise/" class="back-btn">← Dashboard</a>
    </div>

    <div class="section">
        <div class="section-header">
            <h2>All Clients (<?= count($clients) ?>)</h2>
            <button class="btn btn-primary" onclick="showNewClientForm()">+ New Client</button>
        </div>

        <div class="clients-grid">
            <?php foreach ($clients as $client): ?>
                <div class="client-card">
                    <div class="client-header">
                        <div class="client-name"><?= htmlspecialchars($client['company_name']) ?></div>
                        <span class="status-badge status-<?= $client['status'] ?>">
                            <?= strtoupper($client['status']) ?>
                        </span>
                    </div>
                    
                    <div class="client-info">
                        <?php if ($client['contact_name']): ?>
                            <div>📧 <strong><?= htmlspecialchars($client['contact_name']) ?></strong></div>
                        <?php endif; ?>
                        <?php if ($client['contact_email']): ?>
                            <div>✉️ <?= htmlspecialchars($client['contact_email']) ?></div>
                        <?php endif; ?>
                        <?php if ($client['contact_phone']): ?>
                            <div>📞 <?= htmlspecialchars($client['contact_phone']) ?></div>
                        <?php endif; ?>
                        <?php if ($client['industry']): ?>
                            <div>🏢 <?= htmlspecialchars($client['industry']) ?></div>
                        <?php endif; ?>
                        <div>💰 $<?= number_format($client['hourly_rate'], 2) ?>/hr</div>
                        <div>📅 Net <?= $client['payment_terms'] ?> days</div>
                    </div>
                    
                    <div class="client-actions">
                        <button class="btn btn-primary btn-small" onclick="viewClient(<?= $client['id'] ?>)">
                            View Details
                        </button>
                        <button class="btn btn-primary btn-small" onclick="createProject(<?= $client['id'] ?>)">
                            + Project
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function showNewClientForm() {
    alert('New Client Form - To be implemented with modal');
}

function viewClient(clientId) {
    window.location.href = '/enterprise/client-details.php?id=' + clientId;
}

function createProject(clientId) {
    window.location.href = '/enterprise/projects.php?action=new&client=' + clientId;
}
</script>
</body>
</html>
