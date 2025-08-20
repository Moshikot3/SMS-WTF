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
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container-fluid vh-100 d-flex align-items-center justify-content-center">
        <div class="row w-100 justify-content-center">
            <div class="col-11 col-sm-8 col-md-6 col-lg-4 col-xl-3">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-gradient text-white text-center py-4">
                        <div class="mb-3">
                            <i class="bi bi-shield-lock-fill" style="font-size: 3rem;"></i>
                        </div>
                        <h3 class="mb-2 fw-bold">Admin Login</h3>
                        <p class="mb-0 opacity-75"><?php echo htmlspecialchars(SITE_NAME); ?></p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label fw-semibold">
                                    <i class="bi bi-person-fill me-1"></i>Username
                                </label>
                                <input type="text" 
                                       id="username" 
                                       name="username" 
                                       class="form-control form-control-lg" 
                                       placeholder="Enter your username"
                                       required 
                                       autofocus
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">
                                    <i class="bi bi-lock-fill me-1"></i>Password
                                </label>
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       class="form-control form-control-lg" 
                                       placeholder="Enter your password"
                                       required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Sign In
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center py-3 bg-light">
                        <a href="../" class="text-decoration-none text-muted">
                            <i class="bi bi-arrow-left me-1"></i>Back to SMS Messages
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .card {
            border-radius: 15px;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        .bg-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            transition: transform 0.2s ease-in-out;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .card-header i {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</body>
</html>
