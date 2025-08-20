<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/SMSManager.php';

$auth = new Auth();

// Check admin authentication
if (!$auth->isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$smsManager = new SMSManager();

// Get statistics
$totalMessages = $smsManager->getSMSCount();
$phoneNumbers = $smsManager->getPhoneNumbers();
$totalPhones = count($phoneNumbers);
$activePhones = count(array_filter($phoneNumbers, function($phone) { return $phone['is_active']; }));

// Get recent messages
$recentMessages = $smsManager->getSMSMessages(null, 10);

$pageTitle = 'Admin Dashboard - ' . SITE_NAME;
include __DIR__ . '/../templates/header.php';
?>

<div class="container">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="text-gradient mb-1">
                        <i class="bi bi-speedometer2 me-2"></i>Admin Dashboard
                    </h2>
                    <p class="text-muted mb-0">Welcome back, manage your SMS system</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-success px-3 py-2">
                        <i class="bi bi-person-check-fill me-1"></i>
                        <?php echo htmlspecialchars($auth->getAdminUser()['username']); ?>
                    </span>
                    <a href="logout.php" class="btn btn-outline-danger">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stats-card primary">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="bi bi-chat-dots-fill text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <div class="stats-number text-primary"><?php echo number_format($totalMessages); ?></div>
                    <p class="mb-0 fw-semibold">Total Messages</p>
                    <small class="text-muted">All received SMS</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stats-card success">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="bi bi-telephone-fill text-success" style="font-size: 2rem;"></i>
                    </div>
                    <div class="stats-number text-success"><?php echo $activePhones; ?></div>
                    <p class="mb-0 fw-semibold">Active Numbers</p>
                    <small class="text-muted">Receiving messages</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stats-card warning">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="bi bi-telephone-x-fill text-warning" style="font-size: 2rem;"></i>
                    </div>
                    <div class="stats-number text-warning"><?php echo $totalPhones - $activePhones; ?></div>
                    <p class="mb-0 fw-semibold">Inactive Numbers</p>
                    <small class="text-muted">Not receiving</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stats-card info">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="bi bi-list-ol text-info" style="font-size: 2rem;"></i>
                    </div>
                    <div class="stats-number text-info"><?php echo $totalPhones; ?></div>
                    <p class="mb-0 fw-semibold">Total Numbers</p>
                    <small class="text-muted">Registered phones</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-gradient text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-lightning-fill me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6">
                            <a href="phone_numbers.php" class="btn btn-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                <i class="bi bi-telephone-plus-fill mb-2" style="font-size: 2rem;"></i>
                                <span class="fw-semibold">Manage Phones</span>
                                <small class="text-white-50">Add & configure numbers</small>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="messages.php" class="btn btn-info w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                <i class="bi bi-chat-square-text-fill mb-2" style="font-size: 2rem;"></i>
                                <span class="fw-semibold">View Messages</span>
                                <small class="text-white-50">Browse all SMS</small>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="settings.php" class="btn btn-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                <i class="bi bi-gear-fill mb-2" style="font-size: 2rem;"></i>
                                <span class="fw-semibold">Settings</span>
                                <small class="text-white-50">Configure system</small>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="../" class="btn btn-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                <i class="bi bi-house-fill mb-2" style="font-size: 2rem;"></i>
                                <span class="fw-semibold">Public View</span>
                                <small class="text-white-50">Visit main site</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Messages -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-gradient text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>Recent Messages
                        </h5>
                        <a href="messages.php" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-right me-1"></i>View All
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($recentMessages)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📭</div>
                            <h6>No messages yet</h6>
                            <p class="text-muted mb-3">Messages will appear here once they are received via webhook.</p>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <a href="phone_numbers.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i>Add Phone Number
                                </a>
                                <a href="#webhookInfo" class="btn btn-outline-info">
                                    <i class="bi bi-info-circle me-1"></i>View Webhook Info
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <i class="bi bi-calendar3 me-1"></i>Received
                                        </th>
                                        <th>
                                            <i class="bi bi-telephone me-1"></i>To Number
                                        </th>
                                        <th>
                                            <i class="bi bi-person me-1"></i>From
                                        </th>
                                        <th>
                                            <i class="bi bi-chat-dots me-1"></i>Preview
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentMessages as $message): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo date('M j, g:i A', strtotime($message['received_at'])); ?></div>
                                                <small class="text-muted"><?php echo date('Y', strtotime($message['received_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($message['phone_display_name'] ?: $message['phone_number']); ?></div>
                                                <?php if ($message['phone_display_name']): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($message['phone_number']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($message['sender_name']): ?>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($message['sender_name']); ?></div>
                                                    <?php if ($message['sender_number']): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($message['sender_number']); ?></small>
                                                    <?php endif; ?>
                                                <?php elseif ($message['sender_number']): ?>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($message['sender_number']); ?></div>
                                                <?php else: ?>
                                                    <span class="text-muted">Unknown</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="message-preview">
                                                    <?php 
                                                    $preview = strlen($message['message']) > 80 
                                                        ? substr($message['message'], 0, 80) . '...' 
                                                        : $message['message'];
                                                    echo htmlspecialchars($preview);
                                                    ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Webhook Information -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100" id="webhookInfo">
                <div class="card-header bg-gradient text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-broadcast me-2"></i>Webhook Setup
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border-0 mb-3">
                        <h6 class="alert-heading">
                            <i class="bi bi-info-circle me-2"></i>Webhook URL
                        </h6>
                        <p class="mb-2 small">Configure your SMS Gateway with this URL:</p>
                        <div class="position-relative">
                            <code class="d-block p-2 bg-light rounded text-break" style="font-size: 0.8rem;">
                                <?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2) . '/webhook.php'; ?>
                            </code>
                            <button class="btn btn-sm btn-outline-secondary position-absolute top-0 end-0 mt-1 me-1" 
                                    onclick="copyToClipboard('<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2) . '/webhook.php'; ?>', this)" 
                                    data-bs-toggle="tooltip" title="Copy URL">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>
                    
                    <h6 class="mb-2">
                        <i class="bi bi-terminal me-2"></i>Example Configuration
                    </h6>
                    <div class="position-relative">
                        <pre class="bg-dark text-light p-3 rounded small"><code>curl -X POST -u &lt;username&gt;:&lt;password&gt; \
  -H "Content-Type: application/json" \
  -d '{
    "id": "sms-wtf-webhook",
    "url": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2) . '/webhook.php'; ?>",
    "event": "sms:received"
  }' \
  http://&lt;device_ip&gt;:8080/webhooks</code></pre>
                        <button class="btn btn-sm btn-outline-light position-absolute top-0 end-0 mt-2 me-2" 
                                onclick="copyToClipboard(`curl -X POST -u <username>:<password> \\
  -H \"Content-Type: application/json\" \\
  -d '{
    \"id\": \"sms-wtf-webhook\",
    \"url\": \"<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2) . '/webhook.php'; ?>\",
    \"event\": \"sms:received\"
  }' \\
  http://<device_ip>:8080/webhooks`, this)" 
                                data-bs-toggle="tooltip" title="Copy command">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                    
                    <div class="mt-3">
                        <a href="https://github.com/android-sms-gateway/android-sms-gateway" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-github me-1"></i>SMS Gateway Documentation
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
