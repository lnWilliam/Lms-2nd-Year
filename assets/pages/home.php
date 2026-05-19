<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
session_start();
require_once "../../vendor/autoload.php";

use App\Controllers\UserController;
use App\Controllers\ClassController;
use App\Helpers\Database;
use App\Models\UserModel;
use App\Models\ClassModel;

$database = Database::getInstance();
$model = new UserModel($database);
$classModel = new ClassModel($database);
$controller = new UserController($model);
$classController = new ClassController($classModel);
$message = null;
$messageType = '';

$user = null;

if (isset($_SESSION['user_data'])) {
  $user = $_SESSION['user_data'];
} else {
  header('Location: login.php');
  $_SESSION['error'] = 'You need to Login';
  exit();
}

$classes = $classModel->getClassesByUser($user['user_id']);
$classCount = is_array($classes) ? count($classes) : 0;
$_SESSION['classes'] = $classes;

if ($_SERVER['REQUEST_METHOD'] == "POST") {

  //  CREATE CLASS
  if (isset($_POST['createClass'])) {

    $code = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);

    $data = [
      'user_id' => $user['user_id'],
      'class_name' => $_POST['class_name'],
      'class_code' => $code,
      'class_desc' => $_POST['class_desc'] ?? ""
    ];

    $result = $classController->validateAndProcessClass($data);

    if ($result['success']) {
      $_SESSION['success'] = "Class created successfully!";
    } else {
      $_SESSION['error'] = implode('<br>', $result['errors']);
    }
  }

  //  JOIN CLASS
  if (isset($_POST['joinClass'])) {

    $class_code = strtoupper(trim($_POST['class_code']));

    $result = $classModel->joinClassByCode($user['user_id'], $class_code);

    if ($result['success']) {
      $_SESSION['success'] = $result['message'];
    } else {
      $_SESSION['error'] = $result['message'];
    }
  }

  header("Location: " . $_SERVER['PHP_SELF']);
  exit();
}

if (isset($_SESSION['success'])) {
  $message = $_SESSION['success'];
  $messageType = 'success';

  unset($_SESSION['success']);
} elseif (isset($_SESSION['error'])) {
  $message = $_SESSION['error'];
  $messageType = 'error';

  unset($_SESSION['error']);
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LMS Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/home.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  <style>

  </style>
</head>

<body>
  <div class="lms-decor-scene" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
  <script src='../js/homescript.js'></script>
  <script src='../js/mobile-menu.js'></script>
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
        <label class="visually-hidden" for="lmsMobileSearchHome">Search courses</label>
        <input id="lmsMobileSearchHome" class="form-control" type="search" name="q" placeholder="Search courses…" aria-label="Search courses" autocomplete="off">
        <button class="btn btn-lms-primary lms-search-submit-btn" type="submit" aria-label="Search">⌕</button>
      </form>

      <button class="btn btn-lms-ghost d-lg-none flex-shrink-0 px-2 lms-mobile-more-btn" type="button" data-bs-toggle="collapse" data-bs-target="#lmsMobileNavMore" aria-controls="lmsMobileNavMore" aria-expanded="false" aria-label="Account and class menu">⋯</button>

      <div class="collapse lms-mobile-nav-drawer d-lg-none w-100" id="lmsMobileNavMore">
        <div class="lms-mobile-nav-drawer-inner d-flex flex-column gap-2">
          <div class="dropdown">
            <a class="btn dropdown-toggle w-100 text-start" href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="true" aria-expanded="false">+ Class</a>
            <ul class="dropdown-menu dropdown-menu-end text-center w-100">
              <li><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClass" style='all:unset; width:100%'><a class="dropdown-item" href="#">Create Class</a></button></li>
              <li><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#joinClass" style='all:unset; width:100%'><a class="dropdown-item" href="#">Join Class</a></button></li>
            </ul>
          </div>
          <div class="dropdown">
            <a class="btn dropdown-toggle w-100 text-start" href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="true" aria-expanded="false">👤 <?php echo htmlspecialchars($user['first_name'] ?? ''); ?></a>
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
          <div class="dropdown">
            <a class="btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">+ Class</a>
            <ul class="dropdown-menu dropdown-menu-end text-center">
              <li><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClass" style='all:unset; width:100%'><a class="dropdown-item" href="#">Create Class</a></button></li>
              <li><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#joinClass" style='all:unset; width:100%'><a class="dropdown-item" href="#">Join Class</a></button></li>
            </ul>
          </div>
          <input class="form-control" type="search" name="q" placeholder="Search courses…" aria-label="Search courses">
          <button class="btn btn-lms-primary" type="submit">Search</button>
        </form>
        <div class="dropdown">
          <a class="btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">👤 <?php echo htmlspecialchars($user['first_name'] ?? ''); ?></a>
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

  <!-- Create Class Modal -->
  <div class="modal fade" id="createClass" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="exampleModalLabel">Create Class</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="createClassForm">
            <div class="input-box">
              <label for=' class_name'>Class Name:</label>
              <input type="text" id='class_name' name="class_name" required>
              <div id="classStatus" class="classStatus"></div>
            </div>
            <div class="input-box">
              <label for='class_desc'>Class Description:</label>
              <input type="text" id='class_desc' name="class_desc">
            </div>


        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" name='createClass' class="btn btn-primary">Create Class</button>
        </div>
        </form>
      </div>
    </div>
  </div>
  <!-- Join Class Modal -->
  <div class="modal fade" id="joinClass" tabindex="-1" aria-labelledby="joinLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="joinLabel">Join Class</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST">
            <input type="text" name="class_code" placeholder="Enter class code" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" name="joinClass" class="btn btn-primary">Join Class</button>
        </div>
        </form>
      </div>
    </div>
  </div>
  <!-- SIDEBAR -->
  <aside class="sidebar lms-sidebar" aria-label="Primary navigation">
    <p class="sidebar-label">Learn</p>
    <a class="is-active" href="home.php"><span class="nav-ico" aria-hidden="true">⌂</span> Home</a>
    <a href="my_courses.php">
      <span class="nav-ico" aria-hidden="true">▤</span>
      My Courses
    </a>
    <?php if (isset($_SESSION['classes'])) {
      foreach ($classes as $class) {
        echo '<a href="class.php?class_id=' . $class['class_id'] . '">' . $class['class_name'] . '</a>';
      }
    }
    ?>
    <a href="archive_classes.php">
      <span class="nav-ico" aria-hidden="true">🗄</span>
      Archived Classes
    </a>
  </aside>

  <!-- MAIN -->
  <main class="main lms-main">
    <div class="hero lms-hero">
      <h2>Welcome Back <?php echo isset($user) ? htmlspecialchars($user['first_name'] . " " . $user['last_name'], ENT_QUOTES, 'UTF-8') : "" ?>👋</h2>
      <p>Continue where you left off—your next lesson is a click away.</p>
      <div class="hero-meta"></div>
    </div>

    <section class="home-overview-grid" aria-label="Dashboard overview">
      <article class="home-mini-card home-mini-card-primary">
        <span class="mini-icon" aria-hidden="true">📚</span>
        <div>
          <strong><?php echo $classCount; ?></strong>
          <small>Active classes</small>
        </div>
      </article>
      <article class="home-mini-card">
        <span class="mini-icon" aria-hidden="true">⚡</span>
        <div>
          <strong>Ready</strong>
          <small>Study space</small>
        </div>
      </article>
      <article class="home-mini-card">
        <span class="mini-icon" aria-hidden="true">🗂️</span>
        <div>
          <strong>Organized</strong>
          <small>Files & lessons</small>
        </div>
      </article>
    </section>

    <!-- Classes -->
    <div class="section-heading-row">
      <div>
        <h4 class="mb-1">📚 My Classes</h4>
        <p class="section-subtitle mb-0">Create a class as a teacher or join one using the + Class menu in the top navbar.</p>
      </div>
    </div>

    <?php if ($classCount === 0): ?>
      <section class="empty-home-shell" aria-label="No classes yet">
        <div class="empty-hero-card">
          <div class="empty-art" aria-hidden="true">
            <span class="art-orbit"></span>
            <span class="art-window"></span>
            <span class="art-book art-book-1"></span>
            <span class="art-book art-book-2"></span>
            <span class="art-pencil"></span>
            <span class="art-spark art-spark-1"></span>
            <span class="art-spark art-spark-2"></span>
          </div>
          <div class="empty-copy">
            <span class="eyebrow">No classes yet</span>
            <h3>Your dashboard is waiting for its first class.</h3>
            <p>You have not created or joined any class yet. Use the <strong>+ Class</strong> button in the top navbar to create or join your first class.</p>
          </div>
        </div>

        <div class="home-static-grid">
          <article class="static-panel tip-panel">
            <div class="panel-kicker">Getting started</div>
            <h5>What happens after you add a class?</h5>
            <ul class="pretty-list">
              <li><span>1</span> Your class appears here as a card.</li>
              <li><span>2</span> You can open lessons, activities, and announcements.</li>
              <li><span>3</span> Teachers can manage submissions and grades.</li>
            </ul>
          </article>

          <article class="static-panel quote-panel">
            <span class="quote-mark" aria-hidden="true">“</span>
            <h5>Build your learning space one class at a time.</h5>
            <p>Small setup first, clean workflow later. No rush, bro.</p>
          </article>

          <article class="static-panel checklist-panel">
            <div class="panel-kicker">Quick checklist</div>
            <label><input type="checkbox" disabled> Prepare class name</label>
            <label><input type="checkbox" disabled> Add short description</label>
            <label><input type="checkbox" disabled> Share or enter class code</label>
          </article>
        </div>
      </section>
    <?php else: ?>
      <div class="row g-4">
        <?php
          foreach ($classes as $classItem) {
            $className = htmlspecialchars($classItem['class_name'] ?? 'Untitled Class', ENT_QUOTES, 'UTF-8');
            $classDesc = trim($classItem['class_desc'] ?? '') === '' ? 'No description' : htmlspecialchars($classItem['class_desc'], ENT_QUOTES, 'UTF-8');
            $classId = htmlspecialchars((string)$classItem['class_id'], ENT_QUOTES, 'UTF-8');
            echo '<div class="col-md-4 text-center">
                    <div class="card p-3 shadow-sm">
                      <h5>'.$className.'</h5>
                      <p>'.$classDesc.'</p>
                      <div class="progress mb-2">
                        <div class="progress-bar" style="width: 70%"></div>
                      </div>
                      <a href="class.php?class_id='.$classId.'" class="btn btn-primary btn-sm">Continue</a>
                    </div>
                  </div>';
          }
        ?>
      </div>
    <?php endif; ?>
  </main>
    <?php if (isset($message)): ?>
      <div class="position-fixed start-50 translate-middle-x" style="top: 30%; z-index: 11;">
        <div id="myToast" class="toast message <?php echo $messageType; ?> border-0">
          <div class="toast-body text-center fw-bold">
            <?php echo $message; ?>
          </div>
        </div>
      </div>

      <script>
        const toast = new bootstrap.Toast(document.getElementById('myToast'), {
          delay: 2000
        });
        toast.show();
      </script>

      <?php unset($message); ?>
    <?php endif; ?>
    <script>
      function isInViewport(el) {
        const rect = el.getBoundingClientRect();
        return (
          rect.top <= window.innerHeight * 0.85 &&
          rect.bottom >= 0
        );
      }

      function handleScroll() {
        document.querySelectorAll(".card, .stat-box, .home-mini-card, .empty-hero-card, .static-panel").forEach(el => {
          if (isInViewport(el)) {
            el.classList.add("animate-up");
          }
        });
      }

      window.addEventListener("scroll", handleScroll);
      window.addEventListener("load", handleScroll);
    </script>
</body>

</html>