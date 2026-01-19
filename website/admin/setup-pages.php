<?php
/**
 * TruthVault VPN - Pre-Populate Pages
 * Creates 9 essential pages with basic content
 */

define('TRUEVAULT_INIT', true);

require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/PageBuilder.php';

$db = Database::getInstance();
$themesConn = $db->getConnection('themes');

echo "<h1>Pre-Populating Pages...</h1>\n";

// Define pages
$pages = [
    [
        'title' => 'Home',
        'slug' => 'home',
        'sections' => [
            ['type' => 'hero', 'data' => [
                'title' => 'Welcome to TruthVault VPN',
                'subtitle' => 'Your complete digital fortress. Secure, private, automated.',
                'cta_text' => 'Start Free Trial',
                'cta_link' => '/register.php'
            ]],
            ['type' => 'features', 'data' => [
                'title' => 'Why Choose TruthVault?',
                'features' => [
                    'Smart Identity Router - Consistent digital personas',
                    'Family Mesh Network - Connect all devices',
                    '256-bit Encryption - Military-grade security'
                ]
            ]]
        ]
    ],
    [
        'title' => 'Features',
        'slug' => 'features',
        'sections' => [
            ['type' => 'hero', 'data' => [
                'title' => 'Revolutionary Features',
                'subtitle' => 'Beyond traditional VPN services'
            ]],
            ['type' => 'content', 'data' => [
                'title' => 'Smart Identity Router',
                'content' => '<p>Maintain consistent digital identities for different regions. Your Canadian bank always sees the same trusted IP.</p>'
            ]],
            ['type' => 'content', 'data' => [
                'title' => 'Family Mesh Network',
                'content' => '<p>Connect all your devices and family members as if on the same local network, regardless of location.</p>'
            ]]
        ]
    ],
    [
        'title' => 'Pricing',
        'slug' => 'pricing',
        'sections' => [
            ['type' => 'hero', 'data' => [
                'title' => 'Choose Your Plan',
                'subtitle' => 'Simple, transparent pricing'
            ]],
            ['type' => 'pricing', 'data' => [
                'plans' => [
                    ['name' => 'Personal', 'price' => '$9.99/mo', 'features' => ['3 Devices', 'Personal Use', 'Basic Support']],
                    ['name' => 'Family', 'price' => '$14.99/mo', 'features' => ['Unlimited Devices', 'Family Network', 'Priority Support']],
                    ['name' => 'Business', 'price' => '$29.99/mo', 'features' => ['Team Features', 'Admin Dashboard', 'Dedicated Support']]
                ]
            ]]
        ]
    ],
    [
        'title' => 'About Us',
        'slug' => 'about',
        'sections' => [
            ['type' => 'content', 'data' => [
                'title' => 'About TruthVault VPN',
                'content' => '<p>We built TruthVault to solve the real problems with traditional VPNs. Our mission is to provide true privacy and security without compromising usability.</p>'
            ]]
        ]
    ],
    [
        'title' => 'Privacy Policy',
        'slug' => 'privacy',
        'sections' => [
            ['type' => 'content', 'data' => [
                'title' => 'Privacy Policy',
                'content' => '<p>Your privacy is our top priority. We collect minimal data and never sell your information.</p>'
            ]]
        ]
    ],
    [
        'title' => 'Terms of Service',
        'slug' => 'terms',
        'sections' => [
            ['type' => 'content', 'data' => [
                'title' => 'Terms of Service',
                'content' => '<p>By using TruthVault VPN, you agree to these terms.</p>'
            ]]
        ]
    ],
    [
        'title' => 'Contact',
        'slug' => 'contact',
        'sections' => [
            ['type' => 'contact', 'data' => [
                'title' => 'Get In Touch',
                'email' => 'support@truthvault.com'
            ]]
        ]
    ],
    [
        'title' => 'FAQ',
        'slug' => 'faq',
        'sections' => [
            ['type' => 'faq', 'data' => [
                'title' => 'Frequently Asked Questions',
                'items' => [
                    ['q' => 'How secure is TruthVault?', 'a' => 'Military-grade 256-bit encryption.'],
                    ['q' => 'Can I try it free?', 'a' => 'Yes! 7-day free trial.'],
                    ['q' => 'How many devices?', 'a' => 'Depends on your plan.']
                ]
            ]]
        ]
    ],
    [
        'title' => 'Blog',
        'slug' => 'blog',
        'sections' => [
            ['type' => 'content', 'data' => [
                'title' => 'Blog',
                'content' => '<p>Coming soon...</p>'
            ]]
        ]
    ]
];

// Insert pages
foreach ($pages as $pageData) {
    echo "Creating page: {$pageData['title']}...\n";
    
    // Check if page exists
    $stmt = $themesConn->prepare("SELECT id FROM pages WHERE slug = ?");
    $stmt->execute([$pageData['slug']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "  - Already exists, skipping\n";
        continue;
    }
    
    // Insert page
    $stmt = $themesConn->prepare("
        INSERT INTO pages (title, slug, meta_description, is_active, is_public, sort_order)
        VALUES (?, ?, ?, 1, 1, 0)
    ");
    $stmt->execute([
        $pageData['title'],
        $pageData['slug'],
        $pageData['title'] . ' - TruthVault VPN'
    ]);
    
    $pageId = $themesConn->lastInsertId();
    
    // Insert sections
    foreach ($pageData['sections'] as $index => $sectionData) {
        $sectionId = PageBuilder::addSection(
            $pageId,
            $sectionData['type'],
            $sectionData['data'],
            $index
        );
        
        if ($sectionId) {
            echo "  - Added section: {$sectionData['type']}\n";
        }
    }
    
    echo "  ✓ Created successfully\n\n";
}

echo "<h2 style='color: green;'>✓ All pages created!</h2>";
echo "<p><a href='/'>View Homepage</a> | <a href='/admin/page-builder.php'>Edit Pages</a></p>";
?>
