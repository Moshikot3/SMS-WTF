    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-light mt-5 py-4">
        <div class="container text-center">
            <div class="row">
                <div class="col-12">
                    <p class="mb-2">
                        <i class="bi bi-phone-vibrate me-2"></i>
                        <strong><?php echo htmlspecialchars(SITE_NAME); ?></strong>
                    </p>
                    <p class="text-muted small mb-0">
                        &copy; <?php echo date('Y'); ?> SMS Webhook Receiver - Professional SMS Management System
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php
    // Use the same assets path logic as in header
    $currentPath = $_SERVER['REQUEST_URI'];
    $isAdminPage = strpos($currentPath, '/admin/') !== false;
    $assetsPath = $isAdminPage ? '../' : '';
    ?>
    <!-- Custom JS -->
    <script src="<?php echo $assetsPath; ?>assets/js/app.js"></script>
    
    <!-- Hidden CSRF token for AJAX requests -->
    <?php if (isset($auth)): ?>
        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
    <?php endif; ?>
</body>
</html>
