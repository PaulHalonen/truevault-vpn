<?php
/**
 * TrueVault VPN - Billing Plans API
 * 
 * GET ?currency=USD|CAD - Get all available plans
 * 
 * PRICING:
 * Personal:   $9.97/mo  | $99.97/yr
 * Family:    $14.97/mo  | $140.97/yr
 * Dedicated: $39.97/mo  | $399.97/yr
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get currency preference
$currency = strtoupper($_GET['currency'] ?? 'USD');
if (!in_array($currency, ['USD', 'CAD'])) {
    $currency = 'USD';
}

// CAD conversion rate (approximately 1.40)
$cadRate = 1.40;

// Define plans with correct pricing
$plans = [
    'personal' => [
        'id' => 'personal',
        'name' => 'Personal',
        'description' => 'VPN protection for 3 devices',
        'devices' => 3,
        'features' => [
            '3 Devices',
            'All Server Locations',
            'Unlimited Bandwidth',
            '24/7 Support',
            'Browser-side Encryption',
            'Kill Switch'
        ],
        'pricing' => [
            'monthly' => [
                'usd' => 9.97,
                'cad' => round(9.97 * $cadRate, 2)
            ],
            'annual' => [
                'usd' => 99.97,
                'cad' => round(99.97 * $cadRate, 2)
            ]
        ],
        'popular' => false
    ],
    'family' => [
        'id' => 'family',
        'name' => 'Family',
        'description' => 'VPN protection for 6 devices',
        'devices' => 6,
        'features' => [
            '6 Devices',
            'All Server Locations',
            'Unlimited Bandwidth',
            'Priority Support',
            'Browser-side Encryption',
            'Kill Switch',
            'Family Sharing',
            'Parental Controls'
        ],
        'pricing' => [
            'monthly' => [
                'usd' => 14.97,
                'cad' => round(14.97 * $cadRate, 2)
            ],
            'annual' => [
                'usd' => 140.97,
                'cad' => round(140.97 * $cadRate, 2)
            ]
        ],
        'popular' => true
    ],
    'dedicated' => [
        'id' => 'dedicated',
        'name' => 'Dedicated Server',
        'description' => 'Your own private VPN server',
        'devices' => 999,
        'features' => [
            'Unlimited Devices',
            'Dedicated Server IP',
            'Full Server Control',
            'Priority Support',
            'Browser-side Encryption',
            'Kill Switch',
            'Custom DNS',
            'Port Forwarding',
            'Static IP Address',
            'Enterprise Security'
        ],
        'pricing' => [
            'monthly' => [
                'usd' => 39.97,
                'cad' => round(39.97 * $cadRate, 2)
            ],
            'annual' => [
                'usd' => 399.97,
                'cad' => round(399.97 * $cadRate, 2)
            ]
        ],
        'popular' => false
    ]
];

// Format response based on currency
$formattedPlans = [];
foreach ($plans as $key => $plan) {
    $monthly = $plan['pricing']['monthly'][$currency === 'CAD' ? 'cad' : 'usd'];
    $annual = $plan['pricing']['annual'][$currency === 'CAD' ? 'cad' : 'usd'];
    $annualMonthly = round($annual / 12, 2);
    $savings = round(($monthly * 12) - $annual, 2);
    $savingsPercent = round((($monthly * 12 - $annual) / ($monthly * 12)) * 100);
    
    $formattedPlans[] = [
        'id' => $plan['id'],
        'name' => $plan['name'],
        'description' => $plan['description'],
        'devices' => $plan['devices'],
        'features' => $plan['features'],
        'popular' => $plan['popular'],
        'pricing' => [
            'currency' => $currency,
            'symbol' => '$',
            'monthly' => $monthly,
            'monthly_display' => '$' . number_format($monthly, 2),
            'annual' => $annual,
            'annual_display' => '$' . number_format($annual, 2),
            'annual_monthly' => $annualMonthly,
            'annual_monthly_display' => '$' . number_format($annualMonthly, 2),
            'savings' => $savings,
            'savings_display' => '$' . number_format($savings, 2),
            'savings_percent' => $savingsPercent
        ],
        // Also include both currencies for toggle display
        'all_pricing' => [
            'usd' => [
                'monthly' => $plan['pricing']['monthly']['usd'],
                'annual' => $plan['pricing']['annual']['usd']
            ],
            'cad' => [
                'monthly' => $plan['pricing']['monthly']['cad'],
                'annual' => $plan['pricing']['annual']['cad']
            ]
        ]
    ];
}

echo json_encode([
    'success' => true,
    'currency' => $currency,
    'plans' => $formattedPlans
]);
