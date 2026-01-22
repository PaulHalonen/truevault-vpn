<?php
/**
 * Subscription Success Page
 * User is redirected here after PayPal approval
 */
define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Activated - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #fff;
        }
        .container {
            max-width: 500px;
            background: rgba(255,255,255,0.05);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            border: 1px solid rgba(0,255,136,0.3);
        }
        .icon { font-size: 80px; margin-bottom: 20px; }
        h1 { color: #00ff88; margin-bottom: 15px; }
        p { color: #888; margin-bottom: 20px; line-height: 1.6; }
        .btn {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            color: #0f0f1a;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            margin-top: 10px;
        }
        .btn:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🎉</div>
        <h1>Subscription Activated!</h1>
        <p>Thank you for subscribing to TrueVault VPN. Your account has been upgraded and you now have full access to all features.</p>
        <p>You can start setting up your devices right away!</p>
        <a href="/dashboard/setup-device.php" class="btn">Setup Your First Device</a>
        <br><br>
        <a href="/dashboard/" style="color: #00d9ff; text-decoration: none;">← Go to Dashboard</a>
    </div>
</body>
</html>
