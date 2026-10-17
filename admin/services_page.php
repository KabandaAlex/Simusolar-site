<?php
/**
 * Admin - Services Page content & sections
 * Hero, intro, headings, CTA plus process steps and why-choose-us benefits.
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$pdo = getDb();
$currentNav = 'services-page';
$pageTitle = 'Services Page';

$message = '';
$messageType = 'success';

$contentKeys = [
    'hero_heading', 'hero_subtitle', 'hero_primary_text', 'hero_primary_url', 'hero_secondary_text', 'hero_secondary_url',
    'intro_text', 'services_heading', 'services_subtitle',
    'process_heading', 'process_subtitle',
    'why_heading', 'why_subtitle',
    'cta_heading', 'cta_subtitle', 'cta_primary_text', 'cta_primary_url', 'cta_secondary_text', 'cta_secondary_url',
];

$content = array_fill_keys($contentKeys, '');
try {
    $stmt = $pdo->query("SELECT `key`, value FROM services_page_content");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($content[$row['key']])) {
            $content[$row['key']] = $row['value'] ?? '';
        }
    }
} catch (Throwable $e) { }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_content') {
        try {
            $stmt = $pdo->prepare('INSERT INTO services_page_content (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
            foreach ($contentKeys as $k) {
                $v = trim($_POST['content_' . $k] ?? '');
                $stmt->execute([$k, $v]);
            }
            $message = 'Services page content saved.';
            $messageType = 'success';
            logAdminActivity('Updated services page content');
            $stmt = $pdo->query("SELECT `key`, value FROM services_page_content");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (isset($content[$row['key']])) $content[$row['key']] = $row['value'] ?? '';
            }
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }

    if ($action === 'save_step') {
        $id = (int)($_POST['step_id'] ?? 0);
        $num = (int)($_POST['step_number'] ?? 1);
        $title = trim($_POST['step_title'] ?? '');
        $desc = trim($_POST['step_description'] ?? '');
        $sort = (int)($_POST['step_sort_order'] ?? 0);
        try {
            if ($id) {
                $pdo->prepare('UPDATE services_page_steps SET step_number = ?, title = ?, description = ?, sort_order = ? WHERE id = ?')
                    ->execute([$num, $title, $desc, $sort, $id]);
                $message = 'Process step updated.';
            } else {
                $pdo->prepare('INSERT INTO services_page_steps (step_number, title, description, sort_order, is_active) VALUES (?, ?, ?, ?, 1)')
                    ->execute([$num, $title, $desc, $sort]);
                $message = 'Process step added.';
            }
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    if ($action === 'delete_step' && !empty($_POST['step_id'])) {
        try {
            $pdo->prepare('DELETE FROM services_page_steps WHERE id = ?')->execute([(int)$_POST['step_id']]);
            $message = 'Process step deleted.';
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }

    if ($action === 'save_benefit') {
        $id = (int)($_POST['benefit_id'] ?? 0);
        $title = trim($_POST['benefit_title'] ?? '');
        $desc = trim($_POST['benefit_description'] ?? '');
        $icon = trim($_POST['benefit_icon_class'] ?? '');
        $sort = (int)($_POST['benefit_sort_order'] ?? 0);
        try {
            if ($id) {
                $pdo->prepare('UPDATE services_page_benefits SET title = ?, description = ?, icon_class = ?, sort_order = ? WHERE id = ?')
                    ->execute([$title, $desc, $icon !== '' ? $icon : null, $sort, $id]);
                $message = 'Benefit updated.';
            } else {
                $pdo->prepare('INSERT INTO services_page_benefits (title, description, icon_class, sort_order, is_active) VALUES (?, ?, ?, ?, 1)')
                    ->execute([$title, $desc, $icon !== '' ? $icon : null, $sort]);
                $message = 'Benefit added.';
            }
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    if ($action === 'delete_benefit' && !empty($_POST['benefit_id'])) {
        try {
            $pdo->prepare('DELETE FROM services_page_benefits WHERE id = ?')->execute([(int)$_POST['benefit_id']]);
            $message = 'Benefit deleted.';
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$steps = [];
$benefits = [];
try { $steps = $pdo->query('SELECT * FROM services_page_steps ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) { }
try { $benefits = $pdo->query('SELECT * FROM services_page_benefits ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) { }

$adminBase = rtrim(BASE_PATH, '/') . '/admin';
$basePath = BASE_PATH;

include __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
  <?php if ($message): ?>
    <div class="admin-alert admin-alert--<?php echo $messageType; ?> alert-dismissible">
      <?php echo htmlspecialchars($message); ?>
      <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
  <?php endif; ?>

  <div class="dashboard-hero">
    <div class="dashboard-hero__content">
      <div class="dashboard-hero__text">
        <div class="dashboard-hero__greeting">Services</div>
        <h1 class="dashboard-hero__title">Services Page</h1>
        <p class="dashboard-hero__subtitle">Edit hero, intro and section headings for the Services page, and manage the service process steps and \"Why choose us\" benefits.</p>
      </div>
      <div class="dashboard-hero__actions">
        <a href="<?php echo $basePath; ?>/services" target="_blank" rel="noopener" class="dashboard-hero__btn">View page</a>
      </div>
    </div>
  </div>

  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <h3 class="admin-card__title">Page content</h3>
      <p class="admin-card__subtitle">Hero text, intro, section headings and CTA settings.</p>
    </div>
    <div class="admin-card__body">
      <form method="post">
        <input type="hidden" name="action" value="save_content">
        <h4 style="margin:1rem 0 0.5rem;">Hero</h4>
        <div class="form-group">
          <label>Hero heading</label>
          <input type="text" name="content_hero_heading" class="form-control" value="<?php echo htmlspecialchars($content['hero_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Hero subtitle</label>
          <textarea name="content_hero_subtitle" class="form-control form-control--textarea" rows="2"><?php echo htmlspecialchars($content['hero_subtitle']); ?></textarea>
        </div>
        <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group">
            <label>Primary CTA text</label>
            <input type="text" name="content_hero_primary_text" class="form-control" value="<?php echo htmlspecialchars($content['hero_primary_text']); ?>">
          </div>
          <div class="form-group">
            <label>Primary CTA URL</label>
            <input type="text" name="content_hero_primary_url" class="form-control" value="<?php echo htmlspecialchars($content['hero_primary_url']); ?>">
          </div>
          <div class="form-group">
            <label>Secondary CTA text</label>
            <input type="text" name="content_hero_secondary_text" class="form-control" value="<?php echo htmlspecialchars($content['hero_secondary_text']); ?>">
          </div>
          <div class="form-group">
            <label>Secondary CTA URL</label>
            <input type="text" name="content_hero_secondary_url" class="form-control" value="<?php echo htmlspecialchars($content['hero_secondary_url']); ?>">
          </div>
        </div>

        <hr style="margin:1.5rem 0;">
        <h4 style="margin:1rem 0 0.5rem;">Intro</h4>
        <div class="form-group">
          <label>Intro text</label>
          <textarea name="content_intro_text" class="form-control form-control--textarea" rows="3"><?php echo htmlspecialchars($content['intro_text']); ?></textarea>
        </div>

        <hr style="margin:1.5rem 0;">
        <h4 style="margin:1rem 0 0.5rem;">Services section header</h4>
        <div class="form-group">
          <label>Services heading</label>
          <input type="text" name="content_services_heading" class="form-control" value="<?php echo htmlspecialchars($content['services_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Services subtitle</label>
          <input type="text" name="content_services_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['services_subtitle']); ?>">
        </div>

        <hr style="margin:1.5rem 0;">
        <h4 style="margin:1rem 0 0.5rem;">Process section header</h4>
        <div class="form-group">
          <label>Process heading</label>
          <input type="text" name="content_process_heading" class="form-control" value="<?php echo htmlspecialchars($content['process_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Process subtitle</label>
          <input type="text" name="content_process_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['process_subtitle']); ?>">
        </div>

        <hr style="margin:1.5rem 0;">
        <h4 style="margin:1rem 0 0.5rem;">Why Choose Us header</h4>
        <div class="form-group">
          <label>Why Choose Us heading</label>
          <input type="text" name="content_why_heading" class="form-control" value="<?php echo htmlspecialchars($content['why_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Why Choose Us subtitle</label>
          <input type="text" name="content_why_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['why_subtitle']); ?>">
        </div>

        <hr style="margin:1.5rem 0;">
        <h4 style="margin:1rem 0 0.5rem;">Final CTA</h4>
        <div class="form-group">
          <label>CTA heading</label>
          <input type="text" name="content_cta_heading" class="form-control" value="<?php echo htmlspecialchars($content['cta_heading']); ?>">
        </div>
        <div class="form-group">
          <label>CTA subtitle</label>
          <textarea name="content_cta_subtitle" class="form-control form-control--textarea" rows="2"><?php echo htmlspecialchars($content['cta_subtitle']); ?></textarea>
        </div>
        <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group">
            <label>CTA primary text</label>
            <input type="text" name="content_cta_primary_text" class="form-control" value="<?php echo htmlspecialchars($content['cta_primary_text']); ?>">
          </div>
          <div class="form-group">
            <label>CTA primary URL</label>
            <input type="text" name="content_cta_primary_url" class="form-control" value="<?php echo htmlspecialchars($content['cta_primary_url']); ?>">
          </div>
          <div class="form-group">
            <label>CTA secondary text</label>
            <input type="text" name="content_cta_secondary_text" class="form-control" value="<?php echo htmlspecialchars($content['cta_secondary_text']); ?>">
          </div>
          <div class="form-group">
            <label>CTA secondary URL</label>
            <input type="text" name="content_cta_secondary_url" class="form-control" value="<?php echo htmlspecialchars($content['cta_secondary_url']); ?>">
          </div>
        </div>

        <div class="form-actions" style="margin-top:1.5rem;">
          <button type="submit" class="btn btn--primary">Save page content</button>
          <a href="<?php echo $basePath; ?>/services" target="_blank" rel="noopener" class="btn btn--secondary">View Services page</a>
        </div>
      </form>
    </div>
  </div>

  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <h3 class="admin-card__title">Process steps</h3>
      <p class="admin-card__subtitle">Steps shown in the \"Professional Service Process\" section.</p>
    </div>
    <div class="admin-card__body">
      <table class="admin-table" style="width:100%; margin-bottom:1rem;">
        <thead>
          <tr><th>#</th><th>Title</th><th>Description</th><th>Order</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($steps as $s): ?>
            <tr>
              <td><?php echo (int)$s['step_number']; ?></td>
              <td><?php echo htmlspecialchars($s['title']); ?></td>
              <td><?php echo htmlspecialchars(mb_substr($s['description'], 0, 80)) . (mb_strlen($s['description']) > 80 ? '…' : ''); ?></td>
              <td><?php echo (int)$s['sort_order']; ?></td>
              <td>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this step?');">
                  <input type="hidden" name="action" value="delete_step">
                  <input type="hidden" name="step_id" value="<?php echo (int)$s['id']; ?>">
                  <button type="submit" class="btn btn--sm" style="color:var(--color-error);">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <form method="post">
        <input type="hidden" name="action" value="save_step">
        <input type="hidden" name="step_id" value="">
        <div class="form-grid" style="display:grid;grid-template-columns:80px 1.5fr 3fr 80px;gap:1rem;align-items:end;">
          <div class="form-group">
            <label>#</label>
            <input type="number" name="step_number" class="form-control" value="1">
          </div>
          <div class="form-group">
            <label>Title</label>
            <input type="text" name="step_title" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <input type="text" name="step_description" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Sort</label>
            <input type="number" name="step_sort_order" class="form-control" value="0">
          </div>
        </div>
        <button type="submit" class="btn btn--primary" style="margin-top:0.5rem;">Add step</button>
      </form>
    </div>
  </div>

  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <h3 class="admin-card__title">Why choose us benefits</h3>
      <p class="admin-card__subtitle">Benefits shown in the \"Why Choose Our Professional Services?\" section.</p>
    </div>
    <div class="admin-card__body">
      <table class="admin-table" style="width:100%; margin-bottom:1rem;">
        <thead>
          <tr><th>Title</th><th>Description</th><th>Icon class</th><th>Order</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($benefits as $b): ?>
            <tr>
              <td><?php echo htmlspecialchars($b['title']); ?></td>
              <td><?php echo htmlspecialchars(mb_substr($b['description'], 0, 80)) . (mb_strlen($b['description']) > 80 ? '…' : ''); ?></td>
              <td><?php echo htmlspecialchars($b['icon_class']); ?></td>
              <td><?php echo (int)$b['sort_order']; ?></td>
              <td>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this benefit?');">
                  <input type="hidden" name="action" value="delete_benefit">
                  <input type="hidden" name="benefit_id" value="<?php echo (int)$b['id']; ?>">
                  <button type="submit" class="btn btn--sm" style="color:var(--color-error);">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <form method="post">
        <input type="hidden" name="action" value="save_benefit">
        <input type="hidden" name="benefit_id" value="">
        <div class="form-grid" style="display:grid;grid-template-columns:1.5fr 3fr 1.5fr 80px;gap:1rem;align-items:end;">
          <div class="form-group">
            <label>Title</label>
            <input type="text" name="benefit_title" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <input type="text" name="benefit_description" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Icon class (Font Awesome)</label>
            <input type="text" name="benefit_icon_class" class="form-control" placeholder="e.g. fas fa-award">
          </div>
          <div class="form-group">
            <label>Sort</label>
            <input type="number" name="benefit_sort_order" class="form-control" value="0">
          </div>
        </div>
        <button type="submit" class="btn btn--primary" style="margin-top:0.5rem;">Add benefit</button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

