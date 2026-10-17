<?php
$pageTitle = 'Cookie Policy - Tulima Solar';
$pageDescription = 'Understand how Tulima Solar uses cookies and similar technologies on this website.';
$bodyClass = 'page-legal';
$pageCss = 'legal.css';

include __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDb();

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$legal = getLegalPage($pdo, 'cookies');
$siteBase = rtrim(BASE_PATH, '/');
?>

<section class="c-page-header">
  <div class="c-page-header__container">
    <nav class="c-page-header__breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo $siteBase; ?>/">Home</a>
      <span class="c-page-header__breadcrumb-separator" aria-hidden="true">/</span>
      <span aria-current="page">Cookie Policy</span>
    </nav>
    <h1 class="c-page-header__title"><?php echo e($legal['title'] ?? 'Cookie Policy'); ?></h1>
    <p class="c-section__subtitle">Details about the cookies and similar technologies we use.</p>
  </div>
</section>

<section class="c-section c-section--white p-legal">
  <div class="c-section__container p-legal__container">
    <p class="p-legal__updated">
      Last updated:
      <?php echo isset($legal['updated_at']) ? date('F j, Y', strtotime($legal['updated_at'])) : date('F j, Y'); ?>
    </p>
    <div class="p-legal__content">
      <?php if (!empty($legal['content'])): ?>
        <?php echo $legal['content']; ?>
      <?php else: ?>
        <p>This Cookie Policy explains what cookies are, how Tulima Solar uses them on this website, and the choices you have about their use.</p>

        <h2>1. What are cookies?</h2>
        <p>Cookies are small text files that are placed on your device when you visit a website. They help the site remember your actions and preferences over time, and can also be used to understand how visitors use the site.</p>

        <h2>2. How we use cookies</h2>
        <p>We use cookies and similar technologies to:</p>
        <ul>
          <li>Enable core functionality such as page navigation and form submissions.</li>
          <li>Remember certain preferences, such as your language or region.</li>
          <li>Understand how visitors use our website so we can improve content and navigation.</li>
          <li>Support our marketing efforts, for example by measuring which campaigns lead farmers or partners to our site.</li>
        </ul>

        <h2>3. Types of cookies we use</h2>
        <ul>
          <li><strong>Strictly necessary cookies</strong> – required for the website to function properly (for example, security features and session management). These cannot be switched off in our systems.</li>
          <li><strong>Performance and analytics cookies</strong> – help us understand how visitors use the site (which pages are visited most, how users move around the site). This information is aggregated and used to improve the user experience.</li>
          <li><strong>Functionality cookies</strong> – allow the site to remember your preferences and provide enhanced features, such as pre‑filling certain form fields.</li>
          <li><strong>Marketing cookies</strong> – may be set by us or by our partners to measure the effectiveness of campaigns or show more relevant information about our solutions.</li>
        </ul>

        <h2>4. Third‑party cookies</h2>
        <p>We may use third‑party services (for example, analytics providers) that set their own cookies to help us analyse traffic and usage patterns. These providers are required to handle data in line with their own privacy and cookie policies.</p>

        <h2>5. How to control cookies</h2>
        <p>You can manage or disable cookies in your browser settings. Most browsers allow you to:</p>
        <ul>
          <li>See which cookies are stored on your device.</li>
          <li>Delete existing cookies.</li>
          <li>Block all or specific types of cookies.</li>
          <li>Set your browser to notify you when a site wants to set a cookie.</li>
        </ul>
        <p>Please note that blocking certain cookies may affect how our website functions and some features may no longer be available.</p>

        <h2>6. Updates to this Cookie Policy</h2>
        <p>We may update this Cookie Policy from time to time to reflect changes in the cookies we use or in legal requirements. The updated version will always be available on this page.</p>

        <h2>7. Contact us</h2>
        <p>If you have any questions about our use of cookies, please contact us via the <a href="<?php echo $siteBase; ?>/contact">Contact page</a>.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

