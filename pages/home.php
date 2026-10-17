<?php
/**
 * Home Page - SimuSolar
 * 
 * Main landing page with hero, services, impact stats, testimonials, and partners.
 * 
 * @version 1.0.0
 */

// Page variables for header
$pageTitle = "Powering Africa's Future with Clean Solar Energy";
$pageDescription = 'simuSolar provides sustainable solar energy solutions for homes and businesses across Africa. Empowering communities with reliable, affordable solar power.';
$bodyClass = 'page-home';
$pageCss = 'home.css?v=' . filemtime(__DIR__ . '/../assets/css/pages/home.css');
$pageJs = 'home.js';

// Set flag for transparent navigation over hero
$transparentNav = true;

// Include header
include __DIR__ . '/../includes/header.php';

// Get database connection
require_once __DIR__ . '/../config/database.php';
$pdo = getDb();

// Helper function for escaping output
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Fetch impact numbers from database
$impactStats = [];
try {
    $stmt = $pdo->query("SELECT * FROM impact_numbers WHERE is_active = 1 ORDER BY sort_order ASC");
    $impactStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Use default static content if database query fails
    $impactStats = [
        ['icon' => 'home', 'label' => 'Homes Powered', 'value' => '50,000+', 'suffix' => '+'],
        ['icon' => 'leaf', 'label' => 'CO2 Reduced', 'value' => '100,000', 'suffix' => ' tons'],
        ['icon' => 'calendar', 'label' => 'Years of Experience', 'value' => '10', 'suffix' => '+'],
        ['icon' => 'handshake', 'label' => 'Partners', 'value' => '20', 'suffix' => '+']
    ];
}

// Fetch testimonials from database
$testimonials = [];
try {
    $stmt = $pdo->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6");
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Use default static content if database query fails
    $testimonials = [
        [
            'quote' => 'The solar pump provided a consistent and efficient water supply, reducing physical labor and supporting the expansion of my tree nursery. simuSolar\'s technology and support have empowered my business growth.',
            'author' => 'Opio Tonny',
            'role' => 'Farmer',
            'location' => 'Lira City, Uganda'
        ],
        [
            'quote' => 'This new irrigation system drastically cut my previous expenses, allowing me to invest the savings back into my business and expand my crop variety. I highly recommend the solar water pump to other farmers.',
            'author' => 'Rebecca Wakesa',
            'role' => 'Vegetable Grower',
            'location' => 'Mbale, Uganda'
        ]
    ];
}

// Fetch partners from database
$partners = [];
try {
    $stmt = $pdo->query("SELECT * FROM partners WHERE is_active = 1 ORDER BY sort_order ASC");
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Partners will use static content from media/partners/ directory
    $partners = [];
}

// Define services
$services = [
    [
        'icon' => 'sun',
        'title' => 'Residential Solar',
        'description' => 'Power your home with clean, reliable solar energy. Reduce electricity bills and enjoy uninterrupted power supply.',
        'link' => BASE_PATH . '/services/residential'
    ],
    [
        'icon' => 'building',
        'title' => 'Commercial Solar',
        'description' => 'Scale your business with sustainable energy solutions. Lower operational costs and meet sustainability goals.',
        'link' => BASE_PATH . '/services/commercial'
    ],
    [
        'icon' => 'droplet',
        'title' => 'Solar Water Pumping',
        'description' => 'Efficient solar-powered water pumping systems for irrigation, livestock, and domestic use.',
        'link' => BASE_PATH . '/services/water-pumping'
    ],
    [
        'icon' => 'wrench',
        'title' => 'Solar Maintenance',
        'description' => 'Professional maintenance and support services to keep your solar systems running at peak performance.',
        'link' => BASE_PATH . '/services/maintenance'
    ]
];

// Define "Why Choose Us" features
$features = [
    [
        'icon' => 'award',
        'title' => 'Quality Products',
        'description' => 'We use only premium, certified solar equipment from leading manufacturers with long-term warranties.'
    ],
    [
        'icon' => 'users',
        'title' => 'Expert Team',
        'description' => 'Our certified engineers and technicians bring years of experience in solar installation and maintenance.'
    ],
    [
        'icon' => 'headphones',
        'title' => 'After-Sales Support',
        'description' => 'Comprehensive support and maintenance services to ensure your system performs optimally for years.'
    ],
    [
        'icon' => 'credit-card',
        'title' => 'Financing Options',
        'description' => 'Flexible payment plans and financing options to make solar energy accessible and affordable.'
    ]
];

// Partner logos (static fallback)
$partnerLogos = [
    'CLASP_1.png',
    'Acumen-Fund-logo_1.png',
    'Kiva-logo_1.png',
    'Efficiency-for-Access_1.png',
    'USAID-logo_1.png',
    'SNV-logo_1.png',
    'Miller-Center-logo_1.png',
    'SEVIA-logo-1.png'
];
?>

<!-- ========================================================================
     HERO SECTION
     ======================================================================== -->
<section class="c-hero c-hero--video c-hero--home" id="hero">
    <!-- Video Background -->
    <div class="c-hero__background">
        <video autoplay muted loop playsinline id="hero-video" poster="<?php echo BASE_PATH; ?>/media/hero_images/hero-poster.jpg">
            <source src="<?php echo BASE_PATH; ?>/media/hero_videos/hero.mp4" type="video/mp4">
            <!-- Fallback image if video doesn't load -->
            <img src="<?php echo BASE_PATH; ?>/media/hero_images/hero-fallback.jpg" alt="Solar panels in African landscape">
        </video>
    </div>
    
    <!-- Overlay -->
    <div class="c-hero__overlay c-hero__overlay--gradient"></div>
    
    <!-- Content -->
    <div class="c-hero__container">
        <div class="c-hero__content">
            <!-- <span class="c-hero__badge">🌱 Sustainable Energy Solutions</span> -->
            <h1 class="c-hero__title">
                Powering <span class="c-hero__title-highlight">Africa's Future</span> with Clean Solar Energy
            </h1>
            <p class="c-hero__subtitle">
                Bringing sustainable solar solutions to homes, businesses, and farms across East Africa. 
                Join thousands who have already made the switch to clean, reliable, and affordable energy.
            </p>
            <div class="c-hero__actions">
                <a href="<?php echo BASE_PATH; ?>/contact" class="c-btn c-btn--secondary c-btn--lg">
                    <svg class="c-btn__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Talk to US
                </a>
                <a href="#services" class="c-btn c-btn--outline c-btn--lg">
                    Our Services
                    <svg class="c-btn__icon c-btn__icon--right" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Scroll Indicator -->
    <!-- <div class="c-hero__scroll" onclick="document.getElementById('impact').scrollIntoView({behavior: 'smooth'})">
        <div class="c-hero__scroll-icon"></div>
        <span>Scroll to explore</span>
    </div> -->
    
    <!-- Video Control -->
    <!-- <button class="c-hero__video-control" id="video-control" aria-label="Pause video">
        <svg viewBox="0 0 24 24" fill="currentColor" class="icon-pause">
            <rect x="6" y="4" width="4" height="16"/>
            <rect x="14" y="4" width="4" height="16"/>
        </svg>
        <svg viewBox="0 0 24 24" fill="currentColor" class="icon-play" style="display: none;">
            <polygon points="5,3 19,12 5,21"/>
        </svg>
    </button> -->
</section>

<!-- ========================================================================
     IMPACT SECTION (rebuilt)
     ======================================================================== -->
<section class="c-section c-section--impact" id="impact" aria-labelledby="impact-heading">
    <div class="c-section__container">
        <header class="c-section__header c-impact__header">
            <!-- <span class="c-section__pretitle c-impact__pretitle">Our Impact</span> -->
            <h2 class="c-section__title c-impact__title" id="impact-heading">Making a Difference Across Africa</h2>
            <p class="c-section__subtitle c-impact__subtitle">
                Since 2016, we've been transforming lives through sustainable solar energy solutions.
            </p>
        </header>

        <div class="c-impact__grid">
            <?php if (!empty($impactStats)): ?>
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
                    ?>
                    <article class="c-impact-card" data-index="<?php echo (int)$index; ?>">
                        <div class="c-impact-card__icon-wrap">
                            <?php
                            $icon = $stat['icon'] ?? '';
                            $iconSvg = '';
                            switch (strtolower($icon)) {
                                case 'home':
                                case 'house':
                                    $iconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>';
                                    break;
                                case 'leaf':
                                case 'eco':
                                    $iconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>';
                                    break;
                                case 'globe':
                                case 'world':
                                    $iconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>';
                                    break;
                                case 'users':
                                case 'people':
                                    $iconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
                                    break;
                                case 'sun':
                                case 'solar':
                                    $iconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
                                    break;
                                case 'zap':
                                case 'bolt':
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
                            echo $iconSvg;
                            ?>
                        </div>
                        <p class="c-impact-card__value" data-count="<?php echo e($impactCount); ?>" data-suffix="<?php echo e($impactSuffix); ?>">
                            <?php echo e($rawValue !== '' ? $rawValue : $impactCount . $impactSuffix); ?>
                        </p>
                        <h3 class="c-impact-card__label"><?php echo e($stat['label'] ?? ''); ?></h3>
                        <?php if (!empty($stat['sublabel']) || !empty($stat['description'])): ?>
                            <p class="c-impact-card__sublabel"><?php echo e($stat['sublabel'] ?? $stat['description'] ?? ''); ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <article class="c-impact-card" data-index="0">
                    <div class="c-impact-card__icon-wrap"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
                    <p class="c-impact-card__value" data-count="50000" data-suffix="+">50,000+</p>
                    <h3 class="c-impact-card__label">Homes Powered</h3>
                    <p class="c-impact-card__sublabel">Families enjoying clean energy</p>
                </article>
                <article class="c-impact-card" data-index="1">
                    <div class="c-impact-card__icon-wrap"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
                    <p class="c-impact-card__value" data-count="100000" data-suffix="+">100,000+</p>
                    <h3 class="c-impact-card__label">Tons CO2 Reduced</h3>
                    <p class="c-impact-card__sublabel">Environmental impact achieved</p>
                </article>
                <article class="c-impact-card" data-index="2">
                    <div class="c-impact-card__icon-wrap"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                    <p class="c-impact-card__value" data-count="10" data-suffix="+">10+</p>
                    <h3 class="c-impact-card__label">Years Experience</h3>
                    <p class="c-impact-card__sublabel">Industry expertise</p>
                </article>
                <article class="c-impact-card" data-index="3">
                    <div class="c-impact-card__icon-wrap"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                    <p class="c-impact-card__value" data-count="20" data-suffix="+">20+</p>
                    <h3 class="c-impact-card__label">Partners</h3>
                    <p class="c-impact-card__sublabel">Trusted collaborations</p>
                </article>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ========================================================================
     SERVICES SECTION
     ======================================================================== -->
<section class="c-section c-section--white" id="services">
    <div class="c-section__container">
        <header class="c-section__header">
            <!-- <span class="c-section__pretitle">What We Offer</span> -->
            <h2 class="c-section__title">Our Solar Solutions</h2>
            <p class="c-section__subtitle">
                Comprehensive solar energy services tailored to meet your specific needs
            </p>
        </header>
        
        <div class="c-services-grid">
            <?php foreach ($services as $service): ?>
                <article class="c-card c-card--service">
                    <div class="c-card__icon">
                        <?php if ($service['icon'] === 'sun'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="5"/>
                                <line x1="12" y1="1" x2="12" y2="3"/>
                                <line x1="12" y1="21" x2="12" y2="23"/>
                                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                                <line x1="1" y1="12" x2="3" y2="12"/>
                                <line x1="21" y1="12" x2="23" y2="12"/>
                                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                            </svg>
                        <?php elseif ($service['icon'] === 'building'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="4" y="2" width="16" height="20" rx="2" ry="2"/>
                                <path d="M9 22v-4h6v4"/>
                                <path d="M8 6h.01M16 6h.01M12 6h.01M8 10h.01M16 10h.01M12 10h.01M8 14h.01M16 14h.01M12 14h.01"/>
                            </svg>
                        <?php elseif ($service['icon'] === 'droplet'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                            </svg>
                        <?php elseif ($service['icon'] === 'wrench'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <h3 class="c-card__title"><?php echo e($service['title']); ?></h3>
                    <p class="c-card__description"><?php echo e($service['description']); ?></p>
                    <a href="<?php echo e($service['link']); ?>" class="c-card__link">
                        Learn More
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
        
        <footer class="c-section__footer">
            <a href="<?php echo BASE_PATH; ?>/services" class="c-btn c-btn--primary">
                View All Services
            </a>
        </footer>
    </div>
</section>

<!-- ========================================================================
     WHY CHOOSE US SECTION
     ======================================================================== -->
<section class="c-section c-section--gray" id="why-choose-us">
    <div class="c-section__container">
        <header class="c-section__header">
            <!-- <span class="c-section__pretitle">Why SimuSolar</span> -->
            <h2 class="c-section__title">Why Choose Us</h2>
            <p class="c-section__subtitle">
                We're committed to delivering excellence in every aspect of our service
            </p>
        </header>
        
        <div class="c-features-grid">
            <?php foreach ($features as $feature): ?>
                <div class="c-feature">
                    <div class="c-feature__icon">
                        <?php if ($feature['icon'] === 'award'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="8" r="7"/>
                                <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>
                            </svg>
                        <?php elseif ($feature['icon'] === 'users'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        <?php elseif ($feature['icon'] === 'headphones'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 18v-6a9 9 0 0 1 18 0v6"/>
                                <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>
                            </svg>
                        <?php elseif ($feature['icon'] === 'credit-card'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                <line x1="1" y1="10" x2="23" y2="10"/>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <h3 class="c-feature__title"><?php echo e($feature['title']); ?></h3>
                    <p class="c-feature__description"><?php echo e($feature['description']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ========================================================================
     TESTIMONIALS SECTION
     ======================================================================== -->
<section class="c-section c-section--testimonials" id="testimonials">
    <div class="c-section__container">
        <header class="c-section__header">
            <!-- <span class="c-section__pretitle">Testimonials</span> -->
            <h2 class="c-section__title">What Our Clients Say</h2>
            <p class="c-section__subtitle">
                Hear from farmers and businesses who trust simuSolar for their energy needs
            </p>
        </header>
        
        <div class="c-testimonials-slider" id="testimonials-slider">
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
            
            <!-- Slider Controls -->
            <div class="c-testimonials__controls">
                <button class="c-testimonials__prev" aria-label="Previous testimonial">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                </button>
                <div class="c-testimonials__dots">
                    <?php foreach ($testimonials as $index => $testimonial): ?>
                        <button class="c-testimonials__dot<?php echo $index === 0 ? ' is-active' : ''; ?>" 
                                data-index="<?php echo $index; ?>" 
                                aria-label="Go to testimonial <?php echo $index + 1; ?>"></button>
                    <?php endforeach; ?>
                </div>
                <button class="c-testimonials__next" aria-label="Next testimonial">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</section>

<!-- ========================================================================
     PARTNERS SECTION
     ======================================================================== -->
<section class="c-section c-section--partners" id="partners">
    <div class="c-section__container">
        <header class="c-section__header">
            <!-- <span class="c-section__pretitle">Our Partners</span> -->
            <h2 class="c-section__title">Trusted by Leading Organizations</h2>
            <p class="c-section__subtitle">
                We're proud to work with world-class partners who share our vision for sustainable energy
            </p>
        </header>
        
        <div class="c-partners-marquee">
            <div class="c-partners-marquee__track">
                <?php if (!empty($partners)): ?>
                    <?php 
                    // Collect partner logos
                    $partnerLogosList = [];
                    foreach ($partners as $partner) {
                        if (!empty($partner['image_path'])) {
                            $partnerLogosList[] = $partner;
                        }
                    }
                    // Duplicate for seamless loop
                    $duplicatedPartners = array_merge($partnerLogosList, $partnerLogosList);
                    ?>
                    <?php foreach ($duplicatedPartners as $partner): ?>
                        <div class="c-partner">
                            <img src="<?php echo BASE_PATH . '/' . e($partner['image_path']); ?>" 
                                 alt="<?php echo e($partner['name']); ?>" 
                                 class="c-partner__logo"
                                 loading="lazy">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Static fallback partner logos -->
                    <?php 
                    // Duplicate for seamless loop
                    $duplicatedLogos = array_merge($partnerLogos, $partnerLogos);
                    ?>
                    <?php foreach ($duplicatedLogos as $logo): ?>
                        <div class="c-partner">
                            <img src="<?php echo BASE_PATH; ?>/media/partners/<?php echo e($logo); ?>" 
                                 alt="Partner logo" 
                                 class="c-partner__logo"
                                 loading="lazy">
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ========================================================================
     CTA SECTION
     ======================================================================== -->
<section class="c-section c-section--cta" id="cta">
    <div class="c-section__container">
        <div class="c-section__header">
            <h2 class="c-section__title">Ready to Go Solar?</h2>
            <p class="c-section__subtitle">
                Join thousands of satisfied customers who have already made the switch to clean, 
                reliable solar energy. Get your free consultation today!
            </p>
        </div>
        <div class="c-section__actions">
            <a href="<?php echo BASE_PATH; ?>/quote" class="c-btn c-btn--secondary c-btn--lg">
                Get a Free Quote
                <svg class="c-btn__icon c-btn__icon--right" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
            <a href="<?php echo BASE_PATH; ?>/contact" class="c-btn c-btn--outline-light c-btn--lg">
                Contact Us
            </a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
