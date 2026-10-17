# Contact Page Changes Not Visible - Troubleshooting Guide

## Problem
You edited `pages/contact.php` and `assets/css/pages/contact.css` but the changes are not visible on the frontend.

## Possible Causes & Solutions

### 1. Browser Caching (Most Common)

**Symptoms:** Changes made to CSS or HTML don't appear after refreshing the page.

**Solutions:**
- **Hard Refresh:** Press `Ctrl + F5` (Windows) or `Cmd + Shift + R` (Mac)
- **Clear Browser Cache:**
  - Chrome: Settings > Privacy and security > Clear browsing data > Cached images and files
  - Firefox: Options > Privacy & Security > Cookies and Site Data > Clear Data
  - Edge: Settings > Privacy, search, and services > Clear browsing data
- **Try Incognito/Private Mode:** Open the page in an incognito window

### 2. Server-Side Caching

**Symptoms:** Changes persist even after clearing browser cache.

**Solutions:**
- **Restart WAMP Server:** Stop and start Apache
- **Clear OPcache (if enabled):**
  - Restart Apache server
  - Or create a file `clear_cache.php` with:
    ```php
    <?php
    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "OPcache cleared";
    } else {
        echo "OPcache not enabled";
    }
    ```

### 3. Database Page Overriding File

**Symptoms:** The contact page shows old content even after updating `pages/contact.php`.

**Cause:** The routing system in `index.php` first checks the database for a page. If a contact page exists in the `pages` table, it will use that instead of `pages/contact.php`.

**Solutions:**
- **Run the diagnostic script:** Open `http://localhost:8081/tulima_php/check_contact_db.php` in your browser
- **Disable the database page:**
  - Go to phpMyAdmin
  - Select `tulima_solar` database
  - Run: `UPDATE pages SET is_active = 0 WHERE slug = 'contact' OR slug = 'contact-us';`
- **Delete the database page:**
  - Run: `DELETE FROM pages WHERE slug = 'contact' OR slug = 'contact-us';`

### 4. CSS File Not Loading

**Symptoms:** Page loads but styling is missing or outdated.

**Solutions:**
- **Check CSS file exists:** Verify `assets/css/pages/contact.css` exists
- **Check file permissions:** Ensure the file is readable
- **Check CSS path:** View page source and verify the CSS link is correct
- **Test CSS URL directly:** Open `http://localhost:8081/tulima_php/assets/css/pages/contact.css` in browser

### 5. PHP Syntax Errors

**Symptoms:** Blank page or PHP error shown.

**Solutions:**
- **Check PHP error logs:** Look in `C:\wamp64\logs\apache_error.log`
- **Test PHP syntax:** Run `php -l pages/contact.php` in command line
- **Check for missing semicolons, unclosed brackets, or syntax errors**

### 6. .htaccess Caching Rules

**Symptoms:** CSS changes not reflecting due to browser caching headers.

**Cause:** The `.htaccess` file has caching rules for CSS files (1 month cache).

**Solutions:**
- **Cache-busting:** The contact page now includes `?v=TIMESTAMP` in the CSS URL
- **Clear browser cache:** After cache-busting, do a hard refresh
- **Temporarily disable caching:** Comment out the ExpiresByType lines in `.htaccess`:
  ```apache
  # ExpiresByType text/css "access plus 1 month"
  ```

### 7. Wrong File Being Edited

**Symptoms:** Changes not appearing because you edited the wrong file.

**Solutions:**
- **Verify file paths:** Ensure you're editing `c:\wamp64\www\tulima_php\pages\contact.php`
- **Check file timestamps:** Verify the file modification date is recent
- **Use VS Code's Open Files:** Check the visible tabs to confirm the right file is open

## Diagnostic Steps

### Step 1: Run the Debug Script
Open `http://localhost:8081/tulima_php/debug_contact.php` in your browser. This will show:
- If contact pages exist in the database
- If the files exist on disk
- The expected CSS URL

### Step 2: Check Database Pages
Open `http://localhost:8081/tulima_php/check_contact_db.php` to see if there are contact pages in the database that might be overriding your file.

### Step 3: View Page Source
1. Open the contact page in your browser
2. Right-click and select "View Page Source"
3. Search for `contact.css` in the source
4. Verify the URL is correct and includes the cache-busting parameter

### Step 4: Test CSS Directly
Open the CSS file URL directly in your browser:
```
http://localhost:8081/tulima_php/assets/css/pages/contact.css
```
Verify the content shows your premium styles.

## Quick Fix Checklist

- [ ] Hard refresh browser (Ctrl+F5)
- [ ] Clear browser cache
- [ ] Restart WAMP server
- [ ] Run `check_contact_db.php` to check for database pages
- [ ] Disable/delete contact pages in database if found
- [ ] View page source to verify CSS is loading
- [ ] Test CSS URL directly in browser
- [ ] Check Apache error logs for PHP errors

## Most Likely Issue

Based on the project structure, the **most likely cause** is that there's a contact page in the database that is overriding `pages/contact.php`. The routing system in `index.php` first checks the database for a page before falling back to the file system.

**To fix this:**
1. Open `http://localhost:8081/tulima_php/check_contact_db.php`
2. If contact pages are found, run this SQL in phpMyAdmin:
   ```sql
   UPDATE pages SET is_active = 0 WHERE slug = 'contact' OR slug = 'contact-us';
   ```
3. Clear your browser cache (Ctrl+F5)
4. Refresh the contact page

## Additional Help

If the issue persists after trying these solutions:
1. Check the Apache error log: `C:\wamp64\logs\apache_error.log`
2. Enable PHP error display (temporarily) by adding to `pages/contact.php`:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
3. Check that `includes/functions.php` exists and is being loaded correctly
