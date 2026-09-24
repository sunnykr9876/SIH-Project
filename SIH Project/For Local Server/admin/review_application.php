<?php
// htdocs/admin/review_application.php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
if (!hasRole('ADMIN') && !hasRole('SUPER_ADMIN')) die("Access Denied.");

if (!isset($_GET['id'])) { header("Location: applications.php"); exit; }
$app_id = $_GET['id'];
$success = ''; $error = '';

// Handle Admin Decision Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $remarks = trim($_POST['admin_remarks']);
    
    try {
        $stmt = $pdo->prepare("UPDATE applications SET status = ?, admin_remarks = ? WHERE id = ?");
        $stmt->execute([$new_status, $remarks, $app_id]);
        $success = "Application status updated successfully!";
    } catch (Exception $e) {
        $error = "Failed to update status.";
    }
}

// Fetch all Application Data (Fixed with LEFT JOIN and includes ALL new fields)
$stmt = $pdo->prepare("
    SELECT a.*, 
           u.name as student_name, u.email,
           st.phone, st.dob, st.gender, st.category, st.annual_income, st.aadhaar_masked, st.profile_pic,
           s.title as scheme_title, s.scheme_type
    FROM applications a
    LEFT JOIN students st ON a.student_id = st.id
    LEFT JOIN users u ON st.user_id = u.id
    LEFT JOIN scholarships s ON a.scholarship_id = s.id
    WHERE a.id = ?
");
$stmt->execute([$app_id]);
$app = $stmt->fetch();

if (!$app) die("Application not found.");

// Fetch the exactly linked documents for this specific application
$stmt_docs = $pdo->prepare("SELECT * FROM application_documents WHERE application_id = ?");
$stmt_docs->execute([$app_id]);
$documents = $stmt_docs->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Scrutiny - <?php echo $app['application_number']; ?></title>
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
                    <h3 style="color: var(--primary-gov);"><i class="fa-solid fa-magnifying-glass me-2"></i>Scrutiny: <?php echo $app['application_number']; ?></h3>
                    <a href="applications.php" class="btn btn-outline-secondary btn-sm fw-bold"><i class="fa-solid fa-arrow-left me-1"></i> Back to Queue</a>
                </div>

                <?php if($success): ?><div class="alert alert-success shadow-sm border-0"><i class="fa-solid fa-check me-2"></i><?php echo $success; ?></div><?php endif; ?>
                <?php if($error): ?><div class="alert alert-danger shadow-sm border-0"><i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $error; ?></div><?php endif; ?>

                <div class="row g-4">
                    <!-- LEFT COLUMN: Details & Documents -->
                    <div class="col-lg-8">
                        
                        <!-- Student Profile Card with IMAGE -->
                        <div class="card shadow-sm border-0 mb-4 rounded-4">
                            <div class="card-header bg-white pt-3 pb-2 border-bottom">
                                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-user me-2 text-primary"></i>Applicant Profile</h6>
                            </div>
                            <div class="card-body">
                                
                                <!-- Profile Picture & Name Header -->
                                <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                                    <?php 
                                        $pic_url = !empty($app['profile_pic']) 
                                            ? 'view_document.php?type=profile&file=' . urlencode($app['profile_pic']) 
                                            : 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png'; 
                                    ?>
                                    <img src="<?php echo $pic_url; ?>" alt="Profile" class="rounded-circle shadow-sm me-3 border border-3 border-white bg-light" style="width: 85px; height: 85px; object-fit: cover;">
                                    <div>
                                        <h4 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($app['student_name']); ?></h4>
                                        <span class="badge bg-dark mt-1 px-3"><?php echo htmlspecialchars($app['category']); ?> Category</span>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-sm-4 text-muted small fw-bold text-uppercase">Email & Phone</div>
                                    <div class="col-sm-8 fw-bold text-dark"><?php echo htmlspecialchars($app['email']); ?> <br> <i class="fa-solid fa-phone fa-sm text-secondary"></i> <?php echo htmlspecialchars($app['phone']); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4 text-muted small fw-bold text-uppercase">DOB & Gender</div>
                                    <div class="col-sm-8 text-dark"><?php echo date('d M Y', strtotime($app['dob'])); ?> (<?php echo htmlspecialchars($app['gender']); ?>)</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4 text-muted small fw-bold text-uppercase">Annual Family Income</div>
                                    <div class="col-sm-8 text-success fw-bold">₹<?php echo number_format($app['annual_income'], 2); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- NEW: Academic & Financial Verification Card -->
                        <div class="card shadow-sm border-0 mb-4 rounded-4">
                            <div class="card-header bg-white pt-3 pb-2 border-bottom">
                                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-building-columns me-2 text-success"></i>Academic & Financial Verification</h6>
                            </div>
                            <div class="card-body bg-light">
                                <div class="row g-4">
                                    <!-- Academic Info -->
                                    <div class="col-md-6 border-end">
                                        <h6 class="fw-bold text-primary mb-3">Academic Details</h6>
                                        <p class="mb-1"><small class="text-muted fw-bold text-uppercase">Institution:</small><br> <strong><?php echo htmlspecialchars($app['institution_name'] ?? 'N/A'); ?></strong></p>
                                        <p class="mb-1"><small class="text-muted fw-bold text-uppercase">Course:</small><br> <strong><?php echo htmlspecialchars($app['current_course'] ?? 'N/A'); ?></strong></p>
                                        <p class="mb-0"><small class="text-muted fw-bold text-uppercase">Prev. Year Marks:</small><br> <strong class="text-dark"><?php echo htmlspecialchars($app['previous_percentage'] ?? '0'); ?>%</strong></p>
                                    </div>
                                    
                                    <!-- Bank Info -->
                                    <div class="col-md-6">
                                        <h6 class="fw-bold text-success mb-3">DBT Bank Details</h6>
                                        <p class="mb-1"><small class="text-muted fw-bold text-uppercase">Bank Name:</small><br> <strong><?php echo htmlspecialchars($app['bank_name'] ?? 'N/A'); ?></strong></p>
                                        <p class="mb-1"><small class="text-muted fw-bold text-uppercase">Account No:</small><br> <strong><?php echo htmlspecialchars($app['account_number'] ?? 'N/A'); ?></strong></p>
                                        <p class="mb-0"><small class="text-muted fw-bold text-uppercase">IFSC Code:</small><br> <strong class="text-dark"><?php echo htmlspecialchars($app['ifsc_code'] ?? 'N/A'); ?></strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Uploaded Documents Card -->
                        <div class="card shadow-sm border-0 rounded-4">
                            <div class="card-header bg-white pt-3 pb-2 border-bottom d-flex justify-content-between">
                                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-folder-open me-2 text-warning"></i>Uploaded Documents</h6>
                                <span class="badge bg-primary">Proxy Secured</span>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Document Type</th>
                                            <th>Details (No. / Date)</th>
                                            <th class="text-end pe-3">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($documents as $doc): ?>
                                            <tr>
                                                <td class="ps-3 fw-bold text-dark">
                                                    <?php echo str_replace('_', ' ', $doc['document_type']); ?>
                                                </td>
                                                <td>
                                                    <?php if($doc['document_number']): ?>
                                                        <span class="d-block small fw-bold text-dark">No: <?php echo htmlspecialchars($doc['document_number']); ?></span>
                                                        <small class="text-muted">Issued: <?php echo date('d M Y', strtotime($doc['issue_date'])); ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted small">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end pe-3">
                                                    <?php if($doc['file_path']): ?>
                                                        <!-- Uses the Proxy Script to completely bypass the 403 Forbidden Error -->
                                                        <a href="view_document.php?file=<?php echo urlencode($doc['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary fw-bold shadow-sm">
                                                            <i class="fa-solid fa-eye me-1"></i> View
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Missing</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if(empty($documents)): ?>
                                            <tr><td colspan="3" class="text-center py-4 text-muted">No documents found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN: Actions & Scrutiny -->
                    <div class="col-lg-4">
                        <div class="card shadow-sm border-0 h-100 rounded-4">
                            <div class="card-header bg-dark text-white pt-3 pb-2 border-bottom">
                                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-gavel me-2"></i>Scrutiny Action</h6>
                            </div>
                            <div class="card-body p-4 bg-light">
                                
                                <div class="mb-4 text-center">
                                    <small class="text-muted fw-bold text-uppercase d-block mb-1">Current Status</small>
                                    <?php 
                                        $badge = 'bg-secondary';
                                        if($app['status'] == 'APPROVED') $badge = 'bg-success';
                                        elseif($app['status'] == 'DEFICIENCY') $badge = 'bg-warning text-dark';
                                        elseif($app['status'] == 'REJECTED') $badge = 'bg-danger';
                                        elseif(in_array($app['status'], ['SUBMITTED', 'UNDER_SCRUTINY'])) $badge = 'bg-primary';
                                    ?>
                                    <span class="badge <?php echo $badge; ?> fs-5 px-3 py-2 shadow-sm"><?php echo str_replace('_', ' ', $app['status']); ?></span>
                                </div>

                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-uppercase">Update Decision</label>
                                        <select name="status" class="form-select border-secondary shadow-sm fw-bold" required>
                                            <option value="UNDER_SCRUTINY" <?php echo ($app['status']=='UNDER_SCRUTINY')?'selected':'';?>>Under Scrutiny</option>
                                            <option value="DEFICIENCY" <?php echo ($app['status']=='DEFICIENCY')?'selected':'';?>>Deficiency (Require Re-Upload)</option>
                                            <option value="APPROVED" <?php echo ($app['status']=='APPROVED')?'selected':'';?>>Approve Grant</option>
                                            <option value="REJECTED" <?php echo ($app['status']=='REJECTED')?'selected':'';?>>Reject Application</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label small fw-bold text-uppercase">Officer Remarks (Visible to Student)</label>
                                        <textarea name="admin_remarks" class="form-control border-secondary shadow-sm" rows="3" placeholder="e.g., Caste certificate blurry, please re-upload."><?php echo htmlspecialchars($app['admin_remarks']); ?></textarea>
                                    </div>
                                    
                                    <button type="submit" name="update_status" class="btn btn-dark w-100 py-3 fw-bold shadow">
                                        <i class="fa-solid fa-floppy-disk me-2"></i> Save Decision
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