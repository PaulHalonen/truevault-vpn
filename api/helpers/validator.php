<?php
/**
 * TrueVault VPN - Validator Helper
 * Input validation for API requests
 */

class Validator {
    private $errors = [];
    private $data = [];
    
    /**
     * Create new validator instance
     */
    public function __construct($data = []) {
        $this->data = $data;
    }
    
    /**
     * Validate data against rules
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return Validator
     */
    public static function make($data, $rules) {
        $validator = new self($data);
        
        foreach ($rules as $field => $ruleSet) {
            $value = $data[$field] ?? null;
            $rules = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            
            foreach ($rules as $rule) {
                $params = [];
                if (strpos($rule, ':') !== false) {
                    list($rule, $paramStr) = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }
                
                $method = 'validate' . ucfirst($rule);
                if (method_exists($validator, $method)) {
                    $validator->$method($field, $value, $params);
                }
            }
        }
        
        return $validator;
    }
    
    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     */
    public function fails() {
        return !empty($this->errors);
    }
    
    /**
     * Get validation errors
     */
    public function errors() {
        return $this->errors;
    }
    
    /**
     * Get first error message
     */
    public function firstError() {
        foreach ($this->errors as $field => $messages) {
            return $messages[0];
        }
        return null;
    }
    
    /**
     * Add an error
     */
    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    // Validation methods
    
    private function validateRequired($field, $value, $params) {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' is required');
        }
    }
    
    private function validateEmail($field, $value, $params) {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'Invalid email address');
        }
    }
    
    private function validateMin($field, $value, $params) {
        $min = (int) ($params[0] ?? 0);
        if (is_string($value) && strlen($value) < $min) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be at least $min characters");
        } elseif (is_numeric($value) && $value < $min) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be at least $min");
        }
    }
    
    private function validateMax($field, $value, $params) {
        $max = (int) ($params[0] ?? 0);
        if (is_string($value) && strlen($value) > $max) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must not exceed $max characters");
        } elseif (is_numeric($value) && $value > $max) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must not exceed $max");
        }
    }
    
    private function validateNumeric($field, $value, $params) {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must be a number');
        }
    }
    
    private function validateInteger($field, $value, $params) {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must be an integer');
        }
    }
    
    private function validateAlpha($field, $value, $params) {
        if ($value !== null && $value !== '' && !ctype_alpha($value)) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must contain only letters');
        }
    }
    
    private function validateAlphaNum($field, $value, $params) {
        if ($value !== null && $value !== '' && !ctype_alnum($value)) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must contain only letters and numbers');
        }
    }
    
    private function validateUuid($field, $value, $params) {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        if ($value !== null && $value !== '' && !preg_match($pattern, $value)) {
            $this->addError($field, 'Invalid UUID format');
        }
    }
    
    private function validateIp($field, $value, $params) {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_IP)) {
            $this->addError($field, 'Invalid IP address');
        }
    }
    
    private function validateMac($field, $value, $params) {
        $pattern = '/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/';
        if ($value !== null && $value !== '' && !preg_match($pattern, $value)) {
            $this->addError($field, 'Invalid MAC address');
        }
    }
    
    private function validateUrl($field, $value, $params) {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, 'Invalid URL');
        }
    }
    
    private function validateIn($field, $value, $params) {
        if ($value !== null && $value !== '' && !in_array($value, $params)) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must be one of: ' . implode(', ', $params));
        }
    }
    
    private function validateDate($field, $value, $params) {
        if ($value !== null && $value !== '') {
            $format = $params[0] ?? 'Y-m-d';
            $d = DateTime::createFromFormat($format, $value);
            if (!$d || $d->format($format) !== $value) {
                $this->addError($field, 'Invalid date format');
            }
        }
    }
    
    private function validateConfirmed($field, $value, $params) {
        $confirmField = $field . '_confirmation';
        $confirmValue = $this->data[$confirmField] ?? null;
        if ($value !== $confirmValue) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' confirmation does not match');
        }
    }
    
    private function validateRegex($field, $value, $params) {
        $pattern = $params[0] ?? '';
        if ($value !== null && $value !== '' && !preg_match($pattern, $value)) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' format is invalid');
        }
    }
    
    private function validateBoolean($field, $value, $params) {
        $valid = [true, false, 0, 1, '0', '1', 'true', 'false'];
        if ($value !== null && !in_array($value, $valid, true)) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must be true or false');
        }
    }
    
    private function validateArray($field, $value, $params) {
        if ($value !== null && !is_array($value)) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must be an array');
        }
    }
    
    private function validateJson($field, $value, $params) {
        if ($value !== null && $value !== '') {
            json_decode($value);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must be valid JSON');
            }
        }
    }
}

/**
 * Quick validation function
 */
function validate($data, $rules) {
    return Validator::make($data, $rules);
}
