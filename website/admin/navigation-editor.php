<?php
/**
 * TrueVault VPN - Navigation Menu Editor
 * 
 * Admin interface for managing site navigation menus
 * 
 * FEATURES:
 * - Add/edit/delete menu items
 * - Reorder with drag-and-drop
 * - Multiple menus (header, footer, sidebar)
 * - Nested menu items (sub-menus)
 * - External/internal links
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Initialize
define('TRUEVAULT_INIT', true);
session_start();

require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

// Check authentication
if (!Auth::isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// Get database connection
$db = Database::getInstance();
$themesConn = $db->getConnection('themes');

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'add_item':
            $stmt = $themesConn->prepare("
                INSERT INTO navigation_menus (menu_location, label, url, parent_id, sort_order, is_active)
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $_POST['menu_location'],
                $_POST['label'],
                $_POST['url'],
                intval($_POST['parent_id'] ?? 0),
                intval($_POST['sort_order'] ?? 0)
            ]);
            echo json_encode(['success' => true, 'id' => $themesConn->lastInsertId()]);
            exit;
            
        case 'update_item':
            $stmt = $themesConn->prepare("
                UPDATE navigation_menus 
                SET label = ?, url = ?, parent_id = ?, sort_order = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['label'],
                $_POST['url'],
                intval($_POST['parent_id'] ?? 0),
                intval($_POST['sort_order'] ?? 0),
                intval($_POST['id'])
            ]);
            echo json_encode(['success' => true]);
            exit;
            
        case 'delete_item':
            $stmt = $themesConn->prepare("DELETE FROM navigation_menus WHERE id = ?");
            $stmt->execute([intval($_POST['id'])]);
            echo json_encode(['success' => true]);
            exit;
            
        case 'toggle_active':
            $stmt = $themesConn->prepare("UPDATE navigation_menus SET is_active = ? WHERE id = ?");
            $stmt->execute([intval($_POST['is_active']), intval($_POST['id'])]);
            echo json_encode(['success' => true]);
            exit;
            
        case 'reorder':
            $order = json_decode($_POST['order'], true);
            $stmt = $themesConn->prepare("UPDATE navigation_menus SET sort_order = ? WHERE id = ?");
            foreach ($order as $index => $id) {
                $stmt->execute([$index, $id]);
            }
            echo json_encode(['success' => true]);
            exit;
    }
}

// Get all menu items
$stmt = $themesConn->query("SELECT * FROM navigation_menus ORDER BY menu_location, sort_order ASC");
$allItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by location
$menus = ['header' => [], 'footer' => [], 'sidebar' => []];
foreach ($allItems as $item) {
    $menus[$item['menu_location']][] = $item;
}

$pageTitle = 'Navigation Menus';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - TrueVault Admin</title>
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 28px;
            color: #333;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .menus-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .menu-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }
        
        .menu-header h2 {
            font-size: 20px;
            color: #333;
        }
        
        .menu-items-list {
            min-height: 100px;
        }
        
        .menu-item {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            cursor: move;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }
        
        .menu-item:hover {
            border-color: #667eea;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
        }
        
        .menu-item.sortable-ghost {
            opacity: 0.4;
        }
        
        .menu-item-content {
            flex: 1;
        }
        
        .menu-item-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 3px;
        }
        
        .menu-item-url {
            font-size: 12px;
            color: #6b7280;
            font-family: monospace;
        }
        
        .menu-item-actions {
            display: flex;
            gap: 5px;
        }
        
        .icon-btn {
            width: 28px;
            height: 28px;
            border: none;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .icon-btn:hover {
            background: #667eea;
            color: white;
        }
        
        .icon-btn.danger:hover {
            background: #ef4444;
        }
        
        .btn-add {
            width: 100%;
            margin-top: 15px;
            background: #f9fafb;
            border: 2px dashed #d1d5db;
            color: #6b7280;
        }
        
        .btn-add:hover {
            background: #eff6ff;
            border-color: #667eea;
            color: #667eea;
            border-style: solid;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            padding: 20px;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 100%;
            padding: 30px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .modal-header h2 {
            font-size: 24px;
            color: #333;
        }
        
        .close-modal {
            font-size: 28px;
            cursor: pointer;
            color: #999;
            line-height: 1;
        }
        
        .close-modal:hover {
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .form-group input[type="text"],
        .form-group select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
            flex: 1;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>🧭 Navigation Menus</h1>
                <p style="color: #666; margin-top: 5px;">Manage site navigation menus</p>
            </div>
            <a href="/admin/" class="btn btn-primary">← Back to Admin</a>
        </div>

        <div class="menus-grid">
            <?php foreach (['header' => 'Header Menu', 'footer' => 'Footer Menu', 'sidebar' => 'Sidebar Menu'] as $location => $title): ?>
            <div class="menu-card">
                <div class="menu-header">
                    <h2><?= $title ?></h2>
                    <span style="font-size: 12px; color: #999;"><?= count($menus[$location]) ?> items</span>
                </div>
                
                <div class="menu-items-list" id="menu-<?= $location ?>" data-location="<?= $location ?>">
                    <?php if (empty($menus[$location])): ?>
                        <p style="text-align: center; color: #999; padding: 20px;">No menu items yet</p>
                    <?php else: ?>
                        <?php foreach ($menus[$location] as $item): ?>
                        <div class="menu-item" data-id="<?= $item['id'] ?>">
                            <div class="menu-item-content">
                                <div class="menu-item-label"><?= htmlspecialchars($item['label']) ?></div>
                                <div class="menu-item-url"><?= htmlspecialchars($item['url']) ?></div>
                            </div>
                            <div class="menu-item-actions">
                                <button class="icon-btn" onclick="editItem(<?= $item['id'] ?>)" title="Edit">✏️</button>
                                <button class="icon-btn danger" onclick="deleteItem(<?= $item['id'] ?>)" title="Delete">🗑️</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <button class="btn btn-add" onclick="addItem('<?= $location ?>')">
                    + Add Menu Item
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="itemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Menu Item</h2>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            
            <form id="itemForm">
                <input type="hidden" id="item_id" name="id">
                <input type="hidden" id="menu_location" name="menu_location">
                
                <div class="form-group">
                    <label>Label (Display Text)</label>
                    <input type="text" id="item_label" name="label" required>
                </div>
                
                <div class="form-group">
                    <label>URL</label>
                    <input type="text" id="item_url" name="url" placeholder="/page-slug or https://example.com" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">💾 Save</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentLocation = '';
        let currentItemId = null;
        
        // Initialize Sortable for each menu
        document.querySelectorAll('.menu-items-list').forEach(list => {
            Sortable.create(list, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function(evt) {
                    const location = evt.to.dataset.location;
                    const items = evt.to.querySelectorAll('.menu-item');
                    const order = Array.from(items).map(item => parseInt(item.dataset.id));
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=reorder&order=${JSON.stringify(order)}`
                    });
                }
            });
        });
        
        // Add new item
        function addItem(location) {
            currentLocation = location;
            currentItemId = null;
            document.getElementById('modalTitle').textContent = 'Add Menu Item';
            document.getElementById('itemForm').reset();
            document.getElementById('menu_location').value = location;
            document.getElementById('itemModal').classList.add('active');
        }
        
        // Edit item
        function editItem(id) {
            currentItemId = id;
            document.getElementById('modalTitle').textContent = 'Edit Menu Item';
            document.getElementById('item_id').value = id;
            
            // Get item data
            const item = document.querySelector(`[data-id="${id}"]`);
            const label = item.querySelector('.menu-item-label').textContent;
            const url = item.querySelector('.menu-item-url').textContent;
            
            document.getElementById('item_label').value = label;
            document.getElementById('item_url').value = url;
            document.getElementById('itemModal').classList.add('active');
        }
        
        // Delete item
        function deleteItem(id) {
            if (!confirm('Delete this menu item?')) return;
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete_item&id=${id}`
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    location.reload();
                }
            });
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('itemModal').classList.remove('active');
        }
        
        // Handle form submission
        document.getElementById('itemForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', currentItemId ? 'update_item' : 'add_item');
            
            fetch(window.location.href, {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    location.reload();
                }
            });
        });
    </script>
</body>
</html>
