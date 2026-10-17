<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
$pdo = getDb();
$currentNav = 'sections';
$pageTitle = 'Section items';

$sectionId = isset($_GET['section_id']) ? (int)$_GET['section_id'] : (int)($_POST['section_id'] ?? 0);
if ($sectionId <= 0) {
    header('Location: sections.php');
    exit;
}
$stmt = $pdo->prepare('SELECT s.*, p.title AS page_title, p.slug AS page_slug FROM page_sections s JOIN pages p ON p.id = s.page_id WHERE s.id = ?');
$stmt->execute([$sectionId]);
$section = $stmt->fetch();
if (!$section) {
    header('Location: sections.php');
    exit;
}
$sectionKeysWithItems = ['how_it_works', 'transform_farm', 'impact_numbers', 'solar_solutions'];
if (!in_array($section['section_key'], $sectionKeysWithItems, true)) {
    header('Location: sections.php?page_id=' . (int)$section['page_id']);
    exit;
}
$isImpact = ($section['section_key'] === 'impact_numbers');
$isTransformFarm = ($section['section_key'] === 'transform_farm');
if ($isImpact) {
    $labelTitle = 'Label';
    $labelContent = 'Value';
    $labelExtra = 'Sublabel';
    $showExtra = true;
} elseif ($isTransformFarm) {
    $labelTitle = 'Title';
    $labelContent = 'Description';
    $labelExtra = 'Bullets (optional)';
    $showExtra = true;
} else {
    $labelTitle = 'Title';
    $labelContent = 'Content (subtitle / description)';
    $labelExtra = 'Bullets (one per line)';
    $showExtra = true;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmtMax = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM page_section_items WHERE section_id = ?');
        $stmtMax->execute([$sectionId]);
        $nextOrder = (int)$stmtMax->fetchColumn() + 10;
        $stmtInsert = $pdo->prepare('INSERT INTO page_section_items (section_id, sort_order, title, content, content_extra) VALUES (?, ?, ?, ?, ?)');
        $stmtInsert->execute([
            $sectionId,
            $nextOrder,
            trim($_POST['title'] ?? ''),
            trim($_POST['content'] ?? ''),
            trim($_POST['content_extra'] ?? '')
        ]);
        $message = 'Item added.';
    } elseif ($action === 'update' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare('UPDATE page_section_items SET sort_order = ?, title = ?, content = ?, content_extra = ? WHERE id = ? AND section_id = ?');
        $stmt->execute([
            (int)($_POST['sort_order'] ?? 0),
            trim($_POST['title'] ?? ''),
            trim($_POST['content'] ?? ''),
            trim($_POST['content_extra'] ?? ''),
            (int)$_POST['id'],
            $sectionId
        ]);
        $message = 'Item updated.';
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare('DELETE FROM page_section_items WHERE id = ? AND section_id = ?');
        $stmt->execute([(int)$_POST['id'], $sectionId]);
        $message = 'Item deleted.';
    }
}

$items = getSectionItems($pdo, $sectionId);
require_once __DIR__ . '/../config/path.php';
$basePath = BASE_PATH;
include __DIR__ . '/includes/header.php';
?>

<div class="admin-page-intro">
  <div class="admin-page-intro__content">
    <h1>Section items: <?php echo e($section['title'] ?? $section['section_key']); ?></h1>
    <p class="admin-page-intro__subtitle">Edit each step/card separately. <a href="<?php echo $basePath; ?>/admin/sections.php?page_id=<?php echo (int)$section['page_id']; ?>">← Back to Sections</a></p>
  </div>
</div>

<?php if ($message): ?>
  <div class="admin-alert admin-alert--success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="admin-card">
  <div class="admin-card__header">
    <span class="card-header-icon" aria-hidden="true">+</span>
    Add new item
  </div>
  <div class="admin-card__body">
    <form method="post">
      <input type="hidden" name="section_id" value="<?php echo (int)$sectionId; ?>">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label><?php echo e($labelTitle); ?></label>
        <input type="text" name="title" class="form-control" required placeholder="<?php echo $isImpact ? 'e.g. Farmers Empowered' : 'e.g. Free Consultation'; ?>">
      </div>
      <div class="form-group">
        <label><?php echo e($labelContent); ?></label>
        <?php if ($isImpact): ?>
        <input type="text" name="content" class="form-control" placeholder="e.g. 0 or 0%">
        <?php else: ?>
        <textarea name="content" class="form-control form-control--full" rows="3" placeholder="Short description"></textarea>
        <?php endif; ?>
      </div>
      <?php if ($showExtra): ?>
      <div class="form-group">
        <label><?php echo e($labelExtra); ?></label>
        <?php if ($isImpact): ?>
        <input type="text" name="content_extra" class="form-control" placeholder="e.g. Lives transformed through solar power">
        <?php else: ?>
        <textarea name="content_extra" class="form-control form-control--full" rows="4" placeholder="One per line"></textarea>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <button type="submit" class="btn btn--primary">Add item</button>
    </form>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card__header">
    <span class="card-header-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></span>
    Items (<?php echo count($items); ?>)
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th><?php echo e($labelTitle); ?></th>
          <th><?php echo e($labelContent); ?></th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i => $item): ?>
        <tr>
          <td><?php echo (int)$item['sort_order']; ?></td>
          <td><?php echo e($item['title']); ?></td>
          <td><?php echo e(mb_substr($item['content'] ?? '', 0, 60)); ?><?php echo mb_strlen($item['content'] ?? '') > 60 ? '…' : ''; ?></td>
          <td>
            <button type="button" class="btn btn--primary btn--sm" data-toggle-row="<?php echo (int)$item['id']; ?>">Edit</button>
            <form method="post" style="display: inline;" onsubmit="return confirm('Delete this item?');">
              <input type="hidden" name="section_id" value="<?php echo (int)$sectionId; ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
              <button type="submit" class="btn btn--outline btn--sm">Delete</button>
            </form>
          </td>
        </tr>
        <tr id="row-form-<?php echo (int)$item['id']; ?>" class="expand-row" style="display: none;">
          <td colspan="4">
            <form method="post" style="max-width: 640px;">
              <input type="hidden" name="section_id" value="<?php echo (int)$sectionId; ?>">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
              <div class="form-group">
                <label>Sort order</label>
                <input type="number" name="sort_order" class="form-control" value="<?php echo (int)$item['sort_order']; ?>" style="max-width: 100px;">
              </div>
              <div class="form-group">
                <label><?php echo e($labelTitle); ?></label>
                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($item['title'] ?? ''); ?>">
              </div>
              <div class="form-group">
                <label><?php echo e($labelContent); ?></label>
                <?php if ($isImpact): ?>
                <input type="text" name="content" class="form-control" value="<?php echo htmlspecialchars($item['content'] ?? ''); ?>">
                <?php else: ?>
                <textarea name="content" class="form-control form-control--full" rows="3"><?php echo htmlspecialchars($item['content'] ?? ''); ?></textarea>
                <?php endif; ?>
              </div>
              <?php if ($showExtra): ?>
              <div class="form-group">
                <label><?php echo e($labelExtra); ?></label>
                <?php if ($isImpact): ?>
                <input type="text" name="content_extra" class="form-control" value="<?php echo htmlspecialchars($item['content_extra'] ?? ''); ?>">
                <?php else: ?>
                <textarea name="content_extra" class="form-control form-control--full" rows="4"><?php echo htmlspecialchars($item['content_extra'] ?? ''); ?></textarea>
                <?php endif; ?>
              </div>
              <?php endif; ?>
              <div style="margin-top: 1rem;">
                <button type="submit" class="btn btn--primary">Save</button>
                <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('row-form-<?php echo (int)$item['id']; ?>').style.display='none'">Cancel</button>
              </div>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.querySelectorAll('[data-toggle-row]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var id = this.getAttribute('data-toggle-row');
    var row = document.getElementById('row-form-' + id);
    if (row) row.style.display = row.style.display === 'none' ? '' : 'none';
  });
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
