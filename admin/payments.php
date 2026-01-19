<?php
require_once 'config.php';
requireAdminLogin();

$admin = getCurrentAdmin();
$db = getAdminDB();

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Search & filters
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(p.transaction_id LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($statusFilter) {
    $where[] = "p.status = ?";
    $params[] = $statusFilter;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get payments with user info
$stmt = $db->prepare("
    SELECT p.*, u.email, u.first_name, u.last_name
    FROM payments p
    LEFT JOIN users u ON p.user_id = u.id
    $whereClause
    ORDER BY p.date DESC 
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM payments p LEFT JOIN users u ON p.user_id = u.id $whereClause");
$stmt->execute($params);
$totalPayments = $stmt->fetch()['count'];
$totalPages = ceil($totalPayments / $perPage);

// Get revenue stats
$stmt = $db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
$totalRevenue = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed' AND date >= date('now', 'start of month')");
$monthlyRevenue = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'");
$pendingCount = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - Admin</title>
    <style>
        body { background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; font-family: -apple-system, sans-serif; margin: 0; padding: 0; }
        .page-container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .back-btn { padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; text-decoration: none; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: rgba(255,255,255,0.05); border-radius: 8px; padding: 1.5rem; text-align: center; }
        .stat-value { font-size: 2rem; font-weight: 700; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-label { color: #888; font-size: 0.85rem; margin-top: 0.5rem; }
        .toolbar { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .search-box { flex: 1; min-width: 250px; }
        .search-box input { width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; }
        select { padding: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; }
        .payments-table { width: 100%; background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 1rem; border-bottom: 2px solid #00d9ff; font-weight: 600; white-space: nowrap; }
        td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        tr:hover { background: rgba(255,255,255,0.05); }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; display: inline-block; }
        .status-completed { background: rgba(0,255,136,0.2); color: #00ff88; }
        .status-pending { background: rgba(255,200,100,0.2); color: #ffb84d; }
        .status-failed { background: rgba(255,100,100,0.2); color: #ff6464; }
        .status-refunded { background: rgba(150,150,150,0.2); color: #999; }
        .actions button { padding: 0.5rem 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 6px; cursor: pointer; }
        .actions button:hover { background: rgba(255,255,255,0.1); border-color: #00d9ff; }
        .pagination { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; }
        .pagination button { padding: 0.5rem 1rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 6px; cursor: pointer; }
        .pagination button:disabled { opacity: 0.3; cursor: not-allowed; }
        .amount { font-weight: 700; color: #00ff88; }
    </style>
</head>
<body>
<div class="page-container">
    <div class="page-header">
        <div>
            <a href="/admin/index.php" class="back-btn">← Dashboard</a>
            <h1 style="display: inline; margin-left: 1rem;">Payment History</h1>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-value">$<?= number_format($totalRevenue, 2) ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">$<?= number_format($monthlyRevenue, 2) ?></div>
            <div class="stat-label">This Month</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= number_format($totalPayments) ?></div>
            <div class="stat-label">Total Transactions</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $pendingCount ?></div>
            <div class="stat-label">Pending</div>
        </div>
    </div>

    <div class="toolbar">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search by transaction ID or email..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <select id="statusFilter">
            <option value="">All Statuses</option>
            <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
            <option value="refunded" <?= $statusFilter === 'refunded' ? 'selected' : '' ?>>Refunded</option>
        </select>
    </div>

    <div class="payments-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Transaction ID</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Currency</th>
                    <th>Status</th>
                    <th>Payment Method</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: #666; padding: 3rem;">No payments found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= $payment['id'] ?></td>
                            <td><code><?= htmlspecialchars(substr($payment['transaction_id'], 0, 16)) ?>...</code></td>
                            <td><?= htmlspecialchars($payment['email']) ?></td>
                            <td class="amount">$<?= number_format($payment['amount'], 2) ?></td>
                            <td><?= strtoupper($payment['currency']) ?></td>
                            <td>
                                <span class="status-badge status-<?= $payment['status'] ?>">
                                    <?= ucfirst($payment['status']) ?>
                                </span>
                            </td>
                            <td><?= ucfirst($payment['payment_method'] ?? 'PayPal') ?></td>
                            <td><?= date('M j, Y g:i A', strtotime($payment['date'])) ?></td>
                            <td class="actions">
                                <button onclick="viewPayment('<?= $payment['transaction_id'] ?>')">View</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <div>Showing <?= $offset + 1 ?>-<?= min($offset + $perPage, $totalPayments) ?> of <?= $totalPayments ?></div>
            <div>
                <button <?= $page <= 1 ? 'disabled' : '' ?> onclick="changePage(<?= $page - 1 ?>)">← Previous</button>
                <button <?= $page >= $totalPages ? 'disabled' : '' ?> onclick="changePage(<?= $page + 1 ?>)">Next →</button>
            </div>
        </div>
    </div>
</div>

<script>
function changePage(page) {
    const url = new URL(window.location);
    url.searchParams.set('page', page);
    window.location = url;
}

document.getElementById('searchInput').addEventListener('keyup', (e) => {
    if (e.key === 'Enter') {
        const url = new URL(window.location);
        url.searchParams.set('search', e.target.value);
        url.searchParams.set('page', 1);
        window.location = url;
    }
});

document.getElementById('statusFilter').addEventListener('change', (e) => {
    const url = new URL(window.location);
    url.searchParams.set('status', e.target.value);
    url.searchParams.set('page', 1);
    window.location = url;
});

function viewPayment(transactionId) {
    alert('View payment details - Transaction: ' + transactionId);
}
</script>
</body>
</html>
