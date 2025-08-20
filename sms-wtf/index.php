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

<div class="container">
    <div class="row">
        <!-- Sidebar with Phone Numbers -->
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-gradient text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-telephone-fill me-2"></i>
                        Phone Numbers
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="?" class="list-group-item list-group-item-action phone-list-item <?php echo !$selectedPhone ? 'active' : ''; ?>">
                            <div class="phone-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="phone-name">All Numbers</div>
                                        <div class="phone-number text-muted">View all messages</div>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?php echo $smsManager->getSMSCount(); ?></span>
                                </div>
                            </div>
                        </a>
                        <?php foreach ($phoneNumbers as $phone): ?>
                            <a href="?phone=<?php echo urlencode($phone['phone_number']); ?>" 
                               class="list-group-item list-group-item-action phone-list-item <?php echo $selectedPhone === $phone['phone_number'] ? 'active' : ''; ?>">
                                <div class="phone-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="phone-name">
                                                <?php echo htmlspecialchars($phone['display_name'] ?: $phone['phone_number']); ?>
                                            </div>
                                            <div class="phone-number text-muted">
                                                <?php echo htmlspecialchars($phone['phone_number']); ?>
                                            </div>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">
                                            <?php echo $smsManager->getSMSCount($phone['phone_number']); ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="col-lg-9 col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-gradient text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-chat-dots-fill me-2"></i>
                            <?php if ($selectedPhone): ?>
                                Messages for <?php echo htmlspecialchars($selectedPhone); ?>
                            <?php else: ?>
                                All Messages
                            <?php endif; ?>
                        </h5>
                        <div class="header-actions">
                            <a href="admin/" class="btn btn-light btn-sm">
                                <i class="bi bi-gear me-1"></i>Admin Panel
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Enhanced Search -->
                    <div class="search-container">
                        <form method="GET" class="row g-3">
                            <?php if ($selectedPhone): ?>
                                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($selectedPhone); ?>">
                            <?php endif; ?>
                            <div class="col-md-9">
                                <div class="position-relative">
                                    <i class="bi bi-search search-icon"></i>
                                    <input type="text" 
                                           name="search" 
                                           id="search"
                                           class="form-control search-input" 
                                           placeholder="Search messages, sender numbers, or names..."
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-grid gap-2 d-md-flex">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search me-1"></i>Search
                                    </button>
                                    <?php if ($search): ?>
                                        <a href="<?php echo $selectedPhone ? '?phone=' . urlencode($selectedPhone) : '?'; ?>" 
                                           class="btn btn-outline-secondary">
                                            <i class="bi bi-x-circle me-1"></i>Clear
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Messages -->
                    <div class="sms-messages">
                        <?php include __DIR__ . '/templates/messages_partial.php'; ?>
                    </div>

                    <!-- Enhanced Pagination -->
                    <?php if ($totalPages > 1 && !$search): ?>
                        <nav aria-label="Messages pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                            <i class="bi bi-chevron-left"></i> Previous
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                if ($startPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                                    </li>
                                    <?php if ($startPage > 2): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <?php if ($i === $page): ?>
                                        <li class="page-item active">
                                            <span class="page-link"><?php echo $i; ?></span>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>">
                                            <?php echo $totalPages; ?>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                            Next <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<?php include __DIR__ . '/templates/footer.php'; ?>
