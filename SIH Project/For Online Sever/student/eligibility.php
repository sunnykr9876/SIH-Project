<?php
// htdocs/student/eligibility.php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
if (!hasRole('STUDENT')) {
    die("Access Denied.");
}

if (!isset($_GET['id'])) {
    header("Location: scholarships.php");
    exit;
}

$scholarship_id = $_GET['id'];

// 1. Fetch Scholarship Details
$stmt = $pdo->prepare("SELECT * FROM scholarships WHERE id = ?");
$stmt->execute([$scholarship_id]);
$scholarship = $stmt->fetch();

if (!$scholarship) {
    die("Scholarship not found.");
}

// 2. Fetch Student Profile
$stmt2 = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt2->execute([$_SESSION['user_id']]);
$student = $stmt2->fetch();

// Check if profile is complete before running engine
if (empty($student['dob']) || empty($student['category']) || empty($student['annual_income'])) {
    die("<div style='padding:50px; font-family:sans-serif;'><h3>Profile Incomplete</h3><p>You must complete your profile before checking eligibility.</p><a href='profile.php'>Go to Profile</a></div>");
}

// 3. THE ELIGIBILITY ENGINE LOGIC
// Calculate exact age
$dob = new DateTime($student['dob']);
$today = new DateTime('today');
$age = $dob->diff($today)->y;

// Define Hackathon Rules (In a full production build, these would come from the database)
$rules = [
    [
        'name' => 'Category Requirement',
        'expected' => 'Must be Scheduled Tribe (ST)',
        'passed' => ($student['category'] === 'ST')
    ],
    [
        'name' => 'Age Limit',
        'expected' => 'Must be 35 years or younger',
        'passed' => ($age <= 35)
    ],
    [
        'name' => 'Family Income Limit',
        'expected' => 'Annual income must be ₹6,000,000 or less',
        'passed' => ($student['annual_income'] <= 6000000)
    ]
];

// Determine overall status
$is_eligible = true;
foreach ($rules as $rule) {
    if (!$rule['passed']) {
        $is_eligible = false;
        break;
    }
}
$deadlinePassed = (strtotime($scholarship['deadline']) < strtotime(date('Y-m-d')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Eligibility Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-gov sticky-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><i class="fa-solid fa-graduation-cap me-2"></i> ST Scholarship Portal</a>
            <div class="d-flex text-white align-items-center">
                <a href="../logout.php" class="btn btn-sm btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <a href="scholarships.php" class="text-decoration-none mb-3 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Back to Scholarships</a>
                
                <div class="card shadow border-0">
                    <div class="card-header bg-white pt-4 pb-3 text-center border-bottom-0">
                        <h4 class="mb-0" style="color: var(--primary-gov);">Eligibility Result</h4>
                        <p class="text-muted mt-2"><?php echo htmlspecialchars($scholarship['title']); ?></p>
                    </div>
                    
                    <div class="card-body px-5 pb-5">
                        
                        <!-- OVERALL RESULT BADGE -->
                        <div class="text-center mb-5">
                            <?php if($is_eligible): ?>
                                <div class="p-3 bg-success bg-opacity-10 rounded-3 border border-success">
                                    <h2 class="text-success mb-0"><i class="fa-solid fa-circle-check me-2"></i> Eligible</h2>
                                    <p class="text-success mt-2 mb-0">You meet all preliminary requirements for this scheme.</p>
                                </div>
                            <?php else: ?>
                                <div class="p-3 bg-danger bg-opacity-10 rounded-3 border border-danger">
                                    <h2 class="text-danger mb-0"><i class="fa-solid fa-circle-xmark me-2"></i> Not Eligible</h2>
                                    <p class="text-danger mt-2 mb-0">You do not meet one or more criteria based on your profile.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- RULE BREAKDOWN -->
                        <h5 class="mb-4">System Verification Breakdown:</h5>
                        <ul class="list-group list-group-flush mb-4">
                            <!-- Show Calculated Age -->
                            <li class="list-group-item bg-light text-muted mb-2 rounded">
                                <i class="fa-solid fa-calculator me-2"></i> System calculated your age as: <strong><?php echo $age; ?> years</strong>
                            </li>

                            <?php foreach($rules as $rule): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                    <div>
                                        <h6 class="mb-1"><?php echo $rule['name']; ?></h6>
                                        <small class="text-muted"><?php echo $rule['expected']; ?></small>
                                    </div>
                                    <?php if($rule['passed']): ?>
                                        <span class="badge bg-success rounded-pill p-2"><i class="fa-solid fa-check ms-1 me-1"></i> Pass</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-pill p-2"><i class="fa-solid fa-xmark ms-1 me-1"></i> Fail</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <!-- CALL TO ACTION -->
                        <div class="text-center pt-3 border-top">
                            <?php if($deadlinePassed): ?>
                                <button class="btn btn-secondary btn-lg" disabled>Application Closed</button>
                            <?php elseif($is_eligible): ?>
                                <!-- Next Step: Proceed to Application Form -->
                                <a href="apply.php?id=<?php echo $scholarship['id']; ?>" class="btn btn-gov-primary btn-lg px-5">Proceed to Apply</a>
                            <?php else: ?>
                                <p class="text-muted small">Update your <a href="profile.php">Profile</a> if you believe this information is incorrect.</p>
                                <button class="btn btn-gov-primary btn-lg px-5 opacity-50" disabled>Cannot Apply</button>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>