<?php
declare(strict_types=1); // ADDED: PHP strict types must be the first PHP statement.

namespace App\Helpers;

class Sanitizer {
    
    /**
     * Sanitize username - allow only letters, numbers, underscores, dots
     */
    public static function sanitizeUsername(mixed $username): string {
        if ($username === null) return '';
        
        // Trim whitespace
        $sanitized = trim((string) $username);
        
        // Remove HTML tags
        $sanitized = strip_tags($sanitized);
        
        // Convert special characters to HTML entities
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        
        // Allow only letters, numbers, underscores, and dots
        $sanitized = preg_replace('/[^a-zA-Z0-9_.]/', '', $sanitized);
        
        return $sanitized ?? '';
    }
    
    public static function sanitizeClassName(mixed $className): string {
        if ($className === null) return '';
        
        // Trim whitespace
        $sanitized = trim((string) $className);
        
        // Remove HTML tags
        $sanitized = strip_tags($sanitized);
        
        // Convert special characters to HTML entities
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        
        // Allow only letters, numbers, underscores, and dots
        $sanitized = preg_replace('/[^a-zA-Z0-9_.]/', '', $sanitized);
        
        return $sanitized ?? '';
    }

    /**
     * Sanitize email
     */
    public static function sanitizeEmail(mixed $email): string {
        if ($email === null) return '';
        
        $sanitized = trim((string) $email);
        $sanitized = strip_tags($sanitized);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        $sanitized = filter_var($sanitized, FILTER_SANITIZE_EMAIL);
        $sanitized = preg_replace('/[^a-zA-Z0-9_.@]/', '', (string) $sanitized);
        return $sanitized ?? '';
    }
    
    /**
     * Sanitize password (minimal sanitization as passwords should be hashed)
     */
    public static function sanitizePassword(mixed $password): string {
        if ($password === null) return '';
        
        // Just trim whitespace, don't modify password content
        return trim((string) $password);
    }
    
    /**
     * Generic sanitize for string inputs
     */
    public static function sanitizeString(mixed $input): string {
        if ($input === null) return '';
        
        $sanitized = trim((string) $input);
        $sanitized = strip_tags($sanitized);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        
        return $sanitized;
    }

    public static function sanitizeId(mixed $id): int {
        return (int) $id;
    }
    
    /**
     * Sanitize array of inputs based on field types
     */
    public static function sanitizeArray(array $data, array $fieldTypes = []): array {
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
                    case 'class_name':
                        $sanitized[$field] = self::sanitizeClassName($value);
                        break;
                    case 'user_id':
                    case 'class_id':
                    case 'post_id':
                    case 'activity_id':
                    case 'account_id':
                    case 'id':
                        $sanitized[$field] = self::sanitizeId($value);
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