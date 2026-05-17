<?php
declare(strict_types=1);
/**
 * Login and registration page. Handles form submission through UserController.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

session_start();
require_once "../../vendor/autoload.php";

use App\Controllers\UserController;
use App\Helpers\Database;
use App\Models\UserModel;

if (isset($_SESSION['logged'])) {
    header('Location: home.php');
    exit;
}

$database = Database::getInstance();
$model = new UserModel($database);
$controller = new UserController($model);

$message = '';
$messageType = '';
$result = null;

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (isset($_POST['signup'])) {
        $data = [
            "username" => $_POST["username"] ?? '',
            "email" => $_POST["email"] ?? '',
            "password" => $_POST["password"] ?? '',
            "confirm_pass" => $_POST["confirm_pass"] ?? '',
            "first_name" => $_POST["first_name"] ?? '',
            "last_name" => $_POST["last_name"] ?? ''
        ];

        $result = $controller->validateAndProcessRegistration($data);
    }

    if (isset($_POST['login'])) {
        $data = [
            "user_username" => $_POST["user_username"] ?? '',
            "user_password" => $_POST["user_password"] ?? ''
        ];

        $result = $controller->validateAndProcessLogin($data);
    }

    if ($result !== null) {
        if (isset($result['logged_in'])) {
            $_SESSION['success'] = 'User Logged in';
            $_SESSION['user_data'] = $result['data'];
            $_SESSION['logged'] = true;
            header('Location: home.php');
            exit;
        }

        if (!empty($result['success'])) {
            $_SESSION['success'] = "User registered successfully!";
            $_SESSION['user_data'] = $result['data'];
        } else {
            $_SESSION['error'] = implode('<br>', $result['errors']);
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_SESSION['success'])) {
    $message = $_SESSION['success'];
    $messageType = 'success';
    unset($_SESSION['success']);
} elseif (isset($_SESSION['error'])) {
    $message = $_SESSION['error'];
    $messageType = 'error';
    unset($_SESSION['error']);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduRift Login</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<style>
    body{display: flex; flex-direction: column; align-items: center; justify-content: center; }
</style>
<body>

    <?php if ($message): ?>
        <div class="message <?php echo htmlspecialchars($messageType); ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <input type="checkbox" id="toggle" hidden>

        <!-- LOGIN -->
        <div class="panel leftPanel">
            <h2>Login</h2>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="loginForm">
                <div class="input-box">
                    <input type="text" name="user_username" id="user_username" required placeholder=" ">
                    <label for="user_username">Username</label>
                </div>

                <div class="input-box password-box">
                    <input type="password" name="user_password" id="login_password" required placeholder=" ">
                    <label for="login_password">Password</label>
                    <button type="button" class="toggle-password" data-target="login_password" aria-label="Show password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <button type="submit" name="login">Login</button>
                <label for="toggle" class="switch">Create account</label>
            </form>
        </div>

        <!-- CENTER -->
        <div class="panel midPanel">
            <h1>Welcome to EduRift</h1>
            <p>Access your account or create a new one</p>

            <div class="social">
                <i class="fab fa-google"></i>
                <i class="fab fa-facebook-f"></i>
                <i class="fab fa-github"></i>
            </div>
        </div>

        <!-- SIGNUP -->
        <div class="panel rightPanel">
            <h2>Sign Up</h2>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="registrationForm">
                <div class="input-box">
                    <input type="text" name="username" id="username" required placeholder=" " pattern="^[a-zA-Z][a-zA-Z0-9_.]*$" title="Username must start with a letter and can only contain letters, numbers, underscores and dots">
                    <label for="username">Username</label>
                    <div id="usernameStatus" class="username-status"></div>
                </div>

                <div class="input-box">
                    <input type="text" name="first_name" id="firstName" required placeholder=" ">
                    <label for="firstName">First Name</label>
                </div>

                <div class="input-box">
                    <input type="text" name="last_name" id="lastName" required placeholder=" ">
                    <label for="lastName">Last Name</label>
                </div>

                <div class="input-box">
                    <input type="email" name="email" id="email" required placeholder=" ">
                    <label for="email">Email</label>
                    <div id="emailStatus" class="email-status"></div>
                </div>

                <div class="input-box password-box">
                    <input type="password" name="password" id="password" required placeholder=" ">
                    <label for="password">Password</label>
                    <button type="button" class="toggle-password" data-target="password" aria-label="Show password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <div class="input-box password-box">
                    <input type="password" name="confirm_pass" id="confirm_pass" required placeholder=" ">
                    <label for="confirm_pass">Confirm Password</label>
                    <button type="button" class="toggle-password" data-target="confirm_pass" aria-label="Show confirm password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                    <p class="confirm_error unavailable"></p>
                </div>

                <button type="submit" name="signup">Register</button>
                <label for="toggle" class="switch">Already have account?</label>
            </form>
        </div>
    </div>
    <script src="../js/user.js"></script>
</body>

</html>
