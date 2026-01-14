<?php
/**
 * TrueVault VPN - Response Helper
 * Standardized JSON responses for API
 */

class Response {
    
    /**
     * Send success response
     */
    public static function success($data = null, $message = 'Success', $code = 200) {
        self::sendJson([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }
    
    /**
     * Send error response
     */
    public static function error($message = 'Error', $code = 400, $errors = null) {
        self::sendJson([
            'success' => false,
            'error' => $message,
            'errors' => $errors
        ], $code);
    }
    
    /**
     * Send paginated response
     */
    public static function paginated($data, $page, $perPage, $total) {
        self::sendJson([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => (int) $total,
                'total_pages' => ceil($total / $perPage)
            ]
        ], 200);
    }
    
    /**
     * Send JSON response with headers
     */
    public static function sendJson($data, $code = 200) {
        // CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Content-Type: application/json; charset=utf-8');
        
        http_response_code($code);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Handle OPTIONS preflight
     */
    public static function handleOptions() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Access-Control-Max-Age: 86400');
            http_response_code(204);
            exit;
        }
    }
    
    /**
     * Require specific HTTP method
     */
    public static function requireMethod($method) {
        self::handleOptions();
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            self::error('Method not allowed', 405);
        }
    }
    
    /**
     * Get JSON input from request body
     */
    public static function getJsonInput() {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
    
    /**
     * Server error response
     */
    public static function serverError($message = 'Internal server error') {
        self::error($message, 500);
    }
    
    /**
     * Not found response
     */
    public static function notFound($message = 'Not found') {
        self::error($message, 404);
    }
    
    /**
     * Unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }
    
    /**
     * Forbidden response
     */
    public static function forbidden($message = 'Forbidden') {
        self::error($message, 403);
    }
}

// Auto-handle OPTIONS for CORS
Response::handleOptions();
