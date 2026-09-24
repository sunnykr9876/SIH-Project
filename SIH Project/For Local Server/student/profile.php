<?php
// htdocs/student/profile.php
error_reporting(E_ALL);
log_errors: ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
if (!hasRole('STUDENT')) die("Access Denied.");

$user_id = $_SESSION['user_id'];
$success = ''; $error = '';

// =====================================================================
// SILENT DATABASE PATCH (Ensures all required columns exist in Profile DB)
// =====================================================================
try {
    $pdo->exec("ALTER TABLE students 
        ADD COLUMN IF NOT EXISTS current_course VARCHAR(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS bank_name VARCHAR(150) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS account_number VARCHAR(50) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS ifsc_code VARCHAR(20) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS aadhaar_raw VARCHAR(12) DEFAULT NULL");
} catch(PDOException $e) {
    // Silently ignore if columns already exist
}
// =====================================================================

// Check if profile exists, if not, create an empty one to update
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

if (!$student) {
    $pdo->prepare("INSERT INTO students (user_id) VALUES (?)")->execute([$user_id]);
    $stmt->execute([$user_id]);
    $student = $stmt->fetch();
}

// Fetch User Email/Name (Read Only)
$stmt_u = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt_u->execute([$user_id]);
$user_data = $stmt_u->fetch();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    
    // 1. Personal & Family
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $category = $_POST['category'];
    $income = $_POST['annual_income'];
    // FIXED: Mapped directly to aadhaar_raw to match apply.php perfectly
    $aadhaar = trim($_POST['aadhaar_raw']);
    $f_name = trim($_POST['father_name']);
    $f_occ = trim($_POST['father_occupation']);
    $m_name = trim($_POST['mother_name']);
    $m_occ = trim($_POST['mother_occupation']);
    
    // 2. Academic
    $b10_board = trim($_POST['class_10_board']);
    $b10_year = trim($_POST['class_10_year']);
    $b10_marks = trim($_POST['class_10_marks']);
    $b12_board = trim($_POST['class_12_board']);
    $b12_year = trim($_POST['class_12_year']);
    $b12_marks = trim($_POST['class_12_marks']);
    $curr_course = trim($_POST['current_course']);
    
    // 3. Bank Details
    $bank_name = trim($_POST['bank_name']);
    $acc_no = trim($_POST['account_number']);
    $ifsc = strtoupper(trim($_POST['ifsc_code']));

    try {
        $pdo->beginTransaction();

        // Handle Profile Picture Upload safely
        $profile_pic = $student['profile_pic']; // Keep old picture by default
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $new_pic_name = 'PIC_' . $user_id . '_' . time() . '.' . $ext;
                $dest = '../uploads/profile_pics/';
                if (!file_exists($dest)) @mkdir($dest, 0777, true);
                
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest . $new_pic_name)) {
                    $profile_pic = $new_pic_name;
                }
            }
        }

        // Update Database using aadhaar_raw
        $update_sql = "UPDATE students SET 
            phone=?, dob=?, gender=?, category=?, annual_income=?, aadhaar_raw=?, profile_pic=?,
            father_name=?, father_occupation=?, mother_name=?, mother_occupation=?,
            class_10_board=?, class_10_year=?, class_10_marks=?, 
            class_12_board=?, class_12_year=?, class_12_marks=?, current_course=?,
            bank_name=?, account_number=?, ifsc_code=?
            WHERE user_id=?";
            
        $stmt_up = $pdo->prepare($update_sql);
        $stmt_up->execute([
            $phone, $dob, $gender, $category, $income, $aadhaar, $profile_pic,
            $f_name, $f_occ, $m_name, $m_occ,
            $b10_board, $b10_year, $b10_marks,
            $b12_board, $b12_year, $b12_marks, $curr_course,
            $bank_name, $acc_no, $ifsc,
            $user_id
        ]);

        $pdo->commit();
        $success = "Profile updated successfully!";
        
        // Refresh data so form shows latest updates
        $stmt->execute([$user_id]);
        $student = $stmt->fetch();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to update profile: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Scholarship Portal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Select2 CSS for Active Bank Search -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        .step-container { display: none; }
        .step-container.active { display: block; animation: fadeIn 0.4s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .step-indicator { display: flex; justify-content: space-between; margin-bottom: 30px; position: relative; }
        .step-indicator::before { content: ''; position: absolute; top: 50%; left: 0; right: 0; height: 3px; background: #e9ecef; z-index: 1; transform: translateY(-50%); }
        .step-circle { width: 40px; height: 40px; border-radius: 50%; background: #e9ecef; color: #6c757d; display: flex; align-items: center; justify-content: center; font-weight: bold; z-index: 2; border: 4px solid #fff; transition: all 0.3s; }
        .step-circle.active { background: #0d6efd; color: #fff; box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.2); }
        .step-circle.completed { background: #198754; color: #fff; }
        
        /* Make Select2 match Bootstrap 5 */
        .select2-container .select2-selection--single { height: 38px; border: 1px solid #ced4da; border-radius: 0.375rem; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; color: #212529; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <?php include '../includes/header.php'; ?>

    <div class="container py-4 flex-grow-1" style="max-width: 900px;">
        <h3 style="color: #003366;" class="mb-1"><i class="fa-solid fa-user-pen me-2"></i>Complete Your Profile</h3>
        <p class="text-muted fw-bold">Multi-Step Registration Form</p>

        <?php if($success): ?>
            <div class="alert alert-success shadow-sm border-0"><i class="fa-solid fa-check-circle me-2"></i><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger shadow-sm border-0"><i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5">
            
            <!-- Step Indicators -->
            <div class="step-indicator px-2 px-md-5">
                <div class="step-circle active" id="indicator-1">1</div>
                <div class="step-circle" id="indicator-2">2</div>
                <div class="step-circle" id="indicator-3">3</div>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data" id="profileForm">
                
                <!-- ==========================================
                    STEP 1: PERSONAL & FAMILY DETAILS 
                =========================================== -->
                <div class="step-container active" id="step-1">
                    <h5 class="fw-bold text-primary mb-4 border-bottom pb-2">Step 1: Personal & Family Details</h5>
                    
                    <div class="row g-4">
                        <div class="col-md-12 text-center mb-3">
                            <label class="form-label fw-bold d-block">Profile Picture</label>
                            <?php $pic = !empty($student['profile_pic']) ? '../uploads/profile_pics/' . $student['profile_pic'] : '../assets/images/default_avatar.png'; ?>
                            <img src="<?php echo $pic; ?>" class="rounded-circle border border-3 border-primary shadow-sm mb-3" style="width: 120px; height: 120px; object-fit: cover;" id="picPreview">
                            <input type="file" name="profile_pic" class="form-control form-control-sm mx-auto" style="max-width: 300px;" accept="image/*" onchange="document.getElementById('picPreview').src = window.URL.createObjectURL(this.files[0])">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Full Name (From Reg.)</label>
                            <input type="text" class="form-control bg-light fw-bold" value="<?php echo htmlspecialchars($user_data['name']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Mobile Number *</label>
                            <input type="text" name="phone" class="form-control" pattern="\d{10}" title="Enter 10 digit mobile number" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Date of Birth *</label>
                            <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($student['dob'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Gender *</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select...</option>
                                <option value="Male" <?php echo ($student['gender']??'')=='Male'?'selected':'';?>>Male</option>
                                <option value="Female" <?php echo ($student['gender']??'')=='Female'?'selected':'';?>>Female</option>
                                <option value="Other" <?php echo ($student['gender']??'')=='Other'?'selected':'';?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Category *</label>
                            <input type="hidden" name="category" value="ST">
                            <input type="text" class="form-control bg-light fw-bold text-primary" value="ST (Scheduled Tribe)" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Aadhaar Number *</label>
                            <!-- FIXED: Name attribute updated to aadhaar_raw to match apply.php -->
                            <input type="text" name="aadhaar_raw" class="form-control" placeholder="12-digit Number" pattern="\d{12}" title="Must be exactly 12 digits" value="<?php echo htmlspecialchars($student['aadhaar_raw'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Annual Family Income (₹) *</label>
                            <input type="number" name="annual_income" class="form-control" value="<?php echo htmlspecialchars($student['annual_income'] ?? ''); ?>" required>
                        </div>

                        <!-- Family Details -->
                        <div class="col-12 mt-4"><h6 class="fw-bold text-dark border-bottom pb-2">Family Details</h6></div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Father's Name *</label>
                            <input type="text" name="father_name" class="form-control" value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Father's Occupation</label>
                            <input type="text" name="father_occupation" class="form-control" placeholder="e.g. Farmer, Teacher" value="<?php echo htmlspecialchars($student['father_occupation'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Mother's Name *</label>
                            <input type="text" name="mother_name" class="form-control" value="<?php echo htmlspecialchars($student['mother_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Mother's Occupation</label>
                            <input type="text" name="mother_occupation" class="form-control" placeholder="e.g. Homemaker" value="<?php echo htmlspecialchars($student['mother_occupation'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="text-end mt-5">
                        <button type="button" class="btn btn-primary px-5 fw-bold" onclick="nextStep(2)">Next: Academic Details <i class="fa-solid fa-arrow-right ms-2"></i></button>
                    </div>
                </div>

                <!-- ==========================================
                    STEP 2: ACADEMIC DETAILS 
                =========================================== -->
                <div class="step-container" id="step-2">
                    <h5 class="fw-bold text-primary mb-4 border-bottom pb-2">Step 2: Academic Record</h5>
                    
                    <div class="row g-4">
                        <!-- 10th Standard -->
                        <div class="col-12"><h6 class="fw-bold text-dark bg-light p-2 rounded">Class 10th (Matriculation)</h6></div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Board Name *</label>
                            <input type="text" name="class_10_board" class="form-control" placeholder="e.g. CBSE, State Board" value="<?php echo htmlspecialchars($student['class_10_board'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Passing Year *</label>
                            <input type="number" name="class_10_year" class="form-control" placeholder="YYYY" min="1990" max="<?php echo date('Y');?>" value="<?php echo htmlspecialchars($student['class_10_year'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Marks (%) *</label>
                            <input type="number" step="0.01" name="class_10_marks" class="form-control" placeholder="e.g. 85.50" value="<?php echo htmlspecialchars($student['class_10_marks'] ?? ''); ?>" required>
                        </div>

                        <!-- 12th Standard -->
                        <div class="col-12 mt-4"><h6 class="fw-bold text-dark bg-light p-2 rounded">Class 12th (Intermediate/Diploma)</h6></div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Board Name *</label>
                            <input type="text" name="class_12_board" class="form-control" placeholder="e.g. CBSE, State Board" value="<?php echo htmlspecialchars($student['class_12_board'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Passing Year *</label>
                            <input type="number" name="class_12_year" class="form-control" placeholder="YYYY" min="1990" max="<?php echo date('Y');?>" value="<?php echo htmlspecialchars($student['class_12_year'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Marks (%) *</label>
                            <input type="number" step="0.01" name="class_12_marks" class="form-control" placeholder="e.g. 78.20" value="<?php echo htmlspecialchars($student['class_12_marks'] ?? ''); ?>" required>
                        </div>

                        <!-- Current Course -->
                        <div class="col-12 mt-4"><h6 class="fw-bold text-dark bg-light p-2 rounded">Current Enrollment</h6></div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Current Course Name & Year *</label>
                            <input type="text" name="current_course" class="form-control" placeholder="e.g. B.Tech Computer Science (2nd Year)" value="<?php echo htmlspecialchars($student['current_course'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-5">
                        <button type="button" class="btn btn-outline-secondary px-4 fw-bold" onclick="prevStep(1)"><i class="fa-solid fa-arrow-left me-2"></i> Previous</button>
                        <button type="button" class="btn btn-primary px-5 fw-bold" onclick="nextStep(3)">Next: Bank Details <i class="fa-solid fa-arrow-right ms-2"></i></button>
                    </div>
                </div>

                <!-- ==========================================
                    STEP 3: BANK DETAILS (ACTIVE SEARCH)
                =========================================== -->
                <div class="step-container" id="step-3">
                    <h5 class="fw-bold text-success mb-4 border-bottom pb-2">Step 3: Direct Benefit Transfer (Bank Info)</h5>
                    
                    <div class="alert alert-warning small fw-bold border-0 shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i> Ensure your bank account is linked to your ID to receive funds!</div>

                    <div class="row g-4 mt-1">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Select Bank Name (Type to Search) *</label>
                            <select name="bank_name" id="bankSearch" class="form-select w-100" required>
                                <option value="">Select or type bank name...</option>
                                <?php 
                                    $banks = [
                                        "State Bank of India (SBI)", "Punjab National Bank (PNB)", "HDFC Bank", 
                                        "ICICI Bank", "Axis Bank", "Bank of Baroda", "Canara Bank", 
                                        "Union Bank of India", "Bank of India", "Indian Bank", 
                                        "Central Bank of India", "IDBI Bank", "UCO Bank", "Kotak Mahindra Bank",
                                        "IndusInd Bank", "Yes Bank", "Federal Bank", "South Indian Bank"
                                    ];
                                    $saved_bank = $student['bank_name'] ?? '';
                                    foreach($banks as $b) {
                                        $sel = ($saved_bank === $b) ? 'selected' : '';
                                        echo "<option value=\"$b\" $sel>$b</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Account Number *</label>
                            <input type="text" name="account_number" id="acc_no" class="form-control fw-bold text-primary fs-5" pattern="\d+" title="Only Numbers Allowed!" placeholder="e.g. 302011..." value="<?php echo htmlspecialchars($student['account_number'] ?? ''); ?>" oninput="this.value = this.value.replace(/[^0-9]/g, ''); validateAccount();" required>
                            <small class="text-muted">Numbers only.</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Confirm Account Number *</label>
                            <input type="text" id="confirm_acc_no" class="form-control fw-bold fs-5" pattern="\d+" placeholder="Re-type Account Number" value="<?php echo htmlspecialchars($student['account_number'] ?? ''); ?>" oninput="this.value = this.value.replace(/[^0-9]/g, ''); validateAccount();" required>
                            <small id="acc_msg" class="fw-bold mt-1 d-block"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">IFSC Code *</label>
                            <input type="text" name="ifsc_code" class="form-control text-uppercase fw-bold" placeholder="e.g. SBIN0001234" pattern="[A-Za-z0-9]+" title="Letters and Numbers only" value="<?php echo htmlspecialchars($student['ifsc_code'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-5 pt-3 border-top">
                        <button type="button" class="btn btn-outline-secondary px-4 fw-bold" onclick="prevStep(2)"><i class="fa-solid fa-arrow-left me-2"></i> Previous</button>
                        <button type="submit" name="update_profile" id="finalSubmitBtn" class="btn btn-success btn-lg px-5 fw-bold shadow">Save Complete Profile <i class="fa-solid fa-floppy-disk ms-2"></i></button>
                    </div>
                </div>

            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#bankSearch').select2({
                placeholder: "Search for your bank...",
                allowClear: true
            });
        });

        function validateAccount() {
            const acc1 = document.getElementById('acc_no').value;
            const acc2 = document.getElementById('confirm_acc_no').value;
            const msg = document.getElementById('acc_msg');
            const btn = document.getElementById('finalSubmitBtn');

            if (acc2.length > 0) {
                if (acc1 === acc2) {
                    msg.innerHTML = '<i class="fa-solid fa-check text-success"></i> Accounts match';
                    msg.className = "text-success fw-bold mt-1 d-block";
                    document.getElementById('confirm_acc_no').classList.remove('is-invalid');
                    document.getElementById('confirm_acc_no').classList.add('is-valid');
                    btn.disabled = false;
                } else {
                    msg.innerHTML = '<i class="fa-solid fa-xmark text-danger"></i> Accounts do not match!';
                    msg.className = "text-danger fw-bold mt-1 d-block";
                    document.getElementById('confirm_acc_no').classList.add('is-invalid');
                    document.getElementById('confirm_acc_no').classList.remove('is-valid');
                    btn.disabled = true;
                }
            } else {
                msg.innerHTML = '';
                document.getElementById('confirm_acc_no').classList.remove('is-invalid', 'is-valid');
                btn.disabled = false;
            }
        }

        function nextStep(step) {
            const currentStepDiv = document.getElementById('step-' + (step - 1));
            const inputs = currentStepDiv.querySelectorAll('input[required], select[required]');
            let isValid = true;
            inputs.forEach(input => { if (!input.checkValidity()) { input.reportValidity(); isValid = false; } });
            
            if (!isValid) return;

            document.querySelectorAll('.step-container').forEach(el => el.classList.remove('active'));
            document.getElementById('step-' + step).classList.add('active');
            
            document.getElementById('indicator-' + (step - 1)).classList.remove('active');
            document.getElementById('indicator-' + (step - 1)).classList.add('completed');
            document.getElementById('indicator-' + step).classList.add('active');
        }

        function prevStep(step) {
            document.querySelectorAll('.step-container').forEach(el => el.classList.remove('active'));
            document.getElementById('step-' + step).classList.add('active');
            
            document.getElementById('indicator-' + (step + 1)).classList.remove('active');
            document.getElementById('indicator-' + step).classList.remove('completed');
            document.getElementById('indicator-' + step).classList.add('active');
        }
    </script>
</body>
</html>