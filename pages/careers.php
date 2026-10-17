<?php
/**
 * Careers Page - Tulima Solar
 * 
 * Displays job listings and career opportunities from the database.
 * Links to the job portal functionality.
 * 
 * @version 1.0.0
 */

// Page variables for header
$pageTitle = 'Careers - Join Our Team - TuliSimusolar';
$pageDescription = 'Explore career opportunities at Tulima Solar. Join our team and help bring solar energy solutions to communities across East Africa.';
$bodyClass = 'page-careers';
$pageCss = 'careers.css';
$pageJs = 'careers.js';

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

// Fetch job categories from database
try {
    $stmt = $pdo->query("
        SELECT jc.*, 
               (SELECT COUNT(*) FROM jobs j WHERE j.category_id = jc.id AND j.status = 'active') as job_count
        FROM job_categories jc
        WHERE jc.is_active = 1
        ORDER BY jc.sort_order ASC
    ");
    $jobCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $jobCategories = [];
    error_log('Error fetching job categories: ' . $e->getMessage());
}

// Fetch active job listings
try {
    $stmt = $pdo->query("
        SELECT j.*, 
               jc.name as category_name,
               jl.name as location_name,
               jl.city as location_city,
               jl.is_remote
        FROM jobs j
        LEFT JOIN job_categories jc ON j.category_id = jc.id
        LEFT JOIN job_locations jl ON j.location_id = jl.id
        WHERE j.status = 'active'
        AND (j.application_deadline IS NULL OR j.application_deadline > NOW())
        ORDER BY j.is_featured DESC, j.is_urgent DESC, j.created_at DESC
    ");
    $jobListings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $jobListings = [];
    error_log('Error fetching job listings: ' . $e->getMessage());
}

// Fetch featured jobs separately for the hero section
try {
    $stmt = $pdo->query("
        SELECT j.*, jc.name as category_name
        FROM jobs j
        LEFT JOIN job_categories jc ON j.category_id = jc.id
        WHERE j.status = 'active'
        AND j.is_featured = 1
        AND (j.application_deadline IS NULL OR j.application_deadline > NOW())
        ORDER BY j.created_at DESC
        LIMIT 3
    ");
    $featuredJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $featuredJobs = [];
}

// Job type labels
$jobTypeLabels = [
    'full-time' => 'Full Time',
    'part-time' => 'Part Time',
    'contract' => 'Contract',
    'internship' => 'Internship',
    'volunteer' => 'Volunteer'
];

// Employment type labels
$employmentTypeLabels = [
    'permanent' => 'Permanent',
    'temporary' => 'Temporary',
    'seasonal' => 'Seasonal'
];
?>

<!-- ========================================================================
     PAGE HERO SECTION
     ======================================================================== -->
<section class="c-page-hero c-page-hero--careers">
    <div class="c-page-hero__background">
        <div class="c-page-hero__overlay"></div>
    </div>
    <div class="c-page-hero__container">
        <!-- Breadcrumb -->
        <nav class="c-breadcrumb" aria-label="Breadcrumb">
            <ol class="c-breadcrumb__list">
                <li class="c-breadcrumb__item">
                    <a href="<?php echo e(BASE_PATH); ?>/" class="c-breadcrumb__link">Home</a>
                </li>
                <li class="c-breadcrumb__item">
                    <span class="c-breadcrumb__current" aria-current="page">Careers</span>
                </li>
            </ol>
        </nav>

        <div class="c-page-hero__content">
            <!-- <h1 class="c-page-hero__title">Join Our Team</h1> -->
            <p class="c-page-hero__subtitle">
                Be part of the renewable energy revolution. Join Simusolar and help bring 
                sustainable energy solutions to communities across East Africa.
            </p>
            <div class="c-page-hero__cta">
                <a href="#job-listings" class="c-btn c-btn--primary">
                    View Open Positions
                    <svg class="o-icon o-icon--sm" aria-hidden="true">
                        <use xlink:href="<?php echo e(BASE_PATH); ?>/assets/images/icons.svg#arrow-down"></use>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ========================================================================
     WHY WORK WITH US SECTION
     ======================================================================== -->
<section class="o-section o-section--why-work">
    <div class="o-container">
        <div class="o-section__header">
            <h2 class="o-section__title">Why Work at Simusolar?</h2>
            <p class="o-section__subtitle">
                We're more than just a company – we're a mission-driven team making a difference
            </p>
        </div>

        <div class="o-grid o-grid--4">
            <div class="c-feature">
                <div class="c-feature__icon">
                    <svg class="o-icon o-icon--lg" aria-hidden="true">
                        <use xlink:href="<?php echo e(BASE_PATH); ?>/assets/images/icons.svg#globe"></use>
                    </svg>
                </div>
                <h3 class="c-feature__title">Make an Impact</h3>
                <p class="c-feature__description">
                    Help bring clean energy to underserved communities and contribute to a sustainable future.
                </p>
            </div>

            <div class="c-feature">
                <div class="c-feature__icon">
                    <svg class="o-icon o-icon--lg" aria-hidden="true">
                        <use xlink:href="<?php echo e(BASE_PATH); ?>/assets/images/icons.svg#trending-up"></use>
                    </svg>
                </div>
                <h3 class="c-feature__title">Growth Opportunities</h3>
                <p class="c-feature__description">
                    Continuous learning, training programs, and clear career advancement paths.
                </p>
            </div>

            <div class="c-feature">
                <div class="c-feature__icon">
                    <svg class="o-icon o-icon--lg" aria-hidden="true">
                        <use xlink:href="<?php echo e(BASE_PATH); ?>/assets/images/icons.svg#users"></use>
                    </svg>
                </div>
                <h3 class="c-feature__title">Great Team</h3>
                <p class="c-feature__description">
                    Work alongside passionate, talented professionals who support each other.
                </p>
            </div>

            <div class="c-feature">
                <div class="c-feature__icon">
                    <svg class="o-icon o-icon--lg" aria-hidden="true">
                        <use xlink:href="<?php echo e(BASE_PATH); ?>/assets/images/icons.svg#heart"></use>
                    </svg>
                </div>
                <h3 class="c-feature__title">Benefits & Wellness</h3>
                <p class="c-feature__description">
                    Competitive compensation, health benefits, and work-life balance support.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ========================================================================
     FEATURED JOBS SECTION
     ======================================================================== -->
<?php if (!empty($featuredJobs)): ?>
<section class="o-section o-section--featured-jobs o-section--gray">
    <div class="o-container">
        <div class="o-section__header">
            <h2 class="o-section__title">Featured Opportunities</h2>
            <p class="o-section__subtitle">
                Our most exciting and urgent positions
            </p>
        </div>

        <div class="o-grid o-grid--3">
            <?php foreach ($featuredJobs as $job): ?>
                <div class="c-card c-card--job c-card--featured">
                    <div class="c-card__body">
                        <div class="c-card__header">
                            <?php if (!empty($job['category_name'])): ?>
                                <span class="c-badge c-badge--category"><?php echo e($job['category_name']); ?></span>
                            <?php endif; ?>
                            <?php if ($job['is_urgent']): ?>
                                <span class="c-badge c-badge--urgent">Urgent</span>
                            <?php endif; ?>
                        </div>

                        <h3 class="c-card__title">
                            <a href="<?php echo e(BASE_PATH . '/careers/' . $job['slug']); ?>">
                                <?php echo e($job['title']); ?>
                            </a>
                        </h3>

                        <div class="c-card__meta">
                            <span class="c-card__type">
                                <svg class="o-icon o-icon--sm" aria-hidden="true">
                                    <use xlink:href="<?php echo e(BASE_PATH); ?>/assets/images/icons.svg#clock"></use>
                                </svg>
                                <?php echo e($jobTypeLabels[$job['job_type']] ?? $job['job_type']); ?>
                            </span>
                            <?php if (!empty($job['location_city'])): ?>
                                <span class="c-card__location">
                                    <svg class="o-icon o-icon--sm" aria-hidden="true">
                                        <use xlink:href="<?php echo e(BASE_PATH); ?>/assets/images/icons.svg#map-pin"></use>
                                    </svg>
                                    <?php echo e($job['location_city']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <a href="<?php echo e(BASE_PATH . '/careers/' . $job['slug']); ?>" class="c-btn c-btn--primary">
                            View & Apply
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ========================================================================
     JOB LISTINGS SECTION
     ======================================================================== -->
<section id="job-listings" class="o-section o-section--job-listings">
    <div class="o-container">
        <div class="o-section__header">
            <h2 class="o-section__title">Open Positions</h2>
            <p class="o-section__subtitle">
                <?php echo count($jobListings); ?> position<?php echo count($jobListings) != 1 ? 's' : ''; ?> available
            </p>
        </div>

        <?php if (!empty($jobCategories)): ?>
        <!-- Category Filter -->
        <div class="c-filter-tabs">
            <button class="c-filter-tabs__item is-active" data-filter="all">
                All Positions
            </button>
            <?php foreach ($jobCategories as $category): ?>
                <?php if ($category['job_count'] > 0): ?>
                <button class="c-filter-tabs__item" data-filter="<?php echo e($category['slug']); ?>">
                    <?php echo e($category['name']); ?>
                    <span class="c-filter-tabs__count"><?php echo e($category['job_count']); ?></span>
                </button>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($jobListings)): ?>
            <div class="c-job-list">
                <?php foreach ($jobListings as $job): ?>
                    <article class="c-job-item" data-category="<?php echo e($job['category_name'] ?? ''); ?>">
                        <div class="c-job-item__header">
                            <h3 class="c-job-item__title">
                                <a href="<?php echo e(BASE_PATH . '/careers/' . $job['slug']); ?>">
                                    <?php echo e($job['title']); ?>
                                </a>
                            </h3>
                            <div class="c-job-item__badges">
                                <?php if ($job['is_featured']): ?>
                                    <span class="c-badge c-badge--featured">Featured</span>
                                <?php endif; ?>
                                <?php if ($job['is_urgent']): ?>
                                    <span class="c-badge c-badge--urgent">Urgent</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="c-job-item__meta">
                            <?php if (!empty($job['category_name'])): ?>
                                <span class="c-job-item__category">
                                    <svg class="o-icon o-icon--sm" aria-hidden="true">
                                        <use xlink:href="<?php echo e(BASE_PATH); ?>/assets/images/icons.svg#folder"></use>
                                    </svg>
                                    <?php echo e($job['category_name']); ?>
                                </span>
                            <?php endif; ?>

                            <span class="c-job-item__type">
                                <svg class="o-icon o-icon--sm" aria-hidden="true">
                                    <use xlink:href="<?php echo e(BASE_PATH); ?>/assets/images/icons.svg#clock"></use>
                                </svg>
                                <?php echo e($jobTypeLabels[$job['job_type']] ?? $job['job_type']); ?>
                            </span>

                            <?php if (!empty($job['location_name']) || !empty($job['is_remote'])): ?>
                                <span class="c-job-item__location">
                                    <svg class="o-icon o-icon--sm" aria-hidden="true">
                                        <use xlink:href="<?php echo e(BASE_PATH); ?>/assets/images/icons.svg#map-pin"></use>
                                    </svg>
                                    <?php 
                                    echo e($job['location_city'] ?? $job['location_name'] ?? '');
                                    if (!empty($job['is_remote'])) {
                                        echo !empty($job['location_city']) ? ' / Remote' : 'Remote';
                                    }
                                    ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($job['application_deadline'])): ?>
                                <span class="c-job-item__deadline">
                                    <svg class="o-icon o-icon--sm" aria-hidden="true">
                                        <use xlink:href="<?php echo e(BASE_PATH); ?>/assets/images/icons.svg#calendar"></use>
                                    </svg>
                                    Deadline: <?php echo date('M j, Y', strtotime($job['application_deadline'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($job['description'])): ?>
                            <p class="c-job-item__description">
                                <?php 
                                $description = strip_tags($job['description']);
                                echo e(strlen($description) > 200 ? substr($description, 0, 200) . '...' : $description);
                                ?>
                            </p>
                        <?php endif; ?>

                        <div class="c-job-item__actions">
                            <a href="<?php echo e(BASE_PATH . '/careers/' . $job['slug']); ?>" class="c-btn c-btn--primary c-btn--sm">
                                View Details
                            </a>
                            <a href="<?php echo e(BASE_PATH . '/careers/' . $job['slug'] . '#apply'); ?>" class="c-btn c-btn--outline c-btn--sm">
                                Apply Now
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="o-empty-state">
                <svg class="o-icon o-icon--xl" aria-hidden="true">
                    <use xlink:href="<?php echo e(BASE_PATH); ?>/assets/images/icons.svg#briefcase"></use>
                </svg>
                <h3>No Open Positions</h3>
                <p>There are no open positions at this time. Please check back later or submit your CV for future opportunities.</p>
                <a href="<?php echo e(BASE_PATH); ?>/contact" class="c-btn c-btn--primary">
                    Submit Your CV
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ========================================================================
     HOW TO APPLY SECTION
     ======================================================================== -->
<section class="o-section o-section--how-to-apply o-section--gray">
    <div class="o-container">
        <div class="o-section__header">
            <h2 class="o-section__title">How to Apply</h2>
            <p class="o-section__subtitle">
                Our simple application process
            </p>
        </div>

        <div class="o-grid o-grid--4">
            <div class="c-step">
                <div class="c-step__number">1</div>
                <h3 class="c-step__title">Find a Position</h3>
                <p class="c-step__description">
                    Browse our open positions and find one that matches your skills and interests.
                </p>
            </div>

            <div class="c-step">
                <div class="c-step__number">2</div>
                <h3 class="c-step__title">Prepare Your Documents</h3>
                <p class="c-step__description">
                    Update your CV/resume and prepare a cover letter explaining why you're a great fit.
                </p>
            </div>

            <div class="c-step">
                <div class="c-step__number">3</div>
                <h3 class="c-step__title">Submit Application</h3>
                <p class="c-step__description">
                    Fill out the application form and upload your documents through our portal.
                </p>
            </div>

            <div class="c-step">
                <div class="c-step__number">4</div>
                <h3 class="c-step__title">Interview</h3>
                <p class="c-step__description">
                    If shortlisted, you'll be invited for an interview with our team.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ========================================================================
     CTA SECTION
     ======================================================================== -->
<section class="o-section o-section--cta">
    <div class="o-container">
        <div class="c-cta">
            <div class="c-cta__content">
                <h2 class="c-cta__title">Don't See a Suitable Position?</h2>
                <p class="c-cta__description">
                    We're always looking for talented individuals. Send us your CV and we'll keep you in mind for future opportunities.
                </p>
            </div>
            <div class="c-cta__actions">
                <a href="<?php echo e(BASE_PATH); ?>/contact" class="c-btn c-btn--primary c-btn--lg">
                    Contact Us
                </a>
                <a href="mailto:careers@tulimasolar.com" class="c-btn c-btn--outline c-btn--lg">
                    careers@tulimasolar.com
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ========================================================================
     JOB FILTERING SCRIPT
     ======================================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.c-filter-tabs__item');
    const jobItems = document.querySelectorAll('.c-job-item');

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Update active state
            filterButtons.forEach(btn => btn.classList.remove('is-active'));
            this.classList.add('is-active');

            const filter = this.dataset.filter;

            // Filter jobs
            jobItems.forEach(job => {
                if (filter === 'all' || job.dataset.category.toLowerCase().replace(/\s+/g, '-') === filter) {
                    job.style.display = '';
                } else {
                    job.style.display = 'none';
                }
            });
        });
    });
});
</script>
