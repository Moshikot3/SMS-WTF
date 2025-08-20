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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        
        try {
            switch ($action) {
                case 'add_phone':
                    $phoneNumber = preg_replace('/[^+\d]/', '', $_POST['phone_number']);
                    $displayName = trim($_POST['display_name']);
                    
                    if (empty($phoneNumber)) {
                        throw new Exception('Invalid phone number format');
                    }
                    
                    $smsManager->addPhoneNumber($phoneNumber, $displayName, $auth->getAdminUser()['id']);
                    $success = 'Phone number added successfully';
                    break;
                    
                case 'edit_phone':
                    $id = (int)$_POST['phone_id'];
                    $phoneNumber = preg_replace('/[^+\d]/', '', $_POST['phone_number']);
                    $displayName = trim($_POST['display_name']);
                    
                    if (empty($phoneNumber)) {
                        throw new Exception('Invalid phone number format');
                    }
                    
                    $smsManager->updatePhoneNumber($id, $phoneNumber, $displayName);
                    $success = 'Phone number updated successfully';
                    break;
                    
                case 'delete_phone':
                    $id = (int)$_POST['phone_id'];
                    $smsManager->deletePhoneNumber($id);
                    $success = 'Phone number deleted successfully';
                    break;
                    
                case 'toggle_phone':
                    $id = (int)$_POST['phone_id'];
                    $smsManager->togglePhoneNumberStatus($id);
                    $success = 'Phone number status updated';
                    break;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get phone numbers
$phoneNumbers = $smsManager->getPhoneNumbers();

$pageTitle = 'Manage Phone Numbers - ' . SITE_NAME;
include __DIR__ . '/../templates/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="text-gradient mb-1">
                        <i class="bi bi-telephone-plus me-2"></i>Manage Phone Numbers
                    </h2>
                    <p class="text-muted mb-0">Configure phone numbers for receiving SMS messages</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPhoneModal">
                        <i class="bi bi-plus-circle me-1"></i>Add Phone Number
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-gradient text-white">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>Registered Phone Numbers
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($phoneNumbers)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📱</div>
                    <h4>No phone numbers registered</h4>
                    <p class="text-muted mb-4">Add phone numbers to start receiving SMS messages via webhook.</p>
                    <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addPhoneModal">
                        <i class="bi bi-plus-circle me-2"></i>Add Your First Phone Number
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>
                                    <i class="bi bi-toggle-on me-1"></i>Status
                                </th>
                                <th>
                                    <i class="bi bi-tag me-1"></i>Display Name
                                </th>
                                <th>
                                    <i class="bi bi-telephone me-1"></i>Phone Number
                                </th>
                                <th>
                                    <i class="bi bi-chat-dots me-1"></i>Messages
                                </th>
                                <th>
                                    <i class="bi bi-calendar3 me-1"></i>Created
                                </th>
                                <th>
                                    <i class="bi bi-gear me-1"></i>Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($phoneNumbers as $phone): ?>
                                <tr>
                                    <td>
                                        <span class="badge <?php echo $phone['is_active'] ? 'bg-success' : 'bg-secondary'; ?> px-2 py-1">
                                            <i class="bi bi-<?php echo $phone['is_active'] ? 'check-circle' : 'x-circle'; ?> me-1"></i>
                                            <?php echo $phone['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($phone['display_name'] ?: 'Unnamed'); ?></div>
                                    </td>
                                    <td>
                                        <code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($phone['phone_number']); ?></code>
                                    </td>
                                    <td>
                                        <span class="badge bg-info px-2 py-1">
                                            <i class="bi bi-envelope me-1"></i>
                                            <?php echo $smsManager->getSMSCount($phone['phone_number']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><?php echo date('M j, Y', strtotime($phone['created_at'])); ?></div>
                                        <small class="text-muted"><?php echo date('g:i A', strtotime($phone['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editPhone(<?php echo $phone['id']; ?>, '<?php echo htmlspecialchars(addslashes($phone['phone_number'])); ?>', '<?php echo htmlspecialchars(addslashes($phone['display_name'])); ?>')"
                                                    data-bs-toggle="tooltip" title="Edit phone number">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm <?php echo $phone['is_active'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>" 
                                                    onclick="togglePhone(<?php echo $phone['id']; ?>)"
                                                    data-bs-toggle="tooltip" title="<?php echo $phone['is_active'] ? 'Disable' : 'Enable'; ?> phone number">
                                                <i class="bi bi-<?php echo $phone['is_active'] ? 'pause' : 'play'; ?>-fill"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-btn" 
                                                    data-url="phone_numbers.php" 
                                                    data-phone-id="<?php echo $phone['id']; ?>"
                                                    data-message="Are you sure you want to delete this phone number? All associated SMS messages will remain."
                                                    data-bs-toggle="tooltip" title="Delete phone number">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

<!-- Add Phone Modal -->
<div class="modal fade" id="addPhoneModal" tabindex="-1" aria-labelledby="addPhoneModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPhoneModalLabel">
                    <i class="bi bi-telephone-plus me-2"></i>Add Phone Number
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_phone">
                    <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="phone_number" class="form-label fw-semibold">
                            <i class="bi bi-telephone me-1"></i>Phone Number
                        </label>
                        <input type="tel" 
                               id="phone_number" 
                               name="phone_number" 
                               class="form-control" 
                               placeholder="+1234567890"
                               required>
                        <div class="form-text">
                            <i class="bi bi-info-circle me-1"></i>
                            Include country code (e.g., +1 for US, +972 for Israel)
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="display_name" class="form-label fw-semibold">
                            <i class="bi bi-tag me-1"></i>Display Name
                        </label>
                        <input type="text" 
                               id="display_name" 
                               name="display_name" 
                               class="form-control" 
                               placeholder="My Phone"
                               maxlength="100">
                        <div class="form-text">
                            <i class="bi bi-lightbulb me-1"></i>
                            Optional friendly name for this number
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Add Phone Number
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Phone Modal -->
<div class="modal fade" id="editPhoneModal" tabindex="-1" aria-labelledby="editPhoneModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPhoneModalLabel">
                    <i class="bi bi-pencil me-2"></i>Edit Phone Number
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_phone">
                    <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                    <input type="hidden" name="phone_id" id="edit_phone_id">
                    
                    <div class="mb-3">
                        <label for="edit_phone_number" class="form-label fw-semibold">
                            <i class="bi bi-telephone me-1"></i>Phone Number
                        </label>
                        <input type="tel" 
                               id="edit_phone_number" 
                               name="phone_number" 
                               class="form-control" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_display_name" class="form-label fw-semibold">
                            <i class="bi bi-tag me-1"></i>Display Name
                        </label>
                        <input type="text" 
                               id="edit_display_name" 
                               name="display_name" 
                               class="form-control" 
                               maxlength="100">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Update Phone Number
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Initialize Bootstrap 5 modal instances
const editPhoneModal = new bootstrap.Modal(document.getElementById('editPhoneModal'));

function editPhone(id, phoneNumber, displayName) {
    document.getElementById('edit_phone_id').value = id;
    document.getElementById('edit_phone_number').value = phoneNumber;
    document.getElementById('edit_display_name').value = displayName;
    
    // Show the modal using Bootstrap 5 API
    editPhoneModal.show();
}

function togglePhone(id) {
    if (confirm('Are you sure you want to change the status of this phone number?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="toggle_phone">
            <input type="hidden" name="phone_id" value="${id}">
            <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Handle delete buttons
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('delete-btn') || e.target.closest('.delete-btn')) {
        e.preventDefault();
        
        const button = e.target.classList.contains('delete-btn') ? e.target : e.target.closest('.delete-btn');
        const phoneId = button.getAttribute('data-phone-id');
        const message = button.getAttribute('data-message');
        
        if (confirm(message)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_phone">
                <input type="hidden" name="phone_id" value="${phoneId}">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
});

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-focus on modal show
    document.getElementById('addPhoneModal').addEventListener('shown.bs.modal', function () {
        document.getElementById('phone_number').focus();
    });

    document.getElementById('editPhoneModal').addEventListener('shown.bs.modal', function () {
        document.getElementById('edit_phone_number').focus();
    });
    
    // Clear form when modals are hidden
    document.getElementById('addPhoneModal').addEventListener('hidden.bs.modal', function () {
        this.querySelector('form').reset();
    });

    document.getElementById('editPhoneModal').addEventListener('hidden.bs.modal', function () {
        this.querySelector('form').reset();
    });
});
</script>

<style>
.badge-success {
    background: var(--success-color);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
}

.badge-secondary {
    background: var(--text-secondary);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
}

.badge-info {
    background: var(--info-color);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
}

.btn-group {
    display: flex;
    gap: 0.25rem;
}
</style>

<?php include __DIR__ . '/../templates/footer.php'; ?>
