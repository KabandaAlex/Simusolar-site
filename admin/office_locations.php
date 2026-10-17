<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
$pdo = getDb();
$currentNav = 'offices';
$pageTitle = 'Office Locations';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare('UPDATE office_locations SET name = ?, address = ?, phone = ?, email = ?, region_or_city = ?, map_url = ?, latitude = ?, longitude = ?, sort_order = ?, is_active = ? WHERE id = ?');
        $stmt->execute([
            trim($_POST['name'] ?? ''),
            trim($_POST['address'] ?? ''),
            trim($_POST['phone'] ?? '') ?: null,
            trim($_POST['email'] ?? '') ?: null,
            trim($_POST['region_or_city'] ?? '') ?: null,
            trim($_POST['map_url'] ?? '') ?: null,
            trim($_POST['latitude'] ?? '') !== '' ? (float)$_POST['latitude'] : null,
            trim($_POST['longitude'] ?? '') !== '' ? (float)$_POST['longitude'] : null,
            (int)($_POST['sort_order'] ?? 0),
            isset($_POST['is_active']) ? 1 : 0,
            (int)$_POST['id']
        ]);
        $message = 'Office updated successfully.';
    } elseif ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO office_locations (name, address, phone, email, region_or_city, map_url, latitude, longitude, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            trim($_POST['name'] ?? ''),
            trim($_POST['address'] ?? ''),
            trim($_POST['phone'] ?? '') ?: null,
            trim($_POST['email'] ?? '') ?: null,
            trim($_POST['region_or_city'] ?? '') ?: null,
            trim($_POST['map_url'] ?? '') ?: null,
            trim($_POST['latitude'] ?? '') !== '' ? (float)$_POST['latitude'] : null,
            trim($_POST['longitude'] ?? '') !== '' ? (float)$_POST['longitude'] : null,
            (int)($_POST['sort_order'] ?? 0),
            isset($_POST['is_active']) ? 1 : 0
        ]);
        $message = 'Office added successfully.';
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare('DELETE FROM office_locations WHERE id = ?');
        $stmt->execute([(int)$_POST['id']]);
        $message = 'Office deleted successfully.';
    }
}

$items = [];
try {
    $items = $pdo->query('SELECT * FROM office_locations ORDER BY sort_order, id')->fetchAll();
} catch (Throwable $e) { /* table may not exist */ }

$activeOffices = count(array_filter($items, fn($i) => $i['is_active']));
$officesWithMap = count(array_filter($items, fn($i) => !empty($i['map_url'])));

require_once __DIR__ . '/../config/path.php';
$basePath = BASE_PATH;

include __DIR__ . '/includes/header.php';
?>

<!-- Enhanced Page Header -->
 <div class="admin-content">
<div class="page-header-enhanced">
  <div class="page-header-enhanced__content">
    <h1 class="page-header-enhanced__title">Office Locations</h1>
    <p class="page-header-enhanced__subtitle">Manage office locations displayed on your website - add name, address, phone, email, and optional map link.</p>
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
    <div class="page-stat-card__label">Total Offices</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13 9-13c0 .46.04.08.06.12l6.06 6.07c.04.06.08.06.12l-6.07-6.06c-.04-.06-.08-.06-.12z"/><path d="M22 6.92l-1.41-1.41c-.2-.2-.51-.2-.71 0l-1.63 1.63 1.41 1.41c-.2.2-.51.2-.71 0l-1.63-1.63c-.2-.2-.51-.2-.71 0L22 6.92z"/></svg>
      All locations
    </div>
  </div>

  <div class="page-stat-card page-stat-card--green">
    <div class="page-stat-card__value"><?php echo $activeOffices; ?></div>
    <div class="page-stat-card__label">Active</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
      <?php echo $items ? round(($activeOffices / count($items)) * 100) : 0; ?>% visible
    </div>
  </div>

  <div class="page-stat-card page-stat-card--blue">
    <div class="page-stat-card__value"><?php echo $officesWithMap; ?></div>
    <div class="page-stat-card__label">With Maps</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><polygon points="1 6 1 22 8 18 8 6 1 6"/><line x1="21" y1="8" x2="16" y2="13"/><line x1="21" y1="16" x2="8" y2="16"/><line x1="24" y1="12" x2="18" y2="12"/><line x1="18" y1="12" x2="24" y2="12"/></svg>
      <?php echo $items ? round(($officesWithMap / count($items)) * 100) : 0; ?>% with maps
    </div>
  </div>
</div>

<!-- Filter Toolbar -->
<div class="filter-toolbar">
  <div class="filter-toolbar__search">
    <svg class="filter-toolbar__search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="office-search" placeholder="Search offices..." onkeyup="filterOffices()">
  </div>
  <div class="filter-toolbar__filters">
    <select class="filter-toolbar__select" id="status-filter" onchange="filterOffices()">
      <option value="">All Status</option>
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
    </select>
  </div>
  <button type="button" class="btn btn--primary" onclick="var f=document.getElementById('create-form'); f.style.display=f.style.display==='none'?'block':'none'">
    <svg viewBox="0 0 24 24" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Office
  </button>
</div>

<!-- Create Form -->
<div id="create-form" style="display: none;" class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13 9-13c0 .46.04.08.06.12l6.06 6.07c.04.06.08.06.12l-6.07-6.06c-.04-.06-.08-.06-.12z"/><path d="M22 6.92l-1.41-1.41c-.2-.2-.51-.2-.71 0l-1.63 1.63 1.41 1.41c-.2.2-.51.2-.71 0l-1.63-1.63c-.2-.2-.51-.2-.71 0L22 6.92z"/></svg>
      New Office
    </h3>
  </div>
  <div class="admin-card__body">
    <form method="post">
      <input type="hidden" name="action" value="create">
      
      <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" class="form-control" required placeholder="e.g. Kampala Office">
      </div>
      <div class="form-group">
        <label>Region / City</label>
        <input type="text" name="region_or_city" class="form-control" placeholder="e.g. Kampala">
      </div>
      <div class="form-group">
        <label>Address</label>
        <textarea name="address" class="form-control" rows="3" placeholder="Street, building, etc."></textarea>
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input type="text" name="phone" class="form-control" placeholder="+256 ...">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" class="form-control" placeholder="office@example.com">
      </div>
      <div class="form-group">
        <label>Map URL (optional)</label>
        <input type="url" name="map_url" class="form-control" placeholder="https://maps.google.com/...">
      </div>
      <div class="form-group" style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <div>
          <label>Latitude (for map marker)</label>
          <input type="number" name="latitude" class="form-control" step="any" placeholder="e.g. 0.3476" style="max-width: 140px;">
        </div>
        <div>
          <label>Longitude (for map marker)</label>
          <input type="number" name="longitude" class="form-control" step="any" placeholder="e.g. 32.5825" style="max-width: 140px;">
        </div>
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

<!-- Office Cards Grid -->
<div class="card-grid" id="offices-grid">
  <?php foreach ($items as $row): ?>
  <div class="office-card" data-name="<?php echo strtolower(htmlspecialchars($row['name'])); ?>"
       data-region="<?php echo strtolower(htmlspecialchars($row['region_or_city'] ?? '')); ?>"
       data-status="<?php echo $row['is_active'] ? 'active' : 'inactive'; ?>">
    <div class="office-card__header">
      <div class="office-card__name">
        <svg viewBox="0 0 24 24" width="20" height="20"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13 9-13c0 .46.04.08.06.12l6.06 6.07c.04.06.08.06.12l-6.07-6.06c-.04-.06-.08-.06-.12z"/><path d="M22 6.92l-1.41-1.41c-.2-.2-.51-.2-.71 0l-1.63 1.63 1.41 1.41c-.2.2-.51.2-.71 0l-1.63-1.63c-.2-.2-.51-.2-.71 0L22 6.92z"/></svg>
        <?php echo e($row['name']); ?>
        <?php if (!$row['is_active']): ?>
        <span class="badge badge--muted" style="margin-left: var(--space-2);">Inactive</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="office-card__region"><?php echo e($row['region_or_city'] ?? '—'); ?></div>
    <div class="office-card__contact">
      <?php if (!empty($row['address'])): ?>
      <div class="office-card__contact-item">
        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13 9-13c0 .46.04.08.06.12l6.06 6.07c.04.06.08.06.12l-6.07-6.06c-.04-.06-.08-.06-.12z"/><path d="M22 6.92l-1.41-1.41c-.2-.2-.51-.2-.71 0l-1.63 1.63 1.41 1.41c-.2.2-.51.2-.71 0l-1.63-1.63c-.2-.2-.51-.2-.71 0L22 6.92z"/></svg>
        <?php echo htmlspecialchars(mb_substr($row['address'], 0, 50)); ?><?php echo mb_strlen($row['address']) > 50 ? '…' : ''; ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($row['phone'])): ?>
      <div class="office-card__contact-item">
        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M22 16.92v3a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-3"/><path d="M22 6.92l-1.41-1.41c-.2-.2-.51-.2-.71 0l-1.63 1.63 1.41 1.41c-.2.2-.51.2-.71 0l-1.63-1.63c-.2-.2-.51-.2-.71 0L22 6.92z"/></svg>
        <?php echo e($row['phone']); ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($row['email'])): ?>
      <div class="office-card__contact-item">
        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        <?php echo e($row['email']); ?>
      </div>
      <?php endif; ?>
    </div>
    <div class="office-card__actions">
      <button type="button" class="btn btn--primary btn--sm" onclick="document.getElementById('edit-form-<?php echo (int)$row['id']; ?>').style.display='block'">Edit</button>
      <form method="post" style="display:inline;" onsubmit="return confirm('Delete this office?');">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
        <button type="submit" class="btn btn--outline btn--sm">Delete</button>
      </form>
    </div>
  </div>
  
  <!-- Edit Form (Hidden by default) -->
  <div id="edit-form-<?php echo (int)$row['id']; ?>" style="display: none;" class="admin-card office-edit-form">
    <div class="admin-card__header">
      <h3 class="admin-card__title">Edit Office</h3>
    </div>
    <div class="admin-card__body">
      <form method="post">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
        
        <div class="form-group">
          <label>Name</label>
          <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($row['name']); ?>" required>
        </div>
        <div class="form-group">
          <label>Region / City</label>
          <input type="text" name="region_or_city" class="form-control" value="<?php echo htmlspecialchars($row['region_or_city'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label>Address</label>
          <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($row['address'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($row['phone'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($row['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label>Map URL (optional)</label>
          <input type="url" name="map_url" class="form-control" value="<?php echo htmlspecialchars($row['map_url'] ?? ''); ?>">
        </div>
        <div class="form-group" style="display: flex; gap: 1rem; flex-wrap: wrap;">
          <div>
            <label>Latitude (for map marker)</label>
            <input type="number" name="latitude" class="form-control" step="any" value="<?php echo isset($row['latitude']) && $row['latitude'] !== null && $row['latitude'] !== '' ? htmlspecialchars($row['latitude']) : ''; ?>" placeholder="e.g. 0.3476" style="max-width: 140px;">
          </div>
          <div>
            <label>Longitude (for map marker)</label>
            <input type="number" name="longitude" class="form-control" step="any" value="<?php echo isset($row['longitude']) && $row['longitude'] !== null && $row['longitude'] !== '' ? htmlspecialchars($row['longitude']) : ''; ?>" placeholder="e.g. 32.5825" style="max-width: 140px;">
          </div>
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
    </div>
  </div>
  <?php endforeach; ?>
</div>

<style>
.office-edit-form {
  grid-column: 1 / -1;
}
</style>

<script>
function filterOffices() {
  const searchValue = document.getElementById('office-search').value.toLowerCase();
  const statusFilter = document.getElementById('status-filter').value;
  const cards = document.querySelectorAll('.office-card');
  const forms = document.querySelectorAll('.office-edit-form');
  
  cards.forEach(function(card) {
    const name = card.dataset.name;
    const region = card.dataset.region;
    const status = card.dataset.status;
    
    let matchesSearch = name.includes(searchValue) || region.includes(searchValue);
    let matchesStatus = !statusFilter || status === statusFilter;
    
    if (matchesSearch && matchesStatus) {
      card.style.display = '';
      // Hide corresponding edit form
      const formId = card.nextElementSibling.id;
      if (formId && document.getElementById(formId)) {
        document.getElementById(formId).style.display = 'none';
      }
    } else {
      card.style.display = 'none';
    }
  });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
