<?php
/**
 * Admin - Impact Page content (hero, sections, environmental, journey, community)
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$pdo = getDb();
$currentNav = 'impact-page';
$pageTitle = 'Impact Page';

$message = '';
$messageType = 'success';

$contentKeys = [
    'hero_heading', 'hero_subtitle', 'hero_cta_text', 'hero_cta_url', 'hero_cta_secondary_text', 'hero_cta_secondary_url',
    'stats_heading', 'stats_subtitle', 'testimonials_heading', 'testimonials_subtitle',
    'join_heading', 'join_subtitle', 'join_primary_text', 'join_primary_url', 'join_secondary_text', 'join_secondary_url',
    'environmental_heading', 'environmental_subtitle', 'offices_heading', 'offices_subtitle', 'offices_intro',
    'community_heading', 'community_subtitle', 'economic_benefits', 'social_benefits',
    'journey_heading', 'journey_subtitle', 'cta_heading', 'cta_subtitle', 'cta_primary_text', 'cta_primary_url', 'cta_secondary_text', 'cta_secondary_url',
];

$content = array_fill_keys($contentKeys, '');
try {
    $stmt = $pdo->query("SELECT `key`, value FROM impact_page_content");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($content[$row['key']])) {
            $content[$row['key']] = $row['value'] ?? '';
        }
    }
} catch (Throwable $e) { }

// Save page content
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_content') {
        try {
            $stmt = $pdo->prepare('INSERT INTO impact_page_content (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
            foreach ($contentKeys as $k) {
                $v = trim($_POST['content_' . $k] ?? '');
                $stmt->execute([$k, $v]);
            }
            $message = 'Impact page content saved.';
            $messageType = 'success';
            logAdminActivity('Updated impact page content');
            $stmt = $pdo->query("SELECT `key`, value FROM impact_page_content");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (isset($content[$row['key']])) $content[$row['key']] = $row['value'] ?? '';
            }
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    if ($action === 'save_env') {
        $id = (int)($_POST['env_id'] ?? 0);
        $title = trim($_POST['env_title'] ?? '');
        $desc = trim($_POST['env_description'] ?? '');
        $order = (int)($_POST['env_sort_order'] ?? 0);
        try {
            if ($id) {
                $pdo->prepare('UPDATE impact_environmental SET title = ?, description = ?, sort_order = ? WHERE id = ?')->execute([$title, $desc, $order, $id]);
                $message = 'Environmental item updated.';
            } else {
                $pdo->prepare('INSERT INTO impact_environmental (title, description, sort_order, is_active) VALUES (?, ?, ?, 1)')->execute([$title, $desc, $order]);
                $message = 'Environmental item added.';
            }
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    if ($action === 'delete_env' && !empty($_POST['env_id'])) {
        try {
            $pdo->prepare('DELETE FROM impact_environmental WHERE id = ?')->execute([(int)$_POST['env_id']]);
            $message = 'Environmental item deleted.';
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    if ($action === 'save_journey') {
        $id = (int)($_POST['journey_id'] ?? 0);
        $year = trim($_POST['journey_year'] ?? '');
        $title = trim($_POST['journey_title'] ?? '');
        $desc = trim($_POST['journey_description'] ?? '');
        $order = (int)($_POST['journey_sort_order'] ?? 0);
        try {
            if ($id) {
                $pdo->prepare('UPDATE impact_journey SET year = ?, title = ?, description = ?, sort_order = ? WHERE id = ?')->execute([$year, $title, $desc, $order, $id]);
                $message = 'Journey item updated.';
            } else {
                $pdo->prepare('INSERT INTO impact_journey (year, title, description, sort_order, is_active) VALUES (?, ?, ?, ?, 1)')->execute([$year, $title, $desc, $order]);
                $message = 'Journey item added.';
            }
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    if ($action === 'delete_journey' && !empty($_POST['journey_id'])) {
        try {
            $pdo->prepare('DELETE FROM impact_journey WHERE id = ?')->execute([(int)$_POST['journey_id']]);
            $message = 'Journey item deleted.';
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$environmental = [];
$journey = [];
try {
    $environmental = $pdo->query("SELECT * FROM impact_environmental ORDER BY sort_order, id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { }
try {
    $journey = $pdo->query("SELECT * FROM impact_journey ORDER BY sort_order, id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { }

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
        <div class="dashboard-hero__greeting">Impact page</div>
        <h1 class="dashboard-hero__title">Impact Page Content</h1>
        <p class="dashboard-hero__subtitle">Edit hero, section titles, environmental impact, journey timeline, and community bullets. Stats: <a href="<?php echo $adminBase; ?>/impact_numbers.php">Impact Numbers</a>. Testimonials: manage via site testimonials.</p>
      </div>
    </div>
  </div>

  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <h3 class="admin-card__title">Page copy &amp; CTAs</h3>
      <p class="admin-card__subtitle">Headings, subtitles, and button labels/URLs for the public Impact page.</p>
    </div>
    <div class="admin-card__body">
      <form method="post">
        <input type="hidden" name="action" value="save_content">
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
            <label>Hero CTA text</label>
            <input type="text" name="content_hero_cta_text" class="form-control" value="<?php echo htmlspecialchars($content['hero_cta_text']); ?>">
          </div>
          <div class="form-group">
            <label>Hero CTA URL (e.g. #impact-numbers)</label>
            <input type="text" name="content_hero_cta_url" class="form-control" value="<?php echo htmlspecialchars($content['hero_cta_url']); ?>">
          </div>
          <div class="form-group">
            <label>Hero CTA secondary text</label>
            <input type="text" name="content_hero_cta_secondary_text" class="form-control" value="<?php echo htmlspecialchars($content['hero_cta_secondary_text']); ?>">
          </div>
          <div class="form-group">
            <label>Hero CTA secondary URL</label>
            <input type="text" name="content_hero_cta_secondary_url" class="form-control" value="<?php echo htmlspecialchars($content['hero_cta_secondary_url']); ?>">
          </div>
        </div>
        <hr style="margin:1.5rem 0;">
        <div class="form-group">
          <label>Stats section heading</label>
          <input type="text" name="content_stats_heading" class="form-control" value="<?php echo htmlspecialchars($content['stats_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Stats section subtitle</label>
          <input type="text" name="content_stats_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['stats_subtitle']); ?>">
        </div>
        <div class="form-group">
          <label>Testimonials section heading</label>
          <input type="text" name="content_testimonials_heading" class="form-control" value="<?php echo htmlspecialchars($content['testimonials_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Testimonials section subtitle</label>
          <input type="text" name="content_testimonials_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['testimonials_subtitle']); ?>">
        </div>
        <div class="form-group">
          <label>Join CTA heading</label>
          <input type="text" name="content_join_heading" class="form-control" value="<?php echo htmlspecialchars($content['join_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Join CTA subtitle</label>
          <input type="text" name="content_join_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['join_subtitle']); ?>">
        </div>
        <div class="form-group">
          <label>Join primary button text</label>
          <input type="text" name="content_join_primary_text" class="form-control" value="<?php echo htmlspecialchars($content['join_primary_text']); ?>">
        </div>
        <div class="form-group">
          <label>Join primary button URL</label>
          <input type="text" name="content_join_primary_url" class="form-control" value="<?php echo htmlspecialchars($content['join_primary_url']); ?>">
        </div>
        <div class="form-group">
          <label>Join secondary button text</label>
          <input type="text" name="content_join_secondary_text" class="form-control" value="<?php echo htmlspecialchars($content['join_secondary_text']); ?>">
        </div>
        <div class="form-group">
          <label>Join secondary button URL</label>
          <input type="text" name="content_join_secondary_url" class="form-control" value="<?php echo htmlspecialchars($content['join_secondary_url']); ?>">
        </div>
        <hr style="margin:1.5rem 0;">
        <div class="form-group">
          <label>Environmental section heading</label>
          <input type="text" name="content_environmental_heading" class="form-control" value="<?php echo htmlspecialchars($content['environmental_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Environmental section subtitle</label>
          <input type="text" name="content_environmental_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['environmental_subtitle']); ?>">
        </div>
        <div class="form-group">
          <label>Offices section heading</label>
          <input type="text" name="content_offices_heading" class="form-control" value="<?php echo htmlspecialchars($content['offices_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Offices section subtitle</label>
          <input type="text" name="content_offices_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['offices_subtitle']); ?>">
        </div>
        <div class="form-group">
          <label>Offices section intro / impact write-up</label>
          <textarea name="content_offices_intro" class="form-control form-control--textarea" rows="4"><?php echo htmlspecialchars($content['offices_intro']); ?></textarea>
        </div>
        <div class="form-group">
          <label>Community section heading</label>
          <input type="text" name="content_community_heading" class="form-control" value="<?php echo htmlspecialchars($content['community_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Community section subtitle</label>
          <input type="text" name="content_community_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['community_subtitle']); ?>">
        </div>
        <div class="form-group">
          <label>Economic benefits (one per line)</label>
          <textarea name="content_economic_benefits" class="form-control form-control--textarea" rows="5"><?php echo htmlspecialchars($content['economic_benefits']); ?></textarea>
        </div>
        <div class="form-group">
          <label>Social benefits (one per line)</label>
          <textarea name="content_social_benefits" class="form-control form-control--textarea" rows="5"><?php echo htmlspecialchars($content['social_benefits']); ?></textarea>
        </div>
        <div class="form-group">
          <label>Journey section heading</label>
          <input type="text" name="content_journey_heading" class="form-control" value="<?php echo htmlspecialchars($content['journey_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Journey section subtitle</label>
          <input type="text" name="content_journey_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['journey_subtitle']); ?>">
        </div>
        <div class="form-group">
          <label>Final CTA heading</label>
          <input type="text" name="content_cta_heading" class="form-control" value="<?php echo htmlspecialchars($content['cta_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Final CTA subtitle</label>
          <input type="text" name="content_cta_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['cta_subtitle']); ?>">
        </div>
        <div class="form-group">
          <label>Final CTA primary text</label>
          <input type="text" name="content_cta_primary_text" class="form-control" value="<?php echo htmlspecialchars($content['cta_primary_text']); ?>">
        </div>
        <div class="form-group">
          <label>Final CTA primary URL</label>
          <input type="text" name="content_cta_primary_url" class="form-control" value="<?php echo htmlspecialchars($content['cta_primary_url']); ?>">
        </div>
        <div class="form-group">
          <label>Final CTA secondary text</label>
          <input type="text" name="content_cta_secondary_text" class="form-control" value="<?php echo htmlspecialchars($content['cta_secondary_text']); ?>">
        </div>
        <div class="form-group">
          <label>Final CTA secondary URL</label>
          <input type="text" name="content_cta_secondary_url" class="form-control" value="<?php echo htmlspecialchars($content['cta_secondary_url']); ?>">
        </div>
        <div class="form-actions" style="margin-top:1rem;">
          <button type="submit" class="btn btn--primary">Save all page content</button>
          <a href="<?php echo $basePath; ?>/impact" target="_blank" rel="noopener" class="btn btn--secondary">View Impact page</a>
        </div>
      </form>
    </div>
  </div>

  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <h3 class="admin-card__title">Environmental impact items</h3>
      <p class="admin-card__subtitle">Cards shown in the Environmental Impact section (title + description).</p>
    </div>
    <div class="admin-card__body">
      <table class="admin-table" style="width:100%; margin-bottom:1rem;">
        <thead>
          <tr><th>Title</th><th>Description</th><th>Order</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($environmental as $e): ?>
            <tr>
              <td><?php echo htmlspecialchars($e['title']); ?></td>
              <td><?php echo htmlspecialchars(mb_substr($e['description'], 0, 60)) . (mb_strlen($e['description']) > 60 ? '…' : ''); ?></td>
              <td><?php echo (int)$e['sort_order']; ?></td>
              <td>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this item?');">
                  <input type="hidden" name="action" value="delete_env">
                  <input type="hidden" name="env_id" value="<?php echo (int)$e['id']; ?>">
                  <button type="submit" class="btn btn--sm" style="color:var(--color-error);">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <form method="post">
        <input type="hidden" name="action" value="save_env">
        <input type="hidden" name="env_id" value="">
        <div class="form-grid" style="display:grid;grid-template-columns:1fr 2fr auto;gap:1rem;align-items:end;">
          <div class="form-group">
            <label>Title</label>
            <input type="text" name="env_title" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <input type="text" name="env_description" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Sort order</label>
            <input type="number" name="env_sort_order" class="form-control" value="0" style="width:80px;">
          </div>
        </div>
        <button type="submit" class="btn btn--primary" style="margin-top:0.5rem;">Add environmental item</button>
      </form>
    </div>
  </div>

  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <h3 class="admin-card__title">Journey timeline</h3>
      <p class="admin-card__subtitle">Milestones shown in the Our Journey section (year, title, description).</p>
    </div>
    <div class="admin-card__body">
      <table class="admin-table" style="width:100%; margin-bottom:1rem;">
        <thead>
          <tr><th>Year</th><th>Title</th><th>Description</th><th>Order</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($journey as $j): ?>
            <tr>
              <td><?php echo htmlspecialchars($j['year']); ?></td>
              <td><?php echo htmlspecialchars($j['title']); ?></td>
              <td><?php echo htmlspecialchars(mb_substr($j['description'], 0, 50)) . (mb_strlen($j['description']) > 50 ? '…' : ''); ?></td>
              <td><?php echo (int)$j['sort_order']; ?></td>
              <td>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this item?');">
                  <input type="hidden" name="action" value="delete_journey">
                  <input type="hidden" name="journey_id" value="<?php echo (int)$j['id']; ?>">
                  <button type="submit" class="btn btn--sm" style="color:var(--color-error);">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <form method="post">
        <input type="hidden" name="action" value="save_journey">
        <input type="hidden" name="journey_id" value="">
        <div class="form-grid" style="display:grid;grid-template-columns:100px 1fr 2fr auto;gap:1rem;align-items:end;">
          <div class="form-group">
            <label>Year</label>
            <input type="text" name="journey_year" class="form-control" placeholder="e.g. 2024" required>
          </div>
          <div class="form-group">
            <label>Title</label>
            <input type="text" name="journey_title" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <input type="text" name="journey_description" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Sort</label>
            <input type="number" name="journey_sort_order" class="form-control" value="0" style="width:80px;">
          </div>
        </div>
        <button type="submit" class="btn btn--primary" style="margin-top:0.5rem;">Add journey item</button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
