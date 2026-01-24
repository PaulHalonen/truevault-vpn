<?php
/**
 * TrueVault VPN - Form Submissions Viewer
 * Part 14 - Task 14.7
 * View and manage form submissions
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_FORMS', DB_PATH . 'forms.db');

$formId = isset($_GET['form']) ? intval($_GET['form']) : 0;
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));

$forms = [];
$submissions = [];
$total = 0;
$perPage = 25;

if (file_exists(DB_FORMS)) {
    $db = new SQLite3(DB_FORMS);
    $db->enableExceptions(true);
    
    // Get all forms for filter
    $result = $db->query("SELECT id, display_name, submission_count FROM forms ORDER BY display_name");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $forms[] = $row;
    }
    
    // Build query
    $where = [];
    $params = [];
    
    if ($formId) {
        $where[] = "s.form_id = ?";
        $params[] = $formId;
    }
    if ($status) {
        $where[] = "s.status = ?";
        $params[] = $status;
    }
    
    $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
    $offset = ($page - 1) * $perPage;
    
    // Count
    $countSql = "SELECT COUNT(*) FROM form_submissions s {$whereClause}";
    $countStmt = $db->prepare($countSql);
    foreach ($params as $i => $p) {
        $countStmt->bindValue($i + 1, $p, is_int($p) ? SQLITE3_INTEGER : SQLITE3_TEXT);
    }
    $total = $countStmt->execute()->fetchArray()[0];
    
    // Get submissions
    $sql = "SELECT s.*, f.display_name as form_name FROM form_submissions s JOIN forms f ON s.form_id = f.id {$whereClause} ORDER BY s.submitted_at DESC LIMIT {$perPage} OFFSET {$offset}";
    $stmt = $db->prepare($sql);
    foreach ($params as $i => $p) {
        $stmt->bindValue($i + 1, $p, is_int($p) ? SQLITE3_INTEGER : SQLITE3_TEXT);
    }
    $result = $stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $row['form_data'] = json_decode($row['form_data'], true);
        $submissions[] = $row;
    }
    
    $db->close();
}

$totalPages = ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Submissions - TrueVault Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .header h1 { font-size: 1.5rem; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; text-decoration: none; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-danger { background: rgba(255,80,80,0.2); color: #ff5050; }
        .container { padding: 25px; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .filters { display: flex; gap: 15px; align-items: center; }
        .filters select { padding: 10px 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; }
        .stats { display: flex; gap: 15px; }
        .stat { background: rgba(255,255,255,0.03); padding: 8px 15px; border-radius: 8px; font-size: 0.9rem; }
        .stat strong { color: #00d9ff; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
        th { background: rgba(255,255,255,0.03); color: #888; font-size: 0.85rem; text-transform: uppercase; }
        tr:hover { background: rgba(255,255,255,0.02); }
        .status { padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; text-transform: uppercase; }
        .status-new { background: rgba(33,150,243,0.2); color: #2196f3; }
        .status-read { background: rgba(156,39,176,0.2); color: #9c27b0; }
        .status-processed { background: rgba(76,175,80,0.2); color: #4caf50; }
        .status-spam { background: rgba(255,80,80,0.2); color: #ff5050; }
        .actions { display: flex; gap: 5px; }
        .actions button { padding: 6px 12px; font-size: 0.8rem; }
        .empty { text-align: center; padding: 60px 20px; color: #555; }
        .empty .icon { font-size: 4rem; margin-bottom: 15px; }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 25px; }
        .pagination button { padding: 8px 15px; }
        .pagination span { color: #888; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #1a1a2e; border-radius: 12px; width: 600px; max-width: 95%; max-height: 85vh; overflow: hidden; display: flex; flex-direction: column; }
        .modal-header { padding: 20px; border-bottom: 1px solid #333; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { flex: 1; overflow-y: auto; padding: 20px; }
        .modal-footer { padding: 15px 20px; border-top: 1px solid #333; display: flex; justify-content: flex-end; gap: 10px; }
        .detail-row { display: flex; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 12px 0; }
        .detail-label { width: 150px; color: #888; font-size: 0.9rem; }
        .detail-value { flex: 1; }
        .status-select { padding: 8px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: #fff; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📥 Form Submissions</h1>
        <a href="index.php" class="btn btn-secondary">⬅️ Back to Forms</a>
    </div>
    
    <div class="container">
        <div class="toolbar">
            <div class="filters">
                <select onchange="filterByForm(this.value)">
                    <option value="">All Forms</option>
                    <?php foreach ($forms as $f): ?>
                    <option value="<?= $f['id'] ?>" <?= $f['id'] == $formId ? 'selected' : '' ?>><?= htmlspecialchars($f['display_name']) ?> (<?= $f['submission_count'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <select onchange="filterByStatus(this.value)">
                    <option value="">All Status</option>
                    <option value="new" <?= $status === 'new' ? 'selected' : '' ?>>New</option>
                    <option value="read" <?= $status === 'read' ? 'selected' : '' ?>>Read</option>
                    <option value="processed" <?= $status === 'processed' ? 'selected' : '' ?>>Processed</option>
                    <option value="spam" <?= $status === 'spam' ? 'selected' : '' ?>>Spam</option>
                </select>
            </div>
            <div class="stats">
                <div class="stat">Total: <strong><?= $total ?></strong></div>
            </div>
        </div>
        
        <?php if (empty($submissions)): ?>
        <div class="empty">
            <div class="icon">📭</div>
            <h3>No Submissions</h3>
            <p>Form submissions will appear here.</p>
        </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Form</th>
                        <th>Submitter</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $sub): ?>
                    <tr>
                        <td>#<?= $sub['id'] ?></td>
                        <td><?= htmlspecialchars($sub['form_name']) ?></td>
                        <td>
                            <?= htmlspecialchars($sub['submitter_name'] ?: $sub['submitter_email'] ?: 'Anonymous') ?>
                            <?php if ($sub['submitter_email']): ?>
                            <div style="font-size:0.8rem;color:#888;"><?= htmlspecialchars($sub['submitter_email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="status status-<?= $sub['status'] ?>"><?= $sub['status'] ?></span></td>
                        <td><?= date('M j, Y g:i A', strtotime($sub['submitted_at'])) ?></td>
                        <td class="actions">
                            <button class="btn btn-secondary" onclick="viewSubmission(<?= $sub['id'] ?>)">View</button>
                            <button class="btn btn-danger" onclick="deleteSubmission(<?= $sub['id'] ?>)">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <button class="btn btn-secondary" <?= $page <= 1 ? 'disabled' : '' ?> onclick="goToPage(<?= $page - 1 ?>)">◄ Prev</button>
            <span>Page <?= $page ?> of <?= $totalPages ?></span>
            <button class="btn btn-secondary" <?= $page >= $totalPages ? 'disabled' : '' ?> onclick="goToPage(<?= $page + 1 ?>)">Next ►</button>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- View Submission Modal -->
    <div class="modal" id="viewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📋 Submission Details</h3>
                <button onclick="closeModal()" style="background:none;border:none;color:#888;font-size:1.5rem;cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Filled by JS -->
            </div>
            <div class="modal-footer">
                <select class="status-select" id="statusSelect">
                    <option value="new">New</option>
                    <option value="read">Read</option>
                    <option value="processed">Processed</option>
                    <option value="spam">Spam</option>
                </select>
                <button class="btn btn-primary" onclick="updateStatus()">Update Status</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentSubmissionId = null;
        
        function filterByForm(formId) {
            const url = new URL(window.location);
            if (formId) url.searchParams.set('form', formId);
            else url.searchParams.delete('form');
            url.searchParams.delete('page');
            window.location = url;
        }
        
        function filterByStatus(status) {
            const url = new URL(window.location);
            if (status) url.searchParams.set('status', status);
            else url.searchParams.delete('status');
            url.searchParams.delete('page');
            window.location = url;
        }
        
        function goToPage(page) {
            const url = new URL(window.location);
            url.searchParams.set('page', page);
            window.location = url;
        }
        
        function viewSubmission(id) {
            currentSubmissionId = id;
            fetch('api/submissions.php?id=' + id)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const sub = data.submission;
                        let html = `
                            <div class="detail-row">
                                <div class="detail-label">Form</div>
                                <div class="detail-value">${sub.form_name}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Submitted</div>
                                <div class="detail-value">${sub.submitted_at}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">IP Address</div>
                                <div class="detail-value">${sub.submitter_ip || 'N/A'}</div>
                            </div>
                            <hr style="border-color:#333;margin:20px 0">
                            <h4 style="margin-bottom:15px;">Form Data</h4>
                        `;
                        
                        for (const [key, value] of Object.entries(sub.form_data || {})) {
                            html += `
                                <div class="detail-row">
                                    <div class="detail-label">${key}</div>
                                    <div class="detail-value">${value || '-'}</div>
                                </div>
                            `;
                        }
                        
                        document.getElementById('modalBody').innerHTML = html;
                        document.getElementById('statusSelect').value = sub.status;
                        document.getElementById('viewModal').classList.add('active');
                    }
                });
        }
        
        function closeModal() {
            document.getElementById('viewModal').classList.remove('active');
        }
        
        function updateStatus() {
            const status = document.getElementById('statusSelect').value;
            fetch('api/submissions.php', {
                method: 'PATCH',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: currentSubmissionId, status: status})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to update');
                }
            });
        }
        
        function deleteSubmission(id) {
            if (confirm('Delete this submission? This cannot be undone.')) {
                fetch('api/submissions.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id})
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert(data.error || 'Failed to delete');
                });
            }
        }
    </script>
</body>
</html>
