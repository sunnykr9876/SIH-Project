<?php
// htdocs/login.php
require_once 'config/database.php';
session_start();


// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'STUDENT') { header("Location: student/dashboard.php"); } 
    else { header("Location: admin/dashboard.php"); }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, name, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'STUDENT') {
            $stmt2 = $pdo->prepare("SELECT id, profile_pic FROM students WHERE user_id = ?");
            $stmt2->execute([$user['id']]);
            $student = $stmt2->fetch();
            if ($student) {
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['profile_pic'] = $student['profile_pic'];
            }
            header("Location: student/dashboard.php");
        } else {
            header("Location: admin/dashboard.php");
        }
        exit;
    } else {
        $error = "Invalid email or password. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - ST Scholarship Portal</title>
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
            max-width: 450px;
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
                    <i class="fa-solid fa-circle-user fs-1 mb-2" style="color: var(--primary-gov);"></i>
                    <h4 class="fw-bold mb-0" style="color: var(--primary-gov);">Secure Login</h4>
                </div>
                
                <div class="card-body p-5">
                    <?php if($error): ?>
                        <div class="alert alert-danger shadow-sm border-0 small"><i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small text-uppercase">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fa-solid fa-envelope text-muted"></i></span>
                                <input type="email" name="email" class="form-control bg-light" placeholder="Enter your email" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small text-uppercase d-flex justify-content-between">
                                Password 
                                <a href="#" class="text-decoration-none" style="color: var(--secondary-gov);">Forgot?</a>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fa-solid fa-lock text-muted"></i></span>
                                <input type="password" name="password" id="loginPassword" class="form-control bg-light border-end-0" placeholder="Enter your password" required>
                                <button class="btn btn-light border border-start-0 text-muted" type="button" id="togglePassword">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-gov-primary w-100 py-3 fw-bold fs-5 shadow-sm mb-3">
                            Login securely <i class="fa-solid fa-arrow-right-to-bracket ms-2"></i>
                        </button>

                        <div class="text-center text-muted small">
                            Don't have an account? <a href="register.php" class="fw-bold text-decoration-none" style="color: var(--primary-gov);">Register here</a>
                        </div>
                    </form>
                    
             
                </div>
            </div>
            
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Show Password Script -->
    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('loginPassword');
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
    </script>
</body>
</html>