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
    $where[] = "(d.device_name LIKE ? OR d.ip_address LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($statusFilter) {
    if ($statusFilter === 'active') {
        $where[] = "d.last_connected >= datetime('now', '-7 days')";
    } elseif ($statusFilter === 'inactive') {
        $where[] = "d.last_connected < datetime('now', '-7 days')";
    }
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get devices with user info
$stmt = $db->prepare("
    SELECT d.*, u.email, u.plan, u.status as user_status
    FROM devices d
    LEFT JOIN users u ON d.user_id = u.id
    $whereClause
    ORDER BY d.last_connected DESC 
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$devices = $stmt->fetchAll();

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM devices d LEFT JOIN users u ON d.user_id = u.id $whereClause");
$stmt->execute($params);
$totalDevices = $stmt->fetch()['count'];
$totalPages = ceil($totalDevices / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Management - Admin</title>
    <style>
        body { background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; font-family: -apple-system, sans-serif; margin: 0; padding: 0; }
        .page-container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .back-btn { padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; text-decoration: none; }
        .toolbar { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .search-box { flex: 1; min-width: 250px; }
        .search-box input { width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; }
        select { padding: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; }
        .devices-table { width: 100%; background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 1rem; border-bottom: 2px solid #00d9ff; font-weight: 600; white-space: nowrap; }
        td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        tr:hover { background: rgba(255,255,255,0.05); }
        .device-icon { font-size: 1.5rem; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; display: inline-block; }
        .status-active { background: rgba(0,255,136,0.2); color: #00ff88; }
        .status-inactive { background: rgba(255,100,100,0.2); color: #ff6464; }
        .actions button { padding: 0.5rem 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 6px; cursor: pointer; margin-right: 0.5rem; }
        .actions button:hover { background: rgba(255,255,255,0.1); border-color: #00d9ff; }
        .actions button.danger { background: rgba(255,100,100,0.2); border-color: rgba(255,100,100,0.4); color: #ff6464; }
        .pagination { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; }
        .pagination button { padding: 0.5rem 1rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 6px; cursor: pointer; }
        .pagination button:disabled { opacity: 0.3; cursor: not-allowed; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: rgba(255,255,255,0.05); border-radius: 8px; padding: 1.5rem; text-align: center; }
        .stat-value { font-size: 2rem; font-weight: 700; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-label { color: #888; font-size: 0.85rem; margin-top: 0.5rem; }
    </style>
</head>
<body>
<div class="page-container">
    <div class="page-header">
        <div>
            <a href="/admin/index.php" class="back-btn">← Dashboard</a>
            <h1 style="display: inline; margin-left: 1rem;">Device Management</h1>
        </div>
    </div>

    <div class="stats-row">
        <?php
        $totalDevicesCount = $db->query("SELECT COUNT(*) as count FROM devices")->fetch()['count'];
        $activeDevicesCount = $db->query("SELECT COUNT(*) as count FROM devices WHERE last_connected >= datetime('now', '-7 days')")->fetch()['count'];
        ?>
        <div class="stat-card">
            <div class="stat-value"><?= number_format($totalDevicesCount) ?></div>
            <div class="stat-label">Total Devices</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= number_format($activeDevicesCount) ?></div>
            <div class="stat-label">Active (7 days)</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= number_format($totalDevicesCount - $activeDevicesCount) ?></div>
            <div class="stat-label">Inactive</div>
        </div>
    </div>

    <div class="toolbar">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search by device name, IP, or user email..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <select id="statusFilter">
            <option value="">All Devices</option>
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>

    <div class="devices-table">
        <table>
            <thead>
                <tr>
                    <th>Device</th>
                    <th>User</th>
                    <th>Plan</th>
                    <th>Server</th>
                    <th>IP Address</th>
                    <th>Status</th>
                    <th>Last Connected</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($devices)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #666; padding: 3rem;">No devices found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($devices as $device): ?>
                        <?php
                        $isActive = strtotime($device['last_connected']) > strtotime('-7 days');
                        $deviceIcons = [
                            'windows' => '🖥️',
                            'mac' => '🍎',
                            'linux' => '🐧',
                            'android' => '📱',
                            'ios' => '📱',
                            'router' => '🌐'
                        ];
                        $icon = $deviceIcons[strtolower($device['device_type'] ?? 'windows')] ?? '💻';
                        ?>
                        <tr>
                            <td>
                                <span class="device-icon"><?= $icon ?></span>
                                <strong><?= htmlspecialchars($device['device_name']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($device['email']) ?></td>
                            <td><?= ucfirst($device['plan'] ?? 'personal') ?></td>
                            <td><?= htmlspecialchars($device['server_location'] ?? 'N/A') ?></td>
                            <td><code><?= htmlspecialchars($device['ip_address']) ?></code></td>
                            <td>
                                <span class="status-badge <?= $isActive ? 'status-active' : 'status-inactive' ?>">
                                    <?= $isActive ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= $device['last_connected'] ? date('M j, Y g:i A', strtotime($device['last_connected'])) : 'Never' ?></td>
                            <td class="actions">
                                <button onclick="viewDevice(<?= $device['id'] ?>)">View</button>
                                <button class="danger" onclick="removeDevice(<?= $device['id'] ?>, '<?= htmlspecialchars($device['device_name']) ?>')">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <div>Showing <?= $offset + 1 ?>-<?= min($offset + $perPage, $totalDevices) ?> of <?= $totalDevices ?></div>
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

function viewDevice(id) {
    alert('View device details - ID: ' + id);
}

function removeDevice(id, name) {
    if (confirm(`Remove device "${name}"? This will disconnect the device.`)) {
        alert('Remove functionality coming soon - ID: ' + id);
    }
}
</script>
</body>
</html>
