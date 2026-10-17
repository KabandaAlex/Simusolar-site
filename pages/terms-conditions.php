<?php
/**
 * Terms and Conditions Page
 * Preserves all legal content and custom CSS, but uses the modern crash-free layout.
 */

$pageTitle = 'Terms and Conditions - Tulima Solar';
$pageDescription = 'Read our terms and conditions to understand the rules and guidelines for using Tulima Solar\'s website and services.';
$bodyClass = 'page-legal';
$pageCss = 'products.css?v=2'; // Base styles

require_once __DIR__ . '/../config/database.php';
$pdo = getDb();

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// The safe fallback URL path
$siteBase = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '/tulima_php';

include __DIR__ . '/../includes/header.php';
?>

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
                    <span class="c-breadcrumb__current" aria-current="page">Terms & Conditions</span>
                </li>
            </ol>
        </nav> -->

        <div class="c-page-hero__content">
            <h1 class="c-page-hero__title">Terms and<br>Conditions</h1>
            <p class="c-page-hero__subtitle">
                Please read these terms carefully before using our website and services to understand your rights and responsibilities.
            </p>
            <div class="c-page-hero__actions">
                <a href="#terms-content" class="c-btn c-btn--secondary c-btn--lg">
                    <i class="fas fa-file-contract me-2"></i> Read Terms
                </a>
                <a href="<?php echo $siteBase; ?>/contact" class="c-btn c-btn--outline c-btn--lg">
                    <i class="fas fa-envelope me-2"></i> Contact Us
                </a>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--white" id="terms-content">
    <div class="c-section__container" style="max-width: 900px; margin: 0 auto;">
        
        <div style="border-bottom: 2px solid #eee; padding-bottom: 1.5rem; margin-bottom: 2rem;">
            <span style="display: inline-block; background: rgba(102, 0, 153, 0.1); color: var(--primary-purple); padding: 0.5rem 1rem; border-radius: 50px; font-weight: 600; font-size: 0.9rem; margin-bottom: 1rem;">
                Last Updated: December 2024 | Version 1.0
            </span>
            <h2 style="font-size: 2.5rem; color: var(--dark-gray); font-weight: 700;">Legal Agreement</h2>
        </div>

        <div style="color: #4a5568; line-height: 1.8; font-size: 1.05rem;">
            
            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">1. Acceptance of Terms</h3>
            <p>By accessing and using the website of Simusolar Ltd ("we," "our," or "us") at <a href="<?php echo $siteBase; ?>/" style="color: var(--accent-orange); text-decoration: none; font-weight: 600;">www.tulimasolar.com</a>, you accept and agree to be bound by the terms and provision of this agreement.</p>
            <p>If you do not agree to abide by the above, please do not use this service. These terms apply to all visitors, users, and others who access or use the website.</p>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">2. About Simusolar Ltd</h3>
            <p>Simusolar Ltd is a Ugandan company registered under the laws of Uganda, specializing in solar-powered agricultural solutions. We provide:</p>
            <ul style="margin-bottom: 1.5rem; padding-left: 1.5rem;">
                <li>Solar water pump systems</li>
                <li>Agricultural processing machines</li>
                <li>Installation and maintenance services</li>
                <li>Financing solutions</li>
                <li>Technical support and training</li>
            </ul>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">3. Use License</h3>
            <p>Permission is granted to temporarily download one copy of the materials on Simusolar's website for personal, non-commercial transitory viewing only. Under this license you may not:</p>
            <ul style="margin-bottom: 1.5rem; padding-left: 1.5rem;">
                <li>Modify or copy the materials</li>
                <li>Use the materials for any commercial purpose</li>
                <li>Attempt to decompile or reverse engineer any software on the website</li>
                <li>Remove any copyright or proprietary notations</li>
                <li>Transfer the materials to another person</li>
            </ul>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">4. Website Use and Conduct</h3>
            <p>When using our website, you agree to:</p>
            <ul style="margin-bottom: 1.5rem; padding-left: 1.5rem;">
                <li>Use the website only for lawful purposes</li>
                <li>Provide accurate information when contacting us</li>
                <li>Not attempt to gain unauthorized access to our systems</li>
                <li>Not interfere with or disrupt the website's functionality</li>
            </ul>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">5. Products and Services</h3>
            <p>We strive to provide accurate information about our products. However, product specifications may change without notice, images are for illustrative purposes, and prices are subject to change. Service delivery times may vary based on location, complexity, and availability.</p>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">6. Pricing and Payment Terms</h3>
            <p>All prices are quoted in Ugandan Shillings (UGX) unless otherwise stated and are subject to change without prior notice, applicable taxes, delivery, and installation costs. Payment terms will be specified in individual agreements.</p>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">7. Warranty and Returns</h3>
            <p>Our products come with manufacturer warranties (e.g., 25-year performance for panels, 2-5 years for pumps). Returns must be in original condition and initiated within 30 days of delivery. Custom or installed products may not be returnable.</p>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">8. Installation and Maintenance</h3>
            <p>Installation services are subject to site assessment, customer site preparation, weather, and local regulations. Maintenance services include regular inspections and preventive maintenance.</p>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">9. Limitation of Liability</h3>
            <p>In no event shall Simusolar Ltd be liable for any damages arising out of the use or inability to use the materials on our website.</p>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">10. Intellectual Property Rights</h3>
            <p>All content on this website is the property of Simusolar Ltd and is protected by copyright laws.</p>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">11. Governing Law & Changes</h3>
            <p>These terms are governed by the laws of Uganda. We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting on the website.</p>
        </div>
    </div>
</section>

<section class="c-section c-section--gray">
    <div class="c-section__container">
        <div class="c-section__header">
            <span class="c-section__pretitle">Get in Touch</span>
            <h2 class="c-section__title">Contact Information</h2>
            <p class="c-section__subtitle">If you have any questions about these Terms, please contact our legal team.</p>
        </div>

        <div class="contact-info-grid">
            <div class="contact-item">
                <strong>Address</strong>
                <span>Plot 483, Kaduyu road,<br>Ntinda - Kiwatule</span>
            </div>
            <div class="contact-item">
                <strong>Phone</strong>
                <a href="tel:+256800388883">+256 800 388 883</a>
            </div>
            <div class="contact-item">
                <strong>Email</strong>
                <a href="mailto:info@tulimasolar.com">info@tulimasolar.com</a>
            </div>
        </div>
    </div>
</section>

<section class="cta-section section-padding">
    <div class="cta-background">
        <div class="cta-overlay"></div>
    </div>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="cta-content">
                    <h2 class="cta-title mb-4">
                        Questions About <span class="text-gradient">Our Terms?</span><br>
                        We're Here to Help
                    </h2>
                    <p class="cta-subtitle mb-4">
                        Our legal team is ready to clarify any questions you may have about our terms and conditions. Get professional guidance and ensure you understand every aspect of our services.
                    </p>
                    <div class="cta-features">
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Professional Legal Guidance</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Clear Terms Explanation</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Transparent Communication</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="cta-action">
                    <div class="cta-card">
                        <h4 class="cta-card-title mb-3">
                            <i class="fas fa-gavel me-2"></i>Contact Our Legal Team
                        </h4>
                        <p class="cta-card-text mb-4">
                            Get <strong>professional assistance</strong> with any legal questions.
                        </p>
                        <a href="<?php echo $siteBase; ?>/contact" class="btn btn-light btn-lg w-100 mb-3">
                            <i class="fas fa-envelope me-2"></i>Contact Us
                        </a>
                        <a href="<?php echo $siteBase; ?>/privacy-policy" class="btn btn-outline-light btn-lg w-100 mb-3" style="border: 2px solid var(--primary-purple); color: var(--primary-purple);">
                            <i class="fas fa-shield-alt me-2"></i>Privacy Policy
                        </a>
                        <p class="cta-note">
                            <i class="fas fa-handshake me-2"></i>No obligation • Free consultation
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.cta-section {
    padding: 8rem 0 !important;
    position: relative !important;
    background: linear-gradient(135deg, var(--color-primary) 0%, #4a0078 50%, var(--color-secondary) 100%) !important;
    color: #ffffff !important;
    overflow: hidden !important;
}
.cta-section::before {
    content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><defs><pattern id="cta-pattern" width="80" height="80" patternUnits="userSpaceOnUse"><circle cx="40" cy="40" r="3" fill="%23ffffff" opacity="0.08"/><circle cx="20" cy="20" r="2" fill="%234caf50" opacity="0.06"/><circle cx="60" cy="60" r="2" fill="%23e98e39" opacity="0.05"/><path d="M10,10 L30,30 M70,10 L50,30 M10,70 L30,50 M70,70 L50,50" stroke="%23ffffff" stroke-width="1" opacity="0.04"/></pattern></defs><rect width="200" height="200" fill="url(%23cta-pattern)"/></svg>');
    z-index: 1;
}
.cta-background {
    position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(135deg, rgba(102, 0, 153, 0.9) 0%, rgba(76, 175, 80, 0.8) 100%);
    z-index: 2;
}
.cta-overlay {
    position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.3); z-index: 2;
}
.cta-content, .cta-action { position: relative; z-index: 3; }
.cta-title { font-size: 3rem !important; font-weight: 900 !important; line-height: 1.2 !important; margin-bottom: 1.5rem !important; color: white !important; }
.cta-subtitle { font-size: 1.25rem !important; line-height: 1.6 !important; margin-bottom: 2.5rem !important; color: rgba(255, 255, 255, 0.95) !important; }
.cta-features { display: flex; flex-wrap: wrap; gap: 1.25rem; margin-bottom: 2.5rem; }
.cta-features .feature { display: flex; align-items: center; gap: 0.75rem; font-weight: 600; padding: 1rem 1.5rem; background: rgba(255, 255, 255, 0.15); border-radius: 50px; border: 2px solid rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); }
.cta-features .feature i { color: var(--color-secondary); font-size: 1.1rem; }
.cta-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 2.5rem 2rem; text-align: center; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.2); }
.cta-card-title { color: var(--color-primary); font-size: 1.4rem; font-weight: 700; margin-bottom: 1rem; }
.cta-card-text { color: #333; font-size: 1rem; line-height: 1.6; margin-bottom: 1.5rem; }
.cta-card .btn { font-weight: 600; border-radius: 12px; transition: all 0.3s ease; margin-bottom: 1rem; display: block; padding: 1rem; text-decoration: none; }
.cta-card .btn-light { background: #f8f9fa; color: var(--color-primary); border: 2px solid transparent; }
.cta-card .btn-light:hover { background: var(--color-primary); color: white; transform: translateY(-2px); }
.cta-note { font-size: 0.9rem; color: #666; margin: 0; line-height: 1.5; }

.contact-info-grid { display: grid !important; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important; gap: 2rem !important; margin-top: 3rem !important; padding: 0 1rem !important; }
.contact-info-grid .contact-item { background: #ffffff !important; padding: 2.5rem 2rem !important; border-radius: 20px !important; border: 1px solid rgba(102, 0, 153, 0.08) !important; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08) !important; transition: all 0.4s ease !important; position: relative !important; text-align: center !important; }
.contact-info-grid .contact-item:hover { transform: translateY(-8px) !important; box-shadow: 0 20px 40px rgba(102, 0, 153, 0.15) !important; }
.contact-info-grid .contact-item strong { color: var(--color-primary) !important; font-size: 1.3rem !important; font-weight: 700 !important; margin-bottom: 1rem !important; display: flex !important; align-items: center !important; justify-content: center !important; gap: 0.75rem !important; text-transform: uppercase !important; }
.contact-info-grid .contact-item strong::before { font-family: "Font Awesome 5 Free"; font-weight: 900; font-size: 1.5rem; color: var(--color-secondary); }
.contact-info-grid .contact-item:nth-child(1) strong::before { content: "\f3c5"; }
.contact-info-grid .contact-item:nth-child(2) strong::before { content: "\f095"; }
.contact-info-grid .contact-item:nth-child(3) strong::before { content: "\f0e0"; }
.contact-info-grid .contact-item a, .contact-info-grid .contact-item span { color: #333 !important; text-decoration: none !important; font-weight: 600 !important; font-size: 1.1rem !important; display: block !important; margin-top: 0.5rem !important; }
.contact-info-grid .contact-item a:hover { color: var(--color-primary) !important; transform: scale(1.05) !important; }

@media (max-width: 768px) {
    .cta-section { padding: 4rem 0 !important; }
    .cta-title { font-size: 2.2rem !important; }
    .cta-features { gap: 1rem; }
    .contact-info-grid { grid-template-columns: 1fr !important; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>