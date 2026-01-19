<?php
/**
 * TrueVault VPN - Page Builder
 * 
 * Visual page editor with drag-and-drop section management
 * 
 * FEATURES:
 * - Create/edit pages
 * - Add/remove/reorder sections
 * - 11 section types (hero, features, pricing, etc.)
 * - Drag-and-drop with SortableJS
 * - Live preview
 * - Save/revert changes
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Initialize
define('TRUEVAULT_INIT', true);
session_start();

require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/PageBuilder.php';

// Check authentication
if (!Auth::isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// Get page from query string
$pageSlug = $_GET['page'] ?? 'home';
$page = PageBuilder::getPage($pageSlug);

if (!$page) {
    die('Page not found');
}

// Get sections for this page
$sections = PageBuilder::getSections($page['id'], false); // Get all including hidden

$pageTitle = 'Page Builder';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - TrueVault Admin</title>
    
    <!-- SortableJS for drag-and-drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
        }
        
        .top-bar {
            background: white;
            border-bottom: 2px solid #e5e7eb;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .top-bar h1 {
            font-size: 20px;
            color: #333;
        }
        
        .page-selector {
            padding: 8px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .top-bar-actions {
            display: flex;
            gap: 10px;
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
            font-size: 14px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
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
        
        .main-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            height: calc(100vh - 60px);
        }
        
        .sidebar {
            background: white;
            border-right: 2px solid #e5e7eb;
            padding: 20px;
            overflow-y: auto;
        }
        
        .sidebar h2 {
            font-size: 16px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .section-types {
            display: grid;
            gap: 10px;
        }
        
        .section-type-btn {
            padding: 12px;
            background: #f9fafb;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: left;
            font-size: 14px;
            color: #374151;
        }
        
        .section-type-btn:hover {
            background: #eff6ff;
            border-color: #667eea;
            border-style: solid;
        }
        
        .section-type-btn .icon {
            margin-right: 8px;
            font-size: 16px;
        }
        
        .canvas {
            padding: 30px;
            overflow-y: auto;
            background: #f9fafb;
        }
        
        .canvas-inner {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            min-height: 500px;
        }
        
        .empty-canvas {
            padding: 60px 20px;
            text-align: center;
            color: #9ca3af;
        }
        
        .empty-canvas .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .sections-list {
            padding: 20px;
        }
        
        .section-block {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: move;
            transition: all 0.3s;
        }
        
        .section-block:hover {
            border-color: #667eea;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.2);
        }
        
        .section-block.sortable-ghost {
            opacity: 0.4;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .section-title {
            font-weight: 700;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-title .handle {
            cursor: grab;
            color: #9ca3af;
        }
        
        .section-actions {
            display: flex;
            gap: 8px;
        }
        
        .icon-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 16px;
        }
        
        .icon-btn:hover {
            background: #667eea;
            color: white;
        }
        
        .icon-btn.danger:hover {
            background: #ef4444;
        }
        
        .section-preview {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.6;
        }
        
        .visibility-toggle {
            width: 40px;
            height: 22px;
            background: #ccc;
            border-radius: 22px;
            position: relative;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .visibility-toggle.active {
            background: #10b981;
        }
        
        .visibility-toggle:before {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: white;
            top: 3px;
            left: 3px;
            transition: 0.3s;
        }
        
        .visibility-toggle.active:before {
            transform: translateX(18px);
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
            max-width: 700px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
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
        .form-group textarea {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="top-bar-left">
            <h1>📝 Page Builder</h1>
            <select class="page-selector" onchange="window.location.href='?page='+this.value">
                <?php 
                $allPages = PageBuilder::listPages();
                foreach ($allPages as $p): 
                ?>
                    <option value="<?= htmlspecialchars($p['slug']) ?>" <?= $p['slug'] == $pageSlug ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="top-bar-actions">
            <button class="btn btn-secondary" onclick="revertChanges()">🔄 Revert</button>
            <button class="btn btn-success" onclick="saveChanges()">💾 Save Page</button>
            <a href="/admin/" class="btn btn-primary">← Back</a>
        </div>
    </div>

    <div class="main-container">
        <div class="sidebar">
            <h2>Add Sections</h2>
            <div class="section-types">
                <button class="section-type-btn" onclick="addSection('hero')">
                    <span class="icon">🎯</span> Hero Banner
                </button>
                <button class="section-type-btn" onclick="addSection('features')">
                    <span class="icon">⭐</span> Features Grid
                </button>
                <button class="section-type-btn" onclick="addSection('pricing')">
                    <span class="icon">💰</span> Pricing Table
                </button>
                <button class="section-type-btn" onclick="addSection('cta')">
                    <span class="icon">📢</span> Call to Action
                </button>
                <button class="section-type-btn" onclick="addSection('testimonials')">
                    <span class="icon">💬</span> Testimonials
                </button>
                <button class="section-type-btn" onclick="addSection('faq')">
                    <span class="icon">❓</span> FAQ
                </button>
                <button class="section-type-btn" onclick="addSection('content')">
                    <span class="icon">📄</span> Content Block
                </button>
                <button class="section-type-btn" onclick="addSection('stats')">
                    <span class="icon">📊</span> Statistics
                </button>
                <button class="section-type-btn" onclick="addSection('team')">
                    <span class="icon">👥</span> Team Members
                </button>
                <button class="section-type-btn" onclick="addSection('contact')">
                    <span class="icon">📧</span> Contact Form
                </button>
                <button class="section-type-btn" onclick="addSection('gallery')">
                    <span class="icon">🖼️</span> Image Gallery
                </button>
            </div>
        </div>

        <div class="canvas">
            <div class="canvas-inner">
                <?php if (empty($sections)): ?>
                    <div class="empty-canvas">
                        <div class="icon">👈</div>
                        <p>Click a section type on the left to add content to your page</p>
                    </div>
                <?php else: ?>
                    <div class="sections-list" id="sections-list">
                        <?php foreach ($sections as $section): ?>
                            <div class="section-block" data-id="<?= $section['id'] ?>">
                                <div class="section-header">
                                    <div class="section-title">
                                        <span class="handle">☰</span>
                                        <span><?= ucwords(str_replace('_', ' ', $section['section_type'])) ?></span>
                                    </div>
                                    <div class="section-actions">
                                        <div class="visibility-toggle <?= $section['is_visible'] ? 'active' : '' ?>"
                                             onclick="toggleVisibility(<?= $section['id'] ?>, this)"
                                             title="Toggle visibility">
                                        </div>
                                        <button class="icon-btn" onclick="editSection(<?= $section['id'] ?>)" title="Edit">
                                            ✏️
                                        </button>
                                        <button class="icon-btn danger" onclick="deleteSection(<?= $section['id'] ?>)" title="Delete">
                                            🗑️
                                        </button>
                                    </div>
                                </div>
                                <div class="section-preview">
                                    <?php 
                                    $data = json_decode($section['section_data'], true);
                                    $preview = '';
                                    if (isset($data['title'])) $preview .= 'Title: ' . substr($data['title'], 0, 50);
                                    elseif (isset($data['heading'])) $preview .= 'Heading: ' . substr($data['heading'], 0, 50);
                                    elseif (isset($data['content'])) $preview .= substr(strip_tags($data['content']), 0, 80);
                                    echo htmlspecialchars($preview ?: 'Click edit to configure...');
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Section Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Section</h2>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <div id="sectionEditor"></div>
        </div>
    </div>

    <script>
        const pageId = <?= $page['id'] ?>;
        let sortable = null;
        let currentSectionId = null;
        
        // Initialize Sortable for drag-and-drop
        document.addEventListener('DOMContentLoaded', function() {
            const list = document.getElementById('sections-list');
            if (list) {
                sortable = Sortable.create(list, {
                    animation: 150,
                    handle: '.handle',
                    ghostClass: 'sortable-ghost',
                    onEnd: function(evt) {
                        console.log('Section moved from', evt.oldIndex, 'to', evt.newIndex);
                    }
                });
            }
        });
        
        // Add new section
        function addSection(type) {
            const data = {
                title: `New ${type.charAt(0).toUpperCase() + type.slice(1)} Section`,
                content: 'Click edit to add content...'
            };
            
            fetch('/api/pages/add-section.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ page_id: pageId, type, data })
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    location.reload();
                } else {
                    alert('Failed to add section');
                }
            });
        }
        
        // Toggle section visibility
        function toggleVisibility(sectionId, element) {
            const isVisible = element.classList.contains('active');
            
            fetch('/api/pages/toggle-visibility.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ section_id: sectionId, visible: !isVisible })
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    element.classList.toggle('active');
                }
            });
        }
        
        // Edit section
        function editSection(sectionId) {
            // TODO: Load section data and show editor
            currentSectionId = sectionId;
            document.getElementById('editModal').classList.add('active');
        }
        
        // Delete section
        function deleteSection(sectionId) {
            if (!confirm('Delete this section?')) return;
            
            fetch('/api/pages/delete-section.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ section_id: sectionId })
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    location.reload();
                }
            });
        }
        
        // Save changes (reorder)
        function saveChanges() {
            const blocks = document.querySelectorAll('.section-block');
            const order = Array.from(blocks).map(b => parseInt(b.dataset.id));
            
            fetch('/api/pages/reorder-sections.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ page_id: pageId, order })
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    alert('Page saved successfully!');
                }
            });
        }
        
        // Revert changes
        function revertChanges() {
            if (confirm('Discard all unsaved changes?')) {
                location.reload();
            }
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('editModal').classList.remove('active');
        }
    </script>
</body>
</html>
