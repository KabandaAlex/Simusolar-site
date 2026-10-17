<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
$pdo = getDb();
$currentNav = 'testimonials';
$pageTitle = 'Testimonials';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare('UPDATE testimonials SET quote = ?, author = ?, role = ?, image_path = ?, sort_order = ?, is_active = ? WHERE id = ?');
        $stmt->execute([
            trim($_POST['quote'] ?? ''),
            trim($_POST['author'] ?? ''),
            trim($_POST['role'] ?? ''),
            trim($_POST['image_path'] ?? '') ?: null,
            (int)($_POST['sort_order'] ?? 0),
            isset($_POST['is_active']) ? 1 : 0,
            (int)$_POST['id']
        ]);
        $message = 'Testimonial updated successfully.';
    } elseif ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO testimonials (quote, author, role, image_path, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            trim($_POST['quote'] ?? ''),
            trim($_POST['author'] ?? ''),
            trim($_POST['role'] ?? ''),
            trim($_POST['image_path'] ?? '') ?: null,
            (int)($_POST['sort_order'] ?? 0),
            isset($_POST['is_active']) ? 1 : 0
        ]);
        $message = 'Testimonial added successfully.';
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare('DELETE FROM testimonials WHERE id = ?');
        $stmt->execute([(int)$_POST['id']]);
        $message = 'Testimonial deleted successfully.';
    }
}

$testimonials = $pdo->query('SELECT * FROM testimonials ORDER BY sort_order, id')->fetchAll();
$activeTestimonials = count(array_filter($testimonials, fn($t) => $t['is_active']));
$testimonialsWithPhotos = count(array_filter($testimonials, fn($t) => !empty($t['image_path'])));

require_once __DIR__ . '/../config/path.php';
$basePath = BASE_PATH;

include __DIR__ . '/includes/header.php';
?>

<!-- Enhanced Page Header -->
 <div class="admin-content">
<div class="page-header-enhanced">
  <div class="page-header-enhanced__content">
    <h1 class="page-header-enhanced__title">Testimonials</h1>
    <p class="page-header-enhanced__subtitle">Manage client testimonials displayed on your website - home page and testimonials page.</p>
  </div>
</div>
</div>
<?php if ($message): ?>
  <div class="admin-alert admin-alert--success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<!-- Page Stats Overview -->
<div class="page-stats-grid">
  <div class="page-stat-card page-stat-card--purple">
    <div class="page-stat-card__value"><?php echo count($testimonials); ?></div>
    <div class="page-stat-card__label">Total Testimonials</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      All reviews
    </div>
  </div>

  <div class="page-stat-card page-stat-card--green">
    <div class="page-stat-card__value"><?php echo $activeTestimonials; ?></div>
    <div class="page-stat-card__label">Active</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
      <?php echo $testimonials ? round(($activeTestimonials / count($testimonials)) * 100) : 0; ?>% visible
    </div>
  </div>

  <div class="page-stat-card page-stat-card--blue">
    <div class="page-stat-card__value"><?php echo $testimonialsWithPhotos; ?></div>
    <div class="page-stat-card__label">With Photos</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      <?php echo $testimonials ? round(($testimonialsWithPhotos / count($testimonials)) * 100) : 0; ?>% with photos
    </div>
  </div>
</div>

<!-- Filter Toolbar -->
<div class="filter-toolbar">
  <div class="filter-toolbar__search">
    <svg class="filter-toolbar__search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="testimonial-search" placeholder="Search testimonials..." onkeyup="filterTestimonials()">
  </div>
  <div class="filter-toolbar__filters">
    <select class="filter-toolbar__select" id="status-filter" onchange="filterTestimonials()">
      <option value="">All Status</option>
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
    </select>
    <select class="filter-toolbar__select" id="photo-filter" onchange="filterTestimonials()">
      <option value="">All Photos</option>
      <option value="with-photo">With Photo</option>
      <option value="no-photo">No Photo</option>
    </select>
  </div>
  <button type="button" class="btn btn--primary" onclick="var f=document.getElementById('create-form'); f.style.display=f.style.display==='none'?'block':'none'">
    <svg viewBox="0 0 24 24" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Testimonial
  </button>
</div>

<!-- Create Form -->
<div id="create-form" style="display: none;" class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      New Testimonial
    </h3>
  </div>
  <div class="admin-card__body">
    <form method="post">
      <input type="hidden" name="action" value="create">
      
      <div class="form-group">
        <label>Quote</label>
        <textarea name="quote" class="form-control form-control--full" rows="5" required placeholder="Client testimonial text"></textarea>
      </div>
      <div class="form-group">
        <label>Author</label>
        <input type="text" name="author" class="form-control" required placeholder="e.g. Opio Tonny">
      </div>
      <div class="form-group">
        <label>Role / title</label>
        <input type="text" name="role" class="form-control" placeholder="e.g. Farmer">
      </div>
      <div class="form-group">
        <label>Image (optional)</label>
        <div class="upload-field">
          <input type="text" id="new_testimonial_image" name="image_path" class="form-control" placeholder="Upload or enter path">
          <button type="button" class="btn btn--secondary btn--sm" onclick="openMediaPicker('new_testimonial_image', 'image')">Select from Media</button>
        </div>
      </div>
      <div class="form-group">
        <label>Sort order</label>
        <input type="number" name="sort_order" class="form-control" value="0" style="max-width: 100px;">
      </div>
      <div class="form-group">
        <label><input type="checkbox" name="is_active" checked> Active (show on site)</label>
      </div>
      
      <button type="submit" class="btn btn--primary">Add testimonial</button>
      <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('create-form').style.display='none'">Cancel</button>
    </form>
  </div>
</div>

<!-- Testimonials Cards Grid -->
<div class="card-grid" id="testimonials-grid">
  <?php foreach ($testimonials as $t): ?>
  <div class="testimonial-card" data-author="<?php echo strtolower(htmlspecialchars($t['author'])); ?>" 
       data-role="<?php echo strtolower(htmlspecialchars($t['role'] ?? '')); ?>"
       data-status="<?php echo $t['is_active'] ? 'active' : 'inactive'; ?>"
       data-has-photo="<?php echo !empty($t['image_path']) ? 'yes' : 'no'; ?>">
    <div class="testimonial-card__header">
      <?php if (!empty($t['image_path'])): ?>
        <img src="<?php echo htmlspecialchars($basePath . '/' . $t['image_path']); ?>" alt="<?php echo htmlspecialchars($t['author']); ?>" class="testimonial-card__photo">
      <?php else: ?>
        <div class="testimonial-card__photo" style="background: var(--primary-light); display: flex; align-items: center; justify-content: center; color: var(--primary);">
          <svg viewBox="0 0 24 24" width="24" height="24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
      <?php endif; ?>
      <div class="testimonial-card__author">
        <div class="testimonial-card__name"><?php echo e($t['author']); ?></div>
        <div class="testimonial-card__role"><?php echo e($t['role']); ?></div>
      </div>
      <div>
        <span class="badge <?php echo $t['is_active'] ? 'badge--success' : 'badge--muted'; ?>">
          <?php echo $t['is_active'] ? 'Active' : 'Inactive'; ?>
        </span>
      </div>
    </div>
    <div class="testimonial-card__quote">
      <?php echo e($t['quote']); ?>
    </div>
    <div class="testimonial-card__actions">
      <button type="button" class="btn btn--primary btn--sm" onclick="document.getElementById('edit-form-<?php echo (int)$t['id']; ?>').style.display='block'">Edit</button>
      <form method="post" style="display:inline;" onsubmit="return confirm('Delete this testimonial?');">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
        <button type="submit" class="btn btn--outline btn--sm">Delete</button>
      </form>
    </div>
  </div>
  
  <!-- Edit Form (Hidden by default) -->
  <div id="edit-form-<?php echo (int)$t['id']; ?>" style="display: none;" class="admin-card testimonial-edit-form">
    <div class="admin-card__header">
      <h3 class="admin-card__title">Edit Testimonial</h3>
    </div>
    <div class="admin-card__body">
      <form method="post">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
        
        <div class="form-group">
          <label>Quote</label>
          <textarea name="quote" class="form-control form-control--full" rows="5" required><?php echo htmlspecialchars($t['quote']); ?></textarea>
        </div>
        <div class="form-group">
          <label>Author</label>
          <input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($t['author']); ?>" required>
        </div>
        <div class="form-group">
          <label>Role / title</label>
          <input type="text" name="role" class="form-control" value="<?php echo htmlspecialchars($t['role'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label>Image (optional)</label>
          <div class="upload-field">
            <input type="text" id="testimonial_image_<?php echo (int)$t['id']; ?>" name="image_path" class="form-control" value="<?php echo htmlspecialchars($t['image_path'] ?? ''); ?>" placeholder="Upload or enter path">
            <button type="button" class="btn btn--secondary btn--sm" onclick="openMediaPicker('testimonial_image_<?php echo (int)$t['id']; ?>', 'image')">Select from Media</button>
          </div>
          <?php if (!empty($t['image_path'])): ?>
            <div class="upload-field-preview">
              <img src="<?php echo htmlspecialchars($basePath . '/' . $t['image_path']); ?>" alt="" style="max-height: 80px; border-radius: var(--radius-md);">
            </div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label>Sort order</label>
          <input type="number" name="sort_order" class="form-control" value="<?php echo (int)$t['sort_order']; ?>" style="max-width: 100px;">
        </div>
        <div class="form-group">
          <label><input type="checkbox" name="is_active" <?php echo $t['is_active'] ? 'checked' : ''; ?>> Active (show on site)</label>
        </div>
        
        <button type="submit" class="btn btn--primary">Save</button>
        <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('edit-form-<?php echo (int)$t['id']; ?>').style.display='none'">Cancel</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<style>
.testimonial-edit-form {
  grid-column: 1 / -1;
}
</style>

<script>
function filterTestimonials() {
  const searchValue = document.getElementById('testimonial-search').value.toLowerCase();
  const statusFilter = document.getElementById('status-filter').value;
  const photoFilter = document.getElementById('photo-filter').value;
  const cards = document.querySelectorAll('.testimonial-card');
  const forms = document.querySelectorAll('.testimonial-edit-form');
  
  cards.forEach(function(card) {
    const author = card.dataset.author;
    const role = card.dataset.role;
    const status = card.dataset.status;
    const hasPhoto = card.dataset.hasPhoto;
    
    let matchesSearch = author.includes(searchValue) || role.includes(searchValue);
    let matchesStatus = !statusFilter || status === statusFilter;
    let matchesPhoto = true;
    
    if (photoFilter === 'with-photo') {
      matchesPhoto = hasPhoto === 'yes';
    } else if (photoFilter === 'no-photo') {
      matchesPhoto = hasPhoto === 'no';
    }
    
    if (matchesSearch && matchesStatus && matchesPhoto) {
      card.style.display = '';
      // Show corresponding edit form
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
