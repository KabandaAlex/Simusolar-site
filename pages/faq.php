<?php
/**
 * FAQ Page - Tulima Solar
 * Dynamic FAQs loaded from the faqs table.
 */

$pageTitle = 'Frequently Asked Questions - Tulima Solar';
$pageDescription = 'Answers to common questions about Tulima Solar\'s products, services, and support.';
$bodyClass = 'page-faq';
$pageCss = 'faq.css';
$pageJs = 'faq.js';

include __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../config/database.php';
$pdo = getDb();

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$faqs = [];
try {
    $stmt = $pdo->query('SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $faqs = [];
}

$siteBase = rtrim(BASE_PATH, '/');
?>

<section class="c-page-header">
  <div class="c-page-header__container">
    <nav class="c-page-header__breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo $siteBase; ?>/">Home</a>
      <span class="c-page-header__breadcrumb-separator" aria-hidden="true">/</span>
      <span aria-current="page">FAQs</span>
    </nav>
    <h1 class="c-page-header__title">Frequently Asked Questions</h1>
    <p class="c-section__subtitle">Quick answers about our solar solutions, financing, and support.</p>
  </div>
</section>

<section class="c-section c-section--white p-faq">
  <div class="c-section__container">
    <div class="p-faq__intro">
      <p>Browse the most common questions from our customers. If you can’t find what you’re looking for, our team is happy to help.</p>
    </div>

    <!-- Anchor targets for quick links from Contact page -->
    <div id="residential"></div>
    <div id="commercial"></div>
    <div id="financing"></div>
    <div id="maintenance"></div>

    <div class="p-faq__accordion" id="faq-accordion" role="list">
      <?php if (empty($faqs)):
        $faqs = [
          ['id' => 1, 'question' => 'What types of solar solutions does Tulima Solar provide?', 'answer' => 'We design and install complete solar-powered solutions for irrigation, water pumping, agro-processing, and household or business power. Our team helps you choose the right system for your farm, home, or enterprise.'],
          ['id' => 2, 'question' => 'Which regions do you serve?', 'answer' => 'Tulima Solar serves customers across East Africa through local offices and field teams. Contact us with your location and we will confirm service availability and the nearest support office.'],
          ['id' => 3, 'question' => 'Do you offer financing?', 'answer' => 'Yes. We work with financing partners to offer flexible payment plans, so you can spread the cost of your solar system over time. Eligibility and terms depend on your location and project.'],
          ['id' => 4, 'question' => 'How long does installation take?', 'answer' => 'Most solar irrigation and water pumping systems are installed within 1–3 days after the system design is approved and payment or financing is confirmed. Larger commercial systems may take longer.'],
          ['id' => 5, 'question' => 'What kind of maintenance is required?', 'answer' => 'Solar systems require minimal maintenance. We recommend periodic cleaning of panels, basic visual checks, and scheduled service visits where required. Our team can advise on a maintenance plan for your system.'],
          ['id' => 6, 'question' => 'What warranties do you offer?', 'answer' => 'We provide equipment and workmanship warranties on eligible systems, backed by our local service teams. Specific warranty terms depend on the products and package you choose.'],
        ];
      ?>
        <?php foreach ($faqs as $idx => $faq): ?>
          <div class="p-services-faq__item<?php echo $idx === 0 ? ' is-open' : ''; ?>" role="listitem">
            <button
              type="button"
              class="p-services-faq__q"
              aria-expanded="<?php echo $idx === 0 ? 'true' : 'false'; ?>"
              aria-controls="faq-a-<?php echo (int)$faq['id']; ?>"
              id="faq-q-<?php echo (int)$faq['id']; ?>"
              data-faq-toggle
            >
              <span><?php echo e($faq['question']); ?></span>
              <span class="p-services-faq__icon" aria-hidden="true"></span>
            </button>
            <div
              class="p-services-faq__a"
              id="faq-a-<?php echo (int)$faq['id']; ?>"
              role="region"
              aria-labelledby="faq-q-<?php echo (int)$faq['id']; ?>"
              <?php echo $idx === 0 ? '' : 'hidden'; ?>
            >
              <p><?php echo nl2br(e($faq['answer'])); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="c-section__footer" style="margin-top: var(--spacing-8); text-align: center;">
      <p style="margin-bottom: 1rem;">Still have questions?</p>
      <a href="<?php echo $siteBase; ?>/contact" class="c-btn c-btn--primary">Contact Our Team →</a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

