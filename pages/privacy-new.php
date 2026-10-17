<?php
$pageTitle = 'Privacy Policy - Tulima Solar';
$pageDescription = 'Read Tulima Solar\'s Privacy Policy – how we collect, use and protect your personal information.';
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

$legal = getLegalPage($pdo, 'privacy');
$siteBase = rtrim(BASE_PATH, '/');
?>

<section class="c-page-header">
  <div class="c-page-header__container">
    <!-- <nav class="c-page-header__breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo $siteBase; ?>/">Home</a>
      <span class="c-page-header__breadcrumb-separator" aria-hidden="true">/</span>
      <span aria-current="page">Privacy Policy</span>
    </nav> -->
    <h1 class="c-page-header__title"><?php echo e($legal['title'] ?? 'Privacy Policy'); ?></h1>
    <p class="c-section__subtitle">How we collect, use, and protect your information.</p>
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
        <p>At Simusolar, we are committed to protecting the privacy of farmers, partners, and visitors who interact with our website and services. This Privacy Policy explains what information we collect, how we use it, and the choices you have.</p>

        <h2>1. Who we are</h2>
        <p>Simusolar provides solar-powered irrigation and agricultural solutions across East Africa. When we say “we”, “us” or “our” in this policy, we are referring to Simusolar and our local offices and agents.</p>

        <h2>2. Information we collect</h2>
        <p>We collect information in the following ways:</p>
        <ul>
          <li><strong>Information you provide directly</strong> – for example when you complete a contact form, request a quote, apply for financing, subscribe to our newsletter, or communicate with us by phone, email, WhatsApp, or in person. This may include your name, phone number, email address, farm or business details, location, and any information you choose to share.</li>
          <li><strong>Contract and billing information</strong> – if you purchase a system or take financing, we may collect additional details needed to process payments, credit assessments (where applicable), and service your contract.</li>
          <li><strong>Website usage data</strong> – when you visit our website, we may collect information such as your IP address, browser type, pages viewed, and the time and date of your visit. This helps us understand how our site is used and improve it.</li>
        </ul>

        <h2>3. How we use your information</h2>
        <p>We use your information to:</p>
        <ul>
          <li>Respond to your enquiries, quote requests, and support questions.</li>
          <li>Assess your needs and design appropriate solar or irrigation solutions.</li>
          <li>Provide, manage, and improve our products, services, and customer experience.</li>
          <li>Communicate important updates about your system, financing, maintenance, or warranty.</li>
          <li>Send relevant news, offers, and educational content where you have given consent or where permitted by law.</li>
          <li>Comply with legal obligations, resolve disputes, and enforce our agreements.</li>
        </ul>

        <h2>4. Legal bases for processing</h2>
        <p>Depending on your location, we may rely on one or more of the following legal bases to process your personal data:</p>
        <ul>
          <li>Your <strong>consent</strong> (for example, when you opt in to receive marketing communications).</li>
          <li><strong>Performance of a contract</strong> (for example, when we provide a financed solar solution or support under warranty).</li>
          <li>Our <strong>legitimate interests</strong> (for example, improving our services and communicating important product information) provided these are not overridden by your rights.</li>
          <li><strong>Compliance with legal obligations</strong> (for example, accounting and regulatory requirements).</li>
        </ul>

        <h2>5. Sharing your information</h2>
        <p>We do not sell your personal data. We may share it with:</p>
        <ul>
          <li><strong>Trusted service providers</strong> who help us operate our business (such as IT hosting, analytics, payment processing, or customer support). These providers are required to protect your data and only use it on our instructions.</li>
          <li><strong>Financing and banking partners</strong> where necessary to assess and manage customer financing.</li>
          <li><strong>Regulators, law enforcement, or other third parties</strong> when we are legally required to do so or to protect our rights and the safety of our customers.</li>
        </ul>

        <h2>6. Data retention</h2>
        <p>We keep your information only for as long as necessary to fulfil the purposes described in this policy, including meeting legal, accounting, or reporting requirements. When data is no longer needed, we take steps to delete or anonymise it.</p>

        <h2>7. Cookies and similar technologies</h2>
        <p>Our website uses cookies and similar technologies to provide essential functionality, remember your preferences, and help us understand how visitors use the site. For more details, including how to manage your preferences, please see our <a href="<?php echo $siteBase; ?>/cookies">Cookie Policy</a>.</p>

        <h2>8. Your rights and choices</h2>
        <p>Depending on your local law, you may have the right to:</p>
        <ul>
          <li>Access the personal information we hold about you.</li>
          <li>Request corrections to inaccurate or incomplete information.</li>
          <li>Request deletion of your information in certain circumstances.</li>
          <li>Object to or restrict certain types of processing.</li>
          <li>Withdraw consent where we rely on consent (for example, to stop receiving marketing).</li>
        </ul>
        <p>To exercise these rights, please contact us using the details below. We may need to verify your identity before responding.</p>

        <h2>9. International transfers</h2>
        <p>Our systems and service providers may be located in different countries. Where we transfer your personal data across borders, we take appropriate steps to ensure it is protected in line with this policy and applicable laws.</p>

        <h2>10. Contact us</h2>
        <p>If you have any questions about this Privacy Policy or how we handle your data, please contact us via the <a href="<?php echo $siteBase; ?>/contact">Contact page</a> or using the details provided there.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

