<?php

declare(strict_types=1);

session_start();

require_once "../../vendor/autoload.php";

use App\Helpers\Database;
use App\Models\UserModel;

if (!isset($_SESSION["user_data"])) {
    header('Location: ../../logout.php');
    exit();
}

$user = $_SESSION['user_data'];
$user_id = (int) ($user['user_id'] ?? 0);

$database = Database::getInstance();
$userModel = new UserModel($database);

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = trim($_POST['email'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');

    if ($email === '' || $firstName === '' || $lastName === '') {
        $message = 'All profile fields are required.';
        $messageType = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'danger';
    } else {
        $updated = $userModel->updateProfile($user_id, [
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName
        ]);

        if ($updated) {
            $freshUser = $userModel->getUserById($user_id);

            if ($freshUser) {
                unset($freshUser['password']);
                $_SESSION['user_data'] = $freshUser;
                $user = $freshUser;
            }

            $message = 'Profile updated successfully.';
            $messageType = 'success';
        } else {
            $message = 'Unable to update profile.';
            $messageType = 'danger';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {

    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $message = 'All password fields are required.';
        $messageType = 'danger';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'New passwords do not match.';
        $messageType = 'danger';
    } elseif (strlen($newPassword) < 8) {
        $message = 'Password must be at least 8 characters.';
        $messageType = 'danger';
    } else {
        $currentUser = $userModel->getUserById($user_id);

        if (!$currentUser || empty($currentUser['password'])) {
            $message = 'Unable to verify your current password.';
            $messageType = 'danger';
        } elseif (!password_verify($oldPassword, $currentUser['password'])) {
            $message = 'Old password is incorrect.';
            $messageType = 'danger';
        } else {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $updated = $userModel->updatePassword($user_id, $passwordHash);

            if ($updated) {
                $message = 'Password updated successfully.';
                $messageType = 'success';
            } else {
                $message = 'Unable to update password.';
                $messageType = 'danger';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $password = $_POST['delete_password'] ?? '';

    if ($password === '') {
        $message = 'Please enter your password to delete your account.';
        $messageType = 'danger';
    } else {
        $currentUser = $userModel->getUserById($user_id);

        if (!$currentUser || empty($currentUser['password'])) {
            $message = 'Unable to verify your account.';
            $messageType = 'danger';
        } elseif (!password_verify($password, $currentUser['password'])) {
            $message = 'Password is incorrect.';
            $messageType = 'danger';
        } else {
            $deactivated = $userModel->deactivateUser($user_id);

            if ($deactivated) {
                session_unset();
                session_destroy();

                header('Location: login.php');
                exit();
            }

            $message = 'Unable to delete account.';
            $messageType = 'danger';
        }
    }
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Account Settings</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/account_settings.css">

    <style>
        .account-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
        }

        .account-label {
            font-weight: 700;
            color: #374151;
        }

        .account-value {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 14px;
        }

        .section-title {
            font-weight: 800;
            margin-bottom: 4px;
        }

        .section-subtitle {
            color: #6b7280;
            font-size: 0.95rem;
        }
    </style>
</head>

<body>

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
                <label class="visually-hidden" for="lmsMobileSearchAccount">Search courses</label>
                <input id="lmsMobileSearchAccount" class="form-control" type="search" name="q" placeholder="Search courses…" aria-label="Search courses" autocomplete="off">
                <button class="btn btn-lms-primary lms-search-submit-btn" type="submit" aria-label="Search">⌕</button>
            </form>

            <button class="btn btn-lms-ghost d-lg-none flex-shrink-0 px-2 lms-mobile-more-btn" type="button" data-bs-toggle="collapse" data-bs-target="#lmsMobileNavMore" aria-controls="lmsMobileNavMore" aria-expanded="false" aria-label="Account menu">⋯</button>

            <div class="collapse lms-mobile-nav-drawer d-lg-none w-100" id="lmsMobileNavMore">
                <div class="lms-mobile-nav-drawer-inner d-flex flex-column gap-2">
                    <a class="btn btn-lms-ghost w-100 text-start" href="home.php">+ Class</a>

                    <div class="dropdown">
                        <a class="btn dropdown-toggle w-100 text-start" href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="true" aria-expanded="false">
                            👤 <?= e($user['first_name'] ?? '') ?>
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

    <aside class="sidebar lms-sidebar" aria-label="Primary navigation">
        <p class="sidebar-label">Learn</p>

        <a class="is-active active" href="account_settings.php">
            <span class="nav-ico" aria-hidden="true">⌂</span>
            Account Details
        </a>

        <a href="#">
            <span class="nav-ico" aria-hidden="true">▤</span>
            Terms and Condition
        </a>

        <a href="home.php">
            <span class="nav-ico" aria-hidden="true">◎</span>
            Home
        </a>

        <p class="sidebar-label" style="margin-top:1rem">Account</p>

        <a href="settings.php">
            <span class="nav-ico" aria-hidden="true">⚙</span>
            Settings and Privacy
        </a>
    </aside>

    <main class="main lms-main">
        <div class="hero lms-hero">
            <h2>Account Details</h2>
            <p>Manage your profile and password information.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= e($messageType) ?>">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card account-card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <h4 class="section-title">Profile Information</h4>
                            <p class="section-subtitle mb-0">Update your email address and personal information.</p>
                        </div>

                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            Edit Profile
                        </button>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="account-label mb-1">Username</div>
                            <div class="account-value"><?= e($user['username'] ?? '') ?></div>
                        </div>

                        <div class="col-md-6">
                            <div class="account-label mb-1">Email Address</div>
                            <div class="account-value"><?= e($user['email'] ?? '') ?></div>
                        </div>

                        <div class="col-md-6">
                            <div class="account-label mb-1">First Name</div>
                            <div class="account-value"><?= e($user['first_name'] ?? '') ?></div>
                        </div>

                        <div class="col-md-6">
                            <div class="account-label mb-1">Last Name</div>
                            <div class="account-value"><?= e($user['last_name'] ?? '') ?></div>
                        </div>
                    </div>
                </div>

                <div class="card account-card p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h4 class="section-title">Password</h4>
                            <p class="section-subtitle mb-0">Change your password to keep your account secure.</p>
                        </div>

                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            Change Password
                        </button>
                    </div>
                </div>
                <div class="card account-card p-4 mt-4 border-danger">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h4 class="section-title text-danger">Delete Account</h4>
                            <p class="section-subtitle mb-0">
                                Deactivate your account. Your records will remain in the system, but your account will be marked as inactive.
                            </p>
                        </div>

                        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                            Delete Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Username</label>
                        <input type="text" class="form-control" value="<?= e($user['username'] ?? '') ?>" disabled>
                        <small class="text-muted">Username cannot be changed here.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">First Name</label>
                        <input type="text" name="first_name" class="form-control" value="<?= e($user['first_name'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="<?= e($user['last_name'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Old Password</label>
                        <input type="password" name="old_password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">New Password</label>
                        <input type="password" name="new_password" class="form-control" minlength="8" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" minlength="8" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_password" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content"
                onsubmit="return confirm('Are you sure you want to deactivate your account?');">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning">
                        This will mark your account as inactive. You will be logged out after confirmation.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Confirm Password</label>
                        <input type="password" name="delete_password" class="form-control" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" name="delete_account" class="btn btn-danger">
                        Confirm Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/mobile-menu.js"></script>
    <script src="../js/animate.js"></script>
</body>

</html>