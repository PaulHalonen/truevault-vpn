<?php
/**
 * TrueVault VPN - Constants
 * Global constants and configuration
 */

// Site Configuration
define('SITE_NAME', 'TrueVault VPN');
define('SITE_URL', 'https://vpn.the-truth-publishing.com');
define('API_URL', SITE_URL . '/api');
define('API_VERSION', '1.0.0');

// Paths
define('ROOT_PATH', dirname(dirname(__DIR__)));
define('API_PATH', ROOT_PATH . '/api');
define('DATABASE_PATH', ROOT_PATH . '/databases');
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('DOWNLOAD_PATH', ROOT_PATH . '/downloads');
define('LOG_PATH', ROOT_PATH . '/logs');
define('TEMPLATE_PATH', ROOT_PATH . '/templates');

// Upload Limits
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'txt']);

// CORS
define('ALLOWED_ORIGINS', [
    'https://vpn.the-truth-publishing.com',
    'http://localhost:3000',
    'http://localhost:8080'
]);

// VPN Servers
define('VPN_SERVERS', [
    'us-east' => [
        'name' => 'US East',
        'ip' => '66.94.103.91',
        'api_port' => 8080,
        'wg_port' => 51820,
        'type' => 'shared'
    ],
    'us-central-vip' => [
        'name' => 'US Central (VIP)',
        'ip' => '144.126.133.253',
        'api_port' => 8080,
        'wg_port' => 51820,
        'type' => 'vip',
        'vip_user' => 'seige235@yahoo.com'
    ],
    'us-south' => [
        'name' => 'US South',
        'ip' => '66.241.124.4',
        'api_port' => 8443,
        'wg_port' => 51820,
        'type' => 'shared'
    ],
    'canada' => [
        'name' => 'Canada',
        'ip' => '66.241.125.247',
        'api_port' => 8080,
        'wg_port' => 51820,
        'type' => 'shared'
    ]
]);

// PayPal Configuration
define('PAYPAL_MODE', 'live'); // 'sandbox' or 'live'
define('PAYPAL_CLIENT_ID', 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk');
define('PAYPAL_CLIENT_SECRET', 'EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN');
define('PAYPAL_WEBHOOK_ID', '46924926WL757580D');

// Email Configuration
define('MAIL_FROM_EMAIL', 'noreply@truthvault.com');
define('MAIL_FROM_NAME', 'TrueVault VPN');
define('MAIL_REPLY_TO', 'support@truthvault.com');

// Security
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
define('SESSION_LIFETIME', 60 * 60 * 24 * 7); // 7 days

// Subscription Plans
define('PLAN_LIMITS', [
    'trial' => [
        'devices' => 3,
        'identities' => 3,
        'mesh_users' => 0,
        'bandwidth_gb' => 10
    ],
    'personal' => [
        'devices' => 3,
        'identities' => 3,
        'mesh_users' => 0,
        'bandwidth_gb' => null // unlimited
    ],
    'family' => [
        'devices' => 999,
        'identities' => 999,
        'mesh_users' => 6,
        'bandwidth_gb' => null
    ],
    'business' => [
        'devices' => 999,
        'identities' => 999,
        'mesh_users' => 25,
        'bandwidth_gb' => null
    ]
]);

// Scanner Configuration
define('SCANNER_VERSION', '2.0.0');
define('SCANNER_TOKEN_EXPIRY', 60 * 60 * 24 * 30); // 30 days

// Certificate Configuration
define('CERT_VALIDITY_DAYS', 365);
define('CA_VALIDITY_DAYS', 3650); // 10 years

// Rate Limiting
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 60); // seconds
