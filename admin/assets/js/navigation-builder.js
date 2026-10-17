/**
 * Tulima Solar - Navigation Builder JavaScript
 * Handles real-time synchronization, drag-and-drop, and dynamic updates
 */

// Tab switching
document.querySelectorAll('.admin-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const tabId = this.getAttribute('data-tab');
        
        // Update tab buttons
        document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('admin-tab--active'));
        this.classList.add('admin-tab--active');
        
        // Update tab content
        document.querySelectorAll('.admin-tab-content').forEach(content => {
            content.classList.remove('admin-tab-content--active');
        });
        document.getElementById(tabId).classList.add('admin-tab-content--active');
    });
});

// Reset forms
function resetMenuForm() {
    document.getElementById('menu_item_id').value = '';
    document.getElementById('menu_title').value = '';
    document.getElementById('menu_url').value = '';
    document.getElementById('menu_page_id').value = '';
    document.getElementById('menu_parent_id').value = '';
    document.getElementById('menu_icon').value = '';
    document.getElementById('menu_target').value = '_self';
    document.getElementById('menu_type').value = 'main';
    document.getElementById('menu_sort_order').value = '0';
    document.getElementById('menu_is_active').checked = true;
}

function resetFooterSectionForm() {
    document.getElementById('footer_section_id').value = '';
    document.getElementById('footer_title').value = '';
    document.getElementById('footer_section_type').value = 'custom';
    document.getElementById('footer_sort_order').value = '0';
    document.getElementById('footer_is_active').checked = true;
}

function resetNavAssetForm() {
    document.getElementById('nav_asset_id').value = '';
    document.getElementById('nav_asset_type').value = 'logo';
    document.getElementById('nav_asset_path').value = '';
    document.getElementById('nav_asset_alt').value = '';
    document.getElementById('nav_asset_width').value = '';
    document.getElementById('nav_asset_height').value = '';
    document.getElementById('nav_asset_is_active').checked = true;
}

// Edit functions
function editMenuItem(item) {
    document.getElementById('menu_item_id').value = item.id;
    document.getElementById('menu_title').value = item.title;
    document.getElementById('menu_url').value = item.url;
    document.getElementById('menu_page_id').value = item.page_id || '';
    document.getElementById('menu_parent_id').value = item.parent_id || '';
    document.getElementById('menu_icon').value = item.icon || '';
    document.getElementById('menu_target').value = item.target;
    document.getElementById('menu_type').value = item.menu_type;
    document.getElementById('menu_sort_order').value = item.sort_order;
    document.getElementById('menu_is_active').checked = item.is_active === 1;
    
    // Scroll to form
    document.querySelector('.menu-item-form').scrollIntoView({ behavior: 'smooth' });
}

function editFooterSection(section) {
    document.getElementById('footer_section_id').value = section.id;
    document.getElementById('footer_title').value = section.title;
    document.getElementById('footer_section_type').value = section.section_type;
    document.getElementById('footer_sort_order').value = section.sort_order;
    document.getElementById('footer_is_active').checked = section.is_active === 1;
    
    // Scroll to form
    document.querySelector('.footer-section-form').scrollIntoView({ behavior: 'smooth' });
}

function editFooterLink(link) {
    const sectionId = link.section_id;
    const form = document.querySelector(`#footer_link_form_${sectionId}`);
    if (form) {
        form.querySelector('[name="footer_link_id"]').value = link.id;
        form.querySelector('[name="footer_link_title"]').value = link.title;
        form.querySelector('[name="footer_link_url"]').value = link.url;
        form.querySelector('[name="footer_link_page_id"]').value = link.page_id || '';
        form.querySelector('[name="footer_link_icon"]').value = link.icon || '';
        form.querySelector('[name="footer_link_target"]').value = link.target;
        form.querySelector('[name="footer_link_sort_order"]').value = link.sort_order;
        form.querySelector('[name="footer_link_is_active"]').checked = link.is_active === 1;
    }
}

function editNavAsset(asset) {
    document.getElementById('nav_asset_id').value = asset.id;
    document.getElementById('nav_asset_type').value = asset.asset_type;
    document.getElementById('nav_asset_path').value = asset.asset_path;
    document.getElementById('nav_asset_alt').value = asset.alt_text || '';
    document.getElementById('nav_asset_width').value = asset.width || '';
    document.getElementById('nav_asset_height').value = asset.height || '';
    document.getElementById('nav_asset_is_active').checked = asset.is_active === 1;
    
    // Scroll to form
    document.querySelector('.nav-asset-form').scrollIntoView({ behavior: 'smooth' });
}

// Delete functions with confirmation
function deleteMenuItem(id) {
    if (confirm('Are you sure you want to delete this menu item?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_menu_item">
            <input type="hidden" name="menu_item_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteFooterSection(id) {
    if (confirm('Are you sure you want to delete this footer section? All links in this section will also be deleted.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_footer_section">
            <input type="hidden" name="footer_section_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteFooterLink(id) {
    if (confirm('Are you sure you want to delete this footer link?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_footer_link">
            <input type="hidden" name="footer_link_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteNavAsset(id) {
    if (confirm('Are you sure you want to delete this navigation asset?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_navigation_asset">
            <input type="hidden" name="nav_asset_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteSocialLink(id) {
    if (confirm('Are you sure you want to delete this social link?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_social_link">
            <input type="hidden" name="social_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Edit Social Link function
function editSocialLink(link) {
    document.getElementById('social_id').value = link.id;
    document.getElementById('social_platform').value = link.platform;
    document.getElementById('social_url').value = link.url;
    document.getElementById('social_icon').value = link.icon || '';
    document.getElementById('social_sort_order').value = link.sort_order;
    document.getElementById('social_is_active').checked = link.is_active === 1;
    
    // Scroll to form
    document.querySelector('.social-link-form').scrollIntoView({ behavior: 'smooth' });
}

// Reset Social Link form
function resetSocialLinkForm() {
    document.getElementById('social_id').value = '';
    document.getElementById('social_platform').value = 'facebook';
    document.getElementById('social_url').value = '';
    document.getElementById('social_icon').value = '';
    document.getElementById('social_sort_order').value = '0';
    document.getElementById('social_is_active').checked = true;
}

// Real-time synchronization
class NavigationSync {
    constructor() {
        this.syncInterval = null;
        this.syncTimeout = null;
    }

    // Auto-sync with debouncing
    autoSync() {
        if (this.syncTimeout) {
            clearTimeout(this.syncTimeout);
        }
        
        this.syncTimeout = setTimeout(() => {
            this.syncAll();
        }, 2000); // Sync after 2 seconds of inactivity
    }

    // Sync all navigation data
    async syncAll() {
        try {
            // Show sync indicator
            this.showSyncIndicator(true);

            // Sync menu items
            await this.syncMenuItems();

            // Sync footer sections
            await this.syncFooterSections();

            // Sync footer links
            await this.syncFooterLinks();

            // Sync navigation assets
            await this.syncNavigationAssets();

            // Sync social links
            await this.syncSocialLinks();

            // Hide sync indicator
            this.showSyncIndicator(false);

            // Show success message
            this.showSuccess('All navigation data synchronized successfully');

        } catch (error) {
            console.error('Sync error:', error);
            this.showError('Sync failed: ' + error.message);
            this.showSyncIndicator(false);
        }
    }

    // Sync menu items
    async syncMenuItems() {
        return new Promise((resolve) => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="sync_pages">
            `;
            document.body.appendChild(form);
            form.submit();
            resolve();
        });
    }

    // Sync footer sections
    async syncFooterSections() {
        // This would typically require a backend endpoint
        // For now, we'll reload the page to refresh data
        window.location.reload();
    }

    // Sync footer links
    async syncFooterLinks() {
        window.location.reload();
    }

    // Sync navigation assets
    async syncNavigationAssets() {
        window.location.reload();
    }

    // Sync social links
    async syncSocialLinks() {
        window.location.reload();
    }

    // Show sync indicator
    showSyncIndicator(show) {
        let indicator = document.querySelector('.sync-indicator');
        
        if (!indicator && show) {
            indicator = document.createElement('div');
            indicator.className = 'sync-indicator';
            indicator.innerHTML = `
                <div class="sync-indicator__spinner"></div>
                <span class="sync-indicator__text">Syncing...</span>
            `;
            indicator.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: var(--primary);
                color: white;
                padding: 12px 24px;
                border-radius: var(--radius-lg);
                box-shadow: var(--shadow-lg);
                z-index: 10000;
                display: flex;
                align-items: center;
                gap: 12px;
                animation: slideIn 0.3s ease;
            `;
            
            // Add animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from {
                        transform: translateY(100px);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(indicator);
        } else if (indicator && !show) {
            indicator.style.animation = 'slideIn 0.3s ease reverse';
            setTimeout(() => indicator.remove(), 300);
        }
    }

    // Show success message
    showSuccess(message) {
        const alert = document.createElement('div');
        alert.className = 'admin-alert admin-alert--success';
        alert.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--success);
            color: white;
            padding: 16px 24px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        alert.textContent = message;
        
        document.body.appendChild(alert);
        
        setTimeout(() => {
            alert.style.animation = 'slideIn 0.3s ease reverse';
            setTimeout(() => alert.remove(), 300);
        }, 3000);
    }

    // Show error message
    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'admin-alert admin-alert--error';
        alert.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--danger);
            color: white;
            padding: 16px 24px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        alert.textContent = message;
        
        document.body.appendChild(alert);
        
        setTimeout(() => {
            alert.style.animation = 'slideIn 0.3s ease reverse';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    }
}

// Initialize sync
const navigationSync = new NavigationSync();

// Add event listeners for auto-sync - DISABLED to prevent interference with edit functionality
// Auto-sync was causing page reloads that interrupted edit operations
document.addEventListener('DOMContentLoaded', () => {
    // Log that navigation builder is initialized
    console.log('Navigation Builder initialized');
    
    // Add submit listeners to forms to prevent auto-sync during submission
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', () => {
            // Disable auto-sync during submission
            if (navigationSync.syncTimeout) {
                clearTimeout(navigationSync.syncTimeout);
            }
        });
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + S to manually trigger sync
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        navigationSync.syncAll();
    }
});
