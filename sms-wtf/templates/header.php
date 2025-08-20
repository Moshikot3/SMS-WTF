<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? SITE_NAME); ?></title>
    <?php
    // Determine the base path and assets path based on current directory
    $currentPath = $_SERVER['REQUEST_URI'];
    $isAdminPage = strpos($currentPath, '/admin/') !== false;
    $basePath = $isAdminPage ? '../' : './';
    $assetsPath = $isAdminPage ? '../' : '';
    ?>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $assetsPath; ?>assets/css/style.css">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📱</text></svg>">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo $isAdminPage ? '../' : './'; ?>">
                <i class="bi bi-phone-vibrate me-2"></i>
                <?php echo htmlspecialchars(SITE_NAME); ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if ($isAdminPage): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath; ?>">
                                <i class="bi bi-house me-1"></i>Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="./">
                                <i class="bi bi-gear me-1"></i>Admin
                            </a>
                        </li>
                        <?php if (isset($auth) && $auth->isAdminLoggedIn()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link active" href="./">
                                <i class="bi bi-house me-1"></i>Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/">
                                <i class="bi bi-gear me-1"></i>Admin
                            </a>
                        </li>
                        <?php if (isset($auth) && $auth->isAdminLoggedIn()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/logout.php">
                                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <main class="py-4">
