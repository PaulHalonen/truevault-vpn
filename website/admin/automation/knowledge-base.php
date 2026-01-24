<?php
/**
 * TrueVault VPN - Knowledge Base Manager
 * Task 17.7: Tier 1 Auto-Resolution System
 * Created: January 24, 2026
 * 
 * Features:
 * - Admin CRUD interface for KB entries
 * - Auto-resolution engine for tickets
 * - Keyword extraction and matching
 * - Success rate tracking
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
        $stmt = $automationDb->prepare("INSERT INTO knowledge_base 
            (category, keywords, question, answer, resolution_steps) 
            VALUES (:category, :keywords, :question, :answer, :steps)");
        $stmt->bindValue(':category', $_POST['category'], SQLITE3_TEXT);
        $stmt->bindValue(':keywords', strtolower($_POST['keywords']), SQLITE3_TEXT);
        $stmt->bindValue(':question', $_POST['question'], SQLITE3_TEXT);
        $stmt->bindValue(':answer', $_POST['answer'], SQLITE3_TEXT);
        $stmt->bindValue(':steps', $_POST['resolution_steps'] ?? '[]', SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $message = 'Knowledge base entry added successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error adding entry';
            $messageType = 'error';
        }
    }
    
    if ($action === 'update') {
        $stmt = $automationDb->prepare("UPDATE knowledge_base SET 
            category = :category, 
            keywords = :keywords, 
            question = :question, 
            answer = :answer, 
            resolution_steps = :steps,
            updated_at = datetime('now')
            WHERE id = :id");
        $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':category', $_POST['category'], SQLITE3_TEXT);
        $stmt->bindValue(':keywords', strtolower($_POST['keywords']), SQLITE3_TEXT);
        $stmt->bindValue(':question', $_POST['question'], SQLITE3_TEXT);
        $stmt->bindValue(':answer', $_POST['answer'], SQLITE3_TEXT);
        $stmt->bindValue(':steps', $_POST['resolution_steps'] ?? '[]', SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $message = 'Entry updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error updating entry';
            $messageType = 'error';
        }
    }
    
    if ($action === 'delete') {
        $stmt = $automationDb->prepare("DELETE FROM knowledge_base WHERE id = :id");
        $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
            $message = 'Entry deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error deleting entry';
            $messageType = 'error';
        }
    }
    
    if ($action === 'test_resolution') {
        // Test auto-resolution with sample text
        $testText = $_POST['test_text'] ?? '';
        $resolver = new KnowledgeBaseResolver($automationDb);
        $result = $resolver->findBestMatch($testText);
        
        if ($result) {
            $message = "Match found! Score: " . round($result['score'] * 100) . "% - \"" . $result['entry']['question'] . "\"";
            $messageType = 'success';
        } else {
            $message = "No match found (threshold: 60%)";
            $messageType = 'error';
        }
    }
}

// Get filter
$categoryFilter = $_GET['category'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM knowledge_base WHERE 1=1";
$params = [];

if ($categoryFilter) {
    $sql .= " AND category = :category";
    $params[':category'] = $categoryFilter;
}

if ($searchQuery) {
    $sql .= " AND (question LIKE :search OR keywords LIKE :search OR answer LIKE :search)";
    $params[':search'] = '%' . $searchQuery . '%';
}

$sql .= " ORDER BY category, times_used DESC";

$stmt = $automationDb->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, SQLITE3_TEXT);
}
$result = $stmt->execute();

$entries = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $entries[] = $row;
}

// Get categories for filter
$categories = ['billing', 'technical', 'account', 'setup', 'general'];

// Get stats
$stats = [
    'total' => $automationDb->querySingle("SELECT COUNT(*) FROM knowledge_base"),
    'total_used' => $automationDb->querySingle("SELECT COALESCE(SUM(times_used), 0) FROM knowledge_base"),
    'avg_success' => round($automationDb->querySingle("SELECT COALESCE(AVG(success_rate), 0) FROM knowledge_base") ?? 0, 1)
];

// Count by category
$categoryCounts = [];
foreach ($categories as $cat) {
    $categoryCounts[$cat] = $automationDb->querySingle("SELECT COUNT(*) FROM knowledge_base WHERE category = '$cat'");
}

$settingsDb->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knowledge Base - TrueVault Admin</title>
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
        
        .filter-select {
            padding: 12px 15px;
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: <?php echo $textColor; ?>;
            min-width: 150px;
        }
        
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
        .pill-setup { background: rgba(0,200,100,0.2); color: #00c864; }
        .pill-general { background: rgba(255,255,255,0.1); color: #aaa; }
        
        .pill.active { transform: scale(1.05); box-shadow: 0 0 10px currentColor; }
        
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
        
        /* KB Grid */
        .kb-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
        }
        
        .kb-card {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.2s;
        }
        
        .kb-card:hover { border-color: <?php echo $primaryColor; ?>50; }
        
        .kb-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        
        .kb-category {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .category-billing { background: rgba(0,212,255,0.2); color: #00d4ff; }
        .category-technical { background: rgba(255,180,0,0.2); color: #ffb400; }
        .category-account { background: rgba(123,44,191,0.2); color: #9b59b6; }
        .category-setup { background: rgba(0,200,100,0.2); color: #00c864; }
        .category-general { background: rgba(255,255,255,0.1); color: #aaa; }
        
        .kb-question {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #fff;
        }
        
        .kb-answer {
            color: #aaa;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 15px;
            max-height: 80px;
            overflow: hidden;
        }
        
        .kb-keywords {
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
        
        .kb-stats {
            display: flex;
            gap: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.05);
            font-size: 0.85rem;
            color: #666;
        }
        
        .kb-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
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
            max-width: 600px;
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
        
        .form-group {
            margin-bottom: 20px;
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
        }
        
        .form-group textarea {
            min-height: 100px;
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
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
        }
        
        /* Test Resolution Box */
        .test-box {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .test-box h3 {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .test-box h3 i { color: <?php echo $primaryColor; ?>; }
        
        @media (max-width: 768px) {
            .kb-grid { grid-template-columns: 1fr; }
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
                <h1 class="page-title">Knowledge Base</h1>
            </div>
            <div>
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> Add Entry
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
                <div class="stat-label">Total Entries</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_used']; ?></div>
                <div class="stat-label">Times Used</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['avg_success']; ?>%</div>
                <div class="stat-label">Avg Success Rate</div>
            </div>
        </div>
        
        <!-- Test Resolution -->
        <div class="test-box">
            <h3><i class="fas fa-flask"></i> Test Auto-Resolution</h3>
            <form method="POST" style="display: flex; gap: 15px; align-items: flex-end;">
                <input type="hidden" name="action" value="test_resolution">
                <div style="flex: 1;">
                    <input type="text" name="test_text" placeholder="Enter ticket text to test matching..." 
                           style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff;">
                </div>
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Test Match
                </button>
            </form>
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
                    <input type="text" name="search" placeholder="Search questions, keywords, answers..." 
                           value="<?php echo htmlspecialchars($searchQuery); ?>">
                </form>
            </div>
        </div>
        
        <!-- KB Grid -->
        <?php if (empty($entries)): ?>
        <div style="text-align: center; padding: 50px; color: #666;">
            <i class="fas fa-book-open" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <p>No knowledge base entries found</p>
        </div>
        <?php else: ?>
        <div class="kb-grid">
            <?php foreach ($entries as $entry): ?>
            <div class="kb-card">
                <div class="kb-header">
                    <span class="kb-category category-<?php echo $entry['category']; ?>">
                        <?php echo ucfirst($entry['category']); ?>
                    </span>
                    <span style="color: #666; font-size: 0.85rem;">#<?php echo $entry['id']; ?></span>
                </div>
                
                <div class="kb-question"><?php echo htmlspecialchars($entry['question']); ?></div>
                
                <div class="kb-answer"><?php echo htmlspecialchars(substr($entry['answer'], 0, 200)); ?>...</div>
                
                <div class="kb-keywords">
                    <?php 
                    $keywords = explode(',', $entry['keywords']);
                    foreach (array_slice($keywords, 0, 5) as $kw): ?>
                    <span class="keyword-tag"><?php echo htmlspecialchars(trim($kw)); ?></span>
                    <?php endforeach; ?>
                    <?php if (count($keywords) > 5): ?>
                    <span class="keyword-tag">+<?php echo count($keywords) - 5; ?> more</span>
                    <?php endif; ?>
                </div>
                
                <div class="kb-stats">
                    <span><i class="fas fa-check"></i> Used <?php echo $entry['times_used']; ?>x</span>
                    <span><i class="fas fa-chart-line"></i> <?php echo round($entry['success_rate']); ?>% success</span>
                </div>
                
                <div class="kb-actions">
                    <button class="btn btn-sm btn-secondary" onclick='editEntry(<?php echo json_encode($entry); ?>)'>
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this entry?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $entry['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Add/Edit Modal -->
    <div class="modal" id="entryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle"><i class="fas fa-plus"></i> Add KB Entry</h3>
                <button class="modal-close" onclick="hideModal()">&times;</button>
            </div>
            
            <form method="POST" id="entryForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="entryId">
                
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" id="entryCategory" required>
                        <option value="billing">Billing</option>
                        <option value="technical">Technical</option>
                        <option value="account">Account</option>
                        <option value="setup">Setup</option>
                        <option value="general">General</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Question / Issue Title</label>
                    <input type="text" name="question" id="entryQuestion" required 
                           placeholder="e.g., Why is my VPN slow?">
                </div>
                
                <div class="form-group">
                    <label>Keywords (comma-separated)</label>
                    <input type="text" name="keywords" id="entryKeywords" required 
                           placeholder="e.g., slow, speed, lag, performance">
                    <div class="form-help">These trigger auto-resolution matching</div>
                </div>
                
                <div class="form-group">
                    <label>Answer / Resolution</label>
                    <textarea name="answer" id="entryAnswer" required 
                              placeholder="Detailed answer that will be sent to customer..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Resolution Steps (JSON array, optional)</label>
                    <textarea name="resolution_steps" id="entrySteps" 
                              placeholder='["Step 1", "Step 2", "Step 3"]'></textarea>
                    <div class="form-help">Optional step-by-step instructions</div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Save Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showAddModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus"></i> Add KB Entry';
            document.getElementById('formAction').value = 'add';
            document.getElementById('entryId').value = '';
            document.getElementById('entryForm').reset();
            document.getElementById('entryModal').classList.add('show');
        }
        
        function editEntry(entry) {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit KB Entry';
            document.getElementById('formAction').value = 'update';
            document.getElementById('entryId').value = entry.id;
            document.getElementById('entryCategory').value = entry.category;
            document.getElementById('entryQuestion').value = entry.question;
            document.getElementById('entryKeywords').value = entry.keywords;
            document.getElementById('entryAnswer').value = entry.answer;
            document.getElementById('entrySteps').value = entry.resolution_steps || '';
            document.getElementById('entryModal').classList.add('show');
        }
        
        function hideModal() {
            document.getElementById('entryModal').classList.remove('show');
        }
        
        // Close modal on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') hideModal();
        });
        
        // Close modal on background click
        document.getElementById('entryModal').addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) hideModal();
        });
    </script>
</body>
</html>
<?php
$automationDb->close();

/**
 * Knowledge Base Auto-Resolution Engine
 * Used by ticket system to attempt Tier 1 auto-resolution
 */
class KnowledgeBaseResolver {
    private $db;
    private $confidenceThreshold = 0.6; // 60% match required
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Find best matching KB entry for ticket content
     */
    public function findBestMatch($content) {
        $keywords = $this->extractKeywords($content);
        
        if (empty($keywords)) {
            return null;
        }
        
        $entries = $this->getAllActiveEntries();
        $bestMatch = null;
        $bestScore = 0;
        
        foreach ($entries as $entry) {
            $entryKeywords = array_map('trim', explode(',', strtolower($entry['keywords'])));
            $matchCount = count(array_intersect($keywords, $entryKeywords));
            $score = $matchCount / max(count($entryKeywords), 1);
            
            if ($score > $bestScore && $score >= $this->confidenceThreshold) {
                $bestScore = $score;
                $bestMatch = ['entry' => $entry, 'score' => $score];
            }
        }
        
        return $bestMatch;
    }
    
    /**
     * Extract meaningful keywords from text
     */
    private function extractKeywords($text) {
        $stopWords = ['the', 'is', 'at', 'which', 'on', 'a', 'an', 'and', 'or', 'but', 
                      'in', 'with', 'to', 'for', 'of', 'not', 'be', 'are', 'was', 'were',
                      'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
                      'could', 'should', 'may', 'might', 'must', 'can', 'i', 'my', 'me',
                      'you', 'your', 'it', 'its', 'this', 'that', 'these', 'those', 'hi',
                      'hello', 'please', 'help', 'need', 'want', 'thanks', 'thank'];
        
        $text = strtolower($text);
        $words = preg_split('/[\s,.\-:;!?]+/', $text);
        $keywords = [];
        
        foreach ($words as $word) {
            $word = preg_replace('/[^a-z0-9]/', '', $word);
            if (strlen($word) > 2 && !in_array($word, $stopWords)) {
                $keywords[] = $word;
            }
        }
        
        return array_unique($keywords);
    }
    
    /**
     * Get all active KB entries
     */
    private function getAllActiveEntries() {
        $result = $this->db->query("SELECT * FROM knowledge_base ORDER BY times_used DESC");
        $entries = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $entries[] = $row;
        }
        return $entries;
    }
    
    /**
     * Attempt auto-resolution for a ticket
     * Returns: KB entry if match found, null otherwise
     */
    public function attemptResolution($ticketId, $subject, $message) {
        $content = $subject . ' ' . $message;
        $match = $this->findBestMatch($content);
        
        if ($match) {
            // Increment usage count
            $this->db->exec("UPDATE knowledge_base SET times_used = times_used + 1 WHERE id = {$match['entry']['id']}");
            return $match;
        }
        
        return null;
    }
    
    /**
     * Record resolution feedback (did it help?)
     */
    public function recordFeedback($kbId, $helped) {
        $entry = $this->db->querySingle("SELECT success_rate, times_used FROM knowledge_base WHERE id = $kbId", true);
        
        if ($entry) {
            $currentRate = $entry['success_rate'];
            $timesUsed = $entry['times_used'];
            
            // Calculate new success rate (weighted average)
            $newRate = (($currentRate * ($timesUsed - 1)) + ($helped ? 100 : 0)) / $timesUsed;
            
            $stmt = $this->db->prepare("UPDATE knowledge_base SET success_rate = :rate WHERE id = :id");
            $stmt->bindValue(':rate', $newRate, SQLITE3_FLOAT);
            $stmt->bindValue(':id', $kbId, SQLITE3_INTEGER);
            $stmt->execute();
        }
    }
}
