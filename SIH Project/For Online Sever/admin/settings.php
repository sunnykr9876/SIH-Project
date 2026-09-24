<?php
// htdocs/admin/settings.php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
if (!hasRole('ADMIN') && !hasRole('SUPER_ADMIN')) {
    die("Access Denied.");
}

$user_id = $_SESSION['user_id'];
$success = ''; $error = '';

// Fetch current admin details
$stmt = $pdo->prepare("SELECT name, email, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$admin = $stmt->fetch();

// Handle Password Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verify current password first
    $stmt_pass = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt_pass->execute([$user_id]);
    $user_data = $stmt_pass->fetch();

    if (!password_verify($current_password, $user_data['password_hash'])) {
        $error = "Your current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    } else {
        // Update to new hashed password
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        try {
            $stmt_update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt_update->execute([$new_hash, $user_id]);
            $success = "Security settings updated! Your password has been changed successfully.";
        } catch (Exception $e) {
            $error = "Database error. Could not update password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <?php include '../includes/header.php'; ?>

    <div class="container-fluid py-4 flex-grow-1" style="max-width: 1400px;">
        <div class="row">
            <div class="col-md-3 col-lg-2 mb-4">
                <?php include '../includes/admin_sidebar.php'; ?>
            </div>
            
            <div class="col-md-9 col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <h3 style="color: var(--primary-gov);"><i class="fa-solid fa-gear me-2"></i>System Settings</h3>
                </div>

                <div class="row g-4">
                    <!-- Left Column: Account Info -->
                    <div class="col-lg-5">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white pt-4 pb-3 border-bottom">
                                <h5 class="mb-0" style="color: var(--primary-gov);"><i class="fa-solid fa-user-shield me-2"></i>Administrator Profile</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="text-center mb-4">
                                    <div class="rounded-circle bg-dark text-white d-inline-flex align-items-center justify-content-center shadow-sm mb-3" style="width: 90px; height: 90px; font-size: 2.5rem;">
                                        <i class="fa-solid fa-user-tie"></i>
                                    </div>
                                    <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($admin['name']); ?></h4>
                                    <span class="badge bg-primary mt-2 px-3 py-1"><?php echo htmlspecialchars($admin['role']); ?></span>
                                </div>
                                
                                <ul class="list-group list-group-flush mt-4">
                                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                        <span class="text-muted small fw-bold text-uppercase">Email Address</span>
                                        <strong><?php echo htmlspecialchars($admin['email']); ?></strong>
                                    </li>
                                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                        <span class="text-muted small fw-bold text-uppercase">Account Status</span>
                                        <strong class="text-success"><i class="fa-solid fa-circle-check me-1"></i> Active</strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Security Settings -->
                    <div class="col-lg-7">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white pt-4 pb-3 border-bottom">
                                <h5 class="mb-0" style="color: var(--primary-gov);"><i class="fa-solid fa-lock me-2"></i>Security & Credentials</h5>
                            </div>
                            <div class="card-body p-4">
                                
                                <?php if($success): ?><div class="alert alert-success shadow-sm border-0"><i class="fa-solid fa-check me-2"></i><?php echo $success; ?></div><?php endif; ?>
                                <?php if($error): ?><div class="alert alert-danger shadow-sm border-0"><i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $error; ?></div><?php endif; ?>

                                <form method="POST" action="">
                                    <div class="alert alert-secondary bg-light border-0 shadow-sm small mb-4">
                                        <i class="fa-solid fa-circle-info me-2 text-primary"></i> As an administrator, please ensure your password is secure and updated regularly.
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-uppercase">Current Password <span class="text-danger">*</span></label>
                                        <input type="password" name="current_password" class="form-control bg-light" required>
                                    </div>
                                    
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-uppercase">New Password <span class="text-danger">*</span></label>
                                            <input type="password" name="new_password" class="form-control bg-white" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-uppercase">Confirm New Password <span class="text-danger">*</span></label>
                                            <input type="password" name="confirm_password" class="form-control bg-white" required>
                                        </div>
                                    </div>

                                    <button type="submit" name="update_password" class="btn btn-dark w-100 py-3 fw-bold shadow-sm">
                                        <i class="fa-solid fa-shield-halved me-2"></i> Update Security Credentials
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>