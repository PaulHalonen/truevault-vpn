<?php
/**
 * TrueVault VPN - Canned Response Library
 * Task 17.9: Tier 3 Pre-Written Replies
 * Created: January 24, 2026
 * 
 * Features:
 * - Admin CRUD for canned responses
 * - Category organization
 * - Variable preview system
 * - Usage statistics
 * - Suggestion engine for tickets
 */

session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Database connections
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

// Handle CRUD operations
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $stmt = $automationDb->prepare("INSERT INTO canned_responses 
            (category, title, trigger_keywords, subject, body, variables) 
            VALUES (:category, :title, :keywords, :subject, :body, :variables)");
        $stmt->bindValue(':category', $_POST['category'], SQLITE3_TEXT);
        $stmt->bindValue(':title', $_POST['title'], SQLITE3_TEXT);
        $stmt->bindValue(':keywords', strtolower($_POST['trigger_keywords']), SQLITE3_TEXT);
        $stmt->bindValue(':subject', $_POST['subject'], SQLITE3_TEXT);
        $stmt->bindValue(':body', $_POST['body'], SQLITE3_TEXT);
        $stmt->bindValue(':variables', $_POST['variables'] ?? '[]', SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $message = 'Canned response added successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error adding response';
            $messageType = 'error';
        }
    }
    
    if ($action === 'update') {
        $stmt = $automationDb->prepare("UPDATE canned_responses SET 
            category = :category, 
            title = :title, 
            trigger_keywords = :keywords,
            subject = :subject,
            body = :body, 
            variables = :variables,
            updated_at = datetime('now')
            WHERE id = :id");
        $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':category', $_POST['category'], SQLITE3_TEXT);
        $stmt->bindValue(':title', $_POST['title'], SQLITE3_TEXT);
        $stmt->bindValue(':keywords', strtolower($_POST['trigger_keywords']), SQLITE3_TEXT);
        $stmt->bindValue(':subject', $_POST['subject'], SQLITE3_TEXT);
        $stmt->bindValue(':body', $_POST['body'], SQLITE3_TEXT);
        $stmt->bindValue(':variables', $_POST['variables'] ?? '[]', SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $message = 'Response updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error updating response';
            $messageType = 'error';
        }
    }
    
    if ($action === 'delete') {
        $stmt = $automationDb->prepare("DELETE FROM canned_responses WHERE id = :id");
        $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
            $message = 'Response deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error deleting response';
            $messageType = 'error';
        }
    }
    
    if ($action === 'toggle_active') {
        $id = $_POST['id'];
        $automationDb->exec("UPDATE canned_responses SET is_active = NOT is_active WHERE id = $id");
        $message = 'Response status toggled';
        $messageType = 'success';
    }
}

// Get filter
$categoryFilter = $_GET['category'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM canned_responses WHERE 1=1";
$params = [];

if ($categoryFilter) {
    $sql .= " AND category = :category";
    $params[':category'] = $categoryFilter;
}

if ($searchQuery) {
    $sql .= " AND (title LIKE :search OR trigger_keywords LIKE :search OR body LIKE :search)";
    $params[':search'] = '%' . $searchQuery . '%';
}

$sql .= " ORDER BY category, times_used DESC";

$stmt = $automationDb->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, SQLITE3_TEXT);
}
$result = $stmt->execute();

$responses = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $responses[] = $row;
}

// Get categories
$categories = ['billing', 'technical', 'account', 'general'];

// Get stats
$stats = [
    'total' => $automationDb->querySingle("SELECT COUNT(*) FROM canned_responses"),
    'active' => $automationDb->querySingle("SELECT COUNT(*) FROM canned_responses WHERE is_active = 1"),
    'total_used' => $automationDb->querySingle("SELECT COALESCE(SUM(times_used), 0) FROM canned_responses")
];

// Count by category
$categoryCounts = [];
foreach ($categories as $cat) {
    $categoryCounts[$cat] = $automationDb->querySingle("SELECT COUNT(*) FROM canned_responses WHERE category = '$cat'");
}

// Available variables
$availableVariables = [
    '{first_name}' => 'Customer first name',
    '{email}' => 'Customer email',
    '{ticket_id}' => 'Ticket number',
    '{plan_name}' => 'Current plan',
    '{device_limit}' => 'Plan device limit',
    '{device_count}' => 'Current device count',
    '{days_as_customer}' => 'Account age in days',
    '{amount}' => 'Dollar amount',
    '{invoice_number}' => 'Invoice ID',
    '{promo_code}' => 'Promo code',
    '{discount_amount}' => 'Discount amount',
    '{new_plan}' => 'New plan name',
    '{new_email}' => 'New email address',
    '{dashboard_url}' => 'Dashboard URL',
    '{self_service_url}' => 'Self-service action URL'
];

$settingsDb->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canned Responses - TrueVault Admin</title>
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
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
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
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            color: #fff;
        }
        
        .btn-secondary {
            background: <?php echo $cardBg; ?>;
            color: <?php echo $textColor; ?>;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .btn-danger {
            background: rgba(255,80,80,0.2);
            color: #ff5050;
            border: 1px solid rgba(255,80,80,0.3);
        }
        
        .btn-success {
            background: rgba(0,200,100,0.2);
            color: #00c864;
            border: 1px solid rgba(0,200,100,0.3);
        }
        
        .btn-sm { padding: 6px 12px; font-size: 0.85rem; }
        
        /* Stats Row */
        .stats-row {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            flex: 1;
            min-width: 150px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(90deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-label { color: #888; font-size: 0.85rem; margin-top: 5px; }
        
        /* Category Pills */
        .category-pills {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .pill {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .pill-all { background: rgba(255,255,255,0.1); color: #fff; }
        .pill-billing { background: rgba(0,212,255,0.2); color: #00d4ff; }
        .pill-technical { background: rgba(255,180,0,0.2); color: #ffb400; }
        .pill-account { background: rgba(123,44,191,0.2); color: #9b59b6; }
        .pill-general { background: rgba(255,255,255,0.1); color: #aaa; }
        
        .pill.active { transform: scale(1.05); box-shadow: 0 0 10px currentColor; }
        
        /* Filters */
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: <?php echo $textColor; ?>;
            font-size: 1rem;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        
        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success { background: rgba(0,200,100,0.1); border: 1px solid rgba(0,200,100,0.3); color: #00c864; }
        .alert-error { background: rgba(255,80,80,0.1); border: 1px solid rgba(255,80,80,0.3); color: #ff5050; }
        
        /* Response Cards */
        .response-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .response-card {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.2s;
        }
        
        .response-card:hover { border-color: <?php echo $primaryColor; ?>50; }
        
        .response-card.inactive { opacity: 0.5; }
        
        .response-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .response-title-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .response-category {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .category-billing { background: rgba(0,212,255,0.2); color: #00d4ff; }
        .category-technical { background: rgba(255,180,0,0.2); color: #ffb400; }
        .category-account { background: rgba(123,44,191,0.2); color: #9b59b6; }
        .category-general { background: rgba(255,255,255,0.1); color: #aaa; }
        
        .response-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
        }
        
        .response-subject {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .response-preview {
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #ccc;
            max-height: 100px;
            overflow: hidden;
        }
        
        .response-keywords {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 15px;
        }
        
        .keyword-tag {
            padding: 3px 8px;
            background: rgba(255,255,255,0.05);
            border-radius: 4px;
            font-size: 0.75rem;
            color: #888;
        }
        
        .response-stats {
            display: flex;
            gap: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.05);
            font-size: 0.85rem;
            color: #666;
        }
        
        .response-actions {
            display: flex;
            gap: 8px;
        }
        
        /* Modal */
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
            overflow-y: auto;
            padding: 20px;
        }
        
        .modal.show { display: flex; }
        
        .modal-content {
            background: #1a1a2e;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 30px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }
        
        .modal-title {
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: #888;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #ccc;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: <?php echo $textColor; ?>;
            font-size: 1rem;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: <?php echo $primaryColor; ?>;
        }
        
        .form-help {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }
        
        /* Variable Picker */
        .variable-picker {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        
        .variable-btn {
            padding: 4px 10px;
            background: rgba(0,212,255,0.1);
            border: 1px solid rgba(0,212,255,0.3);
            border-radius: 4px;
            color: <?php echo $primaryColor; ?>;
            font-size: 0.8rem;
            cursor: pointer;
            font-family: monospace;
        }
        
        .variable-btn:hover {
            background: rgba(0,212,255,0.2);
        }
        
        /* Preview Panel */
        .preview-panel {
            background: rgba(0,0,0,0.3);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .preview-panel h4 {
            margin-bottom: 15px;
            color: #888;
            font-size: 0.9rem;
        }
        
        .preview-content {
            background: #fff;
            color: #333;
            border-radius: 8px;
            padding: 20px;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
        }
        
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .form-group.full-width { grid-column: span 1; }
            .stats-row { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div class="header-left">
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="page-title">Canned Responses</h1>
            </div>
            <div>
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> Add Response
                </button>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Responses</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['active']; ?></div>
                <div class="stat-label">Active</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_used']; ?></div>
                <div class="stat-label">Times Used</div>
            </div>
        </div>
        
        <!-- Category Pills -->
        <div class="category-pills">
            <a href="?" class="pill pill-all <?php echo !$categoryFilter ? 'active' : ''; ?>">
                All (<?php echo $stats['total']; ?>)
            </a>
            <?php foreach ($categories as $cat): ?>
            <a href="?category=<?php echo $cat; ?>" 
               class="pill pill-<?php echo $cat; ?> <?php echo $categoryFilter === $cat ? 'active' : ''; ?>">
                <?php echo ucfirst($cat); ?> (<?php echo $categoryCounts[$cat]; ?>)
            </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <form method="GET" style="width: 100%;">
                    <?php if ($categoryFilter): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($categoryFilter); ?>">
                    <?php endif; ?>
                    <input type="text" name="search" placeholder="Search responses..." 
                           value="<?php echo htmlspecialchars($searchQuery); ?>">
                </form>
            </div>
        </div>
        
        <!-- Response List -->
        <?php if (empty($responses)): ?>
        <div style="text-align: center; padding: 50px; color: #666;">
            <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <p>No canned responses found</p>
        </div>
        <?php else: ?>
        <div class="response-list">
            <?php foreach ($responses as $response): ?>
            <div class="response-card <?php echo !$response['is_active'] ? 'inactive' : ''; ?>">
                <div class="response-header">
                    <div class="response-title-row">
                        <span class="response-category category-<?php echo $response['category']; ?>">
                            <?php echo ucfirst($response['category']); ?>
                        </span>
                        <span class="response-title"><?php echo htmlspecialchars($response['title']); ?></span>
                        <?php if (!$response['is_active']): ?>
                        <span style="color: #ff5050; font-size: 0.8rem;">(Inactive)</span>
                        <?php endif; ?>
                    </div>
                    <div class="response-actions">
                        <button class="btn btn-sm btn-secondary" onclick="previewResponse(<?php echo $response['id']; ?>)">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                        <button class="btn btn-sm btn-secondary" onclick='editResponse(<?php echo json_encode($response); ?>)'>
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="id" value="<?php echo $response['id']; ?>">
                            <button type="submit" class="btn btn-sm <?php echo $response['is_active'] ? 'btn-danger' : 'btn-success'; ?>">
                                <i class="fas <?php echo $response['is_active'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                            </button>
                        </form>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this response?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $response['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <?php if ($response['subject']): ?>
                <div class="response-subject">
                    <strong>Subject:</strong> <?php echo htmlspecialchars($response['subject']); ?>
                </div>
                <?php endif; ?>
                
                <div class="response-preview">
                    <?php echo strip_tags(substr($response['body'], 0, 300)); ?>...
                </div>
                
                <?php if ($response['trigger_keywords']): ?>
                <div class="response-keywords">
                    <strong style="color: #888; font-size: 0.8rem; margin-right: 5px;">Triggers:</strong>
                    <?php 
                    $keywords = explode(',', $response['trigger_keywords']);
                    foreach (array_slice($keywords, 0, 5) as $kw): ?>
                    <span class="keyword-tag"><?php echo htmlspecialchars(trim($kw)); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="response-stats">
                    <span><i class="fas fa-paper-plane"></i> Used <?php echo $response['times_used']; ?>x</span>
                    <span><i class="fas fa-chart-line"></i> <?php echo round($response['success_rate']); ?>% success</span>
                    <span><i class="fas fa-clock"></i> Updated <?php echo date('M j, Y', strtotime($response['updated_at'])); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Add/Edit Modal -->
    <div class="modal" id="responseModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle"><i class="fas fa-plus"></i> Add Canned Response</h3>
                <button class="modal-close" onclick="hideModal()">&times;</button>
            </div>
            
            <form method="POST" id="responseForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="responseId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" id="responseCategory" required>
                            <option value="billing">Billing</option>
                            <option value="technical">Technical</option>
                            <option value="account">Account</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Title (Internal)</label>
                        <input type="text" name="title" id="responseTitle" required 
                               placeholder="e.g., Payment Retry Instructions">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Trigger Keywords (comma-separated)</label>
                    <input type="text" name="trigger_keywords" id="responseKeywords" 
                           placeholder="e.g., payment, failed, declined, card">
                    <div class="form-help">These keywords help suggest this response for matching tickets</div>
                </div>
                
                <div class="form-group">
                    <label>Email Subject (optional)</label>
                    <input type="text" name="subject" id="responseSubject" 
                           placeholder="e.g., Re: {ticket_id} - Payment Issue">
                </div>
                
                <div class="form-group">
                    <label>Response Body (HTML supported)</label>
                    <textarea name="body" id="responseBody" required 
                              placeholder="<p>Hi {first_name},</p><p>...</p>"></textarea>
                    
                    <div class="form-help">Click variables to insert:</div>
                    <div class="variable-picker">
                        <?php foreach ($availableVariables as $var => $desc): ?>
                        <button type="button" class="variable-btn" onclick="insertVariable('<?php echo $var; ?>')" title="<?php echo $desc; ?>">
                            <?php echo $var; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <input type="hidden" name="variables" id="responseVariables" value="[]">
                
                <div class="preview-panel">
                    <h4><i class="fas fa-eye"></i> Live Preview (with sample data)</h4>
                    <div class="preview-content" id="livePreview">
                        <p style="color: #999;">Start typing to see preview...</p>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Response
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Preview Modal -->
    <div class="modal" id="previewModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-eye"></i> Response Preview</h3>
                <button class="modal-close" onclick="hidePreviewModal()">&times;</button>
            </div>
            <div id="fullPreviewContent" class="preview-content"></div>
        </div>
    </div>
    
    <script>
        // Sample data for preview
        const sampleData = {
            first_name: 'John',
            email: 'john@example.com',
            ticket_id: 'TV-2026-00042',
            plan_name: 'Personal',
            device_limit: '5',
            device_count: '3',
            days_as_customer: '127',
            amount: '$9.97',
            invoice_number: 'INV-2026-0042',
            promo_code: 'SAVE20',
            discount_amount: '$2.00',
            new_plan: 'Family',
            new_email: 'john.new@example.com',
            dashboard_url: 'https://vpn.the-truth-publishing.com/dashboard',
            self_service_url: 'https://vpn.the-truth-publishing.com/self-service'
        };
        
        // Store responses for preview
        const responses = <?php echo json_encode($responses); ?>;
        
        function showAddModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus"></i> Add Canned Response';
            document.getElementById('formAction').value = 'add';
            document.getElementById('responseId').value = '';
            document.getElementById('responseForm').reset();
            document.getElementById('livePreview').innerHTML = '<p style="color: #999;">Start typing to see preview...</p>';
            document.getElementById('responseModal').classList.add('show');
        }
        
        function editResponse(response) {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Canned Response';
            document.getElementById('formAction').value = 'update';
            document.getElementById('responseId').value = response.id;
            document.getElementById('responseCategory').value = response.category;
            document.getElementById('responseTitle').value = response.title;
            document.getElementById('responseKeywords').value = response.trigger_keywords || '';
            document.getElementById('responseSubject').value = response.subject || '';
            document.getElementById('responseBody').value = response.body;
            document.getElementById('responseVariables').value = response.variables || '[]';
            updatePreview();
            document.getElementById('responseModal').classList.add('show');
        }
        
        function hideModal() {
            document.getElementById('responseModal').classList.remove('show');
        }
        
        function previewResponse(id) {
            const response = responses.find(r => r.id == id);
            if (response) {
                let preview = replaceVariables(response.body);
                document.getElementById('fullPreviewContent').innerHTML = preview;
                document.getElementById('previewModal').classList.add('show');
            }
        }
        
        function hidePreviewModal() {
            document.getElementById('previewModal').classList.remove('show');
        }
        
        function insertVariable(variable) {
            const textarea = document.getElementById('responseBody');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            textarea.value = text.substring(0, start) + variable + text.substring(end);
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd = start + variable.length;
            updatePreview();
        }
        
        function replaceVariables(text) {
            for (const [key, value] of Object.entries(sampleData)) {
                text = text.replace(new RegExp(`\\{${key}\\}`, 'g'), value);
            }
            return text;
        }
        
        function updatePreview() {
            const body = document.getElementById('responseBody').value;
            if (body.trim()) {
                document.getElementById('livePreview').innerHTML = replaceVariables(body);
            } else {
                document.getElementById('livePreview').innerHTML = '<p style="color: #999;">Start typing to see preview...</p>';
            }
        }
        
        // Update preview on body change
        document.getElementById('responseBody').addEventListener('input', updatePreview);
        
        // Close modals on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                hideModal();
                hidePreviewModal();
            }
        });
        
        // Close modals on background click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target.classList.contains('modal')) {
                    hideModal();
                    hidePreviewModal();
                }
            });
        });
    </script>
</body>
</html>
<?php
$automationDb->close();

/**
 * Canned Response Suggestion Engine
 * Used by ticket dashboard to suggest responses
 */
class CannedResponseSuggester {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get suggested canned responses for a ticket
     */
    public function getSuggestions($ticketContent, $category = null, $limit = 3) {
        $content = strtolower($ticketContent);
        $words = preg_split('/[\s,.\-:;!?]+/', $content);
        $words = array_filter($words, fn($w) => strlen($w) > 2);
        
        // Get all active responses
        $sql = "SELECT * FROM canned_responses WHERE is_active = 1";
        if ($category) {
            $sql .= " AND category = '$category'";
        }
        
        $result = $this->db->query($sql);
        $suggestions = [];
        
        while ($response = $result->fetchArray(SQLITE3_ASSOC)) {
            $keywords = array_map('trim', explode(',', strtolower($response['trigger_keywords'])));
            $matchCount = count(array_intersect($words, $keywords));
            
            if ($matchCount > 0) {
                $score = $matchCount / max(count($keywords), 1);
                $suggestions[] = [
                    'response' => $response,
                    'score' => $score,
                    'match_count' => $matchCount
                ];
            }
        }
        
        // Sort by score descending
        usort($suggestions, fn($a, $b) => $b['score'] <=> $a['score']);
        
        return array_slice($suggestions, 0, $limit);
    }
    
    /**
     * Record usage of a canned response
     */
    public function recordUsage($responseId) {
        $this->db->exec("UPDATE canned_responses SET times_used = times_used + 1 WHERE id = $responseId");
    }
    
    /**
     * Record feedback (did customer respond positively?)
     */
    public function recordFeedback($responseId, $positive) {
        $response = $this->db->querySingle("SELECT success_rate, times_used FROM canned_responses WHERE id = $responseId", true);
        
        if ($response) {
            $currentRate = $response['success_rate'];
            $timesUsed = $response['times_used'];
            
            // Calculate new success rate (weighted average)
            $newRate = (($currentRate * ($timesUsed - 1)) + ($positive ? 100 : 0)) / $timesUsed;
            
            $stmt = $this->db->prepare("UPDATE canned_responses SET success_rate = :rate WHERE id = :id");
            $stmt->bindValue(':rate', $newRate, SQLITE3_FLOAT);
            $stmt->bindValue(':id', $responseId, SQLITE3_INTEGER);
            $stmt->execute();
        }
    }
    
    /**
     * Replace variables in response body
     */
    public function fillVariables($body, $data) {
        foreach ($data as $key => $value) {
            $body = str_replace('{' . $key . '}', $value, $body);
        }
        return $body;
    }
}
