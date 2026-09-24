<?php
// htdocs/student/edit_application.php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/mock_ocr.php';

requireLogin();
if (!hasRole('STUDENT')) die("Access Denied.");
if (!isset($_GET['id'])) { header("Location: scholarships.php"); exit; }

$app_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$error = ''; $success = '';

// 1. Verify this application belongs to the student and is actually in DEFICIENCY status
$stmt = $pdo->prepare("
    SELECT a.*, s.title as scholarship_title, s.deadline 
    FROM applications a 
    JOIN scholarships s ON a.scholarship_id = s.id 
    WHERE a.id = ? AND a.student_id = ? AND a.status = 'DEFICIENCY'
");
$stmt->execute([$app_id, $_SESSION['student_id']]);
$app = $stmt->fetch();

if (!$app) {
    die("<div class='container mt-5 alert alert-danger shadow-sm'>Access Denied. Application not found or not eligible for editing.</div>");
}

$stmt2 = $pdo->prepare("SELECT u.name, s.* FROM users u JOIN students s ON u.id = s.user_id WHERE u.id = ?"); 
$stmt2->execute([$user_id]); 
$student = $stmt2->fetch();

// 2. Handle the Re-submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_data = [
        'caste_cert_no' => trim($_POST['caste_cert_no']), 
        'caste_issue_date' => $_POST['caste_issue_date'], 
        'income_cert_no' => trim($_POST['income_cert_no']), 
        'income_issue_date' => $_POST['income_issue_date']
    ];
    
    $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png']; 
    $docs_to_upload = ['aadhaar_doc' => 'AADHAAR', 'caste_doc' => 'CASTE_CERTIFICATE', 'income_doc' => 'INCOME_CERTIFICATE']; 
    $uploaded_files = [];
    
    foreach ($docs_to_upload as $input_name => $doc_type) {
        if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_ext)) { 
                $uploaded_files[$input_name] = [
                    'tmp_name' => $_FILES[$input_name]['tmp_name'], 
                    'ext' => $ext, 
                    'doc_type' => $doc_type, 
                    'original_name' => $_FILES[$input_name]['name']
                ]; 
            } else { $error = "Invalid file format. Only PDF, JPG, PNG allowed."; break; }
        } else { $error = "Please upload all 3 documents to ensure accurate AI re-verification."; break; }
    }

    if (empty($error)) {
        try {
            $pdo->beginTransaction();
            $application_number = $app['application_number'];
            $app_dir = '../uploads/documents/' . $application_number . '/'; 
            if (!file_exists($app_dir)) mkdir($app_dir, 0777, true); 

            // Clear old AI document records for this application so we can insert the fresh ones
            $pdo->prepare("DELETE FROM application_documents WHERE application_id = ?")->execute([$app_id]);

            foreach ($uploaded_files as $input_name => $file_data) {
                $safe_original_name = preg_replace("/[^a-zA-Z0-9.]/", "", $file_data['original_name']);
                $new_filename = $file_data['doc_type'] . '_RESUBMIT_' . time() . '_' . $safe_original_name;
                $dest = $app_dir . $new_filename;
                
                if (move_uploaded_file($file_data['tmp_name'], $dest)) {
                    // Run the AI Engine on the newly uploaded files
                    $ai_result = process_document_ai($file_data['doc_type'], $dest, $student, $form_data);
                    
                    $db_filepath = $application_number . '/' . $new_filename;
                    $pdo->prepare("INSERT INTO application_documents (application_id, document_type, file_name, ai_status, ai_extracted_data, verification_remarks) VALUES (?, ?, ?, ?, ?, ?)")->execute([$app_id, $file_data['doc_type'], $db_filepath, $ai_result['status'], $ai_result['extracted_json'], $ai_result['remarks']]);
                }
            }
            
            // Update application status back to UNDER_SCRUTINY and clear the deficiency remarks
            $pdo->prepare("UPDATE applications SET status = 'UNDER_SCRUTINY', admin_remarks = 'Student re-uploaded documents. Pending new review.' WHERE id = ?")->execute([$app_id]);
            
            $pdo->commit(); 
            $success = "Deficiency Resolved! Application <strong>$application_number</strong> has been re-submitted for AI Scrutiny.";
        } catch (Exception $e) { 
            $pdo->rollBack(); 
            $error = "Re-submission failed: " . $e->getMessage(); 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fix Application Deficiency</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    <?php include '../includes/header.php'; ?>
    <div class="container py-5 flex-grow-1">
        <div class="row">
            <div class="col-md-3 mb-4"><?php include '../includes/sidebar.php'; ?></div>
            <div class="col-md-9">
                <div class="card shadow-sm border-0 border-top border-warning border-4">
                    <div class="card-header bg-white pt-4 pb-3 border-bottom">
                        <h4 class="mb-1 text-warning text-darken"><i class="fa-solid fa-triangle-exclamation me-2"></i>Resolve Document Deficiency</h4>
                        <p class="text-muted mb-0 small">App No: <strong><?php echo $app['application_number']; ?></strong> | Scheme: <?php echo htmlspecialchars($app['scholarship_title']); ?></p>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Show the Admin's exact reason for rejecting the previous documents -->
                        <div class="alert alert-warning shadow-sm border-warning mb-4">
                            <h6 class="fw-bold"><i class="fa-solid fa-comment-dots me-2"></i>Officer's Remarks:</h6>
                            <p class="mb-0 text-dark"><?php echo htmlspecialchars($app['admin_remarks']); ?></p>
                        </div>

                        <?php if($error): ?><div class="alert alert-danger border-0 shadow-sm"><?php echo $error; ?></div><?php endif; ?>
                        <?php if($success): ?>
                            <div class="alert alert-success fs-5 text-center py-5 border-0 shadow-sm">
                                <i class="fa-solid fa-circle-check fs-1 text-success mb-3 d-block"></i>
                                <?php echo $success; ?>
                                <br><br><a href="dashboard.php" class="btn btn-gov-primary mt-3 px-5 fw-bold">Return to Dashboard</a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="" enctype="multipart/form-data">
                                <p class="text-muted small mb-4">Please carefully re-enter your certificate details and upload clear, readable copies of your documents. The AI engine will perform a fresh scan.</p>
                                
                                <div class="form-section-box mb-4 bg-light border rounded p-4">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--primary-gov);">1. Correct Certificate Details</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="form-label small fw-bold text-uppercase">ST Caste Certificate No. <span class="text-danger">*</span></label><input type="text" name="caste_cert_no" class="form-control bg-white" required></div>
                                        <div class="col-md-6"><label class="form-label small fw-bold text-uppercase">Caste Issue Date <span class="text-danger">*</span></label><input type="date" name="caste_issue_date" class="form-control bg-white" max="<?php echo date('Y-m-d'); ?>" required></div>
                                        <div class="col-md-6"><label class="form-label small fw-bold text-uppercase">Income Certificate No. <span class="text-danger">*</span></label><input type="text" name="income_cert_no" class="form-control bg-white" required></div>
                                        <div class="col-md-6"><label class="form-label small fw-bold text-uppercase">Income Issue Date <span class="text-danger">*</span></label><input type="date" name="income_issue_date" class="form-control bg-white" max="<?php echo date('Y-m-d'); ?>" required></div>
                                    </div>
                                </div>
                                <div class="form-section-box mb-4 bg-light border rounded p-4">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--primary-gov);">2. Upload Corrected Documents (PDF, JPG, PNG)</h6>
                                    <div class="mb-3"><label class="form-label small fw-bold text-uppercase">Aadhaar Card</label><input type="file" name="aadhaar_doc" class="form-control bg-white" accept=".pdf,.jpg,.jpeg,.png" required></div>
                                    <div class="mb-3"><label class="form-label small fw-bold text-uppercase">ST Caste Certificate</label><input type="file" name="caste_doc" class="form-control bg-white" accept=".pdf,.jpg,.jpeg,.png" required></div>
                                    <div class="mb-2"><label class="form-label small fw-bold text-uppercase">Income Certificate</label><input type="file" name="income_doc" class="form-control bg-white" accept=".pdf,.jpg,.jpeg,.png" required></div>
                                </div>
                                <button type="submit" class="btn btn-warning w-100 py-3 fs-5 shadow fw-bold text-dark"><i class="fa-solid fa-rotate me-2"></i> Re-submit Application for AI Review</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
</body>
</html>