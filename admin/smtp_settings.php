<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

$pdo = getDb();
$currentNav = 'smtp-settings';
$pageTitle = 'SMTP & Email Settings';

// Load current settings
$settings = [];
$stmt = $pdo->query('SELECT `key`, value, label FROM site_settings');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key']] = $row;
}

$get = function(array $settings, string $key, string $default = '') {
    return isset($settings[$key]['value']) ? (string)$settings[$key]['value'] : $default;
};

$message = '';
$messageType = 'success';
$testResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    // Existing password (so we can keep when field is empty)
    $existingPassword = $get($settings, 'smtp_password', '');

    $enabled = isset($_POST['smtp_enabled']) ? '1' : '0';
    $host = trim($_POST['smtp_host'] ?? '');
    $port = (int)($_POST['smtp_port'] ?? 587);
    $security = $_POST['smtp_security'] ?? 'tls';
    if (!in_array($security, ['none', 'tls', 'ssl'], true)) {
        $security = 'tls';
    }
    $username = trim($_POST['smtp_username'] ?? '');
    $passwordInput = trim($_POST['smtp_password'] ?? '');
    $password = $passwordInput === '' ? $existingPassword : $passwordInput;
    $fromEmail = trim($_POST['smtp_from_email'] ?? '');
    $fromName = trim($_POST['smtp_from_name'] ?? '');
    $testRecipient = trim($_POST['smtp_test_recipient'] ?? '');
    $contactRecipients = trim($_POST['smtp_contact_recipients'] ?? '');
    $jobRecipients = trim($_POST['smtp_job_recipients'] ?? '');

    // Basic validation
    if ($enabled === '1') {
        if ($host === '' || $fromEmail === '') {
            $message = 'SMTP host and From email are required when SMTP is enabled.';
            $messageType = 'error';
        } elseif (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please provide a valid From email address.';
            $messageType = 'error';
        }
    }

    // Save settings if validation passed
    if ($messageType !== 'error') {
        $pairs = [
            'smtp_enabled' => $enabled,
            'smtp_host' => $host,
            'smtp_port' => (string)$port,
            'smtp_security' => $security,
            'smtp_username' => $username,
            'smtp_password' => $password,
            'smtp_from_email' => $fromEmail,
            'smtp_from_name' => $fromName,
            'smtp_test_recipient' => $testRecipient,
            'smtp_contact_recipients' => $contactRecipients,
            'smtp_job_recipients' => $jobRecipients,
        ];

        $insert = $pdo->prepare(
            'INSERT INTO site_settings (`key`, value, label) VALUES (?, ?, ?) 
             ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );

        foreach ($pairs as $key => $value) {
            $label = ucfirst(str_replace('_', ' ', $key));
            $insert->execute([$key, $value, $label]);
        }

        // Refresh settings array after save
        $settings = [];
        $stmt = $pdo->query('SELECT `key`, value, label FROM site_settings');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['key']] = $row;
        }

        if ($action === 'test') {
            // Run test email using the freshly-saved values
            $testResult = sendSmtpTestEmail($pdo, $testRecipient ?: null);
            if ($testResult['success']) {
                $message = 'SMTP settings saved and test email sent successfully.';
                $messageType = 'success';
            } else {
                $message = 'SMTP settings saved, but test email failed: ' . $testResult['error'];
                $messageType = 'error';
            }
        } else {
            $message = 'SMTP settings saved successfully.';
            $messageType = 'success';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
  <div class="page-header-enhanced">
    <div class="page-header-enhanced__content">
      <h1 class="page-header-enhanced__title">SMTP &amp; Email Settings</h1>
      <p class="page-header-enhanced__subtitle">
        Configure mail server details used for all outbound emails (contact form, job applications, blog engagement).
      </p>
    </div>
  </div>
</div>

<?php if ($message): ?>
  <div class="admin-alert admin-alert--<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>">
    <?php echo htmlspecialchars($message); ?>
  </div>
<?php endif; ?>

<div class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24" width="18" height="18">
        <circle cx="12" cy="12" r="3"></circle>
        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83
                 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33
                 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2
                 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4
                 1.65 1.65 0 0 0 7.18 19.73l-.06.06a2 2 0 0 1-2.83 0
                 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15
                 1.65 1.65 0 0 0 3.09 14H3a2 2 0 0 1-2-2
                 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9
                 1.65 1.65 0 0 0 4.27 7.18l-.06-.06a2 2 0 0 1 0-2.83
                 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 8.82 4.6H9
                 a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2
                 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51
                 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0
                 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9
                 1.65 1.65 0 0 0 20.91 10H21a2 2 0 0 1 2 2
                 2 2 0 0 1-2 2h-.09A1.65 1.65 0 0 0 19.4 15z"></path>
      </svg>
      Mail Server Configuration
    </h3>
  </div>
  <div class="admin-card__body">
    <?php
      $smtpEnabled = $get($settings, 'smtp_enabled', '0') === '1';
      $smtpHost = $get($settings, 'smtp_host');
      $smtpPort = $get($settings, 'smtp_port', '587');
      $smtpSecurity = $get($settings, 'smtp_security', 'tls');
      $smtpUsername = $get($settings, 'smtp_username');
      $smtpFromEmail = $get($settings, 'smtp_from_email');
      $smtpFromName = $get($settings, 'smtp_from_name', 'Tulima Solar');
      $smtpTestRecipient = $get($settings, 'smtp_test_recipient');
      $smtpContactRecipients = $get($settings, 'smtp_contact_recipients');
      $smtpJobRecipients = $get($settings, 'smtp_job_recipients');
      $hasPassword = $get($settings, 'smtp_password') !== '';
    ?>
    <form method="post">
      <input type="hidden" name="action" value="save" id="smtp-action-input">

      <div class="form-group">
        <label>
          <input type="checkbox" name="smtp_enabled" value="1" <?php echo $smtpEnabled ? 'checked' : ''; ?>>
          Enable SMTP for all outgoing emails
        </label>
        <p class="form-hint">When disabled, the application will not attempt to send any emails.</p>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>SMTP Host <span class="required">*</span></label>
          <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($smtpHost); ?>" placeholder="smtp.your-provider.com">
        </div>
        <div class="form-group">
          <label>Port <span class="required">*</span></label>
          <input type="number" name="smtp_port" class="form-control" value="<?php echo htmlspecialchars($smtpPort); ?>" min="1" max="65535">
        </div>
        <div class="form-group">
          <label>Security</label>
          <select name="smtp_security" class="form-control">
            <option value="none" <?php echo $smtpSecurity === 'none' ? 'selected' : ''; ?>>None</option>
            <option value="tls" <?php echo $smtpSecurity === 'tls' ? 'selected' : ''; ?>>STARTTLS (recommended)</option>
            <option value="ssl" <?php echo $smtpSecurity === 'ssl' ? 'selected' : ''; ?>>SSL</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="smtp_username" class="form-control" value="<?php echo htmlspecialchars($smtpUsername); ?>" autocomplete="off">
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="smtp_password" class="form-control" value="" autocomplete="new-password"
                 placeholder="<?php echo $hasPassword ? '******** (leave blank to keep current password)' : ''; ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>From Email <span class="required">*</span></label>
          <input type="email" name="smtp_from_email" class="form-control" value="<?php echo htmlspecialchars($smtpFromEmail); ?>" placeholder="no-reply@your-domain.com">
        </div>
        <div class="form-group">
          <label>From Name</label>
          <input type="text" name="smtp_from_name" class="form-control" value="<?php echo htmlspecialchars($smtpFromName); ?>" placeholder="Tulima Solar">
        </div>
      </div>

      <hr>

      <div class="form-group">
        <label>Test Recipient</label>
        <input type="email" name="smtp_test_recipient" class="form-control" value="<?php echo htmlspecialchars($smtpTestRecipient); ?>" placeholder="you@example.com">
        <p class="form-hint">Used when sending a test email. If empty, the From email will be used.</p>
      </div>

      <div class="form-group">
        <label>Contact Form Recipients</label>
        <input type="text" name="smtp_contact_recipients" class="form-control" value="<?php echo htmlspecialchars($smtpContactRecipients); ?>" placeholder="admin@your-domain.com, support@your-domain.com">
        <p class="form-hint">Comma-separated list of email addresses to receive contact form submissions.</p>
      </div>

      <div class="form-group">
        <label>Job Application Recipients</label>
        <input type="text" name="smtp_job_recipients" class="form-control" value="<?php echo htmlspecialchars($smtpJobRecipients); ?>" placeholder="hr@your-domain.com">
        <p class="form-hint">Comma-separated list of email addresses to receive new job application notifications.</p>
      </div>

      <div style="margin-top: 1.5rem; display: flex; gap: var(--space-3); flex-wrap: wrap;">
        <button type="submit" class="btn btn--primary" onclick="document.getElementById('smtp-action-input').value='save'">
          Save Settings
        </button>
        <button type="submit" class="btn btn--secondary" onclick="document.getElementById('smtp-action-input').value='test'">
          Send Test Email
        </button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

