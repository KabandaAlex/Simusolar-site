<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
$pdo = getDb();
$currentNav = 'settings';
$pageTitle = 'Settings';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---------------------------------------------------------
    // NEW: Handle the Chatbot Settings Update
    // ---------------------------------------------------------
    if (isset($_POST['update_chatbot_settings'])) {
        $chatbot_status = isset($_POST['chatbot_enabled']) ? 1 : 0;
        
        // Update the chatbot_enabled column (using PDO to match your existing setup)
        $pdo->exec("UPDATE site_settings SET chatbot_enabled = " . (int)$chatbot_status);
        
        $message = 'Chatbot settings saved successfully.';
    } 
    // ---------------------------------------------------------
    // EXISTING: Handle all other Settings Updates
    // ---------------------------------------------------------
    else {
        $keys = ['site_name', 'tagline', 'footer_text', 'footer_description', 'whatsapp_number', 'whatsapp_message', 'business_hours', 'facebook_url', 'twitter_url', 'linkedin_url', 'instagram_url', 'youtube_url'];
        foreach ($keys as $k) {
            if (array_key_exists($k, $_POST)) {
                $stmt = $pdo->prepare('INSERT INTO site_settings (`key`, value, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
                $label = str_replace('_', ' ', ucfirst($k));
                $stmt->execute([$k, trim($_POST[$k]), $label]);
            }
        }
        $message = 'Settings saved successfully.';
    }
}

$settings = [];
$stmt = $pdo->query('SELECT `key`, value, label FROM site_settings');
while ($row = $stmt->fetch()) {
    $settings[$row['key']] = $row;
}
function sv($settings, $key, $default = '') {
    return isset($settings[$key]['value']) ? $settings[$key]['value'] : $default;
}

// NEW: Fetch the current chatbot status to check/uncheck the box properly
$botStmt = $pdo->query('SELECT chatbot_enabled FROM site_settings LIMIT 1');
$botRow = $botStmt->fetch();
$isBotEnabled = $botRow ? ((int)$botRow['chatbot_enabled'] === 1) : true;

include __DIR__ . '/includes/header.php';
?>

<!-- Enhanced Page Header -->
 <div class="admin-content">
<div class="page-header-enhanced">
  <div class="page-header-enhanced__content">
    <h1 class="page-header-enhanced__title">Site Settings</h1>
    <p class="page-header-enhanced__subtitle">Configure global settings for your website - branding, social links, and contact information.</p>
  </div>
</div>
</div>
<?php if ($message): ?>
  <div class="admin-alert admin-alert--success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<!-- Settings Card Groups -->
<div class="settings-card-group">
  <div class="settings-card" onclick="document.getElementById('branding-form').scrollIntoView({behavior: 'smooth'})">
    <div class="settings-card__icon">
      <svg viewBox="0 0 24 24" width="24" height="24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 15"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    </div>
    <div class="settings-card__title">Branding</div>
    <div class="settings-card__description">Site name, tagline, and footer text</div>
  </div>

  <div class="settings-card" onclick="document.getElementById('social-form').scrollIntoView({behavior: 'smooth'})">
    <div class="settings-card__icon">
      <svg viewBox="0 0 24 24" width="24" height="24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7a5 5 0 0 0-5-5V2a2 2 0 0 0-2-2h10a2 2 0 0 0 2 2z"/><line x1="12" y1="22" x2="12" y2="18"/><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="2" x2="12" y2="6"/><circle cx="12" cy="12" r="2"/></svg>
    </div>
    <div class="settings-card__title">Social Links</div>
    <div class="settings-card__description">Facebook, Twitter, LinkedIn, Instagram, YouTube</div>
  </div>

  <div class="settings-card" onclick="document.getElementById('whatsapp-form').scrollIntoView({behavior: 'smooth'})">
    <div class="settings-card__icon">
      <svg viewBox="0 0 24 24" width="24" height="24"><path d="M21 11.5a8.38 8.38 0 0 0-.819-2.207l-.819-2.207C16.145 5.607 14 5 11.5 5c-2.76 0-5.145 1.607-6.381 4.293L4.38 15.793c-.195-.195-.512-.195-.707 0l-2.667 2.667c.195.195.195.512 0 .707l2.667-2.667c.195.195.195.512 0 .707l2.667 2.667c.195.195.195.512 0-.707 0l-2.667 2.667c-.195.195-.512-.195-.707 0L4.38 19.207z"/></svg>
    </div>
    <div class="settings-card__title">WhatsApp</div>
    <div class="settings-card__description">Number and default message for floating button</div>
  </div>

  <!-- NEW: Chatbot Nav Card -->
  <div class="settings-card" onclick="document.getElementById('chatbot-form').scrollIntoView({behavior: 'smooth'})">
    <div class="settings-card__icon">
      <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
    </div>
    <div class="settings-card__title">AI Chatbot</div>
    <div class="settings-card__description">Enable or disable the Botpress chat widget</div>
  </div>
</div>

<!-- Branding Settings -->
<div class="admin-card" id="branding-form">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 15"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Branding Settings
    </h3>
  </div>
  <div class="admin-card__body">
    <form method="post">
      <div class="form-group">
        <label>Site name</label>
        <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars(sv($settings, 'site_name', 'Tulima Solar')); ?>">
      </div>
      <div class="form-group">
        <label>Tagline</label>
        <input type="text" name="tagline" class="form-control" value="<?php echo htmlspecialchars(sv($settings, 'tagline', 'Financed Solutions for Agriculture')); ?>">
      </div>
      <div class="form-group">
        <label>Footer text (short)</label>
        <textarea name="footer_text" class="form-control" rows="2"><?php echo htmlspecialchars(sv($settings, 'footer_text')); ?></textarea>
      </div>
      <div class="form-group">
        <label>Footer description (long, under logo in footer)</label>
        <textarea name="footer_description" class="form-control form-control--full" rows="3"><?php echo htmlspecialchars(sv($settings, 'footer_description')); ?></textarea>
      </div>
      <div class="form-group">
        <label>Business hours (footer)</label>
        <input type="text" name="business_hours" class="form-control" value="<?php echo htmlspecialchars(sv($settings, 'business_hours')); ?>" placeholder="e.g. Mon - Fri: 8:00 AM - 6:00 PM">
      </div>
      
      <button type="submit" class="btn btn--primary">Save Branding Settings</button>
    </form>
  </div>
</div>

<!-- Social Links Settings -->
<div class="admin-card" id="social-form">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7a5 5 0 0 0-5-5V2a2 2 0 0 0-2-2h10a2 2 0 0 0 2 2z"/><line x1="12" y1="22" x2="12" y2="18"/><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="2" x2="12" y2="6"/><circle cx="12" cy="12" r="2"/></svg>
      Social Links
    </h3>
  </div>
  <div class="admin-card__body">
    <form method="post">
      <div class="form-group">
        <label>Facebook URL</label>
        <input type="url" name="facebook_url" class="form-control" value="<?php echo htmlspecialchars(sv($settings, 'facebook_url')); ?>" placeholder="https://facebook.com/...">
      </div>
      <div class="form-group">
        <label>Twitter / X URL</label>
        <input type="url" name="twitter_url" class="form-control" value="<?php echo htmlspecialchars(sv($settings, 'twitter_url')); ?>">
      </div>
      <div class="form-group">
        <label>LinkedIn URL</label>
        <input type="url" name="linkedin_url" class="form-control" value="<?php echo htmlspecialchars(sv($settings, 'linkedin_url')); ?>">
      </div>
      <div class="form-group">
        <label>Instagram URL</label>
        <input type="url" name="instagram_url" class="form-control" value="<?php echo htmlspecialchars(sv($settings, 'instagram_url')); ?>">
      </div>
      <div class="form-group">
        <label>YouTube URL</label>
        <input type="url" name="youtube_url" class="form-control" value="<?php echo htmlspecialchars(sv($settings, 'youtube_url')); ?>">
      </div>
      
      <button type="submit" class="btn btn--primary">Save Social Links</button>
    </form>
  </div>
</div>

<!-- WhatsApp Settings -->
<div class="admin-card" id="whatsapp-form">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 0-.819-2.207l-.819-2.207C16.145 5.607 14 5 11.5 5c-2.76 0-5.145 1.607-6.381 4.293L4.38 15.793c-.195-.195-.512-.195-.707 0l-2.667 2.667c.195.195.195.512 0 .707l2.667-2.667c.195.195.195.512 0 .707l2.667 2.667c.195.195.195.512 0-.707 0l-2.667 2.667c-.195.195-.512-.195-.707 0L4.38 19.207z"/></svg>
      WhatsApp Settings
    </h3>
  </div>
  <div class="admin-card__body">
    <form method="post">
      <div class="form-group">
        <label>WhatsApp number (with country code)</label>
        <input type="text" name="whatsapp_number" class="form-control" value="<?php echo htmlspecialchars(sv($settings, 'whatsapp_number')); ?>" placeholder="e.g. 256123456789">
      </div>
      <div class="form-group">
        <label>Default WhatsApp message</label>
        <input type="text" name="whatsapp_message" class="form-control" value="<?php echo htmlspecialchars(sv($settings, 'whatsapp_message', 'Hello, I would like to know more about Tulima Solar.')); ?>">
      </div>
      
      <button type="submit" class="btn btn--primary">Save WhatsApp Settings</button>
    </form>
  </div>
</div>

<!-- NEW: Chatbot Settings -->
<div class="admin-card" id="chatbot-form">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
      Chatbot Settings
    </h3>
  </div>
  <div class="admin-card__body">
    <form method="post">
      <div class="form-group">
        <label style="display: flex; align-items: center; cursor: pointer;">
            <input type="checkbox" name="chatbot_enabled" value="1" <?php echo $isBotEnabled ? 'checked' : ''; ?> style="width: 20px; height: 20px; margin-right: 10px;">
            <strong>Enable Chatbot on Website</strong>
        </label>
        <p style="color: #666; font-size: 13px; margin-top: 5px;">Uncheck this box and click save to completely hide the AI chat bubble from your live website.</p>
      </div>
      
      <button type="submit" name="update_chatbot_settings" class="btn btn--primary">Save Chatbot Settings</button>
    </form>
  </div>
</div>


<?php include __DIR__ . '/includes/footer.php'; ?>