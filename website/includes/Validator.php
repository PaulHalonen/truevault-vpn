<?php
/**
 * Validator Class
 * 
 * PURPOSE: Validate user input and collect errors
 * USAGE: Create validator, add validations, check for errors
 * 
 * EXAMPLES:
 * $validator = new Validator();
 * $validator->required($email, 'email');
 * $validator->email($email, 'email');
 * $validator->minLength($password, 8, 'password');
 * if ($validator->hasErrors()) {
 *     $errors = $validator->getErrors();
 * }
 * 
 * @created January 2026
 * @version 1.0.0
 */

class Validator {
    
    /**
     * Collected errors
     * @var array
     */
    private $errors = [];
    
    /**
     * Check if validator has any errors
     * 
     * @return bool True if errors exist
     */
    public function hasErrors() {
        return count($this->errors) > 0;
    }
    
    /**
     * Get all errors
     * 
     * @return array Array of error messages
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Add custom error
     * 
     * @param string $field Field name
     * @param string $message Error message
     */
    public function addError($field, $message) {
        $this->errors[$field] = $message;
    }
    
    /**
     * Validate required field
     * 
     * @param mixed $value Value to check
     * @param string $field Field name
     * @return bool Valid status
     */
    public function required($value, $field) {
        if (empty($value) && $value !== '0' && $value !== 0) {
            $this->errors[$field] = ucfirst($field) . " is required";
            return false;
        }
        return true;
    }
    
    /**
     * Validate email format
     * 
     * @param string $value Email address
     * @param string $field Field name
     * @return bool Valid status
     */
    public function email($value, $field) {
        if (empty($value)) {
            $this->errors[$field] = "Email is required";
            return false;
        }
        
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "Invalid email format";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate minimum length
     * 
     * @param string $value Value to check
     * @param int $min Minimum length
     * @param string $field Field name
     * @return bool Valid status
     */
    public function minLength($value, $min, $field) {
        if (strlen($value) < $min) {
            $this->errors[$field] = ucfirst($field) . " must be at least $min characters";
            return false;
        }
        return true;
    }
    
    /**
     * Validate maximum length
     * 
     * @param string $value Value to check
     * @param int $max Maximum length
     * @param string $field Field name
     * @return bool Valid status
     */
    public function maxLength($value, $max, $field) {
        if (strlen($value) > $max) {
            $this->errors[$field] = ucfirst($field) . " must not exceed $max characters";
            return false;
        }
        return true;
    }
    
    /**
     * Validate password strength
     * Requires: min 8 chars, 1 uppercase, 1 lowercase, 1 number
     * 
     * @param string $value Password
     * @param string $field Field name
     * @return bool Valid status
     */
    public function password($value, $field) {
        if (empty($value)) {
            $this->errors[$field] = "Password is required";
            return false;
        }
        
        if (strlen($value) < 8) {
            $this->errors[$field] = "Password must be at least 8 characters";
            return false;
        }
        
        if (!preg_match('/[A-Z]/', $value)) {
            $this->errors[$field] = "Password must contain at least one uppercase letter";
            return false;
        }
        
        if (!preg_match('/[a-z]/', $value)) {
            $this->errors[$field] = "Password must contain at least one lowercase letter";
            return false;
        }
        
        if (!preg_match('/[0-9]/', $value)) {
            $this->errors[$field] = "Password must contain at least one number";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate passwords match
     * 
     * @param string $password1 First password
     * @param string $password2 Second password
     * @param string $field Field name
     * @return bool Valid status
     */
    public function passwordsMatch($password1, $password2, $field = 'password_confirm') {
        if ($password1 !== $password2) {
            $this->errors[$field] = "Passwords do not match";
            return false;
        }
        return true;
    }
    
    /**
     * Validate string matches pattern
     * 
     * @param string $value Value to check
     * @param string $pattern Regex pattern
     * @param string $field Field name
     * @param string $message Custom error message
     * @return bool Valid status
     */
    public function pattern($value, $pattern, $field, $message = null) {
        if (!preg_match($pattern, $value)) {
            $this->errors[$field] = $message ?: ucfirst($field) . " format is invalid";
            return false;
        }
        return true;
    }
    
    /**
     * Validate value is in array of allowed values
     * 
     * @param mixed $value Value to check
     * @param array $allowed Array of allowed values
     * @param string $field Field name
     * @return bool Valid status
     */
    public function inArray($value, $allowed, $field) {
        if (!in_array($value, $allowed, true)) {
            $this->errors[$field] = ucfirst($field) . " must be one of: " . implode(', ', $allowed);
            return false;
        }
        return true;
    }
    
    /**
     * Validate numeric value
     * 
     * @param mixed $value Value to check
     * @param string $field Field name
     * @return bool Valid status
     */
    public function numeric($value, $field) {
        if (!is_numeric($value)) {
            $this->errors[$field] = ucfirst($field) . " must be a number";
            return false;
        }
        return true;
    }
    
    /**
     * Validate integer value
     * 
     * @param mixed $value Value to check
     * @param string $field Field name
     * @return bool Valid status
     */
    public function integer($value, $field) {
        if (!is_int($value) && !ctype_digit($value)) {
            $this->errors[$field] = ucfirst($field) . " must be an integer";
            return false;
        }
        return true;
    }
    
    /**
     * Validate minimum numeric value
     * 
     * @param mixed $value Value to check
     * @param float $min Minimum value
     * @param string $field Field name
     * @return bool Valid status
     */
    public function min($value, $min, $field) {
        if ($value < $min) {
            $this->errors[$field] = ucfirst($field) . " must be at least $min";
            return false;
        }
        return true;
    }
    
    /**
     * Validate maximum numeric value
     * 
     * @param mixed $value Value to check
     * @param float $max Maximum value
     * @param string $field Field name
     * @return bool Valid status
     */
    public function max($value, $max, $field) {
        if ($value > $max) {
            $this->errors[$field] = ucfirst($field) . " must not exceed $max";
            return false;
        }
        return true;
    }
    
    /**
     * Validate URL format
     * 
     * @param string $value URL
     * @param string $field Field name
     * @return bool Valid status
     */
    public function url($value, $field) {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field] = "Invalid URL format";
            return false;
        }
        return true;
    }
    
    /**
     * Validate IP address
     * 
     * @param string $value IP address
     * @param string $field Field name
     * @return bool Valid status
     */
    public function ip($value, $field) {
        if (!filter_var($value, FILTER_VALIDATE_IP)) {
            $this->errors[$field] = "Invalid IP address";
            return false;
        }
        return true;
    }
}
?>
