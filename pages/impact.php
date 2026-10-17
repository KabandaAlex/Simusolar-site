<?php
/**
 * Impact Page - Simusolar
 *
 * Transforming farming and livelihoods: stats, testimonials, environmental impact,
 * journey, community impact. Content from DB; styling aligned with about/services.
 *
 * @version 1.0.0
 */

$pageTitle = 'Our Impact - Simusolar';
$pageDescription = 'See how Simusolar is transforming farming and livelihoods across Africa through solar power. Statistics, success stories, and environmental impact.';
$bodyClass = 'page-impact';
$pageCss = 'impact.css?v=3';

include __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../config/database.php';
$pdo = getDb();

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Load key-value content (hero, section titles, CTAs, community bullets)
$content = [
    'hero_heading' => 'Transforming Farming and Livelihoods Across Africa',
    'hero_subtitle' => 'Through solar power, we\'ve created lasting positive change in communities, economies, and the environment.',
    'hero_cta_text' => 'View Statistics',
    'hero_cta_url' => '#impact-numbers',
    'hero_cta_secondary_text' => 'Success Stories',
    'hero_cta_secondary_url' => '#testimonials',
    'stats_heading' => 'Our Impact in Numbers',
    'stats_subtitle' => 'Since 2016, we\'ve made a significant difference in the lives of African farmers',
    'testimonials_heading' => 'What Our Clients Say',
    'testimonials_subtitle' => 'Hear from farmers and agricultural professionals who trust Simusolar for their sustainable energy solutions',
    'join_heading' => 'Join Our Success Stories',
    'join_subtitle' => 'Be part of the transformation. Start your journey with solar-powered farming today.',
    'join_primary_text' => 'Get Started',
    'join_primary_url' => '/contact',
    'join_secondary_text' => 'Solutions',
    'join_secondary_url' => '/products',
    'environmental_heading' => 'Environmental Impact',
    'environmental_subtitle' => 'Our solar solutions are helping protect the environment while improving livelihoods',
    'offices_heading' => 'Our Office Locations',
    'offices_subtitle' => 'Find our branches and get directions to any of our offices',
    'offices_intro' => 'With regional offices across Uganda and East Africa, we bring solar solutions and after-sales support closer to farmers. Our presence on the ground helps us understand local needs, deliver installations, and provide training and maintenance—demonstrating our commitment to lasting impact in the communities we serve.',
    'community_heading' => 'Community Impact',
    'community_subtitle' => 'How solar power is transforming communities across Africa',
    'economic_benefits' => "Increased farm productivity by up to 300%\nReduced operating costs by eliminating fuel expenses\nCreated new income opportunities through year-round farming\nImproved market access with consistent crop production",
    'social_benefits' => "Reduced manual labor, especially for women and children\nImproved food security and nutrition\nEnhanced education opportunities with increased income\nStrengthened community resilience to climate change",
    'journey_heading' => 'Our Journey',
    'journey_subtitle' => 'Key milestones in our mission to transform African agriculture',
    'cta_heading' => 'Be Part of Our Impact',
    'cta_subtitle' => 'Join thousands of farmers who have already transformed their lives with solar power.',
    'cta_primary_text' => 'Get Started Today',
    'cta_primary_url' => '/contact',
    'cta_secondary_text' => 'View Our Solutions',
    'cta_secondary_url' => '/products',
];
try {
    $stmt = $pdo->query("SELECT `key`, value FROM impact_page_content");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($content[$row['key']]) && $row['value'] !== null && $row['value'] !== '') {
            $content[$row['key']] = $row['value'];
        }
    }
} catch (Exception $e) { /* use defaults */ }

// Stats (impact_numbers) – same data shape as home for shared section markup
$impactStats = [];
try {
    $stmt = $pdo->query("SELECT * FROM impact_numbers WHERE is_active = 1 ORDER BY sort_order ASC");
    $impactStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }
if (empty($impactStats)) {
    $impactStats = [
        ['value' => '0', 'label' => 'Farmers Empowered', 'sublabel' => 'Lives transformed through solar power', 'icon' => 'users'],
        ['value' => '0', 'label' => 'Acres Irrigated', 'sublabel' => 'Land brought to life sustainably', 'icon' => 'leaf'],
        ['value' => '0%', 'label' => 'Income Increase', 'sublabel' => 'Average boost in farmer earnings', 'icon' => 'zap'],
        ['value' => '0', 'label' => 'Regional Offices', 'sublabel' => 'Serving communities across Africa', 'icon' => 'globe'],
    ];
}

// Testimonials (from database, same as home page)
$testimonials = [];
try {
    $stmt = $pdo->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC");
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }
if (empty($testimonials)) {
    $testimonials = [
        ['quote' => 'Opio Tonny, the owner of Otobai Investment Co. Ltd Tree Nursery in Lira City, Northern Uganda, found a solution to his irrigation challenges with a solar water pump from Simusolar.', 'author' => 'Opio Tonny', 'role' => 'Farmer', 'location' => ''],
        ['quote' => 'Rebecca Wakesa installed a solar water pump from Simusolar. This new irrigation system drastically cut her previous expenses, allowing her to invest the savings back into her business.', 'author' => 'Rebecca Wakesa', 'role' => 'Farmer', 'location' => ''],
    ];
}

// Environmental impact items
$environmental = [];
try {
    $stmt = $pdo->query("SELECT title, description FROM impact_environmental WHERE is_active = 1 ORDER BY sort_order ASC");
    $environmental = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }
if (empty($environmental)) {
    $environmental = [
        ['title' => 'Carbon Reduction', 'description' => 'Reduced carbon emissions by replacing fuel-powered pumps with clean solar energy.'],
        ['title' => 'Water Conservation', 'description' => 'Efficient irrigation systems that conserve water while maximizing crop yields.'],
        ['title' => 'Sustainable Farming', 'description' => 'Promoting sustainable agricultural practices that protect soil and biodiversity.'],
        ['title' => 'Renewable Energy', 'description' => 'Harnessing abundant African sunshine for clean, renewable energy solutions.'],
    ];
}

// Office locations (include lat/lng for map pins)
$offices = [];
try {
    $stmt = $pdo->query("SELECT name, address, phone, email, region_or_city, map_url, latitude, longitude FROM office_locations WHERE is_active = 1 ORDER BY sort_order ASC");
    $offices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }
$officesWithCoords = array_filter($offices, function ($o) {
    return isset($o['latitude']) && isset($o['longitude']) && $o['latitude'] !== null && $o['longitude'] !== null && $o['latitude'] !== '' && $o['longitude'] !== '';
});

// Journey milestones
$journey = [];
try {
    $stmt = $pdo->query("SELECT year, title, description FROM impact_journey WHERE is_active = 1 ORDER BY sort_order ASC");
    $journey = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }
if (empty($journey)) {
    $journey = [
        ['year' => '2019', 'title' => 'Company Founded', 'description' => 'Simusolar was established with a mission to empower African farmers with solar solutions.'],
        ['year' => '2020', 'title' => 'First 100 Farmers', 'description' => 'Reached our first milestone of 100 farmers using solar-powered irrigation systems.'],
        ['year' => '2021', 'title' => 'Regional Expansion', 'description' => 'Opened offices in Lira and Obongi to serve farmers across Uganda.'],
        ['year' => '2022', 'title' => '1000+ Farmers', 'description' => 'Celebrated reaching over 1000 farmers with solar-powered solutions.'],
        ['year' => '2023', 'title' => '5000+ Farmers', 'description' => 'Reached 5000+ farmers and expanded product range to include processing machines.'],
        ['year' => '2024', 'title' => 'Future Vision', 'description' => 'Continuing to expand and innovate to serve more farmers across Africa.'],
    ];
}

$economicBullets = array_filter(array_map('trim', explode("\n", $content['economic_benefits'])));
$socialBullets = array_filter(array_map('trim', explode("\n", $content['social_benefits'])));
$siteBase = rtrim(BASE_PATH, '/');
?>

<header class="s-hero" id="impact-hero">
    <div class="s-hero__bg" aria-hidden="true"></div>
    <div class="s-hero__container">
        <!-- <nav class="s-hero__breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo $siteBase; ?>/">Home</a>
            <span class="s-hero__breadcrumb-sep" aria-hidden="true">/</span>
            <span aria-current="page">Our Impact</span>
        </nav> -->
        <h1 class="s-hero__title"><?php echo e($content['hero_heading']); ?></h1>
        <p class="s-hero__subtitle"><?php echo e($content['hero_subtitle']); ?></p>
        <div class="s-hero__ctas">
            <?php
            $heroUrl1 = $content['hero_cta_url'];
            $heroUrl2 = $content['hero_cta_secondary_url'];
            if ($heroUrl1 && $heroUrl1[0] !== '#' && strpos($heroUrl1, '://') === false) { $heroUrl1 = $siteBase . '/' . ltrim($heroUrl1, '/'); }
            if ($heroUrl2 && $heroUrl2[0] !== '#' && strpos($heroUrl2, '://') === false) { $heroUrl2 = $siteBase . '/' . ltrim($heroUrl2, '/'); }
            ?>
            <a href="<?php echo e($heroUrl1); ?>" class="s-hero__cta"><?php echo e($content['hero_cta_text']); ?></a>
            <a href="<?php echo e($heroUrl2); ?>" class="s-hero__cta s-hero__cta--secondary"><?php echo e($content['hero_cta_secondary_text']); ?></a>
        </div>
    </div>
</header>

<section class="c-section c-section--impact" id="impact-numbers" aria-labelledby="impact-numbers-heading">
    <div class="c-section__container">
        <header class="c-section__header c-impact__header">
            <!-- <span class="c-section__pretitle c-impact__pretitle">By the numbers</span> -->
            <h2 class="c-section__title c-impact__title" id="impact-numbers-heading"><?php echo e($content['stats_heading']); ?></h2>
            <p class="c-section__subtitle c-impact__subtitle"><?php echo e($content['stats_subtitle']); ?></p>
        </header>
        <div class="c-impact__grid">
            <?php foreach ($impactStats as $index => $stat):
                $rawValue = trim($stat['value'] ?? '');
                if (isset($stat['suffix']) && $stat['suffix'] !== '') {
                    $impactCount = preg_replace('/[^0-9]/', '', $rawValue) ?: '0';
                    $impactSuffix = $stat['suffix'];
                } else {
                    if (preg_match('/^([\d,\s]*)(.*)$/u', $rawValue, $m)) {
                        $impactCount = preg_replace('/[^0-9]/', '', $m[1]) ?: '0';
                        $impactSuffix = isset($m[2]) ? trim($m[2]) : '';
                    } else {
                        $impactCount = '0';
                        $impactSuffix = '';
                    }
                }
                $icon = $stat['icon'] ?? '';
                $iconSvg = '';
                switch (strtolower($icon)) {
                    case 'home':
                    case 'house':
                        $iconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>';
                        break;
                    case 'leaf':
                    case 'eco':
                    case 'acres':
                        $iconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>';
                        break;
                    case 'globe':
                    case 'world':
                    case 'offices':
                        $iconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>';
                        break;
                    case 'users':
                    case 'people':
                    case 'farmers':
                        $iconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
                        break;
                    case 'sun':
                    case 'solar':
                        $iconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
                        break;
                    case 'zap':
                    case 'bolt':
                    case 'income':
                        $iconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>';
                        break;
                    case 'award':
                    case 'trophy':
                        $iconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>';
                        break;
                    case 'shield':
                    case 'security':
                        $iconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
                        break;
                    case 'calendar':
                        $iconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
                        break;
                    case 'handshake':
                        $iconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
                        break;
                    default:
                        $iconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
                }
            ?>
                <article class="c-impact-card" data-index="<?php echo (int)$index; ?>">
                    <div class="c-impact-card__icon-wrap"><?php echo $iconSvg; ?></div>
                    <p class="c-impact-card__value" data-count="<?php echo e($impactCount); ?>" data-suffix="<?php echo e($impactSuffix); ?>"><?php echo e($rawValue !== '' ? $rawValue : $impactCount . $impactSuffix); ?></p>
                    <h3 class="c-impact-card__label"><?php echo e($stat['label'] ?? ''); ?></h3>
                    <?php if (!empty($stat['sublabel']) || !empty($stat['description'])): ?>
                        <p class="c-impact-card__sublabel"><?php echo e($stat['sublabel'] ?? $stat['description'] ?? ''); ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="c-section c-section--testimonials" id="testimonials">
    <div class="c-section__container">
        <header class="c-section__header">
            <!-- <span class="c-section__pretitle">Success stories</span> -->
            <h2 class="c-section__title"><?php echo e($content['testimonials_heading']); ?></h2>
            <p class="c-section__subtitle"><?php echo e($content['testimonials_subtitle']); ?></p>
        </header>
        <?php if (!empty($testimonials)): ?>
        <div class="c-testimonials-slider" id="impact-testimonials-slider">
            <div class="c-testimonials-track">
                <?php foreach ($testimonials as $index => $testimonial): ?>
                    <article class="c-testimonial<?php echo $index === 0 ? ' is-active' : ''; ?>">
                        <div class="c-testimonial__content">
                            <svg class="c-testimonial__quote-icon" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                            </svg>
                            <blockquote class="c-testimonial__quote">
                                <?php echo e($testimonial['quote'] ?? $testimonial['content'] ?? ''); ?>
                            </blockquote>
                        </div>
                        <footer class="c-testimonial__footer">
                            <div class="c-testimonial__author">
                                <span class="c-testimonial__name"><?php echo e($testimonial['author'] ?? 'Anonymous'); ?></span>
                                <span class="c-testimonial__role"><?php echo e($testimonial['role'] ?? ''); ?></span>
                                <?php if (!empty($testimonial['location'])): ?>
                                    <span class="c-testimonial__location"><?php echo e($testimonial['location']); ?></span>
                                <?php endif; ?>
                            </div>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="c-testimonials__controls">
                <button type="button" class="c-testimonials__prev" aria-label="Previous testimonial">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <div class="c-testimonials__dots">
                    <?php foreach ($testimonials as $index => $t): ?>
                        <button type="button" class="c-testimonials__dot<?php echo $index === 0 ? ' is-active' : ''; ?>" data-index="<?php echo $index; ?>" aria-label="Go to testimonial <?php echo $index + 1; ?>"></button>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="c-testimonials__next" aria-label="Next testimonial">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </button>
            </div>
        </div>
        <div class="i-testimonials__cta">
            <h3 class="i-testimonials__cta-heading"><?php echo e($content['join_heading']); ?></h3>
            <p class="i-testimonials__cta-subtitle"><?php echo e($content['join_subtitle']); ?></p>
            <?php
            $joinUrl1 = $siteBase . '/' . ltrim($content['join_primary_url'], '/');
            $joinUrl2 = $siteBase . '/' . ltrim($content['join_secondary_url'], '/');
            ?>
            <div class="i-testimonials__cta-btns">
                <a href="<?php echo e($joinUrl1); ?>" class="c-btn c-btn--primary"><?php echo e($content['join_primary_text']); ?></a>
                <a href="<?php echo e($joinUrl2); ?>" class="c-btn c-btn--secondary"><?php echo e($content['join_secondary_text']); ?></a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="i-environmental">
    <div class="i-environmental__container">
        <header class="i-environmental__header">
            <!-- <span class="i-environmental__pretitle">Green impact</span> -->
            <h2 class="i-environmental__title"><?php echo e($content['environmental_heading']); ?></h2>
            <p class="i-environmental__subtitle"><?php echo e($content['environmental_subtitle']); ?></p>
        </header>
        <div class="i-environmental__grid">
            <?php foreach ($environmental as $item): ?>
                <div class="i-env-card">
                    <h3 class="i-env-card__title"><?php echo e($item['title']); ?></h3>
                    <p class="i-env-card__desc"><?php echo e($item['description']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($offices)): ?>
<section class="i-offices" id="offices">
    <div class="i-offices__container">
        <header class="i-offices__header">
            <!-- <span class="i-offices__pretitle">Locations</span> -->
            <h2 class="i-offices__title"><?php echo e($content['offices_heading']); ?></h2>
            <p class="i-offices__subtitle"><?php echo e($content['offices_subtitle']); ?></p>
        </header>
        <?php if (!empty($content['offices_intro'])): ?>
        <div class="i-offices__intro">
            <p class="i-offices__intro-text"><?php echo nl2br(e($content['offices_intro'])); ?></p>
        </div>
        <?php endif; ?>
        <?php if (!empty($officesWithCoords)): ?>
        <div class="i-offices__map-wrap">
            <div id="impact-offices-map" class="i-offices__map" data-offices="<?php echo htmlspecialchars(json_encode(array_values(array_map(function ($o) {
                return ['name' => $o['name'], 'lat' => (float)$o['latitude'], 'lng' => (float)$o['longitude'], 'address' => $o['address'] ?? '', 'map_url' => $o['map_url'] ?? ''];
            }, $officesWithCoords))), ENT_QUOTES, 'UTF-8'); ?>"></div>
        </div>
        <?php endif; ?>
        <div class="i-offices__grid">
            <?php foreach ($offices as $office): ?>
                <div class="i-office-card">
                    <h3 class="i-office-card__name"><?php echo e($office['name']); ?></h3>
                    <?php if (!empty($office['region_or_city'])): ?><p class="i-office-card__region"><?php echo e($office['region_or_city']); ?></p><?php endif; ?>
                    <?php if (!empty($office['address'])): ?><p class="i-office-card__address"><?php echo nl2br(e($office['address'])); ?></p><?php endif; ?>
                    <?php if (!empty($office['phone'])): ?><p class="i-office-card__phone"><a href="tel:<?php echo e(preg_replace('/\s+/', '', $office['phone'])); ?>"><?php echo e($office['phone']); ?></a></p><?php endif; ?>
                    <?php if (!empty($office['email'])): ?><p class="i-office-card__email"><a href="mailto:<?php echo e($office['email']); ?>"><?php echo e($office['email']); ?></a></p><?php endif; ?>
                    <?php if (!empty($office['map_url'])): ?><a href="<?php echo e($office['map_url']); ?>" target="_blank" rel="noopener" class="i-office-card__link">Get directions</a><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php else: ?>
<section class="i-offices" id="offices">
    <div class="i-offices__container">
        <header class="i-offices__header">
            <span class="i-offices__pretitle">Locations</span>
            <h2 class="i-offices__title"><?php echo e($content['offices_heading']); ?></h2>
            <p class="i-offices__subtitle"><?php echo e($content['offices_subtitle']); ?></p>
        </header>
        <p class="i-offices__empty"><a href="<?php echo $siteBase; ?>/contact">View our contact page</a> for branches and directions.</p>
    </div>
</section>
<?php endif; ?>

<section class="i-community">
    <div class="i-community__container">
        <header class="i-community__header">
            <!-- <span class="i-community__pretitle">Community change</span> -->
            <h2 class="i-community__title"><?php echo e($content['community_heading']); ?></h2>
            <p class="i-community__subtitle"><?php echo e($content['community_subtitle']); ?></p>
        </header>
        <div class="i-community__grid">
            <div class="i-community__col">
                <h3 class="i-community__col-title">Economic benefits</h3>
                <ul class="i-community__list">
                    <?php foreach ($economicBullets as $b): ?>
                        <li><?php echo e($b); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="i-community__col">
                <h3 class="i-community__col-title">Social benefits</h3>
                <ul class="i-community__list">
                    <?php foreach ($socialBullets as $b): ?>
                        <li><?php echo e($b); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="i-journey">
    <div class="i-journey__container">
        <header class="i-journey__header">
            <!-- <span class="i-journey__pretitle">Our journey</span> -->
            <h2 class="i-journey__title"><?php echo e($content['journey_heading']); ?></h2>
            <p class="i-journey__subtitle"><?php echo e($content['journey_subtitle']); ?></p>
        </header>
        <div class="i-journey__timeline">
            <?php foreach ($journey as $j): ?>
                <div class="i-journey__item">
                    <span class="i-journey__year"><?php echo e($j['year']); ?></span>
                    <div class="i-journey__content">
                        <h3 class="i-journey__item-title"><?php echo e($j['title']); ?></h3>
                        <p class="i-journey__item-desc"><?php echo e($j['description']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="c-section c-section--cta" id="cta">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title"><?php echo e($content['cta_heading']); ?></h2>
            <p class="c-section__subtitle"><?php echo e($content['cta_subtitle']); ?></p>
        </div>
        <?php
        $ctaUrl1 = $siteBase . '/' . ltrim($content['cta_primary_url'], '/');
        $ctaUrl2 = $siteBase . '/' . ltrim($content['cta_secondary_url'], '/');
        ?>
        <div class="c-section__actions">
            <a href="<?php echo e($ctaUrl1); ?>" class="c-btn c-btn--secondary c-btn--lg"><?php echo e($content['cta_primary_text']); ?></a>
            <a href="<?php echo e($ctaUrl2); ?>" class="c-btn c-btn--outline-white c-btn--lg"><?php echo e($content['cta_secondary_text']); ?></a>
        </div>
    </div>
</section>

<?php
// Map: load Leaflet only when we have offices with coordinates
if (!empty($officesWithCoords)):
?>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <script>
  (function () {
    var el = document.getElementById('impact-offices-map');
    if (!el) return;
    var data = el.getAttribute('data-offices');
    if (!data) return;
    var offices;
    try { offices = JSON.parse(data); } catch (e) { return; }
    if (!offices.length) return;
    var center = { lat: offices[0].lat, lng: offices[0].lng };
    if (offices.length > 1) {
      var minLat = Math.min.apply(null, offices.map(function (o) { return o.lat; }));
      var maxLat = Math.max.apply(null, offices.map(function (o) { return o.lat; }));
      var minLng = Math.min.apply(null, offices.map(function (o) { return o.lng; }));
      var maxLng = Math.max.apply(null, offices.map(function (o) { return o.lng; }));
      center = { lat: (minLat + maxLat) / 2, lng: (minLng + maxLng) / 2 };
    }
    var map = L.map('impact-offices-map').setView([center.lat, center.lng], offices.length === 1 ? 12 : 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>' }).addTo(map);
    var bounds = [];
    offices.forEach(function (o) {
      var m = L.marker([o.lat, o.lng]).addTo(map);
      var popup = '<strong>' + (o.name || 'Office') + '</strong>';
      if (o.address) popup += '<br>' + o.address;
      if (o.map_url) popup += '<br><a href="' + o.map_url + '" target="_blank" rel="noopener">Get directions</a>';
      m.bindPopup(popup);
      bounds.push([o.lat, o.lng]);
    });
    if (offices.length > 1) map.fitBounds(bounds, { padding: [24, 24] });
  })();
  </script>
<?php endif; ?>

<?php if (!empty($impactStats)): ?>
<script>
(function() {
  var section = document.getElementById('impact-numbers');
  var values = document.querySelectorAll('.c-impact-card__value[data-count]');
  if (!section || values.length === 0) return;
  var duration = 2000;
  var easing = function(t) { return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2; };
  function animateOne(el) {
    var raw = el.getAttribute('data-count');
    var target = parseInt(raw, 10);
    if (raw === '' || raw === null || isNaN(target) || target < 0) return;
    var suffix = el.getAttribute('data-suffix') || '';
    function format(num) { return Math.floor(num).toLocaleString() + suffix; }
    var start = performance.now();
    function update(now) {
      var elapsed = now - start;
      var progress = Math.min(elapsed / duration, 1);
      var current = target * easing(progress);
      el.textContent = format(current);
      if (progress < 1) requestAnimationFrame(update);
      else el.textContent = format(target);
    }
    requestAnimationFrame(update);
  }
  function runAll() {
    values.forEach(function(el) {
      if (!el.hasAttribute('data-animated')) {
        el.setAttribute('data-animated', '1');
        animateOne(el);
      }
    });
  }
  if ('IntersectionObserver' in window) {
    var obs = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          runAll();
          obs.unobserve(entry.target);
        }
      });
    }, { root: null, rootMargin: '0px 0px -80px 0px', threshold: 0.15 });
    obs.observe(section);
  } else {
    runAll();
  }
})();
</script>
<?php endif; ?>

<?php if (!empty($testimonials)): ?>
<script>
(function() {
  var slider = document.getElementById('impact-testimonials-slider');
  if (!slider) return;
  var testimonials = slider.querySelectorAll('.c-testimonial');
  var prevBtn = slider.querySelector('.c-testimonials__prev');
  var nextBtn = slider.querySelector('.c-testimonials__next');
  var dots = slider.querySelectorAll('.c-testimonials__dot');
  var currentIndex = 0;
  var autoplayInterval = null;
  function showTestimonial(index) {
    if (index < 0) index = testimonials.length - 1;
    else if (index >= testimonials.length) index = 0;
    testimonials.forEach(function(t, i) { t.classList.toggle('is-active', i === index); });
    dots.forEach(function(d, i) { d.classList.toggle('is-active', i === index); });
    currentIndex = index;
  }
  function next() { showTestimonial(currentIndex + 1); }
  function prev() { showTestimonial(currentIndex - 1); }
  function startAutoplay() {
    if (autoplayInterval) clearInterval(autoplayInterval);
    autoplayInterval = setInterval(next, 6000);
  }
  function stopAutoplay() {
    if (autoplayInterval) { clearInterval(autoplayInterval); autoplayInterval = null; }
  }
  if (prevBtn) prevBtn.addEventListener('click', function() { prev(); startAutoplay(); });
  if (nextBtn) nextBtn.addEventListener('click', function() { next(); startAutoplay(); });
  dots.forEach(function(dot, index) {
    dot.addEventListener('click', function() { showTestimonial(index); startAutoplay(); });
  });
  slider.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft') { prev(); startAutoplay(); }
    else if (e.key === 'ArrowRight') { next(); startAutoplay(); }
  });
  if (testimonials.length > 1) startAutoplay();
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
