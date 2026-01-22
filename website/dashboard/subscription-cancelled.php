<?php
/**
 * Subscription Cancelled Page
 * User is redirected here if they cancel during PayPal checkout
 */
define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Cancelled - TrueVault VPN</title>
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
            border: 1px solid rgba(255,255,255,0.1);
        }
        .icon { font-size: 80px; margin-bottom: 20px; }
        h1 { color: #ff9800; margin-bottom: 15px; }
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
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">😕</div>
        <h1>Subscription Cancelled</h1>
        <p>No problem! You cancelled the subscription process. No charges were made to your account.</p>
        <p>You can still use TrueVault VPN with our free tier, or subscribe anytime when you're ready.</p>
        <a href="/pricing.php" class="btn">View Plans & Pricing</a>
        <br><br>
        <a href="/dashboard/" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</body>
</html>
