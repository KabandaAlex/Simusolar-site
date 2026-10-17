<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$pdo = getDb();
$currentNav = 'legal';
$pageTitle = 'Legal Pages';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pageKey = trim($_POST['page_key'] ?? '');
    
    if ($action === 'save' && in_array($pageKey, ['privacy', 'terms', 'cookies', 'sitemap'], true)) {
        try {
            $stmt = $pdo->prepare('UPDATE legal_pages SET title = ?, content = ? WHERE page_key = ?');
            $stmt->execute([
                trim($_POST['title'] ?? ''),
                trim($_POST['content'] ?? ''),
                $pageKey
            ]);
            
            if ($stmt->rowCount() > 0) {
                $message = ucfirst($pageKey) . ' page saved successfully.';
                $messageType = 'success';
                logAdminActivity('Updated legal page: ' . $pageKey);
            } else {
                $stmt = $pdo->prepare('INSERT INTO legal_pages (page_key, title, content) VALUES (?, ?, ?)');
                $stmt->execute([$pageKey, trim($_POST['title'] ?? ''), trim($_POST['content'] ?? '')]);
                $message = ucfirst($pageKey) . ' page created and saved successfully.';
                $messageType = 'success';
                logAdminActivity('Created legal page: ' . $pageKey);
            }
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$items = [];
try {
    $items = $pdo->query('SELECT * FROM legal_pages ORDER BY FIELD(page_key, "privacy", "terms", "cookies", "sitemap")')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $items = [];
}

$keys = [
    'privacy' => 'Privacy Policy',
    'terms' => 'Terms & Conditions',
    'cookies' => 'Cookie Policy',
    'sitemap' => 'Sitemap'
];
foreach ($keys as $k => $label) {
    if (!array_filter($items, function ($r) use ($k) { return ($r['page_key'] ?? '') === $k; })) {
        $items[] = ['page_key' => $k, 'title' => $label, 'content' => ''];
    }
}

$adminBase = rtrim(BASE_PATH, '/') . '/admin';
$basePath = BASE_PATH;

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
        <div class="dashboard-hero__greeting">Legal Pages</div>
        <h1 class="dashboard-hero__title">Manage Legal Content</h1>
        <p class="dashboard-hero__subtitle">Configure Privacy Policy, Terms & Conditions, and Sitemap pages for your website</p>
      </div>
    </div>
  </div>

  <!-- Stats Grid -->
  <div class="dashboard-stats">
    <div class="stat-card stat-card--purple">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
            <polyline points="17 6 23 6 23 12"/>
          </svg>
          All configured
        </div>
      </div>
      <div class="stat-card__value"><?php echo count($items); ?></div>
      <div class="stat-card__label">Legal Pages</div>
    </div>

    <div class="stat-card stat-card--green">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
            <polyline points="17 6 23 6 23 12"/>
          </svg>
          <?php echo count(array_filter($items, fn($i) => !empty($i['content']))); ?> filled
        </div>
      </div>
      <div class="stat-card__value"><?php echo count(array_filter($items, fn($i) => !empty($i['content']))); ?></div>
      <div class="stat-card__label">With Content</div>
    </div>

    <div class="stat-card stat-card--blue">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
            <polyline points="17 6 23 6 23 12"/>
          </svg>
          Auto-generated
        </div>
      </div>
      <div class="stat-card__value">Yes</div>
      <div class="stat-card__label">Sitemap</div>
    </div>
  </div>

  <!-- Legal Pages Content -->
  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <div class="admin-card__header-left">
        <h3 class="admin-card__title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
          </svg>
          Edit Legal Pages
        </h3>
        <p class="admin-card__subtitle">Configure your legal pages. These pages appear in the website footer.</p>
      </div>
    </div>
    <div class="admin-card__body">
      <!-- Tabbed Navigation -->
      <div class="tabs-nav">
        <?php foreach ($items as $idx => $row):
          $key = $row['page_key'] ?? '';
          $label = $keys[$key] ?? $key;
          $isActive = $idx === 0;
        ?>
        <button type="button" class="tabs-nav__tab <?php echo $isActive ? 'tabs-nav__tab--active' : ''; ?>" onclick="switchTab('<?php echo htmlspecialchars($key); ?>')">
          <?php
          $iconMap = [
            'privacy' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 5-5h4"/></svg>',
            'terms' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
            'sitemap' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 8 6 1 6"/><line x1="21" y1="8" x2="16" y2="13"/><line x1="21" y1="16" x2="8" y2="16"/><line x1="24" y1="12" x2="18" y2="12"/><line x1="18" y1="12" x2="24" y2="12"/></svg>'
          ];
          echo $iconMap[$key] ?? '';
          ?>
          <?php echo htmlspecialchars($label); ?>
        </button>
        <?php endforeach; ?>
      </div>

      <!-- Legal Page Forms -->
      <?php foreach ($items as $idx => $row):
        $key = $row['page_key'] ?? '';
        $label = $keys[$key] ?? $key;
        $isActive = $idx === 0;
      ?>
      <div class="legal-page-form" id="form-<?php echo htmlspecialchars($key); ?>" style="display: <?php echo $isActive ? 'block' : 'none'; ?>;">
        <div class="legal-page-header">
          <div class="legal-page-header__icon">
            <?php
            $iconMap = [
              'privacy' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 5-5h4"/></svg>',
              'terms' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
              'sitemap' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 8 6 1 6"/><line x1="21" y1="8" x2="16" y2="13"/><line x1="21" y1="16" x2="8" y2="16"/><line x1="24" y1="12" x2="18" y2="12"/><line x1="18" y1="12" x2="24" y2="12"/></svg>'
            ];
            echo $iconMap[$key] ?? '';
            ?>
          </div>
          <div class="legal-page-header__content">
            <h3 class="legal-page-header__title"><?php echo htmlspecialchars($label); ?></h3>
            <p class="legal-page-header__subtitle"><?php echo $key === 'sitemap' ? 'The Sitemap page is generated automatically from your navigation, products, and legal links.' : 'Configure this legal page for your website.'; ?></p>
          </div>
        </div>

        <form method="post">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="page_key" value="<?php echo htmlspecialchars($key); ?>">
          
          <div class="form-grid">
            <div class="form-group">
              <label for="title_<?php echo htmlspecialchars($key); ?>">Page Title</label>
              <input type="text" id="title_<?php echo htmlspecialchars($key); ?>" name="title" class="form-control" value="<?php echo htmlspecialchars($row['title'] ?? $label); ?>" required>
              <small class="form-hint">The title that appears in the footer and on the page</small>
            </div>
          </div>
          
          <div class="form-group">
            <label for="content_<?php echo htmlspecialchars($key); ?>">Content (HTML allowed)</label>
            <textarea id="content_<?php echo htmlspecialchars($key); ?>" name="content" class="form-control form-control--textarea" rows="14" placeholder="Enter content for <?php echo htmlspecialchars($label); ?>..."><?php echo htmlspecialchars($row['content'] ?? ''); ?></textarea>
            <small class="form-hint">You can use HTML tags to format your content. The Sitemap content is optional as it is auto-generated.</small>
          </div>
          
          <div class="form-actions">
            <button type="submit" class="btn btn--primary">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/>
                <polyline points="7 3 7 8 15 8"/>
              </svg>
              Save <?php echo htmlspecialchars($label); ?>
            </button>
            <a href="<?php echo $basePath; ?>/index.php?page=<?php echo htmlspecialchars($key); ?>" target="_blank" rel="noopener" class="btn btn--secondary">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                <polyline points="15 3 21 3 21 9"/>
                <line x1="10" y1="14" x2="21" y2="3"/>
              </svg>
              View on Site
            </a>
          </div>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<style>
.tabs-nav {
  display: flex;
  gap: var(--space-2);
  margin-bottom: var(--space-6);
  border-bottom: 2px solid var(--border-light);
  padding-bottom: var(--space-2);
}

.tabs-nav__tab {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-3) var(--space-4);
  background: var(--bg-card);
  border: 2px solid var(--border-light);
  border-radius: var(--radius-md);
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all var(--transition-base);
}

.tabs-nav__tab:hover {
  background: var(--bg-secondary);
  color: var(--text-primary);
}

.tabs-nav__tab--active {
  background: var(--primary-light);
  border-color: var(--primary);
  color: var(--primary);
}

.tabs-nav__tab svg {
  flex-shrink: 0;
}

.legal-page-form {
  margin-top: var(--space-6);
}

.legal-page-header {
  display: flex;
  align-items: flex-start;
  gap: var(--space-4);
  padding: var(--space-5);
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  margin-bottom: var(--space-6);
}

.legal-page-header__icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary-light);
  color: var(--primary);
  flex-shrink: 0;
}

.legal-page-header__icon svg {
  width: 28px;
  height: 28px;
}

.legal-page-header__content {
  flex: 1;
  min-width: 0;
}

.legal-page-header__title {
  margin: 0 0 var(--space-1);
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
}

.legal-page-header__subtitle {
  margin: 0;
  font-size: 0.875rem;
  color: var(--text-secondary);
  line-height: 1.5;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-4);
  margin-bottom: var(--space-6);
}

.form-control--textarea {
  font-family: 'Courier New', Courier, monospace;
  font-size: 0.875rem;
  line-height: 1.6;
}

.form-actions {
  display: flex;
  gap: var(--space-3);
  justify-content: flex-end;
  padding-top: var(--space-4);
  border-top: 1px solid var(--border-light);
}

.form-actions .btn {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
}

/* Dark mode styles */
.dark .tabs-nav__tab {
  background: var(--bg-card);
  border-color: var(--border-dark);
  color: var(--text-secondary);
}

.dark .tabs-nav__tab:hover {
  background: var(--bg-secondary);
  color: var(--text-primary);
}

.dark .tabs-nav__tab--active {
  background: var(--primary-light);
  border-color: var(--primary);
  color: var(--primary);
}

.dark .legal-page-header {
  background: var(--bg-card);
}

.dark .form-actions {
  border-top-color: var(--border-dark);
}
</style>

<script>
function switchTab(key) {
  // Hide all forms
  document.querySelectorAll('.legal-page-form').forEach(function(form) {
    form.style.display = 'none';
  });
  
  // Show selected form
  var selectedForm = document.getElementById('form-' + key);
  if (selectedForm) {
    selectedForm.style.display = 'block';
  }
  
  // Update tab active state
  document.querySelectorAll('.tabs-nav__tab').forEach(function(tab) {
    tab.classList.remove('tabs-nav__tab--active');
  });
  event.target.classList.add('tabs-nav__tab--active');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
