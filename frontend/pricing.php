<?php
require_once 'config.php';
require_once 'db.php';

$page_title = 'Pricing - TrueVault VPN';
$page_description = 'Affordable VPN plans starting at $9.97/month';

include 'header.php';
?>

<style>
    body { background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; }
    .pricing-container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
    .page-header { text-align: center; margin: 3rem 0; }
    .page-header h1 { font-size: 3rem; margin-bottom: 1rem; }
    .currency-toggle { text-align: center; margin: 2rem 0; }
    .currency-toggle button { padding: 0.75rem 2rem; margin: 0 0.5rem; border: 2px solid #00d9ff; background: transparent; color: #fff; border-radius: 8px; cursor: pointer; font-size: 1.1rem; font-weight: 600; transition: 0.3s; }
    .currency-toggle button.active { background: #00d9ff; color: #000; }
    .currency-toggle button:hover { transform: translateY(-2px); }
    .billing-toggle { text-align: center; margin: 2rem 0; }
    .billing-toggle label { margin: 0 1rem; font-size: 1.1rem; cursor: pointer; }
    .plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; margin: 3rem 0; }
    .plan-card { background: rgba(255,255,255,0.05); border: 2px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 2.5rem; text-align: center; transition: 0.3s; position: relative; }
    .plan-card:hover { transform: translateY(-5px); border-color: #00d9ff; }
    .plan-card.featured { border: 3px solid #00d9ff; transform: scale(1.05); box-shadow: 0 10px 40px rgba(0,217,255,0.3); }
    .featured-badge { position: absolute; top: -15px; left: 50%; transform: translateX(-50%); background: #00d9ff; color: #000; padding: 0.5rem 1.5rem; border-radius: 20px; font-weight: 700; font-size: 0.9rem; }
    .plan-name { font-size: 1.8rem; margin-bottom: 1rem; color: #fff; font-weight: 700; }
    .plan-price { margin: 1.5rem 0; }
    .price-amount { font-size: 3rem; font-weight: 700; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .price-period { font-size: 1rem; color: #888; }
    .price-usd, .price-cad { font-size: 1.5rem; margin: 0.5rem 0; }
    .plan-features { text-align: left; margin: 2rem 0; }
    .plan-features li { list-style: none; padding: 0.75rem 0; border-bottom: 1px solid rgba(255,255,255,0.1); color: #ccc; }
    .plan-features li:before { content: "✓"; color: #00ff88; font-weight: 700; margin-right: 0.5rem; }
    .plan-cta { padding: 1rem 2rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; width: 100%; transition: 0.3s; }
    .plan-cta:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,217,255,0.4); }
</style>

<div class="pricing-container">
    <div class="page-header">
        <h1>Simple, Transparent Pricing</h1>
        <p style="font-size: 1.2rem; color: #888;">Choose the plan that's right for you</p>
    </div>

    <div class="currency-toggle">
        <button class="currency-btn active" data-currency="usd">🇺🇸 USD</button>
        <button class="currency-btn" data-currency="cad">🇨🇦 CAD</button>
    </div>

    <div class="billing-toggle">
        <label>
            <input type="radio" name="billing" value="monthly" checked> Monthly
        </label>
        <label>
            <input type="radio" name="billing" value="annual"> Annual <span style="color: #00ff88;">(Save 2 months!)</span>
        </label>
    </div>

    <div class="plans-grid">
        <!-- Personal Plan -->
        <div class="plan-card">
            <div class="plan-name">Personal</div>
            <div class="plan-price">
                <div class="price-usd" data-monthly="9.97" data-annual="99.70">
                    <span class="price-amount">$9.97</span>
                    <div class="price-period">/month</div>
                </div>
                <div class="price-cad" data-monthly="13.47" data-annual="134.70" style="display:none;">
                    <span class="price-amount">$13.47</span>
                    <div class="price-period">/month</div>
                </div>
            </div>
            <ul class="plan-features">
                <li>3 devices</li>
                <li>Unlimited bandwidth</li>
                <li>4 server locations</li>
                <li>24/7 email support</li>
                <li>Port forwarding</li>
            </ul>
            <a href="/signup.php?plan=personal" class="plan-cta">Start Free Trial</a>
        </div>

        <!-- Family Plan (Featured) -->
        <div class="plan-card featured">
            <div class="featured-badge">Most Popular</div>
            <div class="plan-name">Family</div>
            <div class="plan-price">
                <div class="price-usd" data-monthly="14.97" data-annual="149.70">
                    <span class="price-amount">$14.97</span>
                    <div class="price-period">/month</div>
                </div>
                <div class="price-cad" data-monthly="20.21" data-annual="202.10" style="display:none;">
                    <span class="price-amount">$20.21</span>
                    <div class="price-period">/month</div>
                </div>
            </div>
            <ul class="plan-features">
                <li>5 devices</li>
                <li>Unlimited bandwidth</li>
                <li>All server locations</li>
                <li>Priority support</li>
                <li>Port forwarding</li>
                <li>Parental controls</li>
                <li>Camera dashboard</li>
            </ul>
            <a href="/signup.php?plan=family" class="plan-cta">Start Free Trial</a>
        </div>

        <!-- Dedicated Server -->
        <div class="plan-card">
            <div class="plan-name">Dedicated Server</div>
            <div class="plan-price">
                <div class="price-usd" data-monthly="39.97" data-annual="399.70">
                    <span class="price-amount">$39.97</span>
                    <div class="price-period">/month</div>
                </div>
                <div class="price-cad" data-monthly="53.96" data-annual="539.60" style="display:none;">
                    <span class="price-amount">$53.96</span>
                    <div class="price-period">/month</div>
                </div>
            </div>
            <ul class="plan-features">
                <li>Unlimited devices</li>
                <li>Dedicated server</li>
                <li>Static IP address</li>
                <li>Priority routing</li>
                <li>24/7 phone support</li>
                <li>All Family features</li>
                <li>Custom configuration</li>
            </ul>
            <a href="/signup.php?plan=dedicated" class="plan-cta">Start Free Trial</a>
        </div>
    </div>
</div>

<script>
// Currency toggle
const currencyBtns = document.querySelectorAll('.currency-btn');
const usdPrices = document.querySelectorAll('.price-usd');
const cadPrices = document.querySelectorAll('.price-cad');

currencyBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        currencyBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        if (btn.dataset.currency === 'usd') {
            usdPrices.forEach(p => p.style.display = 'block');
            cadPrices.forEach(p => p.style.display = 'none');
        } else {
            usdPrices.forEach(p => p.style.display = 'none');
            cadPrices.forEach(p => p.style.display = 'block');
        }
    });
});

// Billing toggle
const billingRadios = document.querySelectorAll('input[name="billing"]');
billingRadios.forEach(radio => {
    radio.addEventListener('change', () => {
        const isAnnual = radio.value === 'annual';
        document.querySelectorAll('.price-usd, .price-cad').forEach(priceDiv => {
            const amount = priceDiv.querySelector('.price-amount');
            const period = priceDiv.querySelector('.price-period');
            const monthly = parseFloat(priceDiv.dataset.monthly);
            const annual = parseFloat(priceDiv.dataset.annual);
            
            if (isAnnual) {
                amount.textContent = '$' + annual.toFixed(2);
                period.textContent = '/year';
            } else {
                amount.textContent = '$' + monthly.toFixed(2);
                period.textContent = '/month';
            }
        });
    });
});
</script>

<?php include 'footer.php'; ?>
