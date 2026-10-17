<?php
/**
 * Projects Page - Tulima Solar
 *
 * Showcase of completed solar installations. Redesigned with shared design system.
 *
 * @version 2.0.0
 */

$pageTitle = 'Our Solar Projects - Tulima Solar';
$pageDescription = 'Explore Tulima Solar\'s completed solar installations across Africa. See our residential, commercial, and agricultural projects.';
$bodyClass = 'page-projects';
$pageCss = 'projects.css';
$pageJs = 'projects.js';

include __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../config/database.php';
$pdo = getDb();

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$siteBase = rtrim(BASE_PATH, '/');

$categories = [
    'all' => 'All Projects',
    'residential' => 'Residential',
    'commercial' => 'Commercial',
    'agricultural' => 'Agricultural',
    'industrial' => 'Industrial'
];

$projects = [
    ['id' => 1, 'name' => 'Kampala Residential Estate', 'category' => 'residential', 'location' => 'Kampala, Uganda', 'capacity' => '50kW', 'description' => 'Complete solar installation powering 25 homes in a modern residential estate. Each home equipped with rooftop panels and battery backup systems.', 'image' => $siteBase . '/media/blog_images/DJI_0663_278Dd2P.JPG', 'stats' => ['homes_powered' => '25', 'annual_savings' => '$15,000', 'co2_avoided' => '45 tons/year'], 'completed' => '2025'],
    ['id' => 2, 'name' => 'Nairobi Office Complex', 'category' => 'commercial', 'location' => 'Nairobi, Kenya', 'capacity' => '200kW', 'description' => 'Corporate headquarters solar installation with integrated battery storage. Features smart monitoring system and grid-tie capability.', 'image' => $siteBase . '/media/blog_images/Untitled-design-7-1_1.png', 'stats' => ['employees_benefiting' => '500+', 'annual_savings' => '$85,000', 'co2_avoided' => '180 tons/year'], 'completed' => '2024'],
    ['id' => 3, 'name' => 'Kenya Farm Irrigation', 'category' => 'agricultural', 'location' => 'Nakuru, Kenya', 'capacity' => '75kW', 'description' => 'Solar-powered water pumping system for large-scale irrigation. Enables year-round farming with sustainable energy.', 'image' => $siteBase . '/media/products/KILIMO-BORA-1.png', 'stats' => ['acres_irrigated' => '120', 'water_pumped_daily' => '50,000L', 'co2_avoided' => '65 tons/year'], 'completed' => '2024'],
    ['id' => 4, 'name' => 'Uganda School System', 'category' => 'commercial', 'location' => 'Entebbe, Uganda', 'capacity' => '150kW', 'description' => 'Educational facility solar installation providing reliable power for classrooms, computer labs, and administrative buildings.', 'image' => $siteBase . '/media/blog_images/IMG-20220803-WA0014-768x1024-1-576x400.jpg', 'stats' => ['students_benefiting' => '1,200', 'classrooms_powered' => '35', 'co2_avoided' => '130 tons/year'], 'completed' => '2025'],
    ['id' => 5, 'name' => 'Rwanda Health Clinic', 'category' => 'commercial', 'location' => 'Kigali, Rwanda', 'capacity' => '80kW', 'description' => 'Critical power installation for healthcare facility. Ensures 24/7 electricity for medical equipment, refrigeration, and lighting.', 'image' => $siteBase . '/media/products/JIWEZESHE-Pump-1024x683.png', 'stats' => ['patients_served' => '5,000+/year', 'vaccine_fridges' => '8', 'co2_avoided' => '70 tons/year'], 'completed' => '2024'],
    ['id' => 6, 'name' => 'Tanzania Factory', 'category' => 'industrial', 'location' => 'Dar es Salaam, Tanzania', 'capacity' => '500kW', 'description' => 'Large-scale industrial solar installation for manufacturing facility. Includes rooftop and ground-mount arrays with grid integration.', 'image' => $siteBase . '/media/products/CLEAN_Milling_machine_.png', 'stats' => ['production_hours' => '24/7', 'annual_savings' => '$200,000', 'co2_avoided' => '450 tons/year'], 'completed' => '2025'],
    ['id' => 7, 'name' => 'Lakeside Community', 'category' => 'residential', 'location' => 'Lake Victoria, Uganda', 'capacity' => '120kW', 'description' => 'Community solar project powering 60 homes in a lakeside village. Includes shared battery storage and individual home connections.', 'image' => $siteBase . '/media/products/MASIKA.png', 'stats' => ['homes_powered' => '60', 'community_members' => '300+', 'co2_avoided' => '105 tons/year'], 'completed' => '2024'],
    ['id' => 8, 'name' => 'Agricultural Cooperative', 'category' => 'agricultural', 'location' => 'Eldoret, Kenya', 'capacity' => '300kW', 'description' => 'Multi-farm solar installation serving a farming cooperative. Powers irrigation, cold storage, and processing equipment.', 'image' => $siteBase . '/media/products/CHEMCHEM-PLUS-PUMP.png', 'stats' => ['farms_served' => '15', 'storage_capacity' => '20 tons', 'co2_avoided' => '270 tons/year'], 'completed' => '2025']
];

$featuredProject = [
    'name' => 'Tanzania Manufacturing Facility',
    'category' => 'Industrial',
    'location' => 'Dar es Salaam, Tanzania',
    'capacity' => '500kW',
    'description' => 'Our flagship industrial project, this 500kW solar installation powers a major manufacturing facility in Dar es Salaam. The system combines rooftop and ground-mounted solar arrays with advanced battery storage, enabling 24/7 operations while significantly reducing energy costs and carbon footprint.',
    'image' => $siteBase . '/media/products/CLEAN_Milling_machine_.png',
    'stats' => [
        ['label' => 'System Capacity', 'value' => '500kW'],
        ['label' => 'Annual Production', 'value' => '750,000 kWh'],
        ['label' => 'CO2 Avoided', 'value' => '450 tons/year'],
        ['label' => 'Annual Savings', 'value' => '$200,000']
    ],
    'testimonial' => [
        'quote' => 'Tulima Solar transformed our energy infrastructure. We\'ve reduced our electricity costs by 70% and our carbon emissions significantly. The installation was professional, and their ongoing support has been exceptional.',
        'author' => 'John Mwangi',
        'title' => 'Facility Manager, Tanzania Manufacturing Ltd.'
    ],
    'timeline' => [
        ['phase' => 'Site Assessment', 'duration' => '2 weeks'],
        ['phase' => 'System Design', 'duration' => '4 weeks'],
        ['phase' => 'Installation', 'duration' => '8 weeks'],
        ['phase' => 'Testing & Commissioning', 'duration' => '2 weeks']
    ]
];

$impactStats = [
    ['value' => '500+', 'label' => 'Projects Completed'],
    ['value' => '50MW+', 'label' => 'Total Capacity Installed'],
    ['value' => '5', 'label' => 'Countries Served'],
    ['value' => '25,000+', 'label' => 'Tons CO2 Avoided/Year']
];
?>

<section class="c-page-header">
    <div class="c-page-header__container">
        <nav class="c-page-header__breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo $siteBase; ?>/">Home</a>
            <span class="c-page-header__breadcrumb-separator" aria-hidden="true">/</span>
            <span aria-current="page">Projects</span>
        </nav>
        <h1 class="c-page-header__title">Our Projects</h1>
        <p class="c-section__subtitle" style="color: rgba(255,255,255,0.9); margin: 0;">See how we're transforming communities across Africa with solar energy</p>
    </div>
</section>

<section class="c-section c-section--white" id="projects-filter-section">
    <div class="c-section__container">
        <header class="c-section__header">
            <span class="c-section__pretitle">Our Portfolio</span>
            <h2 class="c-section__title">Completed Installations</h2>
            <p class="c-section__subtitle">Explore our diverse portfolio of solar projects spanning residential, commercial, agricultural, and industrial sectors</p>
        </header>
        <div class="p-projects-filter" role="tablist" aria-label="Project category filter">
            <?php foreach ($categories as $key => $label): ?>
                <button type="button" class="c-filter-btn<?php echo $key === 'all' ? ' is-active' : ''; ?>" data-filter="<?php echo e($key); ?>" role="tab" aria-selected="<?php echo $key === 'all' ? 'true' : 'false'; ?>" aria-controls="projects-grid"><?php echo e($label); ?></button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="c-section c-section--white" id="projects-grid-section">
    <div class="c-section__container">
        <div class="c-card-grid c-card-grid--3" id="projects-grid" role="tabpanel">
            <?php foreach ($projects as $project): ?>
                <article class="c-card c-card--lift c-project-card" data-category="<?php echo e($project['category']); ?>">
                    <div class="c-card__image">
                        <img src="<?php echo e($project['image']); ?>" alt="<?php echo e($project['name']); ?>" loading="lazy">
                        <span class="p-projects-card-badge"><?php echo e(ucfirst($project['category'])); ?></span>
                    </div>
                    <div class="c-card__body">
                        <h3 class="c-card__title"><?php echo e($project['name']); ?></h3>
                        <div class="p-projects-meta">
                            <span><?php echo e($project['location']); ?></span>
                            <span><?php echo e($project['capacity']); ?></span>
                        </div>
                        <p class="c-card__text"><?php echo e($project['description']); ?></p>
                        <div class="p-projects-stats">
                            <?php foreach ($project['stats'] as $k => $v): ?>
                                <div class="p-projects-stat">
                                    <span class="p-projects-stat__value"><?php echo e($v); ?></span>
                                    <span class="p-projects-stat__label"><?php echo e(ucwords(str_replace('_', ' ', $k))); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="#project-<?php echo $project['id']; ?>" class="c-card__link">View Details →</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="c-section c-section--gray" id="featured-project">
    <div class="c-section__container">
        <header class="c-section__header">
            <span class="c-section__pretitle">Featured Project</span>
            <h2 class="c-section__title"><?php echo e($featuredProject['name']); ?></h2>
            <p class="c-section__subtitle">Our flagship installation showcasing the full potential of industrial solar energy</p>
        </header>
        <div class="c-two-col" style="align-items: flex-start;">
            <div class="c-two-col__media">
                <div class="p-projects-featured-img">
                    <img src="<?php echo e($featuredProject['image']); ?>" alt="<?php echo e($featuredProject['name']); ?>">
                    <span class="p-projects-featured-badge">Featured</span>
                </div>
            </div>
            <div class="c-two-col__content">
                <p class="c-card__text" style="margin-bottom: 1.5rem;"><?php echo e($featuredProject['description']); ?></p>
                <div class="p-projects-featured-stats">
                    <?php foreach ($featuredProject['stats'] as $s): ?>
                        <div class="p-projects-featured-stat">
                            <span class="p-projects-featured-stat__value"><?php echo e($s['value']); ?></span>
                            <span class="p-projects-featured-stat__label"><?php echo e($s['label']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <h4 style="font-size: var(--font-size-lg); margin: 1.5rem 0 0.75rem;">Project Timeline</h4>
                <ul class="p-projects-timeline" style="list-style: none; padding: 0; margin: 0;">
                    <?php foreach ($featuredProject['timeline'] as $phase): ?>
                        <li class="p-projects-timeline__item">
                            <span class="p-projects-timeline__phase"><?php echo e($phase['phase']); ?></span>
                            <span class="p-projects-timeline__duration"><?php echo e($phase['duration']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <blockquote class="c-card c-card--testimonial" style="margin-top: 2rem;">
            <p class="c-card__quote"><?php echo e($featuredProject['testimonial']['quote']); ?></p>
            <footer class="c-card__author">
                <span class="c-card__author-name"><?php echo e($featuredProject['testimonial']['author']); ?></span>
                <span class="c-card__author-title"><?php echo e($featuredProject['testimonial']['title']); ?></span>
            </footer>
        </blockquote>
    </div>
</section>

<section class="c-section c-section--stats" id="project-impact">
    <div class="c-section__container">
        <header class="c-section__header">
            <span class="c-section__pretitle" style="color: rgba(255,255,255,0.8);">Our Impact</span>
            <h2 class="c-section__title" style="color: var(--white);">Making a Difference Across Africa</h2>
        </header>
        <div class="c-stats-grid">
            <?php foreach ($impactStats as $stat): ?>
                <div class="c-stat">
                    <div class="c-stat__number"><?php echo e($stat['value']); ?></div>
                    <div class="c-stat__label"><?php echo e($stat['label']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="c-section c-section--cta" id="cta">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Have a Project in Mind?</h2>
            <p class="c-section__subtitle">Let's discuss how solar energy can transform your home, business, or farm. Our team is ready to design a custom solution for your needs.</p>
        </div>
        <div class="c-section__actions">
            <a href="<?php echo $siteBase; ?>/contact" class="c-btn c-btn--secondary c-btn--lg">Let's Discuss Your Project</a>
            <a href="<?php echo $siteBase; ?>/assets/downloads/tulima-portfolio.pdf" class="c-btn c-btn--outline-white c-btn--lg">Download Our Portfolio</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
