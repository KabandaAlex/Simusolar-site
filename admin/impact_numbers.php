<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
$pdo = getDb();
$currentNav = 'impact';
$pageTitle = 'Impact Numbers';

$message = '';
$iconOptions = ['farmers' => 'Farmers / People', 'acres' => 'Acres / Leaf', 'income' => 'Income / Chart', 'offices' => 'Offices / Location'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update' && !empty($_POST['id'])) {
        try {
            $stmt = $pdo->prepare('UPDATE impact_numbers SET label = ?, value = ?, sublabel = ?, icon = ?, sort_order = ?, is_active = ? WHERE id = ?');
            $stmt->execute([
                trim($_POST['label'] ?? ''),
                trim($_POST['value'] ?? ''),
                trim($_POST['sublabel'] ?? ''),
                trim($_POST['icon'] ?? '') ?: null,
                (int)($_POST['sort_order'] ?? 0),
                isset($_POST['is_active']) ? 1 : 0,
                (int)$_POST['id']
            ]);
            $message = 'Impact stat updated successfully.';
        } catch (Throwable $e) {
            $stmt = $pdo->prepare('UPDATE impact_numbers SET label = ?, value = ?, sublabel = ?, sort_order = ?, is_active = ? WHERE id = ?');
            $stmt->execute([
                trim($_POST['label'] ?? ''),
                trim($_POST['value'] ?? ''),
                trim($_POST['sublabel'] ?? ''),
                (int)($_POST['sort_order'] ?? 0),
                isset($_POST['is_active']) ? 1 : 0,
                (int)$_POST['id']
            ]);
            $message = 'Impact stat updated successfully.';
        }
    } elseif ($action === 'create') {
        try {
            $stmt = $pdo->prepare('INSERT INTO impact_numbers (label, value, sublabel, icon, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                trim($_POST['label'] ?? ''),
                trim($_POST['value'] ?? ''),
                trim($_POST['sublabel'] ?? ''),
                trim($_POST['icon'] ?? '') ?: null,
                (int)($_POST['sort_order'] ?? 0),
                isset($_POST['is_active']) ? 1 : 0
            ]);
            $message = 'Impact stat added successfully.';
        } catch (Throwable $e) {
            $stmt = $pdo->prepare('INSERT INTO impact_numbers (label, value, sublabel, sort_order, is_active) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                trim($_POST['label'] ?? ''),
                trim($_POST['value'] ?? ''),
                trim($_POST['sublabel'] ?? ''),
                (int)($_POST['sort_order'] ?? 0),
                isset($_POST['is_active']) ? 1 : 0
            ]);
            $message = 'Impact stat added successfully.';
        }
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare('DELETE FROM impact_numbers WHERE id = ?');
        $stmt->execute([(int)$_POST['id']]);
        $message = 'Impact stat deleted successfully.';
    }
}

$items = [];
try {
    $items = $pdo->query('SELECT * FROM impact_numbers ORDER BY sort_order, id')->fetchAll();
} catch (Throwable $e) { /* table may not exist */ }

$activeStats = count(array_filter($items, fn($i) => $i['is_active']));
$statsWithIcons = count(array_filter($items, fn($i) => !empty($i['icon'])));

include __DIR__ . '/includes/header.php';
?>

<!-- Enhanced Page Header -->
 <div class="admin-content">
<div class="page-header-enhanced">
  <div class="page-header-enhanced__content">
    <h1 class="page-header-enhanced__title">Impact Numbers</h1>
    <p class="page-header-enhanced__subtitle">Manage statistics displayed in the "Our Impact in Numbers" section on the home page.</p>
  </div>
</div>
</div>
<?php if ($message): ?>
  <div class="admin-alert admin-alert--success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<!-- Page Stats Overview -->
<div class="page-stats-grid">
  <div class="page-stat-card page-stat-card--purple">
    <div class="page-stat-card__value"><?php echo count($items); ?></div>
    <div class="page-stat-card__label">Total Stats</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      All statistics
    </div>
  </div>

  <div class="page-stat-card page-stat-card--green">
    <div class="page-stat-card__value"><?php echo $activeStats; ?></div>
    <div class="page-stat-card__label">Active</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
      <?php echo $items ? round(($activeStats / count($items)) * 100) : 0; ?>% visible
    </div>
  </div>

  <div class="page-stat-card page-stat-card--blue">
    <div class="page-stat-card__value"><?php echo $statsWithIcons; ?></div>
    <div class="page-stat-card__label">With Custom Icons</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0 2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0 2.83l.06.06a1.65 1.65 0 0 0 .33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      <?php echo $items ? round(($statsWithIcons / count($items)) * 100) : 0; ?>% custom icons
    </div>
  </div>
</div>

<!-- Filter Toolbar -->
<div class="filter-toolbar">
  <div class="filter-toolbar__search">
    <svg class="filter-toolbar__search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="stat-search" placeholder="Search stats..." onkeyup="filterStats()">
  </div>
  <div class="filter-toolbar__filters">
    <select class="filter-toolbar__select" id="status-filter" onchange="filterStats()">
      <option value="">All Status</option>
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
    </select>
  </div>
  <button type="button" class="btn btn--primary" onclick="var f=document.getElementById('create-form'); f.style.display=f.style.display==='none'?'block':'none'">
    <svg viewBox="0 0 24 24" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Stat
  </button>
</div>

<!-- Create Form -->
<div id="create-form" style="display: none;" class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      New Impact Stat
    </h3>
  </div>
  <div class="admin-card__body">
    <form method="post">
      <input type="hidden" name="action" value="create">
      
      <div class="form-group">
        <label>Label</label>
        <input type="text" name="label" class="form-control" required placeholder="e.g. Farmers Empowered">
      </div>
      <div class="form-group">
        <label>Value</label>
        <input type="text" name="value" class="form-control" required placeholder="e.g. 50K+ or 50%">
      </div>
      <div class="form-group">
        <label>Sublabel</label>
        <input type="text" name="sublabel" class="form-control" placeholder="e.g. Lives transformed through solar power">
      </div>
      <div class="form-group">
        <label>Icon (optional)</label>
        <select name="icon" class="form-control" style="max-width: 220px;">
          <option value="">— Default by position —</option>
          <?php foreach ($iconOptions as $key => $label): ?>
          <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Sort order</label>
        <input type="number" name="sort_order" class="form-control" value="0" style="max-width: 100px;">
      </div>
      <div class="form-group">
        <label><input type="checkbox" name="is_active" checked> Active</label>
      </div>
      
      <button type="submit" class="btn btn--primary">Add</button>
      <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('create-form').style.display='none'">Cancel</button>
    </form>
  </div>
</div>

<!-- Live Preview -->
<?php if (!empty($items)): ?>
<div class="impact-preview">
  <div class="impact-preview__title">Preview how stats appear on website</div>
  <div class="impact-preview__grid">
    <?php foreach ($items as $row): ?>
    <?php if ($row['is_active']): ?>
    <div class="impact-preview-card">
      <div class="impact-preview-card__icon">
        <?php 
        $iconMap = [
          'farmers' => '👥',
          'acres' => '🌿',
          'income' => '📊',
          'offices' => '📍'
        ];
        echo $iconMap[$row['icon']] ?? '📈';
        ?>
      </div>
      <div class="impact-preview-card__value"><?php echo e($row['value']); ?></div>
      <div class="impact-preview-card__label"><?php echo e($row['label']); ?></div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Stats Table -->
<div class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      All Impact Stats
    </h3>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table enhanced-data-table" id="stats-table">
      <thead>
        <tr>
          <th>Label</th>
          <th>Value</th>
          <th>Sublabel</th>
          <th>Icon</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $row): ?>
        <tr data-label="<?php echo strtolower(htmlspecialchars($row['label'])); ?>" 
            data-value="<?php echo strtolower(htmlspecialchars($row['value'])); ?>"
            data-status="<?php echo $row['is_active'] ? 'active' : 'inactive'; ?>">
          <td><?php echo e($row['label']); ?></td>
          <td><?php echo e($row['value']); ?></td>
          <td><?php echo e($row['sublabel'] ?? ''); ?></td>
          <td><code style="font-size: 0.8rem;"><?php echo htmlspecialchars($row['icon'] ?? '—'); ?></code></td>
          <td><span class="badge <?php echo $row['is_active'] ? 'badge--success' : 'badge--muted'; ?>"><?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
          <td>
            <div style="display: flex; gap: var(--space-2);">
              <button type="button" class="btn btn--primary btn--sm" onclick="document.getElementById('edit-form-<?php echo (int)$row['id']; ?>').style.display='block'">Edit</button>
              <form method="post" style="display:inline;" onsubmit="return confirm('Delete this stat?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                <button type="submit" class="btn btn--outline btn--sm">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <tr id="edit-form-<?php echo (int)$row['id']; ?>" class="expand-row" style="display: none;">
          <td colspan="6">
            <form method="post" style="max-width: 480px;">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
              
              <div class="form-group">
                <label>Label</label>
                <input type="text" name="label" class="form-control" value="<?php echo htmlspecialchars($row['label']); ?>" required>
              </div>
              <div class="form-group">
                <label>Value</label>
                <input type="text" name="value" class="form-control" value="<?php echo htmlspecialchars($row['value']); ?>" required>
              </div>
              <div class="form-group">
                <label>Sublabel</label>
                <input type="text" name="sublabel" class="form-control" value="<?php echo htmlspecialchars($row['sublabel'] ?? ''); ?>">
              </div>
              <div class="form-group">
                <label>Icon (optional)</label>
                <select name="icon" class="form-control" style="max-width: 220px;">
                  <option value="">— Default by position —</option>
                  <?php foreach ($iconOptions as $key => $label): ?>
                  <option value="<?php echo htmlspecialchars($key); ?>" <?php echo isset($row['icon']) && $row['icon'] === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Sort order</label>
                <input type="number" name="sort_order" class="form-control" value="<?php echo (int)$row['sort_order']; ?>" style="max-width: 100px;">
              </div>
              <div class="form-group">
                <label><input type="checkbox" name="is_active" <?php echo $row['is_active'] ? 'checked' : ''; ?>> Active</label>
              </div>
              
              <button type="submit" class="btn btn--primary">Save</button>
              <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('edit-form-<?php echo (int)$row['id']; ?>').style.display='none'">Cancel</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function filterStats() {
  const searchValue = document.getElementById('stat-search').value.toLowerCase();
  const statusFilter = document.getElementById('status-filter').value;
  const rows = document.querySelectorAll('#stats-table tbody tr[data-label]');
  
  rows.forEach(function(row) {
    const label = row.dataset.label;
    const value = row.dataset.value;
    const status = row.dataset.status;
    
    let matchesSearch = label.includes(searchValue) || value.includes(searchValue);
    let matchesStatus = !statusFilter || status === statusFilter;
    
    if (matchesSearch && matchesStatus) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
