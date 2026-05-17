<?php
declare(strict_types=1); // ADDED: PHP strict types must be the first PHP statement.


namespace App\Controllers;

use App\Helpers\Sanitizer;
use App\Helpers\Validator;
use App\Models\ClassModel;

class ClassController
{
    private ClassModel $classModel;

    public function __construct(ClassModel $classModel)
    {
        $this->classModel = $classModel;
    }

    public function validateAndProcessClass(array $input): array
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


    public function validateClassNameOnly(string $className): array
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