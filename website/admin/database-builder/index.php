<?php
/**
 * TrueVault VPN - Database Builder Dashboard
 * Part 13 - Task 13.2
 * Main dashboard showing all custom tables
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_BUILDER', DB_PATH . 'builder.db');

// Check if database exists
if (!file_exists(DB_BUILDER)) {
    header('Location: setup-builder.php');
    exit;
}

// Get all tables
$db = new SQLite3(DB_BUILDER);
$db->enableExceptions(true);

$result = $db->query("SELECT * FROM custom_tables WHERE status = 'active' ORDER BY display_name");
$tables = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $tables[] = $row;
}
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Builder - TrueVault</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .header h1 { font-size: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .header h1 span { font-size: 1.8rem; }
        .actions { display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,217,255,0.3); }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
        .btn-danger { background: rgba(255,80,80,0.2); color: #ff5050; border: 1px solid rgba(255,80,80,0.3); padding: 8px 12px; }
        .container { max-width: 1400px; margin: 0 auto; padding: 30px; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; text-align: center; }
        .stat-num { font-size: 2rem; font-weight: 700; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-label { color: #888; font-size: 0.85rem; margin-top: 5px; }
        .section-title { font-size: 1.2rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .tables-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .table-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; transition: all 0.2s; }
        .table-card:hover { border-color: #00d9ff; transform: translateY(-2px); }
        .table-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 15px; }
        .table-card h3 { font-size: 1.1rem; margin-bottom: 5px; }
        .table-card .meta { color: #888; font-size: 0.85rem; margin-bottom: 15px; }
        .table-card .card-actions { display: flex; gap: 8px; }
        .table-card .card-actions .btn { flex: 1; padding: 8px; font-size: 0.8rem; }
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state .icon { font-size: 4rem; margin-bottom: 20px; }
        .empty-state h3 { margin-bottom: 10px; }
        .empty-state p { color: #888; margin-bottom: 20px; }
        .tutorial-banner { background: linear-gradient(135deg, rgba(0,217,255,0.1), rgba(0,255,136,0.1)); border: 1px solid rgba(0,217,255,0.3); border-radius: 12px; padding: 20px; margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; }
        .tutorial-banner h4 { margin-bottom: 5px; }
        .tutorial-banner p { color: #888; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="header">
        <h1><span>🗂️</span> Database Builder</h1>
        <div class="actions">
            <button class="btn btn-secondary" onclick="location.href='import.php'">📥 Import</button>
            <button class="btn btn-primary" onclick="location.href='create-table.php'">+ New Table</button>
        </div>
    </div>
    
    <div class="container">
        <div class="stats">
            <div class="stat-card">
                <div class="stat-num"><?= count($tables) ?></div>
                <div class="stat-label">Total Tables</div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?= array_sum(array_column($tables, 'record_count')) ?></div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-num">15+</div>
                <div class="stat-label">Field Types</div>
            </div>
            <div class="stat-card">
                <div class="stat-num">∞</div>
                <div class="stat-label">Possibilities</div>
            </div>
        </div>
        
        <?php if (empty($tables)): ?>
        <div class="tutorial-banner">
            <div>
                <h4>💡 New to Database Builder?</h4>
                <p>Create your first table in under 2 minutes - no coding required!</p>
            </div>
            <button class="btn btn-primary" onclick="location.href='tutorial.php'">▶️ Start Tutorial</button>
        </div>
        <?php endif; ?>
        
        <h2 class="section-title">📊 Your Tables</h2>
        
        <?php if (empty($tables)): ?>
        <div class="empty-state">
            <div class="icon">📋</div>
            <h3>No Tables Yet</h3>
            <p>Create your first custom database table to get started.</p>
            <button class="btn btn-primary" onclick="location.href='create-table.php'">+ Create First Table</button>
        </div>
        <?php else: ?>
        <div class="tables-grid">
            <?php foreach ($tables as $table): ?>
            <div class="table-card">
                <div class="table-icon" style="background: <?= htmlspecialchars($table['color']) ?>20;">
                    <?= $table['icon'] === 'table' ? '📋' : htmlspecialchars($table['icon']) ?>
                </div>
                <h3><?= htmlspecialchars($table['display_name']) ?></h3>
                <div class="meta"><?= $table['record_count'] ?> records</div>
                <div class="card-actions">
                    <button class="btn btn-primary" onclick="location.href='view-data.php?table=<?= $table['id'] ?>'">Open</button>
                    <button class="btn btn-secondary" onclick="location.href='designer.php?id=<?= $table['id'] ?>'">Edit</button>
                    <button class="btn btn-danger" onclick="deleteTable(<?= $table['id'] ?>, '<?= htmlspecialchars($table['display_name']) ?>')">🗑️</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    function deleteTable(tableId, tableName) {
        if (confirm('Are you sure you want to delete "' + tableName + '"? This cannot be undone!')) {
            fetch('api/tables.php', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({table_id: tableId})
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to delete table');
                }
            });
        }
    }
    </script>
</body>
</html>
