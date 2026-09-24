<?php
// htdocs/student/applications.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
if (!hasRole('STUDENT')) die("Access Denied.");

$student_id = null;
$success = ''; $error = '';

try {
    $stmt_st = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt_st->execute([$_SESSION['user_id']]);
    $st = $stmt_st->fetch();
    if($st) {
        $student_id = $st['id'];
    }

    // Handle Cancel Application Request (Only allowed for UNDER_SCRUTINY or DEFICIENCY)
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_app_id'])) {
        $cancel_id = $_POST['cancel_app_id'];
        
        $stmt_verify = $pdo->prepare("SELECT id FROM applications WHERE id = ? AND student_id = ? AND status IN ('UNDER_SCRUTINY', 'DEFICIENCY')");
        $stmt_verify->execute([$cancel_id, $student_id]);
        
        if ($stmt_verify->fetch()) {
            $stmt_cancel = $pdo->prepare("UPDATE applications SET status = 'CANCELLED' WHERE id = ?");
            $stmt_cancel->execute([$cancel_id]);
            $success = "Application cancelled successfully. You can now re-apply if you wish.";
        } else {
            $error = "This application cannot be cancelled because it has already been reviewed or finalized by an Admin.";
        }
    }

    // Fetch Student's Applications
    $applications = [];
    if ($student_id) {
        $stmt = $pdo->prepare("
            SELECT a.*, s.title as scheme_title, s.id as scholarship_id 
            FROM applications a
            JOIN scholarships s ON a.scholarship_id = s.id
            WHERE a.student_id = ?
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$student_id]);
        $applications = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .status-APPROVED { background-color: #198754; color: white; }
        .status-REJECTED { background-color: #dc3545; color: white; }
        .status-DEFICIENCY { background-color: #ffc107; color: black; }
        .status-UNDER_SCRUTINY { background-color: #0c5460; color: white; }
        .status-CANCELLED { background-color: #6c757d; color: white; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <?php include '../includes/header.php'; ?>

    <div class="container py-4 py-md-5 flex-grow-1" style="max-width: 1000px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-0" style="color: #003366;"><i class="fa-solid fa-folder-open me-2"></i> My Applications</h3>
            <a href="scholarships.php" class="btn btn-outline-primary fw-bold rounded-pill shadow-sm">Browse Schemes</a>
        </div>

        <?php if($error): ?><div class="alert alert-danger shadow-sm rounded-4"><?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert alert-success shadow-sm rounded-4"><?php echo $success; ?></div><?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-uppercase small text-muted">
                            <tr>
                                <th class="px-4 py-3">App ID</th>
                                <th class="py-3">Scheme Name</th>
                                <th class="py-3">Date Applied</th>
                                <th class="py-3">Status</th>
                                <th class="text-end px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($applications) > 0): ?>
                                <?php foreach ($applications as $app): 
                                    $stmt_docs = $pdo->prepare("SELECT * FROM application_documents WHERE application_id = ?");
                                    $stmt_docs->execute([$app['id']]);
                                    $docs = $stmt_docs->fetchAll();
                                ?>
                                    <tr>
                                        <td class="px-4 py-3 fw-bold text-primary">#<?php echo htmlspecialchars($app['application_number']); ?></td>
                                        <td class="py-3 fw-bold text-dark"><?php echo htmlspecialchars($app['scheme_title']); ?></td>
                                        <td class="py-3 text-muted small"><?php echo date('d M Y', strtotime($app['created_at'])); ?></td>
                                        <td class="py-3">
                                            <span class="badge status-<?php echo strtoupper($app['status']); ?> rounded-pill px-3 py-1">
                                                <?php echo str_replace('_', ' ', strtoupper($app['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="text-end px-4 py-3">
                                            <a href="print_application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-secondary fw-bold rounded-pill me-1" title="Print Form">
                                                <i class="fa-solid fa-print"></i>
                                            </a>
                                            
                                            <button type="button" class="btn btn-sm btn-primary fw-bold rounded-pill shadow-sm me-1" data-bs-toggle="modal" data-bs-target="#trackModal<?php echo $app['id']; ?>">
                                                Track
                                            </button>

                                            <!-- CANCEL BUTTON: Strictly shown ONLY if status is UNDER_SCRUTINY or DEFICIENCY -->
                                            <?php if ($app['status'] === 'UNDER_SCRUTINY' || $app['status'] === 'DEFICIENCY'): ?>
                                                <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this application?');">
                                                    <input type="hidden" name="cancel_app_id" value="<?php echo $app['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger fw-bold rounded-pill" title="Cancel Application">
                                                        <i class="fa-solid fa-xmark"></i> Cancel
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- TRACK STATUS MODAL -->
                                    <div class="modal fade" id="trackModal<?php echo $app['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow-lg rounded-4">
                                                <div class="modal-header bg-light border-bottom-0 p-4">
                                                    <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-location-crosshairs text-primary me-2"></i> Application Status</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4 bg-white text-start">
                                                    
                                                    <?php if(!empty($app['admin_remarks'])): ?>
                                                        <div class="alert alert-warning rounded-3 border-0 shadow-sm mb-4">
                                                            <strong><i class="fa-solid fa-comment-dots me-1"></i> Admin Remarks:</strong> <?php echo htmlspecialchars($app['admin_remarks']); ?>
                                                            <?php if($app['status'] == 'DEFICIENCY'): ?>
                                                                <br><a href="apply.php?id=<?php echo $app['scholarship_id']; ?>" class="btn btn-sm btn-dark fw-bold rounded-pill mt-2">Fix Deficiency Here</a>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <h6 class="fw-bold text-uppercase text-muted mb-3"><i class="fa-solid fa-microchip me-2"></i> AI Document Scrutiny</h6>
                                                    <ul class="list-group shadow-sm border-0 mb-3">
                                                        <?php foreach($docs as $doc): ?>
                                                            <li class="list-group-item d-flex justify-content-between align-items-center bg-light p-3 border mb-2 rounded-3">
                                                                <span class="fw-bold text-dark"><i class="fa-solid fa-file-pdf text-danger me-2"></i> <?php echo str_replace('_', ' ', $doc['document_type']); ?></span>
                                                                
                                                                <?php if($doc['ai_status'] == 'PASSED'): ?>
                                                                    <span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Verified by AI</span>
                                                                <?php elseif($doc['ai_status'] == 'FAILED' || $doc['ai_status'] == 'ERROR'): ?>
                                                                    <span class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> Not Verified by AI</span>
                                                                <?php else: ?>
                                                                    <span class="text-muted fw-bold"><i class="fa-solid fa-clock me-1"></i> Pending Scan</span>
                                                                <?php endif; ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                    <p class="text-muted small mb-0"><i class="fa-solid fa-circle-info me-1"></i> If a document is flagged by AI, the Admin will manually cross-verify it during scrutiny.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- END MODAL -->

                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted fw-bold">
                                        <i class="fa-solid fa-folder-open mb-2 fs-2 d-block"></i> You haven't applied for any scholarships yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- FIXED: Corrected footer inclusion -->
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>