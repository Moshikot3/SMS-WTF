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

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>📱 Manage Phone Numbers</h2>
                <div>
                    <button type="button" class="btn btn-primary" data-modal="addPhoneModal">
                        Add Phone Number
                    </button>
                    <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5>Registered Phone Numbers</h5>
        </div>
        <div class="card-body">
            <?php if (empty($phoneNumbers)): ?>
                <div class="text-center py-5">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📱</div>
                    <h4>No phone numbers registered</h4>
                    <p class="text-muted">Add phone numbers to start receiving SMS messages.</p>
                    <button type="button" class="btn btn-primary" data-modal="addPhoneModal">
                        Add Your First Phone Number
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Display Name</th>
                                <th>Phone Number</th>
                                <th>Messages</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($phoneNumbers as $phone): ?>
                                <tr>
                                    <td>
                                        <span class="badge <?php echo $phone['is_active'] ? 'badge-success' : 'badge-secondary'; ?>">
                                            <?php echo $phone['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($phone['display_name'] ?: 'Unnamed'); ?></strong>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($phone['phone_number']); ?></code>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo $smsManager->getSMSCount($phone['phone_number']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($phone['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline" 
                                                    onclick="editPhone(<?php echo $phone['id']; ?>, '<?php echo htmlspecialchars(addslashes($phone['phone_number'])); ?>', '<?php echo htmlspecialchars(addslashes($phone['display_name'])); ?>')">
                                                Edit
                                            </button>
                                            <button type="button" class="btn btn-sm <?php echo $phone['is_active'] ? 'btn-warning' : 'btn-success'; ?>" 
                                                    onclick="togglePhone(<?php echo $phone['id']; ?>)">
                                                <?php echo $phone['is_active'] ? 'Disable' : 'Enable'; ?>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                                    data-url="phone_numbers.php" 
                                                    data-phone-id="<?php echo $phone['id']; ?>"
                                                    data-message="Are you sure you want to delete this phone number? All associated SMS messages will remain.">
                                                Delete
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
<div id="addPhoneModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h5>Add Phone Number</h5>
            <button type="button" class="close">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add_phone">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="phone_number" class="form-label">Phone Number</label>
                    <input type="tel" 
                           id="phone_number" 
                           name="phone_number" 
                           class="form-control" 
                           placeholder="+1234567890"
                           required>
                    <small class="text-muted">Include country code (e.g., +1 for US)</small>
                </div>
                
                <div class="form-group">
                    <label for="display_name" class="form-label">Display Name</label>
                    <input type="text" 
                           id="display_name" 
                           name="display_name" 
                           class="form-control" 
                           placeholder="My Phone"
                           maxlength="100">
                    <small class="text-muted">Optional friendly name for this number</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Phone Number</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Phone Modal -->
<div id="editPhoneModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h5>Edit Phone Number</h5>
            <button type="button" class="close">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="edit_phone">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                <input type="hidden" name="phone_id" id="edit_phone_id">
                
                <div class="form-group">
                    <label for="edit_phone_number" class="form-label">Phone Number</label>
                    <input type="tel" 
                           id="edit_phone_number" 
                           name="phone_number" 
                           class="form-control" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="edit_display_name" class="form-label">Display Name</label>
                    <input type="text" 
                           id="edit_display_name" 
                           name="display_name" 
                           class="form-control" 
                           maxlength="100">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Phone Number</button>
            </div>
        </form>
    </div>
</div>

<script>
function editPhone(id, phoneNumber, displayName) {
    document.getElementById('edit_phone_id').value = id;
    document.getElementById('edit_phone_number').value = phoneNumber;
    document.getElementById('edit_display_name').value = displayName;
    
    document.getElementById('editPhoneModal').classList.add('show');
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
    if (e.target.classList.contains('delete-btn')) {
        e.preventDefault();
        
        const phoneId = e.target.getAttribute('data-phone-id');
        const message = e.target.getAttribute('data-message');
        
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
