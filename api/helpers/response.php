<?php
/**
 * TrueVault VPN - Response Helper
 * Standardized JSON API responses
 */

class Response {
    
    /**
     * Send a success response
     */
    public static function success($data = null, $message = 'Success', $code = 200) {
        self::sendJson([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }
    
    /**
     * Send an error response
     */
    public static function error($message = 'Error', $code = 400, $errors = null) {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        self::sendJson($response, $code);
    }
    
    /**
     * Send a paginated response
     */
    public static function paginated($data, $page, $perPage, $total, $message = 'Success') {
        $totalPages = ceil($total / $perPage);
        
        self::sendJson([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => (int) $total,
                'total_pages' => (int) $totalPages,
                'has_more' => $page < $totalPages
            ]
        ], 200);
    }
    
    /**
     * Send a created response (201)
     */
    public static function created($data = null, $message = 'Created successfully') {
        self::success($data, $message, 201);
    }
    
    /**
     * Send an unauthorized response (401)
     */
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }
    
    /**
     * Send a forbidden response (403)
     */
    public static function forbidden($message = 'Forbidden') {
        self::error($message, 403);
    }
    
    /**
     * Send a not found response (404)
     */
    public static function notFound($message = 'Not found') {
        self::error($message, 404);
    }
    
    /**
     * Send a validation error response (422)
     */
    public static function validationError($errors, $message = 'Validation failed') {
        self::error($message, 422, $errors);
    }
    
    /**
     * Send a server error response (500)
     */
    public static function serverError($message = 'Internal server error') {
        self::error($message, 500);
    }
    
    /**
     * Send a rate limit response (429)
     */
    public static function rateLimited($message = 'Too many requests') {
        self::error($message, 429);
    }
    
    /**
     * Send JSON response with proper headers
     */
    public static function sendJson($data, $code = 200) {
        // Set CORS headers
        self::setCorsHeaders();
        
        // Set content type
        header('Content-Type: application/json; charset=utf-8');
        
        // Set HTTP status code
        http_response_code($code);
        
        // Send JSON
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Set CORS headers
     */
    public static function setCorsHeaders() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Check if origin is allowed
        $allowedOrigins = [
            'https://vpn.the-truth-publishing.com',
            'http://localhost:3000',
            'http://localhost:8080',
            'http://localhost:8888'
        ];
        
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            header("Access-Control-Allow-Origin: *");
        }
        
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400");
    }
    
    /**
     * Handle OPTIONS preflight request
     */
    public static function handlePreflight() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            self::setCorsHeaders();
            http_response_code(204);
            exit;
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
     * Get request method
     */
    public static function getMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    /**
     * Require specific HTTP method
     */
    public static function requireMethod($method) {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            self::error("Method not allowed. Expected: $method", 405);
        }
    }
    
    /**
     * Require one of specified HTTP methods
     */
    public static function requireMethods($methods) {
        $methods = array_map('strtoupper', $methods);
        if (!in_array($_SERVER['REQUEST_METHOD'], $methods)) {
            self::error("Method not allowed. Expected: " . implode(', ', $methods), 405);
        }
    }
}

// Initialize - handle preflight requests
Response::handlePreflight();
