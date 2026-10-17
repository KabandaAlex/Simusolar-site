<?php
/**
 * Tulima Solar - Front controller (public)
 * Dynamic page routing system
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDb();

// Get the requested page slug from URL
$pageSlug = isset($_GET['page']) ? trim($_GET['page']) : 'home';
if ($pageSlug === '') $pageSlug = 'home';

// Sanitize the slug to prevent directory traversal
$pageSlug = preg_replace('/[^a-z0-9\-\/]/i', '', $pageSlug);
$pageSlug = trim($pageSlug, '/');

// Track if this is a nested page request (e.g., products/solar-pumps)
$isNestedPage = strpos($pageSlug, '/') !== false;

// Initialize variables
$currentPage = null;
$sections = [];
$sectionMap = [];
$is404 = false;

// Try to find the page by slug (supports both simple and nested slugs)
$currentPage = getPageBySlug($pdo, $pageSlug);

// If not found and it's a nested path, try to find by full path
if (!$currentPage && $isNestedPage) {
    $currentPage = getPageByFullPath($pdo, $pageSlug);
}

// Handle special legal / utility pages that might be in legal_pages table instead
if (!$currentPage && in_array($pageSlug, ['privacy', 'terms', 'cookies', 'sitemap', 'faq'], true)) {
    $titles = [
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms & Conditions',
        'cookies' => 'Cookie Policy',
        'sitemap' => 'Sitemap',
        'faq' => 'Frequently Asked Questions'
    ];
    $label = $titles[$pageSlug] ?? ucfirst($pageSlug);
    $currentPage = [
        'slug' => $pageSlug,
        'title' => $label,
        'meta_description' => '',
        'meta_title' => $label . ' | Tulima Solar',
        'id' => 0,
        'hero_title' => $label,
        'hero_subtitle' => '',
        'hero_cta_text' => 'Contact Us',
        'hero_cta_url' => 'index.php?page=contact',
        'template_type' => 'fullwidth',
        'is_active' => 1
    ];
    $sections = [];
    $sectionMap = [];
}

// Page not found handling
if (!$currentPage) {
    header('HTTP/1.0 404 Not Found');
    $is404 = true;
    $currentPage = [
        'slug' => '404', 
        'title' => 'Page Not Found', 
        'meta_description' => 'The requested page could not be found.',
        'meta_title' => '404 - Page Not Found | Tulima Solar',
        'id' => 0,
        'template_type' => 'fullwidth',
        'is_active' => 1
    ];
    $sections = [];
    $sectionMap = [];
} else {
    // Check if page is active (skip check for special pages with id=0)
    if ($currentPage['id'] > 0 && empty($currentPage['is_active'])) {
        header('HTTP/1.0 404 Not Found');
        $is404 = true;
        $currentPage = [
            'slug' => '404', 
            'title' => 'Page Not Found', 
            'meta_description' => 'The requested page could not be found.',
            'meta_title' => '404 - Page Not Found | Tulima Solar',
            'id' => 0,
            'template_type' => 'fullwidth',
            'is_active' => 1
        ];
        $sections = [];
        $sectionMap = [];
    } else {
        // Load page sections
        $sections = getPageSections($pdo, (int)$currentPage['id']);
        $sectionMap = [];
        foreach ($sections as $s) {
            $sectionMap[$s['section_key']] = $s;
        }
    }
}

// Ensure arrays are initialized
if (!isset($sections)) $sections = [];
if (!isset($sectionMap)) $sectionMap = [];

/**
 * Resolve the template file to use for the current page
 * Uses the template_type field if available, falls back to default
 */
function resolveTemplateFile(array $page, string $slug, bool $is404): string {
    $pagesDir = __DIR__ . '/pages/';
    
    // Handle 404 pages
    if ($is404) {
        if (file_exists($pagesDir . '404.php')) {
            return $pagesDir . '404.php';
        }
        if (file_exists($pagesDir . '404-new.php')) {
            return $pagesDir . '404-new.php';
        }
        // Create a minimal 404 response
        return $pagesDir . '404.php';
    }
    
    // Check for custom template file first
    if (!empty($page['custom_template'])) {
        $customFile = $pagesDir . $page['custom_template'];
        if (file_exists($customFile)) {
            return $customFile;
        }
    }
    
    // Check for layout_file field
    if (!empty($page['layout_file']) && $page['layout_file'] !== 'default') {
        $layoutFile = $pagesDir . $page['layout_file'] . '.php';
        if (file_exists($layoutFile)) {
            return $layoutFile;
        }
    }
    
    // Map template types to files
    $templateType = $page['template_type'] ?? 'fullwidth';
    
    // Define template file mappings
    $templateMap = [
        'fullwidth' => null, // Will use slug-based lookup
        'with_sidebar' => 'default-sidebar.php',
        'landing' => null, // Will use slug-based lookup
        'custom' => null
    ];
    
    // If we have a specific template file for the type, check if it exists
    if (isset($templateMap[$templateType]) && $templateMap[$templateType] !== null) {
        $templateFile = $pagesDir . $templateMap[$templateType];
        if (file_exists($templateFile)) {
            return $templateFile;
        }
    }
    
    // Legacy mapping: Check for existing page files based on slug
    $slugToFiles = [
        'home' => 'home.php',
        'about' => 'about.php',
        'who-we-are' => 'about.php',
        'impact' => 'impact.php',
        'our-impact' => 'impact.php',
        'what-we-offer' => 'what-we-offer-new.php',
        'services' => 'services.php',
        'our-services' => 'services.php',
        'projects' => 'projects.php',
        'our-projects' => 'projects.php',
        'products' => 'products.php',
        'solar-water-pumps' => 'solar-water-pumps.php',
        'surface-pumps' => 'surface-pumps-new.php',
        'submersible-pumps' => 'submersible-pumps.php',
        'agricultural-processing-machines' => 'agricultural-processing-machines-new.php',
        'processing-machines' => 'agricultural-processing-machines-new.php',
        'careers' => 'careers.php',
        'jobs' => 'careers.php',
        'contact' => 'contact.php',
        'testimonials' => 'testimonials-new.php',
        'privacy' => 'privacy-new.php',
        'privacy-policy' => 'privacy-new.php',
        'terms' => 'terms-new.php',
        'terms-conditions' => 'terms-new.php',
        'cookies' => 'cookies-new.php',
        'sitemap' => 'sitemap-new.php',
        'faq' => 'faq.php',
        'blog' => 'blog/index.php',
    ];
    
    // Check if we have a legacy file mapping
    if (isset($slugToFiles[$slug])) {
        $legacyFile = $pagesDir . $slugToFiles[$slug];
        if (file_exists($legacyFile)) {
            return $legacyFile;
        }
    }
    
    // Try slug-based file lookup (e.g., pages/my-page.php)
    $slugFile = $pagesDir . $slug . '.php';
    if (file_exists($slugFile)) {
        return $slugFile;
    }
    
    // Try slug with -new suffix (e.g., pages/my-page-new.php)
    $slugNewFile = $pagesDir . $slug . '-new.php';
    if (file_exists($slugNewFile)) {
        return $slugNewFile;
    }
    
    // Try dynamic template based on template_type
    $dynamicTemplate = $pagesDir . 'templates/' . $templateType . '.php';
    if (file_exists($dynamicTemplate)) {
        return $dynamicTemplate;
    }
    
    // Fallback to a generic page template
    $genericTemplate = $pagesDir . 'page.php';
    if (file_exists($genericTemplate)) {
        return $genericTemplate;
    }
    
    // Last resort: create a minimal page template inline
    return $pagesDir . '404-new.php';
}

/**
 * Get the footer file to include
 */
function resolveFooterFile(): string {
    $footerFile = __DIR__ . '/includes/footer-test-site-new.php';
    if (file_exists($footerFile)) {
        return $footerFile;
    }
    return __DIR__ . '/includes/footer.php';
}

// Resolve template and render the page
$templateFile = resolveTemplateFile($currentPage, $pageSlug, $is404);
$footerFile = resolveFooterFile();

// Set page meta information for use in templates
$pageMeta = [
    'title' => $currentPage['meta_title'] ?? $currentPage['title'] . ' | Tulima Solar',
    'description' => $currentPage['meta_description'] ?? '',
    'slug' => $currentPage['slug'] ?? $pageSlug,
];

// Make variables available to the template
$GLOBALS['currentPage'] = $currentPage;
$GLOBALS['sections'] = $sections;
$GLOBALS['sectionMap'] = $sectionMap;
$GLOBALS['pageMeta'] = $pageMeta;
$GLOBALS['pdo'] = $pdo;

// Include the resolved template
if (file_exists($templateFile)) {
    include $templateFile;
} else {
    // If template doesn't exist, show a minimal page
    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($pageMeta['title']) . '</title>';
    echo '</head>';
    echo '<body>';
    echo '<h1>' . htmlspecialchars($currentPage['title']) . '</h1>';
    if (!empty($currentPage['hero_subtitle'])) {
        echo '<p>' . htmlspecialchars($currentPage['hero_subtitle']) . '</p>';
    }
    echo '</body>';
    echo '</html>';
}

// Include the footer
if (file_exists($footerFile)) {
    require_once $footerFile;
}
