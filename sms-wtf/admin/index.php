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

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Admin Dashboard</h2>
                <div>
                    <span class="text-muted">Welcome, <?php echo htmlspecialchars($auth->getAdminUser()['username']); ?></span>
                    <a href="logout.php" class="btn btn-secondary btn-sm ms-2">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h1 class="text-primary"><?php echo number_format($totalMessages); ?></h1>
                    <p class="mb-0">Total Messages</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h1 class="text-success"><?php echo $activePhones; ?></h1>
                    <p class="mb-0">Active Numbers</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h1 class="text-warning"><?php echo $totalPhones - $activePhones; ?></h1>
                    <p class="mb-0">Inactive Numbers</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h1 class="text-info"><?php echo $totalPhones; ?></h1>
                    <p class="mb-0">Total Numbers</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="phone_numbers.php" class="btn btn-primary w-100">
                                📱 Manage Phone Numbers
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="messages.php" class="btn btn-info w-100">
                                💬 View All Messages
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="settings.php" class="btn btn-warning w-100">
                                ⚙️ Site Settings
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="../" class="btn btn-secondary w-100">
                                🏠 View Public Site
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Messages -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Recent Messages</h5>
                    <a href="messages.php" class="btn btn-primary btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentMessages)): ?>
                        <div class="text-center py-4">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
                            <h6>No messages yet</h6>
                            <p class="text-muted">Messages will appear here once they are received via webhook.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Received</th>
                                        <th>To Number</th>
                                        <th>From</th>
                                        <th>Message Preview</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentMessages as $message): ?>
                                        <tr>
                                            <td>
                                                <div><?php echo date('M j, g:i A', strtotime($message['received_at'])); ?></div>
                                                <small class="text-muted"><?php echo date('Y', strtotime($message['received_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div><?php echo htmlspecialchars($message['phone_display_name'] ?: $message['phone_number']); ?></div>
                                                <?php if ($message['phone_display_name']): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($message['phone_number']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($message['sender_name']): ?>
                                                    <div><?php echo htmlspecialchars($message['sender_name']); ?></div>
                                                    <?php if ($message['sender_number']): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($message['sender_number']); ?></small>
                                                    <?php endif; ?>
                                                <?php elseif ($message['sender_number']): ?>
                                                    <div><?php echo htmlspecialchars($message['sender_number']); ?></div>
                                                <?php else: ?>
                                                    <span class="text-muted">Unknown</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $preview = strlen($message['message']) > 100 
                                                    ? substr($message['message'], 0, 100) . '...' 
                                                    : $message['message'];
                                                echo htmlspecialchars($preview);
                                                ?>
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
    </div>

    <!-- Webhook Information -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>📡 Webhook Information</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>Webhook URL</h6>
                        <p class="mb-2">Use this URL to configure your SMS Gateway webhook:</p>
                        <code><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2) . '/webhook.php'; ?></code>
                        
                        <h6 class="mt-3">Example Configuration</h6>
                        <pre><code>curl -X POST -u &lt;username&gt;:&lt;password&gt; \
  -H "Content-Type: application/json" \
  -d '{ "id": "sms-wtf-webhook", "url": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2) . '/webhook.php'; ?>", "event": "sms:received" }' \
  http://&lt;device_local_ip&gt;:8080/webhooks</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
