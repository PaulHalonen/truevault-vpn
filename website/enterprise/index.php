<?php
require_once 'config.php';

$stats = getEnterpriseStats();
$clients = getClients(true);
$activeProjects = getProjects(null, 'active');
$recentInvoices = getInvoices(null, null);
$recentInvoices = array_slice($recentInvoices, 0, 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Business Hub - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1600px; margin: 0 auto; padding: 2rem; }
        .header { text-align: center; margin-bottom: 3rem; }
        .header h1 { font-size: 3rem; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 0.5rem; }
        .header p { color: #888; font-size: 1.2rem; }
        .nav { display: flex; gap: 1rem; justify-content: center; margin-bottom: 3rem; flex-wrap: wrap; }
        .nav-btn { padding: 0.75rem 1.5rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; text-decoration: none; transition: 0.3s; }
        .nav-btn:hover, .nav-btn.active { background: rgba(0,217,255,0.2); border-color: #00d9ff; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 3rem; }
        .stat-card { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; text-align: center; }
        .stat-value { font-size: 3rem; font-weight: 700; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-label { color: #888; font-size: 0.95rem; margin-top: 0.5rem; }
        .section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .section-title { font-size: 1.8rem; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; }
        .btn-primary:hover { transform: translateY(-2px); }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
        .card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 1.5rem; transition: 0.3s; }
        .card:hover { transform: translateY(-3px); border-color: #00d9ff; }
        .card-title { font-size: 1.3rem; font-weight: 700; margin-bottom: 0.5rem; color: #00d9ff; }
        .card-subtitle { color: #888; font-size: 0.9rem; margin-bottom: 1rem; }
        .card-meta { display: flex; justify-content: space-between; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); font-size: 0.85rem; color: #888; }
        .status-badge { padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
        .status-active { background: rgba(0,255,136,0.2); color: #00ff88; }
        .status-sent { background: rgba(0,217,255,0.2); color: #00d9ff; }
        .status-paid { background: rgba(0,255,136,0.2); color: #00ff88; }
        .status-overdue { background: rgba(255,100,100,0.2); color: #ff6464; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { background: rgba(255,255,255,0.05); padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid rgba(255,255,255,0.1); }
        .table td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🏢 Enterprise Business Hub</h1>
        <p>Comprehensive client & project management</p>
    </div>

    <div class="nav">
        <a href="/enterprise/" class="nav-btn active">Dashboard</a>
        <a href="/enterprise/clients.php" class="nav-btn">Clients</a>
        <a href="/enterprise/projects.php" class="nav-btn">Projects</a>
        <a href="/enterprise/time-tracking.php" class="nav-btn">Time Tracking</a>
        <a href="/enterprise/invoices.php" class="nav-btn">Invoicing</a>
        <a href="/enterprise/documents.php" class="nav-btn">Documents</a>
        <a href="/enterprise/reports.php" class="nav-btn">Reports</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['active_clients'] ?></div>
            <div class="stat-label">Active Clients</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['active_projects'] ?></div>
            <div class="stat-label">Active Projects</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">$<?= number_format($stats['total_revenue'], 0) ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">$<?= number_format($stats['outstanding'], 0) ?></div>
            <div class="stat-label">Outstanding</div>
        </div>
    </div>

    <div class="section">
        <div class="section-header">
            <div class="section-title">🚀 Active Projects (<?= count($activeProjects) ?>)</div>
            <a href="/enterprise/projects.php?action=new" class="btn btn-primary">+ New Project</a>
        </div>
        <div class="grid">
            <?php foreach (array_slice($activeProjects, 0, 6) as $project): ?>
                <div class="card">
                    <div class="card-title"><?= htmlspecialchars($project['project_name']) ?></div>
                    <div class="card-subtitle">Client: <?= htmlspecialchars($project['company_name']) ?></div>
                    <div style="margin: 1rem 0;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.85rem;">
                            <span>Progress</span>
                            <span><?= $project['completion_percent'] ?>%</span>
                        </div>
                        <div style="height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; background: linear-gradient(90deg, #00d9ff, #00ff88); width: <?= $project['completion_percent'] ?>%;"></div>
                        </div>
                    </div>
                    <div class="card-meta">
                        <span class="status-badge status-<?= $project['status'] ?>"><?= strtoupper($project['status']) ?></span>
                        <span><?= $project['project_type'] === 'fixed_price' ? '$' . number_format($project['budget']) : 'Hourly' ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="section">
        <div class="section-header">
            <div class="section-title">📄 Recent Invoices</div>
            <a href="/enterprise/invoices.php?action=new" class="btn btn-primary">+ New Invoice</a>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentInvoices as $invoice): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($invoice['invoice_number']) ?></strong></td>
                        <td><?= htmlspecialchars($invoice['company_name']) ?></td>
                        <td><?= date('M j, Y', strtotime($invoice['invoice_date'])) ?></td>
                        <td>$<?= number_format($invoice['total_amount'], 2) ?></td>
                        <td><span class="status-badge status-<?= $invoice['status'] ?>"><?= strtoupper($invoice['status']) ?></span></td>
                        <td>
                            <button class="btn btn-primary" style="padding: 0.5rem 1rem;">View</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
