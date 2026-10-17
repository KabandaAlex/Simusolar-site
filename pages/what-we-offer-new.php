<?php
/**
 * What We Offer Page - Tulima Solar
 *
 * Mirrors live page: https://test.tulimasolar.com/what-we-offer/
 * Sections: hero, services, product categories, why choose us, financing, CTA.
 * Content from what_we_offer_page_content; section items from what_we_offer_* tables.
 *
 * @version 1.0.0
 */

$pageTitle = 'What We Offer - Tulima Solar';
$pageDescription = 'Discover Tulima Solar\'s professional solar-powered solutions for modern African farming – services, product categories, financing, and more.';
$bodyClass = 'page-what-we-offer';
$pageCss = 'what-we-offer.css?v=1';

include __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../config/database.php';
$pdo = getDb();

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$siteBase = rtrim(BASE_PATH, '/');

// Default page content (matches migration seeds)
$content = [
    'hero_heading' => 'What We Offer – Professional Solar Solutions',
    'hero_subtitle' => 'Comprehensive solar-powered solutions designed for modern African farming. From consultation to installation, we provide end-to-end professional services that transform agricultural productivity.',
    'hero_primary_text' => 'Get Consultation',
    'hero_primary_url' => '/contact',
    'hero_secondary_text' => 'View Products',
    'hero_secondary_url' => '/products',
    'services_heading' => 'Our Services',
    'services_subtitle' => 'Professional End-to-End Solutions',
    'services_intro' => 'From consultation to installation and ongoing support, we provide comprehensive services tailored to your specific farming needs.',
    'products_heading' => 'Product Categories',
    'products_subtitle' => 'Professional Product Range',
    'products_intro' => 'Comprehensive range of solar-powered agricultural equipment designed for maximum efficiency and reliability.',
    'why_heading' => 'Why Choose Simusolar?',
    'why_subtitle' => 'We\'re committed to providing the best professional solar solutions for African farmers with unmatched quality and service.',
    'why_stat_value' => '500+',
    'why_stat_label' => 'Happy Farmers',
    'financing_heading' => 'Flexible Professional Financing',
    'financing_subtitle' => 'Making solar power accessible to all farmers with professional financing solutions.',
    'cta_heading' => 'Ready to Transform Your Farm with Solar?',
    'cta_subtitle' => 'Join thousands of successful farmers across East Africa who have already revolutionized their agricultural operations with our professional solar solutions. Get your free consultation and personalized quote today.',
    'cta_feature_1' => 'Free Consultation',
    'cta_feature_2' => 'Custom Solar Solutions',
    'cta_feature_3' => 'Installation Support',
    'cta_feature_4' => 'Ongoing Maintenance',
    'cta_primary_text' => 'Get Started Today',
    'cta_primary_url' => '/contact',
    'cta_secondary_text' => 'Calculate Savings',
    'cta_secondary_url' => '/products#calculator',
];

try {
    $stmt = $pdo->query("SELECT `key`, value FROM what_we_offer_page_content");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($content[$row['key']]) && $row['value'] !== null && $row['value'] !== '') {
            $content[$row['key']] = $row['value'];
        }
    }
} catch (Throwable $e) {
    // use defaults
}

// Load section items
$services = [];
$products = [];
$benefits = [];
$financing = [];

try {
    $services = $pdo->query("SELECT * FROM what_we_offer_services WHERE is_active = 1 ORDER BY sort_order, id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { }

try {
    $products = $pdo->query("SELECT * FROM what_we_offer_products WHERE is_active = 1 ORDER BY sort_order, id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { }

try {
    $benefits = $pdo->query("SELECT * FROM what_we_offer_benefits WHERE is_active = 1 ORDER BY sort_order, id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { }

try {
    $financing = $pdo->query("SELECT * FROM what_we_offer_financing WHERE is_active = 1 ORDER BY sort_order, id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { }

// Fallbacks if tables are empty
if (empty($services)) {
    $services = [
        ['title' => 'Professional Farm Survey', 'description' => 'Our certified experts conduct comprehensive assessments using advanced technology to analyze your farm\'s water needs, terrain characteristics, and crop requirements with professional precision.', 'icon_class' => 'fas fa-search'],
        ['title' => 'Expert System Design', 'description' => 'Custom-engineered solar water pumping systems optimized for your specific farm conditions, designed by professional engineers with decades of experience in solar technology.', 'icon_class' => 'fas fa-cogs'],
        ['title' => 'Professional Installation', 'description' => 'Expert installation by certified technicians following international standards, ensuring optimal performance, system longevity, and professional quality assurance.', 'icon_class' => 'fas fa-tools'],
        ['title' => 'Professional Training', 'description' => 'Comprehensive training programs covering system operation, maintenance protocols, and troubleshooting procedures delivered by certified professional instructors.', 'icon_class' => 'fas fa-graduation-cap'],
        ['title' => '24/7 Professional Support', 'description' => 'Round-the-clock customer support and technical assistance through our dedicated professional service team, ensuring minimal downtime and maximum productivity.', 'icon_class' => 'fas fa-headset'],
        ['title' => 'Comprehensive Warranty', 'description' => 'Extended warranty coverage with regular professional maintenance services, ensuring optimal performance and providing peace of mind for your investment.', 'icon_class' => 'fas fa-shield-alt'],
    ];
}

if (empty($products)) {
    $products = [
        [
            'name' => 'Solar Water Pumps',
            'description' => 'Efficient solar-powered water pumps for irrigation, livestock, and aquaculture applications with professional engineering.',
            'bullet_points' => "Complete Systems\nSubmersible Pumps\nSurface Pumps",
            'primary_cta_label' => 'Contact',
            'primary_cta_url' => '/contact',
            'secondary_cta_label' => 'Learn More',
            'secondary_cta_url' => '/solar-water-pumps',
            'icon_class' => 'fas fa-water',
        ],
        [
            'name' => 'Agricultural Processing Machines',
            'description' => 'Solar-powered machines for post-harvest processing and value addition with professional quality standards.',
            'bullet_points' => "Processing Units\nOil Presses\nGrain Mills",
            'primary_cta_label' => null,
            'primary_cta_url' => null,
            'secondary_cta_label' => 'Learn More',
            'secondary_cta_url' => '/agricultural-processing-machines',
            'icon_class' => 'fas fa-industry',
        ],
        [
            'name' => 'Solar Panel Systems',
            'description' => 'Complete solar panel arrays and mounting systems for optimal energy production with professional installation.',
            'bullet_points' => "Control Systems\nMounting Structures\nSolar Panels",
            'primary_cta_label' => null,
            'primary_cta_url' => null,
            'secondary_cta_label' => 'Learn More',
            'secondary_cta_url' => '/products',
            'icon_class' => 'fas fa-solar-panel',
        ],
    ];
}

if (empty($benefits)) {
    $benefits = [
        ['title' => 'Quality Assurance', 'description' => 'All our products meet international quality standards and come with comprehensive professional warranties.', 'icon_class' => 'fas fa-award'],
        ['title' => 'Expert Team', 'description' => 'Experienced professionals with deep knowledge of solar technology and African agriculture.', 'icon_class' => 'fas fa-users-cog'],
        ['title' => 'Local Presence', 'description' => 'Regional offices across Uganda with local teams who understand your specific professional needs.', 'icon_class' => 'fas fa-map-marker-alt'],
        ['title' => 'Quick Service', 'description' => 'Fast response times and efficient professional service delivery to minimize downtime on your farm.', 'icon_class' => 'fas fa-clock'],
    ];
}

if (empty($financing)) {
    $financing = [
        ['title' => '22-Month Financing', 'description' => 'Spread the cost over 22 months with affordable monthly payments and professional terms.', 'icon_class' => 'fas fa-calendar-alt'],
        ['title' => 'Mobile Money Payments', 'description' => 'Convenient mobile money payments with no additional charges for professional convenience.', 'icon_class' => 'fas fa-mobile-alt'],
        ['title' => 'Equipment as Collateral', 'description' => 'The solar water pump serves as collateral, making professional financing accessible to all.', 'icon_class' => 'fas fa-balance-scale'],
    ];
}

// Helper to resolve URLs (absolute for external, BASE_PATH-relative otherwise)
$resolveUrl = function ($url) use ($siteBase) {
    if (empty($url)) return $siteBase . '/';
    if (preg_match('/^https?:\\/\\//', $url)) return $url;
    return $siteBase . '/' . ltrim($url, '/');
};
?>

<!-- Hero -->
<header class="s-hero" id="wwo-hero">
    <div class="s-hero__bg" aria-hidden="true"></div>
    <div class="s-hero__container">
        <!-- <nav class="s-hero__breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo $siteBase; ?>/">Home</a>
            <span class="s-hero__breadcrumb-sep" aria-hidden="true">/</span>
            <span aria-current="page">What We Offer</span>
        </nav> -->
        <h1 class="s-hero__title"><?php echo e($content['hero_heading']); ?></h1>
        <p class="s-hero__subtitle"><?php echo e($content['hero_subtitle']); ?></p>
        <div class="s-hero__ctas">
            <a href="<?php echo e($resolveUrl($content['hero_primary_url'])); ?>" class="s-hero__cta"><?php echo e($content['hero_primary_text']); ?></a>
            <a href="<?php echo e($resolveUrl($content['hero_secondary_url'])); ?>" class="s-hero__cta s-hero__cta--secondary"><?php echo e($content['hero_secondary_text']); ?></a>
        </div>
    </div>
</header>

<!-- Our Services -->
<section class="wwo-section wwo-section--services" id="services">
    <div class="wwo-section__container">
        <header class="wwo-section__header">
            <!-- <span class="wwo-section__pretitle">Our Services</span> -->
            <h2 class="wwo-section__title"><?php echo e($content['services_heading']); ?></h2>
            <p class="wwo-section__subtitle"><?php echo e($content['services_subtitle']); ?></p>
            <?php if (!empty($content['services_intro'])): ?>
                <p class="wwo-section__intro"><?php echo e($content['services_intro']); ?></p>
            <?php endif; ?>
        </header>
        <div class="wwo-services__grid">
            <?php foreach ($services as $service): ?>
                <article class="wwo-service-card">
                    <?php if (!empty($service['icon_class'])): ?>
                        <div class="wwo-service-card__icon">
                            <i class="<?php echo e($service['icon_class']); ?>" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>
                    <h3 class="wwo-service-card__title"><?php echo e($service['title']); ?></h3>
                    <p class="wwo-service-card__desc"><?php echo e($service['description']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Product Categories -->
<section class="wwo-section wwo-section--products" id="products">
    <div class="wwo-section__container">
        <header class="wwo-section__header">
            <!-- <span class="wwo-section__pretitle">Product Categories</span> -->
            <h2 class="wwo-section__title"><?php echo e($content['products_heading']); ?></h2>
            <p class="wwo-section__subtitle"><?php echo e($content['products_subtitle']); ?></p>
            <?php if (!empty($content['products_intro'])): ?>
                <p class="wwo-section__intro"><?php echo e($content['products_intro']); ?></p>
            <?php endif; ?>
        </header>
        <div class="wwo-products__grid">
            <?php foreach ($products as $product): ?>
                <article class="wwo-product-card">
                    <?php if (!empty($product['icon_class'])): ?>
                        <div class="wwo-product-card__icon">
                            <i class="<?php echo e($product['icon_class']); ?>" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>
                    <h3 class="wwo-product-card__name"><?php echo e($product['name']); ?></h3>
                    <p class="wwo-product-card__desc"><?php echo e($product['description']); ?></p>
                    <?php
                    $bullets = [];
                    if (!empty($product['bullet_points'])) {
                        $bullets = array_filter(array_map('trim', explode("\n", $product['bullet_points'])));
                    }
                    ?>
                    <?php if (!empty($bullets)): ?>
                        <ul class="wwo-product-card__list">
                            <?php foreach ($bullets as $b): ?>
                                <li><i class="fas fa-check-circle" aria-hidden="true"></i> <?php echo e($b); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="wwo-product-card__actions">
                        <?php if (!empty($product['primary_cta_label']) && !empty($product['primary_cta_url'])): ?>
                            <a href="<?php echo e($resolveUrl($product['primary_cta_url'])); ?>" class="c-btn c-btn--secondary c-btn--sm"><?php echo e($product['primary_cta_label']); ?></a>
                        <?php endif; ?>
                        <?php if (!empty($product['secondary_cta_label']) && !empty($product['secondary_cta_url'])): ?>
                            <a href="<?php echo e($resolveUrl($product['secondary_cta_url'])); ?>" class="c-btn c-btn--ghost c-btn--sm"><?php echo e($product['secondary_cta_label']); ?></a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="wwo-section wwo-section--why" id="why-tulima">
    <div class="wwo-section__container">
        <header class="wwo-section__header wwo-section__header--two-col">
            <div>
                <!-- <span class="wwo-section__pretitle">Why Choose Us</span> -->
                <h2 class="wwo-section__title"><?php echo e($content['why_heading']); ?></h2>
                <p class="wwo-section__subtitle"><?php echo e($content['why_subtitle']); ?></p>
            </div>
            <div class="wwo-stat">
                <p class="wwo-stat__value"><?php echo e($content['why_stat_value']); ?></p>
                <p class="wwo-stat__label"><?php echo e($content['why_stat_label']); ?></p>
            </div>
        </header>
        <div class="wwo-why__grid">
            <?php foreach ($benefits as $benefit): ?>
                <article class="wwo-why-card">
                    <?php if (!empty($benefit['icon_class'])): ?>
                        <div class="wwo-why-card__icon">
                            <i class="<?php echo e($benefit['icon_class']); ?>" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>
                    <h3 class="wwo-why-card__title"><?php echo e($benefit['title']); ?></h3>
                    <p class="wwo-why-card__desc"><?php echo e($benefit['description']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Financing Options -->
<section class="wwo-section wwo-section--financing" id="financing">
    <div class="wwo-section__container">
        <header class="wwo-section__header">
            <!-- <span class="wwo-section__pretitle">Financing Options</span> -->
            <h2 class="wwo-section__title"><?php echo e($content['financing_heading']); ?></h2>
            <p class="wwo-section__subtitle"><?php echo e($content['financing_subtitle']); ?></p>
        </header>
        <div class="wwo-financing__grid">
            <?php foreach ($financing as $item): ?>
                <article class="wwo-financing-card">
                    <?php if (!empty($item['icon_class'])): ?>
                        <div class="wwo-financing-card__icon">
                            <i class="<?php echo e($item['icon_class']); ?>" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>
                    <h3 class="wwo-financing-card__title"><?php echo e($item['title']); ?></h3>
                    <p class="wwo-financing-card__desc"><?php echo e($item['description']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Final CTA -->
<section class="c-section c-section--cta" id="wwo-cta">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title"><?php echo e($content['cta_heading']); ?></h2>
            <p class="c-section__subtitle"><?php echo e($content['cta_subtitle']); ?></p>
        </div>
        <div class="wwo-cta__features">
            <?php if (!empty($content['cta_feature_1'])): ?><span><?php echo e($content['cta_feature_1']); ?></span><?php endif; ?>
            <?php if (!empty($content['cta_feature_2'])): ?><span><?php echo e($content['cta_feature_2']); ?></span><?php endif; ?>
            <?php if (!empty($content['cta_feature_3'])): ?><span><?php echo e($content['cta_feature_3']); ?></span><?php endif; ?>
            <?php if (!empty($content['cta_feature_4'])): ?><span><?php echo e($content['cta_feature_4']); ?></span><?php endif; ?>
        </div>
        <div class="c-section__actions">
            <a href="<?php echo e($resolveUrl($content['cta_primary_url'])); ?>" class="c-btn c-btn--secondary c-btn--lg"><?php echo e($content['cta_primary_text']); ?></a>
            <a href="<?php echo e($resolveUrl($content['cta_secondary_url'])); ?>" class="c-btn c-btn--outline-white c-btn--lg"><?php echo e($content['cta_secondary_text']); ?></a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

