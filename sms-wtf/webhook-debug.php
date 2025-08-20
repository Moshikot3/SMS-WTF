<?php
// Debug webhook - logs everything and shows detailed errors
header('Content-Type: application/json');

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/webhook_debug.log');

// Log everything that comes in
$logFile = __DIR__ . '/webhook_debug.log';
$timestamp = date('Y-m-d H:i:s');

// Log the request details
$requestLog = [
    'timestamp' => $timestamp,
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'raw_data' => file_get_contents('php://input'),
    'get_params' => $_GET,
    'post_params' => $_POST,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
];

file_put_contents($logFile, "\n=== WEBHOOK DEBUG $timestamp ===\n" . json_encode($requestLog, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/includes/Database.php';
    require_once __DIR__ . '/includes/SMSManager.php';

    function sendResponse($success, $message = '', $data = []) {
        global $logFile, $timestamp;
        
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ];
        
        file_put_contents($logFile, "RESPONSE: " . json_encode($response, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
        
        http_response_code($success ? 200 : 400);
        echo json_encode($response);
        exit;
    }

    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Only POST requests are allowed');
    }

    // Get raw POST data
    $rawData = file_get_contents('php://input');
    
    if (empty($rawData)) {
        sendResponse(false, 'No data received');
    }

    // Try to parse JSON data
    $data = json_decode($rawData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse(false, 'Invalid JSON data: ' . json_last_error_msg());
    }

    file_put_contents($logFile, "PARSED DATA: " . json_encode($data, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

    // Validate required fields
    if (!isset($data['event']) || !isset($data['payload'])) {
        sendResponse(false, 'Missing required fields: event or payload');
    }

    // Only process SMS received events
    if ($data['event'] !== 'sms:received') {
        sendResponse(false, 'Unsupported event type: ' . $data['event']);
    }

    $payload = $data['payload'];
    
    // Validate payload fields
    $requiredFields = ['message', 'phoneNumber', 'receivedAt'];
    foreach ($requiredFields as $field) {
        if (!isset($payload[$field])) {
            sendResponse(false, "Missing required payload field: $field");
        }
    }

    // Initialize SMS Manager
    $smsManager = new SMSManager();

    // Extract data from payload
    $phoneNumber = $payload['phoneNumber'];
    $message = $payload['message'];
    $receivedAt = $payload['receivedAt'];
    $senderNumber = $payload['sender'] ?? $payload['from'] ?? null;
    $senderName = $payload['senderName'] ?? null;

    file_put_contents($logFile, "EXTRACTED DATA (ORIGINAL):\n" . 
        "Phone (from payload): $phoneNumber\n" . 
        "Message: $message\n" . 
        "Received: $receivedAt\n" . 
        "Sender: " . ($senderNumber ?: 'null') . "\n" . 
        "Sender Name: " . ($senderName ?: 'null') . "\n", FILE_APPEND);

    // IMPORTANT: SMS Gateway behavior analysis:
    // - phoneNumber field can contain either a phone number OR a sender name
    // - We need to determine if phoneNumber is actually a phone number or sender name
    
    $isPhoneNumberActuallyPhone = preg_match('/^\+?\d+$/', $phoneNumber);
    
    $smsManager = new SMSManager();
    $allRegisteredPhones = $smsManager->getPhoneNumbers(true); // Get active phones only
    
    if (!$isPhoneNumberActuallyPhone) {
        // phoneNumber contains a sender name (like "Celerity")
        $senderName = $phoneNumber; // Use phoneNumber as sender name
        $senderNumber = null; // No phone number for this sender
        
        // Use the registered phone as receiver (assuming single phone setup)
        if (count($allRegisteredPhones) >= 1) {
            $phoneNumber = $allRegisteredPhones[0]['phone_number']; // Use first registered phone
            file_put_contents($logFile, "CORRECTED DATA (named sender):\n" . 
                "Receiver Phone: $phoneNumber\n" . 
                "Sender Name: $senderName\n" . 
                "Sender Number: null\n", FILE_APPEND);
        } else {
            sendResponse(false, 'No registered phone numbers found in system');
        }
    } else {
        // phoneNumber contains an actual phone number
        if (count($allRegisteredPhones) == 1) {
            // If only one phone registered, phoneNumber is likely the sender
            $receiverPhone = $allRegisteredPhones[0]['phone_number'];
            $actualSender = $phoneNumber; // phoneNumber is actually the sender
            
            file_put_contents($logFile, "CORRECTED DATA (phone number sender):\n" . 
                "Receiver Phone: $receiverPhone\n" . 
                "Sender Number: $actualSender\n", FILE_APPEND);
            
            $senderNumber = $actualSender;
            $phoneNumber = $receiverPhone; // Set receiver as our registered phone
        } else {
            // Multiple phones - use original logic
            file_put_contents($logFile, "MULTIPLE PHONES REGISTERED - NEED MANUAL LOGIC\n", FILE_APPEND);
        }
    }

    // Validate phone number format
    $phoneNumber = preg_replace('/[^+\d]/', '', $phoneNumber);
    if (empty($phoneNumber)) {
        sendResponse(false, 'Invalid phone number format');
    }

    // Check if this phone number is registered in our system
    $registeredPhone = $smsManager->getPhoneNumberByNumber($phoneNumber);
    if (!$registeredPhone) {
        file_put_contents($logFile, "PHONE NOT REGISTERED: $phoneNumber\n", FILE_APPEND);
        
        // List all registered phones for debugging
        $allPhones = $smsManager->getPhoneNumbers();
        file_put_contents($logFile, "REGISTERED PHONES: " . json_encode($allPhones, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
        
        sendResponse(false, 'Phone number not registered in system: ' . $phoneNumber);
    }

    if (!$registeredPhone['is_active']) {
        sendResponse(false, 'Phone number is disabled: ' . $phoneNumber);
    }

    // Validate and parse received timestamp
    $receivedTimestamp = null;
    try {
        $receivedTimestamp = new DateTime($receivedAt);
        $receivedTimestamp = $receivedTimestamp->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        $receivedTimestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "TIMESTAMP ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    }

    // Clean sender data (only if it's actually a number)
    if ($senderNumber) {
        $cleanedSenderNumber = preg_replace('/[^+\d]/', '', $senderNumber);
        if (!empty($cleanedSenderNumber)) {
            $senderNumber = $cleanedSenderNumber;
        } else {
            // If cleaning results in empty string, keep original or set to null
            $senderNumber = null;
        }
    }

    if ($senderName) {
        $senderName = trim($senderName);
        if (empty($senderName)) {
            $senderName = null;
        }
    }

    file_put_contents($logFile, "FINAL SENDER DATA:\n" . 
        "Sender Number: " . ($senderNumber ?: 'null') . "\n" . 
        "Sender Name: " . ($senderName ?: 'null') . "\n", FILE_APPEND);

    // Validate message content
    if (empty(trim($message))) {
        sendResponse(false, 'Message content cannot be empty');
    }

    // Limit message length
    if (strlen($message) > 10000) {
        $message = substr($message, 0, 10000) . '... [truncated]';
    }

    // Insert SMS into database
    try {
        $smsId = $smsManager->addSMS(
            $phoneNumber,
            $senderNumber,
            $senderName,
            $message,
            $receivedTimestamp
        );

        if ($smsId) {
            file_put_contents($logFile, "SMS SAVED SUCCESSFULLY: ID=$smsId\n", FILE_APPEND);
            
            sendResponse(true, 'SMS received and stored successfully', [
                'id' => $smsId,
                'phone_number' => $phoneNumber,
                'received_at' => $receivedTimestamp
            ]);
        } else {
            sendResponse(false, 'Failed to store SMS in database');
        }
    } catch (Exception $e) {
        file_put_contents($logFile, "DATABASE ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        sendResponse(false, 'Database error occurred: ' . $e->getMessage());
    }

} catch (Exception $e) {
    file_put_contents($logFile, "FATAL ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
