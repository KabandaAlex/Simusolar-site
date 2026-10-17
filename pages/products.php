<?php
/**
 * Products Page - Tulima Solar
 *
 * Fresh redesign inspired by live site:
 * - Hero (Professional Solar Products)
 * - Professional Product Range (key categories)
 * - Complete Product Catalog with View Details modal
 * - Why Choose Solar Power comparison
 * - Final CTA
 *
 * Uses existing `products` and `product_categories` tables; content managed via admin/products.php
 * and admin/product_categories.php.
 */

$pageTitle = 'Professional Solar Products - Tulima Solar';
$pageDescription = 'Professional solar-powered products for African agriculture: solar water pumps, agricultural processing machines, and solar panel systems.';
$bodyClass = 'page-products';
$pageCss = 'products.css?v=2';
$pageJs = 'products.js';

include __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../config/database.php';
$pdo = getDb();

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Fetch top-level product categories (for professional product range)
$categories = [];
try {
    $stmt = $pdo->query("
        SELECT pc.*,
               (SELECT COUNT(*) FROM products p WHERE p.category_id = pc.id AND p.is_active = 1) AS product_count
        FROM product_categories pc
        WHERE pc.is_active = 1
          AND pc.parent_id IS NULL
        ORDER BY pc.sort_order ASC
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $categories = [];
}

// Fetch all active products with category info for catalog + modal
$products = [];
try {
    $stmt = $pdo->query("
        SELECT p.*, pc.name AS category_name, pc.slug AS category_slug
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE p.is_active = 1
        ORDER BY p.sort_order ASC, p.created_at DESC
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $products = [];
}

$siteBase = rtrim(BASE_PATH, '/');
?>

<!-- Hero -->
<section class="c-page-hero c-page-hero--products">
    <div class="c-page-hero__background">
        <div class="c-page-hero__overlay"></div>
    </div>
    <div class="c-page-hero__container">
        <!-- <nav class="c-breadcrumb" aria-label="Breadcrumb">
            <ol class="c-breadcrumb__list">
                <li class="c-breadcrumb__item">
                    <a href="<?php echo $siteBase; ?>/" class="c-breadcrumb__link">Home</a>
                </li>
                <li class="c-breadcrumb__item">
                    <span class="c-breadcrumb__current" aria-current="page">Products</span>
                </li>
            </ol>
        </nav> -->

        <div class="c-page-hero__content">
            <h1 class="c-page-hero__title">Professional Solar Products<br>for African Agriculture</h1>
            <p class="c-page-hero__subtitle">
                Discover our comprehensive range of solar-powered equipment designed for African farmers.
                Professional solutions that transform agricultural productivity with sustainable technology.
            </p>
            <div class="c-page-hero__actions">
                <a href="<?php echo $siteBase; ?>/contact" class="c-btn c-btn--secondary c-btn--lg">Get Free Consultation</a>
                <a href="<?php echo $siteBase; ?>/solar-water-pumps" class="c-btn c-btn--outline c-btn--lg">View Water Pumps</a>
            </div>
        </div>
    </div>
</section>

<!-- Professional Product Range (key categories) -->
<section class="c-section c-section--white" id="product-range">
    <div class="c-section__container">
        <div class="c-section__header">
            <!-- <span class="c-section__pretitle">Product range</span> -->
            <h2 class="c-section__title">Professional Product Range</h2>
            <p class="c-section__subtitle">
                Choose from our comprehensive range of solar-powered solutions designed for maximum efficiency and reliability.
            </p>
        </div>

        <div class="c-section__content">
            <?php if (!empty($categories)): ?>
                <div class="c-card-grid c-card-grid--3">
                    <?php foreach ($categories as $category): ?>
                        <article class="c-card c-card--lift c-card--category">
                            <?php if (!empty($category['image'])): ?>
                                <div class="c-card__image">
                                    <img src="<?php echo e($siteBase . '/' . $category['image']); ?>"
                                         alt="<?php echo e($category['name']); ?>"
                                         loading="lazy">
                                </div>
                            <?php else: ?>
                                <div class="c-card__icon">
                                    <?php
                                    $iconMap = [
                                        'solar-water-pumps' => 'droplet',
                                        'agricultural-processing-machines' => 'wheat',
                                        'solar-systems' => 'sun',
                                        'accessories' => 'package',
                                    ];
                                    $icon = $iconMap[$category['slug']] ?? 'box';
                                    ?>
                                    <svg class="o-icon o-icon--lg" aria-hidden="true">
                                        <use xlink:href="<?php echo e($siteBase); ?>/assets/images/icons.svg#<?php echo e($icon); ?>"></use>
                                    </svg>
                                </div>
                            <?php endif; ?>

                            <div class="c-card__body">
                                <h3 class="c-card__title">
                                    <a href="<?php echo e($siteBase . '/' . $category['slug']); ?>">
                                        <?php echo e($category['name']); ?>
                                    </a>
                                </h3>
                                <?php if (!empty($category['description'])): ?>
                                    <p class="c-card__description"><?php echo e($category['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="c-card__footer">
                                <span class="c-card__meta">
                                    <?php echo (int)($category['product_count'] ?? 0); ?> product<?php echo ($category['product_count'] ?? 0) == 1 ? '' : 's'; ?>
                                </span>
                                <a href="<?php echo e($siteBase . '/' . $category['slug']); ?>" class="c-card__link">
                                    View Category
                                    <svg class="o-icon o-icon--sm" aria-hidden="true">
                                        <use xlink:href="<?php echo e($siteBase); ?>/assets/images/icons.svg#arrow-right"></use>
                                    </svg>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="o-empty-state">
                    <p>Product categories are being updated. Please check back soon.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Complete Product Catalog -->
<?php if (!empty($products)): ?>
<section class="c-section c-section--gray" id="catalog">
    <div class="c-section__container">
        <div class="c-section__header">
            <!-- <span class="c-section__pretitle">Product catalog</span> -->
            <h2 class="c-section__title">Complete Product Catalog</h2>
            <p class="c-section__subtitle">
                Browse our full catalog of professional solar-powered agricultural equipment.
            </p>
        </div>

        <div class="c-section__content">
            <div class="c-card-grid c-card-grid--3">
                <?php foreach ($products as $product): ?>
                    <article class="c-card c-card--lift c-card--product">
                        <?php if (!empty($product['image_path'])): ?>
                            <div class="c-card__image">
                                <img src="<?php echo e($siteBase . '/' . $product['image_path']); ?>"
                                     alt="<?php echo e($product['title']); ?>"
                                     loading="lazy">
                                <?php if (!empty($product['featured'])): ?>
                                    <span class="c-badge c-badge--featured">Featured</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="c-card__body c-card__body--compact">
                            <?php if (!empty($product['category_name'])): ?>
                                <span class="c-card__tag">
                                    <a href="<?php echo e($siteBase . '/' . $product['category_slug']); ?>">
                                        <?php echo e($product['category_name']); ?>
                                    </a>
                                </span>
                            <?php endif; ?>

                            <h3 class="c-card__title"><?php echo e($product['title']); ?></h3>

                            <?php if (!empty($product['short_description'])): ?>
                                <p class="c-card__excerpt"><?php echo e($product['short_description']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="c-card__footer">
                            <div class="c-card__meta">
                                <div class="c-card__meta-item">
                                    <svg class="o-icon o-icon--sm" aria-hidden="true">
                                        <use xlink:href="<?php echo e($siteBase); ?>/assets/images/icons.svg#sun"></use>
                                    </svg>
                                    <span>Solar equipment</span>
                                </div>
                            </div>
                            <div class="c-card__actions">
                                <button type="button"
                                        class="c-btn c-btn--primary c-btn--sm"
                                        data-product-detail-id="<?php echo (int)$product['id']; ?>">
                                    View Details
                                </button>
                                <a href="<?php echo e($siteBase . '/products/' . $product['slug']); ?>"
                                   class="c-btn c-btn--outline c-btn--sm">
                                    View Page
                                </a>
                            </div>
                        </div>

                        <!-- Hidden detail for modal -->
                        <div id="product-detail-<?php echo (int)$product['id']; ?>" class="c-product-detail-data" hidden>
                            <div class="c-product-detail">
                                <?php if (!empty($product['image_path'])): ?>
                                    <div class="c-product-detail__image">
                                        <img src="<?php echo e($siteBase . '/' . $product['image_path']); ?>"
                                             alt="<?php echo e($product['title']); ?>"
                                             loading="lazy">
                                    </div>
                                <?php endif; ?>
                                <div class="c-product-detail__body">
                                    <?php if (!empty($product['category_name'])): ?>
                                        <p class="c-product-detail__category"><?php echo e($product['category_name']); ?></p>
                                    <?php endif; ?>
                                    <h3 class="c-product-detail__title"><?php echo e($product['title']); ?></h3>
                                    <?php if (!empty($product['short_description'])): ?>
                                        <p class="c-product-detail__summary"><?php echo e($product['short_description']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($product['description'])): ?>
                                        <div class="c-product-detail__description">
                                            <?php echo nl2br(e($product['description'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="c-product-detail__actions">
                                        <a href="<?php echo e($siteBase . '/products/' . $product['slug']); ?>" class="c-btn c-btn--secondary c-btn--sm">
                                            View Full Product Page
                                        </a>
                                        <a href="<?php echo e($siteBase . '/quote?product=' . urlencode($product['slug'])); ?>" class="c-btn c-btn--outline c-btn--sm">
                                            Inquire Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Why Choose Solar Power (match live products page) -->
<section class="c-section c-section--white" id="why-solar">
    <div class="c-section__container">
        <div class="c-section__header">
            <!-- <span class="c-section__pretitle">Why solar</span> -->
            <h2 class="c-section__title">Why Choose Solar Power?</h2>
            <p class="c-section__subtitle">
                Compare the benefits of solar-powered equipment vs traditional fuel-powered systems.
            </p>
        </div>

        <div class="c-section__content p-why-solar">
            <div class="p-why-solar__columns">
                <article class="p-why-solar__column p-why-solar__column--solar">
                    <h3 class="p-why-solar__heading">
                        Solar-Powered
                        <span>Sustainable &amp; Efficient</span>
                    </h3>
                    <ul class="p-why-solar__list">
                        <li><strong>Operating Cost:</strong> Free (sunlight)</li>
                        <li><strong>Maintenance:</strong> Minimal</li>
                        <li><strong>Environmental Impact:</strong> Clean &amp; renewable</li>
                        <li><strong>Reliability:</strong> High reliability</li>
                        <li><strong>Lifespan:</strong> 20+ years</li>
                        <li><strong>ROI &amp; Savings:</strong> Excellent long-term</li>
                    </ul>
                </article>

                <article class="p-why-solar__column p-why-solar__column--fuel">
                    <h3 class="p-why-solar__heading">
                        Fuel-Powered
                        <span>Traditional &amp; Costly</span>
                    </h3>
                    <ul class="p-why-solar__list">
                        <li><strong>Operating Cost:</strong> High fuel costs</li>
                        <li><strong>Maintenance:</strong> Regular maintenance</li>
                        <li><strong>Environmental Impact:</strong> Carbon emissions</li>
                        <li><strong>Reliability:</strong> Engine failures</li>
                        <li><strong>Lifespan:</strong> 5–10 years</li>
                        <li><strong>ROI &amp; Savings:</strong> Poor investment</li>
                    </ul>
                </article>
            </div>

            <div class="p-why-solar__table-wrap">
                <table class="p-why-solar__table">
                    <thead>
                    <tr>
                        <th scope="col">Feature</th>
                        <th scope="col">Solar-Powered<br><span>Sustainable &amp; Efficient</span></th>
                        <th scope="col">Fuel-Powered<br><span>Traditional &amp; Costly</span></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <th scope="row">Operating Cost</th>
                        <td>Free (sunlight)</td>
                        <td>High fuel costs</td>
                    </tr>
                    <tr>
                        <th scope="row">Maintenance</th>
                        <td>Minimal</td>
                        <td>Regular maintenance</td>
                    </tr>
                    <tr>
                        <th scope="row">Environmental Impact</th>
                        <td>Clean &amp; renewable</td>
                        <td>Carbon emissions</td>
                    </tr>
                    <tr>
                        <th scope="row">Reliability</th>
                        <td>High reliability</td>
                        <td>Engine failures</td>
                    </tr>
                    <tr>
                        <th scope="row">Lifespan</th>
                        <td>20+ years</td>
                        <td>5–10 years</td>
                    </tr>
                    <tr>
                        <th scope="row">ROI &amp; Savings</th>
                        <td>Excellent long-term</td>
                        <td>Poor investment</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-why-solar__notes">
                <article class="p-why-solar__note p-why-solar__note--solar">
                    <h3>Solar Power Advantage</h3>
                    <p>
                        Professional solar solutions offer superior long-term value with minimal operational costs and
                        maximum environmental benefits.
                    </p>
                </article>
                <article class="p-why-solar__note p-why-solar__note--fuel">
                    <h3>Traditional Limitations</h3>
                    <p>
                        Fuel-powered systems come with ongoing costs, maintenance requirements, and environmental
                        concerns that impact profitability.
                    </p>
                </article>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="c-section c-section--cta" id="products-cta">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Ready to Transform Your Farm with Solar?</h2>
            <p class="c-section__subtitle">
                Join thousands of successful farmers across East Africa who have already revolutionized their agricultural operations with our professional solar solutions.
            </p>
        </div>
        <div class="c-section__actions">
            <a href="<?php echo $siteBase; ?>/contact" class="c-btn c-btn--secondary c-btn--lg">
                Get Started Today
            </a>
            <a href="<?php echo $siteBase; ?>/products#catalog" class="c-btn c-btn--outline-light c-btn--lg">
                Browse Catalog
            </a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

