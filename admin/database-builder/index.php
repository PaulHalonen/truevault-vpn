<?php
/**
 * TrueVault VPN - Database Builder Dashboard
 * Main interface for managing custom databases
 */

// Set database path
$db_path = __DIR__ . '/databases/builder.db';

// Check if setup has been run
if (!file_exists($db_path)) {
    header('Location: setup-builder.php');
    exit;
}

// Connect to builder database
$db = new SQLite3($db_path);

// Get all tables
$tables = [];
$result = $db->query("SELECT * FROM custom_tables WHERE status = 'active' ORDER BY display_name ASC");
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
    <title>Database Builder - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            color: #fff;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 20px;
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-icon {
            font-size: 2rem;
        }
        
        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            color: #0f0f1a;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,217,255,0.3);
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.08);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.15);
        }
        
        .btn-secondary:hover {
            background: rgba(255,255,255,0.12);
        }
        
        /* Stats Bar */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: #aaa;
            font-size: 0.9rem;
        }
        
        /* Tables Grid */
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #00d9ff;
        }
        
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .table-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s;
        }
        
        .table-card:hover {
            transform: translateY(-5px);
            border-color: #00d9ff;
            box-shadow: 0 8px 20px rgba(0,217,255,0.2);
        }
        
        .table-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .table-icon {
            font-size: 2.5rem;
        }
        
        .table-info h3 {
            font-size: 1.2rem;
            color: #fff;
            margin-bottom: 5px;
        }
        
        .table-info p {
            font-size: 0.85rem;
            color: #aaa;
        }
        
        .table-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-top: 1px solid rgba(255,255,255,0.05);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 15px;
        }
        
        .table-meta-item {
            text-align: center;
        }
        
        .meta-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #00ff88;
        }
        
        .meta-label {
            font-size: 0.75rem;
            color: #666;
        }
        
        .table-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-small {
            flex: 1;
            padding: 8px 15px;
            font-size: 0.85rem;
            text-align: center;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            border: 2px dashed rgba(255,255,255,0.1);
        }
        
        .empty-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #aaa;
            margin-bottom: 25px;
        }
        
        /* Tutorial Banner */
        .tutorial-banner {
            background: linear-gradient(135deg, rgba(0,217,255,0.1), rgba(0,255,136,0.1));
            border: 1px solid rgba(0,217,255,0.3);
            border-radius: 12px;
            padding: 25px;
            margin-top: 40px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .tutorial-icon {
            font-size: 3rem;
        }
        
        .tutorial-content {
            flex: 1;
        }
        
        .tutorial-content h3 {
            font-size: 1.3rem;
            margin-bottom: 8px;
        }
        
        .tutorial-content p {
            color: #aaa;
        }
        
        @media (max-width: 768px) {
            .header-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <span class="logo-icon">🗂️</span>
                <div>
                    <div class="logo-text">Database Builder</div>
                    <small style="color: #666;">Manage Your Data</small>
                </div>
            </div>
            
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="location.href='#tutorial'">📚 Tutorial</button>
                <button class="btn btn-secondary" onclick="location.href='#import'">📥 Import</button>
                <button class="btn btn-primary" onclick="location.href='designer.php?action=new'">+ New Table</button>
            </div>
        </header>
        
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($tables); ?></div>
                <div class="stat-label">Total Tables</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $total_records = array_sum(array_column($tables, 'record_count'));
                    echo number_format($total_records);
                    ?>
                </div>
                <div class="stat-label">Total Records</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $total_fields = 0;
                    $db = new SQLite3($db_path);
                    foreach ($tables as $table) {
                        $count = $db->querySingle("SELECT COUNT(*) FROM custom_fields WHERE table_id = " . $table['id']);
                        $total_fields += $count;
                    }
                    $db->close();
                    echo $total_fields;
                    ?>
                </div>
                <div class="stat-label">Total Fields</div>
            </div>
        </div>
        
        <h2 class="section-title">📊 Your Tables</h2>
        
        <?php if (empty($tables)): ?>
            <div class="empty-state">
                <div class="empty-icon">🗂️</div>
                <h3>No Tables Yet</h3>
                <p>Create your first database table to get started. It's as easy as using a spreadsheet!</p>
                <button class="btn btn-primary" onclick="location.href='designer.php?action=new'">Create Your First Table</button>
            </div>
        <?php else: ?>
            <div class="tables-grid">
                <?php foreach ($tables as $table): ?>
                    <?php
                    // Get field count for this table
                    $db = new SQLite3($db_path);
                    $field_count = $db->querySingle("SELECT COUNT(*) FROM custom_fields WHERE table_id = " . $table['id']);
                    $db->close();
                    ?>
                    <div class="table-card">
                        <div class="table-header">
                            <span class="table-icon"><?php echo htmlspecialchars($table['icon']); ?></span>
                            <div class="table-info">
                                <h3><?php echo htmlspecialchars($table['display_name']); ?></h3>
                                <p><?php echo htmlspecialchars($table['description'] ?: 'No description'); ?></p>
                            </div>
                        </div>
                        
                        <div class="table-meta">
                            <div class="table-meta-item">
                                <div class="meta-number"><?php echo $table['record_count']; ?></div>
                                <div class="meta-label">Records</div>
                            </div>
                            <div class="table-meta-item">
                                <div class="meta-number"><?php echo $field_count; ?></div>
                                <div class="meta-label">Fields</div>
                            </div>
                        </div>
                        
                        <div class="table-actions">
                            <button class="btn btn-secondary btn-small" onclick="location.href='data.php?table=<?php echo $table['id']; ?>'">📊 Open</button>
                            <button class="btn btn-secondary btn-small" onclick="location.href='designer.php?id=<?php echo $table['id']; ?>'">✏️ Edit</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="tutorial-banner">
            <span class="tutorial-icon">💡</span>
            <div class="tutorial-content">
                <h3>New to Database Builder?</h3>
                <p>Take our 5-minute interactive tutorial to learn how to create tables, add fields, and manage your data like a pro.</p>
            </div>
            <button class="btn btn-primary">▶️ Start Tutorial</button>
        </div>
    </div>
</body>
</html>
