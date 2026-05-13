<?php
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

    $result = $classModel->joinClassByCode($user['account_id'], $class_code);

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
  <script src='../js/homescript.js'></script>
  <script src='../js/mobile-menu.js'></script>
  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-light lms-topbar fixed-top px-3">
    <div class="container-fluid gap-2 align-items-center lms-topbar-inner">
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

      <button class="navbar-toggler d-lg-none lms-sidebar-toggler flex-shrink-0" type="button" aria-controls="Primary navigation" aria-expanded="false" aria-label="Open side navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse lms-mobile-nav-drawer d-lg-none w-100" id="lmsMobileNavMore">
        <div class="lms-mobile-nav-drawer-inner d-flex flex-column gap-2">
          <div class="dropdown">
            <a class="btn dropdown-toggle w-100 text-start" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">+ Class</a>
            <ul class="dropdown-menu dropdown-menu-end text-center w-100">
              <li><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClass" style='all:unset; width:100%'><a class="dropdown-item" href="#">Create Class</a></button></li>
              <li><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#joinClass" style='all:unset; width:100%'><a class="dropdown-item" href="#">Join Class</a></button></li>
            </ul>
          </div>
          <div class="dropdown">
            <a class="btn dropdown-toggle w-100 text-start" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">👤 <?php echo htmlspecialchars($user['first_name'] ?? ''); ?></a>
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
            <li><hr class="dropdown-divider"></li>
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
        <h1 class="modal-title fs-5" id="exampleModalLabel">Modal title</h1>
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
          <label for='class_desc'>Class Decription:</label>
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
            <h1 class="modal-title fs-5" id="joinlLabel">Modal title</h1>
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
      <a href="class.php"><span class="nav-ico" aria-hidden="true">▤</span> My courses</a>
      <?php if (isset($_SESSION['classes'])) {
        foreach ($classes as $class) {
          echo '<a href="class.php?class_id=' . $class['class_id'] . '">' . $class['class_name'] . '</a>';
        }
      }
      ?>
      <a href="#"><span class="nav-ico" aria-hidden="true">◎</span> Progress</a>
      <a href="#"><span class="nav-ico" aria-hidden="true">✎</span> Assignments</a>
      <a href="#"><span class="nav-ico" aria-hidden="true">★</span> Certificates</a>
      <p class="sidebar-label" style="margin-top:1rem">Account</p>
      <a href="#"><span class="nav-ico" aria-hidden="true">⚙</span> Settings</a>
    </aside>

    <!-- MAIN -->
    <main class="main lms-main">
      <div class="hero lms-hero">
        <h2>Welcome Back <?php echo isset($user) ? $user['first_name'] . " " . $user['last_name'] : "" ?>👋</h2>
        <p>Continue where you left off—your next lesson is a click away.</p>
        <div class="hero-meta">
          <span class="lms-pill">This week: 3 due dates</span>
          <span class="lms-pill">Streak: 5 days</span>
        </div>
      </div>

      <!-- STATS -->

      <div class="row g-3 mb-4">
        <div class="col-md-3 stat-box">
          <div class="card p-3 text-center shadow-sm">
            <h3>6</h3>
            <p>Active Courses</p>
          </div>
        </div>
        <div class="col-md-3 stat-box">
          <div class="card p-3 text-center shadow-sm">
            <h3>78%</h3>
            <p>Average Progress</p>
          </div>
        </div>
        <div class="col-md-3 stat-box">
          <div class="card p-3 text-center shadow-sm">
            <h3>12</h3>
            <p>Assignments</p>
          </div>
        </div>
        <div class="col-md-3 stat-box">
          <div class="card p-3 text-center shadow-sm">
            <h3>3</h3>
            <p>Certificates</p>
          </div>
        </div>
      </div>

      <!-- Classes -->
      <h4 class="mb-3">📚 My Classes</h4>
      
      <div class="row g-4">
        <?php
          if (isset($_SESSION['classes'])) {
            
            foreach ($classes as $classes) {
              $id = '"'. $classes['class_id'].'"';
              if($classes['class_desc'] ===""){
                $classes['class_desc'] = 'No description';
              }
              echo'<div class="col-md-4 text-center">
                    <div class="card p-3 shadow-sm">
                      <h5>'.$classes['class_name'].'</h5>
                      <p>'.$classes['class_desc'].'</p>
                      <div class="progress mb-2">
                        <div class="progress-bar" style="width: 70%"></div>
                      </div>
                      <a href="class.php?class_id='.$classes['class_id'].'"class="btn btn-primary btn-sm">Continue</a>
                    </div>
                  </div>';
            }
          }
        ?>
      </div>
      </div>
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
          document.querySelectorAll(".card, .stat-box").forEach(el => {
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
