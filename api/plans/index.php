<?php
/**
 * TrueVault VPN - Subscription Plans API
 * GET /api/plans/index.php - List available subscription plans
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

// No auth required - plans are public
Response::requireMethods(['GET']);

try {
    $plans = Database::query('plans', "
        SELECT id, name, slug, price_monthly, price_yearly, features, 
               max_devices, max_identities, max_mesh_members, is_vip_required
        FROM subscription_plans 
        WHERE is_active = 1
        ORDER BY sort_order ASC
    ");
    
    // Parse features string into array
    foreach ($plans as &$plan) {
        $plan['features'] = $plan['features'] ? explode('|', $plan['features']) : [];
        $plan['price_monthly'] = (float) $plan['price_monthly'];
        $plan['price_yearly'] = (float) $plan['price_yearly'];
        $plan['max_devices'] = (int) $plan['max_devices'];
        $plan['max_identities'] = (int) $plan['max_identities'];
        $plan['max_mesh_members'] = (int) $plan['max_mesh_members'];
        $plan['is_vip_required'] = (bool) $plan['is_vip_required'];
    }
    
    Response::success(['plans' => $plans]);
    
} catch (Exception $e) {
    error_log("Plans API error: " . $e->getMessage());
    Response::serverError('Failed to get plans');
}
