// AJAX utility functions
const api = {
    async get(endpoint) {
        try {
            const response = await fetch(`/api.php${endpoint}`, {
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });
            return await this.handleResponse(response);
        } catch (error) {
            this.handleError(error);
        }
    },

    async post(endpoint, data) {
        try {
            const response = await fetch(`/api.php${endpoint}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            });
            return await this.handleResponse(response);
        } catch (error) {
            this.handleError(error);
        }
    },

    async handleResponse(response) {
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'An error occurred');
        }
        return data;
    },

    handleError(error) {
        console.error('API Error:', error);
        showNotification(error.message, 'error');
        throw error;
    }
};

// Notification system
const notificationQueue = [];

function showNotification(message, type = 'info') {
    // If offline, queue notifications for later
    if (!navigator.onLine && type !== 'warning') {
        notificationQueue.push({ message, type });
        return;
    }

    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg text-white transform transition-transform duration-300 ease-in-out ${
        type === 'error' ? 'bg-red-500' : 'bg-green-500'
    }`;
    notification.textContent = message;

    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);

    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// File upload handling
function handleFileUpload(inputElement, options = {}) {
    const { onSuccess, onError, allowedTypes = ['xlsx'] } = options;
    
    const file = inputElement.files[0];
    if (!file) return;

    // Validate file type
    const fileType = file.name.split('.').pop().toLowerCase();
    if (!allowedTypes.includes(fileType)) {
        showNotification(`Invalid file type. Allowed types: ${allowedTypes.join(', ')}`, 'error');
        return;
    }

    const formData = new FormData();
    formData.append('file', file);

    // Show upload progress
    const progressBar = document.createElement('div');
    progressBar.className = 'w-full h-2 bg-gray-200 rounded';
    progressBar.innerHTML = '<div class="h-full bg-gradient-to-r from-blue-500 to-green-500 rounded" style="width: 0%"></div>';
    
    inputElement.parentElement.appendChild(progressBar);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', options.url || '/upload');

    xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) {
            const percent = (e.loaded / e.total) * 100;
            progressBar.querySelector('div').style.width = percent + '%';
        }
    };

    xhr.onload = () => {
        progressBar.remove();
        try {
            const response = JSON.parse(xhr.responseText);
            if (xhr.status === 200) {
                onSuccess?.(response);
            } else {
                onError?.(response.message || 'Upload failed');
            }
        } catch (error) {
            onError?.('Upload failed');
        }
    };

    xhr.onerror = () => {
        progressBar.remove();
        onError?.('Network error occurred');
    };

    xhr.send(formData);
}

// Clipboard functionality
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text)
            .then(() => showNotification('Copied to clipboard!'))
            .catch(() => showNotification('Failed to copy to clipboard', 'error'));
    } else {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            showNotification('Copied to clipboard!');
        } catch (err) {
            showNotification('Failed to copy to clipboard', 'error');
        }
        document.body.removeChild(textarea);
    }
}

// Date/time formatting
function formatDateTime(date, format = 'UTC') {
    const d = new Date(date);
    if (format === 'UTC') {
        return d.toISOString().replace('T', ' ').substr(0, 19);
    }
    return d.toLocaleString();
}

// Form validation
function validateForm(formElement, rules) {
    const errors = [];
    
    for (const [fieldName, validations] of Object.entries(rules)) {
        const field = formElement.querySelector(`[name="${fieldName}"]`);
        if (!field) continue;

        validations.forEach(validation => {
            const [rule, ...params] = validation.split(':');
            
            switch (rule) {
                case 'required':
                    if (!field.value.trim()) {
                        errors.push(`${fieldName} is required`);
                    }
                    break;
                case 'min':
                    if (field.value.length < parseInt(params[0])) {
                        errors.push(`${fieldName} must be at least ${params[0]} characters`);
                    }
                    break;
                case 'max':
                    if (field.value.length > parseInt(params[0])) {
                        errors.push(`${fieldName} must not exceed ${params[0]} characters`);
                    }
                    break;
                case 'email':
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
                        errors.push(`${fieldName} must be a valid email`);
                    }
                    break;
            }
        });
    }

    return errors;
}

// Initialize tooltips
function initTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        const tooltip = document.createElement('div');
        tooltip.className = 'absolute bg-gray-800 text-white px-2 py-1 rounded text-sm opacity-0 transition-opacity duration-200 pointer-events-none';
        tooltip.textContent = element.getAttribute('data-tooltip');
        element.appendChild(tooltip);

        element.addEventListener('mouseenter', () => {
            tooltip.style.opacity = '1';
        });

        element.addEventListener('mouseleave', () => {
            tooltip.style.opacity = '0';
        });
    });
}

// Register Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('ServiceWorker registered: ', registration.scope);
            })
            .catch(error => {
                console.log('ServiceWorker registration failed: ', error);
            });
    });
}

// Performance monitoring
const performanceMonitor = {
    metrics: {},
    
    start(operation) {
        this.metrics[operation] = {
            startTime: performance.now(),
            startMemory: window.performance.memory?.usedJSHeapSize
        };
    },

    end(operation) {
        if (!this.metrics[operation]) return;
        
        const endTime = performance.now();
        const duration = endTime - this.metrics[operation].startTime;
        
        // Log to server if duration exceeds threshold
        if (duration > 1000) { // 1 second threshold
            this.logMetrics(operation, duration);
        }
        
        delete this.metrics[operation];
    },

    async logMetrics(operation, duration) {
        try {
            await fetch('/api.php?action=log_performance', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    operation,
                    duration,
                    timestamp: new Date().toISOString()
                })
            });
        } catch (error) {
            console.error('Failed to log performance metrics:', error);
        }
    }
};

// Network status monitoring
function updateOnlineStatus() {
    const status = navigator.onLine ? 'online' : 'offline';
    document.body.className = status;
    
    showNotification(
        navigator.onLine ? 'Back online' : 'You are offline',
        navigator.onLine ? 'success' : 'warning'
    );
}

window.addEventListener('online', updateOnlineStatus);
window.addEventListener('offline', updateOnlineStatus);

// Process queued notifications when coming back online
window.addEventListener('online', () => {
    while (notificationQueue.length > 0) {
        const notification = notificationQueue.shift();
        showNotification(notification.message, notification.type);
    }
});

// Add performance monitoring to AJAX requests
const originalFetch = window.fetch;
window.fetch = async (...args) => {
    const url = args[0]?.url || args[0];
    performanceMonitor.start(`fetch:${url}`);
    
    try {
        const response = await originalFetch(...args);
        performanceMonitor.end(`fetch:${url}`);
        return response;
    } catch (error) {
        performanceMonitor.end(`fetch:${url}`);
        throw error;
    }
};

// Monitor file upload performance
function monitorFileUpload(file, callback) {
    const startTime = performance.now();
    const reader = new FileReader();
    
    reader.onloadend = () => {
        const endTime = performance.now();
        const duration = endTime - startTime;
        const size = file.size;
        const speed = size / (duration / 1000); // bytes per second
        
        callback({
            duration,
            size,
            speed,
            filename: file.name
        });
    };
    
    reader.readAsArrayBuffer(file);
}

// Initialize performance monitoring for all file inputs
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (file) {
                monitorFileUpload(file, metrics => {
                    performanceMonitor.logMetrics('file_upload', {
                        ...metrics,
                        type: input.name
                    });
                });
            }
        });
    });
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    initTooltips();
});