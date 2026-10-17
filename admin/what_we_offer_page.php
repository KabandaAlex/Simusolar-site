<?php
/**
 * Admin - What We Offer page content & sections
 * Hero, section headings, and CRUD for services, product categories, benefits, and financing.
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$pdo = getDb();
$currentNav = 'what-we-offer-page';
$pageTitle = 'What We Offer Page';

$message = '';
$messageType = 'success';

// Load key-value content
$contentKeys = [
    'hero_heading', 'hero_subtitle', 'hero_primary_text', 'hero_primary_url', 'hero_secondary_text', 'hero_secondary_url',
    'services_heading', 'services_subtitle', 'services_intro',
    'products_heading', 'products_subtitle', 'products_intro',
    'why_heading', 'why_subtitle', 'why_stat_value', 'why_stat_label',
    'financing_heading', 'financing_subtitle',
    'cta_heading', 'cta_subtitle',
    'cta_feature_1', 'cta_feature_2', 'cta_feature_3', 'cta_feature_4',
    'cta_primary_text', 'cta_primary_url', 'cta_secondary_text', 'cta_secondary_url',
];

$content = array_fill_keys($contentKeys, '');
try {
    $stmt = $pdo->query("SELECT `key`, value FROM what_we_offer_page_content");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($content[$row['key']])) {
            $content[$row['key']] = $row['value'] ?? '';
        }
    }
} catch (Throwable $e) { }

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_content') {
        try {
            $stmt = $pdo->prepare('INSERT INTO what_we_offer_page_content (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
            foreach ($contentKeys as $k) {
                $v = trim($_POST['content_' . $k] ?? '');
                $stmt->execute([$k, $v]);
            }
            $message = 'What We Offer page content saved.';
            $messageType = 'success';
            logAdminActivity('Updated What We Offer page content');
            $stmt = $pdo->query("SELECT `key`, value FROM what_we_offer_page_content");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (isset($content[$row['key']])) $content[$row['key']] = $row['value'] ?? '';
            }
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }

    // Services CRUD
    if ($action === 'save_service') {
        $id   = (int)($_POST['service_id'] ?? 0);
        $title = trim($_POST['service_title'] ?? '');
        $desc  = trim($_POST['service_description'] ?? '');
        $icon  = trim($_POST['service_icon_class'] ?? '');
        $sort  = (int)($_POST['service_sort_order'] ?? 0);
        try {
            if ($id) {
                $pdo->prepare('UPDATE what_we_offer_services SET title = ?, description = ?, icon_class = ?, sort_order = ? WHERE id = ?')
                    ->execute([$title, $desc, $icon !== '' ? $icon : null, $sort, $id]);
                $message = 'Service updated.';
            } else {
                $pdo->prepare('INSERT INTO what_we_offer_services (title, description, icon_class, sort_order, is_active) VALUES (?, ?, ?, ?, 1)')
                    ->execute([$title, $desc, $icon !== '' ? $icon : null, $sort]);
                $message = 'Service added.';
            }
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    if ($action === 'delete_service' && !empty($_POST['service_id'])) {
        try {
            $pdo->prepare('DELETE FROM what_we_offer_services WHERE id = ?')->execute([(int)$_POST['service_id']]);
            $message = 'Service deleted.';
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }

    // Products CRUD
    if ($action === 'save_product') {
        $id   = (int)($_POST['product_id'] ?? 0);
        $name = trim($_POST['product_name'] ?? '');
        $desc = trim($_POST['product_description'] ?? '');
        $bullets = trim($_POST['product_bullets'] ?? '');
        $pLabel = trim($_POST['product_primary_label'] ?? '');
        $pUrl   = trim($_POST['product_primary_url'] ?? '');
        $sLabel = trim($_POST['product_secondary_label'] ?? '');
        $sUrl   = trim($_POST['product_secondary_url'] ?? '');
        $icon   = trim($_POST['product_icon_class'] ?? '');
        $sort   = (int)($_POST['product_sort_order'] ?? 0);
        try {
            if ($id) {
                $pdo->prepare('UPDATE what_we_offer_products SET name = ?, description = ?, bullet_points = ?, primary_cta_label = ?, primary_cta_url = ?, secondary_cta_label = ?, secondary_cta_url = ?, icon_class = ?, sort_order = ? WHERE id = ?')
                    ->execute([$name, $desc, $bullets !== '' ? $bullets : null, $pLabel !== '' ? $pLabel : null, $pUrl !== '' ? $pUrl : null, $sLabel !== '' ? $sLabel : null, $sUrl !== '' ? $sUrl : null, $icon !== '' ? $icon : null, $sort, $id]);
                $message = 'Product category updated.';
            } else {
                $pdo->prepare('INSERT INTO what_we_offer_products (name, description, bullet_points, primary_cta_label, primary_cta_url, secondary_cta_label, secondary_cta_url, icon_class, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)')
                    ->execute([$name, $desc, $bullets !== '' ? $bullets : null, $pLabel !== '' ? $pLabel : null, $pUrl !== '' ? $pUrl : null, $sLabel !== '' ? $sLabel : null, $sUrl !== '' ? $sUrl : null, $icon !== '' ? $icon : null, $sort]);
                $message = 'Product category added.';
            }
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    if ($action === 'delete_product' && !empty($_POST['product_id'])) {
        try {
            $pdo->prepare('DELETE FROM what_we_offer_products WHERE id = ?')->execute([(int)$_POST['product_id']]);
            $message = 'Product category deleted.';
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }

    // Benefits CRUD
    if ($action === 'save_benefit') {
        $id   = (int)($_POST['benefit_id'] ?? 0);
        $title = trim($_POST['benefit_title'] ?? '');
        $desc  = trim($_POST['benefit_description'] ?? '');
        $icon  = trim($_POST['benefit_icon_class'] ?? '');
        $sort  = (int)($_POST['benefit_sort_order'] ?? 0);
        try {
            if ($id) {
                $pdo->prepare('UPDATE what_we_offer_benefits SET title = ?, description = ?, icon_class = ?, sort_order = ? WHERE id = ?')
                    ->execute([$title, $desc, $icon !== '' ? $icon : null, $sort, $id]);
                $message = 'Benefit updated.';
            } else {
                $pdo->prepare('INSERT INTO what_we_offer_benefits (title, description, icon_class, sort_order, is_active) VALUES (?, ?, ?, ?, 1)')
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
            $pdo->prepare('DELETE FROM what_we_offer_benefits WHERE id = ?')->execute([(int)$_POST['benefit_id']]);
            $message = 'Benefit deleted.';
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }

    // Financing CRUD
    if ($action === 'save_financing') {
        $id   = (int)($_POST['financing_id'] ?? 0);
        $title = trim($_POST['financing_title'] ?? '');
        $desc  = trim($_POST['financing_description'] ?? '');
        $icon  = trim($_POST['financing_icon_class'] ?? '');
        $sort  = (int)($_POST['financing_sort_order'] ?? 0);
        try {
            if ($id) {
                $pdo->prepare('UPDATE what_we_offer_financing SET title = ?, description = ?, icon_class = ?, sort_order = ? WHERE id = ?')
                    ->execute([$title, $desc, $icon !== '' ? $icon : null, $sort, $id]);
                $message = 'Financing option updated.';
            } else {
                $pdo->prepare('INSERT INTO what_we_offer_financing (title, description, icon_class, sort_order, is_active) VALUES (?, ?, ?, ?, 1)')
                    ->execute([$title, $desc, $icon !== '' ? $icon : null, $sort]);
                $message = 'Financing option added.';
            }
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    if ($action === 'delete_financing' && !empty($_POST['financing_id'])) {
        try {
            $pdo->prepare('DELETE FROM what_we_offer_financing WHERE id = ?')->execute([(int)$_POST['financing_id']]);
            $message = 'Financing option deleted.';
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Reload lists
$servicesList = [];
$productsList = [];
$benefitsList = [];
$financingList = [];
try { $servicesList = $pdo->query('SELECT * FROM what_we_offer_services ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) { }
try { $productsList = $pdo->query('SELECT * FROM what_we_offer_products ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) { }
try { $benefitsList = $pdo->query('SELECT * FROM what_we_offer_benefits ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) { }
try { $financingList = $pdo->query('SELECT * FROM what_we_offer_financing ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) { }

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
        <div class="dashboard-hero__greeting">What We Offer</div>
        <h1 class="dashboard-hero__title">What We Offer Page</h1>
        <p class="dashboard-hero__subtitle">Edit hero text, section headings, and manage services, product categories, benefits, and financing options for the public What We Offer page.</p>
      </div>
      <div class="dashboard-hero__actions">
        <a href="<?php echo $basePath; ?>/what-we-offer" target="_blank" rel="noopener" class="dashboard-hero__btn">View page</a>
      </div>
    </div>
  </div>

  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <h3 class="admin-card__title">Page content</h3>
      <p class="admin-card__subtitle">Hero text, section headings, and CTA labels/URLs.</p>
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
        <h4 style="margin:1rem 0 0.5rem;">Services section</h4>
        <div class="form-group">
          <label>Services heading</label>
          <input type="text" name="content_services_heading" class="form-control" value="<?php echo htmlspecialchars($content['services_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Services subtitle</label>
          <input type="text" name="content_services_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['services_subtitle']); ?>">
        </div>
        <div class="form-group">
          <label>Services intro</label>
          <textarea name="content_services_intro" class="form-control form-control--textarea" rows="2"><?php echo htmlspecialchars($content['services_intro']); ?></textarea>
        </div>

        <hr style="margin:1.5rem 0;">
        <h4 style="margin:1rem 0 0.5rem;">Product categories section</h4>
        <div class="form-group">
          <label>Products heading</label>
          <input type="text" name="content_products_heading" class="form-control" value="<?php echo htmlspecialchars($content['products_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Products subtitle</label>
          <input type="text" name="content_products_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['products_subtitle']); ?>">
        </div>
        <div class="form-group">
          <label>Products intro</label>
          <textarea name="content_products_intro" class="form-control form-control--textarea" rows="2"><?php echo htmlspecialchars($content['products_intro']); ?></textarea>
        </div>

        <hr style="margin:1.5rem 0;">
        <h4 style="margin:1rem 0 0.5rem;">Why Choose Us section</h4>
        <div class="form-group">
          <label>Why Choose Us heading</label>
          <input type="text" name="content_why_heading" class="form-control" value="<?php echo htmlspecialchars($content['why_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Why Choose Us subtitle</label>
          <input type="text" name="content_why_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['why_subtitle']); ?>">
        </div>
        <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group">
            <label>Highlight stat value</label>
            <input type="text" name="content_why_stat_value" class="form-control" value="<?php echo htmlspecialchars($content['why_stat_value']); ?>">
          </div>
          <div class="form-group">
            <label>Highlight stat label</label>
            <input type="text" name="content_why_stat_label" class="form-control" value="<?php echo htmlspecialchars($content['why_stat_label']); ?>">
          </div>
        </div>

        <hr style="margin:1.5rem 0;">
        <h4 style="margin:1rem 0 0.5rem;">Financing section</h4>
        <div class="form-group">
          <label>Financing heading</label>
          <input type="text" name="content_financing_heading" class="form-control" value="<?php echo htmlspecialchars($content['financing_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Financing subtitle</label>
          <input type="text" name="content_financing_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['financing_subtitle']); ?>">
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
        <div class="form-group">
          <label>CTA feature chips (4 small feature labels)</label>
          <div class="form-grid" style="display:grid;grid-template-columns:repeat(2, minmax(0,1fr));gap:0.75rem;">
            <input type="text" name="content_cta_feature_1" class="form-control" placeholder="Feature 1" value="<?php echo htmlspecialchars($content['cta_feature_1']); ?>">
            <input type="text" name="content_cta_feature_2" class="form-control" placeholder="Feature 2" value="<?php echo htmlspecialchars($content['cta_feature_2']); ?>">
            <input type="text" name="content_cta_feature_3" class="form-control" placeholder="Feature 3" value="<?php echo htmlspecialchars($content['cta_feature_3']); ?>">
            <input type="text" name="content_cta_feature_4" class="form-control" placeholder="Feature 4" value="<?php echo htmlspecialchars($content['cta_feature_4']); ?>">
          </div>
        </div>

        <div class="form-actions" style="margin-top:1.5rem;">
          <button type="submit" class="btn btn--primary">Save page content</button>
          <a href="<?php echo $basePath; ?>/what-we-offer" target="_blank" rel="noopener" class="btn btn--secondary">View What We Offer page</a>
        </div>
      </form>
    </div>
  </div>

  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <h3 class="admin-card__title">Services</h3>
      <p class="admin-card__subtitle">Service offerings in the \"Our Services\" section.</p>
    </div>
    <div class="admin-card__body">
      <table class="admin-table" style="width:100%; margin-bottom:1rem;">
        <thead>
          <tr><th>Title</th><th>Description</th><th>Icon class</th><th>Order</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($servicesList as $s): ?>
            <tr>
              <td><?php echo htmlspecialchars($s['title']); ?></td>
              <td><?php echo htmlspecialchars(mb_substr($s['description'], 0, 80)) . (mb_strlen($s['description']) > 80 ? '…' : ''); ?></td>
              <td><?php echo htmlspecialchars($s['icon_class']); ?></td>
              <td><?php echo (int)$s['sort_order']; ?></td>
              <td>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this service?');">
                  <input type="hidden" name="action" value="delete_service">
                  <input type="hidden" name="service_id" value="<?php echo (int)$s['id']; ?>">
                  <button type="submit" class="btn btn--sm" style="color:var(--color-error);">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <form method="post">
        <input type="hidden" name="action" value="save_service">
        <input type="hidden" name="service_id" value="">
        <div class="form-grid" style="display:grid;grid-template-columns:1.5fr 3fr 1.5fr auto;gap:1rem;align-items:end;">
          <div class="form-group">
            <label>Title</label>
            <input type="text" name="service_title" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <input type="text" name="service_description" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Icon class (Font Awesome)</label>
            <input type="text" name="service_icon_class" class="form-control" placeholder="e.g. fas fa-search">
          </div>
          <div class="form-group">
            <label>Sort</label>
            <input type="number" name="service_sort_order" class="form-control" value="0" style="width:80px;">
          </div>
        </div>
        <button type="submit" class="btn btn--primary" style="margin-top:0.5rem;">Add service</button>
      </form>
    </div>
  </div>

  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <h3 class="admin-card__title">Product categories</h3>
      <p class="admin-card__subtitle">Cards shown in the \"Product Categories\" section.</p>
    </div>
    <div class="admin-card__body">
      <table class="admin-table" style="width:100%; margin-bottom:1rem;">
        <thead>
          <tr><th>Name</th><th>Description</th><th>Bullets</th><th>Icon</th><th>Order</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($productsList as $p): ?>
            <tr>
              <td><?php echo htmlspecialchars($p['name']); ?></td>
              <td><?php echo htmlspecialchars(mb_substr($p['description'], 0, 60)) . (mb_strlen($p['description']) > 60 ? '…' : ''); ?></td>
              <td><?php echo htmlspecialchars(mb_substr($p['bullet_points'], 0, 40)) . (mb_strlen($p['bullet_points']) > 40 ? '…' : ''); ?></td>
              <td><?php echo htmlspecialchars($p['icon_class']); ?></td>
              <td><?php echo (int)$p['sort_order']; ?></td>
              <td>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this product category?');">
                  <input type="hidden" name="action" value="delete_product">
                  <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
                  <button type="submit" class="btn btn--sm" style="color:var(--color-error);">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <form method="post">
        <input type="hidden" name="action" value="save_product">
        <input type="hidden" name="product_id" value="">
        <div class="form-grid" style="display:grid;grid-template-columns:1.5fr 2.5fr 1.5fr 1fr auto;gap:1rem;align-items:end;">
          <div class="form-group">
            <label>Name</label>
            <input type="text" name="product_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <input type="text" name="product_description" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Bullets (one per line)</label>
            <textarea name="product_bullets" class="form-control form-control--textarea" rows="2"></textarea>
          </div>
          <div class="form-group">
            <label>Icon class</label>
            <input type="text" name="product_icon_class" class="form-control" placeholder="e.g. fas fa-water">
          </div>
          <div class="form-group">
            <label>Sort</label>
            <input type="number" name="product_sort_order" class="form-control" value="0" style="width:80px;">
          </div>
        </div>
        <div class="form-grid" style="display:grid;grid-template-columns:repeat(4, minmax(0,1fr));gap:1rem;margin-top:0.75rem;">
          <div class="form-group">
            <label>Primary CTA label</label>
            <input type="text" name="product_primary_label" class="form-control" placeholder="e.g. Contact">
          </div>
          <div class="form-group">
            <label>Primary CTA URL</label>
            <input type="text" name="product_primary_url" class="form-control" placeholder="/contact">
          </div>
          <div class="form-group">
            <label>Secondary CTA label</label>
            <input type="text" name="product_secondary_label" class="form-control" placeholder="Learn More">
          </div>
          <div class="form-group">
            <label>Secondary CTA URL</label>
            <input type="text" name="product_secondary_url" class="form-control" placeholder="/solar-water-pumps">
          </div>
        </div>
        <button type="submit" class="btn btn--primary" style="margin-top:0.5rem;">Add product category</button>
      </form>
    </div>
  </div>

  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <h3 class="admin-card__title">Why Choose Us benefits</h3>
      <p class="admin-card__subtitle">Cards shown in the \"Why Choose Tulima Solar?\" section.</p>
    </div>
    <div class="admin-card__body">
      <table class="admin-table" style="width:100%; margin-bottom:1rem;">
        <thead>
          <tr><th>Title</th><th>Description</th><th>Icon</th><th>Order</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($benefitsList as $b): ?>
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
        <div class="form-grid" style="display:grid;grid-template-columns:1.5fr 3fr 1.5fr auto;gap:1rem;align-items:end;">
          <div class="form-group">
            <label>Title</label>
            <input type="text" name="benefit_title" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <input type="text" name="benefit_description" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Icon class</label>
            <input type="text" name="benefit_icon_class" class="form-control" placeholder="e.g. fas fa-award">
          </div>
          <div class="form-group">
            <label>Sort</label>
            <input type="number" name="benefit_sort_order" class="form-control" value="0" style="width:80px;">
          </div>
        </div>
        <button type="submit" class="btn btn--primary" style="margin-top:0.5rem;">Add benefit</button>
      </form>
    </div>
  </div>

  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <h3 class="admin-card__title">Financing options</h3>
      <p class="admin-card__subtitle">Items shown in the \"Flexible Professional Financing\" section.</p>
    </div>
    <div class="admin-card__body">
      <table class="admin-table" style="width:100%; margin-bottom:1rem;">
        <thead>
          <tr><th>Title</th><th>Description</th><th>Icon</th><th>Order</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($financingList as $f): ?>
            <tr>
              <td><?php echo htmlspecialchars($f['title']); ?></td>
              <td><?php echo htmlspecialchars(mb_substr($f['description'], 0, 80)) . (mb_strlen($f['description']) > 80 ? '…' : ''); ?></td>
              <td><?php echo htmlspecialchars($f['icon_class']); ?></td>
              <td><?php echo (int)$f['sort_order']; ?></td>
              <td>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this financing option?');">
                  <input type="hidden" name="action" value="delete_financing">
                  <input type="hidden" name="financing_id" value="<?php echo (int)$f['id']; ?>">
                  <button type="submit" class="btn btn--sm" style="color:var(--color-error);">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <form method="post">
        <input type="hidden" name="action" value="save_financing">
        <input type="hidden" name="financing_id" value="">
        <div class="form-grid" style="display:grid;grid-template-columns:1.5fr 3fr 1.5fr auto;gap:1rem;align-items:end;">
          <div class="form-group">
            <label>Title</label>
            <input type="text" name="financing_title" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <input type="text" name="financing_description" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Icon class</label>
            <input type="text" name="financing_icon_class" class="form-control" placeholder="e.g. fas fa-calendar-alt">
          </div>
          <div class="form-group">
            <label>Sort</label>
            <input type="number" name="financing_sort_order" class="form-control" value="0" style="width:80px;">
          </div>
        </div>
        <button type="submit" class="btn btn--primary" style="margin-top:0.5rem;">Add financing option</button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

