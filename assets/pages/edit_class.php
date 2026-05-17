<?php
declare(strict_types=1); // ADDED: PHP strict types must be the first PHP statement.

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
    die("Unauthorized access or class is archived.");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $class_name = trim($_POST['class_name'] ?? '');
    $class_desc = trim($_POST['class_desc'] ?? '');
    $class_code = trim($_POST['class_code'] ?? '');

    if ($class_name === '' || $class_code === '') {
        $error = "Class name and class code are required.";
    } else {

        $updated = $classModel->updateClass(
            $class_id,
            $user['user_id'],
            [
                'class_name' => $class_name,
                'class_desc' => $class_desc,
                'class_code' => $class_code
            ]
        );

        if (!empty($updated['success'])) {
            header("Location: class.php?class_id=" . $class_id);
            exit();
        }

        $error = $updated['message'] ?? "Failed to update class.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Class</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">

        <div class="row justify-content-center">
            <div class="col-md-7">

                <div class="card shadow border-0 rounded-4">

                    <div class="card-body p-4">

                        <h2 class="fw-bold mb-3">
                            ✏️ Edit Class
                        </h2>

                        <p class="text-muted mb-4">
                            Update your class name, description, or class code.
                        </p>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">

                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    Class Name
                                </label>

                                <input
                                    type="text"
                                    name="class_name"
                                    class="form-control"
                                    value="<?= htmlspecialchars($class['class_name']) ?>"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    Class Description
                                </label>

                                <textarea
                                    name="class_desc"
                                    class="form-control"
                                    rows="5"><?= htmlspecialchars($class['class_desc'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    Class Code
                                </label>

                                <input
                                    type="text"
                                    name="class_code"
                                    class="form-control"
                                    value="<?= htmlspecialchars($class['class_code']) ?>"
                                    required>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    Save Changes
                                </button>

                                <a href="class.php?class_id=<?= htmlspecialchars((string) $class_id) ?>" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </div>

                        </form>

                    </div>

                </div>

            </div>
        </div>

    </div>

</body>

</html>