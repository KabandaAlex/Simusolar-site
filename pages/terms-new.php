<?php
$pageTitle = 'Terms & Conditions - Tulima Solar';
$pageDescription = 'Review Tulima Solar\'s Terms & Conditions – rules for using our website and services.';
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

$legal = getLegalPage($pdo, 'terms');
$siteBase = rtrim(BASE_PATH, '/');
?>

<section class="c-page-header">
  <div class="c-page-header__container">
    <nav class="c-page-header__breadcrumb" aria-label="Breadcrumb">
      <!-- <a href="<?php echo $siteBase; ?>/">Home</a> -->
      <!-- <span class="c-page-header__breadcrumb-separator" aria-hidden="true">/</span> -->
      <!-- <span aria-current="page">Terms &amp; Conditions</span> -->
    </nav>
    <h1 class="c-page-header__title"><?php echo e($legal['title'] ?? 'Terms &amp; Conditions'); ?></h1>
    <p class="c-section__subtitle">The terms that apply when you use our website and services.</p>
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
        <p>These Terms &amp; Conditions explain how you may use the Simusolar website and related services. By accessing or using this site, you agree to be bound by these terms.</p>

        <h2>1. Use of our website</h2>
        <p>You may use this website to learn about our products and services, request information, and contact our team. You agree not to misuse the site or attempt to interfere with its security or normal operation.</p>

        <h2>2. Information on this website</h2>
        <p>Content on this site is provided for general information and marketing purposes. While we aim for accuracy, specifications, pricing, and availability of products or financing may change without notice. For the latest details, please contact our sales team directly.</p>

        <h2>3. Quotes, orders, and financing</h2>
        <p>Any quote provided through this website is an invitation to discuss a potential system, not a binding offer. A binding agreement is only formed when you sign a contract or other written agreement with Simusolar or our authorised partners. Financing offers are subject to eligibility, credit assessment, and the terms of the relevant financing partner.</p>

        <h2>4. Customer responsibilities</h2>
        <p>Where we design and install a solar or irrigation system for you, you are responsible for:</p>
        <ul>
          <li>Providing accurate information about your farm, business, or property.</li>
          <li>Obtaining any necessary permissions or approvals for installation on your land or buildings.</li>
          <li>Using the system in accordance with our guidance and the product documentation.</li>
        </ul>

        <h2>5. Intellectual property</h2>
        <p>All content on this website – including text, graphics, logos, photos, and designs – is owned by or licensed to Simusolar and is protected by intellectual property laws. You may not copy, reproduce, or distribute content from this site without our prior written permission, except for personal, non-commercial use.</p>

        <h2>6. Third‑party links</h2>
        <p>Our website may contain links to third‑party websites or services. These are provided for convenience only. We are not responsible for the content, security, or privacy practices of any third‑party site.</p>

        <h2>7. Disclaimer and limitation of liability</h2>
        <p>We provide this website on an “as is” and “as available” basis. To the fullest extent permitted by law, we do not warrant that the site will be uninterrupted or error‑free. We are not liable for any indirect, incidental, or consequential damages arising from your use of this website. Nothing in these terms excludes liability that cannot be excluded under applicable law.</p>

        <h2>8. Changes to these terms</h2>
        <p>We may update these Terms &amp; Conditions from time to time to reflect changes in our services, legal requirements, or business needs. The updated version will be posted on this page with an updated “Last updated” date. Continued use of the website after changes means you accept the revised terms.</p>

        <h2>9. Governing law</h2>
        <p>These Terms &amp; Conditions are governed by the laws of the country in which Simusolar is incorporated, without regard to conflict‑of‑law rules. Any disputes will be subject to the jurisdiction of the competent courts in that country, unless local consumer laws require otherwise.</p>

        <h2>10. Contact us</h2>
        <p>If you have questions about these Terms &amp; Conditions, please contact us via the <a href="<?php echo $siteBase; ?>/contact">Contact page</a>.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

