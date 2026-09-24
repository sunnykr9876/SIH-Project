<?php
// htdocs/includes/footer.php
?>
    <!-- Main Footer -->
    <footer class="bg-dark text-white pt-5 pb-3 mt-auto border-top border-4 border-warning">
        <div class="container-fluid px-4" style="max-width: 1400px;">
            <div class="row g-4 mb-4">
                
                <!-- Column 1: About Portal -->
                <div class="col-md-6 col-lg-4">
                    <h5 class="fw-bold text-warning mb-3 d-flex align-items-center">
                        <i class="fa-solid fa-building-columns me-2"></i> ST Scholarship Portal
                    </h5>
                    <p class="small text-light opacity-75" style="line-height: 1.6;">
                        A dedicated digital initiative by the Government of India to ensure seamless, transparent, and timely disbursement of scholarships to Scheduled Tribe (ST) students across the nation.
                    </p>
                </div>
                
                <!-- Column 2: Quick Links -->
                <div class="col-md-6 col-lg-4">
                    <h5 class="fw-bold text-warning mb-3">Quick Links</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none opacity-75 custom-hover"><i class="fa-solid fa-angle-right me-2 text-warning"></i> Application Guidelines</a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none opacity-75 custom-hover"><i class="fa-solid fa-angle-right me-2 text-warning"></i> Frequently Asked Questions</a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none opacity-75 custom-hover"><i class="fa-solid fa-angle-right me-2 text-warning"></i> Terms & Conditions</a></li>
                    </ul>
                </div>
                
                <!-- Column 3: Helpdesk -->
                <div class="col-md-12 col-lg-4">
                    <h5 class="fw-bold text-warning mb-3">Technical Helpdesk</h5>
                    <ul class="list-unstyled small text-light opacity-75">
                        <li class="mb-2"><i class="fa-solid fa-envelope me-2 text-warning"></i> support@scholarship.gov.in</li>
                        <li class="mb-2"><i class="fa-solid fa-phone me-2 text-warning"></i> 1800-111-2222 (Toll Free)</li>
                        <li><i class="fa-solid fa-clock me-2 text-warning"></i> Mon - Fri (9:00 AM to 6:00 PM IST)</li>
                    </ul>
                </div>
            </div>
            
            <hr class="border-secondary mb-3">
            
            <!-- Copyright & Credits -->
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start small text-light opacity-50 mb-2 mb-md-0">
                    &copy; <?php echo date('Y'); ?> Government of India. All Rights Reserved.
                </div>
                <div class="col-md-6 text-center text-md-end small text-light opacity-50">
                    Secure Digital Scrutiny & Management System
                </div>
            </div>
        </div>
    </footer>

    <!-- ==========================================
         CORE JAVASCRIPT LIBRARIES 
    =========================================== -->
    
    <!-- 1. jQuery (Included safely in case any of your older scripts require it) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- 2. Bootstrap 5 JS Bundle (CRITICAL: Powers the mobile 3-line menu toggle and dropdowns!) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- 3. Your Custom Scripts (If you have a script.js file in your assets folder) -->
    <script src="../assets/js/script.js" onerror="this.outerHTML=''"></script>
    
    <!-- Micro-CSS for Footer Hover Effects -->
    <style>
        .custom-hover { transition: all 0.2s ease-in-out; }
        .custom-hover:hover { opacity: 1 !important; padding-left: 6px; color: #ffc107 !important; }
    </style>
</body>
</html>