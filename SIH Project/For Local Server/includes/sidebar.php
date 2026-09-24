<?php
// htdocs/includes/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="card shadow-sm border-0 mb-4">
    <div class="list-group list-group-flush rounded">
        <a href="dashboard.php" class="list-group-item list-group-item-action py-3 <?= ($currentPage == 'dashboard.php') ? 'active bg-primary border-primary text-white' : 'text-dark' ?>">
            <i class="fa-solid fa-chart-pie me-2"></i> Dashboard Overview
        </a>
        <a href="profile.php" class="list-group-item list-group-item-action py-3 <?= ($currentPage == 'profile.php') ? 'active bg-primary border-primary text-white' : 'text-dark' ?>">
            <i class="fa-solid fa-id-card me-2"></i> My Profile
        </a>
        <a href="scholarships.php" class="list-group-item list-group-item-action py-3 <?= ($currentPage == 'scholarships.php' || $currentPage == 'eligibility.php' || $currentPage == 'apply.php') ? 'active bg-primary border-primary text-white' : 'text-dark' ?>">
            <i class="fa-solid fa-magnifying-glass me-2"></i> Available Schemes
        </a>
        <!-- ACTIVATED MY APPLICATIONS TAB -->
        <a href="applications.php" class="list-group-item list-group-item-action py-3 <?= ($currentPage == 'applications.php') ? 'active bg-primary border-primary text-white' : 'text-dark' ?>">
            <i class="fa-solid fa-file-lines me-2"></i> My Applications
        </a>
    </div>
</div>