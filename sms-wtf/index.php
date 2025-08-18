<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/SMSManager.php';

$auth = new Auth();
$smsManager = new SMSManager();

// Handle site password if required
if ($auth->isSitePasswordEnabled() && !$auth->isUserLoggedIn()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['site_password'])) {
        if ($auth->loginUser($_POST['site_password'])) {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = 'Invalid password';
        }
    }
    
    // Show password form
    include __DIR__ . '/templates/password_form.php';
    exit;
}

// Get parameters
$selectedPhone = $_GET['phone'] ?? null;
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Get phone numbers
$phoneNumbers = $smsManager->getPhoneNumbers(true);

// Get SMS messages
if ($search) {
    $messages = $smsManager->searchSMS($search, $selectedPhone, $limit);
    $totalMessages = count($messages); // Approximate for search
} else {
    $messages = $smsManager->getSMSMessages($selectedPhone, $limit, $offset);
    $totalMessages = $smsManager->getSMSCount($selectedPhone);
}

$totalPages = ceil($totalMessages / $limit);

// If AJAX request, return only messages
if (isset($_GET['ajax'])) {
    include __DIR__ . '/templates/messages_partial.php';
    exit;
}

$pageTitle = 'SMS WTF - Received Messages';
include __DIR__ . '/templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5>Phone Numbers</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="?" class="list-group-item <?php echo !$selectedPhone ? 'active' : ''; ?>">
                            All Numbers
                            <span class="badge badge-primary"><?php echo $smsManager->getSMSCount(); ?></span>
                        </a>
                        <?php foreach ($phoneNumbers as $phone): ?>
                            <a href="?phone=<?php echo urlencode($phone['phone_number']); ?>" 
                               class="list-group-item <?php echo $selectedPhone === $phone['phone_number'] ? 'active' : ''; ?>">
                                <div class="phone-item">
                                    <div class="phone-name">
                                        <?php echo htmlspecialchars($phone['display_name'] ?: $phone['phone_number']); ?>
                                    </div>
                                    <div class="phone-number text-muted">
                                        <?php echo htmlspecialchars($phone['phone_number']); ?>
                                    </div>
                                    <span class="badge badge-primary">
                                        <?php echo $smsManager->getSMSCount($phone['phone_number']); ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>
                        <?php if ($selectedPhone): ?>
                            Messages for <?php echo htmlspecialchars($selectedPhone); ?>
                        <?php else: ?>
                            All Messages
                        <?php endif; ?>
                    </h5>
                    <div class="header-actions">
                        <a href="admin/" class="btn btn-primary btn-sm">Admin Panel</a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Search -->
                    <div class="search-box mb-4">
                        <form method="GET" class="d-flex">
                            <?php if ($selectedPhone): ?>
                                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($selectedPhone); ?>">
                            <?php endif; ?>
                            <input type="text" 
                                   name="search" 
                                   id="search"
                                   class="form-control search-input" 
                                   placeholder="Search messages, sender numbers, or names..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary ms-2">Search</button>
                            <?php if ($search): ?>
                                <a href="<?php echo $selectedPhone ? '?phone=' . urlencode($selectedPhone) : '?'; ?>" 
                                   class="btn btn-secondary ms-2">Clear</a>
                            <?php endif; ?>
                        </form>
                        <div class="search-icon">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div class="sms-messages">
                        <?php include __DIR__ . '/templates/messages_partial.php'; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1 && !$search): ?>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.list-group-item {
    border: 1px solid var(--border-color);
    margin-bottom: 0.5rem;
    border-radius: var(--border-radius);
    transition: all 0.2s;
}

.list-group-item:hover {
    background: var(--surface-hover);
    transform: translateY(-1px);
}

.list-group-item.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.phone-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.phone-name {
    font-weight: 600;
}

.phone-number {
    font-size: 0.875rem;
}

.badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: var(--primary-color);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
}

.list-group-item.active .badge {
    background: rgba(255, 255, 255, 0.3);
}
</style>

<?php include __DIR__ . '/templates/footer.php'; ?>
