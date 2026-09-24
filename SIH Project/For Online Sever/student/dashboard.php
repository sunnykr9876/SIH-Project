<?php
// htdocs/student/dashboard.php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
if (!hasRole('STUDENT')) die("Access Denied.");

$user_id = $_SESSION['user_id'];

// Get user name
$stmt_user = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

// Check Profile & Apps
$stmt_st = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt_st->execute([$user_id]);
$student = $stmt_st->fetch();

$profile_completed = false;
$active_apps = 0;
$approved_apps = 0;

// FIXED: Instantly unlock dashboard if student profile exists!
if ($student && !empty($student['id'])) {
    $profile_completed = true;
    
    // Count active applications (ignoring cancelled ones)
    $stmt_apps = $pdo->prepare("SELECT status FROM applications WHERE student_id = ? AND status NOT IN ('CANCELLED', 'CANCELED')");
    $stmt_apps->execute([$student['id']]);
    $applications = $stmt_apps->fetchAll();
    
    $active_apps = count($applications);
    foreach ($applications as $app) {
        if ($app['status'] === 'APPROVED') $approved_apps++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - ST Scholarship</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <?php include '../includes/header.php'; ?>

    <div class="container py-4 flex-grow-1" style="max-width: 1200px;">
        
        <div class="d-flex align-items-center mb-4">
            <h3 class="fw-bold mb-0" style="color: #003366;">
                Welcome back, <?php echo htmlspecialchars($user['name']); ?>! 👋
            </h3>
        </div>

        <div class="row g-4 mb-4">
            <!-- Profile Status Card -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-4 h-100 border-bottom border-4 <?php echo $profile_completed ? 'border-success' : 'border-warning'; ?>">
                    <div class="card-body p-4 text-center">
                        <div class="rounded-circle d-inline-flex p-3 mb-3 <?php echo $profile_completed ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning'; ?>">
                            <i class="fa-solid <?php echo $profile_completed ? 'fa-user-check' : 'fa-user-pen'; ?> fs-1"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Profile Status</h5>
                        <?php if($profile_completed): ?>
                            <p class="text-success fw-bold small mb-3">Profile Active & Ready</p>
                            <a href="profile.php" class="btn btn-outline-success btn-sm fw-bold rounded-pill w-100">View Profile</a>
                        <?php else: ?>
                            <p class="text-warning text-dark fw-bold small mb-3">Action Required</p>
                            <a href="profile.php" class="btn btn-warning btn-sm fw-bold rounded-pill w-100 text-dark">Complete Profile Now</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Active Applications Card -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-4 h-100 border-bottom border-4 border-primary">
                    <div class="card-body p-4 text-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex p-3 mb-3">
                            <i class="fa-solid fa-file-signature fs-1"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Active Applications</h5>
                        <p class="text-primary fw-bold fs-4 mb-3"><?php echo $active_apps; ?></p>
                        <a href="applications.php" class="btn btn-outline-primary btn-sm fw-bold rounded-pill w-100">Track Status</a>
                    </div>
                </div>
            </div>
            
            <!-- Approved Card -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-4 h-100 border-bottom border-4 border-info">
                    <div class="card-body p-4 text-center">
                        <div class="rounded-circle bg-info bg-opacity-10 text-info d-inline-flex p-3 mb-3">
                            <i class="fa-solid fa-award fs-1"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Approved Schemes</h5>
                        <p class="text-info fw-bold fs-4 mb-3"><?php echo $approved_apps; ?></p>
                        <a href="scholarships.php" class="btn btn-outline-info btn-sm fw-bold rounded-pill w-100">Browse More Schemes</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Smart Unlock Area -->
        <div class="card shadow-sm border-0 rounded-4 bg-white p-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-bolt text-warning me-2"></i> Quick Actions</h5>
            <div class="d-flex flex-wrap gap-3">
                <?php if ($profile_completed): ?>
                    <a href="scholarships.php" class="btn btn-primary fw-bold shadow-sm rounded-pill px-4 py-2"><i class="fa-solid fa-magnifying-glass me-2"></i> Apply for New Scheme</a>
                    <a href="applications.php" class="btn btn-dark fw-bold shadow-sm rounded-pill px-4 py-2"><i class="fa-solid fa-folder-open me-2"></i> Manage My Applications</a>
                <?php else: ?>
                    <button class="btn btn-secondary fw-bold rounded-pill px-4 py-2" disabled><i class="fa-solid fa-lock me-2"></i> Apply for Scheme (Complete Profile First)</button>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>