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
$user_id = (int) ($user['user_id'] ?? 0);

$database = Database::getInstance();
$classModel = new ClassModel($database);

$classes = $classModel->getClassesByUser($user_id);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatDateTime(mixed $date): string
{
    if (empty($date)) {
        return 'Unknown date';
    }

    return date('F d, Y h:i A', strtotime((string) $date));
}

function roleBadgeClass(string $role): string
{
    return $role === 'teacher' ? 'danger' : 'secondary';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/class.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <style>
        .course-card {
            border: 0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
            transition: 0.2s ease;
            height: 100%;
        }

        .course-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.13);
        }

        .course-header {
            min-height: 105px;
            padding: 22px;
            background: linear-gradient(135deg, #0f766e, #0ea5e9);
            color: white;
        }

        .course-title {
            font-weight: 800;
            margin-bottom: 4px;
            word-break: break-word;
        }

        .course-desc {
            color: #64748b;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 44px;
        }

        .course-meta {
            font-size: 13px;
            color: #64748b;
        }

        .empty-courses {
            border: 2px dashed #cbd5e1;
            border-radius: 18px;
            background: #f8fafc;
            padding: 45px;
            text-align: center;
            color: #64748b;
        }

        .quick-action-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
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

        <form class="lms-mobile-toolbar-search d-flex d-lg-none flex-grow-1 min-w-0" role="search" action="#" method="get">
            <input class="form-control" type="search" name="q" placeholder="Search courses…" aria-label="Search courses">
            <button class="btn btn-lms-primary lms-search-submit-btn" type="submit" aria-label="Search">⌕</button>
        </form>

        <button class="btn btn-lms-ghost d-lg-none flex-shrink-0 px-2 lms-mobile-more-btn" type="button" data-bs-toggle="collapse" data-bs-target="#lmsMobileNavMore" aria-expanded="false" aria-label="Account menu">
            ⋯
        </button>

        <div class="collapse lms-mobile-nav-drawer d-lg-none w-100" id="lmsMobileNavMore">
            <div class="lms-mobile-nav-drawer-inner d-flex flex-column gap-2">
                <a class="btn btn-lms-ghost w-100 text-start" href="home.php">+ Class</a>

                <div class="dropdown">
                    <a class="btn dropdown-toggle w-100 text-start" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        👤 <?= e($user['first_name'] ?? '') ?>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end w-100">
                        <li><a class="dropdown-item" href="account_settings.php">Profile</a></li>
                        <li><a class="dropdown-item" href="archive_classes.php">Archived Classes</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">Sign out</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="d-none d-lg-flex align-items-center ms-lg-auto gap-2 flex-nowrap">
            <form class="d-flex align-items-center gap-2 lms-search" role="search" action="#" method="get">
                <a class="btn btn-lms-ghost" href="home.php">+ Class</a>
                <input class="form-control" type="search" name="q" placeholder="Search courses…" aria-label="Search courses">
                <button class="btn btn-lms-primary" type="submit">Search</button>
            </form>

            <div class="dropdown">
                <a class="btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    👤 <?= e($user['first_name'] ?? '') ?>
                </a>

                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="account_settings.php">Profile</a></li>
                    <li><a class="dropdown-item" href="archive_classes.php">Archived Classes</a></li>
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

    <a href="my_courses.php" class="active">
        <span class="nav-ico" aria-hidden="true">▤</span>
        My Courses
    </a>

    <a href="archive_classes.php">
        <span class="nav-ico" aria-hidden="true">🗄</span>
        Archived Classes
    </a>

    <p class="sidebar-label">Classes</p>

    <?php if (!empty($classes)): ?>
        <?php foreach ($classes as $class): ?>
            <a href="class.php?class_id=<?= e($class['class_id']) ?>">
                <?= e($class['class_name']) ?>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <a href="#" class="text-muted">No classes yet</a>
    <?php endif; ?>
</aside>

<main class="main lms-main">

    <div class="hero lms-hero mb-3">
        <h2>My Courses</h2>
        <p>View all your active classes in one place.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card quick-action-card p-3">
                <div class="small text-muted">Total Active Courses</div>
                <h3 class="fw-bold mb-0"><?= count($classes) ?></h3>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card quick-action-card p-3">
                <div class="small text-muted">Teacher Classes</div>
                <h3 class="fw-bold mb-0">
                    <?= count(array_filter($classes, fn($class) => ($class['role'] ?? '') === 'teacher')) ?>
                </h3>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card quick-action-card p-3">
                <div class="small text-muted">Student Classes</div>
                <h3 class="fw-bold mb-0">
                    <?= count(array_filter($classes, fn($class) => ($class['role'] ?? '') === 'student')) ?>
                </h3>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h4 class="fw-bold mb-0">Courses</h4>

        <div class="d-flex gap-2">
            <a href="home.php" class="btn btn-primary btn-sm">
                + Create / Join Class
            </a>

            <a href="archive_classes.php" class="btn btn-outline-secondary btn-sm">
                View Archived
            </a>
        </div>
    </div>

    <?php if (!empty($classes)): ?>
        <div class="row g-3">
            <?php foreach ($classes as $class): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card course-card">

                        <div class="course-header">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <h5 class="course-title">
                                        <?= e($class['class_name']) ?>
                                    </h5>

                                    <div class="small opacity-75">
                                        Code: <?= e($class['class_code']) ?>
                                    </div>
                                </div>

                                <span class="badge bg-<?= e(roleBadgeClass((string) $class['role'])) ?>">
                                    <?= e(ucfirst((string) $class['role'])) ?>
                                </span>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <p class="course-desc mb-3">
                                <?= e($class['class_desc'] ?: 'No description provided.') ?>
                            </p>

                            <div class="course-meta mb-1">
                                <strong>Status:</strong>
                                <?= e($class['status'] ?? 'Active') ?>
                            </div>

                            <?php if (!empty($class['created_at'])): ?>
                                <div class="course-meta mb-3">
                                    <strong>Created:</strong>
                                    <?= e(formatDateTime($class['created_at'])) ?>
                                </div>
                            <?php endif; ?>

                            <a href="class.php?class_id=<?= e($class['class_id']) ?>" class="btn btn-outline-primary w-100">
                                Open Course
                            </a>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-courses">
            <i class="fa-regular fa-folder-open fs-1 mb-3 d-block"></i>

            <h4 class="fw-bold">No active courses yet</h4>

            <p class="mb-3">
                Create a class if you are a teacher, or join a class using a class code.
            </p>

            <a href="home.php" class="btn btn-primary">
                Go to Home
            </a>
        </div>
    <?php endif; ?>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/mobile-menu.js"></script>

</body>
</html>