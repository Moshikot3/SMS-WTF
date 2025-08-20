// SMS WTF - Modern JavaScript with Bootstrap Integration

class SMSApp {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initBootstrap();
        this.initSearch();
        this.autoRefresh();
    }

    bindEvents() {
        // Form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('ajax-form')) {
                e.preventDefault();
                this.handleAjaxForm(e.target);
            }
        });

        // Delete confirmations
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-btn') || e.target.closest('.delete-btn')) {
                e.preventDefault();
                this.confirmDelete(e.target.closest('.delete-btn') || e.target);
            }
        });
    }

    initBootstrap() {
        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialize Bootstrap popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }

    initSearch() {
        const searchInput = document.getElementById('search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    // Auto search is disabled for better UX, user needs to click search
                    // this.performSearch(e.target.value);
                }, 1000);
            });

            // Add search icon click functionality
            const searchIcon = document.querySelector('.search-icon');
            if (searchIcon) {
                searchIcon.addEventListener('click', () => {
                    searchInput.focus();
                });
            }
        }
    }

    autoRefresh() {
        // Auto-refresh SMS messages every 60 seconds
        if (document.querySelector('.sms-messages')) {
            setInterval(() => {
                this.refreshMessages();
            }, 60000);
        }
    }

    handleAjaxForm(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing...';

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showToast(data.message || 'Operation completed successfully', 'success');
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else if (data.reload) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            } else {
                this.showToast(data.message || 'An error occurred', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showToast('An error occurred. Please try again.', 'danger');
        })
        .finally(() => {
            // Restore button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }

    confirmDelete(button) {
        const message = button.getAttribute('data-message') || 'Are you sure you want to delete this item?';
        
        // Create Bootstrap modal for confirmation
        const modalHtml = `
            <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                                Confirm Delete
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>${message}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                                <i class="bi bi-trash me-1"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        const existingModal = document.getElementById('deleteConfirmModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        modal.show();

        // Handle confirm button click
        document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
            const url = button.href || button.getAttribute('data-url');
            const row = button.closest('tr');
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'csrf_token': this.getCSRFToken()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showToast(data.message || 'Item deleted successfully', 'success');
                    if (row) {
                        row.style.opacity = '0.5';
                        setTimeout(() => {
                            row.remove();
                        }, 500);
                    }
                } else {
                    this.showToast(data.message || 'Failed to delete item', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showToast('An error occurred. Please try again.', 'danger');
            });

            modal.hide();
        });
    }

    performSearch(query) {
        const messagesContainer = document.querySelector('.sms-messages');
        if (!messagesContainer) return;

        // Show loading overlay
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'd-flex justify-content-center align-items-center position-absolute top-0 start-0 w-100 h-100';
        loadingOverlay.style.backgroundColor = 'rgba(255, 255, 255, 0.8)';
        loadingOverlay.style.zIndex = '10';
        loadingOverlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
        
        messagesContainer.style.position = 'relative';
        messagesContainer.appendChild(loadingOverlay);

        const params = new URLSearchParams({
            search: query,
            ajax: '1'
        });

        // Add current phone filter if exists
        const phoneParam = new URLSearchParams(window.location.search).get('phone');
        if (phoneParam) {
            params.append('phone', phoneParam);
        }

        fetch(`${window.location.pathname}?${params}`)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newMessages = doc.querySelector('.sms-messages');
            
            if (newMessages) {
                messagesContainer.innerHTML = newMessages.innerHTML;
                this.initBootstrap(); // Re-initialize tooltips for new content
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            this.showToast('Search failed. Please try again.', 'warning');
        });
    }

    refreshMessages() {
        const messagesContainer = document.querySelector('.sms-messages');
        if (!messagesContainer) return;

        const params = new URLSearchParams({
            ajax: '1',
            refresh: '1'
        });

        // Add current filters
        const currentParams = new URLSearchParams(window.location.search);
        for (const [key, value] of currentParams) {
            if (key !== 'ajax' && key !== 'refresh') {
                params.append(key, value);
            }
        }

        fetch(`${window.location.pathname}?${params}`)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newMessages = doc.querySelector('.sms-messages');
            
            if (newMessages && newMessages.innerHTML !== messagesContainer.innerHTML) {
                messagesContainer.innerHTML = newMessages.innerHTML;
                this.showToast('New messages received!', 'info');
                this.initBootstrap(); // Re-initialize tooltips for new content
                
                // Add a subtle animation to indicate refresh
                messagesContainer.style.animation = 'pulse 0.5s ease-in-out';
                setTimeout(() => {
                    messagesContainer.style.animation = '';
                }, 500);
            }
        })
        .catch(error => {
            console.error('Refresh error:', error);
        });
    }

    showToast(message, type = 'info') {
        // Create Bootstrap toast
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 1055;">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-${this.getToastIcon(type)} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = document.body.lastElementChild;
        const toast = new bootstrap.Toast(toastElement, { delay: 4000 });
        
        toast.show();
        
        // Remove toast element after it's hidden
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }

    getToastIcon(type) {
        const icons = {
            'success': 'check-circle-fill',
            'danger': 'exclamation-triangle-fill',
            'warning': 'exclamation-triangle-fill',
            'info': 'info-circle-fill',
            'primary': 'info-circle-fill'
        };
        return icons[type] || 'info-circle-fill';
    }

    getCSRFToken() {
        const tokenInput = document.querySelector('input[name="csrf_token"]');
        return tokenInput ? tokenInput.value : '';
    }

    formatPhoneNumber(phone) {
        // International phone number formatting
        if (phone.startsWith('+')) {
            return phone;
        }
        if (phone.length === 10) {
            return phone.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
        }
        if (phone.length === 11 && phone.startsWith('1')) {
            return phone.replace(/1(\d{3})(\d{3})(\d{4})/, '+1 ($1) $2-$3');
        }
        return phone;
    }

    timeAgo(date) {
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) {
            return 'just now';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else {
            const days = Math.floor(diffInSeconds / 86400);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        }
    }

    // Animation utility
    animateElement(element, animation = 'fadeIn') {
        element.style.animation = `${animation} 0.3s ease-in-out`;
        setTimeout(() => {
            element.style.animation = '';
        }, 300);
    }
}

// Add custom CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }
    
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .search-input:focus {
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25) !important;
    }
    
    .card:hover {
        transition: transform 0.2s ease-in-out;
    }
    
    .btn:hover {
        transform: translateY(-1px);
        transition: all 0.2s ease-in-out;
    }
    
    .sms-message {
        transition: all 0.2s ease-in-out;
    }
    
    .sms-message:hover {
        transform: translateX(2px);
    }
    
    .phone-list-item {
        transition: all 0.2s ease-in-out;
    }
    
    .phone-list-item:hover {
        transform: translateY(-1px);
    }
`;
document.head.appendChild(style);

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.smsApp = new SMSApp();
});

// Enhanced utility functions
window.copyToClipboard = function(text, button = null) {
    navigator.clipboard.writeText(text).then(() => {
        if (button) {
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="bi bi-check-lg me-1"></i>Copied!';
            button.classList.add('copy-success');
            
            setTimeout(() => {
                button.innerHTML = originalHtml;
                button.classList.remove('copy-success');
            }, 2000);
        }
        
        window.smsApp.showToast('Copied to clipboard!', 'success');
    }).catch(() => {
        window.smsApp.showToast('Failed to copy to clipboard', 'danger');
    });
};

window.shareMessage = function(message) {
    if (navigator.share) {
        navigator.share({
            title: 'SMS Message',
            text: message
        }).catch(() => {
            // Fallback to copy
            window.copyToClipboard(message);
        });
    } else {
        // Fallback to copy
        window.copyToClipboard(message);
    }
};

window.togglePhoneNumber = function(id) {
    fetch(`admin/ajax.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'toggle_phone',
            'id': id,
            'csrf_token': window.smsApp.getCSRFToken()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.smsApp.showToast(data.message || 'Phone status updated', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            window.smsApp.showToast(data.message || 'Error updating phone status', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.smsApp.showToast('An error occurred', 'danger');
    });
};

// Auto-hide alerts after page load
window.addEventListener('load', () => {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });
});
