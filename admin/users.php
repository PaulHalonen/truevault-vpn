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
    $where[] = "(email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($statusFilter) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get users
$stmt = $db->prepare("
    SELECT * FROM users 
    $whereClause
    ORDER BY created_at DESC 
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM users $whereClause");
$stmt->execute($params);
$totalUsers = $stmt->fetch()['count'];
$totalPages = ceil($totalUsers / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin</title>
    <link rel="stylesheet" href="admin-style.css">
    <style>
        body { background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; font-family: -apple-system, sans-serif; }
        .page-container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .back-btn { padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; text-decoration: none; }
        .toolbar { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .search-box { flex: 1; min-width: 250px; }
        .search-box input { width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; }
        select { padding: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; }
        .users-table { width: 100%; background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 1rem; border-bottom: 2px solid #00d9ff; font-weight: 600; }
        td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        tr:hover { background: rgba(255,255,255,0.05); }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; display: inline-block; }
        .status-active { background: rgba(0,255,136,0.2); color: #00ff88; }
        .status-trial { background: rgba(0,217,255,0.2); color: #00d9ff; }
        .status-cancelled { background: rgba(255,100,100,0.2); color: #ff6464; }
        .actions button { padding: 0.5rem 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 6px; cursor: pointer; margin-right: 0.5rem; }
        .actions button:hover { background: rgba(255,255,255,0.1); border-color: #00d9ff; }
        .pagination { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; }
        .pagination button { padding: 0.5rem 1rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 6px; cursor: pointer; }
        .pagination button:disabled { opacity: 0.3; cursor: not-allowed; }
    </style>
</head>
<body>
<div class="page-container">
    <div class="page-header">
        <div>
            <a href="/admin/index.php" class="back-btn">← Dashboard</a>
            <h1 style="display: inline; margin-left: 1rem;">User Management</h1>
        </div>
    </div>

    <div class="toolbar">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search by email or name..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <select id="statusFilter">
            <option value="">All Statuses</option>
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="trial" <?= $statusFilter === 'trial' ? 'selected' : '' ?>>Trial</option>
            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
    </div>

    <div class="users-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Devices</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></td>
                        <td><?= ucfirst($user['plan'] ?? 'personal') ?></td>
                        <td><span class="status-badge status-<?= $user['status'] ?>"><?= ucfirst($user['status']) ?></span></td>
                        <td>
                            <?php
                            $stmt = $db->prepare("SELECT COUNT(*) as count FROM devices WHERE user_id = ?");
                            $stmt->execute([$user['id']]);
                            echo $stmt->fetch()['count'];
                            ?>
                        </td>
                        <td><?= formatDate($user['created_at']) ?></td>
                        <td class="actions">
                            <button onclick="viewUser(<?= $user['id'] ?>)">View</button>
                            <button onclick="editUser(<?= $user['id'] ?>)">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            <div>Showing <?= $offset + 1 ?>-<?= min($offset + $perPage, $totalUsers) ?> of <?= $totalUsers ?></div>
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

function viewUser(id) {
    window.location.href = `/admin/user-details.php?id=${id}`;
}

function editUser(id) {
    alert('Edit functionality coming soon');
}
</script>
</body>
</html>
