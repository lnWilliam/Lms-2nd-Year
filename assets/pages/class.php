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
$user_id = (int) ($user['user_id'] ?? 0);

// 🔌 DB
$database = Database::getInstance();
$classModel = new ClassModel($database);

// 📥 Get class_id
if (!isset($_GET['class_id'])) {
    die("No class selected.");
}

$class_id = (int) $_GET['class_id'];
$upload = new Upload();

// 📚 Get user classes
$classes = $classModel->getClassesByUser($user_id);

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

// ✏️ Edit announcement / assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_post'])) {

    $post_id = (int) ($_POST['post_id'] ?? 0);
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? null;
    $points = $_POST['points'] ?? null;

    $post = $classModel->getPostOwner($post_id);

    if ($post) {

        $canEdit = false;

        // Teacher can edit announcement or assignment they made
        if (
            $isTeacher &&
            $post['postedBy'] == $user_id &&
            in_array($post['type'], ['announcement', 'assignment'])
        ) {
            $canEdit = true;
        }

        // Student can edit only their own announcement
        if (
            !$isTeacher &&
            $post['type'] === 'announcement' &&
            $post['postedBy'] == $user_id
        ) {
            $canEdit = true;
        }

        if ($canEdit) {
            $classModel->updatePost(
                $post_id,
                $title,
                $description,
                $due_date,
                $points
            );
        }
    }

    header("Location: class.php?class_id=" . $class_id);
    exit();
}

// 🧑‍🎓 Remove student from class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_student_id'])) {

    if ($isTeacher) {
        $student_id = (int) $_POST['remove_student_id'];
        $classModel->removeStudentFromClass($class_id, $student_id);
    }

    header("Location: class.php?class_id=" . $class_id);
    exit();
}

// 📢 Create announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_announcement'])) {

    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';

    $post_id = $classModel->createAnnouncement(
        $class_id,
        $user_id,
        $title,
        $description
    );

    // 🔥 HANDLE FILES
    if ($post_id && !empty($_FILES['files']['name'][0])) {

        foreach ($_FILES['files']['name'] as $i => $name) {

            $tmp = $_FILES['files']['tmp_name'][$i];

            if (empty($tmp)) {
                continue;
            }

            $unique = time() . '_' . $name;
            $path = "documents/" . $unique;

            move_uploaded_file($tmp, $path);

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            $classModel->addAttachment(
                $post_id,
                $extension,
                $path,
                $name
            );
        }
    }

    header("Location: class.php?class_id=" . $class_id);
    exit();
}

// 🗑 Delete post using POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post_id'])) {

    $post_id = (int) $_POST['delete_post_id']; // EDITED: $_POST values are strings, so cast to int for strict types.
    $post = $classModel->getPostOwner($post_id);

    if ($post && ($isTeacher || $post['postedBy'] == $user_id)) {
        $classModel->deletePost($post_id);
    }

    header("Location: class.php?class_id=" . $class_id);
    exit;
}

// 🗑 Delete post using GET
if (isset($_GET['delete_post'])) {

    $post_id = (int) $_GET['delete_post'];

    $post = $classModel->getPostOwner($post_id);

    if ($post && ($isTeacher || $post['postedBy'] == $user_id)) {
        $classModel->deletePost($post_id);
    }

    header("Location: class.php?class_id=" . $class_id);
    exit();
}

// 📝 Create assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {

    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? null;
    $points = $_POST['points'] ?? null;

    $post_id = $classModel->createAssignment(
        $class_id,
        $user_id,
        $title,
        $description,
        $due_date,
        $points
    );

    // 🔥 HANDLE FILES
    if ($post_id && !empty($_FILES['files']['name'][0])) {

        foreach ($_FILES['files']['name'] as $i => $name) {

            $tmp = $_FILES['files']['tmp_name'][$i];

            if (empty($tmp)) {
                continue;
            }

            $unique = time() . '_' . $name;
            $path = "documents/" . $unique;

            move_uploaded_file($tmp, $path);

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            $classModel->addAttachment(
                $post_id,
                $extension,
                $path,
                $name
            );
        }
    }

    header("Location: class.php?class_id=" . $class_id);
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">

    <title><?php echo htmlspecialchars($currentClass['class_name']); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/class.css">
    <link rel="stylesheet" href="../css/upload.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-light lms-topbar fixed-top px-3">
        <div class="container-fluid gap-2 align-items-center lms-topbar-inner">

            <button class="navbar-toggler d-lg-none lms-sidebar-toggler flex-shrink-0" type="button" aria-controls="Primary navigation" aria-expanded="false" aria-label="Open side navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <a class="navbar-brand d-flex align-items-center flex-shrink-0" href="home.php">
                <span class="brand-mark" aria-hidden="true">◆</span>
                MyLMS
            </a>

            <form class="lms-mobile-toolbar-search d-flex d-lg-none flex-grow-1 min-w-0" role="search" action="#" method="get">
                <label class="visually-hidden" for="lmsMobileSearchClass">Search courses</label>
                <input id="lmsMobileSearchClass" class="form-control" type="search" name="q" placeholder="Search courses…" aria-label="Search courses" autocomplete="off">
                <button class="btn btn-lms-primary lms-search-submit-btn" type="submit" aria-label="Search">⌕</button>
            </form>

            <button class="btn btn-lms-ghost d-lg-none flex-shrink-0 px-2 lms-mobile-more-btn" type="button" data-bs-toggle="collapse" data-bs-target="#lmsMobileNavMore" aria-controls="lmsMobileNavMore" aria-expanded="false" aria-label="Account menu">⋯</button>

            <div class="collapse lms-mobile-nav-drawer d-lg-none w-100" id="lmsMobileNavMore">
                <div class="lms-mobile-nav-drawer-inner d-flex flex-column gap-2">

                    <div class="dropdown">
                        <a class="btn dropdown-toggle w-100 text-start" href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="true" aria-expanded="false">
                            👤 <?php echo htmlspecialchars($user['first_name'] ?? ''); ?>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end w-100">
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

            <div class="d-none d-lg-flex align-items-center ms-lg-auto gap-2 flex-nowrap">
                <form class="d-flex align-items-center gap-2 lms-search" role="search" action="#" method="get">
                    <input class="form-control" type="search" name="q" placeholder="Search courses…" aria-label="Search courses">
                    <button class="btn btn-lms-primary" type="submit">Search</button>
                </form>

                <div class="dropdown">
                    <a class="btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        👤 <?php echo htmlspecialchars($user['first_name'] ?? ''); ?>
                    </a>

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

        <a href="home.php">
            <span class="nav-ico" aria-hidden="true">⌂</span>
            Home
        </a>




        <a href="#">
            <span class="nav-ico" aria-hidden="true">◎</span>
            Classes
        </a>

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

                <span class="badge bg-<?php echo $isTeacher ? 'danger' : 'secondary'; ?>">
                    <?php echo ucfirst($currentClass['role']); ?>
                </span>
            </h2>

            <p><?php echo htmlspecialchars($currentClass['class_desc']); ?></p>
        </div>

        <div class='row shadow p-3 mb-3 bg-body-tertiary rounded mx-0'>
            <ul class="nav gap-5">
                <li class="nav-item">
                    <button class="nav-link active border-0 bg-transparent" type="button" id="streamBtn">
                        Stream
                    </button>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="classwork.php?class_id=<?= htmlspecialchars($class_id) ?>">
                        Classwork
                    </a>
                </li>
                <li class="nav-item">
                    <button class="nav-link border-0 bg-transparent" type="button" id="getStudents">
                        Student
                    </button>
                </li>
            </ul>
        </div>

        <div class="row">

            <!-- LEFT PANEL -->
            <div class="col-md-3">

                <?php if ($isTeacher): ?>

                    <div class="card p-3 mb-3 teacher-panel">
                        <h5>⚙️ Teacher Panel</h5>

                        <a href="edit_class.php?class_id=<?= htmlspecialchars((string) $class_id) ?>" class="btn btn-light btn-sm mb-2">
                            ✏️ Edit Class
                        </a>

                        <a href="delete_class.php?class_id=<?= htmlspecialchars((string) $class_id) ?>" class="btn btn-light btn-sm">
                            🗑 Delete Class
                        </a>
                    </div>

                    <div class="card p-3 mb-3 teacher-panel">
                        <p class='fw-bold'>Class Code</p>
                        <h2 class='fw-bold'><?php echo htmlspecialchars($currentClass['class_code']); ?></h2>
                    </div>

                <?php endif; ?>

                <?php if (!$isTeacher): ?>
                    <div class="card p-3 mb-3">
                        <h5>📘 Class Info</h5>
                        <p>You are enrolled as a student.</p>
                    </div>
                <?php endif; ?>

            </div>

            <!-- RIGHT PANEL -->
            <div class="col-md-9">

                <!-- STREAM SECTION -->
                <div id="streamSection">

                    <button class="btn btn-success mb-3" type="button" data-bs-toggle="modal" data-bs-target="#announcement">
                        ✏️ New Announcement
                    </button>

                    <?php if ($isTeacher): ?>
                        <button class="btn btn-primary mb-3" type="button" data-bs-toggle="modal" data-bs-target="#assignment">
                            ➕ New Assignment
                        </button>
                    <?php endif; ?>

                    <?php
                    $posts = $classModel->getClassPosts($class_id);
                    ?>

                    <?php foreach ($posts as $post): ?>

                        <?php
                        $postLink = $post['type'] . "s.php?post_id=" . $post['post_id'];

                        $canEditPost = false;

                        // Teacher can edit only announcement/assignment they made
                        if (
                            $isTeacher &&
                            $post['postedBy'] == $user_id &&
                            in_array($post['type'], ['announcement', 'assignment'])
                        ) {
                            $canEditPost = true;
                        }

                        // Student can edit only their own announcement
                        if (
                            !$isTeacher &&
                            $post['type'] === 'announcement' &&
                            $post['postedBy'] == $user_id
                        ) {
                            $canEditPost = true;
                        }

                        $canDeletePost = ($isTeacher || $post['postedBy'] == $user_id);
                        ?>

                        <div class="card p-3 mb-3 shadow-sm">

                            <!-- HEADER -->
                            <div class="d-flex justify-content-between">

                                <div>
                                    <strong>
                                        <?= htmlspecialchars($post['first_name']) ?>
                                        <?= htmlspecialchars($post['last_name']) ?>
                                    </strong>

                                    <span class="badge bg-success">
                                        <?= ucfirst(htmlspecialchars($post['type'])) ?>
                                    </span>
                                </div>

                                <small class="text-muted">
                                    <?= htmlspecialchars($post['created_at']) ?>
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

                            <!-- POINTS -->
                            <?php if ($post['type'] === 'assignment' && isset($post['max_score'])): ?>
                                <p class="text-primary">
                                    <strong>Points:</strong>
                                    <?= htmlspecialchars($post['max_score']) ?>
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

                                                <?php if ($type === 'jpg' || $type === 'jpeg' || $type === 'png' || $type === 'image'): ?>
                                                    <img src="<?= htmlspecialchars($url) ?>" style="max-width:200px;">
                                                <?php endif; ?>

                                                <a href="<?= htmlspecialchars($url) ?>" target="_blank">
                                                    📎 <?= htmlspecialchars($name) ?>
                                                </a>

                                            </li>

                                        <?php endforeach; ?>

                                    </ul>
                                </div>

                            <?php endif; ?>

                            <!-- ACTION BUTTONS -->
                            <div class="d-flex gap-2 flex-wrap mt-3">

                                <a href="<?= htmlspecialchars($postLink) ?>" class="btn btn-outline-primary btn-sm">
                                    View
                                </a>

                                <?php if ($canEditPost): ?>
                                    <button
                                        type="button"
                                        class="btn-edit-post"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editPostModal<?= htmlspecialchars($post['post_id']) ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        Edit
                                    </button>
                                <?php endif; ?>

                                <?php if ($canDeletePost): ?>
                                    <a href="class.php?class_id=<?= htmlspecialchars((string) $class_id) ?>&delete_post=<?= htmlspecialchars($post['post_id']) ?>"
                                        class="delete-post-btn"
                                        onclick="return confirm('Delete this post?')">
                                        <i class="fa-solid fa-trash"></i>
                                        Delete
                                    </a>
                                <?php endif; ?>

                            </div>

                        </div>

                        <!-- EDIT MODAL -->
                        <?php if ($canEditPost): ?>
                            <div class="modal fade edit-post-modal" id="editPostModal<?= htmlspecialchars($post['post_id']) ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">

                                    <form method="POST" class="modal-content">

                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5">
                                                Edit <?= ucfirst(htmlspecialchars($post['type'])) ?>
                                            </h1>

                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">

                                            <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['post_id']) ?>">

                                            <div class="mb-3">
                                                <label class="form-label">
                                                    Title
                                                </label>

                                                <input
                                                    type="text"
                                                    name="title"
                                                    class="form-control"
                                                    value="<?= htmlspecialchars($post['title']) ?>"
                                                    required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">
                                                    Description
                                                </label>

                                                <textarea
                                                    name="description"
                                                    class="form-control"
                                                    rows="5"
                                                    required><?= htmlspecialchars($post['description']) ?></textarea>
                                            </div>

                                            <?php if ($post['type'] === 'assignment'): ?>

                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        Due Date
                                                    </label>

                                                    <input
                                                        type="datetime-local"
                                                        name="due_date"
                                                        class="form-control"
                                                        value="<?= !empty($post['due_date']) ? date('Y-m-d\TH:i', strtotime($post['due_date'])) : '' ?>">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        Points
                                                    </label>

                                                    <input
                                                        type="number"
                                                        name="points"
                                                        class="form-control"
                                                        min="0"
                                                        max="100"
                                                        value="<?= htmlspecialchars($post['max_score'] ?? 100) ?>">
                                                </div>

                                            <?php endif; ?>

                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                Cancel
                                            </button>

                                            <button type="submit" name="edit_post" class="btn btn-primary">
                                                Save Changes
                                            </button>
                                        </div>

                                    </form>

                                </div>
                            </div>
                        <?php endif; ?>

                    <?php endforeach; ?>

                </div>

                <!-- STUDENT SECTION -->
                <div id="studentSection" style="display:none;">

                    <div class="card p-3 shadow-sm mb-3 flex-column flex-md-row align-items-md-center gap-3">
                        <strong>
                            <?php
                            $teacher = $classModel->getTeacher($class_id);
                            echo htmlspecialchars(ucfirst($teacher['first_name'] ?? '')) . ' ' . htmlspecialchars(ucfirst($teacher['last_name'] ?? ''));
                            ?>
                        </strong>

                        <span class="badge bg-success">
                            Teacher
                        </span>
                    </div>

                    <div class="card p-3 shadow-sm">

                        <h4 class="mb-4">Students</h4>

                        <?php if (count($students) > 0): ?>

                            <?php foreach ($students as $student): ?>

                                <div class="d-flex justify-content-between align-items-center border-bottom py-3">

                                    <div>
                                        <strong>
                                            <?= htmlspecialchars($student['first_name'] ?? '') ?>
                                            <?= htmlspecialchars($student['last_name'] ?? '') ?>
                                        </strong>

                                        <?php if (!empty($student['email'])): ?>
                                            <div class="text-muted small">
                                                <?= htmlspecialchars($student['email']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($isTeacher): ?>
                                        <form method="POST"
                                            onsubmit="return confirm('Remove this student from the class?');">

                                            <input type="hidden"
                                                name="remove_student_id"
                                                value="<?= htmlspecialchars($student['user_id'] ?? '') ?>">

                                            <button type="submit" class="btn btn-danger btn-sm">
                                                Remove
                                            </button>

                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            Student
                                        </span>
                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <p class="mb-0">No students found.</p>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </main>

    <!-- Announcement modal -->
    <div class="modal fade" id="announcement" tabindex="-1" aria-labelledby="announcementLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">

            <form method="POST" enctype="multipart/form-data" class="modal-content">

                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="announcementLabel">New Announcement</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label" for="announcementTitle">Title</label>
                        <input type="text" id="announcementTitle" name="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="announcementDescription">Description</label>
                        <textarea id="announcementDescription" name="description" class="form-control" rows="4" required></textarea>
                    </div>

                    <div class="upload-section">
                        <div class="file-input-area">
                            <div class="upload-icon">📁</div>
                            <div>Click or drag & drop files</div>
                            <div class="small text-muted">PDF, DOCX, JPG (Max 10MB)</div>
                            <input type="file" name="files[]" multiple accept=".pdf,.docx,.jpg,.jpeg,.png">
                        </div>

                        <div class="file-list mt-3"></div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Close
                    </button>

                    <button type="submit" name="create_announcement" class="btn btn-primary">
                        Post Announcement
                    </button>
                </div>

            </form>

        </div>
    </div>

    <!-- Assignment modal -->
    <div class="modal fade" id="assignment" tabindex="-1" aria-labelledby="assignmentLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">

            <form method="POST" enctype="multipart/form-data" class="modal-content">

                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="assignmentLabel">New Assignment</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label" for="assignmentTitle">Title</label>
                        <input type="text" id="assignmentTitle" name="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="assignmentDescription">Description</label>
                        <textarea id="assignmentDescription" name="description" class="form-control" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="assignmentDueDate">Due Date</label>
                        <input type="datetime-local" id="assignmentDueDate" name="due_date" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="assignmentPoints">Points</label>
                        <input type="number" id="assignmentPoints" name="points" class="form-control" min="0" max="100" value="0" required>
                    </div>

                    <div class="upload-section">
                        <div class="file-input-area">
                            <div class="upload-icon">📁</div>
                            <div>Click or drag & drop files</div>
                            <div class="small text-muted">PDF, DOCX, JPG (Max 10MB)</div>
                            <input type="file" name="files[]" multiple accept=".pdf,.docx,.jpg,.jpeg,.png">
                        </div>

                        <div class="file-list mt-3"></div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Close
                    </button>

                    <button type="submit" name="create_assignment" class="btn btn-primary">
                        Post Assignment
                    </button>
                </div>

            </form>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/upload.js"></script>
    <script src='../js/animate.js'></script>
    <script src='../js/mobile-menu.js'></script>
    <script src='../js/assignment.js'></script>

    <script>
        const streamBtn = document.getElementById('streamBtn');
        const studentBtn = document.getElementById('getStudents');

        const streamSection = document.getElementById('streamSection');
        const studentSection = document.getElementById('studentSection');

        streamBtn.addEventListener('click', function() {
            streamSection.style.display = 'block';
            studentSection.style.display = 'none';

            streamBtn.classList.add('active');
            studentBtn.classList.remove('active');
        });

        studentBtn.addEventListener('click', function() {
            streamSection.style.display = 'none';
            studentSection.style.display = 'block';

            studentBtn.classList.add('active');
            streamBtn.classList.remove('active');
        });
    </script>

</body>

</html>