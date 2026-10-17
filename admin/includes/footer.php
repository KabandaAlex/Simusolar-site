</main>
  
  <?php
  // Ensure we have database access for dynamic footer navigation
  if (!defined('BASE_PATH')) {
      require_once __DIR__ . '/../config/path.php';
  }
  require_once __DIR__ . '/../config/database.php';
  require_once __DIR__ . '/functions.php';
  
  $pdo = getDb();
  $footerNavItems = buildNavigationTree(getNavigationItems($pdo, 'footer', true));

  if (!isset($siteBase)) {
      $siteBase = rtrim(BASE_PATH, '/');
  }
  if (!isset($siteName)) {
      $siteName = 'Tulima Solar';
  }

  if (!function_exists('renderFooterLink')) {
      function renderFooterLink(array $item, string $siteBase): string {
          $url = $item['url'] ?? '';
          if ($url !== '' && !preg_match('/^https?:\/\//', $url)) {
              $url = $siteBase . '/' . ltrim($url, '/');
          }
          $target = $item['target'] ?? '_self';
          return sprintf(
              '<a href="%s" class="c-footer__link"%s>%s</a>',
              htmlspecialchars($url),
              $target !== '_self' ? ' target="' . htmlspecialchars($target) . '" rel="noopener noreferrer"' : '',
              htmlspecialchars($item['title'] ?? '')
          );
      }
  }
  ?>
  <style>
    html { background: #4A0066 !important; }
    body { padding-bottom: 0 !important; margin-bottom: 0 !important; }
    .c-footer {
      background: #4A0066 !important;
      overflow: visible !important;
      padding-bottom: 4rem !important;
      box-shadow: 0 200px 0 #4A0066 !important;
    }
    .c-footer__inner { max-width: 1140px; margin: 0 auto; padding: 2rem 1rem 1.5rem; box-sizing: border-box; }
    .c-footer__row { display: flex !important; flex-wrap: wrap !important; align-items: flex-start !important; justify-content: space-between !important; gap: 2rem; }
    .c-footer__top { padding-bottom: 2rem; border-bottom: 1px solid rgba(255,255,255,0.15); }
    .c-footer__brand { flex: 0 1 280px; min-width: 0; }
    .c-footer__links-row { display: flex !important; flex-wrap: wrap !important; gap: 2rem 2.5rem !important; flex: 1 1 400px; min-width: 0; justify-content: flex-end !important; }
    .c-footer__col { flex: 0 0 auto; min-width: 120px; }
    .c-footer__bottom { display: flex !important; flex-wrap: wrap !important; align-items: center !important; justify-content: space-between !important; gap: 1rem; padding-top: 1.5rem; }
    .c-footer__legal { display: flex !important; flex-wrap: wrap !important; gap: 1rem !important; }
    .c-footer ul, .c-footer__list, .c-footer__contact { list-style: none !important; margin: 0 !important; padding: 0 !important; }
    .c-footer__logo-icon { width: 32px !important; height: 32px !important; max-width: 32px !important; max-height: 32px !important; overflow: hidden !important; flex-shrink: 0 !important; }
    .c-footer__logo-icon svg { width: 32px !important; height: 32px !important; max-width: 32px !important; max-height: 32px !important; }
    .c-footer svg { max-width: 48px !important; max-height: 48px !important; width: auto !important; height: auto !important; }
    .c-footer__back-top { width: 48px !important; height: 48px !important; overflow: hidden !important; }
    .c-footer__back-top svg { width: 24px !important; height: 24px !important; max-width: 24px !important; max-height: 24px !important; }
    /* --- SOCIAL MEDIA ICON FIX --- */
    .c-footer__social {
        display: flex !important;
        gap: 15px !important; /* Space between the circles */
        margin-top: 1.5rem !important;
    }

    /* This creates the perfect circle container */
    .c-footer__social-item {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 45px !important;
        height: 45px !important;
        border-radius: 50% !important;
        background: rgba(255, 255, 255, 0.05) !important; /* Faint background */
        border: 1px solid rgba(255, 255, 255, 0.2) !important; /* Thin white border */
        color: #ffffff !important;
        transition: all 0.3s ease !important;
        text-decoration: none !important;
    }

    /* This shrinks the actual icon to fit inside the circle */
    .c-footer__social-item svg {
        width: 20px !important;
        height: 20px !important;
        max-width: 20px !important;
        max-height: 20px !important;
    }

    /* This adds the beautiful orange hover effect from your second image */
    .c-footer__social-item:hover {
        background: var(--accent-orange, #e98e39) !important; 
        border-color: var(--accent-orange, #e98e39) !important;
        transform: translateY(-3px) !important;
    }

    /* --- FOOTER LINKS TEXT COLOR --- */
    .c-footer__link,
    .c-footer__list li a {
        color: #ffffff !important; /* Makes the text solid white */
        text-decoration: none !important; /* Removes underlines */
        transition: color 0.3s ease !important;
    }

    /* Adds a beautiful orange color when hovering over the links */
    .c-footer__link:hover,
    .c-footer__list li a:hover {
        color: var(--accent-orange, #e98e39) !important; 
    }
    
    /* Make sure the column headings are also white */
    .c-footer__heading {
        color: #ffffff !important;
        font-weight: bold !important;
        margin-bottom: 1.5rem !important;
    }
  </style>
  <footer class="c-footer" role="contentinfo">
    <div class="c-footer__inner">
      <div class="c-footer__row c-footer__top">
        <div class="c-footer__brand">
          <a href="<?php echo $siteBase; ?>/" class="c-nav__logo" aria-label="Tulima Solar – Home">
            <img src="<?php echo $siteBase; ?>/static/images/Simusolar-logo-200.png" alt="Simusolar Logo" style="height: 100px; width: auto; object-fit: contain;">
          </a>
          <p class="c-footer__tagline">
            Empowering communities with sustainable solar energy. We make renewable power accessible, affordable, and reliable.
          </p>
          <div class="c-footer__social" aria-label="Social media">
            <a href="https://web.facebook.com/simusolarinc" class="c-footer__social-item" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
            </a>
            <a href="https://twitter.com/simusolar" class="c-footer__social-item" aria-label="Twitter" target="_blank" rel="noopener noreferrer">
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
            </a>
            <a href="https://tz.linkedin.com/company/simusolar-tz" class="c-footer__social-item" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer">
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
            </a>
            <a href="https://instagram.com/simusolartanzania" class="c-footer__social-item" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
            </a>
            <a href="https://www.youtube.com/@simusolarltd6856" class="c-footer__social-item" aria-label="Youtube" target="_blank" rel="noopener noreferrer">
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M21.582,6.186c-0.23-0.86-0.908-1.538-1.768-1.768C18.254,4,12,4,12,4S5.746,4,4.186,4.418c-0.86,0.23-1.538,0.908-1.768,1.768C2,7.746,2,12,2,12s0,4.254,0.418,5.814c0.23,0.86,0.908,1.538,1.768,1.768C5.746,20,12,20,12,20s6.254,0,7.814-0.418c0.86-0.23,1.538-0.908,1.768-1.768C22,16.254,22,12,22,12S22,7.746,21.582,6.186z M10,15.464V8.536L16,12L10,15.464z"/>
              </svg>
            </a>
          </div>
        </div>

        <div class="c-footer__links-row">
          <?php if (empty($footerNavItems)): ?>
          <div class="c-footer__col">
            <h3 class="c-footer__heading">Company</h3>
            <ul class="c-footer__list">
              <li><a href="<?php echo $siteBase; ?>/about" class="c-footer__link">About Us</a></li>
              <li><a href="<?php echo $siteBase; ?>/contact" class="c-footer__link">Contact</a></li>
            </ul>
          </div>
          <?php else: ?>
            <?php foreach ($footerNavItems as $column): ?>
            <div class="c-footer__col">
              <h3 class="c-footer__heading"><?php echo htmlspecialchars($column['title']); ?></h3>
              <ul class="c-footer__list">
                <?php if (!empty($column['children'])): ?>
                  <?php foreach ($column['children'] as $item): ?>
                  <li><?php echo renderFooterLink($item, $siteBase); ?></li>
                  <?php endforeach; ?>
                <?php else: ?>
                  <li><?php echo renderFooterLink($column, $siteBase); ?></li>
                <?php endif; ?>
              </ul>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <div class="c-footer__col">
            <h3 class="c-footer__heading">Contact</h3>
            <ul class="c-footer__contact" aria-label="Contact information">
              <li class="c-footer__contact-item">
                <span class="c-footer__contact-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </span>
                <span class="c-footer__contact-text">Dar es Salaam, Tanzania· East Africa</span>
              </li>
              <li class="c-footer__contact-item">
                <span class="c-footer__contact-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.913.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.897.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </span>
                <a href="tel:+255 677 028 680" class="c-footer__contact-text c-footer__link">+255 677 028 680</a>
              </li>
              <li class="c-footer__contact-item">
                <span class="c-footer__contact-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </span>
                <a href="mailto:info@simusolar.com" class="c-footer__contact-text c-footer__link">info@simusolar.com</a>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div class="c-footer__newsletter">
        <p class="c-footer__newsletter-title">Stay updated</p>
        <p class="c-footer__newsletter-desc">News and offers in your inbox.</p>
        <form class="c-footer__newsletter-form" action="<?php echo $siteBase; ?>/api/newsletter" method="post">
          <label for="footer-newsletter-email" class="sr-only">Email</label>
          <input type="email" id="footer-newsletter-email" name="email" class="c-footer__newsletter-input" placeholder="Your email" required>
          <button type="submit" class="c-footer__newsletter-btn">Subscribe</button>
        </form>
      </div>

      <div class="c-footer__row c-footer__bottom" style="width: 100%; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem; margin-top: 2rem;">
        
        <p class="c-footer__copy" style="color: rgba(255,255,255,0.7); margin: 0;">
          &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?>. All rights reserved.
        </p>

        <div style="display: flex; gap: 15px; align-items: center; z-index: 10;">
          <a href="<?php echo $siteBase; ?>/privacy-policy" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.9rem;">Privacy Policy</a>
          
          <a href="<?php echo $siteBase; ?>/terms-conditions" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.9rem;">Terms & Conditions</a>

          <a href="<?php echo $siteBase; ?>/cookies" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.9rem;">Cookies</a>

          <a href="<?php echo $siteBase; ?>/faq" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.9rem;">FAQ</a>

        </div>

      </div>
    </div>
  </footer>

  <button type="button" class="c-footer__back-top" id="back-to-top" aria-label="Back to top">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="18 15 12 9 6 15"/></svg>
  </button>
  
  <script>
    window.SITE_CONFIG = {
      basePath: <?php echo json_encode(BASE_PATH); ?>,
      ajaxUrl: <?php echo json_encode(BASE_PATH . '/api/'); ?>,
      siteName: <?php echo json_encode($siteName ?? 'Tulima Solar'); ?>
    };
  </script>
  
  <script src="<?php echo $siteBase; ?>/assets/js/main.js" defer></script>
  
  <?php if (isset($pageJs)): ?>
  <script src="<?php echo $siteBase; ?>/assets/js/pages/<?php echo htmlspecialchars($pageJs); ?>" defer></script>
  <?php endif; ?>
  
  <script>
    (function() {
      'use strict';
      
      // DOM Elements
      const navToggle = document.getElementById('nav-toggle');
      const mobileMenu = document.getElementById('mobile-menu');
      const navOverlay = document.getElementById('nav-overlay');
      const mainNav = document.getElementById('main-nav');
      const backToTop = document.getElementById('back-to-top');
      
      // Mobile menu toggle
      function toggleMobileMenu() {
        const isOpen = navToggle.getAttribute('aria-expanded') === 'true';
        
        navToggle.setAttribute('aria-expanded', !isOpen);
        mobileMenu.classList.toggle('is-open', !isOpen);
        mobileMenu.setAttribute('aria-hidden', isOpen);
        navOverlay.classList.toggle('is-visible', !isOpen);
        document.body.style.overflow = isOpen ? '' : 'hidden';
      }
      
      // Close mobile menu
      function closeMobileMenu() {
        navToggle.setAttribute('aria-expanded', 'false');
        mobileMenu.classList.remove('is-open');
        mobileMenu.setAttribute('aria-hidden', 'true');
        navOverlay.classList.remove('is-visible');
        document.body.style.overflow = '';
      }
      
      // Event listeners
      if (navToggle) {
        navToggle.addEventListener('click', toggleMobileMenu);
      }
      
      if (navOverlay) {
        navOverlay.addEventListener('click', closeMobileMenu);
      }
      
      // Close menu on escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          closeMobileMenu();
        }
      });
      
      // Close menu on window resize (if switching to desktop)
      window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
          closeMobileMenu();
        }
      });
      
      // Nav scroll effect: when scrolled, add solid background + shadow so menu stays readable on all pages
      if (mainNav) {
        function handleNavScroll() {
          if (window.scrollY > 30) {
            mainNav.classList.add('is-scrolled');
          } else {
            mainNav.classList.remove('is-scrolled');
          }
        }
        window.addEventListener('scroll', handleNavScroll, { passive: true });
        handleNavScroll(); // run once on load
      }
      
      // Back to top button
      if (backToTop) {
        function handleBackToTopVisibility() {
          if (window.scrollY > 500) {
            backToTop.classList.add('is-visible');
          } else {
            backToTop.classList.remove('is-visible');
          }
        }
        
        window.addEventListener('scroll', handleBackToTopVisibility, { passive: true });
        
        backToTop.addEventListener('click', function() {
          window.scrollTo({
            top: 0,
            behavior: 'smooth'
          });
        });
      }
      
      // Smooth scroll for anchor links
      document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
          const targetId = this.getAttribute('href');
          if (targetId === '#') return;
          
          const target = document.querySelector(targetId);
          if (target) {
            e.preventDefault();
            const navHeight = mainNav ? mainNav.offsetHeight : 0;
            const targetPosition = target.getBoundingClientRect().top + window.scrollY - navHeight - 20;
            
            window.scrollTo({
              top: targetPosition,
              behavior: 'smooth'
            });
            
            // Update focus for accessibility
            target.setAttribute('tabindex', '-1');
            target.focus();
          }
        });
      });
      
    })();
  </script>

  <?php
  // ==========================================
  // BOTPRESS CHATBOT TOGGLE LOGIC (PDO)
  // ==========================================
  $showBot = false;

  try {
      // Use the existing $pdo connection that is already initialized at the top of this file
      if (isset($pdo)) {
          $botStmt = $pdo->query("SELECT chatbot_enabled FROM site_settings LIMIT 1");
          $botRow = $botStmt->fetch();
          
          if ($botRow && (int)$botRow['chatbot_enabled'] === 1) {
              $showBot = true;
          }
      }
  } catch (Exception $e) {
      // Failsafe: if the query fails, the bot defaults to off
  }

  // Render the Botpress widget ONLY if the database toggle is set to 1
  if ($showBot): 
  ?>
      <script src="https://cdn.botpress.cloud/webchat/v3.6/inject.js"></script>
      <script src="https://files.bpcontent.cloud/2026/07/07/12/20260707121417-6HBO65HQ.js" defer></script>
  <?php endif; ?>

</body>
</html>
<?php 
// ==========================================
// SUNBIRD AI: INTERCEPT AND TRANSLATE PAGE
// ==========================================
if (isset($site_lang) && $site_lang !== 'eng' && ob_get_level() > 0) {
    // Grab the full recorded HTML from the buffer
    $fullPageHtml = ob_get_clean(); 
    
    // Process it through the DOM parser to translate all text nodes
    echo auto_translate_html($fullPageHtml, $site_lang); 
}
?>