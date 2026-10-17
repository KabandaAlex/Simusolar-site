<?php
/**
 * Contact Page - Simiusolar (Premium Design)
 * 
 * Contact page with form, office locations, and contact information.
 * 
 * @version 2.0.0
 */

// Page variables for header
$pageTitle = "Contact Us";
$pageDescription = 'Get in touch with Simusolar for all your solar energy needs. Contact us for consultations, quotes, or any questions about our solar solutions.';
$bodyClass = 'page-contact';
$pageCss = 'contact.css?v=' . filemtime(__DIR__ . '/../assets/css/pages/contact.css');
$pageJs = 'contact.js';

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

// Get office locations
$officeLocations = [];
try {
    $stmt = $pdo->query('SELECT * FROM office_locations WHERE is_active = 1 ORDER BY sort_order ASC');
    $officeLocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Use default locations if query fails
    $officeLocations = [];
}

// Get contact info
$contactInfo = [];
try {
    $stmt = $pdo->query('SELECT * FROM contact_info ORDER BY sort_order ASC');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $contactInfo[$row['key']] = $row['value'];
    }
} catch (Exception $e) {
    // Use defaults if query fails
}

// Default contact info
$phone = $contactInfo['phone'] ?? '+256 700 123 456';
$email = $contactInfo['email'] ?? 'info@simusolar.com';
$address = $contactInfo['address'] ?? 'Kampala, Uganda';
$whatsapp = $contactInfo['whatsapp'] ?? '+256 700 123 456';
$businessHours = $contactInfo['business_hours'] ?? 'Mon - Fri: 8:00 AM - 6:00 PM | Sat: 9:00 AM - 2:00 PM';

// Get inquiry types from database
$inquiryTypes = [];
try {
    $stmt = $pdo->query('SELECT * FROM contact_inquiry_types WHERE is_active = 1 ORDER BY sort_order ASC');
    $inquiryTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback to default types if table doesn't exist
    $inquiryTypes = [
        ['id' => 1, 'slug' => 'general', 'name' => 'General Inquiry', 'icon' => 'help-circle'],
        ['id' => 2, 'slug' => 'quote', 'name' => 'Request a Quote', 'icon' => 'file-text'],
        ['id' => 3, 'slug' => 'support', 'name' => 'Technical Support', 'icon' => 'tool'],
        ['id' => 4, 'slug' => 'partnership', 'name' => 'Partnership', 'icon' => 'users'],
        ['id' => 5, 'slug' => 'careers', 'name' => 'Careers', 'icon' => 'briefcase'],
    ];
}

// Get inquiry reasons grouped by type
$inquiryReasons = [];
try {
    $stmt = $pdo->query('SELECT * FROM contact_inquiry_reasons WHERE is_active = 1 ORDER BY inquiry_type_id, sort_order ASC');
    $reasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($reasons as $reason) {
        $inquiryReasons[$reason['inquiry_type_id']][] = $reason;
    }
} catch (Exception $e) {
    // Fallback reasons
    $inquiryReasons = [
        1 => [['reason' => 'Product Information'], ['reason' => 'Service Details'], ['reason' => 'General Question'], ['reason' => 'Feedback'], ['reason' => 'Other']],
        2 => [['reason' => 'Residential Solar System'], ['reason' => 'Commercial Solar Installation'], ['reason' => 'Solar Water Pumping'], ['reason' => 'Solar Maintenance'], ['reason' => 'Battery Storage']],
        3 => [['reason' => 'System Not Working'], ['reason' => 'Performance Issues'], ['reason' => 'Warranty Claim'], ['reason' => 'Maintenance Request']],
        4 => [['reason' => 'Distribution Partnership'], ['reason' => 'Installation Partnership'], ['reason' => 'Supplier Inquiry']],
        5 => [['reason' => 'Job Application'], ['reason' => 'Internship Inquiry'], ['reason' => 'CV Submission']],
    ];
}

// Handle form submission
$formSubmitted = false;
$formError = null;
$formSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $emailInput = trim($_POST['email'] ?? '');
    $phoneInput = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $inquiryType = trim($_POST['inquiry_type'] ?? 'general');
    $inquiryReason = trim($_POST['inquiry_reason'] ?? '');
    $preferredContact = trim($_POST['preferred_contact'] ?? 'email');
    $subscribeNewsletter = isset($_POST['subscribe_newsletter']) ? 1 : 0;
    
    // Validate required fields
    if (empty($name) || empty($emailInput) || empty($message)) {
        $formError = 'Please fill in all required fields.';
    } elseif (!filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
        $formError = 'Please enter a valid email address.';
    } else {
        try {
            // Check if preferred_contact column exists
            try {
                $pdo->query("SELECT preferred_contact FROM contact_submissions LIMIT 1");
                $hasPreferredContactColumn = true;
            } catch (Exception $e) {
                $hasPreferredContactColumn = false;
            }
            
            // Check if subscribe_newsletter column exists
            try {
                $pdo->query("SELECT subscribe_newsletter FROM contact_submissions LIMIT 1");
                $hasNewsletterColumn = true;
            } catch (Exception $e) {
                $hasNewsletterColumn = false;
            }
            
            // Build the insert based on available columns
            $fullInquiry = $inquiryType . ($inquiryReason ? ' - ' . $inquiryReason : '');
            
            if ($hasPreferredContactColumn && $hasNewsletterColumn) {
                $stmt = $pdo->prepare('
                    INSERT INTO contact_submissions (name, email, phone, subject, message, inquiry_type, preferred_contact, subscribe_newsletter, status, submitted_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, "new", NOW())
                ');
                $stmt->execute([$name, $emailInput, $phoneInput, $subject, $message, $fullInquiry, $preferredContact, $subscribeNewsletter]);
            } elseif ($hasNewsletterColumn) {
                $stmt = $pdo->prepare('
                    INSERT INTO contact_submissions (name, email, phone, subject, message, inquiry_type, subscribe_newsletter, status, submitted_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, "new", NOW())
                ');
                $stmt->execute([$name, $emailInput, $phoneInput, $subject, $message, $fullInquiry, $subscribeNewsletter]);
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO contact_submissions (name, email, phone, subject, message, status, submitted_at)
                    VALUES (?, ?, ?, ?, ?, "new", NOW())
                ');
                $stmt->execute([$name, $emailInput, $phoneInput, $subject, $message]);
            }
            
            $formSubmitted = true;
            $formSuccess = true;

            // Attempt to send notification email via centralized SMTP
            try {
                require_once __DIR__ . '/../includes/mailer.php';
                $cfg = getSmtpSettings($pdo);

                // Determine recipients: use explicit contact recipients if set, otherwise fallback to primary contact email
                $recipients = [];
                if (!empty($cfg['contact_recipients'])) {
                    foreach (explode(',', $cfg['contact_recipients']) as $rcpt) {
                        $rcpt = trim($rcpt);
                        if ($rcpt !== '' && filter_var($rcpt, FILTER_VALIDATE_EMAIL)) {
                            $recipients[] = $rcpt;
                        }
                    }
                } elseif (!empty($email)) {
                    // Fallback: contact_info email from DB
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $recipients[] = $email;
                    }
                }

                if (!empty($recipients) && $cfg['enabled']) {
                    $subjectLine = 'New Contact Form Submission from ' . $name;

                    $htmlBody = '<h2>New Contact Form Submission</h2>'
                        . '<p><strong>Name:</strong> ' . e($name) . '</p>'
                        . '<p><strong>Email:</strong> ' . e($emailInput) . '</p>'
                        . '<p><strong>Phone:</strong> ' . e($phoneInput) . '</p>'
                        . '<p><strong>Subject:</strong> ' . e($subject) . '</p>'
                        . '<p><strong>Inquiry Type:</strong> ' . e($inquiryType) . '</p>'
                        . '<p><strong>Inquiry Reason:</strong> ' . e($inquiryReason) . '</p>'
                        . '<p><strong>Preferred Contact:</strong> ' . e($preferredContact) . '</p>'
                        . '<p><strong>Subscribe to Newsletter:</strong> ' . ($subscribeNewsletter ? 'Yes' : 'No') . '</p>'
                        . '<p><strong>Message:</strong><br>' . nl2br(e($message)) . '</p>';

                    $textBody = "New contact form submission\n\n"
                        . "Name: {$name}\n"
                        . "Email: {$emailInput}\n"
                        . "Phone: {$phoneInput}\n"
                        . "Subject: {$subject}\n"
                        . "Inquiry Type: {$inquiryType}\n"
                        . "Inquiry Reason: {$inquiryReason}\n"
                        . "Preferred Contact: {$preferredContact}\n"
                        . "Subscribe to Newsletter: " . ($subscribeNewsletter ? 'Yes' : 'No') . "\n\n"
                        . "Message:\n{$message}\n";

                    foreach ($recipients as $rcpt) {
                        $res = sendSmtpEmail($pdo, $rcpt, $subjectLine, $htmlBody, $textBody);
                        if (!$res['success']) {
                            error_log('Contact form email error for ' . $rcpt . ': ' . $res['error']);
                        }
                    }
                }
            } catch (Throwable $mailEx) {
                error_log('Contact form mailer error: ' . $mailEx->getMessage());
            }

            // Clear form data
            $name = $emailInput = $phoneInput = $subject = $message = '';
        } catch (Exception $e) {
            $formError = 'Sorry, there was an error submitting your message. Please try again later.';
            error_log('Contact form error: ' . $e->getMessage());
        }
    }
}

// Preserve form data on error
$formData = [
    'name' => $name ?? '',
    'email' => $emailInput ?? '',
    'phone' => $phoneInput ?? '',
    'subject' => $subject ?? '',
    'message' => $message ?? '',
    'inquiry_type' => $inquiryType ?? 'general',
    'inquiry_reason' => $inquiryReason ?? '',
    'preferred_contact' => $preferredContact ?? 'email',
    'subscribe_newsletter' => $subscribeNewsletter ?? 0
];

// Convert inquiry reasons to JSON for JavaScript
$inquiryReasonsJson = json_encode($inquiryReasons);
?>

<main id="main-content" class="site-main">
    <!-- Page Hero - Premium Design -->
    <section class="c-page-hero">
        <div class="c-page-hero__background" style="background-image: url('<?php echo $siteBase; ?>/assets/images/contact-hero.jpg');"></div>
        <div class="c-page-hero__overlay"></div>
        
        <!-- Animated geometric shapes -->
        <div class="c-page-hero__shapes">
            <div class="c-page-hero__shape c-page-hero__shape--1"></div>
            <div class="c-page-hero__shape c-page-hero__shape--2"></div>
            <div class="c-page-hero__shape c-page-hero__shape--3"></div>
        </div>
        
        <div class="c-page-hero__content">
            <div class="c-page-hero__container">
                <!-- <span class="c-page-hero__pretitle">Get In Touch</span> -->
                <h1 class="c-page-hero__title">Contact Us</h1>
                <p class="c-page-hero__subtitle">We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="c-section c-section--padding-lg">
        <div class="c-section__container">
            <div class="c-contact-grid">
                <!-- Contact Form -->
                <div class="c-contact-form-wrapper">
                    <div class="c-section__header c-section__header--left">
                        <span class="c-section__pretitle">Send Message</span>
                        <h2 class="c-section__title">Get in Touch</h2>
                        <p class="c-section__subtitle">Fill out the form below and our team will get back to you within 24 hours.</p>
                    </div>

                    <?php if ($formSuccess): ?>
                        <div class="c-alert c-alert--success">
                            <div class="c-alert__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                            </div>
                            <div class="c-alert__content">
                                <h4 class="c-alert__title">Message Sent!</h4>
                                <p class="c-alert__message">Thank you for contacting us. We'll get back to you shortly.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($formError): ?>
                        <div class="c-alert c-alert--error">
                            <div class="c-alert__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="15" y1="9" x2="9" y2="15"/>
                                    <line x1="9" y1="9" x2="15" y2="15"/>
                                </svg>
                            </div>
                            <div class="c-alert__content">
                                <h4 class="c-alert__title">Error</h4>
                                <p class="c-alert__message"><?php echo e($formError); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!$formSuccess): ?>
                        <form class="c-contact-form" method="POST" action="">
                            <div class="c-form__row">
                                <div class="c-form__group">
                                    <label for="name" class="c-form__label">Full Name <span class="c-form__required">*</span></label>
                                    <input type="text" id="name" name="name" class="c-form__input" placeholder="John Doe" value="<?php echo e($formData['name']); ?>" required>
                                </div>
                                <div class="c-form__group">
                                    <label for="email" class="c-form__label">Email Address <span class="c-form__required">*</span></label>
                                    <input type="email" id="email" name="email" class="c-form__input" placeholder="john@example.com" value="<?php echo e($formData['email']); ?>" required>
                                </div>
                            </div>

                            <div class="c-form__row">
                                <div class="c-form__group">
                                    <label for="phone" class="c-form__label">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" class="c-form__input" placeholder="+256 700 123 456" value="<?php echo e($formData['phone']); ?>">
                                </div>
                                <div class="c-form__group">
                                    <label for="subject" class="c-form__label">Subject</label>
                                    <input type="text" id="subject" name="subject" class="c-form__input" placeholder="How can we help?" value="<?php echo e($formData['subject']); ?>">
                                </div>
                            </div>

                            <!-- Inquiry Type Dropdown -->
                            <div class="c-form__group">
                                <label for="inquiry_type" class="c-form__label">Inquiry Type</label>
                                <select id="inquiry_type" name="inquiry_type" class="c-form__select">
                                    <?php foreach ($inquiryTypes as $type): ?>
                                        <option value="<?php echo e($type['slug']); ?>" data-id="<?php echo $type['id']; ?>" <?php echo $formData['inquiry_type'] === $type['slug'] ? 'selected' : ''; ?>>
                                            <?php echo e($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Inquiry Reason Dropdown (Dynamic) -->
                            <div class="c-form__group" id="inquiry_reason_wrapper">
                                <label for="inquiry_reason" class="c-form__label">Reason for Inquiry</label>
                                <select id="inquiry_reason" name="inquiry_reason" class="c-form__select">
                                    <option value="">Select a reason...</option>
                                    <!-- Options will be populated dynamically via JavaScript -->
                                </select>
                            </div>

                            <div class="c-form__group">
                                <label for="message" class="c-form__label">Message <span class="c-form__required">*</span></label>
                                <textarea id="message" name="message" class="c-form__textarea" rows="5" placeholder="Tell us about your project or inquiry..." required><?php echo e($formData['message']); ?></textarea>
                                <div class="c-form__textarea-footer">
                                    <span class="c-form__hint">Please provide as much detail as possible.</span>
                                    <span class="c-form__char-counter"><span id="charCount">0</span>/1000</span>
                                </div>
                            </div>

                            <!-- Preferred Contact Method -->
                            <div class="c-form__group">
                                <label class="c-form__label">Preferred Contact Method</label>
                                <div class="c-form__radio-group">
                                    <label class="c-form__check">
                                        <input type="radio" name="preferred_contact" value="email" <?php echo ($formData['preferred_contact'] ?? 'email') === 'email' ? 'checked' : ''; ?>>
                                        <span class="c-form__check-label">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                                <polyline points="22,6 12,13 2,6"/>
                                            </svg>
                                            Email
                                        </span>
                                    </label>
                                    <label class="c-form__check">
                                        <input type="radio" name="preferred_contact" value="phone" <?php echo ($formData['preferred_contact'] ?? '') === 'phone' ? 'checked' : ''; ?>>
                                        <span class="c-form__check-label">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                            </svg>
                                            Phone
                                        </span>
                                    </label>
                                    <label class="c-form__check">
                                        <input type="radio" name="preferred_contact" value="whatsapp" <?php echo ($formData['preferred_contact'] ?? '') === 'whatsapp' ? 'checked' : ''; ?>>
                                        <span class="c-form__check-label">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                            </svg>
                                            WhatsApp
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <!-- Newsletter Subscription Checkbox -->
                            <div class="c-form__group">
                                <label class="c-form__check c-form__check--checkbox">
                                    <input type="checkbox" name="subscribe_newsletter" value="1" <?php echo $formData['subscribe_newsletter'] ? 'checked' : ''; ?>>
                                    <span class="c-form__check-label">
                                        <svg class="c-form__check-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                            <polyline class="checkmark" points="9 12 12 15 16 10"/>
                                        </svg>
                                        Subscribe to our newsletter for the latest updates, offers, and solar energy tips.
                                    </span>
                                </label>
                            </div>

                            <button type="submit" class="c-btn c-btn--primary">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="22" y1="2" x2="11" y2="13"/>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                                </svg>
                                Send Message
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Contact Info Sidebar - Premium Cards -->
                <aside class="c-contact-info">
                    <!-- Contact Details -->
                    <div class="c-contact-info__card">
                        <h3 class="c-contact-info__title">Contact Information</h3>
                        
                        <div class="c-contact-info__item">
                            <div class="c-contact-info__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                </svg>
                            </div>
                            <div class="c-contact-info__content">
                                <span class="c-contact-info__label">Phone</span>
                                <a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $phone)); ?>" class="c-contact-info__value"><?php echo e($phone); ?></a>
                            </div>
                        </div>

                        <div class="c-contact-info__item">
                            <div class="c-contact-info__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                            </div>
                            <div class="c-contact-info__content">
                                <span class="c-contact-info__label">Email</span>
                                <a href="mailto:<?php echo e($email); ?>" class="c-contact-info__value"><?php echo e($email); ?></a>
                            </div>
                        </div>

                        <div class="c-contact-info__item">
                            <div class="c-contact-info__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                            </div>
                            <div class="c-contact-info__content">
                                <span class="c-contact-info__label">Address</span>
                                <span class="c-contact-info__value"><?php echo e($address); ?></span>
                            </div>
                        </div>

                        <div class="c-contact-info__item">
                            <div class="c-contact-info__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                            </div>
                            <div class="c-contact-info__content">
                                <span class="c-contact-info__label">Business Hours</span>
                                <div class="c-contact-info__hours">
                                    <span class="c-contact-info__hour"><?php echo e($businessHours); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- WhatsApp Card - Premium Gradient -->
                    <div class="c-contact-info__card c-contact-info__card--whatsapp">
                        <h3 class="c-contact-info__title">Quick Response</h3>
                        <p style="margin-bottom: 1rem; opacity: 0.9;">Chat with us directly on WhatsApp for faster assistance.</p>
                        <a href="https://wa.me/<?php echo e(preg_replace('/[^0-9]/', '', $whatsapp)); ?>" target="_blank" class="c-btn c-btn--light">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            Chat on WhatsApp
                        </a>
                    </div>

                    <!-- Social Links -->
                    <div class="c-contact-social">
                        <h3 class="c-contact-social__title">Follow Us</h3>
                        <div class="c-contact-social__links">
                            <a href="#" class="c-contact-social__link" aria-label="Facebook">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                                </svg>
                            </a>
                            <a href="#" class="c-contact-social__link" aria-label="Twitter">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/>
                                </svg>
                            </a>
                            <a href="#" class="c-contact-social__link" aria-label="LinkedIn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/>
                                    <rect x="2" y="9" width="4" height="12"/>
                                    <circle cx="4" cy="4" r="2"/>
                                </svg>
                            </a>
                            <a href="#" class="c-contact-social__link" aria-label="Instagram">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <!-- FAQ Quick Links Section - Premium Cards -->
    <section class="c-section c-section--padding-lg c-section--gray">
        <div class="c-section__container">
            <div class="c-section__header c-section__header--center">
                <!-- <span class="c-section__pretitle">Quick Help</span> -->
                <h2 class="c-section__title">Frequently Asked Questions</h2>
                <p class="c-section__subtitle">Find quick answers to common questions</p>
            </div>

            <div class="c-faq-quick-grid">
                <a href="<?php echo $siteBase; ?>/faq#residential" class="c-faq-quick-card">
                    <div class="c-faq-quick-card__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </div>
                    <h3 class="c-faq-quick-card__title">Residential Solar</h3>
                    <p class="c-faq-quick-card__text">Learn about home solar installation and costs</p>
                </a>

                <a href="<?php echo $siteBase; ?>/faq#commercial" class="c-faq-quick-card">
                    <div class="c-faq-quick-card__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="4" y="2" width="16" height="20" rx="2" ry="2"/>
                            <path d="M9 22v-4h6v4"/>
                            <path d="M8 6h.01"/>
                            <path d="M16 6h.01"/>
                            <path d="M12 6h.01"/>
                            <path d="M12 10h.01"/>
                            <path d="M12 14h.01"/>
                            <path d="M16 10h.01"/>
                            <path d="M16 14h.01"/>
                            <path d="M8 10h.01"/>
                            <path d="M8 14h.01"/>
                        </svg>
                    </div>
                    <h3 class="c-faq-quick-card__title">Commercial Solutions</h3>
                    <p class="c-faq-quick-card__text">Business and industrial solar options</p>
                </a>

                <a href="<?php echo $siteBase; ?>/faq#financing" class="c-faq-quick-card">
                    <div class="c-faq-quick-card__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <h3 class="c-faq-quick-card__title">Financing Options</h3>
                    <p class="c-faq-quick-card__text">Payment plans and financing available</p>
                </a>

                <a href="<?php echo $siteBase; ?>/faq#maintenance" class="c-faq-quick-card">
                    <div class="c-faq-quick-card__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                        </svg>
                    </div>
                    <h3 class="c-faq-quick-card__title">Maintenance</h3>
                    <p class="c-faq-quick-card__text">Service and warranty information</p>
                </a>
            </div>

            <div class="c-section__footer">
                <a href="<?php echo $siteBase; ?>/faq" class="c-btn c-btn--outline">View All FAQs</a>
            </div>
        </div>
    </section>

    <!-- Office Locations - Premium Boutique Style -->
    <?php if (!empty($officeLocations)): ?>
    <section class="c-section c-section--padding-lg">
        <div class="c-section__container">
            <div class="c-section__header c-section__header--center">
                <!-- <span class="c-section__pretitle">Our Offices</span> -->
                <h2 class="c-section__title">Visit Us</h2>
                <p class="c-section__subtitle">Find our offices across East Africa</p>
            </div>

            <div class="c-office-grid">
                <?php foreach ($officeLocations as $office): ?>
                    <div class="c-office-card">
                        <div class="c-office-card__header">
                            <div class="c-office-card__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                            </div>
                            <h3 class="c-office-card__title"><?php echo e($office['name']); ?></h3>
                            <?php if (!empty($office['region_or_city'])): ?>
                                <span class="c-office-card__region"><?php echo e($office['region_or_city']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="c-office-card__body">
                            <?php if (!empty($office['address'])): ?>
                                <div class="c-office-card__detail">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span><?php echo e($office['address']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($office['phone'])): ?>
                                <div class="c-office-card__detail">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/>
                                    </svg>
                                    <a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $office['phone'])); ?>"><?php echo e($office['phone']); ?></a>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($office['email'])): ?>
                                <div class="c-office-card__detail">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                        <polyline points="22,6 12,13 2,6"/>
                                    </svg>
                                    <a href="mailto:<?php echo e($office['email']); ?>"><?php echo e($office['email']); ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Map Section - Premium Design -->
    <section class="c-section c-section--padding-lg c-section--gray">
        <div class="c-section__container">
            <div class="c-section__header c-section__header--center">
                <!-- <span class="c-section__pretitle">Our Locations</span> -->
                <h2 class="c-section__title">Find Us on the Map</h2>
                <p class="c-section__subtitle">Visit any of our offices across East Africa</p>
            </div>
            
            <div class="c-map-container">
                <div id="office-map" class="c-map-container__embed"></div>
            </div>
            
            <?php if (!empty($officeLocations)): ?>
            <div class="c-map-legend">
                <?php foreach ($officeLocations as $index => $office): ?>
                    <?php if (!empty($office['latitude']) && !empty($office['longitude'])): ?>
                    <button type="button" class="c-map-legend__item" onclick="focusOnOffice(<?php echo $index; ?>)">
                        <span class="c-map-legend__marker"></span>
                        <span><?php echo e($office['name']); ?></span>
                    </button>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
// Office locations data
const officeLocations = <?php
    $locations = [];
    foreach ($officeLocations as $office) {
        if (!empty($office['latitude']) && !empty($office['longitude'])) {
            $locations[] = [
                'name' => $office['name'],
                'region' => $office['region_or_city'] ?? '',
                'address' => $office['address'] ?? '',
                'phone' => $office['phone'] ?? '',
                'email' => $office['email'] ?? '',
                'lat' => (float)$office['latitude'],
                'lng' => (float)$office['longitude']
            ];
        }
    }
    echo json_encode($locations);
?>;

// Initialize map
let map;
let markers = [];

function initMap() {
    // Default center (Kampala, Uganda)
    const defaultCenter = [0.31630375, 32.57078609999999];
    
    // Initialize the map
    map = L.map('office-map').setView(defaultCenter, 7);
    
    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);
    
    // Custom marker icon
    const customIcon = L.divIcon({
        className: 'custom-marker',
        html: `<div style="background: var(--primary-purple, #660099); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 4px 12px rgba(102, 0, 153, 0.4);">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="white">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3" fill="white"/>
            </svg>
        </div>`,
        iconSize: [36, 36],
        iconAnchor: [18, 36],
        popupAnchor: [0, -36]
    });
    
    // Add markers for each office
    if (officeLocations.length > 0) {
        officeLocations.forEach((office, index) => {
            const marker = L.marker([office.lat, office.lng], { icon: customIcon })
                .addTo(map)
                .bindPopup(`
                    <div style="min-width: 220px; padding: 15px; border-radius: 12px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                        <h3 style="margin: 0 0 8px; font-size: 18px; font-weight: 700; color: #660099;">${office.name}</h3>
                        ${office.region ? `<p style="margin: 0 0 12px; font-size: 13px; color: #666; font-weight: 500;">${office.region}</p>` : ''}
                        ${office.address ? `<p style="margin: 0 0 8px; font-size: 14px; color: #333;"><strong>📍 Address:</strong><br/>${office.address}</p>` : ''}
                        ${office.phone ? `<p style="margin: 0 0 8px; font-size: 14px; color: #333;"><strong>📞 Phone:</strong> <a href="tel:${office.phone.replace(/[^0-9+]/g, '')}" style="color: #660099; text-decoration: none; font-weight: 500;">${office.phone}</a></p>` : ''}
                        ${office.email ? `<p style="margin: 0; font-size: 14px; color: #333;"><strong>✉️ Email:</strong> <a href="mailto:${office.email}" style="color: #660099; text-decoration: none; font-weight: 500;">${office.email}</a></p>` : ''}
                    </div>
                `);
            
            markers.push(marker);
        });
        
        // Fit map to show all markers
        if (markers.length > 1) {
            const group = L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.1));
        } else if (markers.length === 1) {
            map.setView([officeLocations[0].lat, officeLocations[0].lng], 14);
        }
    }
}

// Focus on specific office
function focusOnOffice(index) {
    if (officeLocations[index] && map) {
        const office = officeLocations[index];
        map.setView([office.lat, office.lng], 14);
        
        // Open popup
        if (markers[index]) {
            markers[index].openPopup();
        }
    }
}

// Initialize map when page loads
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    
    // Initialize inquiry reasons
    updateInquiryReasons();
    document.getElementById('inquiry_type').addEventListener('change', updateInquiryReasons);
    
    // Character counter
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    
    if (messageTextarea && charCount) {
        messageTextarea.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = count;
            
            if (count > 1000) {
                charCount.style.color = 'var(--color-error, #ef4444)';
            } else if (count > 800) {
                charCount.style.color = 'var(--color-warning, #f59e0b)';
            } else {
                charCount.style.color = 'var(--text-muted, #6b7280)';
            }
        });
        
        // Initial count
        charCount.textContent = messageTextarea.value.length;
    }
});

// Update inquiry reasons based on selected type
function updateInquiryReasons() {
    const inquiryTypeSelect = document.getElementById('inquiry_type');
    const inquiryReasonSelect = document.getElementById('inquiry_reason');
    const selectedOption = inquiryTypeSelect.options[inquiryTypeSelect.selectedIndex];
    const typeId = selectedOption.getAttribute('data-id');
    
    // Clear existing options
    inquiryReasonSelect.innerHTML = '<option value="">Select a reason...</option>';
    
    // Get reasons for selected type
    const reasons = <?php echo $inquiryReasonsJson; ?>;
    if (reasons[typeId]) {
        reasons[typeId].forEach(function(reason) {
            const option = document.createElement('option');
            option.value = reason.reason;
            option.textContent = reason.reason;
            inquiryReasonSelect.appendChild(option);
        });
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
