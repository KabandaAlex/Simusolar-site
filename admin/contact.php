<?php
/**
 * Admin - Contact Information Management
 * 
 * Manage contact information displayed on the public website.
 * 
 * @version 1.0.0
 */

require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/activity_logger.php';

// Auth check
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$adminBase = rtrim(BASE_PATH, '/') . '/admin';
$pdo = getDb();

$message = '';
$messageType = 'success';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_contact') {
            // Update contact information
            $contactFields = [
                'phone' => trim($_POST['phone'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'whatsapp' => trim($_POST['whatsapp'] ?? ''),
                'facebook' => trim($_POST['facebook'] ?? ''),
                'twitter' => trim($_POST['twitter'] ?? ''),
                'linkedin' => trim($_POST['linkedin'] ?? ''),
                'instagram' => trim($_POST['instagram'] ?? ''),
                'business_hours_weekday' => trim($_POST['business_hours_weekday'] ?? ''),
                'business_hours_weekend' => trim($_POST['business_hours_weekend'] ?? ''),
            ];
            
            foreach ($contactFields as $key => $value) {
                // Check if key exists
                $stmt = $pdo->prepare('SELECT id FROM contact_info WHERE `key` = ?');
                $stmt->execute([$key]);
                
                if ($stmt->fetch()) {
                    // Update existing
                    $stmt = $pdo->prepare('UPDATE contact_info SET value = ?, updated_at = NOW() WHERE `key` = ?');
                    $stmt->execute([$value, $key]);
                } else {
                    // Insert new
                    $stmt = $pdo->prepare('INSERT INTO contact_info (`key`, value, sort_order) VALUES (?, ?, 0)');
                    $stmt->execute([$key, $value]);
                }
            }
            
            logAdminActivity('Updated contact information');
            $message = 'Contact information updated successfully!';
            $messageType = 'success';
        }
        
        if ($action === 'add_inquiry_type') {
            // Add new inquiry type
            $name = trim($_POST['inquiry_type_name'] ?? '');
            $slug = trim($_POST['inquiry_type_slug'] ?? '');
            $description = trim($_POST['inquiry_type_description'] ?? '');
            $icon = trim($_POST['inquiry_type_icon'] ?? 'help-circle');
            $sortOrder = (int)($_POST['inquiry_type_sort'] ?? 0);
            
            if (empty($name) || empty($slug)) {
                $message = 'Name and slug are required for inquiry type.';
                $messageType = 'error';
            } else {
                $stmt = $pdo->prepare('INSERT INTO contact_inquiry_types (name, slug, description, icon, sort_order) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$name, $slug, $description, $icon, $sortOrder]);
                
                logAdminActivity('Added inquiry type: ' . $name);
                $message = 'Inquiry type added successfully!';
                $messageType = 'success';
            }
        }
        
        if ($action === 'add_inquiry_reason') {
            // Add new inquiry reason
            $typeId = (int)($_POST['reason_type_id'] ?? 0);
            $reason = trim($_POST['reason_text'] ?? '');
            $sortOrder = (int)($_POST['reason_sort'] ?? 0);
            
            if (empty($typeId) || empty($reason)) {
                $message = 'Type and reason text are required.';
                $messageType = 'error';
            } else {
                $stmt = $pdo->prepare('INSERT INTO contact_inquiry_reasons (inquiry_type_id, reason, sort_order) VALUES (?, ?, ?)');
                $stmt->execute([$typeId, $reason, $sortOrder]);
                
                logAdminActivity('Added inquiry reason: ' . $reason);
                $message = 'Inquiry reason added successfully!';
                $messageType = 'success';
            }
        }
        
        if ($action === 'toggle_inquiry_type') {
            $id = (int)($_POST['inquiry_type_id'] ?? 0);
            $isActive = (int)($_POST['is_active'] ?? 0);
            
            $stmt = $pdo->prepare('UPDATE contact_inquiry_types SET is_active = ? WHERE id = ?');
            $stmt->execute([$isActive, $id]);
            
            $message = $isActive ? 'Inquiry type activated!' : 'Inquiry type deactivated!';
            $messageType = 'success';
        }
        
        if ($action === 'delete_inquiry_reason') {
            $id = (int)($_POST['reason_id'] ?? 0);
            
            $stmt = $pdo->prepare('DELETE FROM contact_inquiry_reasons WHERE id = ?');
            $stmt->execute([$id]);
            
            logAdminActivity('Deleted inquiry reason ID: ' . $id);
            $message = 'Inquiry reason deleted!';
            $messageType = 'success';
        }
        
    } catch (PDOException $e) {
        $message = 'Database error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get current contact info
$contactInfo = [];
try {
    $stmt = $pdo->query('SELECT * FROM contact_info ORDER BY sort_order ASC');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $contactInfo[$row['key']] = $row['value'];
    }
} catch (PDOException $e) {
    // Table may not exist yet
}

// Get inquiry types
$inquiryTypes = [];
try {
    $stmt = $pdo->query('SELECT * FROM contact_inquiry_types ORDER BY sort_order ASC');
    $inquiryTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table may not exist yet
}

// Get inquiry reasons
$inquiryReasons = [];
try {
    $stmt = $pdo->query('SELECT r.*, t.name as type_name FROM contact_inquiry_reasons r LEFT JOIN contact_inquiry_types t ON r.inquiry_type_id = t.id ORDER BY t.sort_order, r.sort_order ASC');
    $inquiryReasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table may not exist yet
}

$currentNav = 'contact';
include __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <?php if ($message): ?>
        <div class="admin-alert admin-alert--<?php echo $messageType; ?> alert-dismissible">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header-enhanced">
        <div class="page-header-enhanced__breadcrumb">
            <a href="<?php echo $adminBase; ?>">Dashboard</a>
            <span>/</span>
            <span>Contact Info</span>
        </div>
        <h1 class="page-header-enhanced__title">Contact Information</h1>
        <p class="page-header-enhanced__subtitle">Manage contact details and inquiry options displayed on your website.</p>
    </div>

    <div class="admin-grid admin-grid--2-col">
        <!-- Contact Information Form -->
        <div class="admin-card">
            <div class="admin-card__header">
                <h2 class="admin-card__title">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/>
                    </svg>
                    Contact Details
                </h2>
            </div>
            <div class="admin-card__body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_contact">
                    
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($contactInfo['phone'] ?? ''); ?>" placeholder="+256 700 123 456">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($contactInfo['email'] ?? ''); ?>" placeholder="info@tulimasolar.com">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Physical Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Kampala, Uganda"><?php echo htmlspecialchars($contactInfo['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">WhatsApp Number</label>
                        <input type="text" name="whatsapp" class="form-control" value="<?php echo htmlspecialchars($contactInfo['whatsapp'] ?? ''); ?>" placeholder="+256 700 123 456">
                    </div>
                    
                    <h3 style="margin: 1.5rem 0 1rem; font-size: 1rem; font-weight: 600;">Business Hours</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Weekday Hours</label>
                        <input type="text" name="business_hours_weekday" class="form-control" value="<?php echo htmlspecialchars($contactInfo['business_hours_weekday'] ?? ''); ?>" placeholder="Mon - Fri: 8:00 AM - 6:00 PM">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Weekend Hours</label>
                        <input type="text" name="business_hours_weekend" class="form-control" value="<?php echo htmlspecialchars($contactInfo['business_hours_weekend'] ?? ''); ?>" placeholder="Sat: 9:00 AM - 2:00 PM">
                    </div>
                    
                    <h3 style="margin: 1.5rem 0 1rem; font-size: 1rem; font-weight: 600;">Social Media Links</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Facebook URL</label>
                        <input type="url" name="facebook" class="form-control" value="<?php echo htmlspecialchars($contactInfo['facebook'] ?? ''); ?>" placeholder="https://facebook.com/tulimasolar">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Twitter URL</label>
                        <input type="url" name="twitter" class="form-control" value="<?php echo htmlspecialchars($contactInfo['twitter'] ?? ''); ?>" placeholder="https://twitter.com/tulimasolar">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">LinkedIn URL</label>
                        <input type="url" name="linkedin" class="form-control" value="<?php echo htmlspecialchars($contactInfo['linkedin'] ?? ''); ?>" placeholder="https://linkedin.com/company/tulimasolar">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Instagram URL</label>
                        <input type="url" name="instagram" class="form-control" value="<?php echo htmlspecialchars($contactInfo['instagram'] ?? ''); ?>" placeholder="https://instagram.com/tulimasolar">
                    </div>
                    
                    <button type="submit" class="btn btn--primary">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                            <polyline points="7 3 7 8 15 8"/>
                        </svg>
                        Save Changes
                    </button>
                </form>
            </div>
        </div>

        <!-- Inquiry Types & Reasons -->
        <div class="admin-card">
            <div class="admin-card__header">
                <h2 class="admin-card__title">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    Inquiry Types
                </h2>
            </div>
            <div class="admin-card__body">
                <!-- Current Inquiry Types -->
                <?php if (!empty($inquiryTypes)): ?>
                    <div class="inquiry-types-list" style="margin-bottom: 1.5rem;">
                        <?php foreach ($inquiryTypes as $type): ?>
                            <div class="inquiry-type-item" style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; background: <?php echo $type['is_active'] ? '#f8f9fa' : '#fff3cd'; ?>; border-radius: 8px; margin-bottom: 0.5rem;">
                                <div>
                                    <strong><?php echo htmlspecialchars($type['name']); ?></strong>
                                    <small style="display: block; color: #666;"><?php echo htmlspecialchars($type['description'] ?? ''); ?></small>
                                </div>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_inquiry_type">
                                    <input type="hidden" name="inquiry_type_id" value="<?php echo $type['id']; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo $type['is_active'] ? 0 : 1; ?>">
                                    <button type="submit" class="btn btn--sm <?php echo $type['is_active'] ? 'btn--outline' : 'btn--success'; ?>">
                                        <?php echo $type['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="admin-empty-state" style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 8px; margin-bottom: 1.5rem;">
                        <p>No inquiry types found. Run the migration or add types below.</p>
                    </div>
                <?php endif; ?>

                <!-- Add New Inquiry Type -->
                <details class="admin-accordion" style="margin-bottom: 1.5rem;">
                    <summary style="padding: 0.75rem 1rem; background: #660099; color: white; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        + Add New Inquiry Type
                    </summary>
                    <div style="padding: 1rem; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px;">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_inquiry_type">
                            
                            <div class="form-group">
                                <label class="form-label">Name *</label>
                                <input type="text" name="inquiry_type_name" class="form-control" required placeholder="e.g., General Inquiry">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Slug *</label>
                                <input type="text" name="inquiry_type_slug" class="form-control" required placeholder="e.g., general">
                                <small class="form-hint">URL-friendly identifier (lowercase, no spaces)</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <input type="text" name="inquiry_type_description" class="form-control" placeholder="Brief description">
                            </div>
                            
                            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label class="form-label">Icon</label>
                                    <select name="inquiry_type_icon" class="form-control">
                                        <option value="help-circle">Help Circle</option>
                                        <option value="file-text">File Text</option>
                                        <option value="tool">Tool</option>
                                        <option value="users">Users</option>
                                        <option value="briefcase">Briefcase</option>
                                        <option value="mail">Mail</option>
                                        <option value="message-circle">Message</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Sort Order</label>
                                    <input type="number" name="inquiry_type_sort" class="form-control" value="0">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn--primary btn--sm">Add Type</button>
                        </form>
                    </div>
                </details>

                <!-- Inquiry Reasons -->
                <h3 style="margin-bottom: 1rem; font-size: 1rem; font-weight: 600;">Inquiry Reasons</h3>
                
                <?php if (!empty($inquiryReasons)): ?>
                    <div class="inquiry-reasons-list" style="max-height: 300px; overflow-y: auto; margin-bottom: 1rem;">
                        <?php 
                        $currentType = '';
                        foreach ($inquiryReasons as $reason): 
                            if ($currentType !== $reason['type_name']):
                                $currentType = $reason['type_name'];
                        ?>
                            <div style="font-weight: 600; margin-top: 1rem; padding: 0.5rem; background: #e9ecef; border-radius: 4px;">
                                <?php echo htmlspecialchars($reason['type_name'] ?? 'Unknown'); ?>
                            </div>
                        <?php endif; ?>
                            <div class="inquiry-reason-item" style="display: flex; align-items: center; justify-content: space-between; padding: 0.5rem; border-bottom: 1px solid #eee;">
                                <span><?php echo htmlspecialchars($reason['reason']); ?></span>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_inquiry_reason">
                                    <input type="hidden" name="reason_id" value="<?php echo $reason['id']; ?>">
                                    <button type="submit" class="btn btn--sm btn--danger" onclick="return confirm('Delete this reason?')">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Add New Reason -->
                <details class="admin-accordion">
                    <summary style="padding: 0.75rem 1rem; background: #E98E39; color: white; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        + Add New Reason
                    </summary>
                    <div style="padding: 1rem; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px;">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_inquiry_reason">
                            
                            <div class="form-group">
                                <label class="form-label">Inquiry Type *</label>
                                <select name="reason_type_id" class="form-control" required>
                                    <option value="">Select type...</option>
                                    <?php foreach ($inquiryTypes as $type): ?>
                                        <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Reason Text *</label>
                                <input type="text" name="reason_text" class="form-control" required placeholder="e.g., Product Information">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="reason_sort" class="form-control" value="0">
                            </div>
                            
                            <button type="submit" class="btn btn--primary btn--sm">Add Reason</button>
                        </form>
                    </div>
                </details>
            </div>
        </div>
    </div>
</div>

<style>
.admin-grid--2-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

@media (max-width: 991px) {
    .admin-grid--2-col {
        grid-template-columns: 1fr;
    }
}

.admin-accordion summary {
    list-style: none;
}

.admin-accordion summary::-webkit-details-marker {
    display: none;
}

.inquiry-type-item:hover {
    background-color: #e9ecef;
}

.inquiry-reason-item:hover {
    background-color: #f8f9fa;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
