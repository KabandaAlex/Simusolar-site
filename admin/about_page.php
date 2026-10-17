<?php
/**
 * Admin - About Page (Who We Are) content
 * Hero, story, mission/vision section titles, values, offices, directions, team, CTA.
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$pdo = getDb();
$currentNav = 'about-page';
$pageTitle = 'About Page';

$message = '';
$messageType = 'success';

$contentKeys = [
    'hero_heading', 'hero_subtitle', 'hero_cta_primary_text', 'hero_cta_primary_url', 'hero_cta_secondary_text', 'hero_cta_secondary_url',
    'story_heading', 'story_subtitle', 'story_paragraph_1', 'story_paragraph_2', 'story_paragraph_3', 'story_cta_text', 'story_cta_url',
    'mission_vision_heading', 'mission_vision_subtitle',
    'values_heading', 'values_subtitle',
    'offices_heading', 'offices_subtitle',
    'directions_heading', 'directions_subtitle', 'directions_form_heading', 'directions_form_subtitle',
    'team_heading', 'team_subtitle', 'directors_heading', 'directors_subtitle', 'advisors_heading', 'advisors_subtitle',
    'cta_heading', 'cta_subtitle', 'cta_primary_text', 'cta_primary_url', 'cta_secondary_text', 'cta_secondary_url',
];

$content = array_fill_keys($contentKeys, '');
try {
    $stmt = $pdo->query("SELECT `key`, value FROM about_page_content");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($content[$row['key']])) {
            $content[$row['key']] = $row['value'] ?? '';
        }
    }
} catch (Throwable $e) { }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_content') {
    try {
        $stmt = $pdo->prepare('INSERT INTO about_page_content (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
        foreach ($contentKeys as $k) {
            $v = trim($_POST['content_' . $k] ?? '');
            $stmt->execute([$k, $v]);
        }
        $message = 'About page content saved.';
        $messageType = 'success';
        logAdminActivity('Updated about page content');
        $stmt = $pdo->query("SELECT `key`, value FROM about_page_content");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($content[$row['key']])) $content[$row['key']] = $row['value'] ?? '';
        }
    } catch (Throwable $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
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
        <div class="dashboard-hero__greeting">Who We Are</div>
        <h1 class="dashboard-hero__title">About Page Content</h1>
        <p class="dashboard-hero__subtitle">Edit headings, story text, and CTAs for the public About (Who We Are) page. Office locations and team are managed in Office Locations and Team Members.</p>
      </div>
      <div class="dashboard-hero__actions">
        <a href="<?php echo $basePath; ?>/who-we-are" target="_blank" rel="noopener" class="dashboard-hero__btn">View About page</a>
      </div>
    </div>
  </div>

  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <h3 class="admin-card__title">Page content</h3>
      <p class="admin-card__subtitle">Hero, story, section titles, and CTA. Mission &amp; Vision text is edited under Mission &amp; Vision.</p>
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
            <label>Hero primary CTA text</label>
            <input type="text" name="content_hero_cta_primary_text" class="form-control" value="<?php echo htmlspecialchars($content['hero_cta_primary_text']); ?>">
          </div>
          <div class="form-group">
            <label>Hero primary CTA URL</label>
            <input type="text" name="content_hero_cta_primary_url" class="form-control" value="<?php echo htmlspecialchars($content['hero_cta_primary_url']); ?>">
          </div>
          <div class="form-group">
            <label>Hero secondary CTA text</label>
            <input type="text" name="content_hero_cta_secondary_text" class="form-control" value="<?php echo htmlspecialchars($content['hero_cta_secondary_text']); ?>">
          </div>
          <div class="form-group">
            <label>Hero secondary CTA URL</label>
            <input type="text" name="content_hero_cta_secondary_url" class="form-control" value="<?php echo htmlspecialchars($content['hero_cta_secondary_url']); ?>">
          </div>
        </div>
        <hr style="margin:1.5rem 0;">
        <h4 style="margin:1rem 0 0.5rem;">Our Story</h4>
        <div class="form-group">
          <label>Story heading</label>
          <input type="text" name="content_story_heading" class="form-control" value="<?php echo htmlspecialchars($content['story_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Story subtitle</label>
          <input type="text" name="content_story_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['story_subtitle']); ?>">
        </div>
        <div class="form-group">
          <label>Story paragraph 1</label>
          <textarea name="content_story_paragraph_1" class="form-control form-control--textarea" rows="3"><?php echo htmlspecialchars($content['story_paragraph_1']); ?></textarea>
        </div>
        <div class="form-group">
          <label>Story paragraph 2</label>
          <textarea name="content_story_paragraph_2" class="form-control form-control--textarea" rows="3"><?php echo htmlspecialchars($content['story_paragraph_2']); ?></textarea>
        </div>
        <div class="form-group">
          <label>Story paragraph 3</label>
          <textarea name="content_story_paragraph_3" class="form-control form-control--textarea" rows="3"><?php echo htmlspecialchars($content['story_paragraph_3']); ?></textarea>
        </div>
        <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group">
            <label>Story CTA button text</label>
            <input type="text" name="content_story_cta_text" class="form-control" value="<?php echo htmlspecialchars($content['story_cta_text']); ?>">
          </div>
          <div class="form-group">
            <label>Story CTA URL</label>
            <input type="text" name="content_story_cta_url" class="form-control" value="<?php echo htmlspecialchars($content['story_cta_url']); ?>">
          </div>
        </div>
        <hr style="margin:1.5rem 0;">
        <h4 style="margin:1rem 0 0.5rem;">Mission &amp; Vision section</h4>
        <div class="form-group">
          <label>Section heading</label>
          <input type="text" name="content_mission_vision_heading" class="form-control" value="<?php echo htmlspecialchars($content['mission_vision_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Section subtitle</label>
          <input type="text" name="content_mission_vision_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['mission_vision_subtitle']); ?>">
        </div>
        <hr style="margin:1.5rem 0;">
        <h4 style="margin:1rem 0 0.5rem;">Our Values section</h4>
        <div class="form-group">
          <label>Values heading</label>
          <input type="text" name="content_values_heading" class="form-control" value="<?php echo htmlspecialchars($content['values_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Values subtitle</label>
          <input type="text" name="content_values_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['values_subtitle']); ?>">
        </div>
        <p class="admin-card__subtitle"><a href="<?php echo $adminBase; ?>/about_values.php">Edit value items (Our Values list)</a></p>
        <hr style="margin:1.5rem 0;">
        <h4 style="margin:1rem 0 0.5rem;">Office locations section</h4>
        <div class="form-group">
          <label>Offices heading</label>
          <input type="text" name="content_offices_heading" class="form-control" value="<?php echo htmlspecialchars($content['offices_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Offices subtitle</label>
          <input type="text" name="content_offices_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['offices_subtitle']); ?>">
        </div>
        <p class="admin-card__subtitle"><a href="<?php echo $adminBase; ?>/office_locations.php">Manage office locations</a></p>
        <hr style="margin:1.5rem 0;">
        <h4 style="margin:1rem 0 0.5rem;">Get Directions section</h4>
        <div class="form-group">
          <label>Directions heading</label>
          <input type="text" name="content_directions_heading" class="form-control" value="<?php echo htmlspecialchars($content['directions_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Directions subtitle</label>
          <input type="text" name="content_directions_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['directions_subtitle']); ?>">
        </div>
        <div class="form-group">
          <label>Directions form heading</label>
          <input type="text" name="content_directions_form_heading" class="form-control" value="<?php echo htmlspecialchars($content['directions_form_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Directions form subtitle</label>
          <input type="text" name="content_directions_form_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['directions_form_subtitle']); ?>">
        </div>
        <hr style="margin:1.5rem 0;">
        <h4 style="margin:1rem 0 0.5rem;">Team sections</h4>
        <div class="form-group">
          <label>Our Staff heading</label>
          <input type="text" name="content_team_heading" class="form-control" value="<?php echo htmlspecialchars($content['team_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Our Staff subtitle</label>
          <input type="text" name="content_team_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['team_subtitle']); ?>">
        </div>
        <div class="form-group">
          <label>Board of Directors heading</label>
          <input type="text" name="content_directors_heading" class="form-control" value="<?php echo htmlspecialchars($content['directors_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Board of Directors subtitle</label>
          <input type="text" name="content_directors_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['directors_subtitle']); ?>">
        </div>
        <div class="form-group">
          <label>Our Advisors heading</label>
          <input type="text" name="content_advisors_heading" class="form-control" value="<?php echo htmlspecialchars($content['advisors_heading']); ?>">
        </div>
        <div class="form-group">
          <label>Our Advisors subtitle</label>
          <input type="text" name="content_advisors_subtitle" class="form-control" value="<?php echo htmlspecialchars($content['advisors_subtitle']); ?>">
        </div>
        <p class="admin-card__subtitle"><a href="<?php echo $adminBase; ?>/team_members.php">Manage team members</a></p>
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
          <button type="submit" class="btn btn--primary">Save all content</button>
          <a href="<?php echo $basePath; ?>/who-we-are" target="_blank" rel="noopener" class="btn btn--secondary">View About page</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
