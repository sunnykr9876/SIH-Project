<?php
// htdocs/hash_utility.php
// A secure utility tool to generate PHP password hashes for MySQL insertion.

$generated_hash = '';$input_password = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input_password =$_POST['plain_password'] ?? '';
    
    if (!empty($input_password)) {
        // Generate a secure bcrypt/argon2 hash using PHP's native password_hash
        $generated_hash = password_hash($input_password, PASSWORD_DEFAULT);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Hash Generator - ST Scholarship Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <div class="container py-5 my-auto" style="max-width: 600px;">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white pt-4 pb-3 border-bottom text-center">
                <h4 style="color: var(--primary-gov);"><i class="fa-solid fa-key me-2"></i>Password Hash Generator</h4>
                <p class="text-muted small mb-0">Generate secure PHP hashes for manual MySQL database insertion.</p>
            </div>
            <div class="card-body p-4">
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Enter Plain-Text Password:</label>
                        <input type="text" name="plain_password" class="form-control" value="<?php echo htmlspecialchars($input_password); ?>" placeholder="e.g. secretpassword123" required>
                    </div>
                    
                    <button type="submit" class="btn btn-gov-primary w-100 py-2 fw-bold">
                        <i class="fa-solid fa-gears me-2"></i> Generate Hash
                    </button>
                </form>

                <?php if (!empty($generated_hash)): ?>
                    <div class="mt-4 pt-3 border-top">
                        <label class="form-label fw-bold text-success"><i class="fa-solid fa-check-circle me-1"></i> Generated Hash (Ready for MySQL):</label>
                        <div class="input-group">
                            <input type="text" id="hashOutput" class="form-control bg-light font-monospace text-break" value="<?php echo htmlspecialchars($generated_hash); ?>" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyHash()">
                                <i class="fa-solid fa-copy"></i> Copy
                            </button>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <i class="fa-solid fa-circle-info"></i> Note: This string is 60 characters long. Ensure your MySQL database column `password_hash` is set to `VARCHAR(255)`.
                        </small>
                    </div>
                <?php endif; ?>

            </div>
            <div class="card-footer bg-white text-center py-3">
                <a href="login.php" class="text-decoration-none small"><i class="fa-solid fa-arrow-left me-1"></i> Return to Login</a>
            </div>
        </div>
    </div>

    <script>
        function copyHash() {
            const copyText = document.getElementById("hashOutput");
            copyText.select();
            copyText.setSelectionRange(0, 99999); // For mobile devices
            navigator.clipboard.writeText(copyText.value);
            alert("Hash copied to clipboard!");
        }
    </script>
</body>
</html>