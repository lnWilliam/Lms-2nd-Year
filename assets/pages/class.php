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

// 🧑‍🎓 Remove student from class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_student_id'])) {

    if ($isTeacher) {
        $student_id = (int) $_POST['remove_student_id'];
        $classModel->removeStudentFromClass($class_id, $student_id);
    }

    header("Location: class.php?class_id=" . $class_id);
    exit();
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {

    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? null;
    $points = $_POST['points'] ?? null;

    $post_id = $classModel->createAssignment(
        $class_id,
        $user['user_id'],
        $title,
        $description,
        $due_date,
        $points
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
                        <a class="btn dropdown-toggle w-100 text-start" href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="true" aria-expanded="false">👤 <?php echo htmlspecialchars($user['first_name'] ?? ''); ?></a>
                        <ul class="dropdown-menu dropdown-menu-end w-100">
                            <li><a class="dropdown-item" href="account_settings.php">Profile</a></li>
                            <li><a class="dropdown-item" href="#">Preferences</a></li>
                            <li><hr class="dropdown-divider"></li>
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
                    <a class="btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">👤 <?php echo htmlspecialchars($user['first_name'] ?? ''); ?></a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="account_settings.php">Profile</a></li>
                        <li><a class="dropdown-item" href="#">Preferences</a></li>
                        <li><hr class="dropdown-divider"></li>
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
            <ul class="nav gap-5">
                <li class="nav-item">
                    <button class="nav-link active border-0 bg-transparent" type="button" id="streamBtn">
                        Stream
                    </button>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Classwork</a>
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

                <!-- TEACHER PANEL -->
                <?php if ($isTeacher): ?>
                    <div class="card p-3 mb-3 teacher-panel">
                        <h5>⚙️ Teacher Panel</h5>

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

                <!-- STREAM SECTION -->
                <div id="streamSection">

                <button class="btn btn-success mb-3" type="button" data-bs-toggle="modal" data-bs-target="#announcement">✏️ New Announcement</button>
                <?php if ($isTeacher): ?>
                    <button class="btn btn-primary mb-3" type="button" data-bs-toggle="modal" data-bs-target="#assignment">➕ New Assignment</button>
                <?php endif; ?>
                <?php
                $posts = $classModel->getClassPosts($class_id);
                ?>

                <?php foreach ($posts as $post): ?>
                    <a href="<?php echo $post['type']."s.php?post_id=" . $post['post_id'] ?? "#" ?>" style="text-decoration:none;color:inherit;">
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
                    </a>
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

                    <div class="upload-section" id="uploadSection">
                        <div class="file-input-area" id="dropZone">
                            <div class="upload-icon">📁</div>
                            <div>Click or drag & drop files</div>
                            <div class="small text-muted">PDF, DOCX, JPG (Max 10MB)</div>
                            <input type="file" name="files[]" multiple accept=".pdf,.docx,.jpg,.jpeg" id="fileInput">
                        </div>
                        <div id="fileList" class="file-list mt-3"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="create_announcement" class="btn btn-primary">Post Announcement</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="assignment" tabindex="-1" aria-labelledby="assignmentLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="assignmentLabel">New assignment</h1>
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
                        <input type="number" id="assignmentPoints" name="points" class="form-control" min="0" max="100" default="0" required>
                    </div>
                    <div class="upload-section" id="uploadSection">
                        <div class="file-input-area" id="dropZone">
                            <div class="upload-icon">📁</div>
                            <div>Click or drag & drop files</div>
                            <div class="small text-muted">PDF, DOCX, JPG (Max 10MB)</div>
                            <input type="file" name="files[]" multiple accept=".pdf,.docx,.jpg,.jpeg" id="fileInput">
                        </div>
                        <div id="fileList" class="file-list mt-3"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="create_assignment" class="btn btn-primary">Post Assignment</button>
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

        streamBtn.addEventListener('click', function () {
            streamSection.style.display = 'block';
            studentSection.style.display = 'none';

            streamBtn.classList.add('active');
            studentBtn.classList.remove('active');
        });

        studentBtn.addEventListener('click', function () {
            streamSection.style.display = 'none';
            studentSection.style.display = 'block';

            studentBtn.classList.add('active');
            streamBtn.classList.remove('active');
        });
    </script>
</body>

</html>