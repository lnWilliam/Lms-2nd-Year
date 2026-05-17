<?php
declare(strict_types=1); // ADDED: PHP strict types must be the first PHP statement.

namespace App\Helpers;

class Sanitizer {
    
    /**
     * Sanitize username - allow only letters, numbers, underscores, dots
     */
    public static function sanitizeUsername($username) {
        if ($username === null) return '';
        
        // Trim whitespace
        $sanitized = trim($username);
        
        // Remove HTML tags
        $sanitized = strip_tags($sanitized);
        
        // Convert special characters to HTML entities
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        
        // Allow only letters, numbers, underscores, and dots
        $sanitized = preg_replace('/[^a-zA-Z0-9_.]/', '', $sanitized);
        
        return $sanitized;
    }
    
    public static function sanitizeClassName($className) {
        if ($className === null) return '';
        
        // Trim whitespace
        $sanitized = trim($className);
        
        // Remove HTML tags
        $sanitized = strip_tags($sanitized);
        
        // Convert special characters to HTML entities
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        
        // Allow only letters, numbers, underscores, and dots
        $sanitized = preg_replace('/[^a-zA-Z0-9_.]/', '', $sanitized);
        
        return $sanitized;
    }

    /**
     * Sanitize email
     */
    public static function sanitizeEmail($email) {
        if ($email === null) return '';
        
        $sanitized = trim($email);
        $sanitized = strip_tags($sanitized);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        $sanitized = filter_var($sanitized, FILTER_SANITIZE_EMAIL);
        $sanitized = preg_replace('/[^a-zA-Z0-9_.@]/', '', $sanitized);
        return $sanitized;
    }
    
    /**
     * Sanitize password (minimal sanitization as passwords should be hashed)
     */
    public static function sanitizePassword($password) {
        if ($password === null) return '';
        
        // Just trim whitespace, don't modify password content
        return trim($password);
    }
    
    /**
     * Generic sanitize for string inputs
     */
    public static function sanitizeString($input) {
        if ($input === null) return '';
        
        $sanitized = trim($input);
        $sanitized = strip_tags($sanitized);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        
        return $sanitized;
    }
    
    /**
     * Sanitize array of inputs based on field types
     */
    public static function sanitizeArray($data, $fieldTypes = []) {
        $sanitized = [];
        
        foreach ($data as $field => $value) {
            if (isset($fieldTypes[$field])) {
                switch ($fieldTypes[$field]) {
                    case 'username':
                        $sanitized[$field] = self::sanitizeUsername($value);
                        break;
                    case 'email':
                        $sanitized[$field] = self::sanitizeEmail($value);
                        break;
                    case 'password':
                        $sanitized[$field] = self::sanitizePassword($value);
                        break;
                    default:
                        $sanitized[$field] = self::sanitizeString($value);
                }
            } else {
                $sanitized[$field] = self::sanitizeString($value);
            }
        }
        
        return $sanitized;
    }
}
?>