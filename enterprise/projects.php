<?php
require_once 'config.php';

$projects = getProjects();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects - Enterprise Hub</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1600px; margin: 0 auto; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h1 { font-size: 2.5rem; }
        .back-btn { padding: 0.75rem 1.5rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; text-decoration: none; }
        .section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .filters { display: flex; gap: 1rem; margin-bottom: 2rem; }
        .filter-btn { padding: 0.5rem 1rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: #fff; cursor: pointer; }
        .filter-btn.active { background: rgba(0,217,255,0.2); border-color: #00d9ff; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; }
        .projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 1.5rem; }
        .project-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 1.5rem; transition: 0.3s; cursor: pointer; }
        .project-card:hover { transform: translateY(-3px); border-color: #00d9ff; }
        .project-header { margin-bottom: 1rem; }
        .project-name { font-size: 1.4rem; font-weight: 700; color: #00d9ff; margin-bottom: 0.5rem; }
        .project-client { color: #888; font-size: 0.9rem; }
        .project-desc { color: #ccc; font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem; }
        .progress-bar { margin: 1rem 0; }
        .progress-label { display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.85rem; }
        .progress-track { height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #00d9ff, #00ff88); transition: width 0.3s; }
        .project-meta { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); font-size: 0.85rem; }
        .meta-item { display: flex; flex-direction: column; }
        .meta-label { color: #888; font-size: 0.75rem; margin-bottom: 0.25rem; }
        .meta-value { color: #fff; font-weight: 600; }
        .status-badge { padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .status-active { background: rgba(0,255,136,0.2); color: #00ff88; }
        .status-on_hold { background: rgba(255,184,77,0.2); color: #ffb84d; }
        .status-completed { background: rgba(0,217,255,0.2); color: #00d9ff; }
        .priority-high { background: rgba(255,100,100,0.2); color: #ff6464; }
        .priority-medium { background: rgba(255,184,77,0.2); color: #ffb84d; }
        .priority-low { background: rgba(0,255,136,0.2); color: #00ff88; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📊 Projects</h1>
        <a href="/enterprise/" class="back-btn">← Dashboard</a>
    </div>

    <div class="section">
        <div class="section-header">
            <h2>All Projects (<?= count($projects) ?>)</h2>
            <button class="btn btn-primary" onclick="showNewProjectForm()">+ New Project</button>
        </div>

        <div class="filters">
            <button class="filter-btn active" onclick="filterProjects('all')">All</button>
            <button class="filter-btn" onclick="filterProjects('active')">Active</button>
            <button class="filter-btn" onclick="filterProjects('on_hold')">On Hold</button>
            <button class="filter-btn" onclick="filterProjects('completed')">Completed</button>
        </div>

        <div class="projects-grid" id="projectsGrid">
            <?php foreach ($projects as $project): ?>
                <div class="project-card" data-status="<?= $project['status'] ?>" onclick="viewProject(<?= $project['id'] ?>)">
                    <div class="project-header">
                        <div class="project-name"><?= htmlspecialchars($project['project_name']) ?></div>
                        <div class="project-client">Client: <?= htmlspecialchars($project['company_name']) ?></div>
                    </div>
                    
                    <?php if ($project['description']): ?>
                        <div class="project-desc"><?= htmlspecialchars(substr($project['description'], 0, 120)) . (strlen($project['description']) > 120 ? '...' : '') ?></div>
                    <?php endif; ?>
                    
                    <div class="progress-bar">
                        <div class="progress-label">
                            <span>Progress</span>
                            <span><?= $project['completion_percent'] ?>%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" style="width: <?= $project['completion_percent'] ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="project-meta">
                        <div class="meta-item">
                            <div class="meta-label">Status</div>
                            <div class="meta-value">
                                <span class="status-badge status-<?= $project['status'] ?>">
                                    <?= strtoupper(str_replace('_', ' ', $project['status'])) ?>
                                </span>
                            </div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Priority</div>
                            <div class="meta-value">
                                <span class="status-badge priority-<?= $project['priority'] ?>">
                                    <?= strtoupper($project['priority']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Type</div>
                            <div class="meta-value"><?= strtoupper(str_replace('_', ' ', $project['project_type'])) ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Budget</div>
                            <div class="meta-value">
                                <?= $project['budget'] ? '$' . number_format($project['budget']) : 'Hourly' ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function showNewProjectForm() {
    alert('New Project Form - To be implemented with modal');
}

function viewProject(projectId) {
    window.location.href = '/enterprise/project-details.php?id=' + projectId;
}

function filterProjects(status) {
    const cards = document.querySelectorAll('.project-card');
    const buttons = document.querySelectorAll('.filter-btn');
    
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    cards.forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
</body>
</html>
