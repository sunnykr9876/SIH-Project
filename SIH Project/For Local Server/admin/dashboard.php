<?php
// htdocs/admin/dashboard.php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
if (!in_array($_SESSION['role'], ['ADMIN', 'SUPER_ADMIN'])) die("Access Denied.");

// Fetch Admin Stats
$total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_schemes = $pdo->query("SELECT COUNT(*) FROM scholarships")->fetchColumn();
$pending_apps = $pdo->query("SELECT COUNT(*) FROM applications WHERE status IN ('SUBMITTED', 'UNDER_SCRUTINY')")->fetchColumn();
$approved_apps = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'APPROVED'")->fetchColumn();

// Fetch 5 most recent applications
$recent_stmt = $pdo->query("
    SELECT a.application_number, a.status, s.title, u.name 
    FROM applications a 
    JOIN scholarships s ON a.scholarship_id = s.id 
    JOIN students st ON a.student_id = st.id 
    JOIN users u ON st.user_id = u.id 
    ORDER BY a.created_at DESC LIMIT 5
");
$recent_apps = $recent_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ST Scholarship</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <?php include '../includes/header.php'; ?>

    <div class="container py-4 flex-grow-1" style="max-width: 1200px;">
        
        <h3 class="fw-bold mb-4" style="color: #003366;">Administrator Dashboard</h3>

        <!-- Responsive Stats Grid -->
        <div class="row g-4 mb-4">
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 rounded-4 h-100 text-center p-3 border-bottom border-4 border-primary">
                    <h2 class="fw-bold text-dark mb-0"><?php echo $pending_apps; ?></h2>
                    <p class="text-muted fw-bold small text-uppercase mb-0 mt-2">Pending Scrutiny</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 rounded-4 h-100 text-center p-3 border-bottom border-4 border-success">
                    <h2 class="fw-bold text-dark mb-0"><?php echo $approved_apps; ?></h2>
                    <p class="text-muted fw-bold small text-uppercase mb-0 mt-2">Approved Apps</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 rounded-4 h-100 text-center p-3 border-bottom border-4 border-warning">
                    <h2 class="fw-bold text-dark mb-0"><?php echo $total_students; ?></h2>
                    <p class="text-muted fw-bold small text-uppercase mb-0 mt-2">Reg. Students</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 rounded-4 h-100 text-center p-3 border-bottom border-4 border-info">
                    <h2 class="fw-bold text-dark mb-0"><?php echo $total_schemes; ?></h2>
                    <p class="text-muted fw-bold small text-uppercase mb-0 mt-2">Total Schemes</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-white border-0 pt-4 pb-2"><h6 class="fw-bold"><i class="fa-solid fa-bolt text-warning me-2"></i> Quick Actions</h6></div>
                    <div class="card-body">
                        <a href="applications.php" class="btn btn-primary w-100 fw-bold rounded-pill mb-3 shadow-sm py-2"><i class="fa-solid fa-laptop-file me-2"></i> Go to Scrutiny Queue</a>
                        <a href="manage_scholarships.php" class="btn btn-outline-dark w-100 fw-bold rounded-pill mb-3 shadow-sm py-2"><i class="fa-solid fa-list me-2"></i> Manage Schemes</a>
                        <a href="reports.php" class="btn btn-outline-secondary w-100 fw-bold rounded-pill shadow-sm py-2"><i class="fa-solid fa-chart-pie me-2"></i> View Reports</a>
                    </div>
                </div>
            </div>

            <!-- Recent Applications Table -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-white border-bottom pt-4 pb-3 px-4"><h6 class="fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Recent Applications</h6></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-nowrap">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 py-3">App ID</th>
                                        <th class="py-3">Student</th>
                                        <th class="py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_apps as $app): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-primary"><?php echo htmlspecialchars($app['application_number']); ?></td>
                                            <td>
                                                <span class="d-block fw-bold text-dark"><?php echo htmlspecialchars($app['name']); ?></span>
                                                <small class="text-muted text-truncate d-inline-block" style="max-width: 150px;"><?php echo htmlspecialchars($app['title']); ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                    $b = 'bg-primary';
                                                    if($app['status'] == 'APPROVED') $b = 'bg-success';
                                                    elseif($app['status'] == 'DEFICIENCY') $b = 'bg-warning text-dark';
                                                    elseif(in_array($app['status'], ['REJECTED', 'CANCELLED'])) $b = 'bg-danger';
                                                ?>
                                                <span class="badge <?php echo $b; ?> rounded-pill"><?php echo str_replace('_', ' ', $app['status']); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>