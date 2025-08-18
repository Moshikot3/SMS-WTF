<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Protected - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h3>🔒 Protected Access</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-error">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <p class="text-center text-muted mb-4">
                            This site is password protected. Please enter the access password to continue.
                        </p>
                        
                        <form method="POST">
                            <div class="form-group">
                                <label for="site_password" class="form-label">Password</label>
                                <input type="password" 
                                       id="site_password" 
                                       name="site_password" 
                                       class="form-control" 
                                       required 
                                       autofocus>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                Access Site
                            </button>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <small class="text-muted">
                            Contact administrator if you need access
                        </small>
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
