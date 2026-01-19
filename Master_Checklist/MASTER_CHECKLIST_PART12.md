# MASTER CHECKLIST - PART 12: FRONTEND LANDING PAGES

**Created:** January 18, 2026 - 9:50 PM CST  
**Status:** ⏳ NOT STARTED  
**Priority:** 🚨 CRITICAL - LAUNCH BLOCKER  
**Estimated Time:** 5-6 hours  

---

## 📋 OVERVIEW

Build all public-facing landing pages that customers see BEFORE logging in.

**Why This Was Missed:**
- Part 8 built the **tools** (page builder, CMS)
- But forgot to build the **product** (actual pages)
- Classic mistake: confusing admin tools with customer experience

**What This Includes:**
- Homepage with VPN education
- Pricing page with USD/CAD pricing
- Features page
- About, Contact, Legal pages
- Reusable components (header, footer)
- Section templates for page builder

---

## 🎯 REQUIREMENTS FROM USER

### **Pricing Requirements:**
✅ Personal Plan: **$9.97 USD** / **$13.47 CAD**  
✅ Family Plan: **$14.97 USD** / **$20.21 CAD**  
✅ Dedicated Server: **$39.97 USD** / **$53.96 CAD**  
✅ USD & CAD same font size (equal importance)  
✅ Monthly/Annual toggle (2 months free on annual)  
❌ **NO VIP tier advertised** (hidden internal only)  

### **Content Requirements:**
✅ What is a VPN (education section)  
✅ Why you need a VPN (privacy, security, freedom)  
✅ All features listed  
✅ Competitor comparison table  
✅ Multiple CTAs (call-to-action)  
✅ Trust badges  

---

## 🔧 TASK 12.1: Create Homepage (index.php)

**Time:** 2 hours  
**Lines:** ~600 lines  
**File:** `/website/index.php`

### **Sections to Include:**

**1. Hero Section**
- [ ] Eye-catching headline
- [ ] Subheadline explaining TrueVault
- [ ] Primary CTA: "Start Free Trial"
- [ ] Secondary CTA: "View Pricing"
- [ ] Trust badges (7-day trial, no credit card, etc.)

**2. What is a VPN Section**
- [ ] Simple explanation of VPN
- [ ] Visual diagram (encrypted tunnel)
- [ ] 3-4 key benefits highlighted
- [ ] "Without VPN" vs "With VPN" comparison

**3. Why You Need a VPN**
- [ ] Privacy: Stop ISP tracking
- [ ] Security: Protect on public WiFi
- [ ] Freedom: Access geo-blocked content
- [ ] Parental Controls: Protect kids online
- [ ] Remote Access: Port forwarding for devices

**4. Features Grid**
- [ ] 15+ feature cards with icons
- [ ] Each card: Icon, Title, Description
- [ ] Features include:
  - 256-bit encryption
  - Zero logs policy
  - Port forwarding
  - Network scanner
  - Parental controls with calendar
  - Gaming server controls
  - 4 server locations
  - WireGuard protocol
  - 2-click device setup
  - Android helper app
  - Family sharing
  - 7-day free trial
  - Email notifications
  - Activity logs
  - 24/7 support

**5. Pricing Preview**
- [ ] 3 pricing cards
- [ ] Personal, Family, Dedicated
- [ ] Monthly pricing only
- [ ] "View Full Pricing" CTA

**6. Competitor Comparison Table**
- [ ] TrueVault vs Traditional VPNs
- [ ] Feature checklist (checkmarks for us, X for them)
- [ ] Features to compare:
  - Multi-IP addresses
  - Persistent regional identities
  - Personal certificate authority
  - Family/Team mesh network
  - Decentralized architecture
  - AI-powered routing
  - Port forwarding
  - Network scanner
  - Parental calendar controls
  - You control the keys

**7. How It Works**
- [ ] 3-step process
- [ ] Step 1: Sign up (7-day trial)
- [ ] Step 2: Download config (2-click setup)
- [ ] Step 3: Connect & browse safely
- [ ] Visual diagram

**8. Trust & Security**
- [ ] Security badges
- [ ] "No logs" policy highlight
- [ ] WireGuard protocol badge
- [ ] Server locations map
- [ ] Testimonial quotes (if available)

**9. Final CTA Section**
- [ ] Large heading: "Ready to protect your privacy?"
- [ ] Subtext: "Start your 7-day free trial today"
- [ ] Primary CTA button
- [ ] "No credit card required" text

**10. Footer**
- [ ] Navigation links
- [ ] Social media icons
- [ ] Copyright
- [ ] Contact email
- [ ] Legal links (Terms, Privacy)

### **Code Structure:**
```php
<?php
define('TRUEVAULT_INIT', true);
require_once 'configs/config.php';
require_once 'includes/Theme.php';
require_once 'includes/Content.php';

$theme = Theme::getActiveTheme();
$colors = Theme::getAllColors();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= Content::get('site_title') ?> - Complete Digital Privacy</title>
    <style>
        /* Use theme colors from database */
        :root {
            --primary: <?= $colors['primary'] ?>;
            --secondary: <?= $colors['secondary'] ?>;
            /* ... all theme colors */
        }
        /* Responsive CSS */
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Hero -->
    <section class="hero">...</section>
    
    <!-- What is VPN -->
    <section class="what-is-vpn">...</section>
    
    <!-- Why You Need VPN -->
    <section class="why-vpn">...</section>
    
    <!-- Features -->
    <section class="features">...</section>
    
    <!-- Pricing Preview -->
    <section class="pricing-preview">...</section>
    
    <!-- Comparison Table -->
    <section class="comparison">...</section>
    
    <!-- How It Works -->
    <section class="how-it-works">...</section>
    
    <!-- Trust -->
    <section class="trust">...</section>
    
    <!-- Final CTA -->
    <section class="final-cta">...</section>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
```

### **Verification:**
- [ ] All 10 sections display
- [ ] Theme colors apply
- [ ] Responsive on mobile
- [ ] All CTAs link correctly
- [ ] Images load
- [ ] No hardcoded values
- [ ] Fast page load (<2 seconds)

---

## 🔧 TASK 12.2: Create Pricing Page (pricing.php)

**Time:** 1.5 hours  
**Lines:** ~400 lines  
**File:** `/website/pricing.php`

### **Sections to Include:**

**1. Hero Section**
- [ ] "Simple, Transparent Pricing"
- [ ] "No hidden fees. Cancel anytime."
- [ ] Currency toggle: USD | CAD
- [ ] Billing toggle: Monthly | Annual (save 2 months!)

**2. Pricing Cards (3 plans)**

**Personal Plan:**
- [ ] $9.97 USD / **$13.47 CAD** monthly
- [ ] $99.70 USD / **$134.70 CAD** annual (2 months free)
- [ ] 3 devices
- [ ] All locations
- [ ] Port forwarding
- [ ] Network scanner
- [ ] Email support
- [ ] 7-day free trial
- [ ] "Start Free Trial" CTA

**Family Plan:**
- [ ] $14.97 USD / **$20.21 CAD** monthly
- [ ] $149.70 USD / **$202.10 CAD** annual (2 months free)
- [ ] 10 devices
- [ ] All locations
- [ ] Port forwarding
- [ ] Network scanner
- [ ] Parental controls
- [ ] Gaming controls
- [ ] Calendar scheduling
- [ ] Priority support
- [ ] 7-day free trial
- [ ] "Most Popular" badge
- [ ] "Start Free Trial" CTA

**Dedicated Server:**
- [ ] $39.97 USD / **$53.96 CAD** monthly
- [ ] $399.70 USD / **$539.60 CAD** annual (2 months free)
- [ ] Unlimited devices
- [ ] Your own dedicated server
- [ ] All features
- [ ] Fastest speeds
- [ ] 24/7 priority support
- [ ] 7-day free trial
- [ ] "Contact Sales" CTA

**3. Competitor Comparison Table**
- [ ] Feature-by-feature comparison
- [ ] TrueVault vs NordVPN vs ExpressVPN vs Surfshark
- [ ] Rows for each feature:
  - Price (monthly)
  - Price (annual with discount)
  - Number of devices
  - Port forwarding
  - Network scanner
  - Parental controls
  - Gaming controls
  - Server locations
  - Free trial
  - Zero logs
  - WireGuard protocol
  - Android helper app
  - Calendar scheduling
  - Dedicated server option
  - Family mesh network
- [ ] Checkmarks ✓ for Yes, ✗ for No
- [ ] Highlight TrueVault column

**4. Feature Comparison Matrix**
- [ ] All features listed
- [ ] Which plan includes what
- [ ] Visual checkmarks

**5. FAQ Section**
- [ ] "What's included in the free trial?"
- [ ] "Can I cancel anytime?"
- [ ] "Do you keep logs?"
- [ ] "What payment methods do you accept?"
- [ ] "Can I upgrade my plan later?"
- [ ] "What's the difference between plans?"
- [ ] "Is there a money-back guarantee?"
- [ ] "How do I set up parental controls?"

**6. Final CTA**
- [ ] "Ready to get started?"
- [ ] "Start your 7-day free trial"
- [ ] "No credit card required"

### **JavaScript for Toggles:**
```javascript
// Currency toggle
const currencyToggle = document.getElementById('currency-toggle');
currencyToggle.addEventListener('change', (e) => {
    const currency = e.target.value;
    document.querySelectorAll('.price-usd').forEach(el => {
        el.style.display = currency === 'USD' ? 'inline' : 'none';
    });
    document.querySelectorAll('.price-cad').forEach(el => {
        el.style.display = currency === 'CAD' ? 'inline' : 'none';
    });
});

// Billing toggle
const billingToggle = document.getElementById('billing-toggle');
billingToggle.addEventListener('change', (e) => {
    const billing = e.target.value;
    document.querySelectorAll('.price-monthly').forEach(el => {
        el.style.display = billing === 'monthly' ? 'block' : 'none';
    });
    document.querySelectorAll('.price-annual').forEach(el => {
        el.style.display = billing === 'annual' ? 'block' : 'none';
    });
});
```

### **Verification:**
- [ ] Currency toggle works
- [ ] Billing toggle works
- [ ] Prices display correctly
- [ ] USD & CAD same font size
- [ ] Comparison table readable
- [ ] FAQ answers helpful
- [ ] All CTAs work
- [ ] Mobile responsive

---

## 🔧 TASK 12.3: Create Features Page (features.php)

**Time:** 1 hour  
**Lines:** ~350 lines  
**File:** `/website/features.php`

### **Sections to Include:**

**1. Hero**
- [ ] "Everything You Need for Complete Privacy"

**2. Core VPN Features**
- [ ] 256-bit Military Encryption
- [ ] Zero Logs Policy
- [ ] WireGuard Protocol
- [ ] 4 Server Locations
- [ ] Unlimited Bandwidth
- [ ] Kill Switch
- [ ] DNS Leak Protection

**3. Advanced Features**
- [ ] Port Forwarding (detailed explanation)
- [ ] Network Scanner (screenshot)
- [ ] 2-Click Device Setup
- [ ] Android Helper App

**4. Parental Controls**
- [ ] Calendar Scheduling (screenshot)
- [ ] Time Windows
- [ ] Gaming Controls (Xbox, PS, Steam, Nintendo)
- [ ] Whitelist/Blacklist
- [ ] Weekly Reports

**5. Family Features**
- [ ] Up to 10 devices
- [ ] Family mesh network
- [ ] Shared port forwarding
- [ ] Individual profiles

**6. Business Features (Dedicated Server)**
- [ ] Your own server
- [ ] Unlimited devices
- [ ] Fastest speeds
- [ ] Priority support

**7. Security Features**
- [ ] Military-grade encryption
- [ ] No logs policy
- [ ] Perfect forward secrecy
- [ ] Secure core servers

**8. CTA**
- [ ] "Try all features free for 7 days"

### **Verification:**
- [ ] All features explained
- [ ] Screenshots included
- [ ] Technical details accurate
- [ ] Benefits clear
- [ ] CTAs work

---

## 🔧 TASK 12.4: Create About/Contact/Legal Pages

**Time:** 1 hour  
**Lines:** ~300 lines total  

### **about.php:**
- [ ] Company mission
- [ ] Why we built TrueVault
- [ ] Our values (privacy, transparency, simplicity)
- [ ] Team (if applicable)

### **contact.php:**
- [ ] Contact form
- [ ] Support email: paulhalonen@gmail.com
- [ ] Response time expectations
- [ ] FAQ link

### **terms.php:**
- [ ] Terms of service
- [ ] User responsibilities
- [ ] Service limitations
- [ ] Cancellation policy
- [ ] Payment terms

### **privacy.php:**
- [ ] Privacy policy
- [ ] What data we collect (minimal!)
- [ ] How we use data
- [ ] Third parties (PayPal only)
- [ ] Data retention
- [ ] Your rights

### **404.php:**
- [ ] "Page not found"
- [ ] Search box
- [ ] Popular links
- [ ] Home button

---

## 🔧 TASK 12.5: Create Reusable Components

**Time:** 30 minutes  
**Lines:** ~200 lines total  

### **includes/header.php:**
```php
<header class="site-header">
    <div class="container">
        <div class="logo">
            <a href="/"><?= Content::get('site_title') ?></a>
        </div>
        <nav class="main-nav">
            <a href="/">Home</a>
            <a href="/features.php">Features</a>
            <a href="/pricing.php">Pricing</a>
            <a href="/about.php">About</a>
            <a href="/contact.php">Contact</a>
        </nav>
        <div class="header-cta">
            <a href="/auth/login.php" class="btn-secondary">Sign In</a>
            <a href="/auth/register.php" class="btn-primary">Start Free Trial</a>
        </div>
    </div>
</header>
```

### **includes/footer.php:**
```php
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>Product</h4>
                <a href="/features.php">Features</a>
                <a href="/pricing.php">Pricing</a>
                <a href="/downloads/">Apps</a>
            </div>
            <div class="footer-col">
                <h4>Company</h4>
                <a href="/about.php">About</a>
                <a href="/contact.php">Contact</a>
            </div>
            <div class="footer-col">
                <h4>Legal</h4>
                <a href="/terms.php">Terms</a>
                <a href="/privacy.php">Privacy</a>
            </div>
            <div class="footer-col">
                <h4>Support</h4>
                <a href="mailto:paulhalonen@gmail.com">Email Support</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 <?= Content::get('site_title') ?>. All rights reserved.</p>
        </div>
    </div>
</footer>
```

---

## 📊 COMPLETION CHECKLIST

### **Pages:**
- [ ] index.php (homepage)
- [ ] pricing.php (detailed pricing)
- [ ] features.php (all features)
- [ ] about.php (company info)
- [ ] contact.php (support)
- [ ] terms.php (legal)
- [ ] privacy.php (legal)
- [ ] 404.php (error)

### **Components:**
- [ ] includes/header.php
- [ ] includes/footer.php

### **Content:**
- [ ] VPN explanation written
- [ ] Features described
- [ ] Pricing accurate
- [ ] FAQ answers written
- [ ] Legal documents written

### **Testing:**
- [ ] All pages load
- [ ] Theme colors apply
- [ ] Mobile responsive
- [ ] Links work
- [ ] Forms submit
- [ ] CTAs functional
- [ ] No hardcoded values
- [ ] Fast load times

---

## ⏱️ TIME ESTIMATE

**Total Time:** 5-6 hours

**Breakdown:**
- Homepage: 2 hours
- Pricing: 1.5 hours
- Features: 1 hour
- Other pages: 1 hour
- Components: 30 minutes

**Total Lines:** ~2,350 lines

---

## 🚀 PRIORITY

**CRITICAL - LAUNCH BLOCKER**

Without these pages:
- ❌ No one can learn about TrueVault
- ❌ No one can see pricing
- ❌ No one can sign up
- ❌ Website appears broken

**Must complete before launch!**

---

**END OF PART 12 CHECKLIST**
