<?php
/**
 * SMS WTF Installation Script
 * Run this file once to set up the database and initial configuration
 */

// Prevent running in production
if (file_exists('config/config.php')) {
    $config = file_get_contents('config/config.php');
    if (strpos($config, "ini_set('display_errors', 0)") !== false) {
        die('Installation is disabled in production mode.');
    }
}

$step = $_GET['step'] ?? 1;
$errors = [];
$success = [];

// Step 1: Welcome and Requirements Check
if ($step == 1) {
    $requirements = [
        'PHP Version' => [
            'check' => version_compare(PHP_VERSION, '7.4.0', '>='),
            'message' => 'PHP 7.4+ required (current: ' . PHP_VERSION . ')'
        ],
        'PDO Extension' => [
            'check' => extension_loaded('pdo'),
            'message' => 'PDO extension is required'
        ],
        'PDO MySQL' => [
            'check' => extension_loaded('pdo_mysql'),
            'message' => 'PDO MySQL extension is required'
        ],
        'JSON Extension' => [
            'check' => extension_loaded('json'),
            'message' => 'JSON extension is required'
        ],
        'Session Support' => [
            'check' => function_exists('session_start'),
            'message' => 'Session support is required'
        ]
    ];
}

// Step 2: Database Configuration
if ($step == 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbName = $_POST['db_name'] ?? 'sms_wtf';
    $dbUser = $_POST['db_user'] ?? '';
    $dbPass = $_POST['db_pass'] ?? '';
    
    // Test database connection
    try {
        $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbName`");
        
        // Read and execute schema
        $schema = file_get_contents('database/schema.sql');
        $statements = explode(';', $schema);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        // Update config file
        $configContent = file_get_contents('config/config.php');
        $configContent = str_replace("define('DB_HOST', 'localhost');", "define('DB_HOST', '$dbHost');", $configContent);
        $configContent = str_replace("define('DB_NAME', 'sms_wtf');", "define('DB_NAME', '$dbName');", $configContent);
        $configContent = str_replace("define('DB_USER', 'root');", "define('DB_USER', '$dbUser');", $configContent);
        $configContent = str_replace("define('DB_PASS', '');", "define('DB_PASS', '$dbPass');", $configContent);
        
        file_put_contents('config/config.php', $configContent);
        
        $success[] = 'Database created and configured successfully!';
        $step = 3;
        
    } catch (Exception $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
    }
}

// Step 3: Admin Account Setup
if ($step == 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['admin_username'] ?? '';
    $password = $_POST['admin_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($username)) {
        $errors[] = 'Username and password are required';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
        } else {
        try {
            require_once __DIR__ . '/config/config.php';
            require_once __DIR__ . '/includes/Database.php';            $db = Database::getInstance();
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Update the default admin user
            $db->query(
                "UPDATE admin_users SET username = ?, password_hash = ? WHERE username = 'admin'",
                [$username, $hashedPassword]
            );
            
            $success[] = 'Admin account created successfully!';
            $step = 4;
            
        } catch (Exception $e) {
            $errors[] = 'Error creating admin account: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS WTF Installation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .installer {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 90%;
            padding: 2rem;
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 0.5rem;
            font-weight: bold;
        }
        
        .step.active {
            background: #667eea;
            color: white;
        }
        
        .step.completed {
            background: #10b981;
            color: white;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #5a67d8;
        }
        
        .btn-full {
            width: 100%;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .requirements {
            list-style: none;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .requirement:last-child {
            border-bottom: none;
        }
        
        .requirement-status {
            margin-right: 1rem;
            font-size: 1.2rem;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: #6b7280;
        }
        
        .mb-4 {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="installer">
        <div class="header">
            <h1>📱 SMS WTF</h1>
            <p class="text-muted">Installation Wizard</p>
        </div>
        
        <div class="step-indicator">
            <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">1</div>
            <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">2</div>
            <div class="step <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>">3</div>
            <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">4</div>
        </div>
        
        <?php foreach ($success as $message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endforeach; ?>
        
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
        
        <?php if ($step == 1): ?>
            <h2>Welcome to SMS WTF</h2>
            <p class="mb-4">This wizard will help you set up your SMS webhook receiver. Let's start by checking your server requirements.</p>
            
            <ul class="requirements">
                <?php foreach ($requirements as $name => $req): ?>
                    <li class="requirement">
                        <span class="requirement-status">
                            <?php echo $req['check'] ? '✅' : '❌'; ?>
                        </span>
                        <div>
                            <strong><?php echo $name; ?></strong><br>
                            <small class="text-muted"><?php echo $req['message']; ?></small>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if (array_reduce($requirements, function($carry, $req) { return $carry && $req['check']; }, true)): ?>
                <div class="text-center" style="margin-top: 2rem;">
                    <a href="?step=2" class="btn">Continue to Database Setup</a>
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    Please fix the requirements above before continuing.
                </div>
            <?php endif; ?>
            
        <?php elseif ($step == 2): ?>
            <h2>Database Configuration</h2>
            <p class="mb-4">Enter your MySQL database connection details. The database will be created automatically if it doesn't exist.</p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="db_host" class="form-label">Database Host</label>
                    <input type="text" id="db_host" name="db_host" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_name" class="form-label">Database Name</label>
                    <input type="text" id="db_name" name="db_name" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['db_name'] ?? 'sms_wtf'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_user" class="form-label">Database Username</label>
                    <input type="text" id="db_user" name="db_user" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass" class="form-label">Database Password</label>
                    <input type="password" id="db_pass" name="db_pass" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['db_pass'] ?? ''); ?>">
                </div>
                
                <button type="submit" class="btn btn-full">Setup Database</button>
            </form>
            
        <?php elseif ($step == 3): ?>
            <h2>Admin Account Setup</h2>
            <p class="mb-4">Create your admin account to manage the SMS system.</p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="admin_username" class="form-label">Admin Username</label>
                    <input type="text" id="admin_username" name="admin_username" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['admin_username'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_password" class="form-label">Admin Password</label>
                    <input type="password" id="admin_password" name="admin_password" class="form-control" 
                           minlength="6" required>
                    <small class="text-muted">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                           minlength="6" required>
                </div>
                
                <button type="submit" class="btn btn-full">Create Admin Account</button>
            </form>
            
        <?php elseif ($step == 4): ?>
            <h2>🎉 Installation Complete!</h2>
            <p class="mb-4">SMS WTF has been successfully installed and configured.</p>
            
            <div class="alert alert-success">
                <strong>What's Next?</strong><br>
                1. Delete this install.php file for security<br>
                2. Set up your Android SMS Gateway<br>
                3. Add phone numbers via the admin panel<br>
                4. Configure your webhook endpoint
            </div>
            
            <div class="text-center">
                <a href="admin/" class="btn" style="margin-right: 1rem;">Go to Admin Panel</a>
                <a href="index.php" class="btn">View SMS Messages</a>
            </div>
            
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
                <h3>Webhook URL</h3>
                <p>Use this URL for your Android SMS Gateway webhook:</p>
                <code style="background: #f3f4f6; padding: 0.5rem; border-radius: 4px; display: block; margin-top: 0.5rem;">
                    <?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/webhook.php'; ?>
                </code>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Password confirmation validation
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.getElementById('admin_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword && confirmPassword) {
                function validatePasswords() {
                    if (newPassword.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Passwords do not match');
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                }
                
                newPassword.addEventListener('input', validatePasswords);
                confirmPassword.addEventListener('input', validatePasswords);
            }
        });
    </script>
</body>
</html>
