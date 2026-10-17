# CSS Emergency Checklist 🚨

> Quick reference when CSS suddenly stops working. Print this page!

---

## ⚡ 30-Second Quick Check

```
□ Hard refresh: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)
□ Try incognito/private browser mode
□ Check if CSS files show 404 in Network tab (F12 → Network → CSS)
□ View page source - are CSS <link> tags present?
```

---

## 🔍 Browser DevTools Investigation

### Step 1: Open Network Tab
1. Press **F12** (opens DevTools)
2. Click **Network** tab
3. Click **CSS** filter button
4. Press **Ctrl+Shift+R** (hard refresh)
5. Look at Status column

### Step 2: Interpret Network Results

| Status | What It Means | Next Step |
|--------|---------------|-----------|
| ✅ 200 | File loaded | → Check Elements tab for CSS application |
| ⚠️ 304 | Using cache | → Clear browser cache |
| ❌ 404 | File not found | → Fix file path |
| ❌ 403 | Permission denied | → Check file permissions |
| ❌ 500 | Server error | → Check server logs |
| 🚫 blocked | CORS/mixed content | → Fix URL protocol |

### Step 3: Check Console Tab
1. Click **Console** tab in DevTools
2. Look for red error messages
3. Common errors:
   - `Failed to load resource: net::ERR_FILE_NOT_FOUND`
   - `Resource interpreted as Stylesheet but transferred with MIME type text/plain`
   - `Mixed Content: The page was loaded over HTTPS, but requested an insecure stylesheet`

---

## 📁 File Path Issues

### Check Your CSS Links

```html
<!-- If your site is at: http://localhost/tulima_php/ -->

<!-- ❌ WRONG - Relative to current page -->
<link rel="stylesheet" href="css/style.css">

<!-- ❌ WRONG - Wrong root -->
<link rel="stylesheet" href="/css/style.css">

<!-- ✅ CORRECT - Absolute from project root -->
<link rel="stylesheet" href="/tulima_php/static/css/style.css">

<!-- ✅ BEST - Using PHP constant -->
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/static/css/style.css">
```

### Verify BASE_PATH in config/path.php

```php
<?php
// Local development (WAMP in subfolder)
define('BASE_PATH', '/tulima_php');

// Production (root domain)
define('BASE_PATH', '');

// Test it works
echo BASE_PATH; // Should output: /tulima_php
```

---

## 🔧 Quick Fixes by Problem

### Problem: All pages lost CSS suddenly

**Most Likely Causes (in order):**
1. □ BASE_PATH changed or undefined
2. □ CSS file(s) deleted or moved
3. □ .htaccess redirect issue
4. □ Server permission changed
5. □ CDN/external CSS unavailable

### Problem: CSS loads but doesn't apply

**Check:**
1. □ CSS syntax errors (open CSS file directly in browser)
2. □ Classes/IDs don't match between HTML and CSS
3. □ CSS being overridden by other styles (check in Elements tab)
4. □ Browser cache serving old version

### Problem: CSS works on some pages but not others

**Check:**
1. □ Different headers used (compare header files)
2. □ Relative paths failing on nested pages
3. □ Different BASE_PATH values

### Problem: Only external CSS (Bootstrap/CDN) loads

**Check:**
1. □ Local CSS file paths
2. □ Local web server configuration
3. □ File permissions on local CSS files

---

## 🛠️ WAMP/Windows Specific

### Check WAMP Settings
```
1. WAMP icon → Apache → httpd.conf
2. Search for "AddType text/css"
3. Should have: AddType text/css .css
4. Restart Apache
```

### Check File Permissions
```
1. Right-click CSS folder → Properties → Security
2. Ensure "Users" have "Read" permission
3. Ensure "IUSR" has "Read" permission (if using IIS)
```

### Common WAMP Issues
- □ Apache not running (orange icon)
- □ Port 80 blocked by another app
- □ Virtual host misconfigured
- □ .htaccess not enabled (mod_rewrite)

---

## 📋 Debugging Commands

### Check if CSS file exists (Windows CMD)
```cmd
cd c:\wamp64\www\tulima_php
dir /s *.css
```

### Check CSS file content for syntax errors
```cmd
type static\css\style.css
```

### Check PHP is processing correctly
```php
<?php
// Add to header.php temporarily
echo '<!-- BASE_PATH: ' . (defined('BASE_PATH') ? BASE_PATH : 'NOT DEFINED') . ' -->';
echo '<!-- CSS Path: ' . BASE_PATH . '/static/css/style.css -->';
?>
```

---

## ✅ Final Verification

After fixing, verify:

```
□ Hard refresh page (Ctrl+Shift+R)
□ Check Network tab - all CSS files show 200
□ Check Console tab - no red errors
□ Inspect element - styles visible in Styles panel
□ Test on multiple pages
□ Test in different browser
□ Test on mobile device
```

---

## 📞 Still Not Working?

### Ultimate Debug Test

Create a minimal test file `css-test.php`:

```php
<!DOCTYPE html>
<html>
<head>
    <title>CSS Test</title>
    <style>
        body { background: red; }
    </style>
    <link rel="stylesheet" href="/tulima_php/test.css">
</head>
<body>
    <h1>If background is red, inline CSS works</h1>
    <p class="test">If this is blue, external CSS works</p>
</body>
</html>
```

Create `test.css` in project root:
```css
.test { color: blue; }
```

**Results:**
- Red background = CSS processing works
- Blue text = External CSS loading works
- Neither = Server/bigger issue

---

## Quick Reference: HTTP Status Codes

| Code | Name | CSS Troubleshooting |
|------|------|---------------------|
| 200 | OK | File loaded successfully |
| 304 | Not Modified | Using cached version |
| 403 | Forbidden | Permission denied |
| 404 | Not Found | Wrong file path |
| 500 | Internal Server Error | Server-side problem |
| 503 | Service Unavailable | Server overloaded/down |

---

*Keep this checklist handy for CSS emergencies!*
