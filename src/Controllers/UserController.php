<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Helpers\Sanitizer;
use App\Helpers\Validator;

/**
 * Coordinates user registration, login, and field validation workflows. This controller separates authentication rules from page rendering so account data is prepared before model calls.
 *
 * @package App\Controllers
 * @author Charlo Marco
 * @since 2026-05-17
 */
class UserController
{
    private $user;

    /**
     * Initializes the object with the dependencies it needs to perform its responsibility.
     *
     * @param mixed $user User model dependency used for account lookup and persistence.
     * @return void No value is returned.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Validate and sanitize user input for registration
     */
    /**
     * Sanitizes login input and verifies credentials so only valid users are allowed into the system.
     *
     * @param array $input Input data collected from a form or JSON request.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function validateAndProcessLogin($input)
    {
        Validator::clearErrors();
        $fieldTypes = [
            'user_username' => 'username',
            'user_password' => 'password'
        ];
        $sanitized = Sanitizer::sanitizeArray($input, $fieldTypes);

        $user = $this->user->getUserByUsername($sanitized['user_username']);

        if (!$user) {
            return [
                'success' => false,
                'errors' => ['Invalid username or password']
            ];
        }

        if (password_verify($sanitized['user_password'], $user['password'])) {
            unset($user['password']);

            return [
                'success' => true,
                'logged_in' => true,
                'data' => $user
            ];
        }
        else{
            return[
                'success' => false,
                'errors' => ['Invalid username or password']
            ];
        }
    }

    
    /**
     * Sanitizes, validates, hashes, and stores registration data so new users are created safely.
     *
     * @param array $input Input data collected from a form or JSON request.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function validateAndProcessRegistration($input)
    {
        Validator::clearErrors();

        // Define field types for sanitization
        $fieldTypes = [
            'username' => 'username',
            'email' => 'email',
            'password' => 'password',
            'confirm_pass' => 'password',
            'first_name' => 'first_name',
            'last_name' => 'last_name'
        ];

        // Sanitize all inputs
        $sanitized = Sanitizer::sanitizeArray($input, $fieldTypes);

        // Validate required fields
        $requiredFields = ['username', 'email', 'password', 'confirm_pass'];
        if (!Validator::validateRequired($sanitized, $requiredFields)) {
            return [
                'success' => false,
                'errors' => Validator::getErrors()
            ];
        }

        // Validate username
        if (!Validator::validateUsername($sanitized['username'])) {
            return [
                'success' => false,
                'errors' => Validator::getErrors()
            ];
        }

        // Validate email
        if (!Validator::validateEmail($sanitized['email'])) {
            return [
                'success' => false,
                'errors' => Validator::getErrors()
            ];
        }

        // Validate password
        if (!Validator::validatePassword($sanitized['password'], $sanitized['confirm_pass'])) {
            return [
                'success' => false,
                'errors' => Validator::getErrors()
            ];
        }
        // Check if username already exists
        if (!$this->user->checkUsernameAvailability($sanitized['username'])) {
            return [
                'success' => false,
                'errors' => ['Username is already taken']
            ];
        }

        // Hash password before storing
        $sanitized['password'] = password_hash($sanitized['password'], PASSWORD_DEFAULT);

        // Remove confirm_pass from data to be inserted
        unset($sanitized['confirm_pass']);

        // Insert user
        $userId = $this->user->insert($sanitized);

        if ($userId) {
            return [
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'username' => $sanitized['username'],
                    'email' => $sanitized['email']
                ]
            ];
        } else {
            return [
                'success' => false,
                'errors' => ['Failed to create user. Please try again.']
            ];
        }
    }

    /**
     * Validate username only (for API)
     */
    /**
     * Sanitizes and validates only a username for asynchronous registration checks.
     *
     * @param mixed $username Username value to check or validate.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function validateUsernameOnly($username)
    {
        Validator::clearErrors();

        // Sanitize username
        $sanitized = Sanitizer::sanitizeUsername($username);

        // Validate username
        if (!Validator::validateUsername($sanitized)) {
            return [
                'valid' => false,
                'username' => $sanitized,
                'errors' => Validator::getErrors()
            ];
        }

        return [
            'valid' => true,
            'username' => $sanitized,
            'errors' => []
        ];
    }
    

    /**
     * Sanitizes and validates only an email address for asynchronous registration checks.
     *
     * @param mixed $email Email address value to check or validate.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function validateEmailOnly($email)
    {
        Validator::clearErrors();

        // Sanitize username
        $sanitized = Sanitizer::sanitizeEmail($email);

        // Validate username
        if (!Validator::validateEmail($sanitized)) {
            return [
                'valid' => false,
                'email' => $sanitized,
                'errors' => Validator::getErrors()
            ];
        }

        return [
            'valid' => true,
            'email' => $sanitized,
            'errors' => []
        ];
    }
}
