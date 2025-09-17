// SAYVERS Management System - Main JavaScript
// Common functions and utilities

// Initialize system when document is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeSystem();
});

// System initialization
function initializeSystem() {
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize file uploads
    initializeFileUploads();
    
    // Initialize data tables
    initializeDataTables();
    
    // Initialize tooltips and modals
    initializeUIComponents();
    
    // Check session timeout
    checkSessionTimeout();
}

// Form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate="true"]');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// Validate form function
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    // Clear previous error messages
    clearFormErrors(form);
    
    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        } else {
            // Validate specific field types
            if (field.type === 'email' && !validateEmail(field.value)) {
                showFieldError(field, 'Please enter a valid email address');
                isValid = false;
            }
            
            if (field.type === 'tel' && !validatePhone(field.value)) {
                showFieldError(field, 'Please enter a valid phone number');
                isValid = false;
            }
            
            if (field.name === 'age' && (field.value < 1 || field.value > 120)) {
                showFieldError(field, 'Please enter a valid age (1-120)');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

// Clear form errors
function clearFormErrors(form) {
    const errorElements = form.querySelectorAll('.invalid-feedback');
    const invalidFields = form.querySelectorAll('.is-invalid');
    
    errorElements.forEach(element => element.remove());
    invalidFields.forEach(field => field.classList.remove('is-invalid'));
}

// Show field error
function showFieldError(field, message) {
    field.classList.add('is-invalid');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

// Email validation
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Phone validation
function validatePhone(phone) {
    const phoneRegex = /^[\+]?[0-9]{10,15}$/;
    return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
}

// File upload initialization
function initializeFileUploads() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            validateFileUpload(this);
        });
    });
    
    // Initialize drag and drop areas
    const dropAreas = document.querySelectorAll('.file-upload-area');
    dropAreas.forEach(function(area) {
        initializeDragDrop(area);
    });
}

// File upload validation
function validateFileUpload(input) {
    const allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    const files = input.files;
    let isValid = true;
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileName = file.name.toLowerCase();
        const fileExtension = fileName.split('.').pop();
        
        // Check file type
        if (!allowedTypes.includes(fileExtension)) {
            showAlert('Invalid file type. Allowed types: ' + allowedTypes.join(', '), 'danger');
            input.value = '';
            isValid = false;
            break;
        }
        
        // Check file size
        if (file.size > maxSize) {
            showAlert('File size exceeds 5MB limit', 'danger');
            input.value = '';
            isValid = false;
            break;
        }
    }
    
    if (isValid && files.length > 0) {
        showFilePreview(input, files[0]);
    }
}

// Show file preview
function showFilePreview(input, file) {
    const previewContainer = input.nextElementSibling;
    if (previewContainer && previewContainer.classList.contains('file-preview')) {
        previewContainer.innerHTML = '';
        
        const fileInfo = document.createElement('div');
        fileInfo.className = 'file-info';
        fileInfo.innerHTML = `
            <i class="fas fa-file"></i>
            <span class="file-name">${file.name}</span>
            <span class="file-size">(${formatFileSize(file.size)})</span>
        `;
        
        previewContainer.appendChild(fileInfo);
    }
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Initialize drag and drop
function initializeDragDrop(area) {
    const input = area.querySelector('input[type="file"]');
    
    if (!input) return;
    
    area.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        area.classList.add('drag-over');
    });
    
    area.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        area.classList.remove('drag-over');
    });
    
    area.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        area.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        input.files = files;
        validateFileUpload(input);
    });
}

// Data tables initialization
function initializeDataTables() {
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(function(table) {
        // Add sorting functionality
        const headers = table.querySelectorAll('th[data-sortable]');
        headers.forEach(function(header, index) {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                sortTable(table, index);
            });
        });
        
        // Add search functionality
        const searchInput = document.querySelector(`#search-${table.id}`);
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                searchTable(table, this.value);
            });
        }
    });
}

// Table sorting
function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const header = table.querySelectorAll('th')[columnIndex];
    
    const isAscending = !header.classList.contains('sort-asc');
    
    // Clear all sort classes
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
    });
    
    // Add appropriate sort class
    header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
    
    // Sort rows
    rows.sort(function(a, b) {
        const aText = a.cells[columnIndex].textContent.trim();
        const bText = b.cells[columnIndex].textContent.trim();
        
        const comparison = aText.localeCompare(bText, undefined, { numeric: true });
        return isAscending ? comparison : -comparison;
    });
    
    // Append sorted rows
    rows.forEach(row => tbody.appendChild(row));
}

// Table search
function searchTable(table, searchTerm) {
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    
    rows.forEach(function(row) {
        const text = row.textContent.toLowerCase();
        const shouldShow = text.includes(searchTerm.toLowerCase());
        row.style.display = shouldShow ? '' : 'none';
    });
}

// Initialize UI components
function initializeUIComponents() {
    // Initialize modals
    initializeModals();
    
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize confirmation dialogs
    initializeConfirmations();
}

// Modal initialization
function initializeModals() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    
    modalTriggers.forEach(function(trigger) {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            showModal(modalId);
        });
    });
    
    // Close modal when clicking outside or on close button
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay') || e.target.classList.contains('modal-close')) {
            closeAllModals();
        }
    });
}

// Show modal
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

// Close all modals
function closeAllModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(function(modal) {
        modal.style.display = 'none';
    });
    document.body.style.overflow = '';
}

// Tooltip initialization
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(function(element) {
        element.addEventListener('mouseenter', function() {
            showTooltip(this, this.getAttribute('data-tooltip'));
        });
        
        element.addEventListener('mouseleave', function() {
            hideTooltip();
        });
    });
}

// Show tooltip
function showTooltip(element, text) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    tooltip.id = 'active-tooltip';
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
}

// Hide tooltip
function hideTooltip() {
    const tooltip = document.getElementById('active-tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

// Confirmation dialog initialization
function initializeConfirmations() {
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    
    confirmButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const message = this.getAttribute('data-confirm') || 'Are you sure?';
            const url = this.href || this.getAttribute('data-url');
            
            if (confirm(message)) {
                if (url) {
                    window.location.href = url;
                }
            }
        });
    });
}

// Session timeout check
function checkSessionTimeout() {
    // Check every minute
    setInterval(function() {
        fetch('check_session.php')
            .then(response => response.json())
            .then(data => {
                if (!data.valid) {
                    alert('Your session has expired. Please log in again.');
                    window.location.href = 'modules/auth/login.php';
                }
            })
            .catch(error => {
                console.log('Session check failed:', error);
            });
    }, 60000);
}

// Alert system
function showAlert(message, type = 'info', duration = 5000) {
    const alertContainer = getAlertContainer();
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <span>${message}</span>
        <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    alertContainer.appendChild(alert);
    
    // Auto-remove after duration
    if (duration > 0) {
        setTimeout(function() {
            if (alert.parentElement) {
                alert.remove();
            }
        }, duration);
    }
}

// Get or create alert container
function getAlertContainer() {
    let container = document.getElementById('alert-container');
    
    if (!container) {
        container = document.createElement('div');
        container.id = 'alert-container';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        container.style.maxWidth = '400px';
        
        document.body.appendChild(container);
    }
    
    return container;
}

// Format currency
function formatCurrency(amount) {
    return '₱' + parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Format datetime
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

// Export to CSV
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            let text = cols[j].textContent.replace(/"/g, '""');
            row.push('"' + text + '"');
        }
        
        csv.push(row.join(','));
    }
    
    downloadCSV(csv.join('\n'), filename);
}

// Download CSV
function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Print page
function printPage() {
    window.print();
}

// Utility functions
const Utils = {
    // Debounce function for search inputs
    debounce: function(func, delay) {
        let timeoutId;
        return function (...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    },
    
    // Generate random ID
    generateId: function() {
        return 'id-' + Math.random().toString(36).substr(2, 9);
    },
    
    // Deep clone object
    deepClone: function(obj) {
        return JSON.parse(JSON.stringify(obj));
    },
    
    // Check if element is in viewport
    isInViewport: function(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= window.innerHeight &&
            rect.right <= window.innerWidth
        );
    }
};

// Global error handler
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    // You can send errors to server for logging
});

// Global unhandled promise rejection handler
window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled Promise Rejection:', e.reason);
    e.preventDefault();
});