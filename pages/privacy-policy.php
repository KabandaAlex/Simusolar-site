<?php
/**
 * Privacy Policy Page
 * Preserves all legal content while adopting the modern 'c-' design system layout.
 */

$pageTitle = 'Privacy Policy - Tulima Solar';
$pageDescription = 'Read our privacy policy to understand how Tulima Solar collects, uses, and protects your personal information when you use our website and services.';
$bodyClass = 'page-legal';
$pageCss = 'products.css?v=2'; // Use the main CSS file for consistent components

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
        <nav class="c-breadcrumb" aria-label="Breadcrumb">
            <ol class="c-breadcrumb__list">
                <li class="c-breadcrumb__item">
                    <a href="<?php echo $siteBase; ?>/" class="c-breadcrumb__link">Home</a>
                </li>
                <li class="c-breadcrumb__item">
                    <span class="c-breadcrumb__current" aria-current="page">Privacy Policy</span>
                </li>
            </ol>
        </nav>

        <div class="c-page-hero__content">
            <h1 class="c-page-hero__title">Privacy Policy<br>& Protection</h1>
            <p class="c-page-hero__subtitle">
                Your privacy is important to us. Learn how we collect, use, and protect your information to ensure your data security and trust.
            </p>
            <div class="c-page-hero__actions">
                <a href="#policy-content" class="c-btn c-btn--secondary c-btn--lg">
                    <i class="fas fa-shield-alt me-2"></i> Read Policy
                </a>
                <a href="<?php echo $siteBase; ?>/contact" class="c-btn c-btn--outline c-btn--lg">
                    <i class="fas fa-envelope me-2"></i> Contact Us
                </a>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--white" id="policy-content">
    <div class="c-section__container" style="max-width: 900px; margin: 0 auto;">
        
        <div style="border-bottom: 2px solid #eee; padding-bottom: 1.5rem; margin-bottom: 2rem;">
            <span style="display: inline-block; background: rgba(102, 0, 153, 0.1); color: var(--primary-purple); padding: 0.5rem 1rem; border-radius: 50px; font-weight: 600; font-size: 0.9rem; margin-bottom: 1rem;">
                Last Updated: December 2024 | Version 1.0
            </span>
            <h2 style="font-size: 2.5rem; color: var(--dark-gray); font-weight: 700;">Data Privacy Practices</h2>
        </div>

        <div style="color: #4a5568; line-height: 1.8; font-size: 1.05rem;">
            
            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">1. Introduction</h3>
            <p>Simusolar Ltd ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website at <a href="<?php echo $siteBase; ?>/" style="color: var(--accent-orange); text-decoration: none; font-weight: 600;">www.tulimasolar.com</a> or use our services.</p>
            <p>By using our website and services, you consent to the data practices described in this policy. If you do not agree with our policies and practices, please do not use our website.</p>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">2. Information We Collect</h3>
            
            <h4 style="color: var(--dark-gray); font-size: 1.2rem; margin: 1.5rem 0 0.5rem; font-weight: 600;">2.1 Personal Information</h4>
            <p>We may collect personal information that you voluntarily provide to us when you:</p>
            <ul style="margin-bottom: 1.5rem; padding-left: 1.5rem;">
                <li>Fill out contact forms on our website</li>
                <li>Request quotes for our products and services</li>
                <li>Subscribe to our newsletter</li>
                <li>Apply for financing options</li>
                <li>Contact us for customer support</li>
            </ul>
            <p>This information may include name and contact details (email, phone number, address), company information (if applicable), project requirements and specifications, and financial information for financing applications.</p>

            <h4 style="color: var(--dark-gray); font-size: 1.2rem; margin: 1.5rem 0 0.5rem; font-weight: 600;">2.2 Automatically Collected Information</h4>
            <p>When you visit our website, we automatically collect certain information about your device, including:</p>
            <ul style="margin-bottom: 1.5rem; padding-left: 1.5rem;">
                <li>IP address and location data</li>
                <li>Browser type and version</li>
                <li>Operating system</li>
                <li>Pages visited and time spent on pages</li>
                <li>Referring website and Device information</li>
            </ul>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">3. How We Use Your Information</h3>
            <p>We use the information we collect for various purposes, including:</p>
            <ul style="margin-bottom: 1.5rem; padding-left: 1.5rem;">
                <li><strong>Providing Services:</strong> To provide you with solar solutions, quotes, and customer support.</li>
                <li><strong>Communication:</strong> To respond to your inquiries and send you relevant information.</li>
                <li><strong>Marketing:</strong> To send you promotional materials about our products and services (with your consent).</li>
                <li><strong>Improving Services:</strong> To analyze website usage and improve our services.</li>
                <li><strong>Legal Compliance:</strong> To comply with applicable laws and regulations.</li>
                <li><strong>Security:</strong> To protect against fraud and ensure website security.</li>
            </ul>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">4. Information Sharing and Disclosure</h3>
            <p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except in the following circumstances:</p>
            <ul style="margin-bottom: 1.5rem; padding-left: 1.5rem;">
                <li><strong>Service Providers:</strong> We may share information with trusted third-party service providers who assist us in operating our website and providing services.</li>
                <li><strong>Legal Requirements:</strong> We may disclose information if required by law or to protect our rights and safety.</li>
                <li><strong>Business Transfers:</strong> In the event of a merger, acquisition, or sale of assets, your information may be transferred.</li>
            </ul>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">5. Data Security</h3>
            <p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. These measures include encryption of sensitive data, regular security assessments, secure data transmission protocols, and employee training. However, no method of transmission over the internet or electronic storage is 100% secure, and we cannot guarantee absolute security.</p>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">6. Cookies and Tracking Technologies</h3>
            <p>We use cookies and similar tracking technologies to enhance your browsing experience, remember your preferences, and analyze website performance. You can control cookie settings through your browser preferences. However, disabling cookies may affect website functionality.</p>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">7. Your Rights and Choices</h3>
            <p>You have the right to request access to your personal information, ask for corrections to inaccurate information, request deletion of your data, or object to the processing of your personal information. To exercise these rights, please contact us.</p>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">8. Data Retention & International Transfers</h3>
            <p>We retain your personal information for as long as necessary to fulfill the purposes outlined in this policy. Your information may be transferred to and processed in countries other than your own. We ensure that such transfers comply with applicable data protection laws.</p>

            <h3 style="color: var(--primary-purple); font-size: 1.5rem; margin: 2.5rem 0 1rem; font-weight: 600;">9. Changes to This Privacy Policy</h3>
            <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the "Last Updated" date. We encourage you to review this policy periodically.</p>
        </div>
    </div>
</section>

<section class="c-section c-section--gray" id="contact-info">
    <div class="c-section__container">
        <div class="c-section__header">
            <span class="c-section__pretitle">Get in Touch</span>
            <h2 class="c-section__title">Contact Our Privacy Team</h2>
            <p class="c-section__subtitle">If you have any questions about this Privacy Policy or our data practices, please contact us.</p>
        </div>

        <div class="c-section__content">
            <div class="c-card-grid c-card-grid--3">
                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2.5rem 2rem 0; color: var(--primary-purple); text-align: center;">
                        <i class="fas fa-map-marker-alt fa-3x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Head Office</h3>
                        <p class="c-card__description" style="font-size: 1.1rem; font-weight: 500;">Plot 483, Kaduyu road,<br>Ntinda - Kiwatule</p>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2.5rem 2rem 0; color: var(--accent-orange); text-align: center;">
                        <i class="fas fa-phone fa-3x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Phone</h3>
                        <a href="tel:+256800388883" style="font-size: 1.1rem; font-weight: 600; color: var(--dark-gray); text-decoration: none; display: block; margin-top: 0.5rem;">+256 800 388 883</a>
                    </div>
                </article>

                <article class="c-card c-card--lift">
                    <div class="c-card__icon" style="padding: 2.5rem 2rem 0; color: var(--accent-green); text-align: center;">
                        <i class="fas fa-envelope fa-3x"></i>
                    </div>
                    <div class="c-card__body" style="text-align: center;">
                        <h3 class="c-card__title">Email</h3>
                        <a href="mailto:info@tulimasolar.com" style="font-size: 1.1rem; font-weight: 600; color: var(--dark-gray); text-decoration: none; display: block; margin-top: 0.5rem;">info@tulimasolar.com</a>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="c-section c-section--cta" id="privacy-cta">
    <div class="c-section__container">
        <div class="c-section__header" style="max-width: 800px; margin: 0 auto; text-align: center;">
            <h2 class="c-section__title">Questions About Our Privacy Policy?</h2>
            <p class="c-section__subtitle">
                Our privacy team is ready to answer any questions about how we collect, use, and protect your personal information. Get clear explanations about your data rights.
            </p>
            
            <div style="margin: 2rem 0; display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; color: white;">
                <span><i class="fas fa-check-circle text-primary me-2"></i> Data Protection</span>
                <span><i class="fas fa-check-circle text-primary me-2"></i> Secure Processing</span>
                <span><i class="fas fa-check-circle text-primary me-2"></i> Transparent Practices</span>
            </div>
        </div>
        
        <div class="c-section__actions" style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
            <a href="<?php echo $siteBase; ?>/contact" class="c-btn c-btn--secondary c-btn--lg">
                <i class="fas fa-envelope me-2"></i> Contact Privacy Team
            </a>
            <a href="<?php echo $siteBase; ?>/terms-conditions" class="c-btn c-btn--outline-light c-btn--lg">
                <i class="fas fa-file-contract me-2"></i> Terms & Conditions
            </a>
        </div>
        <p style="text-align: center; color: rgba(255,255,255,0.7); margin-top: 1.5rem; font-size: 0.9rem;">
            <i class="fas fa-lock me-1"></i> Confidential • Professional • Secure communication
        </p>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>