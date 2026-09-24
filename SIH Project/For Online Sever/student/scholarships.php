<?php
// htdocs/student/scholarships.php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
if (!hasRole('STUDENT')) die("Access Denied.");

// Fetch all ACTIVE scholarships
try {
    $stmt = $pdo->query("SELECT * FROM scholarships WHERE status = 'ACTIVE' ORDER BY created_at DESC");
    $scholarships = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Array of distinct themes to give each card a unique shadow and border color
$themes = [
    ['border' => '#0d6efd', 'shadow' => 'rgba(13, 110, 253, 0.15)', 'bg' => 'bg-primary'],   // Blue
    ['border' => '#198754', 'shadow' => 'rgba(25, 135, 84, 0.15)', 'bg' => 'bg-success'],    // Green
    ['border' => '#6f42c1', 'shadow' => 'rgba(111, 66, 193, 0.15)', 'bg' => 'bg-purple'],    // Purple
    ['border' => '#fd7e14', 'shadow' => 'rgba(253, 126, 20, 0.15)', 'bg' => 'bg-orange'],    // Orange
    ['border' => '#dc3545', 'shadow' => 'rgba(220, 53, 69, 0.15)', 'bg' => 'bg-danger']      // Red
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Schemes - Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .scheme-card {
            transition: all 0.3s ease-in-out;
            background: #ffffff;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .scheme-card:hover {
            transform: translateY(-5px);
        }
        .bg-purple { background-color: #6f42c1 !important; }
        .bg-orange { background-color: #fd7e14 !important; }
        .benefits-box {
            background-color: #f8f9fa;
            border-left: 4px solid;
            border-radius: 0 10px 10px 0;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    
    <?php include '../includes/header.php'; ?>
    
    <div class="container py-4 py-md-5 flex-grow-1" style="max-width: 1000px;">
        <div class="mb-4 mb-md-5 text-center text-md-start">
            <h3 class="fw-bold mb-1" style="color: #003366;"><i class="fa-solid fa-layer-group me-2"></i> Available Schemes</h3>
            <p class="text-muted mb-0">Browse and apply for active scholarships and fellowships.</p>
        </div>

        <div class="row g-4">
            <?php if (count($scholarships) > 0): ?>
                <?php foreach ($scholarships as $index => $sch): 
                    // Select a unique theme based on the loop index
                    $theme = $themes[$index % count($themes)];
                ?>
                    <div class="col-12">
                        <!-- Dynamic Unique Shadow and Border applied here -->
                        <div class="card scheme-card border-0" 
                             style="border-top: 5px solid <?php echo $theme['border']; ?> !important; 
                                    box-shadow: 0 8px 20px <?php echo $theme['shadow']; ?>;">
                            
                            <div class="card-body p-4 p-md-5">
                                <!-- Title and Deadline Header -->
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
                                    <div>
                                        <span class="badge <?php echo $theme['bg']; ?> bg-opacity-10 text-dark border mb-2 px-3 py-2 rounded-pill fw-bold" style="border-color: <?php echo $theme['border']; ?> !important;">
                                            <i class="fa-solid fa-tag me-1" style="color: <?php echo $theme['border']; ?>;"></i> <?php echo htmlspecialchars($sch['scheme_type'] ?? 'Scholarship'); ?>
                                        </span>
                                        <h4 class="fw-bold mb-1" style="color: #003366; font-size: clamp(1.25rem, 2.5vw, 1.5rem);"><?php echo htmlspecialchars($sch['title']); ?></h4>
                                    </div>
                                    <div class="text-start text-md-end w-100 w-md-auto">
                                        <span class="d-inline-block d-md-block small text-muted fw-bold text-uppercase me-2 me-md-0">Deadline:</span>
                                        <span class="badge bg-danger rounded-pill px-3 py-2 shadow-sm fs-6">
                                            <i class="fa-regular fa-clock me-1"></i> <?php echo date('d M Y', strtotime($sch['deadline'])); ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- SHORT DESCRIPTION -->
                                <?php if(!empty($sch['short_description'])): ?>
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-muted text-uppercase small mb-2"><i class="fa-solid fa-align-left me-2"></i>Eligibility & Description</h6>
                                        <p class="text-dark" style="font-size: 1rem; line-height: 1.6;">
                                            <?php echo nl2br(htmlspecialchars($sch['short_description'])); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <!-- FINANCIAL BENEFITS BOX -->
                                <?php if(!empty($sch['benefits'])): ?>
                                    <div class="benefits-box p-3 p-md-4 mb-4" style="border-left-color: <?php echo $theme['border']; ?>;">
                                        <h6 class="fw-bold text-uppercase small mb-2" style="color: <?php echo $theme['border']; ?>;">
                                            <i class="fa-solid fa-sack-dollar me-2"></i>Financial Assistance & Benefits
                                        </h6>
                                        <p class="mb-0 text-dark fw-bold" style="font-size: 0.95rem;">
                                            <?php echo nl2br(htmlspecialchars($sch['benefits'])); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <!-- RESPONSIVE FOOTER & BUTTON -->
                                <!-- Notice the flex-column on mobile, flex-md-row on desktop -->
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 pt-4 border-top gap-3">
                                    <div class="text-muted small fw-bold w-100 w-md-auto text-center text-md-start">
                                        <i class="fa-solid fa-calendar-check me-1"></i> Opened: <?php echo date('d M Y', strtotime($sch['start_date'])); ?>
                                    </div>
                                    <!-- Responsive Button: Takes 100% width on phone (w-100), auto width on desktop (w-md-auto) -->
                                    <a href="apply.php?id=<?php echo $sch['id']; ?>" class="btn px-4 py-2 fw-bold shadow-sm w-100 w-md-auto" style="background-color: <?php echo $theme['border']; ?>; color: white; border-radius: 50px;">
                                        Apply Now <i class="fa-solid fa-arrow-right ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="bg-white p-5 rounded-4 shadow-sm">
                        <i class="fa-solid fa-folder-open text-muted mb-3" style="font-size: 4rem;"></i>
                        <h4 class="fw-bold text-muted">No Schemes Available</h4>
                        <p class="text-muted">There are currently no active schemes open for application.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>