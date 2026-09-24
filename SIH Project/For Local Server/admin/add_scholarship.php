<?php
// htdocs/admin/add_scholarship.php

require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
if (!hasRole('ADMIN') && !hasRole('SUPER_ADMIN')) {
    die("Access Denied.");
}

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $scheme_type = trim($_POST['scheme_type']);
    $short_description = trim($_POST['short_description']);
    $benefits = trim($_POST['benefits']);
    $start_date = $_POST['start_date'];
    $deadline = $_POST['deadline'];
    $status = $_POST['status'];

    if (empty($title) || empty($deadline)) {
        $error = "Title and Deadline are required fields.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO scholarships 
                (title, scheme_type, short_description, benefits, start_date, deadline, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $scheme_type, $short_description, $benefits, $start_date, $deadline, $status]);
            $success = "New Scholarship scheme published successfully!";
            
        } catch (PDOException $e) {
            $error = "Database error: Could not add scholarship.";
        } catch (Exception $e) {
            $error = "System error: Could not add scholarship.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Scheme - Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <?php include '../includes/header.php'; ?>

    <div class="container-fluid py-4 flex-grow-1" style="max-width: 1400px;">
        <div class="row">
            <div class="col-md-3 col-lg-2 mb-4">
                <!-- SMART CHECK: Only load sidebar if it actually exists -->
                <?php 
                if(file_exists('../includes/admin_sidebar.php')) {
                    include '../includes/admin_sidebar.php'; 
                }
                ?>
            </div>
            
            <div class="col-md-9 col-lg-10">
                <a href="manage_scholarships.php" class="text-decoration-none mb-3 d-inline-block fw-bold"><i class="fa-solid fa-arrow-left me-1"></i> Back to Schemes</a>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white pt-4 pb-3 border-bottom">
                        <h4 class="mb-0" style="color: var(--primary-gov);"><i class="fa-solid fa-plus-circle me-2"></i>Publish New Scholarship Scheme</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if($error): ?><div class="alert alert-danger shadow-sm border-0"><?php echo $error; ?></div><?php endif; ?>
                        <?php if($success): ?><div class="alert alert-success shadow-sm border-0"><?php echo $success; ?></div><?php endif; ?>

                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold text-uppercase">Scheme Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control bg-white" required placeholder="Enter scheme title">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase">Scheme Type</label>
                                    <select name="scheme_type" class="form-select bg-white">
                                        <option value="Fellowship">Fellowship</option>
                                        <option value="Scholarship">Scholarship</option>
                                        <option value="Overseas Grant">Overseas Grant</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-uppercase">Short Description</label>
                                    <textarea name="short_description" class="form-control bg-white" rows="2" placeholder="Brief summary of eligibility..."></textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-uppercase">Benefits & Financial Assistance</label>
                                    <textarea name="benefits" class="form-control bg-white" rows="3" placeholder="List the financial amounts..."></textarea>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase">Application Start Date <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" class="form-control bg-white" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase">Application Deadline <span class="text-danger">*</span></label>
                                    <input type="date" name="deadline" class="form-control bg-white" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase">Status</label>
                                    <select name="status" class="form-select bg-white">
                                        <option value="ACTIVE">ACTIVE</option>
                                        <option value="INACTIVE">INACTIVE</option>
                                    </select>
                                </div>

                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm" style="background-color: #003366; border-color: #003366; color: white;">
                                        <i class="fa-solid fa-paper-plane me-2"></i> Publish Scheme
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>