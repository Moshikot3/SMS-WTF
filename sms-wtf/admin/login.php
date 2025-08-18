<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->loginAdmin($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}

$pageTitle = 'Admin Login - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header text-center">
                        <h3>🔐 Admin Login</h3>
                        <p class="mb-0 text-muted"><?php echo htmlspecialchars(SITE_NAME); ?></p>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-error">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="form-group">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" 
                                       id="username" 
                                       name="username" 
                                       class="form-control" 
                                       required 
                                       autofocus
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       class="form-control" 
                                       required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                Login
                            </button>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <a href="../" class="text-muted">← Back to SMS Messages</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .card {
            box-shadow: var(--shadow-lg);
            border: none;
        }
    </style>
</body>
</html>
