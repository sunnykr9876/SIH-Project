<?php
// htdocs/admin/edit_scholarship.php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
if (!hasRole('ADMIN') && !hasRole('SUPER_ADMIN')) {
    die("Access Denied.");
}

if (!isset($_GET['id'])) {
    header("Location: manage_scholarships.php");
    exit;
}

$id = $_GET['id'];
$error = ''; $success = '';

// Fetch existing scholarship data
$stmt = $pdo->prepare("SELECT * FROM scholarships WHERE id = ?");
$stmt->execute([$id]);
$sch = $stmt->fetch();

if (!$sch) {
    die("Scholarship scheme not found.");
}

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
                UPDATE scholarships 
                SET title = ?, scheme_type = ?, short_description = ?, benefits = ?, start_date = ?, deadline = ?, status = ? 
                WHERE id = ?
            ");
            $stmt->execute([$title, $scheme_type, $short_description, $benefits, $start_date, $deadline, $status, $id]);
            $success = "Scholarship scheme updated successfully!";
            
            // Refresh record
            $stmt_refresh = $pdo->prepare("SELECT * FROM scholarships WHERE id = ?");
            $stmt_refresh->execute([$id]);
            $sch = $stmt_refresh->fetch();
        } catch (Exception $e) {
            $error = "Database error: Could not update scholarship.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Scheme - Admin Portal</title>
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
                <a href="manage_scholarships.php" class="text-decoration-none mb-3 d-inline-block fw-bold"><i class="fa-solid fa-arrow-left me-1"></i> Back to Schemes</a>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white pt-4 pb-3 border-bottom">
                        <h4 class="mb-0" style="color: var(--primary-gov);"><i class="fa-solid fa-pen-to-square me-2"></i>Edit Scholarship Scheme</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if($error): ?><div class="alert alert-danger shadow-sm border-0"><?php echo $error; ?></div><?php endif; ?>
                        <?php if($success): ?><div class="alert alert-success shadow-sm border-0"><?php echo $success; ?></div><?php endif; ?>

                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold text-uppercase">Scheme Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control bg-white" value="<?php echo htmlspecialchars($sch['title']); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase">Scheme Type</label>
                                    <select name="scheme_type" class="form-select bg-white">
                                        <option value="Fellowship" <?php echo ($sch['scheme_type'] == 'Fellowship') ? 'selected' : ''; ?>>Fellowship</option>
                                        <option value="Scholarship" <?php echo ($sch['scheme_type'] == 'Scholarship') ? 'selected' : ''; ?>>Scholarship</option>
                                        <option value="Overseas Grant" <?php echo ($sch['scheme_type'] == 'Overseas Grant') ? 'selected' : ''; ?>>Overseas Grant</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-uppercase">Short Description</label>
                                    <textarea name="short_description" class="form-control bg-white" rows="2"><?php echo htmlspecialchars($sch['short_description']); ?></textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-uppercase">Benefits & Financial Assistance</label>
                                    <textarea name="benefits" class="form-control bg-white" rows="3"><?php echo htmlspecialchars($sch['benefits']); ?></textarea>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase">Application Start Date <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" class="form-control bg-white" value="<?php echo htmlspecialchars($sch['start_date']); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase">Application Deadline <span class="text-danger">*</span></label>
                                    <input type="date" name="deadline" class="form-control bg-white" value="<?php echo htmlspecialchars($sch['deadline']); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase">Status</label>
                                    <select name="status" class="form-select bg-white">
                                        <option value="ACTIVE" <?php echo ($sch['status'] == 'ACTIVE') ? 'selected' : ''; ?>>ACTIVE</option>
                                        <option value="INACTIVE" <?php echo ($sch['status'] == 'INACTIVE') ? 'selected' : ''; ?>>INACTIVE</option>
                                    </select>
                                </div>

                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-gov-primary btn-lg w-100 fw-bold shadow-sm">
                                        <i class="fa-solid fa-floppy-disk me-2"></i> Update Scheme Details
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