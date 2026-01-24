<?php
/**
 * TrueVault VPN - Data Management Interface
 * Part 13 - Task 13.6
 * Spreadsheet-like view for table data
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_BUILDER', DB_PATH . 'builder.db');

$tableId = isset($_GET['table']) ? intval($_GET['table']) : 0;
if (!$tableId) {
    header('Location: index.php');
    exit;
}

$db = new SQLite3(DB_BUILDER);
$db->enableExceptions(true);

// Get table info
$stmt = $db->prepare("SELECT * FROM custom_tables WHERE id = ?");
$stmt->bindValue(1, $tableId, SQLITE3_INTEGER);
$result = $stmt->execute();
$table = $result->fetchArray(SQLITE3_ASSOC);

if (!$table) {
    header('Location: index.php');
    exit;
}

// Get fields
$stmt = $db->prepare("SELECT * FROM custom_fields WHERE table_id = ? ORDER BY sort_order");
$stmt->bindValue(1, $tableId, SQLITE3_INTEGER);
$result = $stmt->execute();
$fields = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $fields[] = $row;
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM data_{$table['table_name']}";
if ($search && !empty($fields)) {
    $searchConditions = [];
    foreach ($fields as $f) {
        $searchConditions[] = "{$f['field_name']} LIKE '%' || :search || '%'";
    }
    $countSql .= " WHERE " . implode(' OR ', $searchConditions);
}
$stmt = $db->prepare($countSql);
if ($search) {
    $stmt->bindValue(':search', $search, SQLITE3_TEXT);
}
$result = $stmt->execute();
$countRow = $result->fetchArray(SQLITE3_ASSOC);
$totalRecords = $countRow['total'] ?? 0;
$totalPages = ceil($totalRecords / $perPage);

// Get records
$dataSql = "SELECT * FROM data_{$table['table_name']}";
if ($search && !empty($fields)) {
    $searchConditions = [];
    foreach ($fields as $f) {
        $searchConditions[] = "{$f['field_name']} LIKE '%' || :search || '%'";
    }
    $dataSql .= " WHERE " . implode(' OR ', $searchConditions);
}
$dataSql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($dataSql);
if ($search) {
    $stmt->bindValue(':search', $search, SQLITE3_TEXT);
}
$stmt->bindValue(':limit', $perPage, SQLITE3_INTEGER);
$stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
$result = $stmt->execute();

$records = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $records[] = $row;
}

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($table['display_name']) ?> - Data View</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .back-link { color: #00d9ff; text-decoration: none; }
        .header-title { display: flex; align-items: center; gap: 10px; }
        .header-title h2 { font-size: 1.2rem; }
        .header-actions { display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
        .btn-danger { background: rgba(255,80,80,0.2); color: #ff5050; }
        .btn:hover { transform: translateY(-2px); }
        .container { padding: 20px; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .search-box { display: flex; gap: 10px; }
        .search-box input { padding: 10px 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; width: 250px; }
        .search-box input:focus { outline: none; border-color: #00d9ff; }
        .bulk-actions { display: flex; gap: 10px; }
        .table-wrapper { overflow-x: auto; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px solid rgba(255,255,255,0.08); }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
        th { background: rgba(255,255,255,0.03); color: #888; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; position: sticky; top: 0; }
        th.sortable { cursor: pointer; }
        th.sortable:hover { color: #00d9ff; }
        tr:hover { background: rgba(255,255,255,0.03); }
        td { font-size: 0.9rem; }
        td input[type="checkbox"] { width: 18px; height: 18px; }
        .cell-edit { background: transparent; border: 1px solid transparent; color: #fff; padding: 5px; width: 100%; border-radius: 4px; }
        .cell-edit:hover { border-color: rgba(255,255,255,0.2); }
        .cell-edit:focus { outline: none; border-color: #00d9ff; background: rgba(0,217,255,0.1); }
        .row-actions { display: flex; gap: 5px; }
        .row-actions button { padding: 5px 10px; font-size: 0.8rem; }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px; }
        .pagination a, .pagination span { padding: 8px 15px; background: rgba(255,255,255,0.05); border-radius: 6px; text-decoration: none; color: #888; }
        .pagination a:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .pagination .active { background: #00d9ff; color: #0f0f1a; }
        .info { color: #666; font-size: 0.85rem; }
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state .icon { font-size: 4rem; margin-bottom: 20px; }
        
        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #1a1a2e; border-radius: 16px; padding: 30px; width: 600px; max-width: 90%; max-height: 80vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .modal-close { background: none; border: none; color: #888; font-size: 1.5rem; cursor: pointer; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #aaa; font-size: 0.9rem; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #00d9ff; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px; }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" class="back-link">⬅️ Back to Tables</a>
        <div class="header-title">
            <span style="font-size:1.5rem"><?= htmlspecialchars($table['icon']) ?></span>
            <h2><?= htmlspecialchars($table['display_name']) ?></h2>
            <span class="info">(<?= $totalRecords ?> records)</span>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="exportData()">📤 Export</button>
            <a href="import.php?table=<?= $tableId ?>" class="btn btn-secondary">📥 Import</a>
            <button class="btn btn-primary" onclick="openAddModal()">+ Add Record</button>
        </div>
    </div>
    
    <div class="container">
        <div class="toolbar">
            <form class="search-box" method="GET">
                <input type="hidden" name="table" value="<?= $tableId ?>">
                <input type="text" name="search" placeholder="🔍 Search records..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-secondary">Search</button>
            </form>
            <div class="bulk-actions">
                <span class="info">Selected: <span id="selectedCount">0</span></span>
                <button class="btn btn-secondary" onclick="bulkEdit()" disabled id="bulkEditBtn">✏️ Edit</button>
                <button class="btn btn-danger" onclick="bulkDelete()" disabled id="bulkDeleteBtn">🗑️ Delete</button>
            </div>
        </div>
        
        <?php if (empty($records)): ?>
        <div class="empty-state">
            <div class="icon">📋</div>
            <h3>No Records</h3>
            <p>This table is empty. Add your first record to get started.</p>
            <button class="btn btn-primary" onclick="openAddModal()">+ Add First Record</button>
        </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                        <th class="sortable">ID</th>
                        <?php foreach ($fields as $field): ?>
                        <th class="sortable"><?= htmlspecialchars($field['display_name']) ?></th>
                        <?php endforeach; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record): ?>
                    <tr data-id="<?= $record['id'] ?>">
                        <td><input type="checkbox" class="row-select" value="<?= $record['id'] ?>" onchange="updateBulkButtons()"></td>
                        <td><?= $record['id'] ?></td>
                        <?php foreach ($fields as $field): ?>
                        <td>
                            <input type="text" class="cell-edit" 
                                   data-field="<?= htmlspecialchars($field['field_name']) ?>" 
                                   data-id="<?= $record['id'] ?>"
                                   value="<?= htmlspecialchars($record[$field['field_name']] ?? '') ?>"
                                   onblur="saveCell(this)">
                        </td>
                        <?php endforeach; ?>
                        <td class="row-actions">
                            <button class="btn btn-secondary" onclick="editRecord(<?= $record['id'] ?>)">✏️</button>
                            <button class="btn btn-danger" onclick="deleteRecord(<?= $record['id'] ?>)">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?table=<?= $tableId ?>&page=<?= $page-1 ?>&search=<?= urlencode($search) ?>">◄ Prev</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
            <a href="?table=<?= $tableId ?>&page=<?= $i ?>&search=<?= urlencode($search) ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?table=<?= $tableId ?>&page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">Next ►</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Add/Edit Record Modal -->
    <div class="modal" id="recordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">➕ Add Record</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="recordForm" method="POST" action="api/data.php">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="table_name" value="<?= htmlspecialchars($table['table_name']) ?>">
                <input type="hidden" name="record_id" id="recordId" value="">
                
                <?php foreach ($fields as $field): ?>
                <div class="form-group">
                    <label><?= htmlspecialchars($field['display_name']) ?> <?= $field['is_required'] ? '*' : '' ?></label>
                    <?php if ($field['field_type'] === 'textarea'): ?>
                    <textarea name="<?= htmlspecialchars($field['field_name']) ?>" rows="3" 
                              placeholder="<?= htmlspecialchars($field['placeholder']) ?>"
                              <?= $field['is_required'] ? 'required' : '' ?>></textarea>
                    <?php elseif ($field['field_type'] === 'dropdown' && $field['options']): ?>
                    <select name="<?= htmlspecialchars($field['field_name']) ?>" <?= $field['is_required'] ? 'required' : '' ?>>
                        <option value="">Select...</option>
                        <?php foreach (explode("\n", $field['options']) as $opt): ?>
                        <option value="<?= htmlspecialchars(trim($opt)) ?>"><?= htmlspecialchars(trim($opt)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php elseif ($field['field_type'] === 'checkbox'): ?>
                    <input type="checkbox" name="<?= htmlspecialchars($field['field_name']) ?>" value="1">
                    <?php else: ?>
                    <input type="<?= $field['field_type'] === 'email' ? 'email' : ($field['field_type'] === 'number' ? 'number' : 'text') ?>" 
                           name="<?= htmlspecialchars($field['field_name']) ?>"
                           placeholder="<?= htmlspecialchars($field['placeholder']) ?>"
                           <?= $field['is_required'] ? 'required' : '' ?>>
                    <?php endif; ?>
                    <?php if ($field['help_text']): ?>
                    <small style="color:#666"><?= htmlspecialchars($field['help_text']) ?></small>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">💾 Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const tableId = <?= $tableId ?>;
        const tableName = '<?= $table['table_name'] ?>';
        
        function openAddModal() {
            document.getElementById('modalTitle').textContent = '➕ Add Record';
            document.getElementById('recordForm').reset();
            document.getElementById('recordId').value = '';
            document.querySelector('input[name="action"]').value = 'create';
            document.getElementById('recordModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('recordModal').classList.remove('active');
        }
        
        function editRecord(id) {
            document.getElementById('modalTitle').textContent = '✏️ Edit Record';
            document.getElementById('recordId').value = id;
            document.querySelector('input[name="action"]').value = 'update';
            
            // Load record data
            fetch('api/data.php?table=' + tableName + '&id=' + id)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.record) {
                        Object.keys(data.record).forEach(key => {
                            const input = document.querySelector(`[name="${key}"]`);
                            if (input) {
                                if (input.type === 'checkbox') {
                                    input.checked = data.record[key] == 1;
                                } else {
                                    input.value = data.record[key] || '';
                                }
                            }
                        });
                    }
                });
            
            document.getElementById('recordModal').classList.add('active');
        }
        
        function deleteRecord(id) {
            if (confirm('Delete this record? This cannot be undone.')) {
                fetch('api/data.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({table_name: tableName, record_id: id})
                }).then(r => r.json()).then(data => {
                    if (data.success) location.reload();
                    else alert(data.error || 'Failed to delete');
                });
            }
        }
        
        function saveCell(input) {
            const id = input.dataset.id;
            const field = input.dataset.field;
            const value = input.value;
            
            fetch('api/data.php', {
                method: 'PUT',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({table_name: tableName, record_id: id, field: field, value: value})
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    input.style.borderColor = '#00ff88';
                    setTimeout(() => input.style.borderColor = '', 1000);
                } else {
                    input.style.borderColor = '#ff5050';
                }
            });
        }
        
        function toggleSelectAll() {
            const checked = document.getElementById('selectAll').checked;
            document.querySelectorAll('.row-select').forEach(cb => cb.checked = checked);
            updateBulkButtons();
        }
        
        function updateBulkButtons() {
            const selected = document.querySelectorAll('.row-select:checked').length;
            document.getElementById('selectedCount').textContent = selected;
            document.getElementById('bulkEditBtn').disabled = selected === 0;
            document.getElementById('bulkDeleteBtn').disabled = selected === 0;
        }
        
        function bulkDelete() {
            const ids = Array.from(document.querySelectorAll('.row-select:checked')).map(cb => cb.value);
            if (ids.length && confirm(`Delete ${ids.length} records? This cannot be undone.`)) {
                fetch('api/data.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({table_name: tableName, record_ids: ids})
                }).then(r => r.json()).then(data => {
                    if (data.success) location.reload();
                    else alert(data.error || 'Failed to delete');
                });
            }
        }
        
        function exportData() {
            window.location.href = 'api/export.php?table=' + tableName;
        }
        
        // Close modal on outside click
        document.getElementById('recordModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
