// Global JavaScript for Findex Trial

// Toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('border-red-500');
            isValid = false;
        } else {
            input.classList.remove('border-red-500');
        }
    });
    
    return isValid;
}

// File upload preview
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// AJAX request helper
async function fetchAPI(url, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const mergedOptions = { ...defaultOptions, ...options };
    
    try {
        const response = await fetch(url, mergedOptions);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('API Error:', error);
        showToast('An error occurred', 'error');
        return null;
    }
}

// Load more content (pagination)
function loadMore(containerId, url, page) {
    fetchAPI(`${url}?page=${page}`).then(data => {
        if (data && data.html) {
            const container = document.getElementById(containerId);
            container.insertAdjacentHTML('beforeend', data.html);
        }
    });
}

// Real-time search
let searchTimeout;
function realTimeSearch(input, resultsId, searchUrl) {
    clearTimeout(searchTimeout);
    const query = input.value;
    
    if (query.length < 2) {
        document.getElementById(resultsId).innerHTML = '';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        fetchAPI(`${searchUrl}?q=${encodeURIComponent(query)}`).then(data => {
            if (data && data.results) {
                const resultsContainer = document.getElementById(resultsId);
                resultsContainer.innerHTML = data.results.map(result => `
                    <div class="p-2 hover:bg-gray-100 cursor-pointer" onclick="selectResult('${result.id}')">
                        ${result.title}
                    </div>
                `).join('');
            }
        });
    }, 300);
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!');
    });
}

// Confirmation dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Character counter for textarea
function updateCharCount(textarea, maxLength, counterId) {
    const remaining = maxLength - textarea.value.length;
    const counter = document.getElementById(counterId);
    if (counter) {
        counter.textContent = `${remaining} characters remaining`;
        if (remaining < 0) {
            counter.classList.add('text-red-500');
        } else {
            counter.classList.remove('text-red-500');
        }
    }
}

// Auto-save form data
let autoSaveTimeout;
function autoSave(formId, storageKey) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(() => {
        localStorage.setItem(storageKey, JSON.stringify(data));
        showToast('Draft saved', 'info');
    }, 1000);
}

// Load saved draft
function loadDraft(storageKey, formId) {
    const saved = localStorage.getItem(storageKey);
    if (saved) {
        const data = JSON.parse(saved);
        const form = document.getElementById(formId);
        
        for (const [key, value] of Object.entries(data)) {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                input.value = value;
            }
        }
        
        if (confirm('Load saved draft?')) {
            showToast('Draft loaded');
        }
    }
}

// Image lazy loading
const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.classList.remove('lazy');
            observer.unobserve(img);
        }
    });
});

document.querySelectorAll('img[data-src]').forEach(img => {
    imageObserver.observe(img);
});

// Initialize tooltips
document.querySelectorAll('[data-tooltip]').forEach(element => {
    element.addEventListener('mouseenter', (e) => {
        const tooltip = document.createElement('div');
        tooltip.className = 'absolute bg-gray-800 text-white text-xs rounded px-2 py-1 z-50';
        tooltip.textContent = element.dataset.tooltip;
        tooltip.style.top = `${e.target.offsetTop - 30}px`;
        tooltip.style.left = `${e.target.offsetLeft}px`;
        document.body.appendChild(tooltip);
        
        element.addEventListener('mouseleave', () => {
            tooltip.remove();
        });
    });
});

// Dark mode toggle (if implemented)
function toggleDarkMode() {
    document.body.classList.toggle('dark');
    localStorage.setItem('darkMode', document.body.classList.contains('dark'));
}

// Load dark mode preference
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark');
}

// Export functions for global use
window.showToast = showToast;
window.validateForm = validateForm;
window.previewImage = previewImage;
window.confirmAction = confirmAction;
window.copyToClipboard = copyToClipboard;