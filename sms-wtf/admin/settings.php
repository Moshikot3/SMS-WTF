<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

$auth = new Auth();

// Check admin authentication
if (!$auth->isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        
        try {
            switch ($action) {
                case 'update_site_password':
                    $enabled = isset($_POST['password_enabled']);
                    $password = $_POST['site_password'] ?? '';
                    
                    $auth->enableSitePassword($enabled);
                    
                    if ($enabled && !empty($password)) {
                        $auth->setSitePassword($password);
                        $success = 'Site password updated and enabled';
                    } elseif ($enabled) {
                        $success = 'Site password protection enabled (existing password kept)';
                    } else {
                        $success = 'Site password protection disabled';
                    }
                    break;
                    
                case 'update_admin_password':
                    $currentPassword = $_POST['current_password'] ?? '';
                    $newPassword = $_POST['new_password'] ?? '';
                    $confirmPassword = $_POST['confirm_password'] ?? '';
                    
                    // Verify current password
                    $admin = $auth->getAdminUser();
                    $adminData = $db->fetchOne("SELECT * FROM admin_users WHERE id = ?", [$admin['id']]);
                    
                    if (!password_verify($currentPassword, $adminData['password_hash'])) {
                        throw new Exception('Current password is incorrect');
                    }
                    
                    if ($newPassword !== $confirmPassword) {
                        throw new Exception('New passwords do not match');
                    }
                    
                    if (strlen($newPassword) < 6) {
                        throw new Exception('New password must be at least 6 characters long');
                    }
                    
                    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $db->update('admin_users', ['password_hash' => $newHash], 'id = ?', [$admin['id']]);
                    
                    $success = 'Admin password updated successfully';
                    break;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get current settings
$settings = $db->fetchAll("SELECT setting_key, setting_value FROM website_settings");
$settingsArray = [];
foreach ($settings as $setting) {
    $settingsArray[$setting['setting_key']] = $setting['setting_value'];
}

$pageTitle = 'Site Settings - ' . SITE_NAME;
include __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>⚙️ Site Settings</h2>
                <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Site Password Settings -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>🔒 Site Password Protection</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_site_password">
                        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                        
                        <div class="form-group">
                            <div class="checkbox">
                                <input type="checkbox" 
                                       id="password_enabled" 
                                       name="password_enabled" 
                                       <?php echo ($settingsArray['site_password_enabled'] === 'true') ? 'checked' : ''; ?>>
                                <label for="password_enabled">Enable site password protection</label>
                            </div>
                            <small class="text-muted">Require visitors to enter a password to view SMS messages</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_password" class="form-label">Site Password</label>
                            <input type="password" 
                                   id="site_password" 
                                   name="site_password" 
                                   class="form-control" 
                                   placeholder="Leave blank to keep current password">
                            <small class="text-muted">Only enter a new password if you want to change it</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Password Settings</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Admin Password Settings -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>🔑 Admin Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_admin_password">
                        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                        
                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password" 
                                   class="form-control" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   class="form-control" 
                                   minlength="6"
                                   required>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="form-control" 
                                   minlength="6"
                                   required>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">Change Admin Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Webhook Information -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>📡 Webhook Configuration</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>Webhook Endpoint URL</h6>
                        <p>Configure your Android SMS Gateway to send webhooks to this URL:</p>
                        <div class="input-group mb-3">
                            <input type="text" 
                                   class="form-control" 
                                   value="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2) . '/webhook.php'; ?>" 
                                   readonly>
                            <button class="btn btn-outline-secondary" 
                                    onclick="copyToClipboard('<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2) . '/webhook.php'; ?>')">
                                Copy
                            </button>
                        </div>
                        
                        <h6>Setup Instructions</h6>
                        <ol>
                            <li>Install Android SMS Gateway app on your Android device</li>
                            <li>Configure the app with a username and password</li>
                            <li>Add phone numbers to this system using the "Manage Phone Numbers" page</li>
                            <li>Register the webhook using the curl command below</li>
                        </ol>
                        
                        <h6>Webhook Registration Command</h6>
                        <pre><code>curl -X POST -u &lt;username&gt;:&lt;password&gt; \
  -H "Content-Type: application/json" \
  -d '{ "id": "sms-wtf-webhook", "url": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2) . '/webhook.php'; ?>", "event": "sms:received" }' \
  http://&lt;device_local_ip&gt;:8080/webhooks</code></pre>
                        
                        <small class="text-muted">Replace &lt;username&gt;, &lt;password&gt;, and &lt;device_local_ip&gt; with your actual values.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>💻 System Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>PHP Version:</strong></td>
                                    <td><?php echo PHP_VERSION; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Server Software:</strong></td>
                                    <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Database:</strong></td>
                                    <td>MySQL <?php echo $db->fetchOne("SELECT VERSION() as version")['version']; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Site URL:</strong></td>
                                    <td><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Timezone:</strong></td>
                                    <td><?php echo date_default_timezone_get(); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Current Time:</strong></td>
                                    <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('new_password').addEventListener('input', function() {
    const confirmPassword = document.getElementById('confirm_password');
    confirmPassword.dispatchEvent(new Event('input'));
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
