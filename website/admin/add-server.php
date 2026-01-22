<?php
/**
 * Admin Add Server Form
 * 
 * Form to add new VPN servers to the system
 * 
 * @created January 23, 2026
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

// Check admin auth
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $countryCode = trim($_POST['country_code'] ?? '');
        $ipAddress = trim($_POST['ip_address'] ?? '');
        $provider = trim($_POST['provider'] ?? '');
        $publicKey = trim($_POST['public_key'] ?? '');
        $dedicatedEmail = trim($_POST['dedicated_email'] ?? '');
        $portForwarding = isset($_POST['port_forwarding']) ? 1 : 0;
        $highBandwidth = isset($_POST['high_bandwidth']) ? 1 : 0;
        $streamingOptimized = isset($_POST['streaming_optimized']) ? 1 : 0;
        $monthlyCost = floatval($_POST['monthly_cost'] ?? 0);
        
        // Validation
        if (empty($name) || empty($location) || empty($ipAddress)) {
            throw new Exception('Name, location, and IP address are required');
        }
        
        // Validate IP address
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new Exception('Invalid IP address format');
        }
        
        $serversDb = Database::getInstance('servers');
        
        // Check for duplicate IP
        $stmt = $serversDb->prepare("SELECT id FROM servers WHERE ip_address = :ip");
        $stmt->bindValue(':ip', $ipAddress, SQLITE3_TEXT);
        $result = $stmt->execute();
        if ($result->fetchArray()) {
            throw new Exception('A server with this IP address already exists');
        }
        
        // Insert new server
        $stmt = $serversDb->prepare("
            INSERT INTO servers (
                name, location, country_code, ip_address, provider, public_key,
                dedicated_user_email, port_forwarding_allowed, high_bandwidth_allowed,
                streaming_optimized, monthly_cost, status, created_at
            ) VALUES (
                :name, :location, :cc, :ip, :provider, :pk,
                :dedicated, :pf, :hb, :stream, :cost, 'active', CURRENT_TIMESTAMP
            )
        ");
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':location', $location, SQLITE3_TEXT);
        $stmt->bindValue(':cc', $countryCode, SQLITE3_TEXT);
        $stmt->bindValue(':ip', $ipAddress, SQLITE3_TEXT);
        $stmt->bindValue(':provider', $provider, SQLITE3_TEXT);
        $stmt->bindValue(':pk', $publicKey, SQLITE3_TEXT);
        $stmt->bindValue(':dedicated', $dedicatedEmail ?: null, SQLITE3_TEXT);
        $stmt->bindValue(':pf', $portForwarding, SQLITE3_INTEGER);
        $stmt->bindValue(':hb', $highBandwidth, SQLITE3_INTEGER);
        $stmt->bindValue(':stream', $streamingOptimized, SQLITE3_INTEGER);
        $stmt->bindValue(':cost', $monthlyCost, SQLITE3_FLOAT);
        $stmt->execute();
        
        $newId = $serversDb->lastInsertRowID();
        $message = "Server added successfully! ID: {$newId}";
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get theme
$themesDb = Database::getInstance('themes');
$themeResult = $themesDb->query("SELECT * FROM themes WHERE is_active = 1 LIMIT 1");
$theme = $themeResult->fetchArray(SQLITE3_ASSOC);
$primaryColor = $theme['primary_color'] ?? '#6366f1';
$bgColor = $theme['background_color'] ?? '#0f172a';
$cardBg = $theme['card_background'] ?? '#1e293b';
$textColor = $theme['text_color'] ?? '#f8fafc';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Server - TrueVault Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: <?= $bgColor ?>;
            color: <?= $textColor ?>;
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        h1 { font-size: 1.8rem; }
        .back-btn {
            padding: 10px 20px;
            background: <?= $cardBg ?>;
            color: <?= $textColor ?>;
            text-decoration: none;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .card {
            background: <?= $cardBg ?>;
            border-radius: 12px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #94a3b8;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            background: rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: <?= $textColor ?>;
            font-size: 1rem;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: <?= $primaryColor ?>;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
        }
        .btn-primary { background: <?= $primaryColor ?>; color: white; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: <?= $textColor ?>; margin-left: 10px; }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success { background: rgba(34,197,94,0.2); color: #22c55e; border: 1px solid #22c55e; }
        .alert-error { background: rgba(239,68,68,0.2); color: #ef4444; border: 1px solid #ef4444; }
        .help-text { font-size: 0.85rem; color: #64748b; margin-top: 5px; }
        h3 { margin: 30px 0 20px; color: <?= $primaryColor ?>; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>➕ Add New Server</h1>
            <a href="servers.php" class="back-btn">← Back to Servers</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <h3>Basic Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Server Name *</label>
                        <input type="text" name="name" placeholder="e.g., New York, Chicago, London" required>
                    </div>
                    <div class="form-group">
                        <label>Location *</label>
                        <input type="text" name="location" placeholder="e.g., New York, NY, USA" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>IP Address *</label>
                        <input type="text" name="ip_address" placeholder="e.g., 192.168.1.1" required>
                    </div>
                    <div class="form-group">
                        <label>Country Code</label>
                        <input type="text" name="country_code" placeholder="e.g., US, CA, UK" maxlength="2">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Provider</label>
                        <select name="provider">
                            <option value="Contabo">Contabo</option>
                            <option value="Fly.io">Fly.io</option>
                            <option value="DigitalOcean">DigitalOcean</option>
                            <option value="Vultr">Vultr</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Monthly Cost ($)</label>
                        <input type="number" name="monthly_cost" placeholder="0.00" step="0.01">
                    </div>
                </div>

                <h3>WireGuard Configuration</h3>
                
                <div class="form-group">
                    <label>Server Public Key</label>
                    <input type="text" name="public_key" placeholder="WireGuard public key (base64)">
                    <p class="help-text">Get this from the server: wg show wg0 public-key</p>
                </div>

                <h3>Access Control</h3>
                
                <div class="form-group">
                    <label>Dedicated User Email</label>
                    <input type="email" name="dedicated_email" placeholder="Leave empty for public server">
                    <p class="help-text">If set, ONLY this user can see and use this server</p>
                </div>

                <h3>Features</h3>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="port_forwarding" id="port_forwarding" checked>
                    <label for="port_forwarding">Allow Port Forwarding</label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="high_bandwidth" id="high_bandwidth" checked>
                    <label for="high_bandwidth">Allow High Bandwidth (Gaming, P2P)</label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="streaming_optimized" id="streaming_optimized">
                    <label for="streaming_optimized">Streaming Optimized (not VPN-flagged)</label>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">Add Server</button>
                    <button type="reset" class="btn btn-secondary">Reset Form</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
