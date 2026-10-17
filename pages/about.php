<?php
/**
 * About Page (Who We Are) - Tulima Solar
 *
 * Sections aligned with live: Hero, Our Story, Mission & Vision, Our Values,
 * Office Locations, Get Directions, Our Staff, Board of Directors, Our Advisors, CTA.
 * Content from about_page_content; values from about_values; offices from office_locations;
 * team from team_members; mission/vision from about_mission_vision.
 *
 * @version 4.0.0
 */

$pageTitle = 'Who We Are - Tulima Solar';
$pageDescription = 'Learn about Tulima Solar: empowering African farmers with sustainable solar solutions. Our story, mission, values, team, and office locations.';
$bodyClass = 'page-about';
$pageCss = 'about.css?v=4';

include __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../config/database.php';
$pdo = getDb();

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$siteBase = rtrim(BASE_PATH, '/');

// Default about page content (keys from migration)
$content = [
    'hero_heading' => 'Empowering African Farmers with Sustainable Solar Solutions',
    'hero_subtitle' => 'Since 2019, we\'ve been transforming agriculture across Africa through innovative solar-powered irrigation systems and processing machines.',
    'hero_cta_primary_text' => 'Our Story',
    'hero_cta_primary_url' => '#our-story',
    'hero_cta_secondary_text' => 'Meet Our Team',
    'hero_cta_secondary_url' => '#our-team',
    'story_heading' => 'Our Story',
    'story_subtitle' => 'From humble beginnings to becoming a leading solar solutions provider in Africa',
    'story_paragraph_1' => 'Tulima Solar was founded in 2019 with a clear mission: to empower African farmers with affordable, sustainable solar-powered irrigation systems and agricultural processing machines.',
    'story_paragraph_2' => 'We recognized that traditional fuel-powered farming equipment was not only expensive to operate but also unreliable and environmentally harmful. Our solution was to harness the abundant African sunshine to provide clean, reliable, and cost-effective farming solutions.',
    'story_paragraph_3' => 'Today, we\'ve helped thousands of farmers across Uganda and beyond to increase their productivity, reduce costs, and farm more sustainably. Click the button below to see our impact in numbers and discover how we\'re transforming agriculture across Africa.',
    'story_cta_text' => 'See Our Impact',
    'story_cta_url' => '/our-impact',
    'mission_vision_heading' => 'Mission & Vision',
    'mission_vision_subtitle' => 'Driving sustainable change in African agriculture',
    'values_heading' => 'Our Values',
    'values_subtitle' => 'The principles that guide everything we do',
    'offices_heading' => 'Our Office Locations',
    'offices_subtitle' => 'Visit us at any of our regional offices across Uganda',
    'directions_heading' => 'Get Directions',
    'directions_subtitle' => 'Enter your starting location to get directions to our office',
    'directions_form_heading' => 'Find Your Route',
    'directions_form_subtitle' => 'Get personalized directions to any of our office locations',
    'team_heading' => 'Our Staff',
    'team_subtitle' => 'Our dedicated team of professionals working to serve you',
    'directors_heading' => 'Board of Directors',
    'directors_subtitle' => 'Our leadership team guiding the company\'s vision and strategy',
    'advisors_heading' => 'Our Advisors',
    'advisors_subtitle' => 'Expert advisors providing strategic guidance and industry insights',
    'cta_heading' => 'Join Our Mission to Transform African Agriculture',
    'cta_subtitle' => 'Be part of the solar farming revolution and help us empower thousands more farmers across Africa with sustainable, cost-effective solar solutions. Together, we can create a brighter future for African agriculture.',
    'cta_primary_text' => 'Get Started Today',
    'cta_primary_url' => '/contact',
    'cta_secondary_text' => 'See Our Impact',
    'cta_secondary_url' => '/our-impact',
];

try {
    $stmt = $pdo->query("SELECT `key`, value FROM about_page_content");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($content[$row['key']])) {
            $content[$row['key']] = $row['value'] ?? $content[$row['key']];
        }
    }
} catch (Exception $e) { /* use defaults */ }

// Mission & Vision from database
$missionTitle = 'Our Mission';
$missionContent = 'Enabling prosperity through financed Productive Equipment Access.';
$visionTitle = 'Our Vision';
$visionContent = 'We envision economies that are sustainable, equitable and that do not entirely depend on rainfall or harmful carbon emissions to ensure reliable productivity and income.';
try {
    $stmt = $pdo->query("SELECT block_key, title, content FROM about_mission_vision WHERE block_key IN ('mission', 'vision') ORDER BY sort_order ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (($row['block_key'] ?? '') === 'mission') {
            $missionTitle = $row['title'] ?? $missionTitle;
            $missionContent = $row['content'] ?? $missionContent;
        } elseif (($row['block_key'] ?? '') === 'vision') {
            $visionTitle = $row['title'] ?? $visionTitle;
            $visionContent = $row['content'] ?? $visionContent;
        }
    }
} catch (Exception $e) { /* use defaults */ }

// Our Values from database
$values = [];
try {
    $stmt = $pdo->query("SELECT title, description FROM about_values WHERE is_active = 1 ORDER BY sort_order ASC");
    $values = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }
if (empty($values)) {
    $values = [
        ['title' => 'Customer-Centric', 'description' => 'We focus on customer needs and provide positive customer experience.'],
        ['title' => 'Communication', 'description' => 'We actively listen to our customers and provide clear and concise communication.'],
        ['title' => 'Data-Driven', 'description' => 'We use data to make informed decisions and improve our products and services.'],
        ['title' => 'Collaboration', 'description' => 'We work together to achieve our goals and provide better solutions for our customers.'],
        ['title' => 'Commitment', 'description' => 'We are committed to our customers, employees, and the community.'],
    ];
}

// Office locations from database
$offices = [];
try {
    $stmt = $pdo->query("SELECT name, address, phone, email, region_or_city, map_url FROM office_locations WHERE is_active = 1 ORDER BY sort_order ASC");
    $offices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }

// Team: staff, directors, advisors from database
$staff = [];
$directors = [];
$advisors = [];
try {
    $stmt = $pdo->query("SELECT * FROM team_members WHERE is_active = 1 AND category = 'staff' ORDER BY display_order ASC");
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->query("SELECT * FROM team_members WHERE is_active = 1 AND category = 'director' ORDER BY display_order ASC");
    $directors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->query("SELECT * FROM team_members WHERE is_active = 1 AND category = 'advisor' ORDER BY display_order ASC");
    $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }

// Helper: resolve CTA URL (anchor-only stays as-is for same page)
$resolveUrl = function ($url) use ($siteBase) {
    if (empty($url)) return $siteBase . '/contact';
    if (preg_match('/^https?:\/\//', $url)) return $url;
    if (strpos($url, '#') === 0) return $url;
    return $siteBase . '/' . ltrim($url, '/');
};
?>

<!-- Hero -->
<header class="s-hero" id="about-hero">
    <div class="s-hero__bg" aria-hidden="true"></div>
    <div class="s-hero__container">
        <!-- <nav class="s-hero__breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo $siteBase; ?>/">Home</a>
            <span class="s-hero__breadcrumb-sep" aria-hidden="true">/</span>
            <span aria-current="page">Who We Are</span>
        </nav> -->
        <h1 class="s-hero__title"><?php echo e($content['hero_heading']); ?></h1>
        <p class="s-hero__subtitle"><?php echo e($content['hero_subtitle']); ?></p>
        <div class="s-hero__ctas">
            <a href="<?php echo e($resolveUrl($content['hero_cta_primary_url'])); ?>" class="s-hero__cta"><?php echo e($content['hero_cta_primary_text']); ?></a>
            <a href="<?php echo e($resolveUrl($content['hero_cta_secondary_url'])); ?>" class="s-hero__cta s-hero__cta--secondary"><?php echo e($content['hero_cta_secondary_text']); ?></a>
        </div>
    </div>
</header>

<!-- Our Story -->
<section class="s-intro a-story-section" id="our-story">
    <div class="s-intro__container">
        <header class="a-story-section__header">
            <h2 class="a-story-section__title"><?php echo e($content['story_heading']); ?></h2>
            <p class="a-story-section__subtitle"><?php echo e($content['story_subtitle']); ?></p>
        </header>
        <div class="a-story-section__content">
            <?php if (!empty($content['story_paragraph_1'])): ?><p><?php echo nl2br(e($content['story_paragraph_1'])); ?></p><?php endif; ?>
            <?php if (!empty($content['story_paragraph_2'])): ?><p><?php echo nl2br(e($content['story_paragraph_2'])); ?></p><?php endif; ?>
            <?php if (!empty($content['story_paragraph_3'])): ?><p><?php echo nl2br(e($content['story_paragraph_3'])); ?></p><?php endif; ?>
        </div>
        <?php if (!empty($content['story_cta_text'])): ?>
        <p class="a-story-section__cta-wrap">
            <a href="<?php echo e($resolveUrl($content['story_cta_url'])); ?>" class="c-btn c-btn--secondary"><?php echo e($content['story_cta_text']); ?></a>
        </p>
        <?php endif; ?>
    </div>
</section>

<!-- Mission & Vision (from database) -->
<section class="a-mission" id="mission-vision">
    <div class="a-mission__container">
        <header class="a-mission__header">
            <h2 class="a-mission__section-title"><?php echo e($content['mission_vision_heading']); ?></h2>
            <p class="a-mission__section-subtitle"><?php echo e($content['mission_vision_subtitle']); ?></p>
        </header>
        <div class="a-mission__grid">
            <div class="a-mission__item">
                <h3 class="a-mission__heading"><?php echo e($visionTitle); ?></h3>
                <p class="a-mission__text"><?php echo nl2br(e($visionContent)); ?></p>
            </div>
            <div class="a-mission__item">
                <h3 class="a-mission__heading"><?php echo e($missionTitle); ?></h3>
                <p class="a-mission__text"><?php echo nl2br(e($missionContent)); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Our Values (from database) -->
<section class="a-values" id="our-values">
    <div class="a-values__container">
        <header class="a-values__header">
            <!-- <span class="a-values__pretitle">Values</span> -->
            <h2 class="a-values__title"><?php echo e($content['values_heading']); ?></h2>
            <p class="a-values__subtitle"><?php echo e($content['values_subtitle']); ?></p>
        </header>
        <div class="a-values__grid">
            <?php foreach ($values as $v): ?>
                <div class="a-value-card">
                    <h3 class="a-value-card__title"><?php echo e($v['title']); ?></h3>
                    <p class="a-value-card__desc"><?php echo e($v['description']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Our Office Locations (from database) -->
<section class="a-offices" id="offices">
    <div class="a-offices__container">
        <header class="a-offices__header">
            <!-- <span class="a-offices__pretitle">Locations</span> -->
            <h2 class="a-offices__title"><?php echo e($content['offices_heading']); ?></h2>
            <p class="a-offices__subtitle"><?php echo e($content['offices_subtitle']); ?></p>
        </header>
        <?php if (!empty($offices)): ?>
        <div class="a-offices__grid">
            <?php foreach ($offices as $office): ?>
                <div class="a-office-card">
                    <h3 class="a-office-card__name"><?php echo e($office['name']); ?></h3>
                    <?php if (!empty($office['region_or_city'])): ?><p class="a-office-card__region"><?php echo e($office['region_or_city']); ?></p><?php endif; ?>
                    <?php if (!empty($office['address'])): ?><p class="a-office-card__address"><?php echo nl2br(e($office['address'])); ?></p><?php endif; ?>
                    <?php if (!empty($office['phone'])): ?><p class="a-office-card__phone"><a href="tel:<?php echo e(preg_replace('/\s+/', '', $office['phone'])); ?>"><?php echo e($office['phone']); ?></a></p><?php endif; ?>
                    <?php if (!empty($office['email'])): ?><p class="a-office-card__email"><a href="mailto:<?php echo e($office['email']); ?>"><?php echo e($office['email']); ?></a></p><?php endif; ?>
                    <?php if (!empty($office['map_url'])): ?><a href="<?php echo e($office['map_url']); ?>" target="_blank" rel="noopener" class="a-office-card__link">Get Directions</a><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="a-offices__empty"><a href="<?php echo $siteBase; ?>/contact">Contact us</a> for office details.</p>
        <?php endif; ?>
    </div>
</section>

<!-- Get Directions -->
<section class="a-directions" id="directions">
    <div class="a-directions__container">
        <header class="a-directions__header">
            <h2 class="a-directions__title"><?php echo e($content['directions_heading']); ?></h2>
            <p class="a-directions__subtitle"><?php echo e($content['directions_subtitle']); ?></p>
        </header>
        <div class="a-directions__form-wrap">
            <h3 class="a-directions__form-title"><?php echo e($content['directions_form_heading']); ?></h3>
            <p class="a-directions__form-subtitle"><?php echo e($content['directions_form_subtitle']); ?></p>
            <?php if (!empty($offices)): ?>
            <div class="a-directions__destinations">
                <?php foreach ($offices as $office): ?>
                    <?php if (!empty($office['map_url'])): ?>
                    <a href="<?php echo e($office['map_url']); ?>" target="_blank" rel="noopener" class="c-btn c-btn--outline a-directions__btn"><?php echo e($office['name']); ?><?php if (!empty($office['region_or_city'])): ?> – <?php echo e($office['region_or_city']); ?><?php endif; ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <p class="a-directions__contact-link"><a href="<?php echo $siteBase; ?>/contact">Contact us</a> for more help.</p>
        </div>
    </div>
</section>

<!-- Our Staff (from database) -->
<section class="a-team" id="our-team">
    <div class="a-team__container">
        <header class="a-team__header">
            <!-- <span class="a-team__pretitle">Team</span> -->
            <h2 class="a-team__title"><?php echo e($content['team_heading']); ?></h2>
            <p class="a-team__subtitle"><?php echo e($content['team_subtitle']); ?></p>
        </header>
        <?php if (!empty($staff)): ?>
        <div class="a-team__grid">
            <?php foreach ($staff as $member): ?>
                <article class="a-team-card">
                    <div class="a-team-card__image">
                        <?php if (!empty($member['profile_image'])): ?>
                            <img src="<?php echo $siteBase . '/' . e($member['profile_image']); ?>" alt="<?php echo e($member['name']); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="a-team-card__placeholder" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="a-team-card__body">
                        <h4 class="a-team-card__name"><?php echo e($member['name']); ?></h4>
                        <p class="a-team-card__role"><?php echo e($member['job_title']); ?></p>
                        <?php if (!empty($member['biography'])): ?>
                            <p class="a-team-card__bio"><?php echo e($member['biography']); ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($directors)): ?>
        <h3 class="a-team__subheading"><?php echo e($content['directors_heading']); ?></h3>
        <p class="a-team__subheading-desc"><?php echo e($content['directors_subtitle']); ?></p>
        <div class="a-team__grid">
            <?php foreach ($directors as $member): ?>
                <article class="a-team-card">
                    <div class="a-team-card__image">
                        <?php if (!empty($member['profile_image'])): ?>
                            <img src="<?php echo $siteBase . '/' . e($member['profile_image']); ?>" alt="<?php echo e($member['name']); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="a-team-card__placeholder" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                        <?php endif; ?>
                    </div>
                    <div class="a-team-card__body">
                        <h4 class="a-team-card__name"><?php echo e($member['name']); ?></h4>
                        <p class="a-team-card__role"><?php echo e($member['job_title']); ?></p>
                        <?php if (!empty($member['biography'])): ?><p class="a-team-card__bio"><?php echo e($member['biography']); ?></p><?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($advisors)): ?>
        <h3 class="a-team__subheading"><?php echo e($content['advisors_heading']); ?></h3>
        <p class="a-team__subheading-desc"><?php echo e($content['advisors_subtitle']); ?></p>
        <div class="a-team__grid">
            <?php foreach ($advisors as $member): ?>
                <article class="a-team-card">
                    <div class="a-team-card__image">
                        <?php if (!empty($member['profile_image'])): ?>
                            <img src="<?php echo $siteBase . '/' . e($member['profile_image']); ?>" alt="<?php echo e($member['name']); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="a-team-card__placeholder" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                        <?php endif; ?>
                    </div>
                    <div class="a-team-card__body">
                        <h4 class="a-team-card__name"><?php echo e($member['name']); ?></h4>
                        <p class="a-team-card__role"><?php echo e($member['job_title']); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
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
            <a href="<?php echo e($resolveUrl($content['cta_primary_url'])); ?>" class="c-btn c-btn--secondary c-btn--lg"><?php echo e($content['cta_primary_text']); ?></a>
            <a href="<?php echo e($resolveUrl($content['cta_secondary_url'])); ?>" class="c-btn c-btn--outline-white c-btn--lg"><?php echo e($content['cta_secondary_text']); ?></a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
