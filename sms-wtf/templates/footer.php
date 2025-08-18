    </main>
    
    <footer class="footer mt-5 py-4" style="background: var(--surface-hover); border-top: 1px solid var(--border-color);">
        <div class="container text-center">
            <p class="text-muted mb-0">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?> - SMS Webhook Receiver
            </p>
        </div>
    </footer>

    <?php
    // Use the same assets path logic as in header
    $currentPath = $_SERVER['REQUEST_URI'];
    $isAdminPage = strpos($currentPath, '/admin/') !== false;
    $assetsPath = $isAdminPage ? '../' : '';
    ?>
    <script src="<?php echo $assetsPath; ?>assets/js/app.js"></script>
    
    <!-- Hidden CSRF token for AJAX requests -->
    <?php if (isset($auth)): ?>
        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
    <?php endif; ?>
</body>
</html>
