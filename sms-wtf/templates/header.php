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
    <link rel="stylesheet" href="<?php echo $assetsPath; ?>assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📱</text></svg>">
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="nav">
                <div>
                    <h1><?php echo htmlspecialchars(SITE_NAME); ?></h1>
                </div>
                <ul class="nav-links">
                    <?php if ($isAdminPage): ?>
                        <li><a href="<?php echo $basePath; ?>">Home</a></li>
                        <li><a href="./">Admin</a></li>
                        <?php if (isset($auth) && $auth->isAdminLoggedIn()): ?>
                            <li><a href="logout.php">Logout</a></li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li><a href="./">Home</a></li>
                        <li><a href="admin/">Admin</a></li>
                        <?php if (isset($auth) && $auth->isAdminLoggedIn()): ?>
                            <li><a href="admin/logout.php">Logout</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <main>
