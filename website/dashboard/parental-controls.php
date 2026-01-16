.log-table td {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .log-table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }
        
        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            color: #0f0f1a;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 217, 255, 0.3);
        }
        
        .btn-danger {
            background: rgba(255, 80, 80, 0.15);
            color: #ff5050;
            border: 1px solid rgba(255, 80, 80, 0.4);
        }
        
        .btn-danger:hover {
            background: rgba(255, 80, 80, 0.25);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .upgrade-notice {
            background: linear-gradient(135deg, rgba(0, 217, 255, 0.1), rgba(0, 255, 136, 0.1));
            border: 1px solid rgba(0, 217, 255, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .upgrade-notice h3 {
            color: #00d9ff;
            margin-bottom: 10px;
        }
        
        .upgrade-notice ul {
            margin-left: 20px;
            color: #aaa;
        }
        
        .upgrade-notice ul li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>🛡️ Parental Controls</h1>
            </div>
            <div class="nav-links">
                <a href="index.php">🏠 Dashboard</a>
                <a href="devices.php">📱 Devices</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Master Toggle -->
        <div class="section">
            <form method="POST">
                <input type="hidden" name="action" value="toggle_filtering">
                <div class="toggle-section">
                    <div class="toggle-info">
                        <h3>🛡️ Content Filtering</h3>
                        <p>Enable parental controls to filter inappropriate content</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="enabled" <?= $filteringEnabled ? 'checked' : '' ?> onchange="this.form.submit()">
                        <span class="slider"></span>
                    </label>
                </div>
            </form>
        </div>

        <?php if ($filteringEnabled): ?>
        
        <!-- Category Filters -->
        <div class="section">
            <h2>📂 Content Categories</h2>
            <form method="POST" id="categoriesForm">
                <input type="hidden" name="action" value="update_categories">
                <div class="categories-grid">
                    <?php foreach ($availableCategories as $key => $data): ?>
                        <label class="category-card <?= in_array($key, $selectedCategories) ? 'selected' : '' ?>">
                            <input type="checkbox" name="categories[]" value="<?= $key ?>" 
                                   <?= in_array($key, $selectedCategories) ? 'checked' : '' ?>
                                   onchange="this.closest('.category-card').classList.toggle('selected', this.checked)">
                            <span class="category-icon"><?= $data[1] ?></span>
                            <?= $data[0] ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 15px;">💾 Save Categories</button>
            </form>
        </div>

        <!-- Blocked Domains -->
        <div class="section">
            <h2>🚫 Blocked Domains</h2>
            
            <form method="POST" style="margin-bottom: 20px;">
                <input type="hidden" name="action" value="add_domain">
                <div class="form-group">
                    <label>Add Domain to Block:</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="domain" placeholder="example.com" required style="flex: 1;">
                        <button type="submit" class="btn btn-primary">➕ Add</button>
                    </div>
                </div>
            </form>

            <?php if (empty($blockedDomains)): ?>
                <div class="empty-state">
                    <p>No blocked domains yet. Add domains above to block them.</p>
                </div>
            <?php else: ?>
                <div class="domains-list">
                    <?php foreach ($blockedDomains as $domain): ?>
                        <div class="domain-item">
                            <span class="domain-name"><?= htmlspecialchars($domain['domain']) ?></span>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_domain">
                                <input type="hidden" name="domain_id" value="<?= $domain['id'] ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 6px 12px;">🗑️ Remove</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Blocked Requests Log -->
        <div class="section">
            <h2>📊 Recent Blocked Requests</h2>
            
            <?php if (empty($blockedRequests)): ?>
                <div class="empty-state">
                    <p>No blocked requests yet.</p>
                </div>
            <?php else: ?>
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Domain</th>
                            <th>Category</th>
                            <th>Device</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($blockedRequests, 0, 20) as $request): ?>
                            <tr>
                                <td><?= date('M j, g:i A', strtotime($request['blocked_at'])) ?></td>
                                <td style="font-family: monospace; color: #ff6b6b;"><?= htmlspecialchars($request['domain']) ?></td>
                                <td><?= htmlspecialchars($request['category'] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($request['device_name'] ?? 'Unknown') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Upgrade Notice for Advanced Features -->
        <div class="upgrade-notice">
            <h3>🚀 Coming Soon: Advanced Parental Controls</h3>
            <p>Advanced scheduling and device control features are coming in the next update:</p>
            <ul>
                <li>📅 <strong>Calendar Scheduling:</strong> Set internet access times (3-4pm, 5-6pm, 7-8pm, etc.)</li>
                <li>🔄 <strong>Recurring Schedules:</strong> Daily, weekly, or custom patterns</li>
                <li>🎮 <strong>Gaming Controls:</strong> Block gaming servers while allowing homework sites</li>
                <li>✅ <strong>Whitelist/Blacklist:</strong> Always-allow and always-block lists</li>
                <li>⏱️ <strong>Temporary Blocks:</strong> Block sites for 1 hour, until bedtime, etc.</li>
                <li>📱 <strong>Per-Device Rules:</strong> Different rules for each child's device</li>
            </ul>
            <p style="margin-top: 15px; color: #00d9ff;">These features are currently in development and will be available soon!</p>
        </div>

        <?php endif; ?>
    </div>
</body>
</html>
