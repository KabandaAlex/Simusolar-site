<?php
/**
 * Public Site Header Include
 * 
 * This file provides the HTML header structure for all public-facing pages.
 * Include this at the top of each page template.
 * 
 * Expected variables (optional, set before including):
 * @var string $pageTitle - Page title suffix (e.g., "About Us")
 * @var string $pageDescription - Meta description for SEO
 * @var string $bodyClass - Additional body classes
 * @var string $pageCss - Page-specific CSS file (relative to assets/css/pages/)
 * 
 * @version 1.0.0
 */

// ==========================================
// SUNBIRD AI TRANSLATION LOGIC
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_GET['lang'])) {
    $_SESSION['site_language'] = $_GET['lang'];
}
$site_lang = $_SESSION['site_language'] ?? 'eng';

// CRITICAL FIX: Unlock the session instantly so the user can click other pages without freezing!
session_write_close();

// Require the translation helper (Ensure sunbird_translator.php is in the includes folder!)
require_once __DIR__ . '/sunbird_translator.php';

// Turn on output buffering to capture full page HTML automatically
// We only do this if the language is NOT English to keep the site fast!
if ($site_lang !== 'eng') {
    ob_start();
}
// ==========================================

// Ensure BASE_PATH is defined
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../config/path.php';
}

// Initialize default values
$pageTitle = $pageTitle ?? '';
$pageDescription = $pageDescription ?? 'SimuSolar - Renewable Energy Solutions for a Sustainable Future';
$bodyClass = $bodyClass ?? '';
$pageCss = $pageCss ?? null;
$siteBase = rtrim(BASE_PATH, '/');

// Include database connection and functions for dynamic navigation
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Get navigation items from database and build tree
$pdo = getDb();
$mainNavItems = buildNavigationTree(getNavigationItems($pdo, 'main', true));

// Helper function to render navigation link
if (!function_exists('renderNavLink')) {
    function renderNavLink(array $item, string $siteBase, bool $isHomepage): string {
        $url = $item['url'];
        // Handle internal vs external URLs
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = $siteBase . '/' . ltrim($url, '/');
        }
        
        // Determine if this item is active
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $itemPath = parse_url($url, PHP_URL_PATH);
        $isActive = ($currentPath === $itemPath) || ($url === $siteBase . '/' && $isHomepage);
        
        $target = $item['target'] ?? '_self';
        $classes = 'c-nav__link' . ($isActive ? ' is-active' : '');
        
        return sprintf(
            '<a href="%s" class="%s" role="menuitem"%s>%s</a>',
            htmlspecialchars($url),
            $classes,
            $target !== '_self' ? ' target="' . htmlspecialchars($target) . '"' : '',
            htmlspecialchars($item['title'])
        );
    }
}

// Helper function to render mobile navigation link
if (!function_exists('renderMobileNavLink')) {
    function renderMobileNavLink(array $item, string $siteBase, bool $isHomepage): string {
        $url = $item['url'];
        // Handle internal vs external URLs
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = $siteBase . '/' . ltrim($url, '/');
        }
        
        // Determine if this item is active
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $itemPath = parse_url($url, PHP_URL_PATH);
        $isActive = ($currentPath === $itemPath) || ($url === $siteBase . '/' && $isHomepage);
        
        $target = $item['target'] ?? '_self';
        $classes = 'c-nav__drawer-link' . ($isActive ? ' is-active' : '');
        
        return sprintf(
            '<a href="%s" class="%s" role="menuitem"%s>%s</a>',
            htmlspecialchars($url),
            $classes,
            $target !== '_self' ? ' target="' . htmlspecialchars($target) . '"' : '',
            htmlspecialchars($item['title'])
        );
    }
}

// Build full page title
$siteName = 'SimuSolar';
$fullTitle = $pageTitle ? "{$pageTitle} | {$siteName}" : $siteName;

// Determine if we're on the homepage for navigation styling
$isHomepage = ($_SERVER['REQUEST_URI'] === BASE_PATH . '/' || 
               $_SERVER['REQUEST_URI'] === BASE_PATH . '/index.php' ||
               $_SERVER['REQUEST_URI'] === rtrim(BASE_PATH, '/') . '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
  <meta name="author" content="SimuSolar">
  
  <!-- SEO Meta Tags -->
  <meta name="robots" content="index, follow">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?php echo htmlspecialchars($fullTitle); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
  <meta property="og:site_name" content="<?php echo htmlspecialchars($siteName); ?>">
  <meta property="og:url" content="<?php echo htmlspecialchars((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">
  
  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?php echo $siteBase; ?>/assets/images/favicon.png">
  <link rel="apple-touch-icon" href="<?php echo $siteBase; ?>/assets/images/apple-touch-icon.png">
  
  <!-- Page Title -->
  <title><?php echo htmlspecialchars($fullTitle); ?></title>
  
  <!-- Google Fonts - Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Main CSS -->
  <link rel="stylesheet" href="<?php echo $siteBase; ?>/assets/css/main.css">
   
  <!-- Page-specific CSS (optional) -->
  <?php if ($pageCss): ?>
  <link rel="stylesheet" href="<?php echo $siteBase; ?>/assets/css/pages/<?php echo htmlspecialchars($pageCss); ?>">
  <?php endif; ?>
  
  <!-- Critical: nav layout and logo size (must apply even if main.css fails) -->
  <style>
    .c-nav__bar {
      background-color: <?php echo $isHomepage ? 'transparent' : '#fff'; ?>;
      position: <?php echo $isHomepage ? 'absolute' : 'sticky'; ?>;
      top: 0; left: 0; right: 0; width: 100%;
    }
    .c-nav__inner {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      flex-wrap: nowrap !important;
      max-width: 1140px; margin: 0 auto; padding: 0 1rem;
      height: 70px; gap: 1.5rem;
    }
    .c-nav__logo { display: inline-flex !important; align-items: center !important; gap: 0.75rem; flex-shrink: 0; text-decoration: none; }
    .c-nav__logo-icon {
      width: 40px !important; height: 40px !important;
      min-width: 40px !important; max-width: 40px !important;
      flex-shrink: 0 !important;
      display: flex !important; align-items: center !important; justify-content: center !important;
    }
    .c-nav__logo-icon svg {
      width: 40px !important; height: 40px !important;
      max-width: 40px !important; max-height: 40px !important;
      flex-shrink: 0 !important;
    }
    .c-nav__menu-wrap { display: none; }
    @media (min-width: 992px) {
      .c-nav__menu-wrap { display: block !important; flex: 1; margin: 0 1rem; min-width: 0; }
    }
    .c-nav__list {
      display: flex !important; flex-wrap: wrap; align-items: center; justify-content: center;
      list-style: none !important; margin: 0 !important; padding: 0 !important; gap: 0.25rem;
    }
    .c-nav__li { margin: 0; }
    .c-nav__actions { display: flex !important; align-items: center !important; gap: 0.75rem; }
    .c-nav__cta { display: none; }
    @media (min-width: 768px) { .c-nav__cta { display: inline-flex !important; } }
    .c-nav__burger { display: flex; flex-direction: column; justify-content: center; align-items: center; width: 44px; height: 44px; padding: 0; border: none; background: none; cursor: pointer; }
    @media (min-width: 992px) { .c-nav__burger { display: none !important; } }
    /* Hide mobile drawer so only horizontal nav shows (drawer shown only on small screens via main.css) */
    .c-nav__drawer { display: none !important; position: fixed !important; top: 0; right: 0; bottom: 0; width: 320px; max-width: 100vw; transform: translateX(100%); visibility: hidden; z-index: 300; }
    /* When scrolled: solid bar + dark text so menu stays readable (JS adds .is-scrolled) */
    .c-nav.is-scrolled .c-nav__bar { background-color: #fff !important; box-shadow: 0 2px 16px rgba(0,0,0,0.12); }
    .c-nav.is-scrolled .c-nav__bar .c-nav__logo { color: #660099 !important; }
    .c-nav.is-scrolled .c-nav__bar .c-nav__link { color: #2C2C2C !important; }
    .c-nav.is-scrolled .c-nav__burger { color: #2C2C2C !important; }
  </style>
</head>
<body class="<?php echo htmlspecialchars(trim($bodyClass)); ?>">
  <!-- Skip to main content link for accessibility -->
  <a href="#main-content" class="skip-to-content">Skip to main content</a>
  
  <!-- ========================================================================
       NAVIGATION (rebuilt)
       ======================================================================== -->
  <header class="c-nav<?php echo $isHomepage ? ' c-nav--transparent' : ''; ?>" id="main-nav" role="banner">
    <div class="c-nav__bar<?php echo $isHomepage ? ' c-nav__bar--transparent' : ''; ?>">
      <div class="c-nav__inner">
        <a href="<?php echo $siteBase; ?>/" class="c-nav__logo" aria-label="Simusolar – Home">
            <img src="<?php echo $siteBase; ?>/static/images/Simusolar-logo-200.png" alt="Simusolar Logo" style="height: 50px; width: auto; object-fit: contain;">
        </a>

        <nav class="c-nav__menu-wrap" aria-label="Main navigation">
          <ul class="c-nav__list" role="menubar">
            <?php if (empty($mainNavItems)): ?>
            <li class="c-nav__li" role="none">
              <a href="<?php echo $siteBase; ?>/" class="c-nav__link <?php echo $isHomepage ? 'is-active' : ''; ?>" role="menuitem">Home</a>
            </li>
            <?php else: ?>
              <?php foreach ($mainNavItems as $item): ?>
                <?php if (!empty($item['children'])): ?>
                <li class="c-nav__li c-nav__li--has-dropdown" role="none">
                  <?php echo renderNavLink($item, $siteBase, $isHomepage); ?>
                  <ul class="c-nav__dropdown" role="menu">
                    <?php foreach ($item['children'] as $child): ?>
                    <li class="c-nav__dropdown-li" role="none">
                      <?php echo renderNavLink($child, $siteBase, $isHomepage); ?>
                    </li>
                    <?php endforeach; ?>
                  </ul>
                </li>
                <?php else: ?>
                <li class="c-nav__li" role="none">
                  <?php echo renderNavLink($item, $siteBase, $isHomepage); ?>
                </li>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </nav>

        <!-- Language Switcher in the right actions area -->
        <div class="c-nav__actions" style="display: flex;">
            <div class="c-lang-switcher">
                <select onchange="window.location.href='?lang='+this.value" style="padding: 6px 10px; border-radius: 6px; border: 1px solid #ddd; background: #fff; color: #333; font-weight: 500; cursor: pointer; outline: none;">
                    <option value="eng" <?php echo $site_lang === 'eng' ? 'selected' : ''; ?>>🇬🇧 English</option>
                    <option value="lug" <?php echo $site_lang === 'lug' ? 'selected' : ''; ?>>🇺🇬 Luganda</option>
                    <option value="swa" <?php echo $site_lang === 'swa' ? 'selected' : ''; ?>>🇹🇿 Swahili</option>
                    <option value="ach" <?php echo $site_lang === 'ach' ? 'selected' : ''; ?>>🇺🇬 Acholi</option>
                </select>
            </div>
        </div>
      </div>
    </div>

    <div class="c-nav__drawer" id="mobile-menu" aria-hidden="true" role="dialog" aria-label="Mobile menu">
      <div class="c-nav__drawer-inner">
        <ul class="c-nav__drawer-list" role="menu">
          <?php if (empty($mainNavItems)): ?>
          <li class="c-nav__drawer-li" role="none">
            <a href="<?php echo $siteBase; ?>/" class="c-nav__drawer-link <?php echo $isHomepage ? 'is-active' : ''; ?>" role="menuitem">Home</a>
          </li>
          <?php else: ?>
            <?php foreach ($mainNavItems as $item): ?>
              <?php if (!empty($item['children'])): ?>
              <li class="c-nav__drawer-li c-nav__drawer-li--parent" role="none">
                <?php echo renderMobileNavLink($item, $siteBase, $isHomepage); ?>
                <ul class="c-nav__drawer-sublist" role="menu">
                  <?php foreach ($item['children'] as $child): ?>
                  <li class="c-nav__drawer-sublist-li" role="none">
                    <?php echo renderMobileNavLink($child, $siteBase, $isHomepage); ?>
                  </li>
                  <?php endforeach; ?>
                </ul>
              </li>
              <?php else: ?>
              <li class="c-nav__drawer-li" role="none">
                <?php echo renderMobileNavLink($item, $siteBase, $isHomepage); ?>
              </li>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
        <div class="c-nav__drawer-cta">
          <a href="<?php echo $siteBase; ?>/quote" class="c-btn c-btn--secondary c-btn--block">Get a Quote</a>
        </div>
      </div>
    </div>

    <div class="c-nav__backdrop" id="nav-overlay" aria-hidden="true"></div>
  </header>
  
  <!-- ========================================================================
       MAIN CONTENT
       ======================================================================== -->
  <main id="main-content" class="l-main" role="main">