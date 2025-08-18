<?php
// Test webhook endpoint with sample data
header('Content-Type: application/json');

echo "<h1>Webhook Test</h1>";

// Test data that mimics what Android SMS Gateway sends
$testData = [
    "event" => "sms:received",
    "payload" => [
        "message" => "Test SMS message from webhook test",
        "phoneNumber" => "+1234567890",
        "receivedAt" => date('c'),
        "sender" => "+0987654321",
        "senderName" => "Test Sender"
    ]
];

echo "<h2>Test Data:</h2>";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";

echo "<h2>Sending test webhook...</h2>";

// Send test webhook to our own endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/webhook.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h2>Response:</h2>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";

if ($error) {
    echo "<p><strong>cURL Error:</strong> $error</p>";
} else {
    echo "<p><strong>Response Body:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Check if we have any phone numbers registered
echo "<h2>Database Check:</h2>";

try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/includes/Database.php';
    require_once __DIR__ . '/includes/SMSManager.php';
    
    $smsManager = new SMSManager();
    $phoneNumbers = $smsManager->getPhoneNumbers();
    
    echo "<p><strong>Registered Phone Numbers:</strong></p>";
    if (empty($phoneNumbers)) {
        echo "<p style='color: red;'>❌ No phone numbers registered! You need to add phone numbers in the admin panel first.</p>";
        echo "<p><a href='admin/phone_numbers.php'>Add Phone Numbers</a></p>";
    } else {
        echo "<ul>";
        foreach ($phoneNumbers as $phone) {
            $status = $phone['is_active'] ? '✅ Active' : '❌ Inactive';
            echo "<li>{$phone['phone_number']} - {$phone['display_name']} - $status</li>";
        }
        echo "</ul>";
    }
    
    // Check recent messages
    $messages = $smsManager->getSMSMessages(null, 10);
    echo "<p><strong>Recent Messages:</strong></p>";
    if (empty($messages)) {
        echo "<p>No messages found in database.</p>";
    } else {
        echo "<ul>";
        foreach ($messages as $msg) {
            echo "<li>{$msg['received_at']} - {$msg['phone_number']} - " . substr($msg['message'], 0, 50) . "...</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>Webhook Log Check:</h2>";
$logFile = __DIR__ . '/webhook_errors.log';
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    echo "<pre>" . htmlspecialchars(tail($logContent, 20)) . "</pre>";
} else {
    echo "<p>No webhook log file found.</p>";
}

function tail($string, $lines) {
    $lines = array_slice(explode("\n", $string), -$lines);
    return implode("\n", $lines);
}

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Make sure you have phone numbers registered in the admin panel</li>";
echo "<li>Send a real SMS to one of your registered phone numbers</li>";
echo "<li>Check if the Android SMS Gateway is running and accessible</li>";
echo "<li>Verify the webhook was registered successfully with: <code>curl -u sms:te4vC4Yx http://10.100.102.12:8080/webhooks</code></li>";
echo "</ol>";
?>
