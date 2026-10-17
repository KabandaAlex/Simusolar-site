<?php
/**
 * Services Page - Tulima Solar
 *
 * Professional solar services overview, aligned with live services page.
 *
 * @version 4.0.0
 */

$pageTitle = 'Professional Solar Services - Tulima Solar';
$pageDescription = 'Professional solar services from Tulima Solar: consultation, survey, design, installation, training, and ongoing support for African agriculture.';
$bodyClass = 'page-services';
// Add version query to ensure latest styles load (cache-busting)
$pageCss = 'services.css?v=4';
$pageJs = 'services.js';

include __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../config/database.php';
$pdo = getDb();

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Static service categories (kept as-is, not DB-managed)
$services = [
    ['id' => 'residential', 'icon' => 'home', 'title' => 'Residential Solar', 'description' => 'Complete home solar systems tailored to your energy needs', 'features' => ['System design', 'Installation', 'Maintenance', 'Financing options'], 'link' => BASE_PATH . '/quote', 'link_text' => 'Get Quote'],
    ['id' => 'commercial', 'icon' => 'building', 'title' => 'Commercial Solar', 'description' => 'Large-scale solar solutions for businesses and organizations', 'features' => ['Energy audits', 'Commercial installation', 'Monitoring systems'], 'link' => BASE_PATH . '/services/commercial', 'link_text' => 'Learn More'],
    ['id' => 'water-pumping', 'icon' => 'droplet', 'title' => 'Solar Water Pumping', 'description' => 'Solar-powered irrigation and water pumping solutions', 'features' => ['Agricultural pumps', 'Borehole pumping', 'Water storage integration'], 'link' => BASE_PATH . '/services/water-pumping', 'link_text' => 'Learn More'],
    ['id' => 'maintenance', 'icon' => 'wrench', 'title' => 'Solar Maintenance', 'description' => 'Ongoing maintenance and repair services for solar systems', 'features' => ['System inspections', 'Cleaning', 'Repairs', 'Performance optimization'], 'link' => BASE_PATH . '/contact', 'link_text' => 'Schedule Service'],
    ['id' => 'storage', 'icon' => 'battery', 'title' => 'Energy Storage', 'description' => 'Battery storage solutions for round-the-clock power', 'features' => ['Battery banks', 'Backup systems', 'Grid integration'], 'link' => BASE_PATH . '/services/storage', 'link_text' => 'Learn More'],
    ['id' => 'consultation', 'icon' => 'clipboard', 'title' => 'Consultation & Design', 'description' => 'Expert consultation and custom system design', 'features' => ['Site assessment', 'Energy analysis', 'System sizing'], 'link' => BASE_PATH . '/contact', 'link_text' => 'Book Consultation']
];

// Default DB-backed content for services_page_content
$content = [
    'hero_heading' => 'Professional Solar Solutions That Transform Agriculture',
    'hero_subtitle' => 'From expert consultation to ongoing support, we deliver sustainable solutions that power your agricultural success with unmatched quality and reliability.',
    'hero_primary_text' => 'Get Consultation',
    'hero_primary_url' => '/contact',
    'hero_secondary_text' => 'Explore Solutions',
    'hero_secondary_url' => '/products',
    'intro_text' => 'From consultation to installation and ongoing support, we provide comprehensive solar-powered agricultural solutions tailored to your specific farming needs.',
    'services_heading' => 'End-to-End Professional Solutions',
    'services_subtitle' => 'We deliver comprehensive solar-powered agricultural solutions with professional expertise, ensuring every step of your journey is handled with precision and care.',
    'process_heading' => 'Professional Service Process',
    'process_subtitle' => 'Our proven professional methodology ensures every project is delivered with excellence, from initial consultation to ongoing support and maintenance.',
    'why_heading' => 'Why Choose Our Professional Services?',
    'why_subtitle' => 'We\'re committed to delivering exceptional service experiences with unmatched quality, expertise, and professional standards that exceed expectations.',
    'cta_heading' => 'Ready to Transform Your Farm? Experience Professional Excellence',
    'cta_subtitle' => 'Contact our professional team today for a comprehensive consultation and discover how our expert services can revolutionize your agricultural operations with cutting-edge solar technology and unwavering support.',
    'cta_primary_text' => 'Get Consultation',
    'cta_primary_url' => '/contact',
    'cta_secondary_text' => 'View Products',
    'cta_secondary_url' => '/products',
];

try {
    $stmt = $pdo->query("SELECT `key`, value FROM services_page_content");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($content[$row['key']]) && $row['value'] !== null && $row['value'] !== '') {
            $content[$row['key']] = $row['value'];
        }
    }
} catch (Throwable $e) { }

// Process steps from DB
$processSteps = [];
try {
    $rows = $pdo->query("SELECT * FROM services_page_steps WHERE is_active = 1 ORDER BY sort_order, id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $processSteps[] = [
            'step' => (int)($r['step_number'] ?? count($processSteps) + 1),
            'title' => $r['title'] ?? '',
            'description' => $r['description'] ?? '',
        ];
    }
} catch (Throwable $e) { }
if (empty($processSteps)) {
    $processSteps = [
        ['step' => 1, 'title' => 'Professional Consultation', 'description' => 'Comprehensive initial consultation with our expert team to understand your farming requirements and assess solar potential with professional analysis.'],
        ['step' => 2, 'title' => 'Expert Farm Survey', 'description' => 'Detailed on-site assessment using advanced technology to analyze your farm\'s topography, water sources, and energy requirements with professional precision.'],
        ['step' => 3, 'title' => 'Professional System Design', 'description' => 'Custom engineering of optimal solar water pumping systems tailored to your specific farm conditions, designed by certified professional engineers.'],
        ['step' => 4, 'title' => 'Expert Installation & Support', 'description' => 'Professional installation by certified technicians followed by comprehensive training and ongoing professional support services.'],
    ];
}

// Why choose us benefits from DB
$whyBenefits = [];
try {
    $whyBenefits = $pdo->query("SELECT * FROM services_page_benefits WHERE is_active = 1 ORDER BY sort_order, id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { }
if (empty($whyBenefits)) {
    $whyBenefits = [
        ['title' => 'Quality Guaranteed', 'description' => 'All our services meet and exceed international quality standards with comprehensive professional warranties and quality assurance protocols.', 'icon_class' => 'fas fa-award'],
        ['title' => 'Rapid Response', 'description' => 'Quick response times and efficient professional service delivery to minimize downtime and ensure continuous agricultural operations.', 'icon_class' => 'fas fa-clock'],
        ['title' => 'Expert Professional Team', 'description' => 'Highly experienced professionals with deep expertise in solar technology, agricultural systems, and sustainable energy solutions.', 'icon_class' => 'fas fa-users-cog'],
        ['title' => 'Local Professional Presence', 'description' => 'Strategic regional offices across Uganda with local professional teams who understand your specific agricultural and business requirements.', 'icon_class' => 'fas fa-map-marker-alt'],
        ['title' => 'Flexible Professional Financing', 'description' => 'Customized payment solutions and flexible financing options designed to make professional solar solutions accessible to all farmers.', 'icon_class' => 'fas fa-hand-holding-usd'],
        ['title' => 'Sustainable Professional Solutions', 'description' => 'Environmentally responsible solutions that protect our planet while enhancing agricultural productivity with professional sustainability standards.', 'icon_class' => 'fas fa-leaf'],
    ];
}

$faqs = [
    ['question' => 'How long does a typical solar installation take?', 'answer' => 'The installation timeline depends on the system size and complexity. A typical residential installation takes 1-3 days, while commercial projects may take 1-2 weeks. During your consultation, we\'ll provide a detailed timeline based on your specific project.'],
    ['question' => 'What warranties do you offer?', 'answer' => 'We offer comprehensive warranties including: 25-year performance warranty on solar panels, 10-year warranty on inverters, 5-year warranty on batteries, and 2-year workmanship warranty on all installations. Extended warranty options are also available.'],
    ['question' => 'Do you offer financing options?', 'answer' => 'Yes! We understand that solar is a significant investment. We offer flexible payment plans, including pay-as-you-go options, bank financing partnerships, and in-house installment plans. Our team will help you find a financing solution that fits your budget.'],
    ['question' => 'What maintenance is required for solar systems?', 'answer' => 'Solar systems require minimal maintenance. We recommend periodic cleaning of panels (especially in dusty areas), annual system inspections, and monitoring of system performance. We offer maintenance packages to keep your system running at peak efficiency.'],
    ['question' => 'Can I expand my solar system later?', 'answer' => 'Absolutely! We design our systems with future expansion in mind. Whether you want to add more panels, increase battery capacity, or integrate new technologies, our team can help you scale your system as your energy needs grow.'],
    ['question' => 'How do I know what size system I need?', 'answer' => 'During our free consultation, we analyze your energy consumption, roof space, and budget to recommend the optimal system size. We provide detailed proposals showing expected energy production and savings so you can make an informed decision.']
];

$iconPaths = [
    'home' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
    'building' => '<rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><path d="M9 22v-4h6v4"/>',
    'droplet' => '<path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>',
    'wrench' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
    'battery' => '<rect x="1" y="6" width="18" height="12" rx="2" ry="2"/><line x1="23" y1="13" x2="23" y2="11"/>',
    'clipboard' => '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M9 14l2 2 4-4"/>'
];

$siteBase = rtrim(BASE_PATH, '/');
?>

<!-- Hero -->
<header class="s-hero" id="services-hero">
    <div class="s-hero__bg" aria-hidden="true"></div>
    <div class="s-hero__container">
        <!-- <nav class="s-hero__breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo $siteBase; ?>/">Home</a>
            <span class="s-hero__breadcrumb-sep" aria-hidden="true">/</span>
            <span aria-current="page">Services</span>
        </nav> -->
        <h1 class="s-hero__title"><?php echo e($content['hero_heading']); ?></h1>
        <p class="s-hero__subtitle"><?php echo e($content['hero_subtitle']); ?></p>
        <div class="s-hero__ctas">
            <a href="<?php echo $siteBase . '/' . ltrim($content['hero_primary_url'], '/'); ?>" class="s-hero__cta"><?php echo e($content['hero_primary_text']); ?></a>
            <a href="<?php echo $siteBase . '/' . ltrim($content['hero_secondary_url'], '/'); ?>" class="s-hero__cta s-hero__cta--secondary"><?php echo e($content['hero_secondary_text']); ?></a>
        </div>
    </div>
</header>

<!-- Intro -->
<section class="s-intro" id="services-overview">
    <div class="s-intro__container">
        <p class="s-intro__lead"><?php echo e($content['intro_text']); ?></p>
    </div>
</section>

<!-- Service cards -->
<section class="s-services" id="service-categories">
    <div class="s-services__container">
        <header class="s-services__header">
            <!-- <span class="s-services__pretitle">Professional services</span> -->
            <h2 class="s-services__title"><?php echo e($content['services_heading']); ?></h2>
            <p class="s-services__subtitle"><?php echo e($content['services_subtitle']); ?></p>
        </header>
        <div class="s-services__grid">
            <?php foreach ($services as $service): ?>
                <article class="s-card" id="<?php echo e($service['id']); ?>">
                    <div class="s-card__icon">
                        <?php $d = $iconPaths[$service['icon']] ?? $iconPaths['home']; ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?php echo $d; ?></svg>
                    </div>
                    <h3 class="s-card__title"><?php echo e($service['title']); ?></h3>
                    <p class="s-card__desc"><?php echo e($service['description']); ?></p>
                    <ul class="s-card__features">
                        <?php foreach ($service['features'] as $f): ?>
                            <li><span class="s-card__check" aria-hidden="true"></span><?php echo e($f); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?php echo e($service['link']); ?>" class="s-card__link"><?php echo e($service['link_text']); ?></a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Process -->
<section class="s-process" id="our-process">
    <div class="s-process__container">
        <header class="s-process__header">
            <!-- <span class="s-process__pretitle">Service process</span> -->
            <h2 class="s-process__title"><?php echo e($content['process_heading']); ?></h2>
            <p class="s-process__subtitle"><?php echo e($content['process_subtitle']); ?></p>
        </header>
        <div class="s-process__timeline">
            <?php foreach ($processSteps as $i => $step): ?>
                <div class="s-process__step">
                    <span class="s-process__num"><?php echo $step['step']; ?></span>
                    <?php if ($i < count($processSteps) - 1): ?><span class="s-process__line" aria-hidden="true"></span><?php endif; ?>
                    <div class="s-process__content">
                        <h3 class="s-process__step-title"><?php echo e($step['title']); ?></h3>
                        <p class="s-process__step-desc"><?php echo e($step['description']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Why choose us -->
<section class="s-why" id="why-choose-us">
    <div class="s-why__container">
        <header class="s-why__header">
            <!-- <span class="s-why__pretitle">Why Tulima Solar</span> -->
            <h2 class="s-why__title"><?php echo e($content['why_heading']); ?></h2>
            <p class="s-why__subtitle"><?php echo e($content['why_subtitle']); ?></p>
        </header>
        <div class="s-why__grid">
            <?php foreach ($whyBenefits as $item): ?>
                <div class="s-why__item">
                    <div class="s-why__icon">
                        <?php if (!empty($item['icon_class'])): ?>
                            <i class="<?php echo e($item['icon_class']); ?>" aria-hidden="true"></i>
                        <?php endif; ?>
                    </div>
                    <h3 class="s-why__item-title"><?php echo e($item['title']); ?></h3>
                    <p class="s-why__item-desc"><?php echo e($item['description']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FAQ (keeps p-services-faq for existing JS) -->
<section class="s-faq" id="faq">
    <div class="s-faq__container">
        <header class="s-faq__header">
            <!-- <span class="s-faq__pretitle">Have questions?</span> -->
            <h2 class="s-faq__title">Frequently asked questions</h2>
            <p class="s-faq__subtitle">Common questions about our solar services</p>
        </header>
        <div class="p-services-faq" id="faq-accordion" role="list">
            <?php foreach ($faqs as $idx => $faq): ?>
                <div class="p-services-faq__item<?php echo $idx === 0 ? ' is-open' : ''; ?>" role="listitem">
                    <button type="button" class="p-services-faq__q" aria-expanded="<?php echo $idx === 0 ? 'true' : 'false'; ?>" aria-controls="faq-a-<?php echo $idx; ?>" id="faq-q-<?php echo $idx; ?>" data-faq-toggle>
                        <span><?php echo e($faq['question']); ?></span>
                        <span class="p-services-faq__icon" aria-hidden="true"></span>
                    </button>
                    <div class="p-services-faq__a" id="faq-a-<?php echo $idx; ?>" role="region" aria-labelledby="faq-q-<?php echo $idx; ?>" <?php echo $idx === 0 ? '' : 'hidden'; ?>>
                        <p><?php echo e($faq['answer']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="s-faq__footer">Still have questions? <a href="<?php echo $siteBase; ?>/contact">Contact our team</a></p>
    </div>
</section>

<!-- CTA -->
<section class="c-section c-section--cta" id="cta">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title"><?php echo e($content['cta_heading']); ?></h2>
            <p class="c-section__subtitle"><?php echo e($content['cta_subtitle']); ?></p>
        </div>
        <div class="c-section__actions">
            <a href="<?php echo $siteBase . '/' . ltrim($content['cta_primary_url'], '/'); ?>" class="c-btn c-btn--secondary c-btn--lg"><?php echo e($content['cta_primary_text']); ?></a>
            <a href="<?php echo $siteBase . '/' . ltrim($content['cta_secondary_url'], '/'); ?>" class="c-btn c-btn--outline-white c-btn--lg"><?php echo e($content['cta_secondary_text']); ?></a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
