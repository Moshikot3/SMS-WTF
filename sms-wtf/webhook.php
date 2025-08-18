<?php
header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/SMSManager.php';

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'webhook_errors.log');

function sendResponse($success, $message = '', $data = []) {
    http_response_code($success ? 200 : 400);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
    exit;
}

function logWebhook($data, $error = null) {
    $logEntry = [
        'timestamp' => date('c'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'data' => $data,
        'error' => $error
    ];
    
    error_log('Webhook: ' . json_encode($logEntry));
}

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Only POST requests are allowed');
    }

    // Get raw POST data
    $rawData = file_get_contents('php://input');
    
    if (empty($rawData)) {
        sendResponse(false, 'No data received');
    }

    // Parse JSON data
    $data = json_decode($rawData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logWebhook($rawData, 'Invalid JSON: ' . json_last_error_msg());
        sendResponse(false, 'Invalid JSON data');
    }

    // Log the incoming webhook
    logWebhook($data);

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
    $senderNumber = $payload['sender'] ?? $payload['from'] ?? $phoneNumber; // Use phoneNumber as sender if no sender field
    $senderName = $payload['senderName'] ?? null;

    // IMPORTANT: SMS Gateway seems to put sender number in phoneNumber field
    // We need to find which of our registered numbers actually received this SMS
    $smsManager = new SMSManager();
    $allRegisteredPhones = $smsManager->getPhoneNumbers(true); // Get active phones only
    
    if (count($allRegisteredPhones) == 1) {
        // If only one phone registered, use that as receiver
        $receiverPhone = $allRegisteredPhones[0]['phone_number'];
        $actualSender = $phoneNumber; // phoneNumber is actually the sender
        
        error_log("SMS Gateway correction: Receiver=$receiverPhone, Sender=$actualSender");
        
        $phoneNumber = $receiverPhone;
        $senderNumber = $actualSender;
    } else {
        // Multiple phones - need to determine which one received the SMS
        // For now, use the original logic and let admin handle multiple phones
        error_log("Multiple phones registered - using original phoneNumber: $phoneNumber");
    }

    // Validate phone number format
    $phoneNumber = preg_replace('/[^+\d]/', '', $phoneNumber);
    if (empty($phoneNumber)) {
        sendResponse(false, 'Invalid phone number format');
    }

    // Check if this phone number is registered in our system
    $registeredPhone = $smsManager->getPhoneNumberByNumber($phoneNumber);
    if (!$registeredPhone) {
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
        // If timestamp parsing fails, use current time
        $receivedTimestamp = date('Y-m-d H:i:s');
        error_log('Invalid timestamp format, using current time: ' . $receivedAt);
    }

    // Clean and validate sender number
    if ($senderNumber) {
        $senderNumber = preg_replace('/[^+\d]/', '', $senderNumber);
        if (empty($senderNumber)) {
            $senderNumber = null;
        }
    }

    // Clean sender name
    if ($senderName) {
        $senderName = trim($senderName);
        if (empty($senderName)) {
            $senderName = null;
        }
    }

    // Validate message content
    if (empty(trim($message))) {
        sendResponse(false, 'Message content cannot be empty');
    }

    // Limit message length (adjust as needed)
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
            // Log successful insertion
            error_log("SMS saved successfully. ID: $smsId, Phone: $phoneNumber, Sender: " . ($senderNumber ?: 'unknown'));
            
            sendResponse(true, 'SMS received and stored successfully', [
                'id' => $smsId,
                'phone_number' => $phoneNumber,
                'received_at' => $receivedTimestamp
            ]);
        } else {
            sendResponse(false, 'Failed to store SMS in database');
        }
    } catch (Exception $e) {
        error_log('Database error while storing SMS: ' . $e->getMessage());
        sendResponse(false, 'Database error occurred');
    }

} catch (Exception $e) {
    error_log('Webhook error: ' . $e->getMessage());
    sendResponse(false, 'Internal server error');
}
?>
