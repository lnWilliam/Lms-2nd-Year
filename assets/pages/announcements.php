<?php
<<<<<<< HEAD
declare(strict_types=1);
=======
>>>>>>> refactor-oop
session_start();
require_once "../../vendor/autoload.php";

use App\Helpers\Database;
use App\Models\ClassModel;

if (!isset($_SESSION['user_data'])) {
    header('Location: ../../logout.php');
    exit();
}

$user = $_SESSION['user_data'];

if (!isset($_GET['post_id'])) {
    die('No announcement selected.');
}

$post_id = (int) $_GET['post_id'];

$database = Database::getInstance();
$conn = $database->getConnection();
$classModel = new ClassModel($database);

$sql = "SELECT
            p.post_id,
            p.class_id,
            p.postedBy,
            p.type,
            p.title,
            p.description,
            p.created_at,
            u.first_name,
            u.last_name
        FROM Post p
        JOIN Users u ON u.user_id = p.postedBy
        WHERE p.post_id = ?
        AND p.type = 'announcement'
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$post_id]);
$announcement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$announcement) {
    die('Announcement not found.');
}

$class_id = (int) $announcement['class_id'];

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

$attachments = $classModel->getPostAttachments($post_id);

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatDateTime($date)
{
    if (empty($date)) {
        return '';
    }

    return date('F d, Y h:i A', strtotime($date));
}

function viewerLink($filePath)
{
    return 'viewer.php?file=' . urlencode(basename($filePath));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($announcement['title'] ?? 'Announcement') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/class.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <style>
        .announcement-view-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
        }

        .announcement-meta {
            color: #6b7280;
            font-size: 0.92rem;
        }

        .announcement-description {
            white-space: pre-wrap;
            line-height: 1.7;
            color: #1f2937;
        }

        .attachment-item {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 14px 16px;
            background: #fff;
            transition: 0.2s ease;
        }

        .attachment-item:hover {
            border-color: #0d6efd;
            box-shadow: 0 8px 18px rgba(13, 110, 253, 0.08);
        }

        .attachment-name {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .announcement-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0f766e, #0ea5e9);
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 20px;
            flex-shrink: 0;
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
                <div class="dropdown">
                    <a class="btn dropdown-toggle w-100 text-start" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        👤 <?= e($user['first_name'] ?? '') ?>
                    </a>

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

    <a href="class.php?class_id=<?= e($class_id) ?>" class="active">
        <span class="nav-ico" aria-hidden="true">▤</span>
        <?= e($currentClass['class_name']) ?>
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

        <p>Announcement</p>
    </div>

    <div class="mb-3">
        <a href="class.php?class_id=<?= e($class_id) ?>" class="btn btn-light btn-sm">
            ← Back to Class
        </a>
    </div>

    <div class="card announcement-view-card mb-4">
        <div class="card-body p-4">

            <div class="d-flex align-items-start gap-3 mb-4">
                <div class="announcement-icon">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>

                <div class="flex-grow-1">
                    <h2 class="fw-bold mb-1">
                        <?= e($announcement['title'] ?: 'Untitled Announcement') ?>
                    </h2>

                    <div class="announcement-meta">
                        Posted by
                        <strong>
                            <?= e($announcement['first_name'] . ' ' . $announcement['last_name']) ?>
                        </strong>
                        •
                        <?= e(formatDateTime($announcement['created_at'])) ?>
                    </div>
                </div>
            </div>

            <hr>

            <div class="mb-4">
                <h5 class="fw-bold mb-3">Description</h5>

                <?php if (!empty($announcement['description'])): ?>
                    <div class="announcement-description">
                        <?= e($announcement['description']) ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">
                        No description provided.
                    </p>
                <?php endif; ?>
            </div>

            <div>
                <h5 class="fw-bold mb-3">
                    <i class="fa-solid fa-paperclip me-1"></i>
                    Attachments
                </h5>

                <?php if (!empty($attachments)): ?>
                    <div class="row g-3">
                        <?php foreach ($attachments as $file): ?>
                            <div class="col-md-6">
                                <div class="attachment-item d-flex justify-content-between align-items-center gap-3">
                                    <div class="overflow-hidden">
                                        <div class="fw-semibold attachment-name">
                                            <?= e($file['file_name']) ?>
                                        </div>

                                        <small class="text-muted">
                                            <?= e(strtoupper($file['attachment_type'])) ?>
                                        </small>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <a href="<?= e(viewerLink($file['file_path'])) ?>"
                                           target="_blank"
                                           class="btn btn-outline-primary btn-sm">
                                            View
                                        </a>

                                        <a href="<?= e($file['file_path']) ?>"
                                           download
                                           class="btn btn-outline-secondary btn-sm">
                                            Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">
                        No files attached to this announcement.
                    </p>
                <?php endif; ?>
            </div>

        </div>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/animate.js"></script>

</body>
<<<<<<< HEAD
</html>
=======
</html>
>>>>>>> refactor-oop
