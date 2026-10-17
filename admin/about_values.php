<?php
/**
 * Admin - About page "Our Values" (about_values table)
 * Add, edit, delete value items shown in the Values section.
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$pdo = getDb();
$currentNav = 'about-values';
$pageTitle = 'Our Values';

$message = '';
$messageType = 'success';
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// Handle save (add or update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $value_id = (int)($_POST['value_id'] ?? 0);

    if ($action === 'save' && $title !== '') {
        try {
            if ($value_id) {
                $pdo->prepare('UPDATE about_values SET title = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?')
                    ->execute([$title, $description, $sort_order, $is_active, $value_id]);
                $message = 'Value updated.';
                logAdminActivity('Updated about value: ' . $title);
            } else {
                $pdo->prepare('INSERT INTO about_values (title, description, sort_order, is_active) VALUES (?, ?, ?, ?)')
                    ->execute([$title, $description, $sort_order, $is_active]);
                $message = 'Value added.';
                logAdminActivity('Added about value: ' . $title);
            }
            $messageType = 'success';
            $editId = 0;
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    if ($action === 'delete' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        try {
            $pdo->prepare('DELETE FROM about_values WHERE id = ?')->execute([$id]);
            $message = 'Value deleted.';
            $messageType = 'success';
            $editId = 0;
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$values = [];
try {
    $values = $pdo->query('SELECT * FROM about_values ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { }

$editing = null;
if ($editId) {
    foreach ($values as $v) {
        if ((int)$v['id'] === $editId) {
            $editing = $v;
            break;
        }
    }
}

$adminBase = rtrim(BASE_PATH, '/') . '/admin';
$basePath = BASE_PATH;
include __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
  <?php if ($message): ?>
    <div class="admin-alert admin-alert--<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <div class="dashboard-hero">
    <div class="dashboard-hero__content">
      <div class="dashboard-hero__text">
        <div class="dashboard-hero__greeting">About page</div>
        <h1 class="dashboard-hero__title">Our Values</h1>
        <p class="dashboard-hero__subtitle">Manage the value items shown in the "Our Values" section on the Who We Are page. Order by sort order.</p>
      </div>
      <div class="dashboard-hero__actions">
        <a href="<?php echo $adminBase; ?>/about_page.php" class="dashboard-hero__btn">About page content</a>
        <a href="<?php echo $basePath; ?>/who-we-are" target="_blank" rel="noopener" class="dashboard-hero__btn">View About page</a>
      </div>
    </div>
  </div>

  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <h3 class="admin-card__title">Add or edit value</h3>
      <p class="admin-card__subtitle">Title and description for each value card.</p>
    </div>
    <div class="admin-card__body">
      <form method="post">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="value_id" value="<?php echo $editing ? (int)$editing['id'] : 0; ?>">
        <div class="form-grid" style="display:grid;grid-template-columns:1fr 2fr auto auto;gap:1rem;align-items:end;">
          <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" class="form-control" value="<?php echo $editing ? htmlspecialchars($editing['title']) : ''; ?>" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" class="form-control" value="<?php echo $editing ? htmlspecialchars($editing['description']) : ''; ?>" required>
          </div>
          <div class="form-group">
            <label>Sort order</label>
            <input type="number" name="sort_order" class="form-control" value="<?php echo $editing ? (int)$editing['sort_order'] : 0; ?>" style="width:80px;">
          </div>
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:0.5rem;">
              <input type="checkbox" name="is_active" value="1" <?php echo (!$editing || $editing['is_active']) ? 'checked' : ''; ?>>
              Active
            </label>
          </div>
        </div>
        <button type="submit" class="btn btn--primary" style="margin-top:0.5rem;"><?php echo $editing ? 'Update value' : 'Add value'; ?></button>
        <?php if ($editing): ?>
          <a href="<?php echo $adminBase; ?>/about_values.php" class="btn btn--secondary" style="margin-left:0.5rem;">Cancel</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <h3 class="admin-card__title">Current values</h3>
      <p class="admin-card__subtitle">Only active items are shown on the About page.</p>
    </div>
    <div class="admin-card__body">
      <?php if (empty($values)): ?>
        <p>No values yet. Add one above.</p>
      <?php else: ?>
      <table class="admin-table" style="width:100%;">
        <thead>
          <tr>
            <th>Order</th>
            <th>Title</th>
            <th>Description</th>
            <th>Active</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($values as $v): ?>
            <tr>
              <td><?php echo (int)$v['sort_order']; ?></td>
              <td><?php echo htmlspecialchars($v['title']); ?></td>
              <td><?php echo htmlspecialchars(mb_substr($v['description'], 0, 60)) . (mb_strlen($v['description']) > 60 ? '…' : ''); ?></td>
              <td><?php echo $v['is_active'] ? 'Yes' : 'No'; ?></td>
              <td>
                <a href="<?php echo $adminBase; ?>/about_values.php?edit=<?php echo (int)$v['id']; ?>" class="btn btn--sm">Edit</a>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this value?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                  <button type="submit" class="btn btn--sm" style="color:var(--color-error);">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
