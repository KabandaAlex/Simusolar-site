<?php
/**
 * Tulima Solar - Job Detail Page
 * Modern, clean redesign using blog-style layout patterns
 * 
 * @version 2.0.0
 * @created 2026-02-26
 */

session_start();

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/job-portal-functions.php';

$pdo = getDb();

// Get site settings
$siteName = getSetting($pdo, 'site_name') ?: 'Tulima Solar';

// Get job slug or ID from URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : null;
$jobId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$slug && !$jobId) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/../pages/404.php';
    exit;
}

// Get job details using helper function (supports both slug and ID)
if ($slug) {
    $job = getJob($pdo, $slug, 'active');
} else {
    $job = getJob($pdo, $jobId, 'active');
}

if (!$job) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/../pages/404.php';
    exit;
}

// Get job tags
$tags = getJobTagsForJob($pdo, $job['id']);

// Get related jobs
$relatedJobs = getRelatedJobs($pdo, $job['id'], $job['category_id'], 3);

// Handle application submission
$applicationSubmitted = false;
$applicationError = null;
$prefillData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    $prefillData = [
        'name' => trim($_POST['applicant_name'] ?? ''),
        'email' => trim($_POST['applicant_email'] ?? ''),
        'phone' => trim($_POST['applicant_phone'] ?? ''),
        'cover_letter' => trim($_POST['cover_letter'] ?? '')
    ];
    
    // Map form field names to function expected names
    $applicationData = [
        'applicant_name' => $prefillData['name'],
        'applicant_email' => $prefillData['email'],
        'applicant_phone' => $prefillData['phone'],
        'cover_letter' => $prefillData['cover_letter']
    ];
    
    // Handle resume upload
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleJobFileUpload($_FILES['resume']);
        if ($uploadResult['success']) {
            $applicationData['resume_path'] = $uploadResult['path'];
        } else {
            $applicationError = $uploadResult['error'];
        }
    }
    
    // Submit application if no upload error
    if (!$applicationError) {
        $result = submitJobApplication($pdo, $job['id'], $applicationData);
        
        if ($result['success']) {
            $applicationSubmitted = true;
            $prefillData = []; // Clear form data on success
        } else {
            $applicationError = $result['error'];
        }
    }
}

$pageTitle = $job['title'] . ' - Careers';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Blog/Careers CSS -->
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/pages/blog.css">
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/pages/careers.css">

<!-- Job Detail Hero -->
<section class="job-detail-hero">
    <div class="o-container">
        <div class="job-detail-hero__content">
            <!-- Breadcrumb -->
            <nav class="job-detail-breadcrumb" aria-label="Breadcrumb">
                <ol>
                    <li><a href="<?php echo BASE_PATH; ?>">Home</a></li>
                    <li><a href="<?php echo BASE_PATH; ?>/careers/">Careers</a></li>
                    <li aria-current="page"><?php echo e($job['title']); ?></li>
                </ol>
            </nav>
            
            <div class="job-detail-hero__header">
                <h1 class="job-detail-hero__title"><?php echo e($job['title']); ?></h1>
                
                <div class="job-detail-hero__badges">
                    <?php if (!empty($job['is_featured'])): ?>
                        <span class="job-card__badge job-card__badge--featured">
                            <i class="fas fa-star"></i> Featured
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($job['is_urgent'])): ?>
                        <span class="job-card__badge job-card__badge--urgent">
                            <i class="fas fa-bolt"></i> Urgent Hiring
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="job-detail-hero__meta">
                <div class="job-detail-hero__meta-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo e($job['location_name'] ?: 'Remote'); ?></span>
                    <?php if (!empty($job['is_remote'])): ?>
                        <small>(Remote Available)</small>
                    <?php endif; ?>
                </div>
                <div class="job-detail-hero__meta-item">
                    <i class="fas fa-briefcase"></i>
                    <span><?php echo e(getJobTypeLabel($job['job_type'])); ?></span>
                </div>
                <div class="job-detail-hero__meta-item">
                    <i class="fas fa-clock"></i>
                    <span><?php echo e(getEmploymentTypeLabel($job['employment_type'])); ?></span>
                </div>
                <?php if (!empty($job['salary_min']) || !empty($job['salary_max'])): ?>
                    <div class="job-detail-hero__meta-item job-detail-hero__meta-item--highlight">
                        <i class="fas fa-money-bill-wave"></i>
                        <span><?php echo e(formatSalary($job['salary_min'], $job['salary_max'], $job['salary_currency'] ?? 'USD')); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($job['category_name']): ?>
                <a href="index.php?category=<?php echo e($job['category_slug']); ?>" class="job-detail-hero__category">
                    <i class="fas fa-folder"></i>
                    <?php echo e($job['category_name']); ?>
                </a>
            <?php endif; ?>
            
            <div class="job-detail-hero__actions">
                <a href="#apply-form" class="c-btn c-btn--primary c-btn--lg">
                    <i class="fas fa-paper-plane"></i> Apply Now
                </a>
                <button class="c-btn c-btn--outline c-btn--lg" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
            
            <?php if (!empty($job['application_deadline'])): ?>
                <div class="job-detail-hero__deadline">
                    <i class="fas fa-calendar-alt"></i>
                    Application Deadline: <?php echo date('F j, Y', strtotime($job['application_deadline'])); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Job Detail Content -->
<section class="job-detail-content">
    <div class="o-container">
        <div class="blog-layout">
            <!-- Main Content -->
            <main class="blog-main">
                <article class="job-detail">
                    <!-- Job Description -->
                    <section class="job-detail__section">
                        <h2 class="job-detail__section-title">
                            <i class="fas fa-info-circle"></i>
                            About This Role
                        </h2>
                        <div class="job-detail__content">
                            <?php echo $job['description']; ?>
                        </div>
                    </section>
                    
                    <!-- Responsibilities -->
                    <?php if (!empty($job['responsibilities'])): ?>
                        <section class="job-detail__section">
                            <h2 class="job-detail__section-title">
                                <i class="fas fa-tasks"></i>
                                Responsibilities
                            </h2>
                            <div class="job-detail__content">
                                <?php echo $job['responsibilities']; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                    
                    <!-- Requirements -->
                    <?php if (!empty($job['requirements'])): ?>
                        <section class="job-detail__section">
                            <h2 class="job-detail__section-title">
                                <i class="fas fa-clipboard-check"></i>
                                Requirements
                            </h2>
                            <div class="job-detail__content">
                                <?php echo $job['requirements']; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                    
                    <!-- Qualifications -->
                    <?php if (!empty($job['qualifications'])): ?>
                        <section class="job-detail__section">
                            <h2 class="job-detail__section-title">
                                <i class="fas fa-graduation-cap"></i>
                                Qualifications
                            </h2>
                            <div class="job-detail__content">
                                <?php echo $job['qualifications']; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                    
                    <!-- Tags -->
                    <?php if (!empty($tags)): ?>
                        <section class="job-detail__section job-detail__tags-section">
                            <h2 class="job-detail__section-title">
                                <i class="fas fa-tags"></i>
                                Skills & Tags
                            </h2>
                            <div class="job-detail__tags">
                                <?php foreach ($tags as $tag): ?>
                                    <span class="job-detail__tag"><?php echo e($tag['name']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </article>
                
                <!-- Application Form -->
                <section id="apply-form" class="job-application">
                    <div class="job-application__header">
                        <h2 class="job-application__title">
                            <i class="fas fa-paper-plane"></i>
                            Apply for This Position
                        </h2>
                        <p class="job-application__subtitle">
                            Fill out the form below to submit your application.
                        </p>
                    </div>
                    
                    <?php if ($applicationSubmitted): ?>
                        <div class="job-application__success">
                            <div class="job-application__success-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3>Application Submitted!</h3>
                            <p>Thank you for applying to the <?php echo e($job['title']); ?> position. We'll review your application and get back to you soon.</p>
                            <a href="index.php" class="c-btn c-btn--primary">
                                <i class="fas fa-arrow-left"></i> Browse More Jobs
                            </a>
                        </div>
                    <?php else: ?>
                        <?php if ($applicationError): ?>
                            <div class="job-application__error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo e($applicationError); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data" class="job-application__form" id="applicationForm">
                            <div class="job-application__row">
                                <div class="job-application__field">
                                    <label for="applicant_name" class="job-application__label">
                                        Full Name <span class="required">*</span>
                                    </label>
                                    <input type="text" 
                                           id="applicant_name" 
                                           name="applicant_name" 
                                           class="job-application__input" 
                                           placeholder="John Doe"
                                           value="<?php echo e($prefillData['name'] ?? ''); ?>"
                                           required>
                                </div>
                                <div class="job-application__field">
                                    <label for="applicant_email" class="job-application__label">
                                        Email Address <span class="required">*</span>
                                    </label>
                                    <input type="email" 
                                           id="applicant_email" 
                                           name="applicant_email" 
                                           class="job-application__input" 
                                           placeholder="john@example.com"
                                           value="<?php echo e($prefillData['email'] ?? ''); ?>"
                                           required>
                                </div>
                            </div>
                            
                            <div class="job-application__row">
                                <div class="job-application__field">
                                    <label for="applicant_phone" class="job-application__label">
                                        Phone Number
                                    </label>
                                    <input type="tel" 
                                           id="applicant_phone" 
                                           name="applicant_phone" 
                                           class="job-application__input" 
                                           placeholder="+254 712 345 678"
                                           value="<?php echo e($prefillData['phone'] ?? ''); ?>">
                                </div>
                                <div class="job-application__field">
                                    <label for="resume" class="job-application__label">
                                        Resume/CV <span class="required">*</span>
                                    </label>
                                    <div class="job-application__file-input">
                                        <input type="file" 
                                               id="resume" 
                                               name="resume" 
                                               accept=".pdf,.doc,.docx"
                                               required
                                               onchange="updateFileName(this)">
                                        <label for="resume" class="job-application__file-label" id="fileLabel">
                                            <i class="fas fa-upload"></i>
                                            <span>Choose file (PDF, DOC, DOCX - Max 5MB)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="job-application__field">
                                <label for="cover_letter" class="job-application__label">
                                    Cover Letter
                                </label>
                                <textarea id="cover_letter" 
                                          name="cover_letter" 
                                          class="job-application__textarea" 
                                          rows="6"
                                          placeholder="Tell us why you're the perfect fit for this role..."><?php echo e($prefillData['cover_letter'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="job-application__actions">
                                <button type="submit" name="apply" class="c-btn c-btn--primary c-btn--lg">
                                    <i class="fas fa-paper-plane"></i> Submit Application
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </section>
                
                <!-- Related Jobs -->
                <?php if (!empty($relatedJobs)): ?>
                    <section class="related-jobs">
                        <h2 class="related-jobs__title">
                            <i class="fas fa-briefcase"></i>
                            Similar Positions
                        </h2>
                        <div class="related-jobs__grid">
                            <?php foreach ($relatedJobs as $related): ?>
                                <?php
                                $relatedUrl = !empty($related['slug'])
                                    ? 'job.php?slug=' . e($related['slug'])
                                    : 'job.php?id=' . (int)$related['id'];
                                ?>
                                <a href="<?php echo $relatedUrl; ?>" class="related-job-card">
                                    <h3 class="related-job-card__title"><?php echo e($related['title']); ?></h3>
                                    <div class="related-job-card__meta">
                                        <span><i class="fas fa-map-marker-alt"></i> <?php echo e($related['location_name'] ?: 'Remote'); ?></span>
                                        <span><i class="fas fa-clock"></i> <?php echo e(getJobTypeLabel($related['job_type'])); ?></span>
                                    </div>
                                    <span class="related-job-card__link">
                                        View Details <i class="fas fa-arrow-right"></i>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </main>
            
            <!-- Sidebar -->
            <aside class="blog-sidebar">
                <!-- Job Summary Widget -->
                <div class="sidebar-widget job-summary-widget">
                    <h3 class="sidebar-widget__title">
                        <i class="fas fa-list-ul"></i>
                        Job Summary
                    </h3>
                    <ul class="job-summary__list">
                        <li class="job-summary__item">
                            <span class="job-summary__label">Job Type</span>
                            <span class="job-summary__value"><?php echo e(getJobTypeLabel($job['job_type'])); ?></span>
                        </li>
                        <li class="job-summary__item">
                            <span class="job-summary__label">Employment</span>
                            <span class="job-summary__value"><?php echo e(getEmploymentTypeLabel($job['employment_type'])); ?></span>
                        </li>
                        <li class="job-summary__item">
                            <span class="job-summary__label">Location</span>
                            <span class="job-summary__value"><?php echo e($job['location_name'] ?: 'Remote'); ?></span>
                        </li>
                        <?php if (!empty($job['salary_min']) || !empty($job['salary_max'])): ?>
                            <li class="job-summary__item">
                                <span class="job-summary__label">Salary</span>
                                <span class="job-summary__value"><?php echo e(formatSalary($job['salary_min'], $job['salary_max'], $job['salary_currency'] ?? 'USD')); ?></span>
                            </li>
                        <?php endif; ?>
                        <li class="job-summary__item">
                            <span class="job-summary__label">Posted</span>
                            <span class="job-summary__value"><?php echo date('M j, Y', strtotime($job['created_at'])); ?></span>
                        </li>
                        <?php if (!empty($job['application_deadline'])): ?>
                            <li class="job-summary__item job-summary__item--deadline">
                                <span class="job-summary__label">Deadline</span>
                                <span class="job-summary__value"><?php echo date('M j, Y', strtotime($job['application_deadline'])); ?></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <a href="#apply-form" class="c-btn c-btn--primary c-btn--block">
                        <i class="fas fa-paper-plane"></i> Apply Now
                    </a>
                </div>
                
                <!-- Share Widget -->
                <div class="sidebar-widget">
                    <h3 class="sidebar-widget__title">
                        <i class="fas fa-share-alt"></i>
                        Share This Job
                    </h3>
                    <div class="share-buttons">
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(BASE_PATH . '/careers/job.php?slug=' . $job['slug']); ?>" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="share-btn share-btn--linkedin"
                           aria-label="Share on LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(BASE_PATH . '/careers/job.php?slug=' . $job['slug']); ?>&text=<?php echo urlencode($job['title'] . ' - Career Opportunity at Tulima Solar'); ?>" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="share-btn share-btn--twitter"
                           aria-label="Share on Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(BASE_PATH . '/careers/job.php?slug=' . $job['slug']); ?>" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="share-btn share-btn--facebook"
                           aria-label="Share on Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="mailto:?subject=<?php echo urlencode($job['title'] . ' - Career Opportunity'); ?>&body=<?php echo urlencode('Check out this job opportunity: ' . BASE_PATH . '/careers/job.php?slug=' . $job['slug']); ?>" 
                           class="share-btn share-btn--email"
                           aria-label="Share via Email">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Categories Widget -->
                <div class="sidebar-widget">
                    <h3 class="sidebar-widget__title">
                        <i class="fas fa-folder"></i>
                        More in This Category
                    </h3>
                    <a href="index.php?category=<?php echo e($job['category_slug']); ?>" class="sidebar-widget__link">
                        View all <?php echo e($job['category_name']); ?> jobs
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <!-- Help Widget -->
                <div class="sidebar-widget sidebar-help">
                    <h3 class="sidebar-widget__title">
                        <i class="fas fa-question-circle"></i>
                        Need Help?
                    </h3>
                    <p class="sidebar-help__text">
                        If you have any questions about this position or the application process, feel free to contact us.
                    </p>
                    <a href="<?php echo BASE_PATH; ?>/contact" class="c-btn c-btn--outline c-btn--block">
                        <i class="fas fa-envelope"></i> Contact Us
                    </a>
                </div>
            </aside>
        </div>
    </div>
</section>

<script>
// Update file input label with selected file name
function updateFileName(input) {
    const label = document.getElementById('fileLabel');
    const span = label.querySelector('span');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileSize = (file.size / 1024 / 1024).toFixed(2); // MB
        
        // Check file size (5MB limit)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB limit. Please choose a smaller file.');
            input.value = '';
            span.textContent = 'Choose file (PDF, DOC, DOCX - Max 5MB)';
            return;
        }
        
        span.textContent = file.name + ' (' + fileSize + ' MB)';
        label.classList.add('has-file');
    } else {
        span.textContent = 'Choose file (PDF, DOC, DOCX - Max 5MB)';
        label.classList.remove('has-file');
    }
}

// Form validation
document.getElementById('applicationForm')?.addEventListener('submit', function(e) {
    const resume = document.getElementById('resume');
    if (resume && !resume.files.length) {
        e.preventDefault();
        alert('Please upload your resume/CV.');
        return false;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
