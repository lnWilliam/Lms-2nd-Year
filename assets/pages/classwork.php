<?php
declare(strict_types=1);
session_start();
require_once "../../vendor/autoload.php";

use App\Helpers\Database;
use App\Models\ClassModel;

if (!isset($_SESSION['user_data'])) {
    header('Location: ../../logout.php');
    exit();
}

$user = $_SESSION['user_data'];

if (!isset($_GET['class_id'])) {
    die('No class selected.');
}

$class_id = (int) $_GET['class_id'];

$database = Database::getInstance();
$classModel = new ClassModel($database);

$classes = $classModel->getClassesByUser($user['user_id']);

$currentClass = null;

foreach ($classes as $class) {
    if ((int) $class['class_id'] === $class_id) {
        $currentClass = $class;
        break;
    }
}

if (!$currentClass) {
    die('Unauthorized access.');
}

$isTeacher = ($currentClass['role'] === 'teacher');

$posts = $classModel->getClassPosts($class_id);

$assignments = [];
$announcements = [];


foreach ($posts as $post) {
    if ($post['type'] === 'assignment') {
        $assignments[] = $post;
    } elseif ($post['type'] === 'announcement') {
        $announcements[] = $post;
    }
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatDateTime($date)
{
    if (empty($date)) {
        return 'No due date';
    }

    return date('F d, Y h:i A', strtotime($date));
}

function postLink($post)
{
    if ($post['type'] === 'assignment') {
        return 'assignments.php?post_id=' . urlencode((string) $post['post_id']);
    }

    if ($post['type'] === 'announcement') {
        return 'announcements.php?post_id=' . urlencode((string) $post['post_id']);
    }

    return 'class.php?class_id=' . urlencode((string) $post['class_id']);
}

function renderPostCard($post)
{
    $type = $post['type'];
    $badgeClass = 'secondary';
    $icon = 'fa-file';

    if ($type === 'assignment') {
        $badgeClass = 'primary';
        $icon = 'fa-file-pen';
    } elseif ($type === 'announcement') {
        $badgeClass = 'success';
        $icon = 'fa-bullhorn';
    }

    $link = postLink($post);
    ?>

    <div class="classwork-item card border-0 shadow-sm mb-3">
        <div class="card-body p-3">

            <div class="d-flex align-items-start gap-3">
                <div class="classwork-icon bg-<?= e($badgeClass) ?>">
                    <i class="fa-solid <?= e($icon) ?>"></i>
                </div>

                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div>
                            <h5 class="fw-bold mb-1">
                                <?= e($post['title'] ?: ucfirst($type)) ?>
                            </h5>

                            <div class="small text-muted">
                                Posted by
                                <?= e(($post['first_name'] ?? '') . ' ' . ($post['last_name'] ?? '')) ?>
                                •
                                <?= e(formatDateTime($post['created_at'] ?? null)) ?>
                            </div>
                        </div>

                        <span class="badge bg-<?= e($badgeClass) ?>">
                            <?= e(ucfirst($type)) ?>
                        </span>
                    </div>

                    <?php if (!empty($post['description'])): ?>
                        <p class="text-muted mt-2 mb-2 classwork-desc">
                            <?= e($post['description']) ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($type === 'assignment'): ?>
                        <div class="small mb-2">
                            <span class="text-danger fw-semibold">
                                Due:
                            </span>
                            <?= e(formatDateTime($post['due_date'] ?? null)) ?>

                            <?php if (isset($post['max_score'])): ?>
                                <span class="ms-2 text-primary fw-semibold">
                                    Points:
                                </span>
                                <?= e($post['max_score']) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($post['file_names'])): ?>
                        <?php
                        $fileNames = explode('||', $post['file_names']);
                        ?>
                        <div class="small text-muted mb-2">
                            <i class="fa-solid fa-paperclip me-1"></i>
                            <?= count($fileNames) ?> attachment<?= count($fileNames) > 1 ? 's' : '' ?>
                        </div>
                    <?php endif; ?>

                    <a href="<?= e($link) ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                        View
                    </a>
                </div>
            </div>

        </div>
    </div>

    <?php
}

function renderEmptyState($message)
{
    ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 text-center text-muted">
            <i class="fa-regular fa-folder-open fs-1 d-block mb-2"></i>
            <?= e($message) ?>
        </div>
    </div>
    <?php
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($currentClass['class_name']) ?> - Classwork</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/class.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <style>
        .classwork-tabs .nav-link {
            color: #374151;
            font-weight: 600;
            border-radius: 999px;
            padding: 10px 18px;
        }

        .classwork-tabs .nav-link.active {
            background: linear-gradient(135deg, #0f766e, #0ea5e9);
            color: white;
        }

        .classwork-icon {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-shrink: 0;
            font-size: 18px;
        }

        .classwork-item {
            border-radius: 18px;
        }

        .classwork-desc {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.5;
        }

        .classwork-section-title {
            font-weight: 800;
            margin-bottom: 16px;
        }

        .classwork-count {
            font-size: 13px;
            background: #f1f5f9;
            color: #475569;
            padding: 4px 10px;
            border-radius: 999px;
        }
    </style>
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

        <div class="d-none d-lg-flex align-items-center ms-lg-auto gap-2 flex-nowrap">
            <form class="d-flex align-items-center gap-2 lms-search" role="search" action="#" method="get">
                <input class="form-control" type="search" name="q" placeholder="Search courses…" aria-label="Search courses">
                <button class="btn btn-lms-primary" type="submit">Search</button>
            </form>

            <div class="dropdown">
                <a class="btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    👤 <?= e($user['first_name'] ?? '') ?>
                </a>

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

    <a href="home.php">
        <span class="nav-ico" aria-hidden="true">⌂</span>
        Home
    </a>

    <a href="class.php?class_id=<?= e($class_id) ?>">
        <span class="nav-ico" aria-hidden="true">▤</span>
        Stream
    </a>

    <a href="classwork.php?class_id=<?= e($class_id) ?>" class="active">
        <span class="nav-ico" aria-hidden="true">✎</span>
        Classwork
    </a>

    <p class="sidebar-label">Classes</p>

    <?php foreach ($classes as $class): ?>
        <a href="class.php?class_id=<?= e($class['class_id']) ?>"
           class="<?= ((int) $class['class_id'] === $class_id) ? 'active' : '' ?>">
            <?= e($class['class_name']) ?>
        </a>
    <?php endforeach; ?>
</aside>

<main class="main lms-main">

    <div class="hero lms-hero mb-3">
        <h2>
            <?= e($currentClass['class_name']) ?>
            <span class="badge bg-<?= $isTeacher ? 'danger' : 'secondary' ?>">
                <?= e(ucfirst($currentClass['role'])) ?>
            </span>
        </h2>

        <p>Classwork</p>
    </div>

    <div class="mb-3 d-flex gap-2 flex-wrap">
        <a href="class.php?class_id=<?= e($class_id) ?>" class="btn btn-light btn-sm">
            ← Back to Stream
        </a>

        <?php if ($isTeacher): ?>
            <a href="class.php?class_id=<?= e($class_id) ?>" class="btn btn-primary btn-sm">
                + Create from Stream
            </a>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <ul class="nav classwork-tabs gap-2" id="classworkTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="assignments-tab" data-bs-toggle="tab" data-bs-target="#assignments" type="button" role="tab">
                        Assignments
                        <span class="ms-1"><?= count($assignments) ?></span>
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="announcements-tab" data-bs-toggle="tab" data-bs-target="#announcements" type="button" role="tab">
                        Announcements
                        <span class="ms-1"><?= count($announcements) ?></span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <div class="tab-content">

        <div class="tab-pane fade show active" id="assignments" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h4 class="classwork-section-title mb-0">Assignments</h4>
                <span class="classwork-count"><?= count($assignments) ?> item<?= count($assignments) === 1 ? '' : 's' ?></span>
            </div>

            <?php if (!empty($assignments)): ?>
                <?php foreach ($assignments as $post): ?>
                    <?php renderPostCard($post); ?>
                <?php endforeach; ?>
            <?php else: ?>
                <?php renderEmptyState('No assignments posted yet.'); ?>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="announcements" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h4 class="classwork-section-title mb-0">Announcements</h4>
                <span class="classwork-count"><?= count($announcements) ?> item<?= count($announcements) === 1 ? '' : 's' ?></span>
            </div>

            <?php if (!empty($announcements)): ?>
                <?php foreach ($announcements as $post): ?>
                    <?php renderPostCard($post); ?>
                <?php endforeach; ?>
            <?php else: ?>
                <?php renderEmptyState('No announcements posted yet.'); ?>
            <?php endif; ?>
        </div>

    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/animate.js"></script>

</body>
</html>
