<?php
/**
 * Tulima Solar - Currencies Management
 * Full CRUD for currencies with default currency support
 * 
 * RBAC: Requires admin role
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$pdo = getDb();
$currentNav = 'currencies';
$pageTitle = 'Currencies';

$message = '';
$messageType = 'success';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid security token. Please try again.';
        $messageType = 'error';
    } else {
        // Create new currency
        if ($action === 'create') {
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $name = trim($_POST['name'] ?? '');
            
            if ($code && $name) {
                $symbol = trim($_POST['symbol'] ?? '') ?: $code;
                $exchangeRate = (float)($_POST['exchange_rate'] ?? 1.0);
                $displayOrder = (int)($_POST['display_order'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                $isDefault = isset($_POST['is_default']) ? 1 : 0;
                
                try {
                    $pdo->beginTransaction();
                    
                    // If this is set as default, remove default from others
                    if ($isDefault) {
                        $pdo->exec('UPDATE currencies SET is_default = 0');
                    }
                    
                    $stmt = $pdo->prepare('INSERT INTO currencies (code, name, symbol, exchange_rate, is_default, is_active, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$code, $name, $symbol, $exchangeRate, $isDefault, $isActive, $displayOrder]);
                    $newId = $pdo->lastInsertId();
                    
                    $pdo->commit();
                    
                    logActivity('created', 'currency', $newId, $code);
                    $message = 'Currency created successfully.';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    if (strpos($e->getMessage(), 'Duplicate') !== false) {
                        $message = 'Currency code already exists. Please use a different code.';
                    } else {
                        $message = 'Error creating currency: ' . $e->getMessage();
                    }
                    $messageType = 'error';
                }
            }
        }
        
        // Update currency
        if ($action === 'update' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $name = trim($_POST['name'] ?? '');
            
            if ($code && $name) {
                $symbol = trim($_POST['symbol'] ?? '') ?: $code;
                $exchangeRate = (float)($_POST['exchange_rate'] ?? 1.0);
                $displayOrder = (int)($_POST['display_order'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                $isDefault = isset($_POST['is_default']) ? 1 : 0;
                
                try {
                    $pdo->beginTransaction();
                    
                    // If this is set as default, remove default from others
                    if ($isDefault) {
                        $stmt = $pdo->prepare('UPDATE currencies SET is_default = 0 WHERE id != ?');
                        $stmt->execute([$id]);
                    }
                    
                    $stmt = $pdo->prepare('UPDATE currencies SET code = ?, name = ?, symbol = ?, exchange_rate = ?, is_default = ?, is_active = ?, display_order = ? WHERE id = ?');
                    $stmt->execute([$code, $name, $symbol, $exchangeRate, $isDefault, $isActive, $displayOrder, $id]);
                    
                    $pdo->commit();
                    
                    logActivity('updated', 'currency', $id, $code);
                    $message = 'Currency updated successfully.';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    if (strpos($e->getMessage(), 'Duplicate') !== false) {
                        $message = 'Currency code already exists. Please use a different code.';
                    } else {
                        $message = 'Error updating currency: ' . $e->getMessage();
                    }
                    $messageType = 'error';
                }
            }
        }
        
        // Delete currency
        if ($action === 'delete' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            try {
                // Check if currency is default
                $stmt = $pdo->prepare('SELECT code, is_default FROM currencies WHERE id = ?');
                $stmt->execute([$id]);
                $currency = $stmt->fetch();
                
                if ($currency && $currency['is_default']) {
                    $message = 'Cannot delete the default currency. Set another currency as default first.';
                    $messageType = 'error';
                } else {
                    // Check if currency is used by jobs
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM jobs WHERE currency_id = ?');
                    $stmt->execute([$id]);
                    $jobCount = (int)$stmt->fetchColumn();
                    
                    if ($jobCount > 0) {
                        $message = "Cannot delete currency: $jobCount job(s) using this currency. Please update those jobs first.";
                        $messageType = 'error';
                    } else {
                        $stmt = $pdo->prepare('DELETE FROM currencies WHERE id = ?');
                        $stmt->execute([$id]);
                        
                        logActivity('deleted', 'currency', $id, $currency['code']);
                        $message = 'Currency deleted successfully.';
                        $messageType = 'success';
                    }
                }
            } catch (PDOException $e) {
                $message = 'Error deleting currency: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
        
        // Set as default
        if ($action === 'set_default' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            try {
                $pdo->beginTransaction();
                
                // Remove default from all
                $pdo->exec('UPDATE currencies SET is_default = 0');
                
                // Set new default
                $stmt = $pdo->prepare('UPDATE currencies SET is_default = 1, is_active = 1 WHERE id = ?');
                $stmt->execute([$id]);
                
                $stmt = $pdo->prepare('SELECT code FROM currencies WHERE id = ?');
                $stmt->execute([$id]);
                $code = $stmt->fetchColumn();
                
                $pdo->commit();
                
                logActivity('set as default', 'currency', $id, $code);
                $message = 'Default currency updated successfully.';
                $messageType = 'success';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = 'Error setting default currency: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
        
        // Toggle active status
        if ($action === 'toggle' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            // Check if trying to deactivate the default currency
            $stmt = $pdo->prepare('SELECT code, is_default, is_active FROM currencies WHERE id = ?');
            $stmt->execute([$id]);
            $currency = $stmt->fetch();
            
            if ($currency && $currency['is_default'] && $currency['is_active']) {
                $message = 'Cannot deactivate the default currency. Set another currency as default first.';
                $messageType = 'error';
            } else {
                $stmt = $pdo->prepare('UPDATE currencies SET is_active = NOT is_active WHERE id = ?');
                $stmt->execute([$id]);
                
                logActivity('toggled', 'currency', $id, $currency['code']);
                $message = 'Currency status updated.';
                $messageType = 'success';
            }
        }
        
        // Update exchange rates
        if ($action === 'update_rates' && !empty($_POST['rates'])) {
            $rates = json_decode($_POST['rates'], true);
            if (is_array($rates)) {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare('UPDATE currencies SET exchange_rate = ? WHERE id = ?');
                    
                    foreach ($rates as $id => $rate) {
                        $stmt->execute([(float)$rate, (int)$id]);
                    }
                    
                    $pdo->commit();
                    
                    logActivity('updated rates', 'currencies', null, 'Exchange rates');
                    $message = 'Exchange rates updated successfully.';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $message = 'Error updating exchange rates: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
        
        // Bulk delete
        if ($action === 'bulk_delete' && !empty($_POST['selected_ids'])) {
            $ids = json_decode($_POST['selected_ids'], true);
            if (is_array($ids) && count($ids) > 0) {
                try {
                    $deletedCount = 0;
                    $skippedCount = 0;
                    
                    foreach ($ids as $id) {
                        $id = (int)$id;
                        
                        // Check if currency can be deleted
                        $stmt = $pdo->prepare('SELECT code, is_default FROM currencies WHERE id = ?');
                        $stmt->execute([$id]);
                        $currency = $stmt->fetch();
                        
                        if ($currency && $currency['is_default']) {
                            $skippedCount++;
                            continue;
                        }
                        
                        $stmt = $pdo->prepare('SELECT COUNT(*) FROM jobs WHERE currency_id = ?');
                        $stmt->execute([$id]);
                        $jobCount = (int)$stmt->fetchColumn();
                        
                        if ($jobCount === 0) {
                            $stmt = $pdo->prepare('DELETE FROM currencies WHERE id = ?');
                            $stmt->execute([$id]);
                            
                            logActivity('deleted', 'currency', $id, $currency['code']);
                            $deletedCount++;
                        } else {
                            $skippedCount++;
                        }
                    }
                    
                    if ($deletedCount > 0 && $skippedCount > 0) {
                        $message = "Deleted $deletedCount currency/currencies. Skipped $skippedCount (default or in use).";
                    } elseif ($deletedCount > 0) {
                        $message = "Successfully deleted $deletedCount currency/currencies.";
                    } else {
                        $message = "No currencies deleted. Selected currencies are either default or in use.";
                        $messageType = 'error';
                    }
                } catch (PDOException $e) {
                    $message = 'Error deleting currencies: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
    }
}

// Get all currencies with job counts
$currencies = $pdo->query('
    SELECT c.*, 
           (SELECT COUNT(*) FROM jobs WHERE currency_id = c.id) AS job_count
    FROM currencies c
    ORDER BY c.is_default DESC, c.display_order, c.id
')->fetchAll();

// Stats
$totalCurrencies = count($currencies);
$activeCurrencies = count(array_filter($currencies, fn($c) => $c['is_active']));
$defaultCurrency = array_filter($currencies, fn($c) => $c['is_default']);
$defaultCurrency = reset($defaultCurrency);
$currenciesWithJobs = count(array_filter($currencies, fn($c) => $c['job_count'] > 0));

$basePath = BASE_PATH;

include __DIR__ . '/includes/header.php';
?>

<!-- Enhanced Page Header -->
<div class="admin-content">
<div class="page-header-enhanced">
  <div class="page-header-enhanced__content">
    <h1 class="page-header-enhanced__title">Currencies</h1>
    <p class="page-header-enhanced__subtitle">Manage currencies for job salary display. Set a default currency and configure exchange rates for conversions.</p>
  </div>
</div>
</div>

<?php if ($message): ?>
  <div class="admin-alert admin-alert--<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>" id="currency-message">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="admin-alert__close" onclick="this.parentElement.remove()">&times;</button>
  </div>
<?php endif; ?>

<!-- Page Stats Overview -->
<div class="page-stats-grid">
  <div class="page-stat-card page-stat-card--purple">
    <div class="page-stat-card__value"><?php echo $totalCurrencies; ?></div>
    <div class="page-stat-card__label">Total Currencies</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      All currencies
    </div>
  </div>

  <div class="page-stat-card page-stat-card--green">
    <div class="page-stat-card__value"><?php echo $activeCurrencies; ?></div>
    <div class="page-stat-card__label">Active</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
      <?php echo $totalCurrencies ? round(($activeCurrencies / $totalCurrencies) * 100) : 0; ?>% available
    </div>
  </div>

  <div class="page-stat-card page-stat-card--blue">
    <div class="page-stat-card__value"><?php echo $defaultCurrency ? htmlspecialchars($defaultCurrency['code']) : '-'; ?></div>
    <div class="page-stat-card__label">Default Currency</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
      Base currency
    </div>
  </div>

  <div class="page-stat-card page-stat-card--orange">
    <div class="page-stat-card__value"><?php echo $currenciesWithJobs; ?></div>
    <div class="page-stat-card__label">In Use</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      Currencies with jobs
    </div>
  </div>
</div>

<!-- Filter Toolbar -->
<div class="filter-toolbar">
  <div class="filter-toolbar__search">
    <svg class="filter-toolbar__search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="currency-search" placeholder="Search currencies..." onkeyup="filterCurrencies()">
  </div>
  <div class="filter-toolbar__filters">
    <select class="filter-toolbar__select" id="status-filter" onchange="filterCurrencies()">
      <option value="">All Status</option>
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
    </select>
  </div>
  <button type="button" class="btn btn--primary" onclick="var f=document.getElementById('create-form'); f.style.display=f.style.display==='none'?'block':'none'">
    <svg viewBox="0 0 24 24" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Currency
  </button>
</div>

<!-- Create Form -->
<div id="create-form" style="display: none;" class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      New Currency
    </h3>
  </div>
  <div class="admin-card__body">
    <form method="post" id="currency-create-form">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
      
      <div class="form-section">
        <p class="form-section-title">Currency Information</p>
        <div class="form-row">
          <div class="form-group">
            <label>Code <span class="required">*</span></label>
            <input type="text" name="code" id="create-code" class="form-control" required maxlength="3" placeholder="e.g., USD" style="max-width: 120px; text-transform: uppercase;">
            <small class="form-hint">3-letter ISO currency code</small>
          </div>
          <div class="form-group">
            <label>Name <span class="required">*</span></label>
            <input type="text" name="name" id="create-name" class="form-control" required placeholder="e.g., US Dollar">
          </div>
          <div class="form-group">
            <label>Symbol</label>
            <input type="text" name="symbol" id="create-symbol" class="form-control" maxlength="10" placeholder="e.g., $" style="max-width: 100px;">
            <small class="form-hint">Currency symbol</small>
          </div>
        </div>
      </div>
      
      <div class="form-section">
        <p class="form-section-title">Exchange Rate</p>
        <div class="form-row">
          <div class="form-group">
            <label>Exchange Rate</label>
            <input type="number" name="exchange_rate" id="create-rate" class="form-control" value="1.000000" step="0.000001" min="0" style="max-width: 180px;">
            <small class="form-hint">Rate relative to default currency (1.0 = base rate)</small>
          </div>
          <div class="form-group">
            <label>Display Order</label>
            <input type="number" name="display_order" class="form-control" value="0" style="max-width: 100px;">
          </div>
        </div>
      </div>
      
      <div class="form-section">
        <p class="form-section-title">Status</p>
        <div class="form-group">
          <label><input type="checkbox" name="is_active" checked> Active (available for selection)</label>
        </div>
        <div class="form-group">
          <label><input type="checkbox" name="is_default" id="create-default"> Set as Default Currency</label>
          <small class="form-hint">The default currency is used as the base for exchange rate calculations</small>
        </div>
      </div>
      
      <button type="submit" class="btn btn--primary">Create Currency</button>
      <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('create-form').style.display='none'">Cancel</button>
    </form>
  </div>
</div>

<!-- Currencies Table -->
<div class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      All Currencies
    </h3>
    <div class="admin-card__actions">
      <button type="button" class="btn btn--secondary btn--sm" onclick="toggleRateEdit()">
        <svg viewBox="0 0 24 24" width="14" height="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Quick Edit Rates
      </button>
    </div>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table enhanced-data-table" id="currencies-table">
      <thead>
        <tr>
          <th style="width: 30px;">
            <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
          </th>
          <th>Code</th>
          <th>Name</th>
          <th>Symbol</th>
          <th>Exchange Rate</th>
          <th>Jobs</th>
          <th>Default</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="currencies-tbody">
        <?php foreach ($currencies as $curr): ?>
        <tr data-id="<?php echo (int)$curr['id']; ?>" 
            data-code="<?php echo strtolower(htmlspecialchars($curr['code'])); ?>" 
            data-name="<?php echo strtolower(htmlspecialchars($curr['name'])); ?>" 
            data-status="<?php echo $curr['is_active'] ? 'active' : 'inactive'; ?>"
            class="currency-row <?php echo $curr['is_default'] ? 'currency-row--default' : ''; ?>">
          <td>
            <input type="checkbox" class="currency-checkbox" value="<?php echo (int)$curr['id']; ?>">
          </td>
          <td>
            <div class="enhanced-data-table__cell-primary">
              <div class="enhanced-data-table__cell-title">
                <strong><?php echo htmlspecialchars($curr['code']); ?></strong>
              </div>
            </div>
          </td>
          <td><?php echo htmlspecialchars($curr['name']); ?></td>
          <td>
            <span class="currency-symbol"><?php echo htmlspecialchars($curr['symbol']); ?></span>
          </td>
          <td>
            <input type="number" 
                   class="form-control rate-input" 
                   data-id="<?php echo (int)$curr['id']; ?>"
                   value="<?php echo number_format($curr['exchange_rate'], 6); ?>" 
                   step="0.000001" 
                   min="0"
                   style="max-width: 130px; display: none;">
            <span class="rate-display"><?php echo number_format($curr['exchange_rate'], 6); ?></span>
          </td>
          <td>
            <span class="badge badge--<?php echo $curr['job_count'] > 0 ? 'info' : 'muted'; ?>">
              <?php echo (int)$curr['job_count']; ?> job<?php echo $curr['job_count'] != 1 ? 's' : ''; ?>
            </span>
          </td>
          <td>
            <?php if ($curr['is_default']): ?>
              <span class="badge badge--primary">
                <svg viewBox="0 0 24 24" width="12" height="12"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Default
              </span>
            <?php else: ?>
              <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="set_default">
                <input type="hidden" name="id" value="<?php echo (int)$curr['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit" class="btn btn--link btn--sm" title="Set as default">Set Default</button>
              </form>
            <?php endif; ?>
          </td>
          <td><span class="badge <?php echo $curr['is_active'] ? 'badge--success' : 'badge--muted'; ?>"><?php echo $curr['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
          <td>
            <div style="display: flex; gap: var(--space-2); flex-wrap: wrap;">
              <button type="button" class="btn btn--primary btn--sm" data-toggle-row="<?php echo (int)$curr['id']; ?>">Edit</button>
              <?php if (!$curr['is_default']): ?>
                <form method="post" style="display: inline;" onsubmit="return confirmDelete(<?php echo (int)$curr['job_count']; ?>, <?php echo $curr['is_default'] ? 'true' : 'false'; ?>);">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo (int)$curr['id']; ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                  <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <tr id="row-form-<?php echo (int)$curr['id']; ?>" class="expand-row" style="display: none;">
          <td colspan="9">
            <form method="post" style="max-width: 640px;">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?php echo (int)$curr['id']; ?>">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
              
              <div class="form-section">
                <p class="form-section-title">Currency Information</p>
                <div class="form-row">
                  <div class="form-group">
                    <label>Code <span class="required">*</span></label>
                    <input type="text" name="code" id="edit-code-<?php echo (int)$curr['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($curr['code']); ?>" required maxlength="3" style="max-width: 120px; text-transform: uppercase;">
                  </div>
                  <div class="form-group">
                    <label>Name <span class="required">*</span></label>
                    <input type="text" name="name" id="edit-name-<?php echo (int)$curr['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($curr['name']); ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Symbol</label>
                    <input type="text" name="symbol" id="edit-symbol-<?php echo (int)$curr['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($curr['symbol']); ?>" maxlength="10" style="max-width: 100px;">
                  </div>
                </div>
              </div>
              
              <div class="form-section">
                <p class="form-section-title">Exchange Rate</p>
                <div class="form-row">
                  <div class="form-group">
                    <label>Exchange Rate</label>
                    <input type="number" name="exchange_rate" class="form-control" value="<?php echo number_format($curr['exchange_rate'], 6); ?>" step="0.000001" min="0" style="max-width: 180px;">
                  </div>
                  <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" class="form-control" value="<?php echo (int)$curr['display_order']; ?>" style="max-width: 100px;">
                  </div>
                </div>
              </div>
              
              <div class="form-section">
                <p class="form-section-title">Status</p>
                <div class="form-group">
                  <label><input type="checkbox" name="is_active" <?php echo $curr['is_active'] ? 'checked' : ''; ?>> Active (available for selection)</label>
                </div>
                <div class="form-group">
                  <label><input type="checkbox" name="is_default" <?php echo $curr['is_default'] ? 'checked' : ''; ?>> Set as Default Currency</label>
                </div>
              </div>
              
              <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn--primary">Save Currency</button>
                <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('row-form-<?php echo (int)$curr['id']; ?>').style.display='none'">Cancel</button>
              </div>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (count($currencies) > 0): ?>
  <div class="admin-card__footer">
    <button type="button" class="btn btn--danger btn--sm" onclick="bulkDelete()">
      <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
      Delete Selected
    </button>
  </div>
  <?php endif; ?>
</div>

<!-- Exchange Rate Info Card -->
<div class="admin-card" style="margin-top: 1.5rem;">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
      Exchange Rate Information
    </h3>
  </div>
  <div class="admin-card__body">
    <div class="info-box">
      <h4>How Exchange Rates Work</h4>
      <ul>
        <li><strong>Default Currency:</strong> The default currency always has an exchange rate of <code>1.000000</code>. It serves as the base currency for all conversions.</li>
        <li><strong>Other Currencies:</strong> Set their exchange rate relative to the default currency. For example, if the default is KES and USD rate is 153.50, then 1 USD = 153.50 KES.</li>
        <li><strong>Salary Display:</strong> Job salaries can be displayed in any active currency, with automatic conversion based on these rates.</li>
        <li><strong>Rate Updates:</strong> Use "Quick Edit Rates" to update multiple exchange rates at once. Remember to update rates regularly to reflect current market rates.</li>
      </ul>
    </div>
    
    <?php if ($defaultCurrency): ?>
    <div class="default-currency-info" style="margin-top: var(--space-4); padding: var(--space-3); background: var(--bg-secondary); border-radius: var(--radius-md);">
      <strong>Current Default:</strong> 
      <span class="currency-symbol"><?php echo htmlspecialchars($defaultCurrency['symbol']); ?></span>
      <?php echo htmlspecialchars($defaultCurrency['code']); ?> - 
      <?php echo htmlspecialchars($defaultCurrency['name']); ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<style>
/* Currency specific styles */
.currency-symbol {
  font-weight: 600;
  font-size: 1.1rem;
  color: var(--color-primary);
}

.currency-row--default {
  background: var(--bg-secondary);
}

.currency-row--default td:first-child {
  border-left: 3px solid var(--color-primary);
}

/* Rate input toggle */
.rate-input {
  font-size: 0.9rem;
}

.rate-display {
  font-family: monospace;
}

/* Info box */
.info-box {
  background: var(--bg-secondary);
  padding: var(--space-4);
  border-radius: var(--radius-md);
}

.info-box h4 {
  margin-bottom: var(--space-3);
  color: var(--color-primary);
}

.info-box ul {
  margin: 0;
  padding-left: var(--space-5);
}

.info-box li {
  margin-bottom: var(--space-2);
  line-height: 1.5;
}

.info-box code {
  background: var(--bg-tertiary, #e9ecef);
  padding: 2px 6px;
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
}

/* Form hints */
.form-hint {
  display: block;
  color: var(--text-muted);
  font-size: 0.8rem;
  margin-top: 4px;
}
</style>

<script>
// Auto-uppercase code input
document.getElementById('create-code').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

// Toggle edit forms
document.querySelectorAll('[data-toggle-row]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.getAttribute('data-toggle-row');
        var row = document.getElementById('row-form-' + id);
        if (row) {
            row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
        }
    });
});

// Filter currencies
function filterCurrencies() {
    var search = document.getElementById('currency-search').value.toLowerCase();
    var statusFilter = document.getElementById('status-filter').value;
    
    document.querySelectorAll('#currencies-tbody .currency-row').forEach(function(row) {
        var code = row.getAttribute('data-code');
        var name = row.getAttribute('data-name');
        var status = row.getAttribute('data-status');
        
        var matchesSearch = code.includes(search) || name.includes(search);
        var matchesStatus = !statusFilter || status === statusFilter;
        
        row.style.display = matchesSearch && matchesStatus ? '' : 'none';
        
        // Also hide the corresponding edit form row
        var formRow = document.getElementById('row-form-' + row.getAttribute('data-id'));
        if (formRow) {
            formRow.style.display = 'none';
        }
    });
}

// Select all checkbox
function toggleSelectAll() {
    var selectAll = document.getElementById('select-all').checked;
    document.querySelectorAll('.currency-checkbox').forEach(function(cb) {
        cb.checked = selectAll;
    });
}

// Confirm delete
function confirmDelete(jobCount, isDefault) {
    if (isDefault) {
        alert('Cannot delete the default currency. Set another currency as default first.');
        return false;
    }
    if (jobCount > 0) {
        alert('Cannot delete currency: ' + jobCount + ' job(s) using this currency. Please update those jobs first.');
        return false;
    }
    return confirm('Are you sure you want to delete this currency?');
}

// Bulk delete
function bulkDelete() {
    var selected = [];
    document.querySelectorAll('.currency-checkbox:checked').forEach(function(cb) {
        selected.push(cb.value);
    });
    
    if (selected.length === 0) {
        alert('Please select at least one currency to delete.');
        return;
    }
    
    if (!confirm('Are you sure you want to delete ' + selected.length + ' selected currency/currencies? Default currencies and currencies in use will be skipped.')) {
        return;
    }
    
    var form = document.createElement('form');
    form.method = 'post';
    form.innerHTML = '<input type="hidden" name="action" value="bulk_delete">' +
                     '<input type="hidden" name="selected_ids" value=\'' + JSON.stringify(selected) + '\'>' +
                     '<input type="hidden" name="csrf_token" value="' + document.querySelector('[name="csrf_token"]').value + '">';
    document.body.appendChild(form);
    form.submit();
}

// Toggle quick edit rates mode
var rateEditMode = false;

function toggleRateEdit() {
    rateEditMode = !rateEditMode;
    
    document.querySelectorAll('.rate-input').forEach(function(input) {
        input.style.display = rateEditMode ? 'block' : 'none';
    });
    
    document.querySelectorAll('.rate-display').forEach(function(span) {
        span.style.display = rateEditMode ? 'none' : 'block';
    });
    
    // Show save button if in edit mode
    var saveBtn = document.getElementById('save-rates-btn');
    if (!saveBtn && rateEditMode) {
        saveBtn = document.createElement('button');
        saveBtn.id = 'save-rates-btn';
        saveBtn.type = 'button';
        saveBtn.className = 'btn btn--primary btn--sm';
        saveBtn.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save Rates';
        saveBtn.onclick = saveRates;
        saveBtn.style.marginLeft = 'var(--space-2)';
        
        var cardActions = document.querySelector('.admin-card__actions');
        if (cardActions) {
            cardActions.appendChild(saveBtn);
        }
    } else if (saveBtn && !rateEditMode) {
        saveBtn.remove();
    }
}

function saveRates() {
    var rates = {};
    document.querySelectorAll('.rate-input').forEach(function(input) {
        rates[input.getAttribute('data-id')] = input.value;
    });
    
    var form = document.createElement('form');
    form.method = 'post';
    form.innerHTML = '<input type="hidden" name="action" value="update_rates">' +
                     '<input type="hidden" name="rates" value=\'' + JSON.stringify(rates) + '\'>' +
                     '<input type="hidden" name="csrf_token" value="' + document.querySelector('[name="csrf_token"]').value + '">';
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
