<?php
/**
 * TrueVault VPN - Port Forwarding Dashboard
 * Manage port forwarding rules for devices
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Check authentication
$auth = new Auth();
if (!$auth->isAuthenticated()) {
    header('Location: /login.html');
    exit;
}

$userId = $auth->getUserId();
$db = new Database();

// Get user info
$stmt = $db->users->prepare("SELECT email, tier FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: /login.html');
    exit;
}

// Get port forwarding rules
$stmt = $db->portForwards->prepare("
    SELECT 
        pf.id,
        pf.device_name,
        pf.internal_ip,
        pf.external_port,
        pf.internal_port,
        pf.protocol,
        pf.status,
        pf.created_at,
        d.device_type,
        d.icon
    FROM port_forwarding_rules pf
    LEFT JOIN discovered_devices d ON d.internal_ip = pf.internal_ip
    WHERE pf.user_id = ?
    ORDER BY pf.created_at DESC
");
$stmt->execute([$userId]);
$rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available devices
$stmt = $db->portForwards->prepare("
    SELECT id, device_name, internal_ip, device_type, icon, vendor
    FROM discovered_devices
    WHERE user_id = ?
    ORDER BY device_name
");
$stmt->execute([$userId]);
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Port Forwarding - TrueVault VPN</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0f1a, #1a1a2e);
            color: #fff;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .card h2 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #00d9ff;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
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
            background: rgba(255, 80, 80, 0.2);
            color: #ff5050;
            border: 1px solid #ff5050;
        }

        .rules-table {
            width: 100%;
            border-collapse: collapse;
        }

        .rules-table th,
        .rules-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .rules-table th {
            background: rgba(0, 217, 255, 0.1);
            font-weight: 600;
        }

        .icon {
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }

        .status-active {
            color: #00ff88;
        }

        .status-inactive {
            color: #ff5050;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #999;
        }

        input, select {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Port Forwarding</h1>

        <!-- Add New Rule Card -->
        <div class="card">
            <h2>Add Port Forwarding Rule</h2>
            <form id="addRuleForm">
                <div class="form-group">
                    <label>Device</label>
                    <select name="device_id" id="deviceSelect" required>
                        <option value="">Select a device...</option>
                        <?php foreach ($devices as $device): ?>
                        <option value="<?= htmlspecialchars($device['id']) ?>" 
                                data-ip="<?= htmlspecialchars($device['internal_ip']) ?>"
                                data-name="<?= htmlspecialchars($device['device_name']) ?>"
                                data-icon="<?= htmlspecialchars($device['icon']) ?>">
                            <?= $device['icon'] ?> <?= htmlspecialchars($device['device_name']) ?> 
                            (<?= htmlspecialchars($device['internal_ip']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>External Port (what others use to connect)</label>
                    <input type="number" name="external_port" min="1024" max="65535" required 
                           placeholder="e.g., 25565 for Minecraft">
                </div>

                <div class="form-group">
                    <label>Internal Port (your device's port)</label>
                    <input type="number" name="internal_port" min="1" max="65535" required 
                           placeholder="e.g., 25565">
                </div>

                <div class="form-group">
                    <label>Protocol</label>
                    <select name="protocol" required>
                        <option value="both">Both TCP & UDP</option>
                        <option value="tcp">TCP Only</option>
                        <option value="udp">UDP Only</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Add Port Forwarding Rule</button>
            </form>
        </div>

        <!-- Existing Rules Card -->
        <div class="card">
            <h2>Active Port Forwarding Rules</h2>
            
            <?php if (empty($rules)): ?>
            <div class="empty-state">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🔌</div>
                <p>No port forwarding rules yet.</p>
                <p>Add a rule above to get started!</p>
            </div>
            <?php else: ?>
            <table class="rules-table">
                <thead>
                    <tr>
                        <th>Device</th>
                        <th>Internal IP</th>
                        <th>External Port</th>
                        <th>Internal Port</th>
                        <th>Protocol</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rules as $rule): ?>
                    <tr>
                        <td>
                            <span class="icon"><?= htmlspecialchars($rule['icon'] ?? '🖥️') ?></span>
                            <?= htmlspecialchars($rule['device_name']) ?>
                        </td>
                        <td><?= htmlspecialchars($rule['internal_ip']) ?></td>
                        <td><?= htmlspecialchars($rule['external_port']) ?></td>
                        <td><?= htmlspecialchars($rule['internal_port']) ?></td>
                        <td><?= strtoupper(htmlspecialchars($rule['protocol'])) ?></td>
                        <td class="<?= $rule['status'] === 'active' ? 'status-active' : 'status-inactive' ?>">
                            <?= $rule['status'] === 'active' ? '✓ Active' : '✗ Inactive' ?>
                        </td>
                        <td>
                            <button class="btn btn-danger" onclick="deleteRule(<?= $rule['id'] ?>)">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <p style="margin-top: 2rem; text-align: center; color: #666;">
            <a href="/dashboard/" style="color: #00d9ff;">← Back to Dashboard</a>
        </p>
    </div>

    <script>
        // Add rule form submission
        document.getElementById('addRuleForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const deviceSelect = document.getElementById('deviceSelect');
            const selectedOption = deviceSelect.options[deviceSelect.selectedIndex];
            
            const data = {
                device_id: formData.get('device_id'),
                device_name: selectedOption.dataset.name,
                internal_ip: selectedOption.dataset.ip,
                external_port: formData.get('external_port'),
                internal_port: formData.get('internal_port'),
                protocol: formData.get('protocol')
            };

            try {
                const response = await fetch('/api/port-forwarding/create-rule.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ Port forwarding rule added successfully!');
                    location.reload();
                } else {
                    alert('❌ Error: ' + result.error);
                }
            } catch (error) {
                alert('❌ Failed to add rule: ' + error.message);
            }
        });

        // Delete rule function
        async function deleteRule(ruleId) {
            if (!confirm('Are you sure you want to delete this port forwarding rule?')) {
                return;
            }

            try {
                const response = await fetch('/api/port-forwarding/delete-rule.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ rule_id: ruleId })
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ Port forwarding rule deleted!');
                    location.reload();
                } else {
                    alert('❌ Error: ' + result.error);
                }
            } catch (error) {
                alert('❌ Failed to delete rule: ' + error.message);
            }
        }
    </script>
</body>
</html>
