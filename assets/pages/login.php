<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
session_start();
require_once "../../vendor/autoload.php";

if (isset($_SESSION['logged'])) {
    header('Location: home.php');
}

use App\Controllers\UserController;
use App\Helpers\Database;
use App\Models\UserModel;

$database = Database::getInstance();
$model = new UserModel($database);
$controller = new UserController($model);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (isset($_POST['signup'])) {
        $data = [
            "username" => $_POST["username"],
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
            "user_username" => $_POST["user_username"],
            "user_password" => $_POST["user_password"] ?? ''
        ];
        $result = $controller->validateAndProcessLogin($data);
     
    }
    if (isset($result['logged_in'])) {
        $_SESSION['success'] = 'User Logged in';
        $_SESSION['user_data'] = $result['data'];
        $_SESSION['logged'] = true;
        header('Location: home.php');
        exit;
    } elseif ($result['success']) {
        $_SESSION['success'] = "User registered successfully!";
        $_SESSION['user_data'] = $result['data'];
    } else {
        $_SESSION['error'] = implode('<br>', $result['errors']);
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
// Get session messages
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
    <title>Document</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

</head>
<style>
    body {
        flex-direction: column;
    }

    .message {
        width: 900px;
        border: 1px solid black;
        border-radius: 10px;
        height: fit-content;
        text-align: center;
    }

    .error {
        background-color: red;
        color: white;
    }

    .success {
        background-color: green;
        color: white
    }
</style>

<body>
    <button><a href="home.html">Home</a></button>
    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    <div class="container">
        <input type="checkbox" id="toggle" hidden>

        <!-- LOGIN -->
        <div class="panel leftPanel">
            <h2>Login</h2>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id='loginForm'>
                <div class="input-box">
                    <input type="text" name="user_username" required>
                    <label>Username</label>
                </div>

                <div class="input-box">
                    <input type="password" name="user_password" required>
                    <label>Password</label>
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
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="registrationForm">
                <div class="input-box">
                    <input type="text"
                        name="username"
                        id="username"
                        required
                        pattern="^[a-zA-Z][a-zA-Z0-9_.]*$"
                        title="Username must start with a letter and can only contain letters, numbers, underscores and dots">
                    <label>Username</label>
                    <div id="usernameStatus" class="username-status"></div>

                </div>

                <div class="input-box">
                    <input type="text" name="first_name" required>
                    <label>First Name</label>
                </div>

                <div class="input-box">
                    <input type="text" name="last_name" required>
                    <label>Last Name</label>
                </div>

                <div class="input-box">
                    <input type="email"
                        name="email"
                        id="email"
                        required>
                    <label>Email</label>
                    <div id="emailStatus" class="username-status"></div>
                </div>

                <div class="input-box">
                    <input type="password" name="password" id='password' required>
                    <label>Password</label>
                </div>

                <div class="input-box">
                    <input type="password" name="confirm_pass" id="confirm_pass" required>
                    <label>Confirm Password</label>
                    <p class="confirm_error unavailable"></p>
                </div>

                <button type="submit" name="signup">Register</button>
                <label for="toggle" class="switch">Already have account?</label>
            </form>
        </div>

    </div>
    <script src='../js/user.js'></script>
</body>

</html>