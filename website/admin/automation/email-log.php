<?php
/**
 * TrueVault VPN - Email Log Viewer
 * Part of Business Automation
 * Created: January 24, 2026
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$automationDb = new SQLite3(__DIR__ . '/databases/automation.db');
$settingsDb = new SQLite3(__DIR__ . '/../databases/settings.db');

// Get theme settings
$themeResult = $settingsDb->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'theme_%'");
$theme = [];
while ($row = $themeResult->fetchArray(SQLITE3_ASSOC)) {
    $theme[$row['setting_key']] = $row['setting_value'];
}

$primaryColor = $theme['theme_primary_color'] ?? '#00d4ff';
$secondaryColor = $theme['theme_secondary_color'] ?? '#7b2cbf';
$bgColor = $theme['theme_bg_color'] ?? '#0a0a0f';
$cardBg = $theme['theme_card_bg'] ?? 'rgba(255,255,255,0.03)';
$textColor = $theme['theme_text_color'] ?? '#ffffff';

// Filters
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$searchTerm = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Build query
$whereConditions = [];
$params = [];

if ($statusFilter) {
    $whereConditions[] = "status = :status";
    $params[':status'] = $statusFilter;
}

if ($typeFilter) {
    $whereConditions[] = "email_type = :type";
    $params[':type'] = $typeFilter;
}

if ($searchTerm) {
    $whereConditions[] = "(recipient_email LIKE :search OR subject LIKE :search OR template_name LIKE :search)";
    $params[':search'] = '%' . $searchTerm . '%';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countQuery = "SELECT COUNT(*) FROM email_log $whereClause";
$countStmt = $automationDb->prepare($countQuery);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value, SQLITE3_TEXT);
}
$totalCount = $countStmt->execute()->fetchArray()[0];
$totalPages = ceil($totalCount / $perPage);

// Get emails
$query = "SELECT * FROM email_log $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $automationDb->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, SQLITE3_TEXT);
}
$result = $stmt->execute();
$emails = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $emails[] = $row;
}

// Get stats
$stats = [
    'total' => $automationDb->querySingle("SELECT COUNT(*) FROM email_log"),
    'sent' => $automationDb->querySingle("SELECT COUNT(*) FROM email_log WHERE status = 'sent'"),
    'failed' => $automationDb->querySingle("SELECT COUNT(*) FROM email_log WHERE status = 'failed'"),
    'pending' => $automationDb->querySingle("SELECT COUNT(*) FROM email_log WHERE status = 'pending'"),
    'opened' => $automationDb->querySingle("SELECT COUNT(*) FROM email_log WHERE opened_at IS NOT NULL"),
    'today' => $automationDb->querySingle("SELECT COUNT(*) FROM email_log WHERE date(created_at) = date('now')")
];

$automationDb->close();
$settingsDb->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Log - TrueVault Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: <?php echo $bgColor; ?>;
            color: <?php echo $textColor; ?>;
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header-left { display: flex; align-items: center; gap: 15px; }
        
        .back-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: <?php echo $textColor; ?>;
            text-decoration: none;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(90deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: <?php echo $primaryColor; ?>;
        }
        
        .stat-label { color: #888; font-size: 0.85rem; margin-top: 5px; }
        
        .card {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        
        .filter-group { display: flex; align-items: center; gap: 8px; }
        
        .filter-group label { color: #888; font-size: 0.9rem; }
        
        .filter-group select, .filter-group input {
            padding: 8px 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            color: <?php echo $textColor; ?>;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            color: #fff;
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: <?php echo $textColor; ?>;
        }
        
        table { width: 100%; border-collapse: collapse; }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        th { color: #888; font-weight: 500; font-size: 0.85rem; text-transform: uppercase; }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-sent { background: rgba(0,200,100,0.2); color: #00c864; }
        .status-failed { background: rgba(255,80,80,0.2); color: #ff5050; }
        .status-pending { background: rgba(255,180,0,0.2); color: #ffb400; }
        
        .type-customer { color: <?php echo $primaryColor; ?>; }
        .type-admin { color: <?php echo $secondaryColor; ?>; }
        
        .email-subject {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .pagination a, .pagination span {
            padding: 8px 12px;
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            color: <?php echo $textColor; ?>;
            text-decoration: none;
        }
        
        .pagination a:hover { border-color: <?php echo $primaryColor; ?>; }
        .pagination .active { background: <?php echo $primaryColor; ?>; color: #000; }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #666;
        }
        
        .empty-state i { font-size: 3rem; margin-bottom: 15px; }
        
        .view-btn {
            background: none;
            border: none;
            color: <?php echo $primaryColor; ?>;
            cursor: pointer;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show { display: flex; }
        
        .modal-content {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 30px;
            max-width: 700px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title { font-size: 1.2rem; font-weight: 600; }
        
        .close-btn {
            background: none;
            border: none;
            color: #888;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .email-preview {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 20px;
        }
        
        .email-meta { margin-bottom: 15px; }
        .email-meta p { margin-bottom: 8px; color: #888; }
        .email-meta strong { color: <?php echo $textColor; ?>; }
        
        @media (max-width: 768px) {
            .filters { flex-direction: column; align-items: stretch; }
            .filter-group { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div class="header-left">
                <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
                <h1 class="page-title">Email Log</h1>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Emails</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #00c864;"><?php echo $stats['sent']; ?></div>
                <div class="stat-label">Sent</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #ff5050;"><?php echo $stats['failed']; ?></div>
                <div class="stat-label">Failed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #ffb400;"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['today']; ?></div>
                <div class="stat-label">Today</div>
            </div>
        </div>
        
        <div class="card">
            <form method="GET" class="filters">
                <div class="filter-group">
                    <label>Status:</label>
                    <select name="status">
                        <option value="">All</option>
                        <option value="sent" <?php echo $statusFilter === 'sent' ? 'selected' : ''; ?>>Sent</option>
                        <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Type:</label>
                    <select name="type">
                        <option value="">All</option>
                        <option value="customer" <?php echo $typeFilter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                        <option value="admin" <?php echo $typeFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Search:</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Email, subject, or template...">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                <a href="email-log.php" class="btn btn-secondary">Clear</a>
            </form>
        </div>
        
        <div class="card">
            <?php if (empty($emails)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No emails found</h3>
                <p>No emails match your current filters.</p>
            </div>
            <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Recipient</th>
                            <th>Subject</th>
                            <th>Template</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emails as $email): ?>
                        <tr>
                            <td><?php echo date('M j, g:i a', strtotime($email['created_at'])); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($email['recipient_name']); ?></div>
                                <small style="color: #888;"><?php echo htmlspecialchars($email['recipient_email']); ?></small>
                            </td>
                            <td class="email-subject"><?php echo htmlspecialchars($email['subject']); ?></td>
                            <td><?php echo htmlspecialchars($email['template_name']); ?></td>
                            <td>
                                <span class="type-<?php echo $email['email_type']; ?>">
                                    <?php echo ucfirst($email['email_type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $email['status']; ?>">
                                    <?php echo ucfirst($email['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="view-btn" onclick="viewEmail(<?php echo $email['id']; ?>)" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $statusFilter; ?>&type=<?php echo $typeFilter; ?>&search=<?php echo urlencode($searchTerm); ?>">&laquo;</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>&type=<?php echo $typeFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $statusFilter; ?>&type=<?php echo $typeFilter; ?>&search=<?php echo urlencode($searchTerm); ?>">&raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="modal" id="emailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Email Details</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div id="emailDetails"></div>
        </div>
    </div>
    
    <script>
        const emails = <?php echo json_encode($emails); ?>;
        
        function viewEmail(id) {
            const email = emails.find(e => e.id == id);
            if (!email) return;
            
            const details = document.getElementById('emailDetails');
            details.innerHTML = `
                <div class="email-meta">
                    <p><strong>To:</strong> ${email.recipient_name} &lt;${email.recipient_email}&gt;</p>
                    <p><strong>Subject:</strong> ${email.subject}</p>
                    <p><strong>Template:</strong> ${email.template_name}</p>
                    <p><strong>Type:</strong> ${email.email_type}</p>
                    <p><strong>Status:</strong> <span class="status-badge status-${email.status}">${email.status}</span></p>
                    <p><strong>Created:</strong> ${email.created_at}</p>
                    ${email.sent_at ? `<p><strong>Sent:</strong> ${email.sent_at}</p>` : ''}
                    ${email.error_message ? `<p><strong>Error:</strong> <span style="color: #ff5050;">${email.error_message}</span></p>` : ''}
                </div>
                ${email.metadata ? `<h4 style="margin: 15px 0 10px;">Data</h4><pre style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; overflow-x: auto;">${JSON.stringify(JSON.parse(email.metadata), null, 2)}</pre>` : ''}
            `;
            
            document.getElementById('emailModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('emailModal').classList.remove('show');
        }
        
        document.getElementById('emailModal').addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) closeModal();
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>
