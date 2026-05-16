<?php
namespace App\Controllers;

use App\Helpers\Sanitizer;
use App\Helpers\Validator;

/**
 * Coordinates class-related validation before database actions are performed. This controller keeps class creation rules outside the page file so data is sanitized, validated, and checked before writes.
 *
 * @package App\Controllers
 * @author Charlo Marco
 * @since 2026-05-17
 */
class ClassController
{
    private $classModel;

    /**
     * Initializes the object with the dependencies it needs to perform its responsibility.
     *
     * @param mixed $classModel Class model used for class validation and persistence.
     * @return void No value is returned.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function __construct($classModel)
    {
        $this->classModel = $classModel;
    }

    /**
     * Sanitizes and validates class creation data before creating the class and teacher membership records.
     *
     * @param array $input Input data collected from a form or JSON request.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function validateAndProcessClass($input)
    {
        Validator::clearErrors();

        $user_id = $input['user_id'];

        $fieldTypes = [
            'user_id' => 'user_id',
            'class_name' => 'class_name',
            'class_desc' => 'class_desc',
            'class_code' => 'class_code'
        ];

        $sanitized = Sanitizer::sanitizeArray($input, $fieldTypes);

        $requiredFields = ['class_name', 'class_code'];

        if (!Validator::validateRequired($sanitized, $requiredFields)) {
            return [
                'success' => false,
                'errors' => Validator::getErrors()
            ];
        }

        if (!$this->classModel->checkClassCodeAvailability($sanitized['class_code'])) {
            return [
                'success' => false,
                'errors' => ['Class code already exists. Generate a new one.']
            ];
        }

        $success = $this->classModel->createClass($user_id, $sanitized);

        if ($success) {
            return [
                'success' => true
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to create class. Please try again.']
        ];
    }


    /**
     * Sanitizes and validates only a class name for asynchronous UI validation.
     *
     * @param mixed $className Class name value to validate.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function validateClassNameOnly($className)
    {
        Validator::clearErrors();

        // Sanitize username
        $sanitized = Sanitizer::sanitizeClassName($className);

        // Validate username
        if (!Validator::validateClassName($sanitized)) {
            return [
                'valid' => false,
                'class_name' => $sanitized,
                'errors' => Validator::getErrors()
            ];
        }

        return [
            'valid' => true,
            'class_name' => $sanitized,
            'errors' => []
        ];
    }
}

?>
