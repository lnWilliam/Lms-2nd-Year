<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

session_start();
require_once "../../vendor/autoload.php";

use App\Helpers\Database;
use App\Models\ClassModel;

if (!isset($_SESSION["user_data"])) {
    header('Location: logout.php');
    exit();
}

$user = $_SESSION['user_data'];

if (!isset($_GET['class_id'])) {
    die("No class selected.");
}

$class_id = (int) $_GET['class_id'];

$database = Database::getInstance();
$classModel = new ClassModel($database);

$class = $classModel->getClassForTeacher($class_id, $user['user_id']);

if (!$class) {
    die("Unauthorized access or class is already archived.");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {

    $archived = $classModel->deleteClass($class_id, $user['user_id']);

    if ($archived) {
        header("Location: home.php");
        exit();
    }

    $error = "Failed to archive class.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive Class</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">

        <div class="row justify-content-center">
            <div class="col-md-7">

                <div class="card shadow border-0 rounded-4">

                    <div class="card-body p-4">

                        <h2 class="fw-bold text-danger mb-3">
                            🗄 Archive Class
                        </h2>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <p class="fs-5">
                            Are you sure you want to archive this class?
                        </p>

                        <div class="alert alert-warning">
                            <strong><?= htmlspecialchars($class['class_name']) ?></strong>
                            will be archived. It will no longer appear in your class list, but the data will remain in the database.
                        </div>

                        <form method="POST" class="d-flex gap-2">

                            <button
                                type="submit"
                                name="confirm_delete"
                                class="btn btn-danger"
                                onclick="return confirm('Are you sure you want to archive this class?');">
                                Yes, Archive Class
                            </button>

                            <a href="class.php?class_id=<?= htmlspecialchars($class_id) ?>" class="btn btn-secondary">
                                Cancel
                            </a>

                        </form>

                    </div>

                </div>

            </div>
        </div>

    </div>

</body>

</html>