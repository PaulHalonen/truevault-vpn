<?php
require_once 'config.php';
require_once 'db.php';

$page_title = 'Business VPN Pricing: The Hidden Costs - TrueVault VPN';
$page_description = 'True cost comparison of business VPN solutions';

include 'header.php';
?>

<style>
    body { background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; }
    .comparison-container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
    .page-header { text-align: center; margin: 3rem 0; }
    .page-header h1 { font-size: 2.5rem; margin-bottom: 1rem; color: #00d9ff; }
    .advertised-price { background: rgba(0,217,255,0.1); border: 2px solid #00d9ff; border-radius: 12px; padding: 2rem; text-align: center; margin: 2rem 0; }
    .advertised-price .amount { font-size: 3rem; font-weight: 700; color: #00d9ff; }
    .warning-box { background: rgba(255,100,100,0.1); border-left: 4px solid #ff6464; padding: 1.5rem; margin: 2rem 0; border-radius: 8px; }
    .comparison-table { overflow-x: auto; margin: 3rem 0; }
    .comparison-table table { width: 100%; border-collapse: collapse; background: rgba(255,255,255,0.03); }
    .comparison-table th { background: rgba(255,255,255,0.1); padding: 1rem; text-align: left; border-bottom: 2px solid #00d9ff; }
    .comparison-table td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .comparison-table .provider-col { font-weight: 700; }
    .comparison-table .truevault { background: rgba(0,217,255,0.1); }
    .real-cost-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin: 3rem 0; }
    .real-cost-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 2rem; text-align: center; }
    .real-cost-card .provider-name { font-size: 1.3rem; margin-bottom: 1rem; font-weight: 700; }
    .real-cost-card .advertised { font-size: 1.5rem; color: #888; text-decoration: line-through; margin-bottom: 0.5rem; }
    .real-cost-card .actual { font-size: 2.5rem; font-weight: 700; color: #ff6464; margin-bottom: 0.5rem; }
    .real-cost-card.truevault .actual { color: #00ff88; }
    .features-only { background: rgba(0,217,255,0.05); border-radius: 12px; padding: 2rem; margin: 3rem 0; }
    .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin: 2rem 0; }
    .feature-item { background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 8px; border-left: 3px solid #00d9ff; }
</style>

<div class="comparison-container">
    <div class="page-header">
        <h1>Business VPN Pricing: The Hidden Costs</h1>
        <p style="font-size: 1.1rem; color: #888; margin-top: 1rem;">
            What they advertise vs. what you actually pay
        </p>
    </div>

    <div class="advertised-price">
        <div style="font-size: 1.2rem; color: #888; margin-bottom: 0.5rem;">Most providers advertise</div>
        <div class="amount">$39.97/mo</div>
        <div style="margin-top: 0.5rem; color: #00ff88;">✓ Great price!</div>
        <div style="margin-top: 0.5rem; color: #00ff88;">✓ All features</div>
        <div style="margin-top: 0.5rem; color: #ff9966; font-weight: 700;">⚠️ But wait...there's more</div>
    </div>

    <div class="warning-box">
        <h3 style="font-size: 1.5rem; margin-bottom: 1rem; color: #ff6464;">🚨 The "$7/user" Trap</h3>
        <p style="line-height: 1.6;">
            At first glance, "$7 per user" sounds affordable. But here's the catch: <strong>that price is ONLY for additional users.</strong> 
            You still pay the full "$39.97/month" base price for access to the platform. Then you pay $7/month for each person who uses it. 
            A team of 10? That's actually <strong>$103/month</strong>, not $70. The advertised "$7" is meaningless without the hidden base fee.
        </p>
    </div>

    <h2 style="font-size: 2rem; margin: 3rem 0 2rem 0; text-align: center;">True Cost Comparison</h2>
    <div class="comparison-table">
        <table>
            <thead>
                <tr>
                    <th>Feature</th>
                    <th class="truevault">TrueVault</th>
                    <th>NordLayer</th>
                    <th>Perimeter 81</th>
                    <th>GoodAccess</th>
                    <th>Twingate</th>
                    <th>Tailscale</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="provider-col">Advertised Price</td>
                    <td class="truevault">$39.97/mo</td>
                    <td>$7/user</td>
                    <td>$8/user</td>
                    <td>$10/user</td>
                    <td>$10/user</td>
                    <td>$6/user</td>
                </tr>
                <tr>
                    <td class="provider-col">Minimum Users</td>
                    <td class="truevault">1</td>
                    <td>Unlimited</td>
                    <td>Unlimited</td>
                    <td>Unlimited</td>
                    <td>Unlimited</td>
                    <td>5</td>
                </tr>
                <tr>
                    <td class="provider-col">Hidden "Base Fee"</td>
                    <td class="truevault">$0</td>
                    <td>Platform access fee</td>
                    <td>Enterprise license</td>
                    <td>Gateway fee</td>
                    <td>Network fee</td>
                    <td>$0</td>
                </tr>
                <tr>
                    <td class="provider-col">Setup Fee</td>
                    <td class="truevault">$0</td>
                    <td>Varies</td>
                    <td>$500-2000</td>
                    <td>$0</td>
                    <td>$0</td>
                    <td>$0</td>
                </tr>
                <tr>
                    <td class="provider-col">Support Cost</td>
                    <td class="truevault">$0 (included)</td>
                    <td>+$20/mo Premium</td>
                    <td>+$100/mo Priority</td>
                    <td>Tickets only</td>
                    <td>Community only</td>
                    <td>Email only</td>
                </tr>
                <tr>
                    <td class="provider-col">VPN Access</td>
                    <td class="truevault">✓ Included</td>
                    <td>✓ Included</td>
                    <td>✓ Included</td>
                    <td>✓ Included</td>
                    <td>✓ Included</td>
                    <td>✓ Included</td>
                </tr>
                <tr>
                    <td class="provider-col">Port Forwarding</td>
                    <td class="truevault">✓ Unlimited (free)</td>
                    <td>✗ Not available</td>
                    <td>+$50/mo</td>
                    <td>+$25/mo</td>
                    <td>Custom pricing</td>
                    <td>✓ Included</td>
                </tr>
                <tr>
                    <td class="provider-col">Static IP</td>
                    <td class="truevault">✓ Included</td>
                    <td>+$5/IP</td>
                    <td>+$10/IP</td>
                    <td>+$5/IP</td>
                    <td>+$8/IP</td>
                    <td>✓ Included</td>
                </tr>
                <tr>
                    <td class="provider-col">Camera Dashboard</td>
                    <td class="truevault">✓ Included</td>
                    <td>✗ Not available</td>
                    <td>✗ Not available</td>
                    <td>✗ Not available</td>
                    <td>✗ Not available</td>
                    <td>✗ Not available</td>
                </tr>
                <tr>
                    <td class="provider-col">Network Scanner</td>
                    <td class="truevault">✓ Included</td>
                    <td>✗ Not available</td>
                    <td>✗ Not available</td>
                    <td>✗ Not available</td>
                    <td>✗ Not available</td>
                    <td>✗ Not available</td>
                </tr>
                <tr>
                    <td class="provider-col">HR Management</td>
                    <td class="truevault">✓ Included</td>
                    <td>✗ Not available</td>
                    <td>✗ Not available</td>
                    <td>✗ Not available</td>
                    <td>✗ Not available</td>
                    <td>✗ Not available</td>
                </tr>
                <tr>
                    <td class="provider-col">DataForge</td>
                    <td class="truevault">✓ Included (FileMaker alternative)</td>
                    <td>✗ Not available</td>
                    <td>✗ Not available</td>
                    <td>✗ Not available</td>
                    <td>✗ Not available</td>
                    <td>✗ Not available</td>
                </tr>
                <tr style="background: rgba(255,255,255,0.1); font-weight: 700;">
                    <td class="provider-col">ACTUAL Monthly Cost (5 users + features)</td>
                    <td class="truevault" style="color: #00ff88; font-size: 1.3rem;">$39.97</td>
                    <td style="color: #ff6464;">$74.00</td>
                    <td style="color: #ff6464;">$93.00</td>
                    <td style="color: #ff6464;">$80.00</td>
                    <td style="color: #ff6464;">~$95.00</td>
                    <td style="color: #ff9966;">$30.00*</td>
                </tr>
            </tbody>
        </table>
        <p style="text-align: center; color: #888; margin-top: 1rem; font-size: 0.9rem;">
            *Tailscale pricing doesn't include camera dashboard, network scanner, HR management, or DataForge
        </p>
    </div>

    <h2 style="font-size: 2rem; margin: 3rem 0 2rem 0; text-align: center;">The Real Monthly Cost</h2>
    <p style="text-align: center; color: #888; margin-bottom: 2rem;">
        After you add up the base fee, per-user charges, and "optional" features you actually need:
    </p>
    
    <div class="real-cost-grid">
        <div class="real-cost-card truevault">
            <div class="provider-name">✅ TrueVault</div>
            <div class="advertised">Advertised: $39.97</div>
            <div class="actual">$39.97</div>
            <div style="color: #00ff88; margin-top: 0.5rem;">✓ No hidden fees</div>
            <div style="color: #00ff88;">✓ All features included</div>
            <div style="color: #00ff88;">✓ Includes FileMaker Pro alternative</div>
        </div>

        <div class="real-cost-card">
            <div class="provider-name">❌ NordLayer</div>
            <div class="advertised">Advertised: $7/user</div>
            <div class="actual">$74.00</div>
            <div style="color: #888; margin-top: 0.5rem; font-size: 0.9rem;">Base fee + 5 users + premium support</div>
        </div>

        <div class="real-cost-card">
            <div class="provider-name">❌ Perimeter 81</div>
            <div class="advertised">Advertised: $8/user</div>
            <div class="actual">$93.00</div>
            <div style="color: #888; margin-top: 0.5rem; font-size: 0.9rem;">Enterprise license + users + port forwarding</div>
        </div>

        <div class="real-cost-card">
            <div class="provider-name">❌ GoodAccess</div>
            <div class="advertised">Advertised: $10/user</div>
            <div class="actual">$80.00</div>
            <div style="color: #888; margin-top: 0.5rem; font-size: 0.9rem;">Gateway fee + users + static IPs</div>
        </div>
    </div>

    <div class="features-only">
        <h2 style="font-size: 2rem; margin-bottom: 1.5rem; text-align: center;">Features Only TrueVault Offers</h2>
        <div class="features-grid">
            <div class="feature-item">
                <h3 style="margin-bottom: 0.5rem; color: #00d9ff;">📹 2-Click Port Forwarding</h3>
                <p style="color: #ccc;">Access your home IP cameras from anywhere. Geeni, Wyze, Hikvision + 50 more brands supported.</p>
            </div>
            <div class="feature-item">
                <h3 style="margin-bottom: 0.5rem; color: #00d9ff;">👨‍👩‍👧‍👦 Family Mesh Network</h3>
                <p style="color: #ccc;">Connect family members in different cities as if they're on the same local network. Share files, printers, anything.</p>
            </div>
            <div class="feature-item">
                <h3 style="margin-bottom: 0.5rem; color: #00d9ff;">🗄️ Camera Dashboard</h3>
                <p style="color: #ccc;">Unified dashboard for all your security cameras. No juggling 5 different apps.</p>
            </div>
            <div class="feature-item">
                <h3 style="margin-bottom: 0.5rem; color: #00d9ff;">🔍 Network Scanner</h3>
                <p style="color: #ccc;">Auto-discover all devices on your network. Windows, Mac, and Linux tools included free.</p>
            </div>
            <div class="feature-item">
                <h3 style="margin-bottom: 0.5rem; color: #00d9ff;">👔 HR Management</h3>
                <p style="color: #ccc;">Employee directory, time-off requests, performance reviews. Enterprise features at consumer prices.</p>
            </div>
            <div class="feature-item">
                <h3 style="margin-bottom: 0.5rem; color: #00d9ff;">🏗️ DataForge</h3>
                <p style="color: #ccc;">Build custom databases without code. FileMaker Pro alternative ($588/year value) included FREE.</p>
            </div>
        </div>
    </div>

    <div style="background: rgba(0,217,255,0.1); border: 2px solid #00d9ff; border-radius: 12px; padding: 3rem; text-align: center; margin: 4rem 0;">
        <h2 style="font-size: 2.5rem; margin-bottom: 1rem;">Honest Assessment</h2>
        <div style="max-width: 800px; margin: 0 auto;">
            <div style="margin: 1.5rem 0; padding: 1rem; background: rgba(0,255,136,0.1); border-radius: 8px;">
                <strong style="color: #00ff88;">✅ TrueVault Advantages:</strong> This is the whole story—no tricks, no hidden costs.
            </div>
            <div style="margin: 1.5rem 0; padding: 1rem; background: rgba(255,100,100,0.1); border-radius: 8px;">
                <strong style="color: #ff6464;">⚠️ To Be Fair:</strong> This is the whole story—you're locked into one setup and can't migrate independently. Also, we don't offer SSO or deep enterprise integrations like Azure AD.
            </div>
            <div style="margin: 1.5rem 0; padding: 1rem; background: rgba(0,217,255,0.1); border-radius: 8px;">
                <strong style="color: #00d9ff;">🎯 NetB2000B-Yxs6xB-3YxG-7HxG:</strong> You're paying less than these providers while getting MORE features. Most of their advertised "$7-$10/user" plans don't include cameras, port forwarding, HR tools, or database builders.
            </div>
            <div style="margin: 1.5rem 0; padding: 1rem; background: rgba(255,217,0,0.1); border-radius: 8px;">
                <strong style="color: #ffd900;">⚡ Your comparison:</strong> No setup fees, no base fee, not enterprise BS.
            </div>
        </div>
    </div>

    <div style="text-align: center; margin: 4rem 0;">
        <h2 style="font-size: 2rem; margin-bottom: 1rem;">Ready for Dedicated VPN Without Minimum Users?</h2>
        <p style="color: #888; margin-bottom: 2rem;">Get all our dedicated server options at $39.97/month, no contracts.</p>
        <a href="/pricing.php" style="display: inline-block; padding: 1rem 3rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 1.2rem;">
            View Pricing
        </a>
        <div style="margin-top: 1rem;">
            <a href="/contact.php" style="color: #00d9ff; text-decoration: none;">Questions? Contact us</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
