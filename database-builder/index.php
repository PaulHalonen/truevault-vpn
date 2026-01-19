<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Builder - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        header h1 { font-size: 2rem; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .btn { padding: 0.75rem 1.5rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; transition: 0.3s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,217,255,0.4); }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
        .tables-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .table-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 1.5rem; cursor: pointer; transition: 0.3s; }
        .table-card:hover { transform: translateY(-3px); border-color: #00d9ff; box-shadow: 0 10px 30px rgba(0,217,255,0.2); }
        .table-icon { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .table-name { font-size: 1.3rem; margin-bottom: 0.5rem; color: #fff; font-weight: 600; }
        .table-description { font-size: 0.9rem; color: #888; margin-bottom: 1rem; }
        .table-stats { display: flex; gap: 1rem; font-size: 0.85rem; color: #666; }
        .table-actions { display: flex; gap: 0.5rem; margin-top: 1rem; }
        .table-actions button { padding: 0.5rem 1rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: 0.3s; }
        .table-actions button:hover { background: rgba(255,255,255,0.1); border-color: #00d9ff; }
        .empty-state { text-align: center; padding: 4rem 2rem; color: #666; }
        .empty-state .icon { font-size: 4rem; margin-bottom: 1rem; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #1a1a2e; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; border: 1px solid rgba(255,255,255,0.1); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h2 { font-size: 1.5rem; }
        .modal-close { background: transparent; border: none; color: #888; font-size: 1.5rem; cursor: pointer; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #ccc; font-weight: 600; }
        .form-group input, .form-group textarea { width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; font-size: 1rem; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #00d9ff; }
        .icon-picker { display: grid; grid-template-columns: repeat(8, 1fr); gap: 0.5rem; margin-top: 0.5rem; }
        .icon-option { padding: 0.5rem; background: rgba(255,255,255,0.05); border: 2px solid transparent; border-radius: 6px; cursor: pointer; text-align: center; font-size: 1.5rem; transition: 0.3s; }
        .icon-option:hover, .icon-option.selected { border-color: #00d9ff; background: rgba(0,217,255,0.1); }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>🗄️ Database Builder</h1>
        <button class="btn" onclick="openCreateModal()">+ Create Table</button>
    </header>

    <div id="tablesContainer">
        <div class="empty-state">
            <div class="icon">📊</div>
            <p>Loading tables...</p>
        </div>
    </div>
</div>

<!-- Create Table Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Create New Table</h2>
            <button class="modal-close" onclick="closeCreateModal()">×</button>
        </div>
        <form id="createTableForm">
            <div class="form-group">
                <label for="tableName">Table Name *</label>
                <input type="text" id="tableName" placeholder="e.g., customers, products, orders" required>
                <small style="color: #666; font-size: 0.85rem;">Technical name (lowercase, no spaces)</small>
            </div>
            <div class="form-group">
                <label for="displayName">Display Name *</label>
                <input type="text" id="displayName" placeholder="e.g., Customers, Products, Orders" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" rows="3" placeholder="What this table is for..."></textarea>
            </div>
            <div class="form-group">
                <label>Icon</label>
                <div class="icon-picker" id="iconPicker"></div>
                <input type="hidden" id="selectedIcon" value="📊">
            </div>
            <button type="submit" class="btn" style="width: 100%;">Create Table</button>
        </form>
    </div>
</div>

<script>
const icons = ['📊', '👥', '📦', '🛒', '💼', '📝', '📅', '💰', '🏢', '📞', '📧', '🌐', '⚙️', '📈', '📉', '🎯'];
let selectedIcon = '📊';

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadTables();
    renderIconPicker();
});

function renderIconPicker() {
    const picker = document.getElementById('iconPicker');
    picker.innerHTML = icons.map(icon => 
        `<div class="icon-option ${icon === selectedIcon ? 'selected' : ''}" onclick="selectIcon('${icon}')">${icon}</div>`
    ).join('');
}

function selectIcon(icon) {
    selectedIcon = icon;
    document.getElementById('selectedIcon').value = icon;
    renderIconPicker();
}

async function loadTables() {
    try {
        const response = await fetch('api/tables.php?action=list');
        const data = await response.json();
        
        if (data.success && data.tables.length > 0) {
            renderTables(data.tables);
        } else {
            document.getElementById('tablesContainer').innerHTML = `
                <div class="empty-state">
                    <div class="icon">📊</div>
                    <p>No tables yet. Create your first table to get started!</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading tables:', error);
        document.getElementById('tablesContainer').innerHTML = `
            <div class="empty-state">
                <div class="icon">❌</div>
                <p>Error loading tables. Please refresh the page.</p>
            </div>
        `;
    }
}

function renderTables(tables) {
    const html = `
        <div class="tables-grid">
            ${tables.map(table => `
                <div class="table-card" onclick="openTable(${table.id})">
                    <div class="table-icon">${table.icon}</div>
                    <div class="table-name">${table.display_name}</div>
                    <div class="table-description">${table.description || 'No description'}</div>
                    <div class="table-stats">
                        <span>📊 ${table.record_count || 0} records</span>
                    </div>
                    <div class="table-actions" onclick="event.stopPropagation()">
                        <button onclick="designTable(${table.id})">⚙️ Design</button>
                        <button onclick="viewData(${table.id})">📝 Data</button>
                        <button onclick="deleteTable(${table.id}, '${table.display_name}')">🗑️ Delete</button>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
    document.getElementById('tablesContainer').innerHTML = html;
}

function openCreateModal() {
    document.getElementById('createModal').classList.add('active');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.remove('active');
    document.getElementById('createTableForm').reset();
    selectedIcon = '📊';
    renderIconPicker();
}

document.getElementById('createTableForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const tableName = document.getElementById('tableName').value.toLowerCase().replace(/[^a-z0-9_]/g, '');
    const displayName = document.getElementById('displayName').value;
    const description = document.getElementById('description').value;
    const icon = document.getElementById('selectedIcon').value;
    
    try {
        const response = await fetch('api/tables.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ table_name: tableName, display_name: displayName, description, icon })
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeCreateModal();
            loadTables();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        alert('Error creating table');
        console.error(error);
    }
});

function openTable(tableId) {
    window.location.href = `data-manager.php?table_id=${tableId}`;
}

function designTable(tableId) {
    window.location.href = `designer.php?table_id=${tableId}`;
}

function viewData(tableId) {
    window.location.href = `data-manager.php?table_id=${tableId}`;
}

async function deleteTable(tableId, tableName) {
    if (!confirm(`Delete table "${tableName}"? This will delete all data and cannot be undone.`)) {
        return;
    }
    
    try {
        const response = await fetch(`api/tables.php?action=delete&id=${tableId}`, { method: 'POST' });
        const data = await response.json();
        
        if (data.success) {
            loadTables();
        } else {
            alert('Error deleting table');
        }
    } catch (error) {
        alert('Error deleting table');
        console.error(error);
    }
}
</script>
</body>
</html>
