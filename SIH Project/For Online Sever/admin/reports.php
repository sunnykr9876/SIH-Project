<?php
// htdocs/admin/reports.php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
if (!hasRole('ADMIN') && !hasRole('SUPER_ADMIN')) die("Access Denied.");

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Scholarship_Applications_Report_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['App Number', 'Student Name', 'Email', 'Phone', 'Category', 'Income', 'Scheme Applied', 'Status', 'Submitted Date']);
    
    $stmt = $pdo->query("
        SELECT a.application_number, u.name, u.email, st.phone, st.category, st.annual_income, s.title as scheme_title, a.status, a.submitted_at 
        FROM applications a
        JOIN students st ON a.student_id = st.id
        JOIN users u ON st.user_id = u.id
        JOIN scholarships s ON a.scholarship_id = s.id
        ORDER BY a.submitted_at DESC
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['application_number'], 
            $row['name'], 
            $row['email'], 
            $row['phone'], 
            $row['category'], 
            $row['annual_income'], 
            $row['scheme_title'], 
            $row['status'], 
            $row['submitted_at']
        ]);
    }
    fclose($output);
    exit;
}

// Fetch data for screen display
$stmt = $pdo->query("
    SELECT a.application_number, u.name, s.title as scheme_title, a.status, a.submitted_at 
    FROM applications a
    JOIN students st ON a.student_id = st.id
    JOIN users u ON st.user_id = u.id
    JOIN scholarships s ON a.scholarship_id = s.id
    ORDER BY a.submitted_at DESC
");
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Reports - Admin Portal</title>
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
                    <h3 style="color: var(--primary-gov);"><i class="fa-solid fa-file-excel me-2"></i>Data & Reports Engine</h3>
                    <a href="reports.php?export=csv" class="btn btn-success fw-bold shadow-sm">
                        <i class="fa-solid fa-download me-2"></i> Export to Excel (CSV)
                    </a>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="py-3 ps-3">App Number</th>
                                        <th class="py-3">Applicant Name</th>
                                        <th class="py-3">Scheme</th>
                                        <th class="py-3">Status</th>
                                        <th class="py-3">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($applications as $app): ?>
                                        <tr>
                                            <td class="ps-3 fw-bold font-monospace"><?php echo htmlspecialchars($app['application_number']); ?></td>
                                            <td><?php echo htmlspecialchars($app['name']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($app['scheme_title'], 0, 40)); ?>...</td>
                                            <td><span class="badge bg-secondary"><?php echo str_replace('_', ' ', $app['status']); ?></span></td>
                                            <td><?php echo date('d M Y', strtotime($app['submitted_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>