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

// Handle delete message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_message') {
    if ($auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        try {
            $messageId = (int)$_POST['message_id'];
            $smsManager->deleteSMS($messageId);
            $success = 'Message deleted successfully';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = 'Invalid security token';
    }
}

// Get parameters
$selectedPhone = $_GET['phone'] ?? null;
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

// Get phone numbers
$phoneNumbers = $smsManager->getPhoneNumbers();

// Get SMS messages
if ($search) {
    $messages = $smsManager->searchSMS($search, $selectedPhone, $limit);
    $totalMessages = count($messages); // Approximate for search
} else {
    $messages = $smsManager->getSMSMessages($selectedPhone, $limit, $offset);
    $totalMessages = $smsManager->getSMSCount($selectedPhone);
}

$totalPages = ceil($totalMessages / $limit);

$pageTitle = 'All Messages - ' . SITE_NAME;
include __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>💬 All Messages</h2>
                <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <label for="phone" class="form-label">Filter by Phone Number</label>
                    <select name="phone" id="phone" class="form-control form-select">
                        <option value="">All Phone Numbers</option>
                        <?php foreach ($phoneNumbers as $phone): ?>
                            <option value="<?php echo htmlspecialchars($phone['phone_number']); ?>" 
                                    <?php echo $selectedPhone === $phone['phone_number'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($phone['display_name'] ?: $phone['phone_number']); ?>
                                (<?php echo $smsManager->getSMSCount($phone['phone_number']); ?> messages)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Search Messages</label>
                    <input type="text" 
                           name="search" 
                           id="search"
                           class="form-control" 
                           placeholder="Search messages, sender numbers, or names..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
            
            <?php if ($search || $selectedPhone): ?>
                <div class="mt-3">
                    <a href="messages.php" class="btn btn-secondary btn-sm">Clear Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Messages -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>
                <?php if ($selectedPhone): ?>
                    Messages for <?php echo htmlspecialchars($selectedPhone); ?>
                <?php elseif ($search): ?>
                    Search Results for "<?php echo htmlspecialchars($search); ?>"
                <?php else: ?>
                    All Messages
                <?php endif; ?>
                <span class="badge badge-info"><?php echo number_format($totalMessages); ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($messages)): ?>
                <div class="text-center py-5">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📭</div>
                    <h4>No messages found</h4>
                    <p class="text-muted">
                        <?php if ($search): ?>
                            No messages match your search criteria.
                        <?php elseif ($selectedPhone): ?>
                            No messages received for this phone number yet.
                        <?php else: ?>
                            No SMS messages have been received yet.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Received</th>
                                <th>To</th>
                                <th>From</th>
                                <th>Message</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $message): ?>
                                <tr>
                                    <td style="min-width: 120px;">
                                        <div><?php echo date('M j, Y', strtotime($message['received_at'])); ?></div>
                                        <small class="text-muted"><?php echo date('g:i A', strtotime($message['received_at'])); ?></small>
                                    </td>
                                    <td style="min-width: 150px;">
                                        <div><strong><?php echo htmlspecialchars($message['phone_display_name'] ?: $message['phone_number']); ?></strong></div>
                                        <?php if ($message['phone_display_name']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($message['phone_number']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="min-width: 150px;">
                                        <?php if ($message['sender_name']): ?>
                                            <div><strong><?php echo htmlspecialchars($message['sender_name']); ?></strong></div>
                                            <?php if ($message['sender_number']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($message['sender_number']); ?></small>
                                            <?php endif; ?>
                                        <?php elseif ($message['sender_number']): ?>
                                            <div><strong><?php echo htmlspecialchars($message['sender_number']); ?></strong></div>
                                        <?php else: ?>
                                            <span class="text-muted">Unknown Sender</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="max-width: 300px;">
                                        <div class="message-content" style="max-height: 100px; overflow-y: auto;">
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                        </div>
                                    </td>
                                    <td style="min-width: 120px;">
                                        <div class="btn-group-vertical">
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    data-modal="viewMessageModal"
                                                    onclick="viewMessage(<?php echo $message['id']; ?>, 
                                                                        '<?php echo htmlspecialchars(addslashes($message['message'])); ?>', 
                                                                        '<?php echo htmlspecialchars(addslashes($message['sender_name'] ?: $message['sender_number'] ?: 'Unknown')); ?>', 
                                                                        '<?php echo date('M j, Y g:i A', strtotime($message['received_at'])); ?>')">
                                                View
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline" 
                                                    onclick="copyToClipboard('<?php echo htmlspecialchars(addslashes($message['message'])); ?>')">
                                                Copy
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                                    data-url="messages.php" 
                                                    data-message-id="<?php echo $message['id']; ?>"
                                                    data-message="Are you sure you want to delete this message? This action cannot be undone.">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1 && !$search): ?>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalMessages); ?> of <?php echo number_format($totalMessages); ?> messages
                        </div>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    &laquo; Previous
                                </a>
                            <?php endif; ?>

                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <?php if ($i === $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    Next &raquo;
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- View Message Modal -->
<div id="viewMessageModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h5>Message Details</h5>
            <button type="button" class="close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">From:</label>
                <div id="modal-sender" class="form-control-static"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Received:</label>
                <div id="modal-time" class="form-control-static"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Message:</label>
                <div id="modal-message" class="form-control-static" style="white-space: pre-wrap; background: var(--surface-hover); padding: 1rem; border-radius: var(--border-radius); max-height: 300px; overflow-y: auto;"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary close">Close</button>
            <button type="button" class="btn btn-primary" onclick="copyModalMessage()">Copy Message</button>
        </div>
    </div>
</div>

<script>
let currentMessageText = '';

function viewMessage(id, message, sender, time) {
    document.getElementById('modal-sender').textContent = sender;
    document.getElementById('modal-time').textContent = time;
    document.getElementById('modal-message').textContent = message;
    currentMessageText = message;
    
    document.getElementById('viewMessageModal').classList.add('show');
}

function copyModalMessage() {
    copyToClipboard(currentMessageText);
}

// Handle delete buttons
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('delete-btn')) {
        e.preventDefault();
        
        const messageId = e.target.getAttribute('data-message-id');
        const message = e.target.getAttribute('data-message');
        
        if (confirm(message)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_message">
                <input type="hidden" name="message_id" value="${messageId}">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
});
</script>

<style>
.message-content {
    word-break: break-word;
    line-height: 1.4;
}

.btn-group-vertical {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.btn-group-vertical .btn {
    border-radius: var(--border-radius);
}

.form-control-static {
    padding: 0.375rem 0;
    margin-bottom: 0;
    min-height: calc(1.5em + 0.75rem);
}
</style>

<?php include __DIR__ . '/../templates/footer.php'; ?>
