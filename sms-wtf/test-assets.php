<?php
// Simple test page to check asset accessibility
?>
<!DOCTYPE html>
<html>
<head>
    <title>Asset Test Page</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-result { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <h1>Asset Accessibility Test</h1>
    
    <h2>From Root Directory:</h2>
    <div class="test-result">
        <strong>CSS:</strong> <a href="assets/css/style.css" target="_blank">assets/css/style.css</a>
    </div>
    <div class="test-result">
        <strong>JS:</strong> <a href="assets/js/app.js" target="_blank">assets/js/app.js</a>
    </div>
    
    <h2>From Admin Directory:</h2>
    <div class="test-result">
        <strong>CSS:</strong> <a href="admin/../assets/css/style.css" target="_blank">admin/../assets/css/style.css</a>
    </div>
    <div class="test-result">
        <strong>JS:</strong> <a href="admin/../assets/js/app.js" target="_blank">admin/../assets/js/app.js</a>
    </div>
    
    <h2>Direct Test Links:</h2>
    <div class="test-result">
        <a href="admin/" target="_blank">Go to Admin Panel</a>
    </div>
    
    <h2>File System Check:</h2>
    <div class="test-result">
        CSS file exists: <?php echo file_exists(__DIR__ . '/assets/css/style.css') ? '✅ Yes' : '❌ No'; ?>
    </div>
    <div class="test-result">
        JS file exists: <?php echo file_exists(__DIR__ . '/assets/js/app.js') ? '✅ Yes' : '❌ No'; ?>
    </div>
    
    <h2>Server Info:</h2>
    <div class="test-result">
        Document Root: <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Not set'; ?>
    </div>
    <div class="test-result">
        Request URI: <?php echo $_SERVER['REQUEST_URI'] ?? 'Not set'; ?>
    </div>
    <div class="test-result">
        Script Name: <?php echo $_SERVER['SCRIPT_NAME'] ?? 'Not set'; ?>
    </div>
    
    <script>
        // Test if JavaScript is loading
        console.log('JavaScript is working!');
        document.body.style.backgroundColor = '#f0f0f0';
    </script>
</body>
</html>
