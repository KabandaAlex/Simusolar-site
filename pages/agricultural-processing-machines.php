<?php
/**
 * Agricultural Processing Machines - Product Page
 * Preserves original content while adopting the new 'c-' design system layout.
 */

$pageTitle = 'Agricultural Processing Machines - Tulima Solar';
$pageDescription = 'Solar-powered machines for post-harvest processing and value addition. Transform your harvests into profitable products.';
$bodyClass = 'page-products category-page';
$pageCss = 'products.css?v=2';

require_once __DIR__ . '/../config/database.php';
$pdo = getDb();

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// The safe fallback we added earlier
$siteBase = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '/tulima_php';

// Fetch Processing Machines from the database
$processing_machines = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, pc.name AS category_name, pc.slug AS category_slug
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE p.is_active = 1 AND pc.slug = 'agricultural-processing-machines'
        ORDER BY p.sort_order ASC, p.created_at DESC
    ");
    $stmt->execute();
    $processing_machines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $processing_machines = [];
}

include __DIR__ . '/../includes/header.php';
?>

<section class="c-page-hero c-page-hero--products">
    <div class="c-page-hero__background">
        <div class="c-page-hero__overlay"></div>
    </div>
    <div class="c-page-hero__container">
        <div class="c-page-hero__content">
            <h1 class="c-page-hero__title">Solar Machines<br>for Processing & Value Addition</h1>
            <p class="c-page-hero__subtitle">
                Transform your harvests into profitable products with our range of solar-powered processing machines designed for African farming conditions.
            </p>
            <div class="c-page-hero__actions">
                <a href="#processing-machines" class="c-btn c-btn--secondary c-btn--lg">
                    <i class="fas fa-cogs me-2"></i> View Machines
                </a>
                <a href="#benefits" class="c-btn c-btn--outline c-btn--lg">
                    <i class="fas fa-star me-2"></i> Benefits
                </a>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--white" id="technology">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Understanding Agricultural Processing</h2>
            <p class="c-section__subtitle">
                Add value to your harvests and increase income through solar-powered processing.
            </p>
        </div>

        <div class="c-section__content" style="max-width: 800px; margin: 0 auto 3rem; text-align: center;">
            <p class="lead" style="margin-bottom: 1.5rem;">
                Our solar-powered agricultural processing machines help farmers add value to their harvests and increase their income through post-harvest processing.
            </p>
            <p>
                These machines are designed to work efficiently with solar power, reducing operating costs and making processing accessible to farmers in remote areas.
            </p>
        </div>

        <div class="c-section__content">
            <div class="c-card-grid c-card-grid--3">
                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Increased Income</h3>
                        <p class="c-card__description">Add value to your harvests to sell at premium prices.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-solar-panel fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Solar Powered</h3>
                        <p class="c-card__description">Clean, renewable energy with zero fuel costs.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-map-marker-alt fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Remote Access</h3>
                        <p class="c-card__description">Operate anywhere, even completely off the grid.</p>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--gray" id="processing-machines">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Our Processing Machine Range</h2>
            <p class="c-section__subtitle">High-quality solar-powered processing machines for various agricultural products.</p>
        </div>

        <div class="c-section__content">
            <?php if (!empty($processing_machines)): ?>
                <div class="c-card-grid c-card-grid--3">
                    <?php foreach ($processing_machines as $machine): ?>
                        <article class="c-card c-card--lift c-card--product">
                            <?php if (!empty($machine['image_path'])): ?>
                                <div class="c-card__image">
                                    <img src="<?php echo e($siteBase . '/' . $machine['image_path']); ?>" alt="<?php echo e($machine['title']); ?>" loading="lazy">
                                </div>
                            <?php else: ?>
                                <div class="c-card__image" style="background: #eee; display:flex; align-items:center; justify-content:center; min-height: 200px;">
                                    <i class="fas fa-cogs fa-4x text-muted"></i>
                                </div>
                            <?php endif; ?>

                            <div class="c-card__body c-card__body--compact">
                                <span class="c-card__tag">Processing Machine</span>
                                <h3 class="c-card__title"><?php echo e($machine['title']); ?></h3>
                                <?php if (!empty($machine['short_description'])): ?>
                                    <p class="c-card__excerpt"><?php echo e($machine['short_description']); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="c-card__footer">
                                <div class="c-card__actions" style="display: flex; gap: 10px;">
                                    <a href="<?php echo e($siteBase . '/products/' . $machine['slug']); ?>" class="c-btn c-btn--outline c-btn--sm" style="flex: 1; text-align: center;">
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
                    <i class="fas fa-cogs fa-3x mb-3 text-muted"></i>
                    <p>New processing machines are being added to our catalog. Please check back soon or contact us for inventory!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="c-section c-section--white" id="machine-types">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Types of Processing Machines</h2>
            <p class="c-section__subtitle">Specialized machines tailored for different agricultural processing needs.</p>
        </div>

        <div class="c-section__content">
            <div class="c-card-grid c-card-grid--3">
                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-wheat-awn fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Grain Mills</h3>
                        <p class="c-card__description">Process grains like maize, rice, wheat, and millet into flour and other products.</p>
                        <ul style="list-style: none; padding: 0; margin-top: 1rem; color: #555; font-size: 0.9rem;">
                            <li><i class="fas fa-check text-primary me-2"></i> High-capacity processing</li>
                            <li><i class="fas fa-check text-primary me-2"></i> Adjustable fineness</li>
                        </ul>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-seedling fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Oil Presses</h3>
                        <p class="c-card__description">Extract oil from seeds like sunflower, groundnuts, sesame, and soybeans.</p>
                        <ul style="list-style: none; padding: 0; margin-top: 1rem; color: #555; font-size: 0.9rem;">
                            <li><i class="fas fa-check text-primary me-2"></i> Cold pressing technology</li>
                            <li><i class="fas fa-check text-primary me-2"></i> High oil extraction rate</li>
                        </ul>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-apple-alt fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Fruit Processors</h3>
                        <p class="c-card__description">Process fruits into juices, purees, and dried products for value addition.</p>
                        <ul style="list-style: none; padding: 0; margin-top: 1rem; color: #555; font-size: 0.9rem;">
                            <li><i class="fas fa-check text-primary me-2"></i> Preserve nutrients</li>
                            <li><i class="fas fa-check text-primary me-2"></i> Extend shelf life</li>
                        </ul>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-carrot fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Vegetable Processors</h3>
                        <p class="c-card__description">Process vegetables for drying, canning, and other preservation methods.</p>
                        <ul style="list-style: none; padding: 0; margin-top: 1rem; color: #555; font-size: 0.9rem;">
                            <li><i class="fas fa-check text-primary me-2"></i> Washing and cutting</li>
                            <li><i class="fas fa-check text-primary me-2"></i> Quality preservation</li>
                        </ul>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-coffee fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Coffee Processors</h3>
                        <p class="c-card__description">Process coffee cherries into green beans ready for roasting.</p>
                        <ul style="list-style: none; padding: 0; margin-top: 1rem; color: #555; font-size: 0.9rem;">
                            <li><i class="fas fa-check text-primary me-2"></i> Pulping & fermentation</li>
                            <li><i class="fas fa-check text-primary me-2"></i> Washing and drying</li>
                        </ul>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-cube fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Multi-Purpose</h3>
                        <p class="c-card__description">Versatile machines that can handle multiple types of agricultural products.</p>
                        <ul style="list-style: none; padding: 0; margin-top: 1rem; color: #555; font-size: 0.9rem;">
                            <li><i class="fas fa-check text-primary me-2"></i> Interchangeable attachments</li>
                            <li><i class="fas fa-check text-primary me-2"></i> Cost-effective solution</li>
                        </ul>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--gray" id="benefits">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Benefits of Solar-Powered Processing</h2>
            <p class="c-section__subtitle">Why choose solar-powered processing machines for your farm.</p>
        </div>

        <div class="c-section__content">
            <div class="c-card-grid c-card-grid--3">
                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Increased Income</h3>
                        <p class="c-card__description">Add value to your harvests and increase your income through processing.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-solar-panel fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Solar Powered</h3>
                        <p class="c-card__description">Clean, renewable energy with no fuel costs or emissions.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-map-marker-alt fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Remote Access</h3>
                        <p class="c-card__description">Operate in remote areas without access to grid electricity.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Year-Round Operation</h3>
                        <p class="c-card__description">Process your products throughout the year, not just during harvest.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Job Creation</h3>
                        <p class="c-card__description">Create employment opportunities in your community through processing.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-leaf fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Sustainable</h3>
                        <p class="c-card__description">Environmentally friendly processing with minimal waste generation.</p>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--white" id="applications">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Applications & Markets</h2>
            <p class="c-section__subtitle">How processing machines can help you access new markets.</p>
        </div>

        <div class="c-section__content">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary); text-align: center;">
                        <i class="fas fa-store fa-3x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Local Markets</h3>
                        <ul style="list-style: none; padding: 0; margin-top: 1rem; color: #555;">
                            <li style="margin-bottom: 0.5rem;"><i class="fas fa-check text-primary me-2"></i> Processed flour for bakeries</li>
                            <li style="margin-bottom: 0.5rem;"><i class="fas fa-check text-primary me-2"></i> Cooking oil for households</li>
                            <li style="margin-bottom: 0.5rem;"><i class="fas fa-check text-primary me-2"></i> Dried fruits and vegetables</li>
                        </ul>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary); text-align: center;">
                        <i class="fas fa-globe fa-3x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Export Markets</h3>
                        <ul style="list-style: none; padding: 0; margin-top: 1rem; color: #555;">
                            <li style="margin-bottom: 0.5rem;"><i class="fas fa-check text-primary me-2"></i> Organic processed products</li>
                            <li style="margin-bottom: 0.5rem;"><i class="fas fa-check text-primary me-2"></i> Specialty coffee beans</li>
                            <li style="margin-bottom: 0.5rem;"><i class="fas fa-check text-primary me-2"></i> Value-added food products</li>
                        </ul>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--gray" id="training">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Training & Support</h2>
            <p class="c-section__subtitle">We provide comprehensive training and support for all our processing machines.</p>
        </div>

        <div class="c-section__content">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; text-align: center; color: var(--color-primary);">
                        <i class="fas fa-graduation-cap fa-2x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Installation</h3>
                        <p class="c-card__description">Learn how to properly install and set up your machine.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; text-align: center; color: var(--color-primary);">
                        <i class="fas fa-cogs fa-2x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Operation</h3>
                        <p class="c-card__description">Master the operation of your machine for optimal output.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; text-align: center; color: var(--color-primary);">
                        <i class="fas fa-tools fa-2x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Maintenance</h3>
                        <p class="c-card__description">Learn proper procedures to ensure a long machine life.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; text-align: center; color: var(--color-primary);">
                        <i class="fas fa-headset fa-2x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Ongoing Support</h3>
                        <p class="c-card__description">Technical support and troubleshooting assistance.</p>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--cta" id="products-cta">
    <div class="c-section__container">
        <div class="c-section__header" style="max-width: 800px; margin: 0 auto; text-align: center;">
            <h2 class="c-section__title">Ready to Transform Your Harvest?</h2>
            <p class="c-section__subtitle">
                Join successful farmers across East Africa who have revolutionized their agricultural operations with our professional processing solutions.
            </p>
            
            <div style="margin: 2rem 0; display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; color: white;">
                <span><i class="fas fa-check-circle text-primary me-2"></i> Free Consultation</span>
                <span><i class="fas fa-check-circle text-primary me-2"></i> Custom Solutions</span>
                <span><i class="fas fa-check-circle text-primary me-2"></i> Training Included</span>
                <span><i class="fas fa-check-circle text-primary me-2"></i> Ongoing Maintenance</span>
            </div>
            
            <div style="background: rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); margin-bottom: 2rem;">
                <h4 style="color: white; margin-bottom: 0.5rem;"><i class="fas fa-gift me-2"></i> Special Offer</h4>
                <p style="margin-bottom: 0;">Get a <strong>free processing assessment</strong> on your first machine installation.</p>
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