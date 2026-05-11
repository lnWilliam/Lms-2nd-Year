<?php
session_start();
require_once "../../vendor/autoload.php";

use App\Helpers\Database;
use App\Models\ClassModel;
use App\Utils\Upload;



// 🔐 Check login
if (!isset($_SESSION["user_data"])) {
    header('Location: ../../logout.php');
    exit();
}

$user = $_SESSION['user_data'];

// 🔌 DB
$database = Database::getInstance();
$classModel = new ClassModel($database);

// 📥 Get class_id
if (!isset($_GET['class_id'])) {
    die("No class selected.");
}

$class_id = $_GET['class_id'];
$upload = new Upload();

// 📚 Get user classes
$classes = $classModel->getClassesByUser($user['user_id']);

// 🔒 Find current class + role
$currentClass = null;

foreach ($classes as $c) {
    if ($c['class_id'] == $class_id) {
        $currentClass = $c;
        break;
    }
}
$students = $classModel->getStudents($class_id);

if (!$currentClass) {
    die("Unauthorized access.");
}

// 👨‍🏫 Role check
$isTeacher = ($currentClass['role'] === 'teacher');

// fallback desc
if (empty($currentClass['class_desc'])) {
    $currentClass['class_desc'] = "No description";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_announcement'])) {

    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';

    $post_id = $classModel->createAnnouncement(
        $class_id,
        $user['user_id'],
        $title,
        $description
    );

    // 🔥 HANDLE FILES
    if ($post_id && !empty($_FILES['files']['name'][0])) {

        foreach ($_FILES['files']['name'] as $i => $name) {

            $tmp = $_FILES['files']['tmp_name'][$i];

            $unique = time() . '_' . $name;
            $path = "documents/" . $unique;

            move_uploaded_file($tmp, $path);

            $classModel->addAttachment(
                $post_id,
                'other',
                $path,
                $name
            );
        }
    }

    header("Location: class.php?class_id=" . $class_id);
    exit();
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post_id'])) {

    $post_id = $_POST['delete_post_id'];

    $classModel->deletePost($post_id);

    header("Location: class.php?class_id=" . $class_id);
    exit;
}
if (isset($_GET['delete_post'])) {

    $post_id = (int) $_GET['delete_post'];

    // optional safety check (teacher OR owner)
    $post = $classModel->getPostOwner($post_id);

    if ($post && ($isTeacher || $post['postedBy'] == $user['user_id'])) {
        $classModel->deletePost($post_id);
    }

    header("Location: class.php?class_id=" . $class_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($currentClass['class_name']); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/upload.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <style>
        .sidebar a.active {
            background: grey;
            color: white;
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-light lms-topbar fixed-top px-3">
        <div class="container-fluid gap-2">
            <a class="navbar-brand d-flex align-items-center" href="home.php">
                <span class="brand-mark" aria-hidden="true">◆</span>
                MyLMS
            </a>
            <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#lmsTopNav" aria-controls="lmsTopNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="lmsTopNav">
                <form class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center ms-lg-auto gap-2 my-2 my-lg-0 lms-search" role="search">

                    <input class="form-control" type="search" placeholder="Search courses…" aria-label="Search courses">
                    <button class="btn btn-lms-primary" type="submit">Search</button>
                </form>
                <div class="dropdown ms-lg-3">
                    <a class="btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">👤<?php echo $user['first_name'] ?? ''; ?></a>
                    <ul class="dropdown-menu dropdown-menu-end">

                        <li><a class="dropdown-item" href="account_settings.php">Profile</a></li>
                        <li><a class="dropdown-item" href="#">Preferences</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="logout.php">Sign out</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <!-- SIDEBAR -->
    <aside class="sidebar lms-sidebar">
        <p class="sidebar-label">Learn</p>
        <a href="home.php"><span class="nav-ico" aria-hidden="true">⌂</span>Home</a>
        <a href="#"><span class="nav-ico" aria-hidden="true">▤</span> Calendar </a>
        <a href="#"><span class="nav-ico" aria-hidden="true">◎</span> Classes</a>
        <p class="sidebar-label">Classes</p>

        <?php foreach ($classes as $c): ?>
            <a href="class.php?class_id=<?php echo $c['class_id']; ?>"
                class="<?php echo ($c['class_id'] == $class_id) ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($c['class_name']); ?>
            </a>
        <?php endforeach; ?>
    </aside>

    <!-- MAIN -->
    <main class="main lms-main">

        <!-- HEADER -->
        <div class="hero lms-hero mb-3">
            <h2>
                <?php echo htmlspecialchars($currentClass['class_name']); ?>

                <!-- ROLE BADGE -->
                <span class="badge bg-<?php echo $isTeacher ? 'danger' : 'secondary'; ?>">
                    <?php echo ucfirst($currentClass['role']); ?>
                </span>
            </h2>

            <p><?php echo htmlspecialchars($currentClass['class_desc']); ?></p>
        </div>
        <div class='row shadow p-3 mb-3 bg-body-tertiary rounded mx-0'>
            <ul class="nav gap-5" >
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="#">Stream</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Classwork</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" id='getStudents'>Student</a>
                </li>
            </ul>
        </div>
        <div class="row">

            <!-- LEFT PANEL -->
            <div class="col-md-3">

                <!-- TEACHER PANEL -->
                <?php if ($isTeacher): ?>
                    <div class="card p-3 mb-3 teacher-panel">
                        <h5>⚙️ Teacher Panel</h5>

                        <a href="add_material.php?class_id=<?php echo $class_id; ?>" class="btn btn-light btn-sm mb-2">
                            ➕ Add Material
                        </a>

                        <a href="manage_students.php?class_id=<?php echo $class_id; ?>" class="btn btn-light btn-sm mb-2">
                            👥 Manage Students
                        </a>

                        <a href="edit_class.php?class_id=<?php echo $class_id; ?>" class="btn btn-light btn-sm mb-2">
                            ✏️ Edit Class
                        </a>

                        <a href="delete_class.php?class_id=<?php echo $class_id; ?>" class="btn btn-light btn-sm">
                            🗑 Delete Class
                        </a>

                    </div>
                    <div class="card p-3 mb-3 teacher-panel">
                        <p class='fw-bold'>Class Code</p>
                        <h2 class='fw-bold'><?php echo $currentClass['class_code']; ?><h2>
                    </div>
                <?php endif; ?>

                <!-- STUDENT PANEL -->
                <?php if (!$isTeacher): ?>
                    <div class="card p-3 mb-3">
                        <h5>📘 Class Info</h5>
                        <p>You are enrolled as a student.</p>
                    </div>
                <?php endif; ?>

            </div>

            <!-- RIGHT PANEL -->
            <div class="col-md-9">



                <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#announcement">
                    ✏️ New Announcement</button>

                <!-- Announcement Modal -->
                <form method="POST"
                    enctype="multipart/form-data"
                    class="upload-form">

                    <div class="modal fade"
                        id="announcement"
                        tabindex="-1">

                        <div class="modal-dialog modal-lg">

                            <div class="modal-content">

                                <div class="modal-header">
                                    <h1 class="modal-title fs-5">
                                        New Announcement
                                    </h1>

                                    <button type="button"
                                        class="btn-close"
                                        data-bs-dismiss="modal">
                                    </button>
                                </div>

                                <div class="modal-body">

                                    <!-- TITLE -->

                                    <div class="mb-3">
                                        <label class="form-label">
                                            Title
                                        </label>

                                        <input type="text"
                                            name="title"
                                            class="form-control"
                                            required>
                                    </div>

                                    <!-- DESCRIPTION -->

                                    <div class="mb-3">
                                        <label class="form-label">
                                            Description
                                        </label>

                                        <textarea
                                            name="description"
                                            class="form-control"
                                            rows="4"
                                            required></textarea>
                                    </div>

                                    <!-- FILE UPLOAD -->

                                    <div class="upload-section"
                                        id="uploadSection">

                                        <div class="file-input-area"
                                            id="dropZone">

                                            <div class="upload-icon">
                                                📁
                                            </div>

                                            <div>
                                                Click or drag & drop files
                                            </div>

                                            <div class="small text-muted">
                                                PDF, DOCX, JPG
                                                (Max 10MB)
                                            </div>

                                            <input type="file"
                                                name="files[]"
                                                multiple
                                                accept=".pdf,.docx,.jpg,.jpeg"
                                                id="fileInput">
                                        </div>

                                        <!-- FILE LIST -->

                                        <div id="fileList"
                                            class="file-list mt-3">
                                        </div>

                                    </div>

                                </div>

                                <div class="modal-footer">

                                    <button type="button"
                                        class="btn btn-secondary"
                                        data-bs-dismiss="modal">

                                        Close
                                    </button>

                                    <button type="submit"
                                        name="create_announcement"
                                        class="btn btn-primary">

                                        Post Announcement
                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>

                </form>

                <?php
                $posts = $classModel->getClassPosts($class_id);
                ?>

                <?php foreach ($posts as $post): ?>

                    <div class="card p-3 mb-3 shadow-sm">

                        <!-- HEADER -->

                        <div class="d-flex justify-content-between">

                            <div>

                                <strong>
                                    <?= htmlspecialchars($post['first_name']) ?>
                                    <?= htmlspecialchars($post['last_name']) ?>
                                </strong>

                                <span class="badge bg-success">
                                    <?= ucfirst($post['type']) ?>
                                </span>

                            </div>

                            <small class="text-muted">
                                <?= $post['created_at'] ?>
                            </small>

                        </div>

                        <hr>

                        <!-- TITLE -->

                        <?php if (!empty($post['title'])): ?>

                            <h5>
                                <?= htmlspecialchars($post['title']) ?>
                            </h5>

                        <?php endif; ?>

                        <!-- DESCRIPTION -->

                        <p>
                            <?= nl2br(htmlspecialchars($post['description'])) ?>
                        </p>

                        <!-- DUE DATE -->

                        <?php if (!empty($post['due_date'])): ?>

                            <p class="text-danger">

                                <strong>Due:</strong>

                                <?= htmlspecialchars($post['due_date']) ?>

                            </p>

                        <?php endif; ?>

                        <!-- ATTACHMENTS -->

                        <?php if (!empty($post['file_paths'])): ?>

                            <?php
                            $filePaths = explode('||', $post['file_paths']);
                            $fileNames = explode('||', $post['file_names']);
                            $fileTypes = explode('||', $post['attachment_types']);
                            ?>

                            <div class="mt-3">
                                <strong>Attachments:</strong>

                                <ul class="list-group mt-2">

                                    <?php foreach ($filePaths as $i => $path): ?>

                                        <?php
                                        $name = $fileNames[$i] ?? 'file';
                                        $type = $fileTypes[$i] ?? 'other';
                                        $url = "/NewSite/" . $path;
                                        ?>

                                        <li class="list-group-item">

                                            <?php if ($type === 'jpg' || $type === 'jpeg' || $type === 'png'): ?>
                                                <img src="<?= $url ?>" style="max-width:200px;">
                                            <?php endif; ?>

                                            <a href="<?= $url ?>" target="_blank">
                                                📎 <?= htmlspecialchars($name) ?>
                                            </a>

                                        </li>

                                    <?php endforeach; ?>

                                </ul>

                            </div>


                        <?php endif; ?>
                        <?php if ($isTeacher || $post['postedBy'] == $user['user_id']): ?>
                            <a href="class.php?class_id=<?= $class_id ?>&delete_post=<?= $post['post_id'] ?>"
                                class="btn btn-danger btn-sm"
                                onclick="return confirm('Delete this post?')">
                                🗑 Delete
                            </a>
                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </main>
    <script src="../js/upload.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src='../js/animate.js'></script>

</body>

</html>