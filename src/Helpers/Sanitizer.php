<?php

namespace App\Helpers;

/**
 * Sanitizes raw user input before validation and database processing.
 *
 * This helper centralizes input cleanup so controllers can consistently remove
 * unsafe characters, normalize scalar values, and avoid strict-type input errors.
 *
 * @package App\Helpers
 * @author Charlo Marco
 * @since 2026-05-17
 */
class Sanitizer
{
    /**
     * Cleans a username while preserving only the characters allowed by the account rules.
     *
     * This is used before validation so usernames are checked in a predictable format and
     * malicious markup or unsupported characters do not reach the model layer.
     *
     * @param mixed $username Raw username value from a form or API request.
     * @return string Sanitized username safe for validation.
     * @throws \Throwable If an unexpected runtime error occurs while sanitizing the value.
     */
    public static function sanitizeUsername(mixed $username): string
    {
        if ($username === null) {
            return '';
        }

        $sanitized = trim((string) $username);
        $sanitized = strip_tags($sanitized);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        $sanitized = preg_replace('/[^a-zA-Z0-9_.]/', '', $sanitized);

        return $sanitized ?? '';
    }

    /**
     * Cleans a class name for safe validation and storage.
     *
     * The class name is normalized before database use so class creation and AJAX validation
     * both apply the same character rules.
     *
     * @param mixed $className Raw class name from a form or API request.
     * @return string Sanitized class name safe for validation.
     * @throws \Throwable If an unexpected runtime error occurs while sanitizing the value.
     */
    public static function sanitizeClassName(mixed $className): string
    {
        if ($className === null) {
            return '';
        }

        $sanitized = trim((string) $className);
        $sanitized = strip_tags($sanitized);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        $sanitized = preg_replace('/[^a-zA-Z0-9_.]/', '', $sanitized);

        return $sanitized ?? '';
    }

    /**
     * Cleans an email address before validation.
     *
     * This limits email input to expected email characters so validation and uniqueness checks
     * receive a normalized value.
     *
     * @param mixed $email Raw email value from a form or API request.
     * @return string Sanitized email address.
     * @throws \Throwable If an unexpected runtime error occurs while sanitizing the value.
     */
    public static function sanitizeEmail(mixed $email): string
    {
        if ($email === null) {
            return '';
        }

        $sanitized = trim((string) $email);
        $sanitized = strip_tags($sanitized);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        $sanitized = filter_var($sanitized, FILTER_SANITIZE_EMAIL);
        $sanitized = preg_replace('/[^a-zA-Z0-9_.@]/', '', (string) $sanitized);

        return $sanitized ?? '';
    }

    /**
     * Trims a password without changing its internal characters.
     *
     * Passwords must not be aggressively filtered because special characters are valid and
     * needed for password strength; this method only removes accidental surrounding spaces.
     *
     * @param mixed $password Raw password value from a form request.
     * @return string Trimmed password value.
     * @throws \Throwable If an unexpected runtime error occurs while sanitizing the value.
     */
    public static function sanitizePassword(mixed $password): string
    {
        if ($password === null) {
            return '';
        }

        return trim((string) $password);
    }

    /**
     * Converts and cleans a generic string field.
     *
     * This method is used for text fields that do not have a specialized sanitizer, keeping
     * tags and unsafe HTML out of the application while still allowing ordinary text.
     *
     * @param mixed $input Raw scalar input value.
     * @return string Sanitized string value.
     * @throws \Throwable If an unexpected runtime error occurs while sanitizing the value.
     */
    public static function sanitizeString(mixed $input): string
    {
        if ($input === null) {
            return '';
        }

        $sanitized = trim((string) $input);
        $sanitized = strip_tags($sanitized);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');

        return $sanitized;
    }

    /**
     * Converts an identifier field into an integer.
     *
     * IDs are cast instead of trimmed so strict typing does not break when controllers pass
     * numeric session values into the sanitizer.
     *
     * @param mixed $id Raw identifier value.
     * @return int Integer identifier value.
     * @throws \Throwable If an unexpected runtime error occurs while sanitizing the value.
     */
    public static function sanitizeId(mixed $id): int
    {
        return (int) $id;
    }

    /**
     * Sanitizes an associative array using field-specific rules.
     *
     * Controllers use this to keep their request-processing code small while still applying
     * the correct sanitizer for usernames, emails, passwords, IDs, and ordinary strings.
     *
     * @param array $data Raw request data keyed by field name.
     * @param array $fieldTypes Map of field names to sanitizer type names.
     * @return array Sanitized data preserving the original keys.
     * @throws \Throwable If an unexpected runtime error occurs while sanitizing any field.
     */
    public static function sanitizeArray(array $data, array $fieldTypes = []): array
    {
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
                    case 'id':
                    case 'user_id':
                    case 'class_id':
                    case 'post_id':
                    case 'activity_id':
                    case 'student_id':
                    case 'submission_id':
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
