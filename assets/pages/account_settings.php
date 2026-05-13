<?php
session_start();
if (!isset($_SESSION["user_data"])) {
    header('Location: ../../logout.php');
    exit();
    return;
}

$user = $_SESSION['user_data'];

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>LMS Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/account_settings.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-light lms-topbar fixed-top px-3">
        <div class="container-fluid gap-2 align-items-center lms-topbar-inner">
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

            <button class="navbar-toggler d-lg-none lms-sidebar-toggler flex-shrink-0" type="button" aria-controls="Primary navigation" aria-expanded="false" aria-label="Open side navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse lms-mobile-nav-drawer d-lg-none w-100" id="lmsMobileNavMore">
                <div class="lms-mobile-nav-drawer-inner d-flex flex-column gap-2">
                    <a class="btn btn-lms-ghost w-100 text-start" href="home.php">+ Class</a>
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
                    <a class="btn btn-lms-ghost" href="home.php">+ Class</a>
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
    <aside class="sidebar lms-sidebar" aria-label="Primary navigation">
        <p class="sidebar-label">Learn</p>
        <a class="is-active" href="#"><span class="nav-ico" aria-hidden="true">⌂</span> Account Details</a>
        <a href="#"><span class="nav-ico" aria-hidden="true">▤</span> Terms and Condition</a>
        <a href="home.php"><span class="nav-ico" aria-hidden="true">◎</span> Home</a>
        <p class="sidebar-label" style="margin-top:1rem">Account</p>
        <a href="#"><span class="nav-ico" aria-hidden="true">⚙</span> Settings and Privacy</a>
    </aside>

    <!-- MAIN -->
    <main class="main lms-main">
        <div class="hero lms-hero">
            <h2>Account Details</h2>
        </div>
        <div class="row g-3 mb-4">
            <div class="col stat-box">
                <div class="card p-4 shadow-sm">
                    <div class="row">
                        <div class="col-md-6 mb-3 mx-5">
                            <label class="form-label fw-bold">Username</label>
                            <div class='info justify-content-around'><p class="form-control-plaintext border-bottom"><?php echo htmlspecialchars($user['username']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">    
                        <div class="col mb-3 mx-5">
                            <label class="form-label fw-bold">Email Address</label>
                            <div class='info justify-content-around'><p class="form-control-plaintext border-bottom"><?php echo htmlspecialchars($user['email']); ?></p> <a href="#">Edit</a>
                            </div>
                        </div>
                    </div>
                    <div class="row">    
                        <div class="col mb-3 mx-5">
                            <label class="form-label fw-bold">First Name</label>
                            <div class='info justify-content-around'><p class="form-control-plaintext border-bottom"><?php echo htmlspecialchars($user['first_name']); ?></p> <a href="#">Edit</a>
                            </div>
                        </div>
                    </div>
                    <div class="row">    
                        <div class="col mb-3 mx-5">
                            <label class="form-label  fw-bold">Last Name</label>
                            <div class='info justify-content-around'><p class="form-control-plaintext border-bottom"><?php echo htmlspecialchars($user['last_name']); ?></p> <a href="#">Edit</a>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col mb-3 mx-5">
                            <label class="form-label fw-bold">Password </label>
                            <p class="form-control-plaintext border-bottom d-flex justify-content-end  " ><a href="#">Change Password</a></p>
                            
                        </div>
                    </div>
                   
                </div>
            </div>
        </div>
    </main>
    <script>
        const links = document.querySelectorAll('.sidebar a');

        links.forEach(link => {
            link.addEventListener('click', function() {
                links.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });

        function isInViewport(el) {
            const rect = el.getBoundingClientRect();
            return (
                rect.top <= window.innerHeight * 0.85 &&
                rect.bottom >= 0
            );
        }
        window.addEventListener("scroll", handleScroll);
        window.addEventListener("load", handleScroll);

        function handleScroll() {
            document.querySelectorAll(".card, .stat-box").forEach(el => {
                if (isInViewport(el)) {
                    el.classList.add("animate-up");
                }
            });
        }
    </script>
    <script src='../js/mobile-menu.js'></script>

</body>

</html>