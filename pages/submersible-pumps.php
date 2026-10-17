<?php
/**
 * Submersible Pumps - Product Page
 * Preserves original content while adopting the new 'c-' design system layout.
 */

$pageTitle = 'Submersible Pumps - Tulima Solar';
$pageDescription = 'High-performance solar-powered submersible pumps for deep wells and boreholes. Perfect for extracting water from deep sources with quiet operation and high efficiency.';
$bodyClass = 'page-products';
$pageCss = 'products.css?v=2'; // Use the new products CSS for alignment

require_once __DIR__ . '/../config/database.php';
$pdo = getDb();

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

//$siteBase = rtrim(BASE_PATH, '/');

// Fetch Submersible Pumps from the database
$submersible_pumps = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, pc.name AS category_name, pc.slug AS category_slug
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE p.is_active = 1 AND pc.slug = 'submersible-pumps'
        ORDER BY p.sort_order ASC, p.created_at DESC
    ");
    $stmt->execute();
    $submersible_pumps = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $submersible_pumps = [];
}

include __DIR__ . '/../includes/header.php';
?>

<section class="c-page-hero c-page-hero--products">
    <div class="c-page-hero__background">
        <div class="c-page-hero__overlay"></div>
    </div>
    <div class="c-page-hero__container">
        <div class="c-page-hero__content">
            <h1 class="c-page-hero__title">Submersible Pumps<br>for Deep Wells</h1>
            <p class="c-page-hero__subtitle">
                Extract water from deep wells and boreholes with quiet, efficient operation and reliable performance for all your agricultural and commercial needs.
            </p>
            <div class="c-page-hero__actions">
                <a href="#submersible-pumps" class="c-btn c-btn--secondary c-btn--lg">
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
            <h2 class="c-section__title">Understanding Submersible Pump Technology</h2>
            <p class="c-section__subtitle">
                Designed for deep water sources with superior efficiency and reliability.
            </p>
        </div>

        <div class="c-section__content" style="max-width: 800px; margin: 0 auto 3rem; text-align: center;">
            <p class="lead" style="margin-bottom: 1.5rem;">
                Submersible pumps are installed in the water within the well or borehole, and are submerged within the reservoir. These pumps are designed to operate underwater and are ideal for deep water sources.
            </p>
            <p>
                They are perfect for extracting water from deep wells, boreholes, and other deep water sources where surface pumps cannot reach effectively.
            </p>
        </div>

        <div class="c-section__content">
            <div class="c-card-grid c-card-grid--3">
                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-arrow-down fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Deep Access</h3>
                        <p class="c-card__description">Reach water from much deeper sources.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-volume-mute fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Quiet Operation</h3>
                        <p class="c-card__description">Silent operation underwater.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-tachometer-alt fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">High Efficiency</h3>
                        <p class="c-card__description">Superior performance for deep water.</p>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($submersible_pumps)): ?>
<section class="c-section c-section--gray" id="submersible-pumps">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Our Submersible Pump Range</h2>
            <p class="c-section__subtitle">High-quality submersible pumps designed for deep water applications.</p>
        </div>

        <div class="c-section__content">
            <div class="c-card-grid c-card-grid--3">
                <?php foreach ($submersible_pumps as $pump): ?>
                    <article class="c-card c-card--lift c-card--product">
                        <?php if (!empty($pump['image_path'])): ?>
                            <div class="c-card__image">
                                <img src="<?php echo e($siteBase . '/' . $pump['image_path']); ?>" alt="<?php echo e($pump['title']); ?>" loading="lazy">
                            </div>
                        <?php endif; ?>

                        <div class="c-card__body c-card__body--compact">
                            <span class="c-card__tag">Submersible Pump</span>
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
        </div>
    </div>
</section>
<?php endif; ?>

<section class="c-section c-section--white" id="applications">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Applications</h2>
            <p class="c-section__subtitle">Submersible pumps are ideal for deep water applications and various agricultural uses.</p>
        </div>

        <div class="c-section__content">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary); text-align: center;">
                        <i class="fas fa-water fa-3x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Deep Wells</h3>
                        <p class="c-card__description">Extract water from deep wells and boreholes efficiently.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary); text-align: center;">
                        <i class="fas fa-seedling fa-3x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Large Scale Irrigation</h3>
                        <p class="c-card__description">High-capacity irrigation for large farms and commercial agriculture.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary); text-align: center;">
                        <i class="fas fa-building fa-3x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Commercial Use</h3>
                        <p class="c-card__description">Water supply for commercial buildings, hotels, and institutions.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary); text-align: center;">
                        <i class="fas fa-industry fa-3x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Industrial Applications</h3>
                        <p class="c-card__description">Industrial water supply and processing applications.</p>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--gray" id="advantages">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Advantages of Submersible Pumps</h2>
            <p class="c-section__subtitle">Why choose submersible pumps for deep water applications.</p>
        </div>

        <div class="c-section__content">
            <div class="c-card-grid c-card-grid--3">
                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-arrow-down fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Deep Water Access</h3>
                        <p class="c-card__description">Can access water from much deeper sources than surface pumps.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-volume-mute fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Quiet Operation</h3>
                        <p class="c-card__description">Operate silently underwater, perfect for residential areas.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-shield-alt fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Protected Design</h3>
                        <p class="c-card__description">Sealed design protects against water damage and corrosion.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; color: var(--color-primary);">
                        <i class="fas fa-tachometer-alt fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">High Efficiency</h3>
                        <p class="c-card__description">More efficient than surface pumps for deep water applications.</p>
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
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title">Long Lifespan</h3>
                        <p class="c-card__description">Designed for long-term operation with minimal maintenance.</p>
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
            <p class="c-section__subtitle">Key specifications for our submersible pump range.</p>
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
                            <td>1 - 10 HP</td>
                            <td>Various power options for different applications</td>
                        </tr>
                        <tr>
                            <th scope="row">Flow Rate</th>
                            <td>5,000 - 100,000 L/day</td>
                            <td>Daily water output capacity</td>
                        </tr>
                        <tr>
                            <th scope="row">Head Height</th>
                            <td>50 - 300 meters</td>
                            <td>Maximum vertical pumping distance</td>
                        </tr>
                        <tr>
                            <th scope="row">Well Diameter</th>
                            <td>4 - 12 inches</td>
                            <td>Compatible well diameters</td>
                        </tr>
                        <tr>
                            <th scope="row">Solar Panel</th>
                            <td>500W - 5000W</td>
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

<section class="c-section c-section--gray" id="installation">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Installation Process</h2>
            <p class="c-section__subtitle">Professional installation by our certified technicians.</p>
        </div>

        <div class="c-section__content">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; text-align: center;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 50px; height: 50px; background: var(--color-primary); color: white; border-radius: 50%; font-size: 1.5rem; font-weight: bold;">1</span>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Site Assessment</h3>
                        <p class="c-card__description">Evaluate well depth, diameter, and water level to select the right pump.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; text-align: center;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 50px; height: 50px; background: var(--color-primary); color: white; border-radius: 50%; font-size: 1.5rem; font-weight: bold;">2</span>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Pump Installation</h3>
                        <p class="c-card__description">Carefully lower the pump into the well with proper cable and pipe management.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; text-align: center;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 50px; height: 50px; background: var(--color-primary); color: white; border-radius: 50%; font-size: 1.5rem; font-weight: bold;">3</span>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Solar Setup</h3>
                        <p class="c-card__description">Install solar panels and connect the electrical system for power generation.</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2rem 2rem 0; text-align: center;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 50px; height: 50px; background: var(--color-primary); color: white; border-radius: 50%; font-size: 1.5rem; font-weight: bold;">4</span>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Testing & Training</h3>
                        <p class="c-card__description">Test the system and provide training on operation and maintenance.</p>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--cta" id="products-cta">
    <div class="c-section__container">
        <div class="c-section__header" style="max-width: 800px; margin: 0 auto; text-align: center;">
            <h2 class="c-section__title">Ready to Transform Your Farm with Solar?</h2>
            <p class="c-section__subtitle">
                Join thousands of successful farmers across East Africa who have already revolutionized their agricultural operations with our professional solar solutions. Get your free consultation and personalized quote today.
            </p>
            
            <div style="margin: 2rem 0; display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; color: white;">
                <span><i class="fas fa-check-circle text-primary me-2"></i> Free Consultation</span>
                <span><i class="fas fa-check-circle text-primary me-2"></i> Custom Solutions</span>
                <span><i class="fas fa-check-circle text-primary me-2"></i> Install Support</span>
                <span><i class="fas fa-check-circle text-primary me-2"></i> Maintenance</span>
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