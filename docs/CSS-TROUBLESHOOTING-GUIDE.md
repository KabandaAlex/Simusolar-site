# Comprehensive CSS Troubleshooting Guide

## 🚨 Emergency Checklist: Website Lost All CSS Styling

When your website suddenly loses all CSS styling across every page, follow this systematic troubleshooting guide to diagnose and resolve the issue.

---

## Table of Contents

1. [Quick Diagnosis Steps](#quick-diagnosis-steps)
2. [Browser DevTools Investigation](#browser-devtools-investigation)
3. [Common Causes & Solutions](#common-causes--solutions)
4. [Build Tool Configuration Issues](#build-tool-configuration-issues)
5. [Server & Infrastructure Issues](#server--infrastructure-issues)
6. [Project-Specific Troubleshooting](#project-specific-troubleshooting)
7. [Prevention Checklist](#prevention-checklist)

---

## Quick Diagnosis Steps

### Step 1: Hard Refresh First
Before anything else, rule out browser caching:

```
Windows/Linux: Ctrl + Shift + R  OR  Ctrl + F5
Mac: Cmd + Shift + R
```

### Step 2: Check if CSS Files Are Loading
Open Browser DevTools (F12) → **Network Tab** → Filter by "CSS"

**Expected Result:** All CSS files show status `200 OK`  
**Problem Indicators:**
- `404 Not Found` → File path is wrong
- `403 Forbidden` → Permission issue
- `(blocked)` → CORS or mixed content issue
- `500 Internal Server Error` → Server-side problem

### Step 3: Check Console for Errors
DevTools → **Console Tab**

Look for:
- Red error messages about CSS files
- MIME type warnings
- CORS policy errors

---

## Browser DevTools Investigation

### Using the Network Tab (Most Important)

1. **Open DevTools**: Press `F12` or right-click → "Inspect"
2. **Go to Network Tab**: Click "Network" at the top
3. **Filter CSS**: Click "CSS" filter button
4. **Hard Refresh**: Press `Ctrl + Shift + R`
5. **Analyze Results**:

| Status | Meaning | Action |
|--------|---------|--------|
| `200 OK` | File loaded successfully | Check if CSS is being applied (see Elements tab) |
| `304 Not Modified` | Cached version used | Clear browser cache |
| `404 Not Found` | File doesn't exist at URL | Fix file path |
| `403 Forbidden` | No permission to access | Check file permissions |
| `500 Server Error` | Server-side issue | Check server logs |
| `(blocked:mixed-content)` | HTTPS page loading HTTP CSS | Change to HTTPS URL |
| `(blocked by extension)` | Browser extension blocking | Disable extensions |

### Using the Elements Tab

1. **Inspect an element**: Right-click on unstyled element → "Inspect"
2. **Check Styles Panel**: Look at right side "Styles" panel
3. **Diagnose**:
   - **No styles shown** → CSS file not loading
   - **Strikethrough styles** → Being overridden by another rule
   - **Styles present but not applying** → Check for syntax errors in CSS

### Checking Computed Styles

1. In Elements tab, click "Computed" tab (next to Styles)
2. Look for expected CSS properties
3. If values are default/initial, CSS isn't being applied

---

## Common Causes & Solutions

### 1. Incorrect File Paths (Most Common)

#### Relative vs Absolute Paths

**Problem**: Using wrong path type for your environment

```html
<!-- ❌ WRONG: Relative path from current page -->
<link rel="stylesheet" href="css/style.css">

<!-- ❌ WRONG: Relative path going up directories -->
<link rel="stylesheet" href="../static/css/style.css">

<!-- ✅ CORRECT: Absolute path from site root -->
<link rel="stylesheet" href="/static/css/style.css">

<!-- ✅ CORRECT: Using PHP constant (recommended for this project) -->
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/static/css/style.css">
```

#### Path Debugging in This Project

Check your [`config/path.php`](config/path.php) file:

```php
<?php
// Should define BASE_PATH correctly
define('BASE_PATH', '/tulima_php'); // For local development
// define('BASE_PATH', ''); // For production root domain
```

**Test**: View page source and check what URLs are generated for CSS files.

### 2. Broken Links / Missing Files

#### Verify Files Exist

```bash
# Check if CSS files exist (Windows Command Prompt)
dir /s /b *.css

# Check specific directory
dir static\css
```

#### Common File Location Issues

In this project, CSS files should be in:
- `/static/css/` - Main site styles
- `/admin/assets/css/` - Admin panel styles
- `/includes/css/` - Navigation and footer styles
- `/job-portal/pages/assets/css/` - Job portal styles

### 3. 404 Errors in Network Tab

**Diagnosis Steps**:

1. Note the failing URL from Network tab
2. Compare with actual file location
3. Check for:
   - Case sensitivity (style.css vs Style.css)
   - Extra/missing slashes
   - Wrong directory

**Solution Example**:

```
Failing URL: http://localhost/tulima_php/static/css/style.css
Actual Path: c:/wamp64/www/tulima_php/static/css/style.css
```

If file exists but 404s, check:
- Web server configuration
- .htaccess rewrite rules
- Virtual host settings

### 4. MIME Type Mismatches

**Symptom**: Console shows warnings like:
```
Resource interpreted as Stylesheet but transferred with MIME type text/plain
```

**Causes**:
- Server not configured for `.css` extension
- PHP setting wrong content-type
- ModSecurity or similar blocking

**Solutions**:

#### Apache (.htaccess)
```apache
AddType text/css .css
```

#### PHP Header (if serving CSS via PHP)
```php
<?php
header('Content-Type: text/css');
```

#### WAMP Configuration
1. Left-click WAMP icon → Apache → httpd.conf
2. Ensure `AddType text/css .css` exists
3. Restart Apache

### 5. Browser Caching Problems

**Symptoms**:
- CSS works in incognito/private mode
- Changes don't appear after refresh
- Different browsers show different results

**Solutions**:

#### Clear Browser Cache
- Chrome: Ctrl + Shift + Delete → Clear cached images and files
- Firefox: Ctrl + Shift + Delete → Cache

#### Disable Cache During Development
1. Open DevTools (F12)
2. Go to Network tab
3. Check "Disable cache" checkbox
4. Keep DevTools open

#### Add Cache Busting (Development Only)
```html
<link rel="stylesheet" href="/static/css/style.css?v=<?php echo time(); ?>">
```

### 6. CSS Syntax Errors

**Diagnosis**:
1. Open CSS file directly in browser
2. Look for parse errors displayed
3. Use CSS validator: https://jigsaw.w3.org/css-validator/

**Common Syntax Errors**:

```css
/* ❌ Missing closing brace */
.header {
  color: red;

/* ❌ Missing semicolon (may cause subsequent rules to fail) */
.header {
  color: red
  background: blue;
}

/* ❌ Invalid property value */
.header {
  color: #red; /* Should be red or #ff0000 */
}

/* ❌ Unclosed comment */
/* This comment never ends...

/* ✅ Correct CSS */
.header {
  color: red;
  background: blue;
}
```

### 7. CSS Specificity & Override Issues

**Symptom**: CSS loads but styles don't apply

**Diagnosis**:
1. Inspect element in DevTools
2. Check if styles are crossed out
3. Look for more specific selectors overriding

**Solution**: Increase specificity or use `!important` (sparingly)

```css
/* Lower specificity - may be overridden */
.header {
  color: red;
}

/* Higher specificity - more likely to apply */
.site-header .header {
  color: red;
}

/* Using !important - last resort */
.header {
  color: red !important;
}
```

---

## Build Tool Configuration Issues

### Webpack Configuration

**Common Issues**:

#### MiniCssExtractPlugin Misconfiguration

```javascript
// webpack.config.js
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  module: {
    rules: [
      {
        test: /\.css$/,
        use: [
          // ❌ Wrong: style-loader with MiniCssExtractPlugin
          // 'style-loader',
          
          // ✅ Correct: MiniCssExtractPlugin.loader
          MiniCssExtractPlugin.loader,
          'css-loader'
        ]
      }
    ]
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'css/[name].[contenthash].css'
    })
  ]
};
```

#### Public Path Issues

```javascript
// webpack.config.js
module.exports = {
  output: {
    // ❌ Wrong publicPath
    // publicPath: '/dist/',
    
    // ✅ Correct for your setup
    publicPath: '/tulima_php/static/'
  }
};
```

### Vite Configuration

**Common Issues**:

```javascript
// vite.config.js
import { defineConfig } from 'vite';

export default defineConfig({
  base: '/tulima_php/', // Must match your base path
  build: {
    // Ensure CSS is extracted
    cssCodeSplit: false,
  }
});
```

#### CSS Not Loading in Vite

1. Check `base` configuration matches your deployment path
2. Ensure CSS is imported in main entry file:
```javascript
// main.js
import './style.css';
```

### Laravel Mix / General Build Tools

**Check**:
1. Build output directory matches HTML references
2. Versioning/hashing is consistent
3. Manifest file is being read correctly

---

## Server & Infrastructure Issues

### Apache .htaccess Issues

**Common Problems**:

```apache
# .htaccess in CSS directory blocking access
<FilesMatch "\.(css)$">
  Order Allow,Deny
  Deny from all
</FilesMatch>

# Should be:
<FilesMatch "\.(css)$">
  Order Allow,Deny
  Allow from all
</FilesMatch>
```

### Nginx Configuration

```nginx
# Ensure CSS files are served correctly
location ~* \.css$ {
    expires 30d;
    add_header Content-Type text/css;
}
```

### File Permissions (Linux/Mac)

```bash
# CSS files should be readable by web server
chmod 644 /path/to/css/files.css
chown www-data:www-data /path/to/css/files.css
```

### WAMP/Windows Permissions

1. Right-click CSS folder → Properties → Security
2. Ensure `IUSR` and `IIS_IUSRS` have Read access
3. For WAMP, ensure `Users` have Read access

---

## Project-Specific Troubleshooting

### For This Tulima PHP Project

Based on the project structure, check these specific areas:

### 1. Check Header File CSS Loading

File: [`includes/header.php`](includes/header.php)

```php
<!-- Verify these lines exist and paths are correct -->
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/includes/css/navigation-footer.css">
```

### 2. Check BASE_PATH Configuration

File: [`config/path.php`](config/path.php)

```php
<?php
// For local WAMP development
define('BASE_PATH', '/tulima_php');

// For production (root domain)
// define('BASE_PATH', '');
```

### 3. Verify CSS File Locations

Expected structure:
```
tulima_php/
├── static/
│   └── css/
│       └── style.css
├── includes/
│   └── css/
│       └── navigation-footer.css
├── admin/
│   └── assets/
│       └── css/
│           └── admin.css
└── job-portal/
    └── pages/
        └── assets/
            └── css/
                └── job-portal.css
```

### 4. Check Admin Panel CSS

File: [`admin/includes/header.php`](admin/includes/header.php)

Ensure admin CSS is loaded:
```php
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/admin/assets/css/admin.css">
```

### 5. Debug Path Issues

Add this debug code temporarily to see what paths are being generated:

```php
<!-- Add to header.php temporarily -->
<?php
echo '<!-- DEBUG: BASE_PATH = ' . BASE_PATH . ' -->';
echo '<!-- DEBUG: CSS URL = ' . BASE_PATH . '/includes/css/navigation-footer.css -->';
?>
```

---

## Prevention Checklist

### Development Best Practices

- [ ] Use version control for CSS files
- [ ] Test in multiple browsers during development
- [ ] Use consistent path strategy (absolute recommended)
- [ ] Implement CSS linting in build process
- [ ] Document CSS architecture

### Deployment Checklist

- [ ] Update BASE_PATH for production
- [ ] Clear all caches (browser, CDN, server)
- [ ] Verify CSS files exist on server
- [ ] Check file permissions
- [ ] Test on HTTPS if using SSL
- [ ] Verify MIME types configured

### Monitoring

- [ ] Set up 404 alerts for CSS files
- [ ] Monitor for MIME type errors in logs
- [ ] Regular cross-browser testing

---

## Quick Reference: DevTools Shortcuts

| Browser | Open DevTools | Hard Refresh | Disable Cache |
|---------|---------------|--------------|---------------|
| Chrome | F12 or Ctrl+Shift+I | Ctrl+Shift+R | Network tab → checkbox |
| Firefox | F12 or Ctrl+Shift+I | Ctrl+Shift+R | Network tab → checkbox |
| Edge | F12 or Ctrl+Shift+I | Ctrl+Shift+R | Network tab → checkbox |
| Safari | Cmd+Option+I | Cmd+Option+R | Network tab → checkbox |

---

## Still Not Working?

### Final Debug Steps

1. **Create a test CSS file**:
```html
<link rel="stylesheet" href="/test.css">
```

```css
/* test.css */
body { border: 10px solid red !important; }
```

If this works, the problem is with your CSS files. If not, the problem is with how CSS is being loaded.

2. **Check PHP errors**:
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

3. **View Generated Source**:
Right-click → "View Page Source" → Check CSS link URLs

4. **Test CSS URL Directly**:
Copy CSS URL from source, paste in browser address bar - does the CSS content load?

---

## Common Error Messages & Solutions

| Error Message | Likely Cause | Solution |
|---------------|--------------|----------|
| `404 Not Found` | Wrong file path | Fix href attribute |
| `403 Forbidden` | Permission denied | Check file/folder permissions |
| `net::ERR_ABORTED` | Server error | Check server logs |
| `MIME type mismatch` | Server config | Add proper MIME type |
| `CORS policy blocked` | Cross-origin request | Use same protocol/domain |
| `Mixed content blocked` | HTTPS loading HTTP | Change to HTTPS URL |
| `Failed to load resource` | Generic failure | Check Network tab details |

---

*Last Updated: February 2026*
*Project: Tulima Solar Website*
