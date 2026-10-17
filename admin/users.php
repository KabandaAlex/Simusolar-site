<?php
/**
 * User Management
 * 
 * RBAC: Requires admin role for access
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/includes/auth_helpers.php';
requireRole('admin', 'index.php'); // Only admins can manage users

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$pdo = getDb();
$currentNav = 'users';
$pageTitle = 'User Management';

$message = '';
$messageType = '';

// Get current admin ID
$currentAdminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create_user':
                // Validate form data
                $errors = [];
                
                if (empty($_POST['username'])) {
                    $errors['username'] = 'Username is required';
                }
                
                if (empty($_POST['email'])) {
                    $errors['email'] = 'Email is required';
                } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Invalid email format';
                }
                
                if (empty($_POST['password'])) {
                    $errors['password'] = 'Password is required';
                } elseif (strlen($_POST['password']) < 8) {
                    $errors['password'] = 'Password must be at least 8 characters';
                }
                
                if (empty($_POST['role'])) {
                    $errors['role'] = 'Role is required';
                }
                
                if (empty($errors)) {
                    // Check if username already exists
                    $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = ?');
                    $stmt->execute([$_POST['username']]);
                    if ($stmt->fetch()) {
                        $errors['username'] = 'Username already exists';
                    }
                    
                    // Check if email already exists
                    $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ?');
                    $stmt->execute([$_POST['email']]);
                    if ($stmt->fetch()) {
                        $errors['email'] = 'Email already exists';
                    }
                }
                
                if (empty($errors)) {
                    // Hash password
                    $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare('INSERT INTO admins (username, email, password_hash, role, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                    $stmt->execute([
                        $_POST['username'],
                        $_POST['email'],
                        $passwordHash,
                        $_POST['role'],
                        $currentAdminId
                    ]);
                    
                    logAdminActivity('Created new user: ' . $_POST['username']);
                    $message = 'User created successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Please fix the errors below.';
                    $messageType = 'error';
                }
                break;
                
            case 'update_user':
                $userId = (int)$_POST['user_id'];
                
                // Validate
                $errors = [];
                
                if (empty($_POST['username'])) {
                    $errors['username'] = 'Username is required';
                }
                
                if (empty($_POST['email'])) {
                    $errors['email'] = 'Email is required';
                } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Invalid email format';
                }
                
                if (empty($_POST['role'])) {
                    $errors['role'] = 'Role is required';
                }
                
                if (empty($errors)) {
                    // Check if username exists for other users
                    $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = ? AND id != ?');
                    $stmt->execute([$_POST['username'], $userId]);
                    if ($stmt->fetch()) {
                        $errors['username'] = 'Username already exists';
                    }
                    
                    // Check if email exists for other users
                    $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ? AND id != ?');
                    $stmt->execute([$_POST['email'], $userId]);
                    if ($stmt->fetch()) {
                        $errors['email'] = 'Email already exists';
                    }
                }
                
                if (empty($errors)) {
                    $stmt = $pdo->prepare('UPDATE admins SET username = ?, email = ?, role = ?, updated_by = ?, updated_at = NOW() WHERE id = ?');
                    $stmt->execute([
                        $_POST['username'],
                        $_POST['email'],
                        $_POST['role'],
                        $currentAdminId,
                        $userId
                    ]);
                    
                    logAdminActivity('Updated user: ' . $_POST['username']);
                    $message = 'User updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Please fix the errors below.';
                    $messageType = 'error';
                }
                break;
                
            case 'reset_password':
                $userId = (int)$_POST['user_id'];
                $newPassword = $_POST['new_password'] ?? '';
                
                if (strlen($newPassword) < 8) {
                    $message = 'Password must be at least 8 characters';
                    $messageType = 'error';
                } else {
                    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE admins SET password_hash = ?, login_attempts = 0, is_locked = 0, locked_until = NULL, updated_by = ?, updated_at = NOW() WHERE id = ?');
                    $stmt->execute([$passwordHash, $currentAdminId, $userId]);
                    
                    // Get username for logging
                    $stmt = $pdo->prepare('SELECT username FROM admins WHERE id = ?');
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                    
                    logAdminActivity('Reset password for user: ' . ($user['username'] ?? $userId));
                    $message = 'Password reset successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'toggle_lock':
                $userId = (int)$_POST['user_id'];
                $isLocked = isset($_POST['is_locked']) && (int)$_POST['is_locked'] === 1 ? 1 : 0;
                $lockedUntil = $isLocked && !empty($_POST['locked_until']) ? $_POST['locked_until'] : NULL;
                
                $stmt = $pdo->prepare('UPDATE admins SET is_locked = ?, locked_until = ?, updated_by = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([$isLocked, $lockedUntil, $currentAdminId, $userId]);
                
                // Get username for logging
                $stmt = $pdo->prepare('SELECT username FROM admins WHERE id = ?');
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                $actionText = $isLocked ? 'Locked' : 'Unlocked';
                logAdminActivity($actionText . ' user: ' . ($user['username'] ?? $userId));
                $message = 'User ' . strtolower($actionText) . ' successfully!';
                $messageType = 'success';
                break;
                
            case 'toggle_disable':
                $userId = (int)$_POST['user_id'];
                $isDisabled = isset($_POST['is_disabled']) && (int)$_POST['is_disabled'] === 1 ? 1 : 0;
                
                $stmt = $pdo->prepare('UPDATE admins SET is_disabled = ?, updated_by = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([$isDisabled, $currentAdminId, $userId]);
                
                // Get username for logging
                $stmt = $pdo->prepare('SELECT username FROM admins WHERE id = ?');
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                $actionText = $isDisabled ? 'Disabled' : 'Enabled';
                logAdminActivity($actionText . ' user: ' . ($user['username'] ?? $userId));
                $message = 'User ' . strtolower($actionText) . ' successfully!';
                $messageType = 'success';
                break;
                
            case 'delete_user':
                $userId = (int)$_POST['user_id'];
                
                if ($userId === $currentAdminId) {
                    $message = 'You cannot delete your own account!';
                    $messageType = 'error';
                } else {
                    // Get username for logging
                    $stmt = $pdo->prepare('SELECT username FROM admins WHERE id = ?');
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                    
                    $stmt = $pdo->prepare('DELETE FROM admins WHERE id = ?');
                    $stmt->execute([$userId]);
                    
                    logAdminActivity('Deleted user: ' . ($user['username'] ?? $userId));
                    $message = 'User deleted successfully!';
                    $messageType = 'success';
                }
                break;
        }
    } catch (PDOException $e) {
        $message = 'Database error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get all users
$users = $pdo->query('SELECT * FROM admins ORDER BY created_at DESC')->fetchAll();

$roles = ['admin', 'editor', 'viewer'];

include __DIR__ . '/includes/header.php';
?>

<!-- Content Area -->
<div class="admin-content">
  <?php if ($message): ?>
    <div class="admin-alert admin-alert--<?php echo $messageType; ?> alert-dismissible">
      <?php echo htmlspecialchars($message); ?>
      <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
  <?php endif; ?>

  <!-- Dashboard Hero -->
  <div class="dashboard-hero">
    <div class="dashboard-hero__content">
      <div class="dashboard-hero__text">
        <div class="dashboard-hero__greeting">User Management</div>
        <h1 class="dashboard-hero__title">Manage Admin Users</h1>
        <p class="dashboard-hero__subtitle">Create, edit, and manage admin users with advanced permissions and controls</p>
      </div>
      <div class="dashboard-hero__actions">
        <button type="button" class="dashboard-hero__btn" onclick="openCreateModal()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Create New User
        </button>
      </div>
    </div>
  </div>

  <!-- Users Stats -->
  <div class="dashboard-stats">
    <div class="stat-card stat-card--purple">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
          </svg>
        </div>
      </div>
      <div class="stat-card__value"><?php echo count($users); ?></div>
      <div class="stat-card__label">Total Users</div>
    </div>
    
    <div class="stat-card stat-card--green">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
        </div>
      </div>
      <div class="stat-card__value"><?php echo count(array_filter($users, fn($u) => $u['is_disabled'] == 0 && $u['is_locked'] == 0)); ?></div>
      <div class="stat-card__label">Active Users</div>
    </div>
    
    <div class="stat-card stat-card--orange">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
        </div>
      </div>
      <div class="stat-card__value"><?php echo count(array_filter($users, fn($u) => $u['is_locked'] == 1)); ?></div>
      <div class="stat-card__label">Locked Users</div>
    </div>
    
    <div class="stat-card stat-card--blue">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="15" y1="9" x2="9" y2="15"/>
            <line x1="9" y1="9" x2="15" y2="15"/>
          </svg>
        </div>
      </div>
      <div class="stat-card__value"><?php echo count(array_filter($users, fn($u) => $u['is_disabled'] == 1)); ?></div>
      <div class="stat-card__label">Disabled Users</div>
    </div>
  </div>

  <!-- Users Table -->
  <div class="admin-card">
    <div class="admin-card__header">
      <h3 class="admin-card__title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
        </svg>
        All Users
      </h3>
    </div>
    <div class="admin-card__body">
      <?php if (!empty($users)): ?>
        <div class="data-table">
          <table class="data-table__table">
            <thead>
              <tr>
                <th>User</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last Login</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user): ?>
                <tr>
                  <td>
                    <div class="data-table__cell-primary">
                      <div class="data-table__cell-title"><?php echo htmlspecialchars($user['username']); ?></div>
                      <div class="data-table__cell-subtitle"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                  </td>
                  <td>
                    <span class="badge badge--<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'editor' ? 'warning' : 'info'); ?>">
                      <?php echo ucfirst($user['role'] ?? 'admin'); ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($user['is_disabled'] == 1): ?>
                      <span class="badge badge--danger">Disabled</span>
                    <?php elseif ($user['is_locked'] == 1): ?>
                      <span class="badge badge--warning">
                        Locked
                        <?php if ($user['locked_until']): ?>
                          (until <?php echo date('M j, Y', strtotime($user['locked_until'])); ?>)
                        <?php endif; ?>
                      </span>
                    <?php else: ?>
                      <span class="badge badge--success">Active</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($user['last_login']): ?>
                      <div class="data-table__cell-subtitle"><?php echo date('M j, Y g:i A', strtotime($user['last_login'])); ?></div>
                    <?php else: ?>
                      <span class="text-muted">Never</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="data-table__actions">
                      <button type="button" class="btn btn--secondary btn--sm btn-icon" onclick="openEditModal(<?php echo (int)$user['id']; ?>)" title="Edit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8z"/>
                          <polyline points="11 4 11 8 20 8"/>
                        </svg>
                      </button>
                      <button type="button" class="btn btn--secondary btn--sm btn-icon" onclick="openPasswordResetModal(<?php echo (int)$user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" title="Reset Password">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                      </button>
                      <?php if ($user['id'] != $currentAdminId): ?>
                        <button type="button" class="btn btn--ghost btn--sm btn-icon btn--<?php echo $user['is_locked'] == 1 ? 'success' : 'warning'; ?>" onclick="toggleLock(<?php echo (int)$user['id']; ?>, <?php echo $user['is_locked'] == 1 ? 0 : 1; ?>)" title="<?php echo $user['is_locked'] == 1 ? 'Unlock' : 'Lock'; ?>">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <?php if ($user['is_locked'] == 1): ?>
                              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                              <path d="M7 11V7a5 5 0 0 1 9.9-1"/>
                            <?php else: ?>
                              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            <?php endif; ?>
                          </svg>
                        </button>
                        <button type="button" class="btn btn--ghost btn--sm btn-icon btn--<?php echo $user['is_disabled'] == 1 ? 'success' : 'danger'; ?>" onclick="toggleDisable(<?php echo (int)$user['id']; ?>, <?php echo $user['is_disabled'] == 1 ? 0 : 1; ?>)" title="<?php echo $user['is_disabled'] == 1 ? 'Enable' : 'Disable'; ?>">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <?php if ($user['is_disabled'] == 1): ?>
                              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                              <circle cx="12" cy="12" r="3"/>
                            <?php else: ?>
                              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-5.523 0-10-4.477-10-10a10.07 10.07 0 0 1 .94-2.94L1 1l20 20-1.06 1.06zM2.06 2.06L12 12l9.94 9.94A10.07 10.07 0 0 1 18 0z"/>
                            <?php endif; ?>
                          </svg>
                        </button>
                        <button type="button" class="btn btn--ghost btn--sm btn-icon btn--danger" onclick="confirmDelete(<?php echo (int)$user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" title="Delete">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                          </svg>
                        </button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
          </svg>
          <h3 class="empty-state__title">No Users Found</h3>
          <p class="empty-state__text">Create your first admin user to get started.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Create User Modal -->
<div id="createUserModal" class="modal">
  <div class="modal__overlay" onclick="closeCreateModal()"></div>
  <div class="modal__content">
    <div class="modal__header">
      <h3 class="modal__title">Create New User</h3>
      <button type="button" class="modal__close" onclick="closeCreateModal()">&times;</button>
    </div>
    <form method="POST" action="" class="modal__body">
      <input type="hidden" name="action" value="create_user">
      
      <div class="form-group">
        <label for="create_username">Username</label>
        <input type="text" id="create_username" name="username" required>
      </div>
      
      <div class="form-group">
        <label for="create_email">Email</label>
        <input type="email" id="create_email" name="email" required>
      </div>
      
      <div class="form-group">
        <label for="create_password">Password</label>
        <input type="password" id="create_password" name="password" required minlength="8">
        <small class="form-hint">Password must be at least 8 characters</small>
      </div>
      
      <div class="form-group">
        <label for="create_role">Role</label>
        <select id="create_role" name="role" required>
          <?php foreach ($roles as $role): ?>
            <option value="<?php echo $role; ?>"><?php echo ucfirst($role); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Create User</button>
        <button type="button" class="btn btn--secondary" onclick="closeCreateModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
  <div class="modal__overlay" onclick="closeEditModal()"></div>
  <div class="modal__content">
    <div class="modal__header">
      <h3 class="modal__title">Edit User</h3>
      <button type="button" class="modal__close" onclick="closeEditModal()">&times;</button>
    </div>
    <form method="POST" action="" class="modal__body">
      <input type="hidden" name="action" value="update_user">
      <input type="hidden" name="user_id" id="edit_user_id">
      
      <div class="form-group">
        <label for="edit_username">Username</label>
        <input type="text" id="edit_username" name="username" required>
      </div>
      
      <div class="form-group">
        <label for="edit_email">Email</label>
        <input type="email" id="edit_email" name="email" required>
      </div>
      
      <div class="form-group">
        <label for="edit_role">Role</label>
        <select id="edit_role" name="role" required>
          <?php foreach ($roles as $role): ?>
            <option value="<?php echo $role; ?>"><?php echo ucfirst($role); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Update User</button>
        <button type="button" class="btn btn--secondary" onclick="closeEditModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Password Reset Modal -->
<div id="passwordResetModal" class="modal">
  <div class="modal__overlay" onclick="closePasswordResetModal()"></div>
  <div class="modal__content">
    <div class="modal__header">
      <h3 class="modal__title">Reset Password</h3>
      <button type="button" class="modal__close" onclick="closePasswordResetModal()">&times;</button>
    </div>
    <form method="POST" action="" class="modal__body">
      <input type="hidden" name="action" value="reset_password">
      <input type="hidden" name="user_id" id="reset_user_id">
      
      <p class="form-hint">Resetting password for: <strong id="reset_username"></strong></p>
      
      <div class="form-group">
        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password" required minlength="8">
        <small class="form-hint">Password must be at least 8 characters</small>
      </div>
      
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Reset Password</button>
        <button type="button" class="btn btn--secondary" onclick="closePasswordResetModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Lock User Modal -->
<form id="lockUserForm" method="POST" action="" style="display: none;">
  <input type="hidden" name="action" value="toggle_lock">
  <input type="hidden" name="user_id" id="lock_user_id">
  <input type="hidden" name="is_locked" id="lock_is_locked">
  <input type="hidden" name="locked_until" id="lock_locked_until">
</form>

<!-- Disable User Modal -->
<form id="disableUserForm" method="POST" action="" style="display: none;">
  <input type="hidden" name="action" value="toggle_disable">
  <input type="hidden" name="user_id" id="disable_user_id">
  <input type="hidden" name="is_disabled" id="disable_is_disabled">
</form>

<!-- Delete User Modal -->
<form id="deleteUserForm" method="POST" action="" style="display: none;">
  <input type="hidden" name="action" value="delete_user">
  <input type="hidden" name="user_id" id="delete_user_id">
</form>

<style>
.modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1000;
}

.modal__overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
}

.modal__content {
  position: relative;
  max-width: 500px;
  margin: 100px auto;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.modal__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal__title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
}

.modal__close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6b7280;
}

.modal__close:hover {
  color: #111827;
}

.modal__body {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #374151;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-hint {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.form-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  margin-top: 2rem;
}

.data-table__actions {
  display: flex;
  gap: 0.5rem;
}

.badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 9999px;
  text-transform: uppercase;
}

.badge--success {
  background-color: #d1fae5;
  color: #065f46;
}

.badge--warning {
  background-color: #fef3c7;
  color: #92400e;
}

.badge--danger {
  background-color: #fee2e2;
  color: #991b1b;
}

.badge--info {
  background-color: #dbeafe;
  color: #1e40af;
}

.text-muted {
  color: #6b7280;
}

.dashboard-hero__actions {
  display: flex;
  gap: 1rem;
}

.dashboard-hero__btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background-color: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.375rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.dashboard-hero__btn:hover {
  background-color: #2563eb;
}

.dashboard-hero__btn svg {
  width: 1.25rem;
  height: 1.25rem;
}
</style>

<script>
const users = <?php echo json_encode($users); ?>;

function openCreateModal() {
  document.getElementById('createUserModal').style.display = 'block';
}

function closeCreateModal() {
  document.getElementById('createUserModal').style.display = 'none';
}

function openEditModal(userId) {
  const user = users.find(u => u.id === userId);
  if (user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role || 'admin';
    document.getElementById('editUserModal').style.display = 'block';
  }
}

function closeEditModal() {
  document.getElementById('editUserModal').style.display = 'none';
}

function openPasswordResetModal(userId, username) {
  document.getElementById('reset_user_id').value = userId;
  document.getElementById('reset_username').textContent = username;
  document.getElementById('passwordResetModal').style.display = 'block';
}

function closePasswordResetModal() {
  document.getElementById('passwordResetModal').style.display = 'none';
}

function toggleLock(userId, isLocked) {
  if (isLocked) {
    document.getElementById('lock_user_id').value = userId;
    document.getElementById('lock_is_locked').value = 0;
    document.getElementById('lock_locked_until').value = '';
    document.getElementById('lockUserForm').submit();
  } else {
    const lockedUntil = prompt('Enter lock expiration date (YYYY-MM-DD) or leave empty for indefinite lock:');
    if (lockedUntil !== null) {
      document.getElementById('lock_user_id').value = userId;
      document.getElementById('lock_is_locked').value = 1;
      document.getElementById('lock_locked_until').value = lockedUntil || '';
      document.getElementById('lockUserForm').submit();
    }
  }
}

function toggleDisable(userId, isDisabled) {
  const action = isDisabled ? 'enable' : 'disable';
  if (confirm(`Are you sure you want to ${action} this user?`)) {
    document.getElementById('disable_user_id').value = userId;
    document.getElementById('disable_is_disabled').value = isDisabled ? 0 : 1;
    document.getElementById('disableUserForm').submit();
  }
}

function confirmDelete(userId, username) {
  if (confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
    document.getElementById('delete_user_id').value = userId;
    document.getElementById('deleteUserForm').submit();
  }
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeCreateModal();
    closeEditModal();
    closePasswordResetModal();
  }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>