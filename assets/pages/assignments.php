<?php
declare(strict_types=1); // ADDED: PHP strict types must be the first PHP statement.

session_start();
require_once "../../vendor/autoload.php";

use App\Helpers\Database;
use App\Models\ClassModel;

if (!isset($_SESSION['user_data'])) {
    header('Location: ../../logout.php');
    exit();
}

$user = $_SESSION['user_data'];
$user_id = (int) ($user['user_id'] ?? 0); // EDITED: cast session ID to int for strict-types-safe model/controller calls.
$database = Database::getInstance();
$classModel = new ClassModel($database);

if (!isset($_GET['post_id'])) {
    die('No assignment selected.');
}

$post_id = (int) $_GET['post_id']; // EDITED: $_GET values are strings, so cast to int for strict types.
$assignment = $classModel->getAssignmentByPostId($post_id);

if (!$assignment) {
    die('Assignment not found.');
}

$class_id = (int) $assignment['class_id'];
$classes = $classModel->getClassesByUser($user_id);
$currentClass = null;

foreach ($classes as $c) {
    if ((int)$c['class_id'] === $class_id) {
        $currentClass = $c;
        break;
    }
}

if (!$currentClass) {
    die('Unauthorized access.');
}

$isTeacher = ($currentClass['role'] === 'teacher');
$message = null;
$messageType = 'success';

// TEACHER: save / update grade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grade'])) {
    if (!$isTeacher) {
        die('Only teachers can grade assignments.');
    }

    $student_id = (int) ($_POST['student_id'] ?? 0);
    $grade = $_POST['grade'] ?? null;

    if ($grade === '' || !is_numeric($grade)) {
        $_SESSION['assignment_error'] = 'Grade must be a number.';
    } elseif ((float)$grade < 0 || (float)$grade > (float)$assignment['max_score']) {
        $_SESSION['assignment_error'] = 'Grade must be between 0 and ' . $assignment['max_score'] . '.';
    } else {
        $saved = $classModel->saveStudentGrade($class_id, $student_id, (int) $assignment['activity_id'], $grade);
        $_SESSION[$saved ? 'assignment_success' : 'assignment_error'] = $saved ? 'Grade saved successfully.' : 'Unable to save grade.';
    }

    header('Location: assignments.php?post_id=' . $post_id);
    exit();
}

// STUDENT: upload files and automatically turn in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['turn_in_assignment'])) {
    if ($isTeacher) {
        die('Teachers cannot submit this assignment.');
    }

    $result = $classModel->submitAssignmentFiles(
        $class_id,
        $user_id,
        (int) $assignment['activity_id'],
        $_FILES['submission_files'] ?? []
    );

    $_SESSION[$result['success'] ? 'assignment_success' : 'assignment_error'] = $result['message'];
    header('Location: assignments.php?post_id=' . $post_id);
    exit();
}

if (isset($_SESSION['assignment_success'])) {
    $message = $_SESSION['assignment_success'];
    $messageType = 'success';
    unset($_SESSION['assignment_success']);
} elseif (isset($_SESSION['assignment_error'])) {
    $message = $_SESSION['assignment_error'];
    $messageType = 'danger';
    unset($_SESSION['assignment_error']);
}

$gradeRows = $isTeacher
    ? $classModel->getAssignmentGrades($class_id, (int) $assignment['activity_id'])
    : [];

$mySubmission = !$isTeacher
    ? $classModel->getStudentSubmission($class_id, $user_id, (int) $assignment['activity_id'])
    : null;

$myFiles = (!$isTeacher && $mySubmission && !empty($mySubmission['submission_id']))
    ? $classModel->getSubmissionFiles($mySubmission['submission_id'])
    : [];

$teacherAttachments = $classModel->getPostAttachments($post_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= htmlspecialchars($assignment['title'] ?? 'Assignment') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/class.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light lms-topbar fixed-top px-3">
        <div class="container-fluid gap-2 align-items-center lms-topbar-inner">
            <button class="navbar-toggler d-lg-none lms-sidebar-toggler flex-shrink-0" type="button" aria-label="Open side navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand d-flex align-items-center flex-shrink-0" href="home.php">
                <span class="brand-mark" aria-hidden="true">◆</span>
                MyLMS
            </a>
            <form class="lms-mobile-toolbar-search d-flex d-lg-none flex-grow-1 min-w-0" role="search" action="#" method="get">
                <input class="form-control" type="search" name="q" placeholder="Search courses…" aria-label="Search courses">
                <button class="btn btn-lms-primary lms-search-submit-btn" type="submit" aria-label="Search">⌕</button>
            </form>
            <button class="btn btn-lms-ghost d-lg-none flex-shrink-0 px-2 lms-mobile-more-btn" type="button" data-bs-toggle="collapse" data-bs-target="#lmsMobileNavMore" aria-expanded="false" aria-label="Account menu">⋯</button>
            <div class="collapse lms-mobile-nav-drawer d-lg-none w-100" id="lmsMobileNavMore">
                <div class="lms-mobile-nav-drawer-inner d-flex flex-column gap-2">
                    <div class="dropdown">
                        <a class="btn dropdown-toggle w-100 text-start" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">👤 <?= htmlspecialchars($user['first_name'] ?? '') ?></a>
                        <ul class="dropdown-menu dropdown-menu-end w-100">
                            <li><a class="dropdown-item" href="account_settings.php">Profile</a></li>
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
                    <a class="btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">👤 <?= htmlspecialchars($user['first_name'] ?? '') ?></a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="account_settings.php">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">Sign out</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <aside class="sidebar lms-sidebar">
        <p class="sidebar-label">Learn</p>
        <a href="home.php"><span class="nav-ico" aria-hidden="true">⌂</span>Home</a>
        <a href="class.php?class_id=<?= $class_id ?>" class="active"><span class="nav-ico" aria-hidden="true">▤</span><?= htmlspecialchars($currentClass['class_name']) ?></a>
        <p class="sidebar-label">Classes</p>
        <?php foreach ($classes as $c): ?>
            <a href="class.php?class_id=<?= $c['class_id'] ?>" class="<?= ((int)$c['class_id'] === $class_id) ? 'active' : '' ?>">
                <?= htmlspecialchars($c['class_name']) ?>
            </a>
        <?php endforeach; ?>
    </aside>

    <main class="main lms-main">
        <div class="hero lms-hero mb-3">
            <h2>
                <?= htmlspecialchars($assignment['title']) ?>
                <span class="badge bg-<?= $isTeacher ? 'danger' : 'secondary' ?>"><?= ucfirst($currentClass['role']) ?></span>
            </h2>
            <p><?= htmlspecialchars($currentClass['class_name']) ?></p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card p-3 teacher-panel mb-3">
                    <h5><i class="fa-solid fa-circle-info me-1"></i> Assignment Details</h5>
                    <p class="mb-3"><?= nl2br(htmlspecialchars($assignment['description'] ?: 'No description provided.')) ?></p>
                    <div class="small text-muted mb-1"><strong>Due:</strong> <?= htmlspecialchars($assignment['due_date'] ?? 'No due date') ?></div>
                    <div class="small text-muted mb-1"><strong>Points:</strong> <?= htmlspecialchars($assignment['max_score']) ?></div>
                    <a href="class.php?class_id=<?= $class_id ?>" class="btn btn-light btn-sm mt-3">← Back to Class</a>
                </div>

                <div class="card p-3 teacher-panel mb-3">
                    <h5><i class="fa-solid fa-paperclip me-1"></i> Teacher Files</h5>
                    <?php if (!empty($teacherAttachments)): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($teacherAttachments as $file): ?>
                                <li class="list-group-item bg-transparent px-0">
                                    <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank" class="attachment-link">
                                        📎 <?= htmlspecialchars($file['file_name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0">No files uploaded by the teacher.</p>
                    <?php endif; ?>
                </div>

                <?php if (!$isTeacher): ?>
                    <div class="card p-3 teacher-panel">
                        <h5><i class="fa-solid fa-upload me-1"></i> Your Work</h5>

                        <?php if ($mySubmission && !empty($mySubmission['submission_id'])): ?>
                            <div class="alert alert-success py-2 mb-3">
                                Turned in<?= !empty($mySubmission['submitted_at']) ? ' at ' . htmlspecialchars($mySubmission['submitted_at']) : '' ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning py-2 mb-3">Not turned in yet.</div>
                        <?php endif; ?>

                        <?php if (!empty($myFiles)): ?>
                            <p class="fw-bold mb-2">Uploaded files</p>
                            <ul class="list-group list-group-flush mb-3">
                                <?php foreach ($myFiles as $file): ?>
                                    <li class="list-group-item bg-transparent px-0">
                                        <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank" class="attachment-link">
                                            📄 <?= htmlspecialchars($file['file_name']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <label class="form-label">Upload file/s to turn in</label>
                            <input type="file" name="submission_files[]" class="form-control mb-3" multiple required>
                            <button type="submit" name="turn_in_assignment" class="btn btn-primary w-100">
                                <?= ($mySubmission && !empty($mySubmission['submission_id'])) ? 'Upload More Files' : 'Turn In' ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-8">
                <?php if ($isTeacher): ?>
                    <div class="card p-3 announcement-card">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <h4 class="mb-0">Student Submissions & Grades</h4>
                            <span class="badge bg-primary"><?= count($gradeRows) ?> students</span>
                        </div>

                        <?php if (count($gradeRows) > 0): ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Status</th>
                                            <th>Files</th>
                                            <th style="width: 170px;">Grade</th>
                                            <th style="width: 110px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($gradeRows as $row): ?>
                                            <?php $studentFiles = $classModel->getSubmissionFilesByStudent($class_id, $row['user_id'], (int) $assignment['activity_id']); ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></strong>
                                                    <div class="small text-muted"><?= htmlspecialchars($row['email'] ?? '') ?></div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($row['submission_id'])): ?>
                                                        <span class="badge bg-success">Turned in</span>
                                                        <div class="small text-muted"><?= htmlspecialchars($row['submitted_at'] ?? '') ?></div>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">Missing</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($studentFiles)): ?>
                                                        <?php foreach ($studentFiles as $file): ?>
                                                            <div class="mb-1">
                                                                <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank" class="attachment-link">
                                                                    📄 <?= htmlspecialchars($file['file_name']) ?>
                                                                </a>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted small">No files</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <form method="POST" class="d-flex gap-2 align-items-center">
                                                        <input type="hidden" name="student_id" value="<?= htmlspecialchars($row['user_id']) ?>">
                                                        <input type="number" step="0.01" min="0" max="<?= htmlspecialchars($assignment['max_score']) ?>" name="grade" class="form-control" value="<?= htmlspecialchars($row['grade'] ?? '') ?>" placeholder="0">
                                                </td>
                                                <td>
                                                        <button type="submit" name="save_grade" class="btn btn-primary btn-sm">Save</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="mb-0">No students found in this class.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="card p-4 announcement-card">
                        <h4>Your Grade</h4>
                        <?php if ($mySubmission && $mySubmission['grade'] !== null): ?>
                            <p class="display-6 fw-bold mb-1"><?= htmlspecialchars($mySubmission['grade']) ?> / <?= htmlspecialchars($assignment['max_score']) ?></p>
                            <p class="text-muted mb-0">Submitted at <?= htmlspecialchars($mySubmission['submitted_at']) ?></p>
                        <?php else: ?>
                            <p class="mb-0 text-muted">Not graded yet.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/animate.js"></script>
</body>
</html>
