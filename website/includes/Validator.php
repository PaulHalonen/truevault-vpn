<?php
/**
 * Input Validator Class
 * 
 * PURPOSE: Validate and sanitize all user input
 * Prevents SQL injection, XSS, and invalid data
 * 
 * USAGE:
 *   $validator = new Validator();
 *   $validator->email($input, 'email');
 *   $validator->password($input, 'password');
 *   if ($validator->hasErrors()) { ... }
 * 
 * @created January 2026
 * @version 1.0.0
 */

// Security check
if (!defined('TRUEVAULT_INIT')) {
    http_response_code(403);
    die('Direct access not allowed');
}

class Validator {
    
    private $errors = [];
    private $data = [];
    
    /**
     * Validate email address
     */
    public function email($value, $field = 'email') {
        $value = trim($value);
        
        if (empty($value)) {
            $this->errors[$field] = 'Email is required';
        } elseif (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Invalid email format';
        } elseif (strlen($value) > 255) {
            $this->errors[$field] = 'Email too long (max 255 characters)';
        } else {
            $this->data[$field] = strtolower($value);
        }
        
        return $this;
    }
    
    /**
     * Validate password
     */
    public function password($value, $field = 'password', $requireStrong = true) {
        if (empty($value)) {
            $this->errors[$field] = 'Password is required';
        } elseif (strlen($value) < 8) {
            $this->errors[$field] = 'Password must be at least 8 characters';
        } elseif (strlen($value) > 128) {
            $this->errors[$field] = 'Password too long (max 128 characters)';
        } elseif ($requireStrong) {
            if (!preg_match('/[A-Z]/', $value)) {
                $this->errors[$field] = 'Password must contain at least one uppercase letter';
            } elseif (!preg_match('/[a-z]/', $value)) {
                $this->errors[$field] = 'Password must contain at least one lowercase letter';
            } elseif (!preg_match('/[0-9]/', $value)) {
                $this->errors[$field] = 'Password must contain at least one number';
            } else {
                $this->data[$field] = $value;
            }
        } else {
            $this->data[$field] = $value;
        }
        
        return $this;
    }
    
    /**
     * Validate required string
     */
    public function string($value, $field, $minLength = 1, $maxLength = 255) {
        $value = trim($value);
        
        if (empty($value) && $minLength > 0) {
            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        } elseif (strlen($value) < $minLength) {
            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$minLength} characters";
        } elseif (strlen($value) > $maxLength) {
            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be less than {$maxLength} characters";
        } else {
            $this->data[$field] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        
        return $this;
    }
    
    /**
     * Validate optional string (allows empty)
     */
    public function optionalString($value, $field, $maxLength = 255) {
        $value = trim($value);
        
        if (!empty($value)) {
            if (strlen($value) > $maxLength) {
                $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be less than {$maxLength} characters";
            } else {
                $this->data[$field] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        } else {
            $this->data[$field] = '';
        }
        
        return $this;
    }
    
    /**
     * Validate integer
     */
    public function integer($value, $field, $min = null, $max = null) {
        if (!is_numeric($value) || intval($value) != $value) {
            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a whole number';
        } else {
            $intValue = intval($value);
            
            if ($min !== null && $intValue < $min) {
                $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$min}";
            } elseif ($max !== null && $intValue > $max) {
                $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at most {$max}";
            } else {
                $this->data[$field] = $intValue;
            }
        }
        
        return $this;
    }
    
    /**
     * Validate device name
     */
    public function deviceName($value, $field = 'device_name') {
        $value = trim($value);
        
        if (empty($value)) {
            $this->errors[$field] = 'Device name is required';
        } elseif (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $value)) {
            $this->errors[$field] = 'Device name can only contain letters, numbers, spaces, dashes, and underscores';
        } elseif (strlen($value) > 50) {
            $this->errors[$field] = 'Device name too long (max 50 characters)';
        } else {
            $this->data[$field] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        
        return $this;
    }
    
    /**
     * Validate device type
     */
    public function deviceType($value, $field = 'device_type') {
        $validTypes = ['mobile', 'desktop', 'tablet', 'router', 'other'];
        
        if (empty($value)) {
            $this->errors[$field] = 'Device type is required';
        } elseif (!in_array($value, $validTypes)) {
            $this->errors[$field] = 'Invalid device type. Must be: ' . implode(', ', $validTypes);
        } else {
            $this->data[$field] = $value;
        }
        
        return $this;
    }
    
    /**
     * Validate port number
     */
    public function port($value, $field = 'port') {
        if (!is_numeric($value)) {
            $this->errors[$field] = 'Port must be a number';
        } else {
            $port = intval($value);
            if ($port < 1 || $port > 65535) {
                $this->errors[$field] = 'Port must be between 1 and 65535';
            } else {
                $this->data[$field] = $port;
            }
        }
        
        return $this;
    }
    
    /**
     * Validate IP address
     */
    public function ipAddress($value, $field = 'ip_address') {
        $value = trim($value);
        
        if (empty($value)) {
            $this->errors[$field] = 'IP address is required';
        } elseif (!filter_var($value, FILTER_VALIDATE_IP)) {
            $this->errors[$field] = 'Invalid IP address format';
        } else {
            $this->data[$field] = $value;
        }
        
        return $this;
    }
    
    /**
     * Validate protocol (tcp/udp/both)
     */
    public function protocol($value, $field = 'protocol') {
        $validProtocols = ['tcp', 'udp', 'both'];
        $value = strtolower(trim($value));
        
        if (empty($value)) {
            $this->errors[$field] = 'Protocol is required';
        } elseif (!in_array($value, $validProtocols)) {
            $this->errors[$field] = 'Protocol must be tcp, udp, or both';
        } else {
            $this->data[$field] = $value;
        }
        
        return $this;
    }
    
    /**
     * Check if there are any validation errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Get all errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get first error message
     */
    public function getFirstError() {
        return reset($this->errors) ?: null;
    }
    
    /**
     * Get all validated data
     */
    public function getData() {
        return $this->data;
    }
    
    /**
     * Get specific field value
     */
    public function get($field) {
        return $this->data[$field] ?? null;
    }
    
    /**
     * Reset validator for reuse
     */
    public function reset() {
        $this->errors = [];
        $this->data = [];
        return $this;
    }
}
