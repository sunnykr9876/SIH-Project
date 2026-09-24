<?php
// htdocs/register.php
require_once 'config/database.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php"); exit;
}

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = "Email is already registered. Please login.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'STUDENT')");
                $stmt->execute([$name, $email, $hashed_password]);
                $user_id = $pdo->lastInsertId();
                $stmt2 = $pdo->prepare("INSERT INTO students (user_id) VALUES (?)");
                $stmt2->execute([$user_id]);
                $pdo->commit();
                $success = "Registration successful! You can now login.";
            } catch (Exception $e) {
                $pdo->rollBack(); $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - ST Scholarship Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .auth-bg {
            background: linear-gradient(rgba(0, 51, 102, 0.8), rgba(0, 51, 102, 0.8)), url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=2070&auto=format&fit=crop') center/cover fixed;
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 550px;
            overflow: hidden;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <?php include 'includes/header.php'; ?>

    <div class="auth-bg flex-grow-1 py-5">
        <div class="container d-flex justify-content-center">
            
            <!-- Floating Dialog Card -->
            <div class="auth-card">
                <div class="card-header border-0 text-center py-4" style="background-color: var(--gov-light);">
                    <i class="fa-solid fa-user-plus fs-1 mb-2" style="color: var(--primary-gov);"></i>
                    <h4 class="fw-bold mb-0" style="color: var(--primary-gov);">Student Registration</h4>
                    <small class="text-muted">Create your official digital profile</small>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <?php if($error): ?>
                        <div class="alert alert-danger shadow-sm border-0 small"><i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success shadow-sm border-0 text-center py-4">
                            <i class="fa-solid fa-circle-check fs-1 mb-3 text-success d-block"></i>
                            <strong><?php echo $success; ?></strong><br><br>
                            <a href="login.php" class="btn btn-gov-primary w-100 py-2">Click here to Login</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold text-muted small text-uppercase">Full Name (As per Aadhaar)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fa-solid fa-user text-muted"></i></span>
                                        <input type="text" name="name" class="form-control bg-light" placeholder="e.g. Rahul Kumar" required>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label fw-bold text-muted small text-uppercase">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fa-solid fa-envelope text-muted"></i></span>
                                        <input type="email" name="email" class="form-control bg-light" placeholder="email@example.com" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small text-uppercase">Create Password</label>
                                    <div class="input-group">
                                        <input type="password" name="password" id="regPassword" class="form-control bg-light border-end-0" required>
                                        <button class="btn btn-light border border-start-0 text-muted toggle-pwd" type="button" data-target="regPassword">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small text-uppercase">Confirm Password</label>
                                    <div class="input-group">
                                        <input type="password" name="confirm_password" id="regConfirmPassword" class="form-control bg-light border-end-0" required>
                                        <button class="btn btn-light border border-start-0 text-muted toggle-pwd" type="button" data-target="regConfirmPassword">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-gov-primary w-100 py-3 fw-bold fs-5 shadow-sm">
                                        Register Account <i class="fa-solid fa-check ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <div class="text-center text-muted small mt-4 pt-3 border-top">
                        Already have an account? <a href="login.php" class="fw-bold text-decoration-none" style="color: var(--primary-gov);">Login here</a>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Show Password Script for Multiple Fields -->
    <script>
        document.querySelectorAll('.toggle-pwd').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    </script>
</body>
</html>