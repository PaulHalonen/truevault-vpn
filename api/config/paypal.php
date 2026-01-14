<?php
/**
 * TrueVault VPN - PayPal Configuration
 * Live PayPal API credentials
 */

return [
    // Environment: 'sandbox' or 'live'
    'environment' => 'live',
    
    // Live credentials
    'live' => [
        'client_id' => 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk',
        'client_secret' => 'EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN',
        'webhook_id' => '46924926WL757580D',
        'api_base' => 'https://api-m.paypal.com'
    ],
    
    // Sandbox credentials (for testing)
    'sandbox' => [
        'client_id' => '',
        'client_secret' => '',
        'webhook_id' => '',
        'api_base' => 'https://api-m.sandbox.paypal.com'
    ],
    
    // Business email
    'business_email' => 'paulhalonen@gmail.com',
    
    // Webhook URL (must be registered in PayPal)
    'webhook_url' => 'https://vpn.the-truth-publishing.com/api/payments/webhook.php',
    
    // Return URLs
    'return_url' => 'https://vpn.the-truth-publishing.com/dashboard/billing.html?payment=success',
    'cancel_url' => 'https://vpn.the-truth-publishing.com/dashboard/billing.html?payment=cancelled',
    
    // Currency
    'currency' => 'USD',
    
    // Plan IDs (create these in PayPal dashboard)
    'plans' => [
        'personal_monthly' => [
            'price' => 9.99,
            'name' => 'Personal Monthly',
            'description' => '3 devices, personal certificates'
        ],
        'family_monthly' => [
            'price' => 14.99,
            'name' => 'Family Monthly', 
            'description' => 'Unlimited devices, mesh networking'
        ],
        'business_monthly' => [
            'price' => 29.99,
            'name' => 'Business Monthly',
            'description' => 'Enterprise features, API access'
        ]
    ]
];
