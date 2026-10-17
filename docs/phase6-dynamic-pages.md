# Phase 6: Dynamic Page Creation System

## Overview

Phase 6 implements a fully dynamic page routing system for the Tulima Solar website. This replaces the hardcoded page routes in `index.php` with a database-driven approach, allowing administrators to create, edit, and manage pages through the CMS without modifying code.

**Implementation Date:** February 2026  
**Status:** Complete

---

## Features Implemented

### 1. Dynamic Page Routing

The front controller (`index.php`) now dynamically resolves pages based on the URL slug:

- **Database-driven routing:** Pages are looked up from the `pages` table by slug
- **Nested slug support:** Supports hierarchical URLs like `/products/solar-pumps`
- **404 handling:** Proper 404 responses for non-existent or inactive pages
- **Template resolution:** Automatically selects the appropriate template based on page settings
- **Backward compatibility:** Existing pages continue to work without modification

### 2. Enhanced Database Schema

New columns added to the `pages` table:

| Column | Type | Description |
|--------|------|-------------|
| `template_type` | ENUM | Page template: 'fullwidth', 'with_sidebar', 'landing', 'custom' |
| `meta_title` | VARCHAR(255) | SEO meta title |
| `requires_auth` | TINYINT(1) | Whether page requires authentication |
| `parent_id` | INT | For hierarchical page structure |
| `custom_template` | VARCHAR(100) | Custom template file name |
| `layout_file` | VARCHAR(100) | Layout file to use |
| `show_in_footer` | TINYINT(1) | Display in footer navigation |

New table `page_templates`:
- Stores template definitions
- Supports custom template management
- Includes display names and descriptions

### 3. Admin Page Management

Enhanced `admin/pages.php` with:

- **Create New Page:** Modal form for creating new pages
- **Edit Pages:** Inline editing with expandable forms
- **Delete Pages:** With confirmation and cascade deletion of sections
- **Duplicate Pages:** One-click page duplication
- **SEO Fields:** Meta title and description editing
- **Template Selection:** Choose from available templates
- **Parent/Child Relationships:** Set page hierarchy
- **Slug Auto-generation:** Automatic URL-safe slug creation
- **Preview Links:** Direct links to view pages on the site

### 4. Page Builder Interface

New `admin/page_builder.php` providing:

- **Visual Section Management:** Drag-and-drop section ordering
- **Section Templates:** Pre-configured section types for quick setup
- **Live Preview:** Real-time preview of page changes
- **Responsive Preview:** Desktop, tablet, and mobile views
- **Section CRUD:** Create, edit, duplicate, and delete sections
- **Page Details Panel:** Quick access to page information and settings

### 5. Helper Functions

New functions in `includes/functions.php`:

```php
// Slug generation
generateSlug(string $title): string
generateUniqueSlug(PDO $pdo, string $title, ?int $excludeId = null): string

// Page operations
getPageByFullPath(PDO $pdo, string $path): ?array
getAllPages(PDO $pdo, array $filters = []): array
getPageById(PDO $pdo, int $id): ?array
createPage(PDO $pdo, array $data): int
updatePage(PDO $pdo, int $id, array $data): bool
deletePage(PDO $pdo, int $id): bool
duplicatePage(PDO $pdo, int $sourceId, string $newTitle): ?int

// Page hierarchy
getChildPages(PDO $pdo, int $parentId): array
getPageBreadcrumb(PDO $pdo, array $page): array

// Templates
getPageTemplates(PDO $pdo): array

// Utilities
isSlugAvailable(PDO $pdo, string $slug, ?int $excludeId = null): bool
```

---

## Installation

### Step 1: Run Database Migration

Execute the migration file to add new columns and tables:

```bash
mysql -u username -p tulima_solar < database/migration_dynamic_pages.sql
```

Or run via phpMyAdmin/MySQL Workbench.

### Step 2: Verify File Changes

Ensure the following files have been updated:

- `index.php` - Dynamic routing logic
- `admin/pages.php` - Enhanced page management
- `admin/page_builder.php` - New page builder interface
- `includes/functions.php` - New helper functions

### Step 3: Clear Cache

If using any caching, clear it to ensure new routes are recognized.

---

## Usage Guide

### Creating a New Page

1. Navigate to **Admin > Pages**
2. Click **Create New Page** button
3. Fill in the required fields:
   - **Title:** Page title (required)
   - **Slug:** URL path (auto-generated if left blank)
   - **Template Type:** Choose appropriate template
   - **Meta Title:** SEO title (optional)
   - **Meta Description:** SEO description (optional)
4. Configure optional settings:
   - Parent page for hierarchy
   - Sort order
   - Active/inactive status
   - Show in footer
5. Click **Create Page**

### Using the Page Builder

1. From the Pages list, click **Builder** on any page
2. **Add Sections:**
   - Click "Add Section" for custom sections
   - Click "Templates" for pre-configured section types
3. **Edit Sections:**
   - Click the edit icon on any section card
   - Modify section key, title, content, and media
4. **Reorder Sections:**
   - Drag and drop sections to change order
   - Order is saved automatically
5. **Preview Changes:**
   - Use the preview panel on the right
   - Switch between desktop, tablet, and mobile views
   - Click refresh to see latest changes

### Managing Page Hierarchy

1. Edit a page from the Pages list
2. Select a **Parent Page** from the dropdown
3. Child pages will display with parent reference in the table
4. Nested URLs are supported (e.g., `products/solar-pumps`)

### SEO Best Practices

1. **Meta Title:** Keep under 60 characters, include page-specific keywords
2. **Meta Description:** Keep under 160 characters, compelling summary
3. **Slug:** Use lowercase, hyphens instead of spaces, descriptive keywords
4. **Page Title:** Clear and descriptive, used as fallback for meta title

---

## Template Types

### Full Width
- Standard page layout
- Content spans full container width
- Best for: General content pages, About, Impact

### With Sidebar
- Includes sidebar navigation
- Content area reduced for sidebar
- Best for: Documentation, long-form content

### Landing Page
- Conversion-focused layout
- Emphasis on hero and CTAs
- Best for: Marketing pages, product highlights

### Custom
- Use a custom PHP template file
- Specify template file name
- Best for: Special layouts, custom functionality

---

## API Reference

### Page Data Structure

```php
[
    'id' => int,
    'slug' => string,
    'title' => string,
    'meta_title' => ?string,
    'meta_description' => ?string,
    'template_type' => string,
    'is_active' => int,
    'sort_order' => int,
    'parent_id' => ?int,
    'hero_title' => ?string,
    'hero_subtitle' => ?string,
    'hero_cta_text' => ?string,
    'hero_cta_url' => ?string,
    'hero_cta_secondary_text' => ?string,
    'hero_cta_secondary_url' => ?string,
    'hero_video_path' => ?string,
    'custom_template' => ?string,
    'layout_file' => string,
    'show_in_footer' => int,
    'requires_auth' => int,
]
```

### Section Data Structure

```php
[
    'id' => int,
    'page_id' => int,
    'section_key' => string,
    'title' => ?string,
    'content' => ?string,
    'content_extra' => ?string,
    'image_path' => ?string,
    'sort_order' => int,
]
```

---

## Troubleshooting

### Page Not Found (404)

1. Verify page exists in database: `SELECT * FROM pages WHERE slug = 'your-slug'`
2. Check `is_active` is set to 1
3. Clear any caching
4. Check for slug conflicts

### Template Not Loading

1. Verify template file exists in `/pages/` directory
2. Check `template_type` is valid
3. For custom templates, verify `custom_template` path

### Sections Not Appearing

1. Verify sections exist: `SELECT * FROM page_sections WHERE page_id = X`
2. Check `sort_order` values
3. Verify section keys match template expectations

### Slug Already Exists

1. Slugs must be unique across all pages
2. Use `generateUniqueSlug()` to auto-generate unique slugs
3. Check for deleted pages with same slug

---

## Security Considerations

1. **Input Sanitization:** All user inputs are sanitized using prepared statements
2. **Slug Validation:** Slugs are validated to prevent directory traversal
3. **Authentication:** Pages can require authentication via `requires_auth` flag
4. **CSRF Protection:** Forms include action validation
5. **SQL Injection:** All database queries use prepared statements

---

## Future Enhancements

### Planned Features

1. **Visual Block Editor:** Drag-and-drop content blocks within sections
2. **Version History:** Track and revert page changes
3. **Scheduled Publishing:** Set publish/unpublish dates
4. **Multi-language Support:** Create pages in multiple languages
5. **Page Analytics:** View page-specific analytics
6. **Export/Import:** Export pages and import to other installations

### Recommended Optimizations

1. **Caching:** Implement page caching for improved performance
2. **CDN Integration:** Serve static assets from CDN
3. **Lazy Loading:** Lazy load sections below the fold
4. **Search Indexing:** Integrate with site search

---

## Files Modified/Created

### New Files
- `database/migration_dynamic_pages.sql` - Database migration
- `admin/page_builder.php` - Page builder interface
- `docs/phase6-dynamic-pages.md` - This documentation

### Modified Files
- `index.php` - Dynamic routing implementation
- `admin/pages.php` - Enhanced page management
- `includes/functions.php` - New helper functions

---

## Changelog

### Version 1.0.0 (February 2026)

**Added:**
- Dynamic page routing system
- Page creation functionality in admin
- Page builder interface
- SEO fields (meta_title, meta_description)
- Page hierarchy support (parent_id)
- Template type selection
- Section templates in page builder
- Drag-and-drop section ordering
- Live preview with device switching
- Page duplication feature
- Slug auto-generation

**Changed:**
- Replaced hardcoded switch statement with dynamic routing
- Enhanced page editing forms with more fields
- Improved page listing with template and hierarchy info

**Database:**
- Added template_type column to pages table
- Added meta_title column to pages table
- Added requires_auth column to pages table
- Added parent_id column to pages table
- Added custom_template column to pages table
- Added layout_file column to pages table
- Created page_templates table
- Created page_hierarchy view

---

## Support

For issues or questions regarding the dynamic pages system:

1. Check this documentation
2. Review the troubleshooting section
3. Check database migration has been applied
4. Contact the development team

---

*Last Updated: February 2026*
