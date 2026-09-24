<?php
// htdocs/admin/manage_scholarships.php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
if (!in_array($_SESSION['role'], ['ADMIN', 'SUPER_ADMIN'])) die("Access Denied.");

$success = ''; $error = '';

// Quick Status Toggle Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
    $scheme_id = $_POST['scheme_id'];
    $new_status = $_POST['new_status'];
    $stmt = $pdo->prepare("UPDATE scholarships SET status = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $scheme_id])) {
        $success = "Scheme status updated successfully.";
    } else {
        $error = "Failed to update status.";
    }
}

// Fetch all schemes
$stmt = $pdo->query("SELECT * FROM scholarships ORDER BY created_at DESC");
$schemes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schemes - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <?php include '../includes/header.php'; ?>

    <div class="container py-4 flex-grow-1" style="max-width: 1200px;">
        
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <h3 class="fw-bold mb-3 mb-md-0" style="color: #003366;"><i class="fa-solid fa-list-check me-2 text-primary"></i> Scheme Management</h3>
            <!-- Link to the add_scholarship page we built earlier! -->
            <a href="add_scholarship.php" class="btn btn-primary fw-bold shadow-sm rounded-pill px-4">
                <i class="fa-solid fa-plus me-1"></i> Create New Scheme
            </a>
        </div>

        <?php if($success): ?><div class="alert alert-success shadow-sm fw-bold"><?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger shadow-sm fw-bold"><?php echo $error; ?></div><?php endif; ?>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-4 py-3">Scheme Name</th>
                                <th class="py-3">Type</th>
                                <th class="py-3">Deadline</th>
                                <th class="py-3">Visibility</th>
                                <th class="text-end pe-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schemes as $scheme): ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="d-block fw-bold text-dark text-truncate" style="max-width: 250px;"><?php echo htmlspecialchars($scheme['title']); ?></span>
                                    </td>
                                    <td><span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars($scheme['scheme_type']); ?></span></td>
                                    <td>
                                        <?php 
                                            $dl = strtotime($scheme['deadline']);
                                            if ($dl && $dl < time()) echo '<span class="text-danger fw-bold">'.date('d M Y', $dl).' (Expired)</span>';
                                            else echo '<span class="text-dark fw-bold">'.($dl ? date('d M Y', $dl) : 'N/A').'</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($scheme['status'] == 'ACTIVE'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3"><i class="fa-solid fa-eye me-1"></i> Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill px-3"><i class="fa-solid fa-eye-slash me-1"></i> Hidden</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="edit_scholarship.php?id=<?php echo $scheme['id']; ?>" class="btn btn-outline-primary btn-sm fw-bold shadow-sm"><i class="fa-solid fa-pen"></i> Edit</a>
                                            
                                            <!-- Quick Status Toggle Button -->
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="scheme_id" value="<?php echo $scheme['id']; ?>">
                                                <?php if ($scheme['status'] == 'ACTIVE'): ?>
                                                    <input type="hidden" name="new_status" value="INACTIVE">
                                                    <button type="submit" name="toggle_status" class="btn btn-outline-danger btn-sm fw-bold shadow-sm" title="Hide this scheme"><i class="fa-solid fa-eye-slash"></i></button>
                                                <?php else: ?>
                                                    <input type="hidden" name="new_status" value="ACTIVE">
                                                    <button type="submit" name="toggle_status" class="btn btn-outline-success btn-sm fw-bold shadow-sm" title="Show this scheme"><i class="fa-solid fa-eye"></i></button>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
</body>
</html>