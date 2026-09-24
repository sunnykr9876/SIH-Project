<?php
// print_application.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

if (!isset($_GET['id'])) {
    die("Application ID is required.");
}

$app_id = $_GET['id'];

// Fetch all application details securely
try {
    $stmt = $pdo->prepare("
        SELECT 
    		a.*, 
    		s.title as scheme_title, s.scheme_type, 
    		u.name as student_name, u.email, u.id as applicant_user_id,
    		st.phone, st.dob, st.gender, st.category, 
    		st.father_name, st.mother_name, st.annual_income,
    		COALESCE(a.snapshot_pic, st.profile_pic) as profile_pic,
    		COALESCE(a.snapshot_aadhaar, st.aadhaar_raw) as aadhaar_raw
		FROM applications a
        JOIN scholarships s ON a.scholarship_id = s.id
        JOIN students st ON a.student_id = st.id
        JOIN users u ON st.user_id = u.id
        WHERE a.id = ?
    ");
    $stmt->execute([$app_id]);
    $app = $stmt->fetch();

    if (!$app) {
        die("Application not found.");
    }

    // Security Check: Only the applicant or an Admin can print this form
    if (!hasRole('ADMIN') && !hasRole('SUPER_ADMIN') && $_SESSION['user_id'] != $app['applicant_user_id']) {
        die("Access Denied. You do not have permission to view this application.");
    }

    // Fetch the uploaded documents from the relational table
    $stmt_docs = $pdo->prepare("SELECT * FROM application_documents WHERE application_id = ?");
    $stmt_docs->execute([$app_id]);
    $documents = $stmt_docs->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Application - <?php echo htmlspecialchars($app['application_number']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .print-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 51, 102, 0.1);
        }
        .section-title {
            background-color: #f1f6ff;
            color: #003366;
            padding: 10px 15px;
            border-left: 5px solid #003366;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        .info-label {
            font-weight: bold;
            color: #6c757d;
            font-size: 0.85rem;
            text-transform: uppercase;
            margin-bottom: 2px;
            display: block;
        }
        .info-value {
            font-size: 1rem;
            color: #212529;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        /* MAGIC CSS FOR PRINTING - Hides buttons & shadows, makes text pure black */
        @media print {
            body { background-color: #fff !important; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .print-card { box-shadow: none !important; border: 1px solid #ddd !important; border-radius: 0 !important; margin-bottom: 0 !important; }
            .section-title { background-color: #eee !important; color: #000 !important; border-left: 4px solid #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .info-label, .info-value, h3, h4, h5, th, td { color: #000 !important; }
            .container { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
            .badge { border: 1px solid #000; color: #000 !important; }
        }
    </style>
</head>
<body class="bg-light">

    <!-- Navbar: Hidden during printing -->
    <div class="no-print">
        <?php include '../includes/header.php'; ?>
    </div>

    <div class="container py-4 py-md-5" style="max-width: 1000px;">
        
        <!-- Action Bar: Hidden during printing -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 no-print gap-3">
            <!-- FIXED: Changed href to point directly to applications.php -->
            <a href="applications.php" class="btn btn-outline-secondary fw-bold rounded-pill px-4">
                <i class="fa-solid fa-arrow-left me-2"></i> Go Back
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-lg fw-bold shadow-sm rounded-pill px-5" style="background-color: #003366; border-color: #003366;">
                <i class="fa-solid fa-print me-2"></i> Print Application
            </button>
        </div>

        <!-- PRINTABLE DOCUMENT STARTS HERE -->
        <div class="card print-card bg-white p-4 p-md-5 mb-5">
            
            <!-- Document Header -->
            <div class="text-center mb-5 border-bottom pb-4">
                <i class="fa-solid fa-building-columns fs-1 mb-3" style="color: #003366;"></i>
                <h3 class="fw-bold text-uppercase mb-1" style="color: #003366; letter-spacing: 1px;">National ST Scholarship Portal</h3>
                <h5 class="fw-bold text-muted mb-3">Application Confirmation Record</h5>
                <div class="d-flex justify-content-center gap-3 mt-3">
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-2">ID: <?php echo htmlspecialchars($app['application_number']); ?></span>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2">Status: <?php echo htmlspecialchars($app['status']); ?></span>
                </div>
            </div>

            <!-- Header Row: Scheme Info & Profile Pic -->
            <div class="row align-items-center mb-4">
                <div class="col-md-9">
                    <span class="info-label">Applied Scheme</span>
                    <h4 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($app['scheme_title']); ?></h4>
                    <p class="text-muted fw-bold"><i class="fa-solid fa-tag me-1"></i> <?php echo htmlspecialchars($app['scheme_type']); ?> | <i class="fa-regular fa-calendar me-1"></i> Submitted: <?php echo date('d M Y, h:i A', strtotime($app['created_at'])); ?></p>
                </div>
                <div class="col-md-3 text-start text-md-end">
                    <?php 
                        $profile_image = !empty($app['profile_pic']) ? '../uploads/profile_pics/' . htmlspecialchars($app['profile_pic']) : '../assets/images/default-avatar.png';
                    ?>
                    <img src="<?php echo $profile_image; ?>" alt="Applicant Photo" class="img-thumbnail rounded-3" style="width: 140px; height: 160px; object-fit: cover; border: 2px solid #003366;">
                </div>
            </div>

            <!-- Section 1: Personal Details -->
            <div class="section-title"><i class="fa-solid fa-user me-2"></i> Applicant Personal Details</div>
            <div class="row mb-3">
                <div class="col-md-4"><span class="info-label">Full Name</span><div class="info-value"><?php echo htmlspecialchars($app['student_name']); ?></div></div>
                <div class="col-md-4"><span class="info-label">Gender</span><div class="info-value"><?php echo htmlspecialchars($app['gender'] ?? 'N/A'); ?></div></div>
                <div class="col-md-4"><span class="info-label">Date of Birth</span><div class="info-value"><?php echo !empty($app['dob']) ? date('d M Y', strtotime($app['dob'])) : 'N/A'; ?></div></div>
                <div class="col-md-4"><span class="info-label">Mobile Number</span><div class="info-value"><?php echo htmlspecialchars($app['phone'] ?? 'N/A'); ?></div></div>
                <div class="col-md-4"><span class="info-label">Email Address</span><div class="info-value"><?php echo htmlspecialchars($app['email']); ?></div></div>
                <div class="col-md-4"><span class="info-label">Aadhaar Number</span><div class="info-value fw-bold text-primary"><?php echo !empty($app['aadhaar_raw']) ? htmlspecialchars($app['aadhaar_raw']) : 'N/A'; ?></div></div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4"><span class="info-label">Father's Name</span><div class="info-value"><?php echo htmlspecialchars($app['father_name'] ?? 'N/A'); ?></div></div>
                <div class="col-md-4"><span class="info-label">Mother's Name</span><div class="info-value"><?php echo htmlspecialchars($app['mother_name'] ?? 'N/A'); ?></div></div>
                <div class="col-md-4"><span class="info-label">Annual Family Income</span><div class="info-value">₹<?php echo htmlspecialchars($app['annual_income'] ?? 'N/A'); ?></div></div>
            </div>

            <!-- Section 2: Academic Details -->
            <div class="section-title mt-4"><i class="fa-solid fa-graduation-cap me-2"></i> Current Academic Details</div>
            <div class="row mb-3">
                <div class="col-md-6"><span class="info-label">Institution Name</span><div class="info-value"><?php echo htmlspecialchars($app['institution_name'] ?? 'N/A'); ?></div></div>
                <div class="col-md-4"><span class="info-label">Current Course/Class</span><div class="info-value"><?php echo htmlspecialchars($app['current_course'] ?? 'N/A'); ?></div></div>
                <div class="col-md-2"><span class="info-label">Prev. Year Marks</span><div class="info-value"><?php echo htmlspecialchars($app['previous_percentage'] ?? 'N/A'); ?>%</div></div>
            </div>

            <!-- Section 3: Bank Details -->
            <div class="section-title mt-4"><i class="fa-solid fa-building-columns me-2"></i> Direct Benefit Transfer (Bank Details)</div>
            <div class="row mb-3">
                <div class="col-md-4"><span class="info-label">Bank Name</span><div class="info-value"><?php echo htmlspecialchars($app['bank_name'] ?? 'N/A'); ?></div></div>
                <div class="col-md-4"><span class="info-label">Account Number</span><div class="info-value fw-bold"><?php echo htmlspecialchars($app['account_number'] ?? 'N/A'); ?></div></div>
                <div class="col-md-4"><span class="info-label">IFSC Code</span><div class="info-value"><?php echo htmlspecialchars($app['ifsc_code'] ?? 'N/A'); ?></div></div>
            </div>

            <!-- Section 4: Document Evidence -->
            <div class="section-title mt-4"><i class="fa-solid fa-folder-open me-2"></i> Uploaded Verification Documents</div>
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-uppercase small text-muted">Document Type</th>
                            <th class="text-uppercase small text-muted">File Name (System Reference)</th>
                            <th class="text-uppercase small text-muted">Certificate Number</th>
                            <th class="text-uppercase small text-muted">Issue Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($documents) > 0): ?>
                            <?php foreach ($documents as $doc): 
                                $doc_type = $doc['document_type'];
                                
                                // Set defaults for standard documents
                                $cert_no = !empty($doc['document_number']) ? htmlspecialchars($doc['document_number']) : 'N/A';
                                $issue_date = !empty($doc['issue_date']) ? date('d M Y', strtotime($doc['issue_date'])) : 'N/A';

                                // Override dynamically for Aadhaar
                                if ($doc_type === 'AADHAAR') {
                                    $cert_no = !empty($app['aadhaar_raw']) ? htmlspecialchars($app['aadhaar_raw']) : 'N/A';
                                    $issue_date = 'N/A';
                                }
                                
                                // Ensure Bank Passbook shows N/A properly instead of blank
                                if ($doc_type === 'BANK_PASSBOOK') {
                                    $cert_no = 'N/A';
                                    $issue_date = 'N/A';
                                }
                            ?>
                            <tr>
                                <td class="fw-bold text-dark"><i class="fa-solid fa-file-pdf text-danger me-2 no-print"></i> <?php echo htmlspecialchars(str_replace('_', ' ', $doc_type)); ?></td>
                                <td class="text-muted small"><?php echo htmlspecialchars(basename($doc['file_path'])); ?></td>
                                <td class="fw-bold"><?php echo $cert_no; ?></td>
                                <td><?php echo $issue_date; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted fw-bold py-3">No documents found for this application.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Signature Section -->
            <div class="mt-5 pt-4 border-top">
                <p class="text-muted small mb-5">
                    <strong>DECLARATION:</strong> I hereby declare that the information provided above is true and correct to the best of my knowledge and belief. I understand that any false statement may result in the rejection of my application or cancellation of the scholarship at any stage.
                </p>
                <div class="d-flex justify-content-between align-items-end px-3">
                    <div class="text-center">
                        <div style="border-bottom: 1px solid #000; width: 200px; margin-bottom: 5px;"></div>
                        <span class="fw-bold text-uppercase small">Date & Place</span>
                    </div>
                    <div class="text-center">
                        <div style="border-bottom: 1px solid #000; width: 200px; margin-bottom: 5px;"></div>
                        <span class="fw-bold text-uppercase small">Applicant Signature</span>
                    </div>
                </div>
            </div>

        </div> <!-- End of Printable Card -->
    </div>

    <!-- Footer: Hidden during printing -->
    <div class="no-print">
        <?php include '../includes/footer.php'; ?>
    </div>

</body>
</html>