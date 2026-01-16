<?php
/**
 * TrueVault VPN - Network Device Scanner
 * Discovers devices on local network for port forwarding
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
$stmt = $db->users->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get discovered devices
$stmt = $db->portForwards->prepare("
    SELECT * FROM discovered_devices
    WHERE user_id = ?
    ORDER BY discovered_at DESC
");
$stmt->execute([$userId]);
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover Devices - TrueVault VPN</title>
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

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            color: #0f0f1a;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 217, 255, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .devices-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }

        .device-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 10px;
            padding: 1rem;
            transition: all 0.3s;
        }

        .device-card:hover {
            background: rgba(255, 255, 255, 0.07);
            border-color: #00d9ff;
        }

        .device-card.camera {
            border-left: 3px solid #ff6b6b;
        }

        .device-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .device-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .device-ip {
            font-family: monospace;
            color: #00d9ff;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .device-vendor {
            color: #999;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Discover Network Devices</h1>

        <!-- Scanner Instructions Card -->
        <div class="card">
            <h2 style="margin-bottom: 1rem; color: #00d9ff;">📡 How to Scan</h2>
            <p style="margin-bottom: 1rem; color: #999;">
                To scan your network for devices, you need to run the TrueVault Scanner on your computer.
                This detects IP cameras, printers, gaming consoles, smart TVs, and more!
            </p>
            
            <h3 style="margin: 1.5rem 0 1rem; color: #00ff88;">Step 1: Download Scanner</h3>
            <p style="margin-bottom: 1rem; color: #999;">
                <a href="/scanner/truthvault-scanner.zip" class="btn btn-primary" download>
                    📥 Download TrueVault Scanner
                </a>
            </p>

            <h3 style="margin: 1.5rem 0 1rem; color: #00ff88;">Step 2: Your Auth Token</h3>
            <p style="margin-bottom: 0.5rem; color: #999;">
                Copy this token and paste it when the scanner asks:
            </p>
            <div style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px; font-family: monospace; color: #00d9ff;">
                <?= htmlspecialchars($_SESSION['auth_token'] ?? 'TOKEN_NOT_FOUND') ?>
            </div>

            <h3 style="margin: 1.5rem 0 1rem; color: #00ff88;">Step 3: Run Scanner</h3>
            <ul style="margin-left: 1.5rem; color: #999;">
                <li><strong>Windows:</strong> Double-click <code>run_scanner.bat</code></li>
                <li><strong>Mac/Linux:</strong> Run <code>./run_scanner.sh</code> in Terminal</li>
            </ul>
        </div>

        <p style="margin-top: 2rem; text-align: center; color: #666;">
            <a href="/dashboard/" style="color: #00d9ff;">← Back to Dashboard</a>
        </p>
    </div>
</body>
</html>
