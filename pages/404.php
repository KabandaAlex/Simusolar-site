<?php
/**
 * 404 Page - Page Not Found
 * 
 * Displayed when a requested page cannot be found.
 * 
 * @version 1.0.0
 */

// Page variables for header
$pageTitle = 'Page Not Found - Tulima Solar';
$pageDescription = 'The page you are looking for could not be found. Return to our homepage or use the navigation to find what you need.';
$bodyClass = 'page-404';
$pageCss = null; // Uses main CSS only

// Include header
include __DIR__ . '/../includes/header.php';

// Ensure BASE_PATH is available
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../config/path.php';
}
$siteBase = rtrim(BASE_PATH, '/');
?>

<!-- 404 Hero Section -->
<section class="o-section o-section--lg">
  <div class="o-container">
    <div class="c-404">
      <div class="c-404__content">
        <!-- 404 Number -->
        <div class="c-404__number">
          <span class="c-404__digit">4</span>
          <span class="c-404__digit c-404__digit--center">
            <svg viewBox="0 0 100 100" class="c-404__sun" aria-hidden="true">
              <circle cx="50" cy="50" r="20" fill="currentColor"/>
              <g stroke="currentColor" stroke-width="3" stroke-linecap="round">
                <line x1="50" y1="5" x2="50" y2="20"/>
                <line x1="50" y1="80" x2="50" y2="95"/>
                <line x1="5" y1="50" x2="20" y2="50"/>
                <line x1="80" y1="50" x2="95" y2="50"/>
                <line x1="18" y1="18" x2="28" y2="28"/>
                <line x1="72" y1="72" x2="82" y2="82"/>
                <line x1="18" y1="82" x2="28" y2="72"/>
                <line x1="72" y1="28" x2="82" y2="18"/>
              </g>
            </svg>
          </span>
          <span class="c-404__digit">4</span>
        </div>
        
        <!-- Message -->
        <h1 class="c-404__title">Page Not Found</h1>
        <p class="c-404__message">
          Oops! The page you're looking for seems to have gone off the grid. 
          Don't worry, we can help you find your way back to the light.
        </p>
        
        <!-- Actions -->
        <div class="c-404__actions">
          <a href="<?php echo $siteBase; ?>/" class="c-btn c-btn--primary c-btn--lg">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="c-btn__icon" aria-hidden="true">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            Back to Homepage
          </a>
          <a href="<?php echo $siteBase; ?>/contact" class="c-btn c-btn--secondary c-btn--lg">
            Contact Support
          </a>
        </div>
        
        <!-- Helpful Links -->
        <div class="c-404__links">
          <h2 class="c-404__links-title">Popular Pages</h2>
          <ul class="c-404__links-list">
            <li>
              <a href="<?php echo $siteBase; ?>/about">About Us</a>
            </li>
            <li>
              <a href="<?php echo $siteBase; ?>/services">Our Services</a>
            </li>
            <li>
              <a href="<?php echo $siteBase; ?>/projects">Solar Projects</a>
            </li>
            <li>
              <a href="<?php echo $siteBase; ?>/products">Products</a>
            </li>
            <li>
              <a href="<?php echo $siteBase; ?>/blog">Blog & News</a>
            </li>
            <li>
              <a href="<?php echo $siteBase; ?>/careers">Careers</a>
            </li>
          </ul>
        </div>
      </div>
      
      <!-- Decorative Element -->
      <div class="c-404__decoration" aria-hidden="true">
        <svg viewBox="0 0 400 300" class="c-404__illustration">
          <!-- Sun rays -->
          <g class="c-404__rays" stroke="currentColor" stroke-width="2" opacity="0.2">
            <line x1="200" y1="50" x2="200" y2="100"/>
            <line x1="300" y1="100" x2="260" y2="140"/>
            <line x1="350" y1="200" x2="300" y2="200"/>
            <line x1="100" y1="100" x2="140" y2="140"/>
            <line x1="50" y1="200" x2="100" y2="200"/>
          </g>
          <!-- Solar panel -->
          <g transform="translate(120, 150)">
            <rect x="0" y="0" width="160" height="100" rx="4" fill="none" stroke="currentColor" stroke-width="2"/>
            <line x1="0" y1="33" x2="160" y2="33" stroke="currentColor" stroke-width="1"/>
            <line x1="0" y1="66" x2="160" y2="66" stroke="currentColor" stroke-width="1"/>
            <line x1="53" y1="0" x2="53" y2="100" stroke="currentColor" stroke-width="1"/>
            <line x1="106" y1="0" x2="106" y2="100" stroke="currentColor" stroke-width="1"/>
          </g>
        </svg>
      </div>
    </div>
  </div>
</section>

<style>
/* 404 Page Styles */
.c-404 {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  text-align: center;
  padding: var(--space-2xl) 0;
}

.c-404__content {
  max-width: 600px;
}

.c-404__number {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-xl);
}

.c-404__digit {
  font-size: clamp(4rem, 15vw, 8rem);
  font-weight: 800;
  color: var(--color-primary);
  line-height: 1;
}

.c-404__digit--center {
  color: var(--color-accent);
}

.c-404__sun {
  width: clamp(4rem, 15vw, 8rem);
  height: clamp(4rem, 15vw, 8rem);
  color: var(--color-accent);
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.05); opacity: 0.8; }
}

.c-404__title {
  font-size: clamp(1.75rem, 5vw, 2.5rem);
  font-weight: 700;
  color: var(--color-text);
  margin-bottom: var(--space-md);
}

.c-404__message {
  font-size: var(--text-lg);
  color: var(--color-text-muted);
  margin-bottom: var(--space-xl);
  line-height: 1.6;
}

.c-404__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-md);
  justify-content: center;
  margin-bottom: var(--space-2xl);
}

.c-404__links {
  border-top: 1px solid var(--color-border);
  padding-top: var(--space-xl);
  width: 100%;
}

.c-404__links-title {
  font-size: var(--text-lg);
  font-weight: 600;
  color: var(--color-text);
  margin-bottom: var(--space-md);
}

.c-404__links-list {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: var(--space-sm) var(--space-lg);
  list-style: none;
  padding: 0;
  margin: 0;
}

.c-404__links-list a {
  color: var(--color-primary);
  text-decoration: none;
  transition: color 0.2s ease;
}

.c-404__links-list a:hover {
  color: var(--color-accent);
  text-decoration: underline;
}

.c-404__decoration {
  margin-top: var(--space-xl);
  opacity: 0.3;
}

.c-404__illustration {
  width: 100%;
  max-width: 400px;
  height: auto;
  color: var(--color-primary);
}

.c-404__rays {
  animation: rays 3s ease-in-out infinite;
}

@keyframes rays {
  0%, 100% { opacity: 0.2; }
  50% { opacity: 0.4; }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .c-404__actions {
    flex-direction: column;
    align-items: center;
  }
  
  .c-404__actions .c-btn {
    width: 100%;
    max-width: 280px;
  }
}
</style>

<?php
// Include footer
include __DIR__ . '/../includes/footer.php';
