<?php
declare(strict_types=1); // ADDED: PHP strict types must be the first PHP statement.

namespace App\APIs;

require_once "../../vendor/autoload.php";

use App\Helpers\Database;
use App\Models\UserModel;
use App\Models\ClassModel;
use App\Controllers\UserController;
use App\Controllers\ClassController;


class UserAPI {
    private $userController;
    private $userModel;
    private $classController;
    private $classModel;

    public function __construct() {
        $database = Database::getInstance();
        $this->userModel = new UserModel($database);
        $this->classModel = new ClassModel($database);
        $this->userController = new UserController($this->userModel);
        $this->classController = new ClassController($this->classModel);
    }

    public function handleRequest() {
        header('Content-Type: application/json');
        
        // Handle CORS if needed
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        // Get the action from the request
        
            $data = json_decode(file_get_contents('php://input'), true);
            $action = $data["action"] ?? "";


            switch ($action) {
                case 'check-username':
                    $this->checkUsername();
                    break;
                case 'check-email':
                    $this->checkEmail();
                    break;
                case 'check-className':
                    $this->checkClassName();
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Action not found']);
                

            }
    }
     private function checkClassName() {
        $input = json_decode(file_get_contents('php://input'), true);
        $className = $input['class_name'] ?? '';
        
        // Use controller to validate
        $validationResult = $this->classController->validateClassNameOnly($className);
        
        if (!$validationResult['valid']) {
            echo json_encode([
                'valid' => false,
                'errors' => $validationResult['errors']
            ]);
            return;
        }

        echo json_encode([
            'valid' => true,
            'class_name' => $validationResult['class_name'],
            'message' => 'Class Name is Valid' 
        ]);
    }

    private function checkUsername() {
        $input = json_decode(file_get_contents('php://input'), true);
        $username = $input['username'] ?? '';
        
        // Use controller to validate
        $validationResult = $this->userController->validateUsernameOnly($username);
        
        if (!$validationResult['valid']) {
            echo json_encode([
                'available' => false,
                'valid' => false,
                'errors' => $validationResult['errors']
            ]);
            return;
        }

        // Check availability in database
        $isAvailable = $this->userModel->checkUsernameAvailability($validationResult['username']);
        
        echo json_encode([
            'available' => $isAvailable,
            'valid' => true,
            'username' => $validationResult['username'],
            'message' => $isAvailable ? 'Username is available' : 'Username is already taken'
        ]);
    }

    private function checkEmail() {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        
        // Use controller to validate
        $validationResult = $this->userController->validateEmailOnly($email);
        
        if (!$validationResult['valid']) {
            echo json_encode([
                'available' => false,
                'valid' => false,
                'errors' => $validationResult['errors']
            ]);
            return;
        }

        // Check availability in database
        $isAvailable = $this->userModel->checkEmailAvailability($validationResult['email']);
        
        echo json_encode([
            'available' => $isAvailable,
            'valid' => true,
            'email' => $validationResult['email'],
            'message' => $isAvailable ? 'Email is available' : 'Email is already taken'
        ]);
    }
}

// Handle the request
$api = new UserAPI();
$api->handleRequest();

