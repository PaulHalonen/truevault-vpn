<?php
/**
 * TrueVault VPN - User Management
 * 
 * PURPOSE: Admin page to manage all user accounts
 * AUTHENTICATION: Admin or VIP tier required
 * 
 * FEATURES:
 * - List all users
 * - Search and filter
 * - Edit user details
 * - Change tier (upgrade/downgrade)
 * - Change status (active, suspended, cancelled)
 * - Delete users
 * - View user devices
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load configuration
require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/JWT.php';
require_once __DIR__ . '/../includes/Auth.php';

// Check authentication and admin access
try {
    $user = Auth::require();
    $userTier = $user['tier'] ?? 'standard';
    
    if (!in_array($userTier, ['admin', 'vip'])) {
        header('Location: /dashboard/my-devices.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: /auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - TrueVault VPN</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-title h1 {
            font-size: 28px;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .header-title p {
            color: #64748b;
            font-size: 14px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .search-bar {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .search-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .filters {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 10px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }

        .users-table {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8fafc;
        }

        th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
            font-size: 14px;
        }

        tr:hover {
            background: #f8fafc;
        }

        .tier-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .tier-standard {
            background: #e0e7ff;
            color: #3730a3;
        }

        .tier-pro {
            background: #dbeafe;
            color: #1e40af;
        }

        .tier-vip {
            background: #fef3c7;
            color: #92400e;
        }

        .tier-admin {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-suspended {
            background: #fed7aa;
            color: #c2410c;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            margin-right: 4px;
            transition: all 0.2s;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
        }

        .btn-edit:hover {
            background: #2563eb;
        }

        .btn-devices {
            background: #8b5cf6;
            color: white;
        }

        .btn-devices:hover {
            background: #7c3aed;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: white;
        }

        .spinner {
            display: inline-block;
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 24px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #1e293b;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #475569;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .form-select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <h1>👥 User Management</h1>
                    <p>View and manage all user accounts</p>
                </div>
                <div style="display: flex; gap: 12px;">
                    <a href="/admin/dashboard.php" class="btn btn-secondary">
                        ← Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
            <input type="text" id="search-input" class="search-input" placeholder="🔍 Search users by name or email...">
            <div class="filters">
                <select id="tier-filter" class="filter-select">
                    <option value="">All Tiers</option>
                    <option value="standard">Standard</option>
                    <option value="pro">Pro</option>
                    <option value="vip">VIP</option>
                    <option value="admin">Admin</option>
                </select>
                <select id="status-filter" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <button class="btn btn-primary" onclick="loadUsers()">Apply Filters</button>
            </div>
        </div>

        <!-- Loading -->
        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p>Loading users...</p>
        </div>

        <!-- Users Table -->
        <div id="users-table" class="users-table" style="display: none;">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Tier</th>
                        <th>Status</th>
                        <th>Devices</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="users-body">
                    <!-- Users loaded via JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="edit-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Edit User</div>
            <form id="edit-form">
                <input type="hidden" id="edit-user-id">
                
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" id="edit-first-name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" id="edit-last-name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" id="edit-email" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Tier</label>
                    <select id="edit-tier" class="form-select">
                        <option value="standard">Standard</option>
                        <option value="pro">Pro</option>
                        <option value="vip">VIP</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select id="edit-status" class="form-select">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Delete User</div>
            <p style="margin-bottom: 20px; color: #64748b;">
                Are you sure you want to delete <strong id="delete-user-name"></strong>?
            </p>
            <p style="color: #ef4444; font-size: 14px; margin-bottom: 20px;">
                ⚠️ This action cannot be undone. All user data and devices will be permanently deleted.
            </p>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn btn-delete" onclick="confirmDelete()">Delete User</button>
            </div>
        </div>
    </div>

    <script>
        const JWT_TOKEN = localStorage.getItem('vpn_token');
        let users = [];
        let currentUserId = null;

        document.addEventListener('DOMContentLoaded', () => {
            if (!JWT_TOKEN) {
                window.location.href = '/auth/login.php';
                return;
            }
            loadUsers();
        });

        async function loadUsers() {
            try {
                const response = await fetch('/api/admin/users/list.php', {
                    headers: {
                        'Authorization': `Bearer ${JWT_TOKEN}`
                    }
                });

                const data = await response.json();

                if (data.success) {
                    users = data.users;
                    renderUsers();
                } else {
                    showError(data.error || 'Failed to load users');
                }
            } catch (error) {
                showError('Error loading users: ' + error.message);
            }
        }

        function renderUsers() {
            const loading = document.getElementById('loading');
            const table = document.getElementById('users-table');
            const tbody = document.getElementById('users-body');

            loading.style.display = 'none';
            table.style.display = 'block';

            // Apply filters
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const tierFilter = document.getElementById('tier-filter').value;
            const statusFilter = document.getElementById('status-filter').value;

            let filteredUsers = users.filter(user => {
                const matchesSearch = user.first_name.toLowerCase().includes(searchTerm) ||
                                    user.last_name.toLowerCase().includes(searchTerm) ||
                                    user.email.toLowerCase().includes(searchTerm);
                const matchesTier = !tierFilter || user.tier === tierFilter;
                const matchesStatus = !statusFilter || user.status === statusFilter;
                
                return matchesSearch && matchesTier && matchesStatus;
            });

            if (filteredUsers.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-icon">🔍</div>
                                <p>No users found</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = filteredUsers.map(user => `
                <tr>
                    <td>
                        <strong>${escapeHtml(user.first_name)} ${escapeHtml(user.last_name)}</strong>
                    </td>
                    <td>${escapeHtml(user.email)}</td>
                    <td><span class="tier-badge tier-${user.tier}">${user.tier}</span></td>
                    <td><span class="status-badge status-${user.status}">${user.status}</span></td>
                    <td>${user.device_count || 0}</td>
                    <td>${formatDate(user.created_at)}</td>
                    <td>
                        <button class="action-btn btn-edit" onclick="showEditModal(${user.user_id})">
                            ✏️ Edit
                        </button>
                        <button class="action-btn btn-delete" onclick="showDeleteModal(${user.user_id}, '${escapeHtml(user.first_name)} ${escapeHtml(user.last_name)}')">
                            🗑️ Delete
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function showEditModal(userId) {
            const user = users.find(u => u.user_id === userId);
            if (!user) return;

            document.getElementById('edit-user-id').value = user.user_id;
            document.getElementById('edit-first-name').value = user.first_name;
            document.getElementById('edit-last-name').value = user.last_name;
            document.getElementById('edit-email').value = user.email;
            document.getElementById('edit-tier').value = user.tier;
            document.getElementById('edit-status').value = user.status;

            document.getElementById('edit-modal').classList.add('active');
        }

        document.getElementById('edit-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const userId = document.getElementById('edit-user-id').value;
            const data = {
                user_id: parseInt(userId),
                first_name: document.getElementById('edit-first-name').value,
                last_name: document.getElementById('edit-last-name').value,
                email: document.getElementById('edit-email').value,
                tier: document.getElementById('edit-tier').value,
                status: document.getElementById('edit-status').value
            };

            try {
                const response = await fetch('/api/admin/users/update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${JWT_TOKEN}`
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    closeModal();
                    showSuccess('User updated successfully!');
                    loadUsers();
                } else {
                    showError(result.error || 'Failed to update user');
                }
            } catch (error) {
                showError('Error updating user: ' + error.message);
            }
        });

        function showDeleteModal(userId, userName) {
            currentUserId = userId;
            document.getElementById('delete-user-name').textContent = userName;
            document.getElementById('delete-modal').classList.add('active');
        }

        async function confirmDelete() {
            try {
                const response = await fetch('/api/admin/users/delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${JWT_TOKEN}`
                    },
                    body: JSON.stringify({ user_id: currentUserId })
                });

                const result = await response.json();

                if (result.success) {
                    closeModal();
                    showSuccess('User deleted successfully!');
                    loadUsers();
                } else {
                    showError(result.error || 'Failed to delete user');
                }
            } catch (error) {
                showError('Error deleting user: ' + error.message);
            }
        }

        function closeModal() {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.classList.remove('active');
            });
            currentUserId = null;
        }

        document.getElementById('search-input').addEventListener('input', renderUsers);

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showSuccess(message) {
            alert('✅ ' + message);
        }

        function showError(message) {
            alert('❌ ' + message);
        }

        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>
