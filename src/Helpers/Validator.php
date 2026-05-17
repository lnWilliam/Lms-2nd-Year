<?php
declare(strict_types=1);
namespace App\Helpers;

/**
 * Validates sanitized input before the application writes data or accepts login details.
 *
 * The validator keeps business rules in one place so controllers can make decisions
 * using consistent error messages and database-safe values.
 *
 * @package App\Helpers
 * @author Charlo Marco
 * @since 2026-05-17
 */
class Validator
{
    /** @var array<int, string> List of validation errors collected during the current request. */
    private static array $errors = [];

    /**
     * Validates a username against length, format, and unsafe SQL-like patterns.
     *
     * This protects registration and username availability checks from accepting empty,
     * malformed, or suspicious values.
     *
     * @param mixed $username Username value to validate.
     * @return bool True when the username passes all validation rules; otherwise false.
     * @throws \Throwable If an unexpected runtime error occurs during validation.
     */
    public static function validateUsername(mixed $username): bool
    {
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

        $sqlPatterns = ['/\bSELECT\b/i', '/\bINSERT\b/i', '/\bUPDATE\b/i', '/\bDELETE\b/i', '/\bDROP\b/i', '/--/', '/;\s*$/'];
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $username)) {
                self::$errors[] = "Username contains invalid characters or patterns";
                return false;
            }
        }

        return true;
    }

    /**
     * Validates a class name against the naming rules used by the LMS.
     *
     * Class names are checked before creation so the user receives immediate feedback and
     * invalid values never reach the database insert step.
     *
     * @param mixed $className Class name value to validate.
     * @return bool True when the class name is valid; otherwise false.
     * @throws \Throwable If an unexpected runtime error occurs during validation.
     */
    public static function validateClassName(mixed $className): bool
    {
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
     * Validates an email address for registration and availability checks.
     *
     * This ensures email values are present, correctly formatted, and short enough for the
     * database column before uniqueness checks are performed.
     *
     * @param mixed $email Email value to validate.
     * @return bool True when the email is valid; otherwise false.
     * @throws \Throwable If an unexpected runtime error occurs during validation.
     */
    public static function validateEmail(mixed $email): bool
    {
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
     * Validates password strength and optional confirmation matching.
     *
     * Password rules are enforced before hashing so weak or mismatched passwords never create
     * an account record.
     *
     * @param mixed $password Password value to validate.
     * @param mixed $confirmPassword Optional confirmation password value.
     * @return bool True when the password satisfies all strength and match rules; otherwise false.
     * @throws \Throwable If an unexpected runtime error occurs during validation.
     */
    public static function validatePassword(mixed $password, mixed $confirmPassword = null): bool
    {
        $password = (string) $password;
        $confirmPassword = $confirmPassword === null ? null : (string) $confirmPassword;

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
     * Confirms that required fields exist and are not blank.
     *
     * Controllers use this before deeper validation so users receive clear messages when
     * mandatory form fields are missing.
     *
     * @param array $data Sanitized data keyed by field name.
     * @param array $requiredFields Field names that must be present and non-empty.
     * @return bool True when all required fields are present; otherwise false.
     * @throws \Throwable If an unexpected runtime error occurs during validation.
     */
    public static function validateRequired(array $data, array $requiredFields): bool
    {
        $missing = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
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
     * Returns all validation errors collected during the current validation flow.
     *
     * This allows controllers to display user-friendly feedback after one or more checks fail.
     *
     * @return array<int, string> Validation error messages.
     * @throws \Throwable If an unexpected runtime error occurs while reading errors.
     */
    public static function getErrors(): array
    {
        return self::$errors;
    }

    /**
     * Clears validation errors before a new validation flow begins.
     *
     * This prevents old errors from leaking into a new request or a new field validation check.
     *
     * @return void No value is returned.
     * @throws \Throwable If an unexpected runtime error occurs while clearing errors.
     */
    public static function clearErrors(): void
    {
        self::$errors = [];
    }

    /**
     * Checks whether the current validation flow has any errors.
     *
     * This method gives callers a simple way to branch when validation has already collected
     * messages.
     *
     * @return bool True when errors exist; otherwise false.
     * @throws \Throwable If an unexpected runtime error occurs while checking errors.
     */
    public static function hasErrors(): bool
    {
        return !empty(self::$errors);
    }
}
