// SMS WTF - JavaScript Functions

class SMSApp {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initModals();
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
            if (e.target.classList.contains('delete-btn')) {
                e.preventDefault();
                this.confirmDelete(e.target);
            }
        });

        // Modal triggers
        document.addEventListener('click', (e) => {
            if (e.target.hasAttribute('data-modal')) {
                e.preventDefault();
                this.openModal(e.target.getAttribute('data-modal'));
            }
        });

        // Close modals
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal') || e.target.classList.contains('close')) {
                this.closeModal();
            }
        });

        // Escape key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }

    initModals() {
        // Prevent modal content clicks from closing modal
        document.querySelectorAll('.modal-content').forEach(content => {
            content.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        });
    }

    initSearch() {
        const searchInput = document.getElementById('search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performSearch(e.target.value);
                }, 500);
            });
        }
    }

    autoRefresh() {
        // Auto-refresh SMS messages every 30 seconds
        if (document.querySelector('.sms-messages')) {
            setInterval(() => {
                this.refreshMessages();
            }, 30000);
        }
    }

    handleAjaxForm(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;

        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading"></span> Processing...';

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showAlert(data.message || 'Operation completed successfully', 'success');
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else if (data.reload) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
                this.closeModal();
            } else {
                this.showAlert(data.message || 'An error occurred', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showAlert('An error occurred. Please try again.', 'error');
        })
        .finally(() => {
            // Restore button state
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    }

    confirmDelete(button) {
        const message = button.getAttribute('data-message') || 'Are you sure you want to delete this item?';
        
        if (confirm(message)) {
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
                    this.showAlert(data.message || 'Item deleted successfully', 'success');
                    if (row) {
                        row.style.opacity = '0.5';
                        setTimeout(() => {
                            row.remove();
                        }, 500);
                    }
                } else {
                    this.showAlert(data.message || 'Failed to delete item', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showAlert('An error occurred. Please try again.', 'error');
            });
        }
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal() {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            modal.classList.remove('show');
        });
        document.body.style.overflow = '';
    }

    performSearch(query) {
        const messagesContainer = document.querySelector('.sms-messages');
        if (!messagesContainer) return;

        // Show loading
        messagesContainer.style.opacity = '0.5';

        const params = new URLSearchParams({
            search: query,
            ajax: '1'
        });

        fetch(`${window.location.pathname}?${params}`)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newMessages = doc.querySelector('.sms-messages');
            
            if (newMessages) {
                messagesContainer.innerHTML = newMessages.innerHTML;
            }
        })
        .catch(error => {
            console.error('Search error:', error);
        })
        .finally(() => {
            messagesContainer.style.opacity = '1';
        });
    }

    refreshMessages() {
        const messagesContainer = document.querySelector('.sms-messages');
        if (!messagesContainer) return;

        const params = new URLSearchParams({
            ajax: '1',
            refresh: '1'
        });

        fetch(`${window.location.pathname}?${params}`)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newMessages = doc.querySelector('.sms-messages');
            
            if (newMessages && newMessages.innerHTML !== messagesContainer.innerHTML) {
                messagesContainer.innerHTML = newMessages.innerHTML;
                this.showAlert('New messages received', 'info');
            }
        })
        .catch(error => {
            console.error('Refresh error:', error);
        });
    }

    showAlert(message, type = 'info') {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert-toast');
        existingAlerts.forEach(alert => alert.remove());

        // Create new alert
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-toast`;
        alert.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
            animation: slideIn 0.3s ease-out;
        `;
        alert.textContent = message;

        document.body.appendChild(alert);

        // Auto remove after 5 seconds
        setTimeout(() => {
            alert.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    }

    getCSRFToken() {
        const tokenInput = document.querySelector('input[name="csrf_token"]');
        return tokenInput ? tokenInput.value : '';
    }

    formatPhoneNumber(phone) {
        // Basic phone number formatting
        if (phone.startsWith('+')) {
            return phone;
        }
        if (phone.length === 10) {
            return phone.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
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
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new SMSApp();
});

// Utility functions
window.copyToClipboard = function(text) {
    navigator.clipboard.writeText(text).then(() => {
        const app = new SMSApp();
        app.showAlert('Copied to clipboard', 'success');
    });
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
            'csrf_token': new SMSApp().getCSRFToken()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            new SMSApp().showAlert(data.message || 'Error toggling phone number', 'error');
        }
    });
};
