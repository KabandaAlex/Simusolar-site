<?php
/**
 * Solar Water Pumps - Product Page
 * Matches the modern 'c-' design system layout.
 */

$pageTitle = 'Solar Water Pumps - Tulima Solar';
$pageDescription = 'Comprehensive solar water pumping solutions for agricultural irrigation, livestock watering, and domestic supply across East Africa.';
$bodyClass = 'page-products category-page';
$pageCss = 'products.css?v=2'; 

require_once __DIR__ . '/../config/database.php';
$pdo = getDb();

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// The safe fallback
$siteBase = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '/tulima_php';

// Fetch Solar Water Pumps from the database
$solar_pumps = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, pc.name AS category_name, pc.slug AS category_slug
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE p.is_active = 1 AND pc.slug = 'solar-water-pumps'
        ORDER BY p.sort_order ASC, p.created_at DESC
    ");
    $stmt->execute();
    $solar_pumps = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $solar_pumps = [];
}

include __DIR__ . '/../includes/header.php';
?>

<section class="c-page-hero c-page-hero--products">
    <div class="c-page-hero__background">
        <div class="c-page-hero__overlay"></div>
    </div>
    <div class="c-page-hero__container">
        <div class="c-page-hero__content">
            <h1 class="c-page-hero__title">Complete Solar Water<br>Pumping Solutions</h1>
            <p class="c-page-hero__subtitle">
                Reliable, cost-effective water supply systems powered by the sun. Perfect for irrigation, livestock, and community water needs.
            </p>
            <div class="c-page-hero__actions">
                <a href="#pump-systems" class="c-btn c-btn--secondary c-btn--lg">
                    <i class="fas fa-solar-panel me-2"></i> View Systems
                </a>
                <a href="#benefits" class="c-btn c-btn--outline c-btn--lg">
                    <i class="fas fa-leaf me-2"></i> See Benefits
                </a>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--white" id="benefits">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">The Smart Choice for Water Supply</h2>
            <p class="c-section__subtitle">
                Replace expensive diesel generators with clean, reliable solar energy.
            </p>
        </div>

        <div class="c-section__content">
            <div class="c-card-grid c-card-grid--3">
                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-money-bill-wave fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Zero Fuel Costs</h3>
                        <p class="c-card__description">Eliminate the ongoing expense of diesel or grid electricity. The sun powers your water for free.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-tools fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Low Maintenance</h3>
                        <p class="c-card__description">Fewer moving parts than traditional engines means less wear and tear and minimal maintenance required.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-leaf fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Eco-Friendly</h3>
                        <p class="c-card__description">Zero emissions and silent operation. Protect the environment while securing your water supply.</p>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--gray" id="pump-systems">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Solar Pumping Solutions</h2>
            <p class="c-section__subtitle">Browse our selection of complete solar water pumping kits.</p>
        </div>

        <div class="c-section__content">
            <?php if (!empty($solar_pumps)): ?>
                <div class="c-card-grid c-card-grid--3">
                    <?php foreach ($solar_pumps as $pump): ?>
                        <article class="c-card c-card--lift c-card--product">
                            <?php if (!empty($pump['image_path'])): ?>
                                <div class="c-card__image">
                                    <img src="<?php echo e($siteBase . '/' . $pump['image_path']); ?>" alt="<?php echo e($pump['title']); ?>" loading="lazy">
                                </div>
                            <?php else: ?>
                                <div class="c-card__image" style="background: #eee; display:flex; align-items:center; justify-content:center; min-height: 200px;">
                                    <i class="fas fa-solar-panel fa-4x text-muted"></i>
                                </div>
                            <?php endif; ?>

                            <div class="c-card__body c-card__body--compact">
                                <span class="c-card__tag">Complete System</span>
                                <h3 class="c-card__title"><?php echo e($pump['title']); ?></h3>
                                <?php if (!empty($pump['short_description'])): ?>
                                    <p class="c-card__excerpt"><?php echo e($pump['short_description']); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="c-card__footer">
                                <div class="c-card__actions" style="display: flex; gap: 10px;">
                                    <a href="<?php echo e($siteBase . '/products/' . $pump['slug']); ?>" class="c-btn c-btn--outline c-btn--sm" style="flex: 1; text-align: center;">
                                        View Details
                                    </a>
                                    <a href="<?php echo e($siteBase . '/contact'); ?>" class="c-btn c-btn--primary c-btn--sm" style="flex: 1; text-align: center;">
                                        Inquire Now
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="o-empty-state" style="text-align: center; padding: 3rem;">
                    <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
                    <p>New solar water pumping systems are being added to our catalog. Please check back soon!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="c-section c-section--white" id="components">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">System Components</h2>
            <p class="c-section__subtitle">What makes up a complete solar water pumping solution.</p>
        </div>

        <div class="c-section__content">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary); text-align: center;">
                        <i class="fas fa-solar-panel fa-3x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Solar PV Array</h3>
                        <p class="c-card__description">High-efficiency solar panels capture sunlight and convert it into direct current (DC) electricity.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary); text-align: center;">
                        <i class="fas fa-microchip fa-3x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Pump Controller</h3>
                        <p class="c-card__description">The brain of the system. It matches the power output of the panels to the pump to maximize efficiency.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary); text-align: center;">
                        <i class="fas fa-tint fa-3x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">The Pump Unit</h3>
                        <p class="c-card__description">Whether submersible or surface, our high-quality DC/AC pumps deliver reliable water flow.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary); text-align: center;">
                        <i class="fas fa-water fa-3x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Storage Tank</h3>
                        <p class="c-card__description">Pumping water into a storage tank during the day ensures water availability 24/7, without needing batteries.</p>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--cta" id="products-cta">
    <div class="c-section__container">
        <div class="c-section__header" style="max-width: 800px; margin: 0 auto; text-align: center;">
            <h2 class="c-section__title">Ready to Secure Your Water Supply?</h2>
            <p class="c-section__subtitle">
                Let our experts design the perfect solar pumping system for your specific well depth, daily water requirement, and location.
            </p>
            
            <div style="margin: 2rem 0; display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; color: white;">
                <span><i class="fas fa-check-circle text-primary me-2"></i> Free Site Assessment</span>
                <span><i class="fas fa-check-circle text-primary me-2"></i> System Sizing</span>
                <span><i class="fas fa-check-circle text-primary me-2"></i> Professional Installation</span>
            </div>
            
            <div style="background: rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); margin-bottom: 2rem;">
                <h4 style="color: white; margin-bottom: 0.5rem;"><i class="fas fa-handshake me-2"></i> Get a Custom Quote</h4>
                <p style="margin-bottom: 0;">Contact us with your daily water needs and we'll calculate the exact system you require.</p>
            </div>
        </div>
        
        <div class="c-section__actions">
            <a href="<?php echo $siteBase; ?>/contact" class="c-btn c-btn--secondary c-btn--lg">
                <i class="fas fa-calculator me-2"></i> Request Sizing Quote
            </a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>