<?php
/**
 * Surface Pumps - Product Page
 * Preserves original content while adopting the new 'c-' design system layout.
 */

$pageTitle = 'Surface Pumps - Tulima Solar';
$pageDescription = 'Solar-powered surface pumps for irrigation and water supply. Perfect for shallow water sources like rivers, ponds, and lakes with easy maintenance.';
$bodyClass = 'page-products category-page';
$pageCss = 'products.css?v=2'; // Use the new products CSS for alignment

require_once __DIR__ . '/../config/database.php';
$pdo = getDb();

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// The safe fallback we added earlier
$siteBase = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '/tulima_php';

// Fetch Surface Pumps from the database
$surface_pumps = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, pc.name AS category_name, pc.slug AS category_slug
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE p.is_active = 1 AND pc.slug = 'surface-pumps'
        ORDER BY p.sort_order ASC, p.created_at DESC
    ");
    $stmt->execute();
    $surface_pumps = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $surface_pumps = [];
}

include __DIR__ . '/../includes/header.php';
?>

<section class="c-page-hero c-page-hero--products">
    <div class="c-page-hero__background">
        <div class="c-page-hero__overlay"></div>
    </div>
    <div class="c-page-hero__container">
        <div class="c-page-hero__content">
            <h1 class="c-page-hero__title">Efficient Solar-Powered<br>Surface Pumps</h1>
            <p class="c-page-hero__subtitle">
                Perfect for shallow water sources with easy maintenance and reliable performance for all your agricultural water needs.
            </p>
            <div class="c-page-hero__actions">
                <a href="#surface-pumps" class="c-btn c-btn--secondary c-btn--lg">
                    <i class="fas fa-tint me-2"></i> View Pumps
                </a>
                <a href="#applications" class="c-btn c-btn--outline c-btn--lg">
                    <i class="fas fa-cogs me-2"></i> Applications
                </a>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--white" id="technology">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Understanding Surface Pump Technology</h2>
            <p class="c-section__subtitle">
                Ideal for shallow water sources with superior accessibility and maintenance.
            </p>
        </div>

        <div class="c-section__content" style="max-width: 800px; margin: 0 auto 3rem; text-align: center;">
            <p class="lead" style="margin-bottom: 1.5rem;">
                Surface pumps operate from water sources of rivers, ponds and lakes near the surface of the earth and are used primarily for moving water through a pipe. These pumps have a pull and water exhaust component.
            </p>
            <p>
                They are often placed near a water source and draw water over it, pushing it to the desired location. They do not enter the water, meaning they operate while placed safely outside the water or near the source.
            </p>
        </div>

        <div class="c-section__content">
            <div class="c-card-grid c-card-grid--3">
                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-tools fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Easy Maintenance</h3>
                        <p class="c-card__description">Accessible for repairs without water removal.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Cost Effective</h3>
                        <p class="c-card__description">More affordable than submersible alternatives.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-arrows-alt fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Versatile</h3>
                        <p class="c-card__description">Easy to move and adapt for different sources.</p>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--gray" id="surface-pumps">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Our Surface Pump Range</h2>
            <p class="c-section__subtitle">High-quality surface pumps designed for African farming conditions.</p>
        </div>

        <div class="c-section__content">
            <?php if (!empty($surface_pumps)): ?>
                <div class="c-card-grid c-card-grid--3">
                    <?php foreach ($surface_pumps as $pump): ?>
                        <article class="c-card c-card--lift c-card--product">
                            <?php if (!empty($pump['image_path'])): ?>
                                <div class="c-card__image">
                                    <img src="<?php echo e($siteBase . '/' . $pump['image_path']); ?>" alt="<?php echo e($pump['title']); ?>" loading="lazy">
                                </div>
                            <?php else: ?>
                                <div class="c-card__image" style="background: #eee; display:flex; align-items:center; justify-content:center; min-height: 200px;">
                                    <i class="fas fa-tint fa-4x text-muted"></i>
                                </div>
                            <?php endif; ?>

                            <div class="c-card__body c-card__body--compact">
                                <span class="c-card__tag">Surface Pump</span>
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
                    <p>New surface pump models are being added to our catalog. Please check back soon or contact us for inventory!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="c-section c-section--white" id="applications">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Applications</h2>
            <p class="c-section__subtitle">Surface pumps are ideal for various agricultural and commercial applications.</p>
        </div>

        <div class="c-section__content">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary); text-align: center;">
                        <i class="fas fa-seedling fa-3x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Crop Irrigation</h3>
                        <p class="c-card__description">Efficient irrigation for various crops including vegetables, fruits, and grains.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary); text-align: center;">
                        <i class="fas fa-cow fa-3x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Livestock Watering</h3>
                        <p class="c-card__description">Reliable water supply for cattle, goats, sheep, and other livestock.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary); text-align: center;">
                        <i class="fas fa-fish fa-3x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Aquaculture</h3>
                        <p class="c-card__description">Water circulation and aeration for fish ponds and aquaculture systems.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary); text-align: center;">
                        <i class="fas fa-hand-holding-water fa-3x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Water Supply</h3>
                        <p class="c-card__description">Domestic and commercial water supply from surface water sources.</p>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--gray" id="advantages">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Advantages of Surface Pumps</h2>
            <p class="c-section__subtitle">Why choose surface pumps for your water pumping needs.</p>
        </div>

        <div class="c-section__content">
            <div class="c-card-grid c-card-grid--3">
                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-tools fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Easy Maintenance</h3>
                        <p class="c-card__description">Accessible for maintenance without needing to remove from the water source.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Cost Effective</h3>
                        <p class="c-card__description">Generally more affordable than submersible pumps for similar applications.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-cog fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Versatile</h3>
                        <p class="c-card__description">Can be easily moved and adapted for different water sources.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-solar-panel fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Solar Powered</h3>
                        <p class="c-card__description">Clean, renewable energy source with no fuel costs or emissions.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-shield-alt fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Durable</h3>
                        <p class="c-card__description">Built to withstand harsh environmental conditions and long-term use.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-leaf fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Environmentally Friendly</h3>
                        <p class="c-card__description">No pollution or noise impact compared to fuel-powered pumps.</p>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--white" id="specifications">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Technical Specifications</h2>
            <p class="c-section__subtitle">Key specifications for our surface pump range.</p>
        </div>

        <div class="c-section__content p-why-solar">
            <div class="p-why-solar__table-wrap">
                <table class="p-why-solar__table">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 30%;">Specification</th>
                            <th scope="col" style="width: 30%;">Range</th>
                            <th scope="col">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">Power Output</th>
                            <td>0.5 - 5 HP</td>
                            <td>Various power options to suit different applications</td>
                        </tr>
                        <tr>
                            <th scope="row">Flow Rate</th>
                            <td>1,000 - 50,000 L/day</td>
                            <td>Daily water output capacity</td>
                        </tr>
                        <tr>
                            <th scope="row">Head Height</th>
                            <td>10 - 100 meters</td>
                            <td>Maximum vertical pumping distance</td>
                        </tr>
                        <tr>
                            <th scope="row">Solar Panel</th>
                            <td>200W - 2000W</td>
                            <td>Solar panel capacity for power generation</td>
                        </tr>
                        <tr>
                            <th scope="row">Warranty</th>
                            <td>2 Years</td>
                            <td>Comprehensive warranty coverage</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--cta" id="products-cta">
    <div class="c-section__container">
        <div class="c-section__header" style="max-width: 800px; margin: 0 auto; text-align: center;">
            <h2 class="c-section__title">Ready to Transform Your Farm with Solar?</h2>
            <p class="c-section__subtitle">
                Join thousands of successful farmers across East Africa who have revolutionized their agricultural operations with our professional solar solutions. Get your free consultation and personalized quote today.
            </p>
            
            <div style="margin: 2rem 0; display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; color: white;">
                <span><i class="fas fa-check-circle text-primary me-2"></i> Free Consultation</span>
                <span><i class="fas fa-check-circle text-primary me-2"></i> Custom Solutions</span>
                <span><i class="fas fa-check-circle text-primary me-2"></i> Complete Support</span>
                <span><i class="fas fa-check-circle text-primary me-2"></i> Ongoing Maintenance</span>
            </div>
            
            <div style="background: rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); margin-bottom: 2rem;">
                <h4 style="color: white; margin-bottom: 0.5rem;"><i class="fas fa-gift me-2"></i> Special Offer</h4>
                <p style="margin-bottom: 0;">Get a <strong>free solar assessment</strong> on your first solar installation.</p>
            </div>
        </div>
        
        <div class="c-section__actions">
            <a href="<?php echo $siteBase; ?>/contact" class="c-btn c-btn--secondary c-btn--lg">
                <i class="fas fa-rocket me-2"></i> Get Started Today
            </a>
        </div>
        <p style="text-align: center; color: rgba(255,255,255,0.7); margin-top: 1rem; font-size: 0.9rem;">
            <i class="fas fa-shield-alt me-1"></i> No commitment required • Free quote • Professional support
        </p>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>