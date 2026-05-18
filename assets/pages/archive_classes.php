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

$activeClasses = $classModel->getClassesByUser($user_id);
$archivedClasses = $classModel->getArchivedClassesByUser($user_id);

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

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Classes</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/class.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <style>
        .archive-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
            transition: 0.2s ease;
        }

        .archive-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.12);
        }

        .archive-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #64748b, #94a3b8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .empty-box {
            border: 2px dashed #cbd5e1;
            border-radius: 18px;
            background: #f8fafc;
            padding: 40px;
            text-align: center;
            color: #64748b;
        }

        .class-desc {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            color: #64748b;
        }

        .status-pill {
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 13px;
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
                        <li>
                            <hr class="dropdown-divider">
                        </li>
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
        <a href="my_courses.php">
            <span class="nav-ico" aria-hidden="true">▤</span>
            My Courses
        </a>
        <a href="archive_classes.php" class="active">
            <span class="nav-ico" aria-hidden="true">🗄</span>
            Archived Classes
        </a>

        <p class="sidebar-label">Active Classes</p>

        <?php if (!empty($activeClasses)): ?>
            <?php foreach ($activeClasses as $class): ?>
                <a href="class.php?class_id=<?= e($class['class_id']) ?>">
                    <?= e($class['class_name']) ?>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <a href="#" class="text-muted">No active classes</a>
        <?php endif; ?>
    </aside>

    <main class="main lms-main">

        <div class="hero lms-hero mb-3">
            <h2>Archived Classes</h2>
            <p>View classes that were archived or marked as inactive.</p>
        </div>

        <div class="mb-3">
            <a href="home.php" class="btn btn-light btn-sm">
                ← Back to Home
            </a>
        </div>

        <?php if (!empty($archivedClasses)): ?>
            <div class="row g-3">
                <?php foreach ($archivedClasses as $class): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card archive-card h-100">
                            <div class="card-body p-4">

                                <div class="d-flex align-items-start gap-3 mb-3">
                                    <div class="archive-icon">
                                        <i class="fa-solid fa-box-archive"></i>
                                    </div>

                                    <div class="flex-grow-1">
                                        <h5 class="fw-bold mb-1">
                                            <?= e($class['class_name']) ?>
                                        </h5>

                                        <span class="status-pill bg-secondary text-white">
                                            <?= e($class['status']) ?>
                                        </span>
                                    </div>
                                </div>

                                <p class="class-desc mb-3">
                                    <?= e($class['class_desc'] ?: 'No description provided.') ?>
                                </p>

                                <div class="small text-muted mb-1">
                                    <strong>Role:</strong>
                                    <?= e(ucfirst($class['role'])) ?>
                                </div>

                                <div class="small text-muted mb-1">
                                    <strong>Class Code:</strong>
                                    <?= e($class['class_code']) ?>
                                </div>

                                <div class="small text-muted mb-3">
                                    <strong>Created:</strong>
                                    <?= e(formatDateTime($class['created_at'] ?? null)) ?>
                                </div>

                                <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                                    Archived
                                </button>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-box">
                <i class="fa-solid fa-box-archive fs-1 mb-3 d-block"></i>
                <h4 class="fw-bold">No archived classes</h4>
                <p class="mb-0">
                    Archived or inactive classes will appear here.
                </p>
            </div>
        <?php endif; ?>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/mobile-menu.js"></script>

</body>

</html>