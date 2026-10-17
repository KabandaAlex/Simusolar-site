<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$pdo = getDb();
$currentNav = 'mission-vision';
$pageTitle = 'Mission & Vision';

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $blockKey = trim($_POST['block_key'] ?? '');
    if ($action === 'save' && in_array($blockKey, ['mission', 'vision'], true)) {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        try {
            $stmt = $pdo->prepare('UPDATE about_mission_vision SET title = ?, content = ? WHERE block_key = ?');
            $stmt->execute([$title, $content, $blockKey]);
            if ($stmt->rowCount() > 0) {
                $message = ($blockKey === 'mission' ? 'Mission' : 'Vision') . ' saved successfully.';
                $messageType = 'success';
                logAdminActivity('Updated about ' . $blockKey);
            } else {
                $stmt = $pdo->prepare('INSERT INTO about_mission_vision (block_key, title, content, sort_order) VALUES (?, ?, ?, ?)');
                $stmt->execute([$blockKey, $title, $content, $blockKey === 'mission' ? 1 : 2]);
                $message = ($blockKey === 'mission' ? 'Mission' : 'Vision') . ' created and saved successfully.';
                $messageType = 'success';
                logAdminActivity('Created about ' . $blockKey);
            }
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Load current mission & vision
$mission = ['block_key' => 'mission', 'title' => 'Our mission', 'content' => 'To provide affordable, reliable, and sustainable solar energy solutions to homes and businesses across Africa, empowering communities to thrive with clean energy.'];
$vision = ['block_key' => 'vision', 'title' => 'Our vision', 'content' => 'A future where every African community has access to clean, affordable energy — where sustainable power drives economic growth, education, and quality of life.'];
try {
    $stmt = $pdo->query("SELECT block_key, title, content FROM about_mission_vision WHERE block_key IN ('mission', 'vision') ORDER BY sort_order ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (($row['block_key'] ?? '') === 'mission') {
            $mission = $row;
        } elseif (($row['block_key'] ?? '') === 'vision') {
            $vision = $row;
        }
    }
} catch (Throwable $e) {
    // use defaults
}

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
        <div class="dashboard-hero__greeting">About page</div>
        <h1 class="dashboard-hero__title">Mission & Vision</h1>
        <p class="dashboard-hero__subtitle">Edit the mission and vision text shown on the About page</p>
      </div>
    </div>
  </div>

  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <div class="admin-card__header-left">
        <h3 class="admin-card__title">Mission & Vision content</h3>
        <p class="admin-card__subtitle">These blocks appear in the "Mission & vision" section on the public About page. Plain text only; line breaks are preserved.</p>
        <p class="admin-card__jump">
          <a href="#mission-section" class="admin-card__jump-link">Our Mission</a>
          <span class="admin-card__jump-sep">|</span>
          <a href="#vision-section" class="admin-card__jump-link">Our Vision</a>
        </p>
      </div>
    </div>
    <div class="admin-card__body">
      <div class="mission-vision-forms">
        <!-- Our Mission -->
        <div class="mission-vision-form" id="mission-section">
          <h2 class="mission-vision-form__heading">Our Mission</h2>
          <div class="legal-page-header">
            <div class="legal-page-header__icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            </div>
            <div class="legal-page-header__content">
              <h3 class="legal-page-header__title">Mission</h3>
              <p class="legal-page-header__subtitle">Headline and paragraph shown for "Our mission" on the About page.</p>
            </div>
          </div>
          <form method="post">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="block_key" value="mission">
            <div class="form-group">
              <label for="mission_title">Title</label>
              <input type="text" id="mission_title" name="title" class="form-control" value="<?php echo htmlspecialchars($mission['title'] ?? 'Our mission'); ?>" required>
            </div>
            <div class="form-group">
              <label for="mission_content">Content</label>
              <textarea id="mission_content" name="content" class="form-control form-control--textarea" rows="6" required><?php echo htmlspecialchars($mission['content'] ?? ''); ?></textarea>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn btn--primary">Save Mission</button>
            </div>
          </form>
        </div>

        <!-- Our Vision -->
        <div class="mission-vision-form" id="vision-section">
          <h2 class="mission-vision-form__heading">Our Vision</h2>
          <div class="legal-page-header">
            <div class="legal-page-header__icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </div>
            <div class="legal-page-header__content">
              <h3 class="legal-page-header__title">Vision</h3>
              <p class="legal-page-header__subtitle">Headline and paragraph shown for "Our vision" on the About page.</p>
            </div>
          </div>
          <form method="post">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="block_key" value="vision">
            <div class="form-group">
              <label for="vision_title">Title</label>
              <input type="text" id="vision_title" name="title" class="form-control" value="<?php echo htmlspecialchars($vision['title'] ?? 'Our vision'); ?>" required>
            </div>
            <div class="form-group">
              <label for="vision_content">Content</label>
              <textarea id="vision_content" name="content" class="form-control form-control--textarea" rows="6" required><?php echo htmlspecialchars($vision['content'] ?? ''); ?></textarea>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn btn--primary">Save Vision</button>
            </div>
          </form>
        </div>
      </div>
      <div class="form-actions" style="margin-top: 1.5rem;">
        <a href="<?php echo $basePath; ?>/about" target="_blank" rel="noopener" class="btn btn--secondary">View About page</a>
      </div>
    </div>
  </div>
</div>

<style>
.admin-card__jump { margin: 0.5rem 0 0; font-size: 0.9375rem; }
.admin-card__jump-sep { margin: 0 0.5rem; color: var(--text-muted, #6b6b6b); }
.admin-card__jump-link { color: var(--primary); font-weight: 500; text-decoration: none; }
.admin-card__jump-link:hover { text-decoration: underline; }
.mission-vision-forms { display: grid; gap: var(--space-8); }
.mission-vision-form { padding: var(--space-6); background: var(--bg-secondary); border-radius: var(--radius-lg); border: 1px solid var(--border-light); }
.mission-vision-form__heading { margin: 0 0 var(--space-4); font-size: 1.375rem; font-weight: 700; color: var(--text-primary); border-bottom: 2px solid var(--primary); padding-bottom: var(--space-2); }
.legal-page-header { display: flex; align-items: flex-start; gap: var(--space-4); margin-bottom: var(--space-6); }
.legal-page-header__icon { width: 56px; height: 56px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; background: var(--primary-light); color: var(--primary); flex-shrink: 0; }
.legal-page-header__icon svg { width: 28px; height: 28px; }
.legal-page-header__title { margin: 0 0 var(--space-1); font-size: 1.25rem; font-weight: 600; color: var(--text-primary); }
.legal-page-header__subtitle { margin: 0; font-size: 0.875rem; color: var(--text-secondary); line-height: 1.5; }
.form-actions { display: flex; gap: var(--space-3); padding-top: var(--space-4); border-top: 1px solid var(--border-light); }
.form-actions .btn { display: inline-flex; align-items: center; gap: var(--space-2); }
.form-control--textarea { font-family: inherit; font-size: 0.9375rem; line-height: 1.6; }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
