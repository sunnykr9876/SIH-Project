<?php
// htdocs/admin/applications.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/ai_engine.php';

requireLogin();
if (!hasRole('ADMIN') && !hasRole('SUPER_ADMIN')) {
    die("Access Denied.");
}

$success = ''; $error = '';

// ==========================================
// HANDLE AI RE-SCAN BUTTON
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rerun_ai'])) {
    $doc_id = $_POST['doc_id'];
    
    $stmt_d = $pdo->prepare("
        SELECT d.*, u.name as student_name, COALESCE(a.snapshot_aadhaar, st.aadhaar_raw) as aadhaar_raw
        FROM application_documents d
        JOIN applications a ON d.application_id = a.id
        JOIN students st ON a.student_id = st.id
        JOIN users u ON st.user_id = u.id
        WHERE d.id = ?
    ");
    $stmt_d->execute([$doc_id]);
    $d = $stmt_d->fetch();
    
    if ($d) {
        $abs_path = __DIR__ . '/../uploads/documents/' . $d['file_path'];
        $exp_no = ($d['document_type'] == 'AADHAAR') ? $d['aadhaar_raw'] : $d['document_number'];
        runAIVerification($pdo, $doc_id, $d['document_type'], $abs_path, $d['student_name'], $exp_no, $d['issue_date']);
        $success = "AI Verification re-run completed for " . str_replace('_', ' ', $d['document_type']) . "!";
    }
}

// Handle Status Update (Strictly Approve, Reject, or Deficiency)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $app_id = $_POST['application_id'];
    $new_status = $_POST['status'];
    $remarks = trim($_POST['admin_remarks']);

    // Validation: ensure status is one of the 3 allowed options
    if (in_array($new_status, ['APPROVED', 'REJECTED', 'DEFICIENCY'])) {
        try {
            $stmt = $pdo->prepare("UPDATE applications SET status = ?, admin_remarks = ? WHERE id = ?");
            $stmt->execute([$new_status, $remarks, $app_id]);
            $success = "Application status updated successfully.";
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Invalid status action selected.";
    }
}

// Fetch all applications prioritizing SNAPSHOT data
try {
    $stmt = $pdo->query("
        SELECT 
            a.*, 
            s.title as scheme_title, 
            u.name as student_name, u.email,
            st.phone, st.gender, st.dob,
            COALESCE(a.snapshot_pic, st.profile_pic) as profile_pic,
            COALESCE(a.snapshot_aadhaar, st.aadhaar_raw) as aadhaar_raw
        FROM applications a
        JOIN scholarships s ON a.scholarship_id = s.id
        JOIN students st ON a.student_id = st.id
        JOIN users u ON st.user_id = u.id
        ORDER BY a.created_at DESC
    ");
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scrutiny Queue - Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .status-APPROVED { background-color: #198754; color: white; }
        .status-REJECTED { background-color: #dc3545; color: white; }
        .status-DEFICIENCY { background-color: #ffc107; color: black; }
        .status-UNDER_SCRUTINY { background-color: #0c5460; color: #ffffff; border: 1px solid #0a4650; } 
        .status-CANCELLED { background-color: #6c757d; color: white; }
        
        .table-hover tbody tr:hover { background-color: #f1f6ff; }
        .modal-xl { max-width: 1200px; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <?php include '../includes/header.php'; ?>

    <div class="container-fluid py-4 flex-grow-1" style="max-width: 1400px;">
        <div class="row">
            <div class="col-md-3 col-lg-2 mb-4">
                <?php if(file_exists('../includes/admin_sidebar.php')) { include '../includes/admin_sidebar.php'; } ?>
            </div>
            
            <div class="col-md-9 col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0 fw-bold" style="color: var(--gov-blue);"><i class="fa-solid fa-magnifying-glass me-2"></i> Application Scrutiny Queue</h4>
                </div>

                <?php if($error): ?><div class="alert alert-danger shadow-sm rounded-4"><?php echo $error; ?></div><?php endif; ?>
                
                <?php if($success): ?>
                    <div id="autoDismissAlert" class="alert alert-success shadow-sm rounded-4"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-uppercase small text-muted">
                                    <tr>
                                        <th class="px-4 py-3">App ID</th>
                                        <th class="py-3">Applicant Name</th>
                                        <th class="py-3">Scheme Applied</th>
                                        <th class="py-3">Status</th>
                                        <th class="text-end px-4 py-3">Action</th>
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
                                                <td class="px-4 py-3 fw-bold text-primary">#<?php echo htmlspecialchars($app['application_number'] ?? $app['id']); ?></td>
                                                <td class="py-3 fw-bold"><?php echo htmlspecialchars($app['student_name']); ?></td>
                                                <td class="py-3 text-muted small"><i class="fa-solid fa-book me-1"></i> <?php echo htmlspecialchars($app['scheme_title']); ?></td>
                                                <td class="py-3">
                                                    <span class="badge status-<?php echo strtoupper($app['status']); ?> rounded-pill px-3 py-1">
                                                        <?php echo str_replace('_', ' ', strtoupper($app['status'])); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end px-4 py-3">
                                                    <button type="button" class="btn btn-outline-primary fw-bold rounded-pill shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#scrutinyModal<?php echo $app['id']; ?>">
                                                        <i class="fa-solid fa-eye me-1"></i> View Details
                                                    </button>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="scrutinyModal<?php echo $app['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                                        
                                                        <div class="modal-header bg-light border-bottom-0 p-4">
                                                            <h5 class="modal-title fw-bold text-dark">
                                                                Scrutiny Window - <span class="text-primary">#<?php echo htmlspecialchars($app['application_number'] ?? $app['id']); ?></span>
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>

                                                        <div class="modal-body p-4 p-md-5 bg-white text-start">
                                                            <div class="row g-5">
                                                                <div class="col-lg-4 border-end">
                                                                    <div class="text-center mb-4">
                                                                        <?php $profile_image = !empty($app['profile_pic']) ? '../uploads/profile_pics/' . htmlspecialchars($app['profile_pic']) : '../assets/images/default-avatar.png'; ?>
                                                                        <img src="<?php echo $profile_image; ?>" alt="Student Photo" class="img-thumbnail rounded-circle shadow-sm mb-3" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #003366;">
                                                                        <h4 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($app['student_name']); ?></h4>
                                                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($app['email']); ?></p>
                                                                    </div>
                                                                    
                                                                    <div class="bg-light p-3 rounded-4 mb-4 text-center">
                                                                        <h6 class="fw-bold text-muted text-uppercase small mb-1">Aadhaar No.</h6>
                                                                        <h5 class="fw-bold text-primary mb-0" style="letter-spacing: 2px;">
                                                                            <?php echo !empty($app['aadhaar_raw']) ? htmlspecialchars($app['aadhaar_raw']) : 'Not Provided'; ?>
                                                                        </h5>
                                                                    </div>

                                                                    <form method="POST" action="" class="card bg-white border border-primary shadow-sm rounded-4">
                                                                        <div class="card-body p-4">
                                                                            <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-gavel me-2"></i> Final Action</h6>
                                                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                                            <div class="mb-3">
                                                                                <label class="form-label fw-bold small text-muted">Update Status</label>
                                                                                <!-- STRICTLY LIMITED TO 3 OPTIONS REQUESTED -->
                                                                                <select name="status" class="form-select fw-bold" required>
                                                                                    <option value="" disabled selected>Select Action...</option>
                                                                                    <option value="APPROVED">APPROVE SCHOLARSHIP</option>
                                                                                    <option value="REJECTED">REJECT APPLICATION</option>
                                                                                    <option value="DEFICIENCY">MARK DEFICIENCY (Send Back)</option>
                                                                                </select>
                                                                            </div>
                                                                            <div class="mb-3">
                                                                                <label class="form-label fw-bold small text-muted">Admin Remarks</label>
                                                                                <textarea name="admin_remarks" class="form-control" rows="2" placeholder="Required if rejecting or marking deficiency..."><?php echo htmlspecialchars($app['admin_remarks'] ?? ''); ?></textarea>
                                                                            </div>
                                                                            <button type="submit" name="update_status" class="btn btn-primary w-100 fw-bold rounded-pill">
                                                                                Submit Decision <i class="fa-solid fa-check ms-1"></i>
                                                                           </button>
                                                                        </div>
                                                                    </form>
                                                                </div>

                                                                <div class="col-lg-8">
                                                                    <h6 class="fw-bold text-uppercase text-muted mb-3"><i class="fa-solid fa-microchip me-2 text-primary"></i> Document AI Analysis</h6>
                                                                    <ul class="list-group shadow-sm mb-5 border-0">
                                                                        <?php if(count($docs) > 0): ?>
                                                                            <?php foreach($docs as $doc): ?>
                                                                                <li class="list-group-item d-flex justify-content-between align-items-center bg-light p-3 border mb-2 rounded-3">
                                                                                    <div>
                                                                                        <a href="../uploads/documents/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="fw-bold text-decoration-none fs-6 text-dark d-block mb-1">
                                                                                            <i class="fa-solid fa-file-pdf text-danger me-1"></i> <?php echo str_replace('_', ' ', $doc['document_type']); ?> <i class="fa-solid fa-arrow-up-right-from-square ms-1 small text-primary"></i>
                                                                                        </a>
                                                                                        <div>
                                                                                            <?php if($doc['ai_status'] == 'PASSED'): ?>
                                                                                                <span class="badge bg-success"><i class="fa-solid fa-check"></i> Passed</span>
                                                                                            <?php elseif($doc['ai_status'] == 'FAILED'): ?>
                                                                                                <span class="badge bg-danger"><i class="fa-solid fa-xmark"></i> Failed</span>
                                                                                            <?php elseif($doc['ai_status'] == 'ERROR'): ?>
                                                                                                <span class="badge bg-warning text-dark"><i class="fa-solid fa-triangle-exclamation"></i> Error</span>
                                                                                            <?php else: ?>
                                                                                                <span class="badge bg-secondary">Pending</span>
                                                                                            <?php endif; ?>
                                                                                            
                                                                                            <small class="text-dark ms-2 fw-bold" style="font-size: 0.85rem;">
                                                                                                <?php echo htmlspecialchars($doc['ai_remarks'] ?? 'No verification data.'); ?>
                                                                                            </small>
                                                                                        </div>
                                                                                    </div>
                                                                                    <form method="POST">
                                                                                        <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                                                                        <button type="submit" name="rerun_ai" class="btn btn-sm btn-outline-primary fw-bold rounded-pill shadow-sm">
                                                                                            <i class="fa-solid fa-rotate-right me-1"></i> Re-Scan
                                                                                        </button>
                                                                                    </form>
                                                                                </li>
                                                                            <?php endforeach; ?>
                                                                        <?php else: ?>
                                                                            <li class="list-group-item text-danger fw-bold">No documents uploaded.</li>
                                                                        <?php endif; ?>
                                                                    </ul>

                                                                    <div class="row g-4">
                                                                        <div class="col-md-6">
                                                                            <h6 class="fw-bold text-uppercase text-muted border-bottom pb-2">Academic Profile</h6>
                                                                            <p class="mb-1 small"><strong>Institution:</strong> <?php echo htmlspecialchars($app['institution_name'] ?? 'N/A'); ?></p>
                                                                            <p class="mb-1 small"><strong>Course:</strong> <?php echo htmlspecialchars($app['current_course'] ?? 'N/A'); ?></p>
                                                                            <p class="mb-1 small"><strong>Previous Marks:</strong> <?php echo htmlspecialchars($app['previous_percentage'] ?? 'N/A'); ?>%</p>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <h6 class="fw-bold text-uppercase text-muted border-bottom pb-2">Bank Data (DBT)</h6>
                                                                            <p class="mb-1 small"><strong>Bank Name:</strong> <?php echo htmlspecialchars($app['bank_name'] ?? 'N/A'); ?></p>
                                                                            <p class="mb-1 small"><strong>Account No:</strong> <?php echo htmlspecialchars($app['account_number'] ?? 'N/A'); ?></p>
                                                                            <p class="mb-1 small"><strong>IFSC Code:</strong> <?php echo htmlspecialchars($app['ifsc_code'] ?? 'N/A'); ?></p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted fw-bold"><i class="fa-solid fa-inbox mb-2 fs-2 d-block"></i> No Applications Found in Queue</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        setTimeout(function() {
            let alertBox = document.getElementById('autoDismissAlert');
            if (alertBox) {
                alertBox.classList.remove('show');
                setTimeout(() => alertBox.remove(), 300);
            }
        }, 5000);
    </script>
</body>
</html>