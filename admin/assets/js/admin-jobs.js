/**
 * Job Portal Admin JavaScript
 * Handles interactive features for the admin job management
 * 
 * Enhanced with:
 * - Form validation with real-time feedback
 * - Character counters
 * - Slug auto-generation
 * - Salary range validation
 * - Deadline validation
 * - Form dirty state detection
 * - Keyboard shortcuts
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all interactive features
    initDeleteModal();
    initStatusToggle();
    initFormValidation();
    initAutoSave();
    initSlugGeneration();
    initFileUploadPreview();
    initTagInput();
    initSearch();
    initFilters();
    initLoadingStates();
    initToastNotifications();
    initKeyboardShortcuts();
    initCharacterCounters();
    initSalaryValidation();
    initDeadlineValidation();
    initFormDirtyDetection();
    initTooltips();
});

/**
 * Initialize delete confirmation modal
 */
function initDeleteModal() {
    const deleteButtons = document.querySelectorAll('button[onclick^="deleteJob"]');
    const deleteModal = document.getElementById('deleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    let currentJobId = null;

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            currentJobId = this.dataset.jobId;
            if (deleteModal) {
                deleteModal.classList.add('active');
            }
        });
    });

    if (confirmDeleteBtn && deleteModal) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (currentJobId) {
                // Create and submit the delete form
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const actionField = document.createElement('input');
                actionField.type = 'hidden';
                actionField.name = 'action';
                actionField.value = 'delete';

                const jobIdField = document.createElement('input');
                jobIdField.type = 'hidden';
                jobIdField.name = 'job_id';
                jobIdField.value = currentJobId;

                form.appendChild(actionField);
                form.appendChild(jobIdField);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // Close modal when clicking outside
    if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if (e.target === deleteModal) {
                closeModal('deleteModal');
            }
        });
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && deleteModal && deleteModal.classList.contains('active')) {
            closeModal('deleteModal');
        }
    });
}

/**
 * Initialize status toggle functionality
 */
function initStatusToggle() {
    const toggleButtons = document.querySelectorAll('button[onclick^="toggleStatus"]');

    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const jobId = this.dataset.jobId;
            const currentStatus = this.dataset.currentStatus;

            // Determine new status
            const statusOrder = ['draft', 'active', 'closed'];
            const currentIndex = statusOrder.indexOf(currentStatus);
            const newStatus = statusOrder[(currentIndex + 1) % statusOrder.length];

            // Create and submit the status toggle form
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const actionField = document.createElement('input');
            actionField.type = 'hidden';
            actionField.name = 'action';
            actionField.value = 'toggle_status';

            const jobIdField = document.createElement('input');
            jobIdField.type = 'hidden';
            jobIdField.name = 'job_id';
            jobIdField.value = jobId;

            const newStatusField = document.createElement('input');
            newStatusField.type = 'hidden';
            newStatusField.name = 'new_status';
            newStatusField.value = newStatus;

            form.appendChild(actionField);
            form.appendChild(jobIdField);
            form.appendChild(newStatusField);
            document.body.appendChild(form);
            form.submit();
        });
    });
}

/**
 * Close modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

/**
 * Initialize form validation
 */
function initFormValidation() {
    const forms = document.querySelectorAll('.admin-job-form, .post-editor-form');

    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            let firstError = null;

            // Clear previous errors
            form.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });

            // Validate required fields
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                    if (!firstError) firstError = field;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            // Validate email if present
            const emailField = form.querySelector('input[type="email"]');
            if (emailField && emailField.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailField.value)) {
                    isValid = false;
                    emailField.classList.add('is-invalid');
                    if (!firstError) firstError = emailField;
                }
            }

            // Validate salary range
            const salaryMin = form.querySelector('#salary_min');
            const salaryMax = form.querySelector('#salary_max');
            if (salaryMin && salaryMax && salaryMin.value && salaryMax.value) {
                if (parseFloat(salaryMax.value) < parseFloat(salaryMin.value)) {
                    isValid = false;
                    salaryMax.classList.add('is-invalid');
                    if (!firstError) firstError = salaryMax;
                    showFormError('Maximum salary must be greater than minimum salary.');
                }
            }

            // Validate deadline (must be future date for new jobs)
            const deadlineField = form.querySelector('#application_deadline');
            if (deadlineField && deadlineField.value) {
                const deadline = new Date(deadlineField.value);
                const now = new Date();
                if (deadline < now && !form.dataset.isEdit) {
                    isValid = false;
                    deadlineField.classList.add('is-invalid');
                    if (!firstError) firstError = deadlineField;
                    showFormError('Application deadline must be a future date.');
                }
            }

            if (!isValid) {
                e.preventDefault();
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
                if (!document.querySelector('.form-error-toast')) {
                    showFormError('Please fill in all required fields correctly.');
                }
            }
        });

        // Remove error classes on input
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                // Also remove error from related elements
                const errorDiv = document.getElementById('salary-error');
                if (errorDiv && (this.id === 'salary_min' || this.id === 'salary_max')) {
                    errorDiv.style.display = 'none';
                }
            });
        });
    });
}

/**
 * Show form error message
 */
function showFormError(message) {
    // Remove existing error message
    const existingError = document.querySelector('.admin-form-error');
    if (existingError) {
        existingError.remove();
    }
    
    const existingToast = document.querySelector('.form-error-toast');
    if (existingToast) {
        existingToast.remove();
    }

    // Create error toast
    const errorElement = document.createElement('div');
    errorElement.className = 'form-error-toast';
    errorElement.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: #ef4444;
        color: white;
        border-radius: 0.375rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        animation: slideIn 0.3s ease;
    `;
    errorElement.innerHTML = `
        <svg viewBox="0 0 24 24" width="20" height="20"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/><line x1="12" y1="8" x2="12" y2="12" stroke="currentColor" stroke-width="2"/><line x1="12" y1="16" x2="12.01" y2="16" stroke="currentColor" stroke-width="2"/></svg>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" style="margin-left: 1rem; background: none; border: none; color: white; cursor: pointer; font-size: 1.25rem;">&times;</button>
    `;

    document.body.appendChild(errorElement);

    // Scroll to error message
    errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (errorElement.parentElement) {
            errorElement.style.opacity = '0';
            errorElement.style.transform = 'translateX(100%)';
            errorElement.style.transition = 'all 0.3s ease';
            setTimeout(() => errorElement.remove(), 300);
        }
    }, 5000);
}

/**
 * Initialize auto-save functionality
 */
function initAutoSave() {
    const forms = document.querySelectorAll('.admin-job-form, .post-editor-form');

    forms.forEach(form => {
        let saveTimeout;

        form.addEventListener('input', function() {
            clearTimeout(saveTimeout);

            saveTimeout = setTimeout(() => {
                // Auto-save functionality can be implemented here
                console.log('Form auto-saved');
            }, 2000);
        });
    });
}

/**
 * Initialize real-time slug generation
 */
function initSlugGeneration() {
    const titleInput = document.querySelector('#title');
    const slugInput = document.querySelector('#slug');

    if (titleInput && slugInput) {
        // Only set up listener if not already set by inline script
        if (!titleInput.dataset.slugInit) {
            titleInput.addEventListener('input', function() {
                if (this.value) {
                    slugInput.value = generateSlug(this.value);
                    slugInput.dataset.autoGenerated = 'true';
                } else {
                    slugInput.value = '';
                }
            });
            titleInput.dataset.slugInit = 'true';
        }
    }
}

/**
 * Generate URL-friendly slug
 */
function generateSlug(text) {
    return text.toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .substring(0, 180);
}

/**
 * Regenerate slug from title (global function for onclick)
 */
function regenerateSlug() {
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    
    if (titleInput && slugInput) {
        slugInput.value = generateSlug(titleInput.value);
        slugInput.dataset.autoGenerated = 'true';
        slugInput.focus();
    }
}

/**
 * Initialize file upload preview
 */
function initFileUploadPreview() {
    const fileInput = document.querySelector('input[type="file"]');
    const filePreview = document.querySelector('.file-preview');

    if (fileInput && filePreview) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                filePreview.textContent = file.name;
                filePreview.classList.add('has-file');
            } else {
                filePreview.textContent = 'No file selected';
                filePreview.classList.remove('has-file');
            }
        });
    }
}

/**
 * Initialize tag input with autocomplete
 */
function initTagInput() {
    const tagInput = document.querySelector('#tags');
    const tagContainer = document.querySelector('.tag-container');

    if (tagInput && tagContainer) {
        tagInput.addEventListener('keydown', function(e) {
            if (e.key === ',' || e.key === 'Enter') {
                e.preventDefault();

                const tag = this.value.trim().replace(/,/g, '');
                if (tag) {
                    addTag(tag);
                    this.value = '';
                }
            }
        });
    }

    function addTag(tag) {
        const tagElement = document.createElement('span');
        tagElement.className = 'tag';
        tagElement.textContent = tag;

        const removeButton = document.createElement('button');
        removeButton.className = 'tag-remove';
        removeButton.innerHTML = '&times;';
        removeButton.addEventListener('click', function() {
            tagElement.remove();
        });

        tagElement.appendChild(removeButton);
        tagContainer.appendChild(tagElement);
    }
}

/**
 * Initialize search functionality
 */
function initSearch() {
    const searchInput = document.querySelector('input[name="search"]');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            // Debounce search
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                const form = this.closest('form');
                if (form) {
                    form.submit();
                }
            }, 500);
        });
    }
}

/**
 * Initialize filter changes
 */
function initFilters() {
    const filterSelects = document.querySelectorAll('.admin-filter-select');

    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                form.submit();
            }
        });
    });
}

/**
 * Initialize loading states
 */
function initLoadingStates() {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton && !submitButton.disabled) {
                submitButton.disabled = true;
                const originalContent = submitButton.innerHTML;
                submitButton.dataset.originalContent = originalContent;
                submitButton.innerHTML = `
                    <svg class="admin-btn-icon admin-btn-icon-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="animation: spin 1s linear infinite;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Processing...
                `;
                
                // Re-enable after timeout (in case of validation error)
                setTimeout(() => {
                    submitButton.disabled = false;
                    if (submitButton.dataset.originalContent) {
                        submitButton.innerHTML = submitButton.dataset.originalContent;
                    }
                }, 5000);
            }
        });
    });
}

/**
 * Initialize toast notifications
 */
function initToastNotifications() {
    let toastContainer = document.querySelector('.toast-container');

    if (!toastContainer) {
        const container = document.createElement('div');
        container.className = 'toast-container';
        container.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        `;
        document.body.appendChild(container);
        toastContainer = container;
    }

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.cssText = `
            padding: 1rem 1.5rem;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            border-radius: 0.375rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.3s ease;
        `;
        toast.textContent = message;

        toastContainer.appendChild(toast);

        // Auto-remove after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            toast.style.transition = 'all 0.3s ease';

            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }

    // Store toast function globally
    window.showToast = showToast;
}

/**
 * Initialize keyboard shortcuts
 */
function initKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const saveButton = document.querySelector('.btn--primary, .admin-btn-primary');
            if (saveButton && !saveButton.disabled) {
                saveButton.click();
            }
        }

        // Ctrl/Cmd + N to create new job
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            const newJobButton = document.querySelector('a[href="job-post.php"]');
            if (newJobButton) {
                newJobButton.click();
            }
        }
        
        // Ctrl/Cmd + P to preview (if available)
        if ((e.ctrlKey || e.metaKey) && e.key === 'p' && !e.shiftKey) {
            const previewButton = document.querySelector('.btn-preview');
            if (previewButton) {
                e.preventDefault();
                previewButton.click();
            }
        }
    });
}

/**
 * Initialize character counters
 */
function initCharacterCounters() {
    const counters = [
        { input: 'title', counter: 'title-count', max: 255 },
        { input: 'description', counter: 'description-count', max: null },
        { input: 'requirements', counter: 'requirements-count', max: null },
        { input: 'responsibilities', counter: 'responsibilities-count', max: null }
    ];
    
    counters.forEach(item => {
        const input = document.getElementById(item.input);
        const counter = document.getElementById(item.counter);
        
        if (input && counter) {
            const updateCounter = () => {
                const length = input.value.length;
                counter.textContent = length;
                
                if (item.max) {
                    if (length > item.max * 0.9) {
                        counter.style.color = '#ef4444';
                    } else if (length > item.max * 0.7) {
                        counter.style.color = '#f59e0b';
                    } else {
                        counter.style.color = '#6b7280';
                    }
                }
            };
            
            // Remove existing listener if any
            input.removeEventListener('input', updateCounter);
            input.addEventListener('input', updateCounter);
            updateCounter();
        }
    });
}

/**
 * Initialize salary validation
 */
function initSalaryValidation() {
    const minInput = document.getElementById('salary_min');
    const maxInput = document.getElementById('salary_max');
    const errorDiv = document.getElementById('salary-error');
    
    const validateSalary = () => {
        const min = parseFloat(minInput?.value) || 0;
        const max = parseFloat(maxInput?.value) || 0;
        
        if (max > 0 && min > 0 && max < min) {
            if (maxInput) maxInput.classList.add('is-invalid');
            if (errorDiv) errorDiv.style.display = 'block';
            return false;
        } else {
            if (maxInput) maxInput.classList.remove('is-invalid');
            if (errorDiv) errorDiv.style.display = 'none';
            return true;
        }
    };
    
    if (minInput && maxInput) {
        minInput.addEventListener('input', validateSalary);
        maxInput.addEventListener('input', validateSalary);
    }
    
    // Store validation function for form submission
    window.validateSalary = validateSalary;
}

/**
 * Initialize deadline validation
 */
function initDeadlineValidation() {
    const deadlineInput = document.getElementById('application_deadline');
    
    if (deadlineInput) {
        // Set minimum date to now
        const now = new Date();
        const offset = now.getTimezoneOffset() * 60000;
        const localISOTime = new Date(now - offset).toISOString().slice(0, 16);
        deadlineInput.setAttribute('min', localISOTime);
        
        // Validate on change
        deadlineInput.addEventListener('change', function() {
            const deadline = new Date(this.value);
            const now = new Date();
            
            if (deadline < now) {
                this.classList.add('is-invalid');
                showFormError('Application deadline should be a future date.');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }
}

/**
 * Initialize form dirty detection
 */
function initFormDirtyDetection() {
    const form = document.getElementById('job-form');
    const indicator = document.getElementById('form-dirty-indicator');
    
    if (form && indicator) {
        let isFormDirty = false;
        
        // Store original values
        const originalValues = {};
        form.querySelectorAll('input, textarea, select').forEach(field => {
            if (field.name && field.type !== 'hidden') {
                originalValues[field.name] = field.value;
            }
        });
        
        form.addEventListener('input', function(e) {
            if (e.target.name && e.target.type !== 'hidden') {
                if (e.target.value !== originalValues[e.target.name]) {
                    isFormDirty = true;
                    indicator.classList.add('visible');
                } else {
                    // Check if any field is different
                    const allFields = form.querySelectorAll('input, textarea, select');
                    let anyChanged = false;
                    allFields.forEach(field => {
                        if (field.name && field.type !== 'hidden') {
                            if (field.value !== originalValues[field.name]) {
                                anyChanged = true;
                            }
                        }
                    });
                    if (!anyChanged) {
                        isFormDirty = false;
                        indicator.classList.remove('visible');
                    }
                }
            }
        });
        
        form.addEventListener('submit', function() {
            isFormDirty = false;
            indicator.classList.remove('visible');
        });
        
        // Warn before leaving with unsaved changes
        window.addEventListener('beforeunload', function(e) {
            if (isFormDirty) {
                e.preventDefault();
                e.returnValue = '';
                return '';
            }
        });
    }
}

/**
 * Initialize tooltips
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('.tooltip-icon');
    
    tooltips.forEach(tooltip => {
        const title = tooltip.getAttribute('title');
        if (title) {
            tooltip.dataset.tooltip = title;
            tooltip.removeAttribute('title');
            
            tooltip.addEventListener('mouseenter', function(e) {
                const tooltipText = this.dataset.tooltip;
                if (tooltipText) {
                    const tooltipEl = document.createElement('div');
                    tooltipEl.className = 'tooltip-popup';
                    tooltipEl.textContent = tooltipText;
                    tooltipEl.style.cssText = `
                        position: absolute;
                        background: #1f2937;
                        color: white;
                        padding: 0.5rem 0.75rem;
                        border-radius: 0.375rem;
                        font-size: 0.75rem;
                        z-index: 1000;
                        max-width: 200px;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                    `;
                    
                    document.body.appendChild(tooltipEl);
                    
                    const rect = this.getBoundingClientRect();
                    tooltipEl.style.top = (rect.bottom + 5) + 'px';
                    tooltipEl.style.left = (rect.left + (rect.width / 2) - (tooltipEl.offsetWidth / 2)) + 'px';
                    
                    this.tooltipEl = tooltipEl;
                }
            });
            
            tooltip.addEventListener('mouseleave', function() {
                if (this.tooltipEl) {
                    this.tooltipEl.remove();
                    this.tooltipEl = null;
                }
            });
        }
    });
}

/**
 * Publish job (set status to active and submit)
 */
function publishJob(event) {
    event.preventDefault();
    
    // Validate salary first
    if (window.validateSalary && !window.validateSalary()) {
        showFormError('Maximum salary must be greater than minimum salary.');
        return false;
    }
    
    // Validate required fields
    const form = document.getElementById('job-form');
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    let firstError = null;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
            if (!firstError) firstError = field;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
        showFormError('Please fill in all required fields.');
        return false;
    }
    
    // Set action to publish and submit
    const actionInput = document.getElementById('form-action');
    if (actionInput) {
        actionInput.value = 'publish';
    }
    form.submit();
}

/**
 * Preview job in new window
 */
function previewJob(jobId) {
    if (jobId) {
        window.open('job-portal/pages/job-detail.php?id=' + jobId, '_blank');
    }
}

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .admin-btn-icon-spin {
        animation: spin 1s linear infinite;
    }
    
    .form-control.is-invalid {
        border-color: #ef4444 !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }
`;
document.head.appendChild(style);

/**
 * ============================================
 * JOBS LISTING PAGE FUNCTIONALITY
 * ============================================
 */

/**
 * Initialize jobs listing page features
 */
function initJobsListing() {
    initRepostModal();
    initBulkActions();
    initCheckboxSelection();
    initExpiredJobHighlighting();
    initDeadlineValidationNew();
}

/**
 * Initialize repost modal functionality
 */
function initRepostModal() {
    const repostButtons = document.querySelectorAll('.admin-btn-repost');
    const repostModal = document.getElementById('repostModal');
    const repostForm = document.getElementById('repostForm');
    
    repostButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const jobId = this.dataset.jobId;
            const jobTitle = this.dataset.jobTitle;
            
            // Populate modal
            document.getElementById('repostJobId').value = jobId;
            document.getElementById('repostJobTitle').textContent = jobTitle;
            
            // Set default deadline to 30 days from now
            const defaultDeadline = new Date();
            defaultDeadline.setDate(defaultDeadline.getDate() + 30);
            const deadlineInput = document.getElementById('newDeadline');
            if (deadlineInput) {
                deadlineInput.value = formatDateForInput(defaultDeadline);
                deadlineInput.min = formatDateForInput(new Date());
            }
            
            // Show modal
            if (repostModal) {
                repostModal.classList.add('active');
            }
        });
    });
    
    // Handle form submission
    if (repostForm) {
        repostForm.addEventListener('submit', function(e) {
            const deadlineInput = document.getElementById('newDeadline');
            if (deadlineInput) {
                const deadline = new Date(deadlineInput.value);
                const now = new Date();
                
                if (deadline <= now) {
                    e.preventDefault();
                    showFormError('New deadline must be a future date.');
                    return false;
                }
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <svg class="admin-btn-icon-spin" viewBox="0 0 24 24" width="16" height="16">
                        <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Processing...
                `;
            }
        });
    }
}

/**
 * Format date for datetime-local input
 */
function formatDateForInput(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

/**
 * Initialize bulk actions functionality
 */
function initBulkActions() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const jobCheckboxes = document.querySelectorAll('.job-checkbox');
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCountEl = document.getElementById('selectedCount');
    const applyBulkBtn = document.getElementById('applyBulkAction');
    const bulkActionSelect = document.getElementById('bulkActionSelect');
    
    // Select all handler
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const visibleCheckboxes = document.querySelectorAll('.job-checkbox:not([style*="display: none"])');
            visibleCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateBulkActionsBar();
        });
    }
    
    // Individual checkbox handlers
    jobCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionsBar);
    });
    
    // Update bulk actions bar visibility and count
    function updateBulkActionsBar() {
        const checkedBoxes = document.querySelectorAll('.job-checkbox:checked');
        
        if (checkedBoxes.length > 0 && bulkActionsBar) {
            bulkActionsBar.style.display = 'block';
            if (selectedCountEl) {
                selectedCountEl.textContent = checkedBoxes.length;
            }
            
            // Update select all checkbox state
            if (selectAllCheckbox) {
                const totalVisible = document.querySelectorAll('.job-checkbox:not([style*="display: none"])').length;
                selectAllCheckbox.checked = checkedBoxes.length === totalVisible;
                selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < totalVisible;
            }
        } else if (bulkActionsBar) {
            bulkActionsBar.style.display = 'none';
        }
    }
    
    // Apply bulk action
    if (applyBulkBtn && bulkActionSelect) {
        applyBulkBtn.addEventListener('click', function() {
            const action = bulkActionSelect.value;
            
            if (!action) {
                showToast('Please select an action', 'error');
                return;
            }
            
            const checkedBoxes = document.querySelectorAll('.job-checkbox:checked');
            if (checkedBoxes.length === 0) {
                showToast('Please select at least one job', 'error');
                return;
            }
            
            const jobIds = Array.from(checkedBoxes).map(cb => cb.value);
            
            // Confirm destructive actions
            if (action === 'delete') {
                if (!confirm(`Are you sure you want to delete ${jobIds.length} job(s)? This action cannot be undone.`)) {
                    return;
                }
            }
            
            // Create and submit form
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="bulk_${action === 'delete' ? 'delete' : 'status'}">
                <input type="hidden" name="job_ids" value='${JSON.stringify(jobIds)}'>
                ${action !== 'delete' ? `<input type="hidden" name="bulk_status" value="${action}">` : ''}
            `;
            document.body.appendChild(form);
            form.submit();
        });
    }
    
    // Store update function globally
    window.updateBulkActionsBar = updateBulkActionsBar;
}

/**
 * Initialize checkbox selection with visual feedback
 */
function initCheckboxSelection() {
    const table = document.getElementById('jobsTable');
    
    if (table) {
        // Row click to toggle checkbox (except on action buttons)
        table.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('click', function(e) {
                // Don't toggle if clicking on a button, link, or checkbox
                if (e.target.closest('button, a, input[type="checkbox"]')) {
                    return;
                }
                
                const checkbox = this.querySelector('.job-checkbox');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change'));
                    
                    // Visual feedback
                    if (checkbox.checked) {
                        this.classList.add('row-selected');
                    } else {
                        this.classList.remove('row-selected');
                    }
                }
            });
            
            // Update row visual on checkbox change
            const checkbox = row.querySelector('.job-checkbox');
            if (checkbox) {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        row.classList.add('row-selected');
                    } else {
                        row.classList.remove('row-selected');
                    }
                });
            }
        });
    }
}

/**
 * Initialize expired job highlighting
 */
function initExpiredJobHighlighting() {
    const expiredRows = document.querySelectorAll('.job-expired');
    
    expiredRows.forEach(row => {
        // Add hover effect for expired rows
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#fed7d7';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
    
    // Count and display expired jobs
    const expiredCount = expiredRows.length;
    const expiredStatCard = document.querySelector('.page-stat-card--red .page-stat-card__value');
    if (expiredStatCard && expiredCount > 0) {
        // Add pulse animation to draw attention
        expiredStatCard.classList.add('pulse-animation');
    }
}

/**
 * Initialize deadline validation for repost form
 */
function initDeadlineValidationNew() {
    const deadlineInput = document.getElementById('newDeadline');
    
    if (deadlineInput) {
        // Set minimum date to now
        const now = new Date();
        deadlineInput.min = formatDateForInput(now);
        
        // Validate on change
        deadlineInput.addEventListener('change', function() {
            const deadline = new Date(this.value);
            const now = new Date();
            
            if (deadline <= now) {
                this.classList.add('is-invalid');
                showFormError('Deadline must be a future date.');
            } else {
                this.classList.remove('is-invalid');
                
                // Calculate days until deadline
                const daysUntil = Math.ceil((deadline - now) / (1000 * 60 * 60 * 24));
                
                // Show info about deadline
                const hint = this.parentElement.querySelector('.admin-form-hint');
                if (hint) {
                    hint.textContent = `Application deadline set for ${daysUntil} days from now.`;
                    hint.style.color = daysUntil < 7 ? '#d97706' : '#10b981';
                }
            }
        });
    }
}

/**
 * Global function to open repost modal (for external calls)
 */
function openRepostModal(jobId, jobTitle) {
    const repostModal = document.getElementById('repostModal');
    const jobIdInput = document.getElementById('repostJobId');
    const jobTitleEl = document.getElementById('repostJobTitle');
    const deadlineInput = document.getElementById('newDeadline');
    
    if (jobIdInput) jobIdInput.value = jobId;
    if (jobTitleEl) jobTitleEl.textContent = jobTitle;
    
    // Set default deadline to 30 days from now
    if (deadlineInput) {
        const defaultDeadline = new Date();
        defaultDeadline.setDate(defaultDeadline.getDate() + 30);
        deadlineInput.value = formatDateForInput(defaultDeadline);
        deadlineInput.min = formatDateForInput(new Date());
    }
    
    if (repostModal) {
        repostModal.classList.add('active');
    }
}

/**
 * Global function to handle status toggle with confirmation for expired jobs
 */
function toggleJobStatus(jobId, currentStatus) {
    const row = document.querySelector(`tr[data-job-id="${jobId}"]`);
    const isExpired = row && row.classList.contains('job-expired');
    
    if (isExpired && currentStatus === 'active') {
        // Suggest reposting instead of reactivating
        if (confirm('This job has expired. Would you like to repost it with a new deadline instead?')) {
            const jobTitle = row.querySelector('.admin-table-cell-title').textContent.trim();
            openRepostModal(jobId, jobTitle);
            return;
        }
    }
    
    // Proceed with normal status toggle
    const statusOrder = ['draft', 'active', 'closed'];
    const currentIndex = statusOrder.indexOf(currentStatus);
    const newStatus = statusOrder[(currentIndex + 1) % statusOrder.length];
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="toggle_status">
        <input type="hidden" name="job_id" value="${jobId}">
        <input type="hidden" name="new_status" value="${newStatus}">
    `;
    document.body.appendChild(form);
    form.submit();
}

/**
 * Export functions for global use
 */
window.openRepostModal = openRepostModal;
window.toggleJobStatus = toggleJobStatus;
window.formatDateForInput = formatDateForInput;

// Initialize jobs listing when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on the jobs listing page
    const jobsTable = document.getElementById('jobsTable');
    if (jobsTable) {
        initJobsListing();
    }
});
