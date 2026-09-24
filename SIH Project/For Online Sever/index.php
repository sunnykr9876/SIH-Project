<?php
// htdocs/index.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Required for Header Profile Pic
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ST Scholarship Portal - Govt of India</title>
    
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Premium CSS -->
    <style>
        :root {
            --gov-blue: #003366;
            --gov-blue-light: #004b99;
            --gov-gold: #ff9933;
            --gov-green: #138808;
        }
        
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; overflow-x: hidden; }
        
        /* Premium Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--gov-blue) 0%, var(--gov-blue-light) 100%);
            color: white;
            padding: 80px 0 100px 0;
            position: relative;
        }
        .hero-section::after {
            content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 40px;
            background: white; border-top-left-radius: 50% 100%; border-top-right-radius: 50% 100%;
        }
        
        /* Floating Feature Cards */
        .feature-card {
            background: white; border-radius: 15px; padding: 25px; transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05);
            height: 100%;
        }
        .feature-card:hover { transform: translateY(-10px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .feature-icon-box {
            width: 60px; height: 60px; border-radius: 12px; display: flex;
            align-items: center; justify-content: center; font-size: 24px; margin-bottom: 20px;
        }
        
        /* Timeline Roadmap */
        .timeline-step { position: relative; padding-bottom: 30px; }
        .timeline-step:last-child { padding-bottom: 0; }
        .timeline-step::before {
            content: ''; position: absolute; left: 24px; top: 50px; bottom: 0;
            width: 2px; background: #e9ecef; z-index: 1;
        }
        .timeline-step:last-child::before { display: none; }
        .timeline-icon {
            width: 50px; height: 50px; border-radius: 50%; display: flex;
            align-items: center; justify-content: center; font-size: 20px;
            background: white; border: 3px solid var(--gov-blue); color: var(--gov-blue);
            position: relative; z-index: 2; font-weight: bold;
        }
        
        /* Failsafe Gallery (If image breaks, shows beautiful gradient) */
        .gallery-img {
            width: 100%; height: 280px; object-fit: cover; border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08); transition: transform 0.4s ease;
            background: linear-gradient(45deg, #e0eaf5, #f8f9fa);
        }
        .gallery-img:hover { transform: scale(1.03); }
        .gallery-wrapper { overflow: hidden; border-radius: 15px; }

        .btn-gold { background-color: var(--gov-gold); color: white; border: none; }
        .btn-gold:hover { background-color: #e68a2e; color: white; }
    </style>
    <link rel="icon" type="images/png" href="favicon/favicon.png">

</head>
<body class="d-flex flex-column min-vh-100">

    <?php include 'includes/header.php'; ?>

    <!-- ==========================================
         1. PREMIUM HERO SECTION
    =========================================== -->
    <section class="hero-section text-center">
        <div class="container" style="max-width: 900px;">
            <img src="assets/images/logo.png" alt="Gov Logo" style="height: 110px; width: auto; filter: drop-shadow(0px 4px 6px rgba(0,0,0,0.3));" class="mb-4" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg';">
            
            <h1 class="fw-bold mb-4 display-5 text-white" style="letter-spacing: -0.5px;">National ST Scholarship Portal</h1>
            
            <p class="fs-5 mb-5 text-white-50 px-md-4 lh-base">
                A unified, transparent, and secure digital platform empowering Scheduled Tribe students across India with direct financial assistance for higher education.
            </p>
            
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center px-4 px-sm-0">
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role'])): ?>
                    <?php if ($_SESSION['role'] === 'ADMIN' || $_SESSION['role'] === 'SUPER_ADMIN'): ?>
                        <a href="admin/dashboard.php" class="btn btn-light btn-lg fw-bold rounded-pill px-5 text-primary shadow">
                            <i class="fa-solid fa-laptop-file me-2"></i> Go to Admin Dashboard
                        </a>
                    <?php else: ?>
                        <a href="student/dashboard.php" class="btn btn-light btn-lg fw-bold rounded-pill px-5 text-primary shadow">
                            <i class="fa-solid fa-graduation-cap me-2"></i> Go to My Dashboard
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn btn-light btn-lg fw-bold rounded-pill px-5 text-primary shadow">
                        <i class="fa-solid fa-right-to-bracket me-2"></i> Login to Portal
                    </a>
                    <a href="register.php" class="btn btn-gold btn-lg fw-bold rounded-pill px-5 shadow">
                        <i class="fa-solid fa-user-plus me-2"></i> New Registration
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ==========================================
         2. MODERN ABOUT / FEATURES GRID (No cheap images)
    =========================================== -->
    <section class="py-5 bg-white">
        <div class="container py-4" style="max-width: 1200px;">
            <div class="row align-items-center g-5">
                <!-- Left: Text -->
                <div class="col-lg-5">
                    <span class="text-uppercase fw-bold text-primary tracking-wide small">About The Initiative</span>
                    <h2 class="fw-bold mt-2 mb-4" style="color: var(--gov-blue);">Transparent. Paperless. Fast.</h2>
                    <p class="text-muted fs-6 mb-4 lh-lg">
                        The ST Scholarship Portal is a dedicated infrastructure built to eliminate administrative delays. By integrating directly with Aadhaar and utilizing Direct Benefit Transfer (DBT), we ensure that funds reach the right students exactly when they need them.
                    </p>
                    <a href="register.php" class="fw-bold text-decoration-none">Learn about eligibility <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
                
                <!-- Right: Professional Icon Grid -->
                <div class="col-lg-7">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon-box bg-primary bg-opacity-10 text-primary"><i class="fa-solid fa-building-columns"></i></div>
                                <h5 class="fw-bold text-dark">Direct Transfer</h5>
                                <p class="text-muted small mb-0">Funds are transferred securely to your Aadhaar-seeded bank account via DBT.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-card mt-md-4">
                                <div class="feature-icon-box bg-success bg-opacity-10 text-success"><i class="fa-solid fa-leaf"></i></div>
                                <h5 class="fw-bold text-dark">100% Paperless</h5>
                                <p class="text-muted small mb-0">Upload documents online. No need to visit government offices or mail physical copies.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon-box bg-warning bg-opacity-10 text-warning"><i class="fa-solid fa-bell"></i></div>
                                <h5 class="fw-bold text-dark">Live Tracking</h5>
                                <p class="text-muted small mb-0">Track your application from submission to disbursement directly on your dashboard.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-card mt-md-4">
                                <div class="feature-icon-box bg-info bg-opacity-10 text-info"><i class="fa-solid fa-shield-halved"></i></div>
                                <h5 class="fw-bold text-dark">Secure Data</h5>
                                <p class="text-muted small mb-0">Your academic and financial data is encrypted and handled with strict privacy protocols.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==========================================
         3. ELEGANT ROADMAP / TIMELINE
    =========================================== -->
    <section class="py-5" style="background-color: #f4f7fb;">
        <div class="container py-4" style="max-width: 900px;">
            <div class="text-center mb-5">
                <h2 class="fw-bold" style="color: var(--gov-blue);">Application Roadmap</h2>
                <p class="text-muted">Four simple steps to secure your educational funding.</p>
            </div>
            
            <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border">
                
                <div class="d-flex timeline-step">
                    <div class="timeline-icon shadow-sm"><i class="fa-solid fa-user-plus"></i></div>
                    <div class="ms-4 pt-2">
                        <h5 class="fw-bold text-dark mb-1">1. Student Registration</h5>
                        <p class="text-muted small mb-0">Create your account and complete the multi-step profile including academic history and bank details.</p>
                    </div>
                </div>
                
                <div class="d-flex timeline-step">
                    <div class="timeline-icon shadow-sm"><i class="fa-solid fa-file-arrow-up"></i></div>
                    <div class="ms-4 pt-2">
                        <h5 class="fw-bold text-dark mb-1">2. Scheme Application</h5>
                        <p class="text-muted small mb-0">Browse available schemes, apply, and upload necessary certificates (Aadhaar, Caste, Income).</p>
                    </div>
                </div>
                
                <div class="d-flex timeline-step">
                    <div class="timeline-icon shadow-sm"><i class="fa-solid fa-magnifying-glass-chart"></i></div>
                    <div class="ms-4 pt-2">
                        <h5 class="fw-bold text-dark mb-1">3. Institutional Scrutiny</h5>
                        <p class="text-muted small mb-0">Portal administrators will verify your submitted documents. You can fix any deficiencies if requested.</p>
                    </div>
                </div>
                
                <div class="d-flex timeline-step">
                    <div class="timeline-icon shadow-sm"><i class="fa-solid fa-money-bill-transfer text-success"></i></div>
                    <div class="ms-4 pt-2">
                        <h5 class="fw-bold text-success mb-1">4. Approval & Disbursement</h5>
                        <p class="text-muted small mb-0">Once approved, the scholarship amount is credited directly to your bank account.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ==========================================
         4. FAILSAFE GALLERY
    =========================================== -->
    <section class="py-5 bg-white border-top">
        <div class="container py-4" style="max-width: 1200px;">
            <h2 class="fw-bold text-center mb-5" style="color: var(--gov-blue);">Campus & Community</h2>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="gallery-wrapper">
                        <!-- 'onerror' replaces a broken image with a beautiful colored block, so it NEVER looks broken -->
                        <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=800&auto=format&fit=crop" 
                             alt="Students" class="gallery-img" 
                             onerror="this.onerror=null; this.src='https://placehold.co/800x600/003366/FFFFFF?text=Student+Community';">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="gallery-wrapper">
                        <img src="https://images.unsplash.com/photo-1577896851231-70ef18881754?q=80&w=800&auto=format&fit=crop" 
                             alt="Library" class="gallery-img"
                             onerror="this.onerror=null; this.src='https://placehold.co/800x600/004b99/FFFFFF?text=Academic+Resources';">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="gallery-wrapper">
                        <img src="https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?q=80&w=800&auto=format&fit=crop" 
                             alt="Campus" class="gallery-img"
                             onerror="this.onerror=null; this.src='https://placehold.co/800x600/138808/FFFFFF?text=University+Campus';">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

</body>
</html>