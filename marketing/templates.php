<?php
require_once 'config.php';

$templates = getEmailTemplates();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Templates - Marketing</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .back-btn { padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; text-decoration: none; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .btn { padding: 0.75rem 1.5rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .templates-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .template-card { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; transition: 0.3s; cursor: pointer; }
        .template-card:hover { transform: translateY(-3px); border: 1px solid #00d9ff; }
        .template-type { display: inline-block; padding: 0.25rem 0.75rem; background: rgba(0,217,255,0.2); color: #00d9ff; border-radius: 6px; font-size: 0.75rem; margin-bottom: 1rem; }
        .template-name { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .template-subject { color: #888; font-size: 0.9rem; margin-bottom: 1rem; }
        .template-preview { background: rgba(0,0,0,0.3); border-radius: 8px; padding: 1rem; font-size: 0.85rem; max-height: 100px; overflow: hidden; position: relative; }
        .template-preview::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 40px; background: linear-gradient(transparent, rgba(0,0,0,0.8)); }
        .template-variables { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); font-size: 0.85rem; color: #666; }
    </style>
</head>
<body>
<div class="container">
    <header>
        <div>
            <a href="/marketing/index.php" class="back-btn">← Back</a>
            <h1 style="display: inline; margin-left: 1rem;">Email Templates</h1>
        </div>
        <button class="btn">+ New Template</button>
    </header>

    <div class="templates-grid">
        <?php if (empty($templates)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 4rem; color: #666;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📧</div>
                <p>No email templates yet. Create your first template!</p>
            </div>
        <?php else: ?>
            <?php foreach ($templates as $template): ?>
                <div class="template-card" onclick="alert('Template editor coming soon!')">
                    <span class="template-type"><?= ucfirst($template['template_type']) ?></span>
                    <div class="template-name"><?= htmlspecialchars($template['template_name']) ?></div>
                    <div class="template-subject">Subject: <?= htmlspecialchars($template['subject_line']) ?></div>
                    <div class="template-preview">
                        <?= htmlspecialchars(substr(strip_tags($template['text_body']), 0, 150)) ?>...
                    </div>
                    <div class="template-variables">
                        Variables: <?= htmlspecialchars(implode(', ', json_decode($template['variables'] ?? '[]', true))) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
