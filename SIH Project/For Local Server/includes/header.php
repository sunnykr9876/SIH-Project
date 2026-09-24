<?php
// htdocs/includes/header.php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$base_path = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/student/') !== false) ? '../' : '';
$current_page = basename($_SERVER['SCRIPT_NAME']);

$header_pic = 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png';
if (isset($_SESSION['user_id']) && isset($pdo) && isset($_SESSION['role']) && $_SESSION['role'] === 'STUDENT') {
    try {
        $stmt_pic = $pdo->prepare("SELECT profile_pic FROM students WHERE user_id = ?");
        $stmt_pic->execute([$_SESSION['user_id']]);
        $pic_data = $stmt_pic->fetch();
        if ($pic_data && !empty($pic_data['profile_pic'])) {
            $header_pic = $base_path . 'uploads/profile_pics/' . $pic_data['profile_pic'];
        }
    } catch (Exception $e) {}
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-2">
    <div class="container-fluid px-4">
        
        <a class="navbar-brand fw-bold d-flex align-items-center" href="<?php echo $base_path; ?>index.php">
            <img src="<?php echo $base_path; ?>assets/images/logo.png" alt="Gov Logo" style="height: 55px; width: auto;" class="me-3" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg';">
            <div class="d-flex flex-column">
                <span class="fs-4" style="color: #003366;">ST Scholarship Portal</span>
                <span class="fw-bold text-secondary" style="font-size: 0.75rem; letter-spacing: 1px;">GOVERNMENT OF INDIA</span>
            </div>
        </a>
        
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center mt-3 mt-lg-0">
                <li class="nav-item me-lg-2"><a class="nav-link fw-bold <?php echo ($current_page == 'index.php') ? 'text-primary' : 'text-dark'; ?>" href="<?php echo $base_path; ?>index.php"><i class="fa-solid fa-house me-1"></i> Home</a></li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    
                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'ADMIN' || $_SESSION['role'] === 'SUPER_ADMIN')): ?>
                        <!-- ADMIN LINKS -->
                        <li class="nav-item ms-1"><a class="nav-link fw-bold <?php echo ($current_page == 'dashboard.php') ? 'text-primary border-bottom border-primary border-2' : 'text-dark'; ?>" href="<?php echo $base_path; ?>admin/dashboard.php">Dashboard</a></li>
                        <li class="nav-item ms-1"><a class="nav-link fw-bold <?php echo ($current_page == 'manage_scholarships.php' || $current_page == 'add_scholarship.php' || $current_page == 'edit_scholarship.php') ? 'text-primary border-bottom border-primary border-2' : 'text-dark'; ?>" href="<?php echo $base_path; ?>admin/manage_scholarships.php">Schemes</a></li>
                        <li class="nav-item ms-1"><a class="nav-link fw-bold <?php echo ($current_page == 'applications.php') ? 'text-primary border-bottom border-primary border-2' : 'text-dark'; ?>" href="<?php echo $base_path; ?>admin/applications.php">Scrutiny</a></li>
                        <li class="nav-item ms-1"><a class="nav-link fw-bold <?php echo ($current_page == 'reports.php') ? 'text-primary border-bottom border-primary border-2' : 'text-dark'; ?>" href="<?php echo $base_path; ?>admin/reports.php">Reports</a></li>
                        <li class="nav-item ms-1"><a class="nav-link fw-bold <?php echo ($current_page == 'settings.php') ? 'text-primary border-bottom border-primary border-2' : 'text-dark'; ?>" href="<?php echo $base_path; ?>admin/settings.php"><i class="fa-solid fa-gear"></i></a></li>
                    <?php else: ?>
                        <!-- STUDENT LINKS -->
                        <li class="nav-item ms-1"><a class="nav-link fw-bold <?php echo ($current_page == 'dashboard.php') ? 'text-primary border-bottom border-primary border-2' : 'text-dark'; ?>" href="<?php echo $base_path; ?>student/dashboard.php">Dashboard</a></li>
                        <li class="nav-item ms-1"><a class="nav-link fw-bold <?php echo ($current_page == 'scholarships.php') ? 'text-primary border-bottom border-primary border-2' : 'text-dark'; ?>" href="<?php echo $base_path; ?>student/scholarships.php">Schemes</a></li>
                        <li class="nav-item ms-1"><a class="nav-link fw-bold <?php echo ($current_page == 'applications.php') ? 'text-primary border-bottom border-primary border-2' : 'text-dark'; ?>" href="<?php echo $base_path; ?>student/applications.php">My Applications</a></li>
                        <li class="nav-item d-lg-none ms-1"><a class="nav-link fw-bold <?php echo ($current_page == 'profile.php') ? 'text-primary border-bottom border-primary border-2' : 'text-dark'; ?>" href="<?php echo $base_path; ?>student/profile.php">My Profile</a></li>
                    <?php endif; ?>
                    
                    <!-- LOGOUT BUTTON -->
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0 mb-2 mb-lg-0">
                        <a class="btn btn-danger btn-sm shadow-sm fw-bold px-3 d-block d-lg-inline-block rounded-pill" href="<?php echo $base_path; ?>logout.php">Logout <i class="fa-solid fa-right-from-bracket ms-1"></i></a>
                    </li>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'STUDENT'): ?>
                        <li class="nav-item ms-lg-3 d-none d-lg-block"><a href="<?php echo $base_path; ?>student/profile.php"><img src="<?php echo $header_pic; ?>" alt="Profile" class="rounded-circle shadow-sm <?php echo ($current_page == 'profile.php') ? 'border border-3 border-primary' : 'border border-2 border-secondary'; ?>" style="width: 45px; height: 45px; object-fit: cover;"></a></li>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0"><a class="btn btn-primary btn-sm shadow-sm px-4 fw-bold d-block d-lg-inline-block rounded-pill" href="<?php echo $base_path; ?>login.php"><i class="fa-solid fa-right-to-bracket me-1"></i> Login</a></li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0 mb-2 mb-lg-0"><a class="btn btn-outline-dark btn-sm shadow-sm px-4 fw-bold d-block d-lg-inline-block rounded-pill" href="<?php echo $base_path; ?>register.php"><i class="fa-solid fa-user-plus me-1"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>