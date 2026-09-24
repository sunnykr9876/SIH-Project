<?php
// htdocs/student/apply.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/ai_engine.php';

requireLogin();
if (!hasRole('STUDENT')) {
    die("Access Denied.");
}

if (!isset($_GET['id'])) { 
    header("Location: scholarships.php"); 
    exit; 
}

$scholarship_id = $_GET['id'];

// 1. FETCH LATEST LIVE STUDENT PROFILE (Ensures updated Aadhaar & profile data are always fresh)
$stmt_st = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt_st->execute([$_SESSION['user_id']]);
$st_data = $stmt_st->fetch();

// Strict Check: Profile completion
if (!$st_data || empty($st_data['bank_name']) || empty($st_data['current_course'])) {
    die("
    <!DOCTYPE html>
    <html lang='en'>
    <head><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'></head>
    <body class='bg-light d-flex align-items-center justify-content-center vh-100'>
        <div class='text-center p-5 bg-white shadow-sm rounded-4' style='max-width: 500px;'>
            <h3 class='fw-bold text-dark'>Profile Incomplete</h3>
            <p class='text-muted'>You must complete your Multi-Step Profile before applying.</p>
            <a href='profile.php' class='btn btn-primary fw-bold px-4 rounded-pill mt-2'>Complete Profile Now</a>
        </div>
    </body>
    </html>");
}

$student_id = $st_data['id'];
$student_name = $_SESSION['name']; 
$error = ''; 
$success = '';

// 2. CHECK EXISTING APPLICATIONS (Ignored if CANCELLED or REJECTED to allow fresh re-applications)
$stmt_check = $pdo->prepare("SELECT * FROM applications WHERE student_id = ? AND scholarship_id = ? AND status NOT IN ('CANCELLED', 'REJECTED') ORDER BY id DESC LIMIT 1");
$stmt_check->execute([$student_id, $scholarship_id]);
$existing_app = $stmt_check->fetch();

$is_deficiency = false;
$docs_exist = [];

if ($existing_app) {
    if ($existing_app['status'] === 'DEFICIENCY') {
        $is_deficiency = true;
        $stmt_d = $pdo->prepare("SELECT * FROM application_documents WHERE application_id = ?");
        $stmt_d->execute([$existing_app['id']]);
        while($d = $stmt_d->fetch()) {
            $docs_exist[$d['document_type']] = $d;
        }
    } else {
        die("
        <!DOCTYPE html>
        <html lang='en'>
        <head><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'></head>
        <body class='bg-light d-flex align-items-center justify-content-center vh-100'>
            <div class='text-center p-5 bg-white shadow-sm rounded-4' style='max-width: 500px;'>
                <h3 class='fw-bold text-dark'>Application Locked</h3>
                <p class='text-muted'>You have an active application for this scheme. Status: <strong class='text-primary'>" . htmlspecialchars($existing_app['status']) . "</strong></p>
                <a href='applications.php' class='btn btn-dark fw-bold px-4 rounded-pill mt-2'>View My Applications</a>
            </div>
        </body>
        </html>");
    }
}

$stmt = $pdo->prepare("SELECT title FROM scholarships WHERE id = ?");
$stmt->execute([$scholarship_id]);
$scheme = $stmt->fetch();
if (!$scheme) {
    die("Scholarship scheme not found.");
}

// 3. HANDLE FORM SUBMISSION & TRIGGER AI SCRUTINY
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_application'])) {
    
    $aadhaar_file = $_POST['aadhaar_uploaded_file'] ?? '';
    $caste_file = $_POST['caste_uploaded_file'] ?? '';
    $income_file = $_POST['income_uploaded_file'] ?? '';

    $caste_cert_no = trim($_POST['caste_cert_no']);
    $caste_issue_date = $_POST['caste_issue_date'];
    $income_cert_no = trim($_POST['income_cert_no']);
    $income_issue_date = $_POST['income_issue_date'];

    $inst_name = trim($_POST['institution_name']);
    $curr_course = trim($_POST['current_course']);
    $prev_marks = $_POST['previous_percentage'];
    $bank_name = trim($_POST['bank_name']);
    $acc_no = trim($_POST['account_number']);
    $ifsc = trim($_POST['ifsc_code']);

    if (!$is_deficiency && (empty($aadhaar_file) || empty($caste_file) || empty($income_file))) {
        $error = "Please wait for all documents to finish uploading before submitting.";
    } else {
        try {
            $pdo->beginTransaction();
            $base_dir = '../uploads/documents/';

            function processDocument($temp_file, $prefix, $base_dir, $app_dir, $app_number) {
                if (empty($temp_file) || !file_exists($base_dir . $temp_file)) return '';
                $ext = strtolower(pathinfo($temp_file, PATHINFO_EXTENSION));
                $new_name = $prefix . '.' . $ext;
                $target_path = $app_dir . $new_name;
                
                if (file_exists($target_path)) {
                    $new_name = $prefix . '_' . date('Ymd_His') . '.' . $ext;
                    $target_path = $app_dir . $new_name;
                }
                
                if (rename($base_dir . $temp_file, $target_path)) {
                    chmod($target_path, 0644); 
                    return $app_number . '/' . $new_name; 
                }
                return '';
            }

            if ($is_deficiency) {
                $app_id = $existing_app['id'];
                $app_number = $existing_app['application_number'];
                $app_dir = $base_dir . $app_number . '/';
                @mkdir($app_dir, 0777, true);

                // Update deficiency with fresh snapshot data
                $stmt_upd = $pdo->prepare("UPDATE applications SET institution_name=?, current_course=?, previous_percentage=?, bank_name=?, account_number=?, ifsc_code=?, status='UNDER_SCRUTINY', admin_remarks=NULL, snapshot_pic=?, snapshot_aadhaar=? WHERE id=?");
                $stmt_upd->execute([$inst_name, $curr_course, $prev_marks, $bank_name, $acc_no, $ifsc, $st_data['profile_pic'], $st_data['aadhaar_raw'], $app_id]);

                function upsertDocument($pdo, $app_id, $doc_type, $temp_file, $prefix, $base_dir, $app_dir, $app_number, $cert_no = null, $issue_date = null) {
                    $file_subpath = '';
                    if (!empty($temp_file) && file_exists($base_dir . $temp_file)) {
                        $file_subpath = processDocument($temp_file, $prefix, $base_dir, $app_dir, $app_number);
                    }
                    $stmt_chk = $pdo->prepare("SELECT id, file_path FROM application_documents WHERE application_id = ? AND document_type = ?");
                    $stmt_chk->execute([$app_id, $doc_type]);
                    $existing_doc = $stmt_chk->fetch();

                    if ($existing_doc) {
                        $final_path = !empty($file_subpath) ? $file_subpath : $existing_doc['file_path'];
                        $stmt_doc_upd = $pdo->prepare("UPDATE application_documents SET file_path = ?, document_number = ?, issue_date = ?, ai_status='PENDING' WHERE application_id = ? AND document_type = ?");
                        $stmt_doc_upd->execute([$final_path, $cert_no, $issue_date, $app_id, $doc_type]);
                    } else {
                        $stmt_doc_ins = $pdo->prepare("INSERT INTO application_documents (application_id, document_type, file_path, document_number, issue_date) VALUES (?, ?, ?, ?, ?)");
                        $stmt_doc_ins->execute([$app_id, $doc_type, $file_subpath, $cert_no, $issue_date]);
                    }
                }

                upsertDocument($pdo, $app_id, 'AADHAAR', $aadhaar_file, 'AADHAAR', $base_dir, $app_dir, $app_number);
                upsertDocument($pdo, $app_id, 'CASTE_CERTIFICATE', $caste_file, 'CASTE', $base_dir, $app_dir, $app_number, $caste_cert_no, $caste_issue_date);
                upsertDocument($pdo, $app_id, 'INCOME_CERTIFICATE', $income_file, 'INCOME', $base_dir, $app_dir, $app_number, $income_cert_no, $income_issue_date);

                $success = "Deficiency resolved successfully! Application sent back for AI verification.";

            } else {
                $year = date('Y');
                $stmt_count = $pdo->query("SELECT COUNT(*) FROM applications");
                $count = $stmt_count->fetchColumn() + 1;
                $app_number = "ST-APP-{$year}-" . str_pad($count, 6, "0", STR_PAD_LEFT);
                
                $app_dir = $base_dir . $app_number . '/';
                if (!file_exists($app_dir)) @mkdir($app_dir, 0777, true);

                $final_aadhaar = processDocument($aadhaar_file, 'AADHAAR', $base_dir, $app_dir, $app_number);
                $final_caste = processDocument($caste_file, 'CASTE', $base_dir, $app_dir, $app_number);
                $final_income = processDocument($income_file, 'INCOME', $base_dir, $app_dir, $app_number);

                // Insert new application with status set strictly to UNDER_SCRUTINY and storing profile snapshot
                $stmt_app = $pdo->prepare("INSERT INTO applications (application_number, student_id, scholarship_id, status, institution_name, current_course, previous_percentage, bank_name, account_number, ifsc_code, snapshot_pic, snapshot_aadhaar) VALUES (?, ?, ?, 'UNDER_SCRUTINY', ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_app->execute([$app_number, $student_id, $scholarship_id, $inst_name, $curr_course, $prev_marks, $bank_name, $acc_no, $ifsc, $st_data['profile_pic'], $st_data['aadhaar_raw']]);
                $app_id = $pdo->lastInsertId();

                $stmt_doc = $pdo->prepare("INSERT INTO application_documents (application_id, document_type, file_path, document_number, issue_date) VALUES (?, ?, ?, ?, ?)");
                $stmt_doc->execute([$app_id, 'AADHAAR', $final_aadhaar, NULL, NULL]);
                $stmt_doc->execute([$app_id, 'CASTE_CERTIFICATE', $final_caste, $caste_cert_no, $caste_issue_date]);
                $stmt_doc->execute([$app_id, 'INCOME_CERTIFICATE', $final_income, $income_cert_no, $income_issue_date]);

                $success = "Application submitted! System is verifying documents...";
            }

            // TRIGGER AI VERIFICATION AFTER SAVING DOCUMENTS
            $stmt_get_docs = $pdo->prepare("SELECT * FROM application_documents WHERE application_id = ?");
            $stmt_get_docs->execute([$app_id]);
            $docs_to_scan = $stmt_get_docs->fetchAll();

            foreach($docs_to_scan as $d) {
                $abs_path = __DIR__ . '/../uploads/documents/' . $d['file_path'];
                // Pulls the fresh live profile Aadhaar number for comparison during AI scan
                $exp_no = ($d['document_type'] == 'AADHAAR') ? $st_data['aadhaar_raw'] : $d['document_number'];
                $exp_date = $d['issue_date'];
                
                runAIVerification($pdo, $d['id'], $d['document_type'], $abs_path, $student_name, $exp_no, $exp_date);
            }

            $pdo->commit();
            $success = "Application submitted and AI Verification complete! Application ID: $app_number.";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Database Error: " . $e->getMessage(); 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply - <?php echo htmlspecialchars($scheme['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .upload-box { border: 2px dashed #dee2e6; border-radius: 10px; padding: 25px; background: #f8f9fa; transition: all 0.3s; }
        .upload-box:hover { border-color: #0d6efd; background: #f1f6ff; }
        .status-badge { display: none; font-weight: bold; font-size: 0.9rem; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    
    <?php include '../includes/header.php'; ?>
    
    <div class="container py-4 flex-grow-1" style="max-width: 900px;">
        <h3 style="color: var(--primary-gov);" class="mb-1"><i class="fa-solid fa-file-signature me-2"></i>Application Form</h3>
        <p class="text-muted fw-bold"><?php echo htmlspecialchars($scheme['title']); ?></p>

        <?php if($success): ?>
            <div class="alert alert-success shadow-sm border-0 p-5 text-center rounded-4">
                <i class="fa-solid fa-circle-check fs-1 text-success mb-3 d-block"></i>
                <h4 class="fw-bold"><?php echo $success; ?></h4>
                <a href="applications.php" class="btn btn-primary mt-3 fw-bold px-4 py-2">View My Applications</a>
            </div>
        <?php else: ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger shadow-sm border-start border-4 border-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if($is_deficiency): ?>
                <div class="alert alert-danger shadow-sm border-0 p-4 mb-4 rounded-4">
                    <h5 class="fw-bold text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i> ACTION REQUIRED: Application Deficiency</h5>
                    <p class="mb-0">The Admin has requested corrections. Review the remarks below, update your details, and re-upload any requested document.</p>
                    <hr>
                    <p class="mb-0"><strong>Admin Remarks:</strong> <?php echo htmlspecialchars($existing_app['admin_remarks']); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="applicationForm">
                
                <!-- Academic Details -->
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-header bg-white pt-3 pb-2 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-graduation-cap text-primary me-2"></i>Academic Details</h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary"><i class="fa-solid fa-bolt me-1"></i> Auto-Synced</span>
                    </div>
                    <div class="card-body p-4 row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Current Institution Name *</label>
                            <input type="text" name="institution_name" class="form-control bg-light" required value="<?php echo $is_deficiency ? htmlspecialchars($existing_app['institution_name']) : htmlspecialchars($st_data['current_course'] ?? ''); ?>" placeholder="e.g. Govt. Science College">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Current Course / Year *</label>
                            <input type="text" name="current_course" class="form-control bg-light" required value="<?php echo $is_deficiency ? htmlspecialchars($existing_app['current_course']) : htmlspecialchars($st_data['current_course'] ?? ''); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Previous Year Score (%) *</label>
                            <input type="number" step="0.01" name="previous_percentage" class="form-control bg-light" placeholder="e.g. 85.50" required value="<?php echo $is_deficiency ? htmlspecialchars($existing_app['previous_percentage']) : htmlspecialchars($st_data['class_12_marks'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Bank Details -->
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-header bg-white pt-3 pb-2 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-building-columns text-success me-2"></i>Direct Benefit Transfer (Bank Details)</h6>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="fa-solid fa-bolt me-1"></i> Auto-Synced</span>
                    </div>
                    <div class="card-body p-4 row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Bank Name *</label>
                            <input type="text" name="bank_name" class="form-control bg-light fw-bold" required value="<?php echo $is_deficiency ? htmlspecialchars($existing_app['bank_name']) : htmlspecialchars($st_data['bank_name'] ?? ''); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Account Number *</label>
                            <input type="text" name="account_number" class="form-control bg-light fw-bold" required value="<?php echo $is_deficiency ? htmlspecialchars($existing_app['account_number']) : htmlspecialchars($st_data['account_number'] ?? ''); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">IFSC Code *</label>
                            <input type="text" name="ifsc_code" class="form-control bg-light fw-bold" required value="<?php echo $is_deficiency ? htmlspecialchars($existing_app['ifsc_code']) : htmlspecialchars($st_data['ifsc_code'] ?? ''); ?>" readonly>
                        </div>
                    </div>
                </div>

                <!-- Document Uploads -->
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-header bg-white pt-3 pb-2 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-folder-open text-warning me-2"></i>Document Uploads (AI Analyzed)</h6>
                        <?php if($is_deficiency): ?><span class="badge bg-warning text-dark">Upload only files you wish to replace</span><?php endif; ?>
                    </div>
                    <div class="card-body p-4">
                        <input type="hidden" name="aadhaar_uploaded_file" id="aadhaar_hidden">
                        <input type="hidden" name="caste_uploaded_file" id="caste_hidden">
                        <input type="hidden" name="income_uploaded_file" id="income_hidden">

                        <!-- Aadhaar Upload with Live Profile Preview -->
                        <div class="upload-box mb-4" id="box_aadhaar">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                                <span><i class="fa-solid fa-id-card text-primary me-2"></i> 1. Aadhaar Card (PDF/JPG)</span>
                                <span class="status-badge text-success" id="status_aadhaar"><i class="fa-solid fa-check-circle me-1"></i> Uploaded</span>
                            </label>
                            <div class="alert alert-info py-2 px-3 mb-3 small d-flex justify-content-between align-items-center">
                                <span><i class="fa-solid fa-circle-info me-1"></i> Live Profile Number:</span>
                                <strong class="text-dark" style="letter-spacing: 1px;"><?php echo !empty($st_data['aadhaar_raw']) ? htmlspecialchars($st_data['aadhaar_raw']) : 'Not Provided in Profile'; ?></strong>
                            </div>
                            <input class="form-control file-input" type="file" data-target="aadhaar" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted mt-2 d-block" id="msg_aadhaar"><?php echo $is_deficiency ? 'File already on server. Upload new only to replace.' : 'Select file to upload instantly.'; ?></small>
                        </div>

                        <!-- Caste Upload -->
                        <div class="upload-box mb-4" id="box_caste">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-3">
                                <span><i class="fa-solid fa-certificate text-warning me-2"></i> 2. ST Caste Certificate</span>
                                <span class="status-badge text-success" id="status_caste"><i class="fa-solid fa-check-circle me-1"></i> Uploaded</span>
                            </label>
                            <input class="form-control file-input" type="file" data-target="caste" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted mt-2 d-block" id="msg_caste"><?php echo $is_deficiency ? 'File already on server. Upload new only to replace.' : 'Select file to upload instantly.'; ?></small>
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Certificate Number *</label>
                                    <input type="text" name="caste_cert_no" class="form-control bg-white" required value="<?php echo $is_deficiency ? htmlspecialchars($docs_exist['CASTE_CERTIFICATE']['document_number'] ?? '') : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Date of Issue *</label>
                                    <input type="date" name="caste_issue_date" class="form-control bg-white" required value="<?php echo $is_deficiency ? htmlspecialchars($docs_exist['CASTE_CERTIFICATE']['issue_date'] ?? '') : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Income Upload -->
                        <div class="upload-box mb-2" id="box_income">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-3">
                                <span><i class="fa-solid fa-file-invoice-dollar text-success me-2"></i> 3. Family Income Certificate</span>
                                <span class="status-badge text-success" id="status_income"><i class="fa-solid fa-check-circle me-1"></i> Uploaded</span>
                            </label>
                            <input class="form-control file-input" type="file" data-target="income" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted mt-2 d-block" id="msg_income"><?php echo $is_deficiency ? 'File already on server. Upload new only to replace.' : 'Select file to upload instantly.'; ?></small>
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Certificate Number *</label>
                                    <input type="text" name="income_cert_no" class="form-control bg-white" required value="<?php echo $is_deficiency ? htmlspecialchars($docs_exist['INCOME_CERTIFICATE']['document_number'] ?? '') : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Date of Issue *</label>
                                    <input type="date" name="income_issue_date" class="form-control bg-white" required value="<?php echo $is_deficiency ? htmlspecialchars($docs_exist['INCOME_CERTIFICATE']['issue_date'] ?? '') : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end mb-5">
                    <button type="submit" name="submit_application" id="submitBtn" class="btn <?php echo $is_deficiency ? 'btn-success' : 'btn-dark'; ?> btn-lg px-5 fw-bold shadow-sm" <?php echo $is_deficiency ? '' : 'disabled'; ?>>
                        <?php echo $is_deficiency ? 'Submit Updates' : 'Submit Application'; ?> <i class="fa-solid fa-paper-plane ms-2"></i>
                    </button>
                    <p class="small text-danger mt-2 fw-bold" id="submitWarning" style="display: <?php echo $is_deficiency ? 'none' : 'block'; ?>;">Please upload all 3 documents to enable submission.</p>
                </div>
            </form>

        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isDeficiency = <?php echo $is_deficiency ? 'true' : 'false'; ?>;
            const fileInputs = document.querySelectorAll('.file-input');
            const submitForm = document.getElementById('applicationForm');
            const btn = document.getElementById('submitBtn');

            if(submitForm) {
                submitForm.addEventListener('submit', function() {
                    btn.innerHTML = '<i class="fa-solid fa-microchip fa-spin me-2"></i> AI Verifying Documents (10-15s)...';
                    btn.style.pointerEvents = 'none';
                    btn.style.opacity = '0.7';
                });
            }

            fileInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.files.length === 0) return;
                    const file = this.files[0];
                    const target = this.getAttribute('data-target'); 
                    const msgBox = document.getElementById('msg_' + target);
                    const statusBadge = document.getElementById('status_' + target);
                    const hiddenInput = document.getElementById(target + '_hidden');
                    const uploadBox = document.getElementById('box_' + target);

                    msgBox.innerHTML = '<span class="text-primary fw-bold"><i class="fa-solid fa-spinner fa-spin me-2"></i> Uploading to secure server...</span>';
                    statusBadge.style.display = 'none';

                    const formData = new FormData();
                    formData.append('file', file);

                    fetch('ajax_upload.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            msgBox.innerHTML = '<span class="text-success"><i class="fa-solid fa-shield-check me-1"></i> Securely uploaded.</span>';
                            statusBadge.style.display = 'inline-block';
                            uploadBox.style.borderColor = '#198754'; 
                            uploadBox.style.backgroundColor = '#f4fbf7';
                            hiddenInput.value = data.filename;
                            checkCompletion();
                        } else {
                            msgBox.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> ' + data.message + '</span>';
                            this.value = ''; hiddenInput.value = ''; checkCompletion();
                        }
                    }).catch(error => {
                        msgBox.innerHTML = '<span class="text-danger fw-bold">Network error. Please try again.</span>';
                        this.value = ''; hiddenInput.value = ''; checkCompletion();
                    });
                });
            });

            function checkCompletion() {
                if (isDeficiency) return; 
                const aadhaar = document.getElementById('aadhaar_hidden').value;
                const caste = document.getElementById('caste_hidden').value;
                const income = document.getElementById('income_hidden').value;
                const warning = document.getElementById('submitWarning');

                if (aadhaar !== '' && caste !== '' && income !== '') {
                    btn.disabled = false;
                    btn.classList.replace('btn-dark', 'btn-success');
                    warning.style.display = 'none';
                } else {
                    btn.disabled = true;
                    btn.classList.replace('btn-success', 'btn-dark');
                    warning.style.display = 'block';
                }
            }
        });
    </script>
</body>
</html>