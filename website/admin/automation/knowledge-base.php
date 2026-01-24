<?php
/**
 * TrueVault VPN - Knowledge Base Management
 * Part of Business Automation - Auto-resolution system
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

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $stmt = $automationDb->prepare("
            INSERT INTO knowledge_base (category, keywords, question, answer, resolution_steps, success_rate)
            VALUES (:category, :keywords, :question, :answer, :steps, 90)
        ");
        $stmt->bindValue(':category', $_POST['category'], SQLITE3_TEXT);
        $stmt->bindValue(':keywords', $_POST['keywords'], SQLITE3_TEXT);
        $stmt->bindValue(':question', $_POST['question'], SQLITE3_TEXT);
        $stmt->bindValue(':answer', $_POST['answer'], SQLITE3_TEXT);
        $stmt->bindValue(':steps', $_POST['steps'], SQLITE3_TEXT);
        $stmt->execute();
        $message = 'Knowledge base entry added successfully!';
        $messageType = 'success';
    }
    
    if ($action === 'update') {
        $stmt = $automationDb->prepare("
            UPDATE knowledge_base SET
                category = :category,
                keywords = :keywords,
                question = :question,
                answer = :answer,
                resolution_steps = :steps
            WHERE id = :id
        ");
        $stmt->bindValue(':category', $_POST['category'], SQLITE3_TEXT);
        $stmt->bindValue(':keywords', $_POST['keywords'], SQLITE3_TEXT);
        $stmt->bindValue(':question', $_POST['question'], SQLITE3_TEXT);
        $stmt->bindValue(':answer', $_POST['answer'], SQLITE3_TEXT);
        $stmt->bindValue(':steps', $_POST['steps'], SQLITE3_TEXT);
        $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
        $stmt->execute();
        $message = 'Knowledge base entry updated successfully!';
        $messageType = 'success';
    }
    
    if ($action === 'delete') {
        $stmt = $automationDb->prepare("DELETE FROM knowledge_base WHERE id = :id");
        $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
        $stmt->execute();
        $message = 'Knowledge base entry deleted!';
        $messageType = 'success';
    }
}

// Filters
$categoryFilter = $_GET['category'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// Build query
$whereConditions = [];
$params = [];

if ($categoryFilter) {
    $whereConditions[] = "category = :category";
    $params[':category'] = $categoryFilter;
}

if ($searchTerm) {
    $whereConditions[] = "(keywords LIKE :search OR question LIKE :search OR answer LIKE :search)";
    $params[':search'] = '%' . $searchTerm . '%';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get entries
$query = "SELECT * FROM knowledge_base $whereClause ORDER BY category, times_used DESC";
$stmt = $automationDb->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, SQLITE3_TEXT);
}
$result = $stmt->execute();
$entries = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $entries[] = $row;
}

// Get categories with counts
$categoriesResult = $automationDb->query("SELECT category, COUNT(*) as count FROM knowledge_base GROUP BY category ORDER BY category");
$categories = [];
while ($row = $categoriesResult->fetchArray(SQLITE3_ASSOC)) {
    $categories[] = $row;
}

// Get stats
$stats = [
    'total' => $automationDb->querySingle("SELECT COUNT(*) FROM knowledge_base"),
    'total_uses' => $automationDb->querySingle("SELECT SUM(times_used) FROM knowledge_base"),
    'avg_success' => $automationDb->querySingle("SELECT AVG(success_rate) FROM knowledge_base"),
    'most_used' => $automationDb->querySingle("SELECT question FROM knowledge_base ORDER BY times_used DESC LIMIT 1")
];

$automationDb->close();
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
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: <?php echo $primaryColor; ?>;
        }
        
        .stat-label { color: #888; font-size: 0.85rem; margin-top: 5px; }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
        }
        
        .card {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-title i { color: <?php echo $primaryColor; ?>; }
        
        .category-list { list-style: none; }
        
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            border-radius: 6px;
            cursor: pointer;
            margin-bottom: 5px;
            transition: background 0.2s;
        }
        
        .category-item:hover { background: rgba(255,255,255,0.05); }
        .category-item.active { background: rgba(0,212,255,0.1); border-left: 3px solid <?php echo $primaryColor; ?>; }
        
        .category-count {
            background: rgba(255,255,255,0.1);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
        }
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filters input {
            padding: 10px 15px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: <?php echo $textColor; ?>;
            flex: 1;
            min-width: 200px;
        }
        
        .kb-entry {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .kb-entry:hover { border-color: rgba(255,255,255,0.1); }
        
        .kb-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .kb-question { font-weight: 600; font-size: 1.05rem; flex: 1; }
        
        .kb-category {
            padding: 4px 10px;
            background: <?php echo $primaryColor; ?>20;
            color: <?php echo $primaryColor; ?>;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .kb-answer { color: #aaa; margin-bottom: 12px; line-height: 1.6; }
        
        .kb-meta {
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 0.85rem;
        }
        
        .kb-meta span { display: flex; align-items: center; gap: 5px; }
        
        .kb-keywords {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }
        
        .keyword-tag {
            padding: 3px 8px;
            background: rgba(255,255,255,0.05);
            border-radius: 4px;
            font-size: 0.75rem;
            color: #888;
        }
        
        .kb-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(0,200,100,0.1);
            border: 1px solid rgba(0,200,100,0.3);
            color: #00c864;
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
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .modal-title { font-size: 1.3rem; font-weight: 600; }
        
        .close-btn {
            background: none;
            border: none;
            color: #888;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .form-group { margin-bottom: 20px; }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #ccc;
            font-weight: 500;
        }
        
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: <?php echo $textColor; ?>;
            font-size: 1rem;
        }
        
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: <?php echo $primaryColor; ?>;
        }
        
        .form-group textarea { resize: vertical; min-height: 100px; }
        
        .form-group small { color: #666; font-size: 0.8rem; }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #666;
        }
        
        .empty-state i { font-size: 3rem; margin-bottom: 15px; }
        
        @media (max-width: 900px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div class="header-left">
                <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
                <h1 class="page-title">Knowledge Base</h1>
            </div>
            <button class="btn btn-primary" onclick="showAddModal()">
                <i class="fas fa-plus"></i> Add Entry
            </button>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Entries</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_uses'] ?? 0; ?></div>
                <div class="stat-label">Times Used</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo round($stats['avg_success'] ?? 0); ?>%</div>
                <div class="stat-label">Avg Success Rate</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($categories); ?></div>
                <div class="stat-label">Categories</div>
            </div>
        </div>
        
        <div class="grid-2">
            <div class="card">
                <h3 class="card-title"><i class="fas fa-folder"></i> Categories</h3>
                <ul class="category-list">
                    <li>
                        <a href="knowledge-base.php" class="category-item <?php echo !$categoryFilter ? 'active' : ''; ?>">
                            <span>All Entries</span>
                            <span class="category-count"><?php echo $stats['total']; ?></span>
                        </a>
                    </li>
                    <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="?category=<?php echo urlencode($cat['category']); ?>" class="category-item <?php echo $categoryFilter === $cat['category'] ? 'active' : ''; ?>">
                            <span><?php echo ucfirst($cat['category']); ?></span>
                            <span class="category-count"><?php echo $cat['count']; ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div>
                <form method="GET" class="filters">
                    <?php if ($categoryFilter): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($categoryFilter); ?>">
                    <?php endif; ?>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search knowledge base...">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                </form>
                
                <?php if (empty($entries)): ?>
                <div class="card empty-state">
                    <i class="fas fa-book-open"></i>
                    <h3>No Entries Found</h3>
                    <p>No knowledge base entries match your filters.</p>
                </div>
                <?php else: ?>
                <?php foreach ($entries as $entry): ?>
                <div class="kb-entry">
                    <div class="kb-header">
                        <div class="kb-question"><?php echo htmlspecialchars($entry['question']); ?></div>
                        <span class="kb-category"><?php echo htmlspecialchars($entry['category']); ?></span>
                    </div>
                    <div class="kb-answer"><?php echo htmlspecialchars($entry['answer']); ?></div>
                    <div class="kb-meta">
                        <span><i class="fas fa-check-circle"></i> <?php echo $entry['success_rate']; ?>% success</span>
                        <span><i class="fas fa-chart-line"></i> Used <?php echo $entry['times_used']; ?> times</span>
                    </div>
                    <div class="kb-keywords">
                        <?php foreach (explode(',', $entry['keywords']) as $keyword): ?>
                        <span class="keyword-tag"><?php echo htmlspecialchars(trim($keyword)); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="kb-actions">
                        <button class="btn btn-sm btn-secondary" onclick="editEntry(<?php echo htmlspecialchars(json_encode($entry)); ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this entry?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $entry['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div class="modal" id="entryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Add Knowledge Base Entry</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
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
                    <label>Question</label>
                    <input type="text" name="question" id="entryQuestion" required placeholder="e.g., How do I reset my password?">
                </div>
                
                <div class="form-group">
                    <label>Keywords</label>
                    <input type="text" name="keywords" id="entryKeywords" required placeholder="password, reset, forgot, login">
                    <small>Comma-separated keywords for auto-matching</small>
                </div>
                
                <div class="form-group">
                    <label>Answer</label>
                    <textarea name="answer" id="entryAnswer" required placeholder="Provide a clear answer to the question..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Resolution Steps (JSON)</label>
                    <textarea name="steps" id="entrySteps" placeholder='["Step 1", "Step 2", "Step 3"]'></textarea>
                    <small>Optional JSON array of steps</small>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Entry</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Knowledge Base Entry';
            document.getElementById('formAction').value = 'add';
            document.getElementById('entryForm').reset();
            document.getElementById('entryModal').classList.add('show');
        }
        
        function editEntry(entry) {
            document.getElementById('modalTitle').textContent = 'Edit Knowledge Base Entry';
            document.getElementById('formAction').value = 'update';
            document.getElementById('entryId').value = entry.id;
            document.getElementById('entryCategory').value = entry.category;
            document.getElementById('entryQuestion').value = entry.question;
            document.getElementById('entryKeywords').value = entry.keywords;
            document.getElementById('entryAnswer').value = entry.answer;
            document.getElementById('entrySteps').value = entry.resolution_steps || '';
            document.getElementById('entryModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('entryModal').classList.remove('show');
        }
        
        document.getElementById('entryModal').addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) closeModal();
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>
