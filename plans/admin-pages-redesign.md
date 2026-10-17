# Admin Pages Redesign Plan

## Overview

This document outlines the comprehensive redesign of 11 admin pages to match the modern, polished design of the dashboard. The dashboard has been redesigned with enhanced visual components, and this plan brings the same design language to all management pages.

---

## Design System Analysis

### Dashboard Design Patterns to Apply

The current dashboard (`admin/index.php`) features these modern design elements:

1. **Dashboard Hero Section**
   - Gradient background with decorative elements
   - Greeting text with personalized welcome
   - Action buttons with icons
   - Subtitle with descriptive text

2. **Enhanced Stat Cards**
   - Color-coded variants (purple, orange, green, blue)
   - Icon headers with trend indicators
   - Large values with labels
   - Sparkline placeholders for mini charts

3. **Analytics Overview Section**
   - Section header with icon and title
   - Period indicator badge
   - Grid of stat cards

4. **Admin Cards**
   - Clean white background with subtle shadows
   - Header with icon and title
   - Action buttons in header
   - Organized body content

5. **Data Tables**
   - Clean header styling
   - Primary/subtitle cell content
   - Badge indicators for status
   - Hover effects

6. **Quick Links**
   - Icon + content layout
   - Descriptive subtitles
   - Hover animations

---

## Pages to Redesign

### 1. Pages Management (`admin/pages.php`)

**Current State:**
- Basic page intro with title and subtitle
- Simple admin card with table
- Inline edit forms that expand below rows
- Basic form controls

**Proposed Improvements:**
```
+------------------------------------------------------------------+
|  PAGES MANAGEMENT                                                 |
|  Manage page titles, meta descriptions, and hero sections        |
+------------------------------------------------------------------+
|  +--------------------+  +--------------------+  +---------------+
|  | Total Pages        |  | Active Pages       |  | With Hero Video|
|  | 24                 |  | 18                 |  | 3              |
|  | ↑ 2 this month     |  | 75% published      |  | 12.5%          |
|  +--------------------+  +--------------------+  +---------------+
+------------------------------------------------------------------+
|  PAGES TABLE                                                      |
|  +----------------------------------------------------------+    |
|  | Filter: [All ▾] [Active ▾]    [Search...        ] 🔍   |    |
|  +----------------------------------------------------------+    |
|  | Slug      | Title        | Status  | Hero   | Actions   |    |
|  |-----------|--------------|---------|--------|-----------|    |
|  | home      | Home         | ✓ Active| Video  | Edit | View|    |
|  | about     | About Us     | ✓ Active| Image  | Edit | View|    |
|  | products  | Products     | ✓ Active| -      | Edit | View|    |
|  +----------------------------------------------------------+    |
+------------------------------------------------------------------+
```

**Key Changes:**
- Add stats overview cards at top
- Improve table with better visual hierarchy
- Add filter/search toolbar
- Enhanced edit form in modal or slide-out panel
- Add "View on site" links

---

### 2. Sections Management (`admin/sections.php`)

**Current State:**
- Basic filter dropdown
- Simple table with edit buttons
- Inline expand forms

**Proposed Improvements:**
```
+------------------------------------------------------------------+
|  PAGE SECTIONS                                                    |
|  Edit content blocks for each page - titles, body text, images   |
+------------------------------------------------------------------+
|  +--------------------+  +--------------------+  +---------------+
|  | Total Sections     |  | With Images        |  | With Items     |
|  | 32                 |  | 18                 |  | 8              |
|  +--------------------+  +--------------------+  +---------------+
+------------------------------------------------------------------+
|  FILTER & SEARCH                                                  |
|  [Filter by Page ▾]  [Filter by Type ▾]  [Search...        ]    |
+------------------------------------------------------------------+
|  SECTIONS TABLE                                                   |
|  +----------------------------------------------------------+    |
|  | Page      | Section Key  | Title        | Actions       |    |
|  |-----------|--------------|--------------|---------------|    |
|  | home      | hero         | Welcome...   | Edit | Items  |    |
|  | home      | features     | Our Features | Edit | Items  |    |
|  | about     | team         | Our Team     | Edit          |    |
|  +----------------------------------------------------------+    |
+------------------------------------------------------------------+
```

**Key Changes:**
- Add section stats cards
- Enhanced filter bar with multiple options
- Visual indicators for sections with items
- Better visual hierarchy in table

---

### 3. Products Management (`admin/products.php`)

**Current State:**
- Basic add form toggle
- Simple table with image thumbnails
- Inline edit forms

**Proposed Improvements:**
```
+------------------------------------------------------------------+
|  PRODUCTS                                                         |
|  Manage your product catalog - add, edit, and organize products  |
+------------------------------------------------------------------+
|  +--------------------+  +--------------------+  +---------------+
|  | Total Products     |  | Active             |  | With Images    |
|  | 12                 |  | 10                 |  | 11             |
|  | ↑ 2 this month     |  | 83% visible        |  | 92%            |
|  +--------------------+  +--------------------+  +---------------+
+------------------------------------------------------------------+
|  [+ Add Product]                    [Search...        ] [Export] |
+------------------------------------------------------------------+
|  PRODUCTS GRID/TABLE                                              |
|  +----------------------------------------------------------+    |
|  | [Image] | Title        | Slug       | Status | Actions   |    |
|  |---------|--------------|------------|--------|-----------|    |
|  | [img]   | Solar Pump   | solar-pump | ✓ Active| Edit |View|    |
|  | [img]   | Chaff Cutter | chaff-...  | ✓ Active| Edit |View|    |
|  +----------------------------------------------------------+    |
+------------------------------------------------------------------+
```

**Key Changes:**
- Product stats overview
- Toggle between grid and table view
- Enhanced product cards with images
- Quick status toggle
- Better image preview

---

### 4. Media Library (`admin/media/index.php`)

**Current State:**
- Upload form toggle
- Grid of media items
- Basic metadata display

**Proposed Improvements:**
```
+------------------------------------------------------------------+
|  MEDIA LIBRARY                                                    |
|  Upload and manage images and videos for your website            |
+------------------------------------------------------------------+
|  +--------------------+  +--------------------+  +---------------+
|  | Total Files        |  | Images             |  | Videos         |
|  | 156                |  | 142                |  | 14             |
|  | 2.4 GB used        |  | 1.8 GB             |  | 0.6 GB         |
|  +--------------------+  +--------------------+  +---------------+
+------------------------------------------------------------------+
|  [+ Upload]  [Sync]  [Sort: Newest ▾]  [Type: All ▾]  [Search...]|
+------------------------------------------------------------------+
|  UPLOAD DROPZONE (collapsible)                                    |
|  +----------------------------------------------------------+    |
|  |  📁 Drag & drop files here or click to browse            |    |
|  |  Supports: JPG, PNG, GIF, WebP, MP4, WebM (Max 100MB)   |    |
|  +----------------------------------------------------------+    |
+------------------------------------------------------------------+
|  MEDIA GRID                                                       |
|  +--------+  +--------+  +--------+  +--------+  +--------+      |
|  | [img]  |  | [img]  |  | [vid]  |  | [img]  |  | [img]  |      |
|  | title  |  | title  |  | title  |  | title  |  | title  |      |
|  | Edit   |  | Edit   |  | Edit   |  | Edit   |  | Edit   |      |
|  +--------+  +--------+  +--------+  +--------+  +--------+      |
+------------------------------------------------------------------+
```

**Key Changes:**
- Storage usage stats
- Drag-and-drop upload zone
- Filter by type (images/videos)
- Better thumbnail display
- Bulk selection for actions

---

### 5. Testimonials (`admin/testimonials.php`)

**Current State:**
- Basic add form toggle
- Simple table with image thumbnails
- Quote truncation in table

**Proposed Improvements:**
```
+------------------------------------------------------------------+
|  TESTIMONIALS                                                     |
|  Manage client testimonials displayed on your website            |
+------------------------------------------------------------------+
|  +--------------------+  +--------------------+  +---------------+
|  | Total              |  | Active             |  | With Photos    |
|  | 8                  |  | 6                  |  | 5              |
|  +--------------------+  +--------------------+  +---------------+
+------------------------------------------------------------------+
|  [+ Add Testimonial]                                               |
+------------------------------------------------------------------+
|  TESTIMONIALS CARDS                                               |
|  +----------------------------------------------------------+    |
|  |  [Photo]  "Great service..."                             |    |
|  |           - John Doe, Farmer                             |    |
|  |           ✓ Active                    [Edit] [Delete]    |    |
|  +----------------------------------------------------------+    |
|  |  [Photo]  "Amazing products..."                          |    |
|  |           - Jane Smith, Business Owner                   |    |
|  |           ✓ Active                    [Edit] [Delete]    |    |
|  +----------------------------------------------------------+    |
+------------------------------------------------------------------+
```

**Key Changes:**
- Card-based layout instead of table
- Full quote display in cards
- Photo thumbnails prominent
- Quick status toggle
- Better visual hierarchy

---

### 6. Partners (`admin/partners.php`)

**Current State:**
- Basic table with logo thumbnails
- Simple add/edit forms

**Proposed Improvements:**
```
+------------------------------------------------------------------+
|  PARTNERS                                                         |
|  Manage partner logos and links displayed on your website        |
+------------------------------------------------------------------+
|  +--------------------+  +--------------------+  +---------------+
|  | Total Partners     |  | Active             |  | With Logos     |
|  | 15                 |  | 12                 |  | 14             |
|  +--------------------+  +--------------------+  +---------------+
+------------------------------------------------------------------+
|  [+ Add Partner]                                                   |
+------------------------------------------------------------------+
|  PARTNERS GRID                                                    |
|  +--------+  +--------+  +--------+  +--------+  +--------+      |
|  | [logo] |  | [logo] |  | [logo] |  | [logo] |  | [logo] |      |
|  | Name   |  | Name   |  | Name   |  | Name   |  | Name   |      |
|  | ✓Active|  | ✓Active|  | ✗Inactive| | ✓Active|  | ✓Active|      |
|  | [Edit] |  | [Edit] |  | [Edit] |  | [Edit] |  | [Edit] |      |
|  +--------+  +--------+  +--------+  +--------+  +--------+      |
+------------------------------------------------------------------+
```

**Key Changes:**
- Grid layout for logo display
- Visual logo preview
- Quick status toggle
- Drag-and-drop reordering

---

### 7. Impact Numbers (`admin/impact_numbers.php`)

**Current State:**
- Basic table layout
- Simple add/edit forms

**Proposed Improvements:**
```
+------------------------------------------------------------------+
|  IMPACT NUMBERS                                                   |
|  Manage statistics displayed in the Our Impact section           |
+------------------------------------------------------------------+
|  +--------------------+  +--------------------+  +---------------+
|  | Total Stats        |  | Active             |  | Icons Used     |
|  | 4                  |  | 4                  |  | 4              |
|  +--------------------+  +--------------------+  +---------------+
+------------------------------------------------------------------+
|  [+ Add Stat]                                                      |
+------------------------------------------------------------------+
|  IMPACT STATS PREVIEW                                             |
|  +----------------------------------------------------------+    |
|  |  Preview how stats appear on the website:                |    |
|  |  +--------+  +--------+  +--------+  +--------+          |    |
|  |  | 👥     |  | 🌿     |  | 📊     |  | 📍     |          |    |
|  |  | 50K+   |  | 10K+   |  | 40%    |  | 5      |          |    |
|  |  | Farmers|  | Acres  |  | Income |  | Offices|          |    |
|  |  +--------+  +--------+  +--------+  +--------+          |    |
|  +----------------------------------------------------------+    |
|  STATS TABLE                                                      |
|  | Label           | Value    | Icon    | Status | Actions   |    |
|  |-----------------|----------|---------|--------|-----------|    |
|  | Farmers...      | 50K+     | farmers | ✓ Active| Edit | Del|    |
|  +----------------------------------------------------------+    |
+------------------------------------------------------------------+
```

**Key Changes:**
- Live preview of how stats appear
- Visual icon selector
- Better visual representation
- Drag-and-drop reordering

---

### 8. Office Locations (`admin/office_locations.php`)

**Current State:**
- Basic table layout
- Simple forms with lat/lng fields

**Proposed Improvements:**
```
+------------------------------------------------------------------+
|  OFFICE LOCATIONS                                                 |
|  Manage office locations displayed on your website               |
+------------------------------------------------------------------+
|  +--------------------+  +--------------------+  +---------------+
|  | Total Offices      |  | Active             |  | With Maps      |
|  | 5                  |  | 5                  |  | 3              |
|  +--------------------+  +--------------------+  +---------------+
+------------------------------------------------------------------+
|  [+ Add Office]                                                    |
+------------------------------------------------------------------+
|  OFFICES CARDS                                                    |
|  +----------------------------------------------------------+    |
|  |  📍 Kampala Office                    ✓ Active            |    |
|  |     Region: Kampala                                       |    |
|  |     Address: Plot 123, Main Street...                     |    |
|  |     Phone: +256 700 123 456                               |    |
|  |     Email: kampala@tulima.com                             |    |
|  |     [View Map]                          [Edit] [Delete]    |    |
|  +----------------------------------------------------------+    |
+------------------------------------------------------------------+
```

**Key Changes:**
- Card-based layout
- Map preview integration
- Better contact info display
- Quick actions

---

### 9. Contact Info (`admin/contact.php`)

**Current State:**
- Simple form with 4 fields
- Basic admin card

**Proposed Improvements:**
```
+------------------------------------------------------------------+
|  CONTACT INFO                                                     |
|  Manage contact information displayed across your website        |
+------------------------------------------------------------------+
|  CONTACT DETAILS                                                  |
|  +----------------------------------------------------------+    |
|  |  📞 Phone / Toll-free                                     |    |
|  |  +256 800 123 456                                         |    |
|  |  Displayed in header and footer                           |    |
|  +----------------------------------------------------------+    |
|  |  📧 Email                                                 |    |
|  |  info@tulimasolar.com                                     |    |
|  |  Primary contact email                                    |    |
|  +----------------------------------------------------------+    |
|  |  📍 Address                                               |    |
|  |  Plot 123, Main Street, Kampala, Uganda                   |    |
|  |  Displayed in footer and contact page                     |    |
|  +----------------------------------------------------------+    |
|  |  💬 WhatsApp                                              |    |
|  |  +256 700 123 456                                         |    |
|  |  Used for floating WhatsApp button                        |    |
|  +----------------------------------------------------------+    |
|  [Save Contact Info]                                               |
+------------------------------------------------------------------+
```

**Key Changes:**
- Card-based field groups
- Icons for each field type
- Helper text for where info appears
- Better visual organization

---

### 10. Legal Pages (`admin/legal_pages.php`)

**Current State:**
- Three separate cards for Privacy, Terms, Sitemap
- Basic textarea editors

**Proposed Improvements:**
```
+------------------------------------------------------------------+
|  LEGAL PAGES                                                      |
|  Manage legal content - Privacy Policy, Terms, Sitemap           |
+------------------------------------------------------------------+
|  +--------------------+  +--------------------+  +---------------+
|  | Privacy Policy     |  | Terms & Conditions |  | Sitemap        |
|  | ✓ Published        |  | ✓ Published        |  | ✓ Published    |
|  | Updated: Feb 2026  |  | Updated: Feb 2026  |  | Auto-generated |
|  +--------------------+  +--------------------+  +---------------+
+------------------------------------------------------------------+
|  TABS: [Privacy Policy] [Terms & Conditions] [Sitemap]           |
+------------------------------------------------------------------+
|  PRIVACY POLICY                                                   |
|  +----------------------------------------------------------+    |
|  | Title: [Privacy Policy                          ]         |    |
|  |                                                          |    |
|  | Content:                                                 |    |
|  | +----------------------------------------------------+   |    |
|  | |                                                    |   |    |
|  | |  Rich text editor or enhanced textarea             |   |    |
|  | |                                                    |   |    |
|  | +----------------------------------------------------+   |    |
|  |                                                          |    |
|  | [Save] [Preview on Site]                                 |    |
|  +----------------------------------------------------------+    |
+------------------------------------------------------------------+
```

**Key Changes:**
- Tab-based navigation between pages
- Status cards showing last updated
- Better editor experience
- Preview functionality

---

### 11. Site Settings (`admin/settings.php`)

**Current State:**
- Single form with all settings
- Grouped by sections

**Proposed Improvements:**
```
+------------------------------------------------------------------+
|  SITE SETTINGS                                                    |
|  Configure global settings for your website                      |
+------------------------------------------------------------------+
|  SETTINGS NAVIGATION                                              |
|  +------------------+  +------------------+  +------------------+  |
|  | 🏷️ Branding      |  | 🔗 Social Links  |  | 💬 WhatsApp     |  |
|  | Site name, tagline|  | Facebook, Twitter|  | Number, message |  |
|  +------------------+  +------------------+  +------------------+  |
+------------------------------------------------------------------+
|  BRANDING SETTINGS                                                |
|  +----------------------------------------------------------+    |
|  | Site Name                                                |    |
|  | [Tulima Solar                                    ]        |    |
|  |                                                          |    |
|  | Tagline                                                  |    |
|  | [Financed Solutions for Agriculture              ]        |    |
|  |                                                          |    |
|  | Footer Description                                       |    |
|  | [Empowering farmers with solar-powered...         ]       |    |
|  |                                                          |    |
|  | Business Hours                                           |    |
|  | [Mon - Fri: 8:00 AM - 6:00 PM                    ]        |    |
|  +----------------------------------------------------------+    |
|  [Save Settings]                                                   |
+------------------------------------------------------------------+
```

**Key Changes:**
- Tabbed/sectioned navigation
- Grouped settings cards
- Better visual organization
- Live preview where applicable

---

## Shared Components to Create

### 1. Page Header Component
```css
.page-header {
  /* Hero-style header for each page */
}
```

### 2. Stats Overview Grid
```css
.stats-overview-grid {
  /* Grid of stat cards at top of pages */
}
```

### 3. Enhanced Data Table
```css
.enhanced-data-table {
  /* Improved table with sorting, filtering */
}
```

### 4. Card Grid Layout
```css
.card-grid {
  /* Grid layout for cards (testimonials, partners) */
}
```

### 5. Form Panel
```css
.form-panel {
  /* Slide-out or modal form panel */
}
```

### 6. Filter Toolbar
```css
.filter-toolbar {
  /* Search and filter bar */
}
```

---

## Implementation Order

1. **Phase 1: Core CSS Components**
   - Add shared CSS components to admin.css
   - Create reusable BEM classes

2. **Phase 2: Content Management Pages**
   - Pages management
   - Sections management
   - Products management

3. **Phase 3: Media & Testimonials**
   - Media Library
   - Testimonials

4. **Phase 4: Organization Pages**
   - Partners
   - Impact Numbers
   - Office Locations

5. **Phase 5: Settings Pages**
   - Contact Info
   - Legal Pages
   - Site Settings

---

## Visual Consistency Guidelines

### Colors
- Use CSS variables from dashboard
- Purple (#660099) for primary actions
- Orange (#E98E39) for accents
- Green (#10B981) for success/active states

### Typography
- Inter font family
- Consistent heading hierarchy
- Proper text muted colors

### Spacing
- Use space variables (--space-4, --space-6, etc.)
- Consistent card padding
- Proper margins between sections

### Components
- Consistent border radius (--radius-lg)
- Consistent shadows (--shadow-card)
- Consistent transitions

---

## Success Criteria

1. All pages match dashboard visual quality
2. Consistent component styling across pages
3. Improved information hierarchy
4. Better usability with stats overviews
5. Mobile responsive on all pages
6. Accessible focus states and contrast

---

*Document Version: 1.0*
*Created: February 2026*
