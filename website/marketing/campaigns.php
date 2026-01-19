<?php
require_once 'config.php';

$action = $_GET['action'] ?? 'list';
$campaignId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $name = $_POST['campaign_name'] ?? '';
    $type = $_POST['campaign_type'] ?? 'email';
    $platforms = $_POST['platforms'] ?? [];
    
    if ($name) {
        $id = createCampaign($name, $type, null, $platforms);
        header('Location: /marketing/campaigns.php?id=' . $id);
        exit;
    }
}

$campaigns = getCampaigns();
$allPlatforms = getPlatformsByType();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Management - Marketing</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .back-btn { padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; text-decoration: none; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .btn { padding: 0.75rem 1.5rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .campaigns-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .campaign-card { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; cursor: pointer; transition: 0.3s; }
        .campaign-card:hover { transform: translateY(-3px); border: 1px solid #00d9ff; }
        .campaign-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .campaign-name { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .campaign-type { color: #888; font-size: 0.85rem; }
        .campaign-status { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; }
        .status-draft { background: rgba(150,150,150,0.2); color: #999; }
        .status-active { background: rgba(0,255,136,0.2); color: #00ff88; }
        .campaign-footer { display: flex; justify-content: space-between; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #1a1a2e; border-radius: 12px; padding: 2rem; max-width: 600px; width: 90%; }
        .modal-header { display: flex; justify-content: space-between; margin-bottom: 1.5rem; }
        .modal-close { background: transparent; border: none; color: #888; font-size: 1.5rem; cursor: pointer; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #ccc; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; }
        .platform-selector { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.75rem; }
        .platform-option { padding: 0.75rem; background: rgba(255,255,255,0.05); border: 2px solid transparent; border-radius: 8px; cursor: pointer; text-align: center; transition: 0.3s; }
        .platform-option:hover { border-color: rgba(0,217,255,0.5); }
        .platform-option.selected { border-color: #00d9ff; background: rgba(0,217,255,0.1); }
        .platform-option input[type="checkbox"] { display: none; }
    </style>
</head>
<body>
<div class="container">
    <header>
        <div>
            <a href="/marketing/index.php" class="back-btn">← Back</a>
            <h1 style="display: inline; margin-left: 1rem;">Campaigns</h1>
        </div>
        <button class="btn" onclick="openCreateModal()">+ New Campaign</button>
    </header>

    <?php if ($action === 'list'): ?>
        <div class="campaigns-grid">
            <?php if (empty($campaigns)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 4rem; color: #666;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📭</div>
                    <p>No campaigns yet. Create your first campaign!</p>
                </div>
            <?php else: ?>
                <?php foreach ($campaigns as $campaign): ?>
                    <div class="campaign-card" onclick="window.location.href='/marketing/campaigns.php?id=<?= $campaign['id'] ?>'">
                        <div class="campaign-header">
                            <div>
                                <div class="campaign-name"><?= htmlspecialchars($campaign['campaign_name']) ?></div>
                                <div class="campaign-type"><?= ucfirst($campaign['campaign_type']) ?> Campaign</div>
                            </div>
                            <span class="campaign-status status-<?= $campaign['status'] ?>">
                                <?= ucfirst($campaign['status']) ?>
                            </span>
                        </div>
                        <div class="campaign-footer">
                            <div style="color: #888; font-size: 0.85rem;">
                                Created: <?= date('M j, Y', strtotime($campaign['created_at'])) ?>
                            </div>
                            <div style="color: #00d9ff; font-size: 0.85rem;">
                                <?php
                                $platformIds = json_decode($campaign['platforms'] ?? '[]', true);
                                echo count($platformIds) . ' platforms';
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Create Campaign Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Create New Campaign</h2>
            <button class="modal-close" onclick="closeCreateModal()">×</button>
        </div>
        <form method="POST" action="?action=create">
            <div class="form-group">
                <label for="campaign_name">Campaign Name *</label>
                <input type="text" id="campaign_name" name="campaign_name" required placeholder="e.g., Summer Promo 2026">
            </div>
            
            <div class="form-group">
                <label for="campaign_type">Campaign Type *</label>
                <select id="campaign_type" name="campaign_type" required>
                    <option value="email">Email Campaign</option>
                    <option value="social">Social Media Campaign</option>
                    <option value="multi">Multi-Channel Campaign</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Select Platforms</label>
                <div class="platform-selector">
                    <?php foreach ($allPlatforms as $platform): ?>
                        <label class="platform-option" onclick="togglePlatform(this)">
                            <input type="checkbox" name="platforms[]" value="<?= $platform['id'] ?>">
                            <div style="font-size: 1.5rem;"><?= $platform['icon'] ?></div>
                            <div style="font-size: 0.75rem; margin-top: 0.25rem;"><?= $platform['platform_name'] ?></div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit" class="btn" style="width: 100%;">Create Campaign</button>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('createModal').classList.add('active');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.remove('active');
}

function togglePlatform(label) {
    const checkbox = label.querySelector('input[type="checkbox"]');
    checkbox.checked = !checkbox.checked;
    label.classList.toggle('selected', checkbox.checked);
}
</script>
</body>
</html>
