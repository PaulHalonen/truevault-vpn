<?php
/**
 * TrueVault VPN - Billing Dashboard
 * 
 * PURPOSE: User billing and subscription management
 * AUTHENTICATION: JWT required
 * 
 * FEATURES:
 * - View current subscription
 * - Upgrade/downgrade plans
 * - View payment history
 * - Cancel subscription
 * - Payment status notifications
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load configuration
require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/JWT.php';
require_once __DIR__ . '/../includes/Auth.php';

// Check authentication
try {
    $user = Auth::require();
    $userId = $user['user_id'];
    $userName = $user['first_name'];
} catch (Exception $e) {
    header('Location: /auth/login.php');
    exit;
}

// Get subscription status
$db = Database::getInstance();
$paymentsConn = $db->getConnection('payments');

$stmt = $paymentsConn->prepare("
    SELECT subscription_id, paypal_subscription_id, plan_id, status, created_at, activated_at
    FROM subscriptions
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$userId]);
$subscription = $stmt->fetch(PDO::FETCH_ASSOC);

// Get payment history
$stmt = $paymentsConn->prepare("
    SELECT payment_id, amount, currency, status, created_at
    FROM payments
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->execute([$userId]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Plan pricing
$plans = [
    'standard' => ['name' => 'Standard', 'price' => '$9.99', 'devices' => 3],
    'pro' => ['name' => 'Pro', 'price' => '$14.99', 'devices' => 5],
    'vip' => ['name' => 'VIP', 'price' => '$29.99', 'devices' => 'Unlimited']
];

// Handle return from PayPal
$paymentStatus = $_GET['status'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing - TrueVault VPN</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-title h1 {
            font-size: 28px;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .header-title p {
            color: #64748b;
            font-size: 14px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #86efac;
        }

        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fca5a5;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
        }

        .subscription-status {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
            margin-bottom: 20px;
        }

        .status-icon {
            font-size: 48px;
        }

        .status-info h2 {
            font-size: 24px;
            margin-bottom: 4px;
        }

        .status-info p {
            opacity: 0.9;
            font-size: 14px;
        }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .plan-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            transition: all 0.3s;
        }

        .plan-card:hover {
            border-color: #667eea;
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
        }

        .plan-card.active {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }

        .plan-name {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .plan-price {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 16px;
        }

        .plan-features {
            list-style: none;
            margin-bottom: 20px;
            text-align: left;
        }

        .plan-features li {
            padding: 8px 0;
            color: #475569;
            font-size: 14px;
        }

        .plan-features li:before {
            content: "✓ ";
            color: #10b981;
            font-weight: 700;
            margin-right: 8px;
        }

        .btn-plan {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-plan:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-plan:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .payment-history {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-history th {
            padding: 12px;
            text-align: left;
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
            font-size: 14px;
            border-bottom: 2px solid #e2e8f0;
        }

        .payment-history td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            font-size: 14px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-completed {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-refunded {
            background: #fee2e2;
            color: #dc2626;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <h1>💳 Billing & Subscription</h1>
                    <p>Manage your subscription and payment methods</p>
                </div>
                <a href="/dashboard/my-devices.php" class="btn btn-secondary">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Status Messages -->
        <?php if ($paymentStatus === 'success'): ?>
            <div class="alert alert-success">
                ✅ <strong>Success!</strong> Your subscription is now active. Welcome to TrueVault VPN!
            </div>
        <?php elseif ($paymentStatus === 'cancelled'): ?>
            <div class="alert alert-error">
                ❌ <strong>Cancelled:</strong> You cancelled the payment. Your subscription was not activated.
            </div>
        <?php endif; ?>

        <!-- Current Subscription -->
        <div class="card">
            <h2 class="card-title">Current Subscription</h2>
            
            <?php if ($subscription && $subscription['status'] === 'active'): ?>
                <div class="subscription-status">
                    <div class="status-icon">✅</div>
                    <div class="status-info">
                        <h2><?= htmlspecialchars($plans[$subscription['plan_id']]['name']) ?> Plan</h2>
                        <p>Active since <?= date('F j, Y', strtotime($subscription['activated_at'])) ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <p><strong>No Active Subscription</strong></p>
                    <p>Choose a plan below to get started!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Available Plans -->
        <div class="card">
            <h2 class="card-title">Available Plans</h2>
            
            <div class="plans-grid">
                <?php foreach ($plans as $planId => $plan): ?>
                    <div class="plan-card <?= ($subscription && $subscription['plan_id'] === $planId && $subscription['status'] === 'active') ? 'active' : '' ?>">
                        <div class="plan-name"><?= htmlspecialchars($plan['name']) ?></div>
                        <div class="plan-price"><?= htmlspecialchars($plan['price']) ?><small>/mo</small></div>
                        
                        <ul class="plan-features">
                            <li><?= htmlspecialchars($plan['devices']) ?> Devices</li>
                            <li>Unlimited Bandwidth</li>
                            <li>All Server Locations</li>
                            <li>24/7 Support</li>
                            <?php if ($planId === 'vip'): ?>
                                <li>Dedicated Server</li>
                                <li>Priority Support</li>
                            <?php endif; ?>
                        </ul>
                        
                        <button 
                            class="btn-plan" 
                            onclick="subscribeToPlan('<?= $planId ?>')"
                            <?= ($subscription && $subscription['plan_id'] === $planId && $subscription['status'] === 'active') ? 'disabled' : '' ?>
                        >
                            <?= ($subscription && $subscription['plan_id'] === $planId && $subscription['status'] === 'active') ? 'Current Plan' : 'Select Plan' ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Payment History -->
        <div class="card">
            <h2 class="card-title">Payment History</h2>
            
            <?php if (count($payments) > 0): ?>
                <table class="payment-history">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($payment['created_at'])) ?></td>
                                <td><?= htmlspecialchars($payment['amount']) ?> <?= htmlspecialchars($payment['currency']) ?></td>
                                <td>
                                    <span class="status-badge status-<?= htmlspecialchars($payment['status']) ?>">
                                        <?= ucfirst(htmlspecialchars($payment['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <p>No payment history yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const JWT_TOKEN = localStorage.getItem('vpn_token');

        if (!JWT_TOKEN) {
            window.location.href = '/auth/login.php';
        }

        async function subscribeToPlan(plan) {
            if (!confirm(`Subscribe to ${plan} plan?`)) {
                return;
            }

            try {
                const response = await fetch('/api/billing/create-subscription.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${JWT_TOKEN}`
                    },
                    body: JSON.stringify({ plan })
                });

                const result = await response.json();

                if (result.success && result.approval_url) {
                    // Redirect to PayPal for payment
                    window.location.href = result.approval_url;
                } else {
                    alert('❌ Error: ' + (result.error || 'Failed to create subscription'));
                }
            } catch (error) {
                alert('❌ Error: ' + error.message);
            }
        }
    </script>
</body>
</html>
