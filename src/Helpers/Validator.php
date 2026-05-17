<?php
declare(strict_types=1); // ADDED: PHP strict types must be the first PHP statement.

namespace App\Helpers;

class Validator {
    private static array $errors = [];
    
    /**
     * Validate username
     */
    public static function validateUsername(mixed $username): bool {
        $username = (string) $username;
        if (empty($username)) {
            self::$errors[] = "Username is required";
            return false;
        }
        
        if (strlen($username) < 3) {
            self::$errors[] = "Username must be at least 3 characters long";
            return false;
        }
        
        if (strlen($username) > 50) {
            self::$errors[] = "Username cannot exceed 50 characters";
            return false;
        }
        
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_.]*$/', $username)) {
            self::$errors[] = "Username must start with a letter and can only contain letters, numbers, underscores and dots";
            return false;
        }
        
        // Check for SQL injection patterns
        $sqlPatterns = ['/\bSELECT\b/i', '/\bINSERT\b/i', '/\bUPDATE\b/i', '/\bDELETE\b/i', '/\bDROP\b/i', '/--/', '/;\s*$/'];
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $username)) {
                self::$errors[] = "Username contains invalid characters or patterns";
                return false;
            }
        }
        
        return true;
    }
    
    public static function validateClassName(mixed $className): bool {
        $className = (string) $className;
        if (empty($className)) {
            self::$errors[] = "className is required";
            return false;
        }
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_.]*$/', $className)) {
            self::$errors[] = "Class Name must start with a letter and can only contain letters, numbers, underscores and dots";
            return false;
        }
        $sqlPatterns = ['/\bSELECT\b/i', '/\bINSERT\b/i', '/\bUPDATE\b/i', '/\bDELETE\b/i', '/\bDROP\b/i', '/--/', '/;\s*$/'];
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $className)) {
                self::$errors[] = "Class Name contains invalid characters or patterns";
                return false;
            }
        }

        return true;
    }
    /**
     * Validate email
     */
    public static function validateEmail(mixed $email): bool {
        $email = (string) $email;
        if (empty($email)) {
            self::$errors[] = "Email is required";
            return false;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::$errors[] = "Invalid email format";
            return false;
        }
        
        if (strlen($email) > 100) {
            self::$errors[] = "Email cannot exceed 100 characters";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate password
     */
    public static function validatePassword(mixed $password, mixed $confirmPassword = null): bool {
        $password = (string) $password;
        if ($confirmPassword !== null) {
            $confirmPassword = (string) $confirmPassword;
        }
        if (empty($password)) {
            self::$errors[] = "Password is required";
            return false;
        }
        
        if (strlen($password) < 8) {
            self::$errors[] = "Password must be at least 8 characters long";
            return false;
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            self::$errors[] = "Password must contain at least one uppercase letter";
            return false;
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            self::$errors[] = "Password must contain at least one lowercase letter";
            return false;
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            self::$errors[] = "Password must contain at least one number";
            return false;
        }
        
        if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
            self::$errors[] = "Password must contain at least one special character";
            return false;
        }
        
        if ($confirmPassword !== null && $password !== $confirmPassword) {
            self::$errors[] = "Passwords do not match";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate required fields
     */
    public static function validateRequired(array $data, array $requiredFields): bool {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim((string) $data[$field]))) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            self::$errors[] = "Required fields missing: " . implode(', ', $missing);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get all validation errors
     */
    public static function getErrors(): array {
        return self::$errors;
    }
    
    /**
     * Clear errors
     */
    public static function clearErrors(): void {
        self::$errors = [];
    }
    
    /**
     * Check if there are any errors
     */
    public static function hasErrors(): bool {
        return !empty(self::$errors);
    }
}
?>