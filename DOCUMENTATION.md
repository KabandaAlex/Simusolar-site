# Tulima Solar Website - Complete Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [System Requirements](#system-requirements)
3. [Installation Guide](#installation-guide)
4. [Architecture](#architecture)
5. [Database Schema](#database-schema)
6. [File Structure](#file-structure)
7. [Frontend Pages](#frontend-pages)
8. [Admin Panel](#admin-panel)
9. [Helper Functions](#helper-functions)
10. [Styling & Design System](#styling--design-system)
11. [Media Management](#media-management)
12. [Security Considerations](#security-considerations)
13. [Configuration](#configuration)
14. [API Reference](#api-reference)

---

## Project Overview

**Tulima Solar** is a dynamic PHP + MySQL website for a solar-powered agricultural solutions company. The website showcases products, services, and provides contact functionality with an integrated admin portal for content management.

### Key Features
- **Dynamic Content**: All content loaded from MySQL database
- **Admin Portal**: Full-featured CMS for managing pages, products, testimonials, and more
- **Responsive Design**: Mobile-first approach with modern CSS
- **Hero Video Support**: Full-screen video backgrounds with transparent navigation
- **Interactive Maps**: Office locations displayed on Leaflet/OpenStreetMap
- **Contact Form**: With math CAPTCHA spam protection
- **WhatsApp Integration**: Direct WhatsApp contact links
- **Media Library**: Centralized media management with picker interface
- **Partner Showcase**: Animated marquee for partner logos
- **Testimonials System**: Client testimonials with images

---

## System Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 7.4+ (with PDO MySQL) |
| MySQL | 5.7+ or MariaDB |
| Web Server | WAMP/XAMPP/Apache/Nginx |
| Browser | Modern browsers (Chrome, Firefox, Safari, Edge) |

### PHP Extensions Required
- PDO
- PDO_MySQL
- session
- json

---

## Installation Guide

### Step 1: Database Setup

1. Create a MySQL database (or use the install script)
2. Edit `config/database.php` with your credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tulima_solar');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### Step 2: Run Installation

Open in browser:
```
http://localhost/tulima_php/install/install.php
```

This will:
- Create the `tulima_solar` database
- Create all required tables
- Seed initial data

### Step 3: Configure Base Path

Edit `config/path.php`:
```php
// If site is at document root:
define('BASE_PATH', '');

// If site is in a subfolder:
define('BASE_PATH', '/tulima_php');
```

### Step 4: Default Admin Access

- **Username**: `admin`
- **Password**: `password`

⚠️ **Change the default password immediately after first login!**

### Step 5: Security (Production)

- Remove or restrict access to `install/` folder
- Protect `config/database.php` from public access
- Use HTTPS in production

---

## Architecture

### Application Flow

```
┌─────────────────────────────────────────────────────────────┐
│                      index.php (Front Controller)            │
│  - Loads config/database.php                                 │
│  - Loads includes/functions.php                              │
│  - Determines page from $_GET['page']                        │
│  - Fetches page data from database                           │
│  - Routes to appropriate page template                       │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Page Templates (pages/)                   │
│  - home.php, about.php, products.php, etc.                  │
│  - Includes header.php, hero.php, footer.php                │
│  - Renders sections from database                            │
└─────────────────────────────────────────────────────────────┘
```

### Admin Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    admin/auth.php                            │
│  - Session management                                        │
│  - Authentication check                                      │
│  - Password verification                                     │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    admin/*.php                                │
│  - Dashboard, Pages, Sections, Products, etc.               │
│  - CRUD operations for each content type                     │
│  - Media picker integration                                  │
└─────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### Core Tables

#### `admins`
Administrative users for the CMS.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| username | VARCHAR(64) | Unique username |
| password_hash | VARCHAR(255) | Bcrypt password hash |
| email | VARCHAR(255) | Admin email |
| created_at | TIMESTAMP | Account creation |
| updated_at | TIMESTAMP | Last update |

#### `site_settings`
Key-value store for global settings.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| key | VARCHAR(100) | Unique setting key |
| value | TEXT | Setting value |
| label | VARCHAR(255) | Human-readable label |
| updated_at | TIMESTAMP | Last update |

**Common Settings:**
- `site_name` - Website name
- `tagline` - Site tagline
- `footer_text` - Footer copyright text
- `whatsapp_number` - WhatsApp contact number
- `whatsapp_message` - Default WhatsApp message
- `business_hours` - Operating hours

#### `pages`
Page definitions with hero configuration.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| slug | VARCHAR(100) | URL slug (unique) |
| title | VARCHAR(255) | Page title |
| meta_description | TEXT | SEO meta description |
| sort_order | INT | Navigation order |
| is_active | TINYINT(1) | Visibility flag |
| hero_title | VARCHAR(255) | Hero section title |
| hero_subtitle | TEXT | Hero subtitle |
| hero_cta_text | VARCHAR(120) | Primary CTA button text |
| hero_cta_url | VARCHAR(500) | Primary CTA link |
| hero_cta_secondary_text | VARCHAR(120) | Secondary CTA text |
| hero_cta_secondary_url | VARCHAR(500) | Secondary CTA link |
| hero_video_path | VARCHAR(500) | Video background path |
| updated_at | TIMESTAMP | Last update |

#### `page_sections`
Content blocks for each page.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| page_id | INT UNSIGNED | Foreign key to pages |
| section_key | VARCHAR(100) | Unique section identifier |
| title | VARCHAR(255) | Section title |
| content | TEXT | Main content |
| content_extra | TEXT | Additional content (bullets, etc.) |
| image_path | VARCHAR(500) | Optional image |
| sort_order | INT | Display order |
| updated_at | TIMESTAMP | Last update |

#### `page_section_items`
Individual items within sections (cards, steps, etc.).

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| section_id | INT UNSIGNED | Foreign key to page_sections |
| title | VARCHAR(255) | Item title |
| content | TEXT | Item content |
| content_extra | TEXT | Additional content |
| icon | VARCHAR(100) | Icon identifier |
| image_path | VARCHAR(500) | Optional image |
| sort_order | INT | Display order |

#### `products`
Product/service listings.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| title | VARCHAR(255) | Product name |
| slug | VARCHAR(120) | URL slug (unique) |
| short_description | TEXT | Brief description |
| description | TEXT | Full description |
| image_path | VARCHAR(500) | Product image |
| sort_order | INT | Display order |
| is_active | TINYINT(1) | Visibility flag |
| created_at | TIMESTAMP | Creation date |
| updated_at | TIMESTAMP | Last update |

#### `testimonials`
Client testimonials.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| quote | TEXT | Testimonial text |
| author | VARCHAR(255) | Client name |
| role | VARCHAR(255) | Client role/title |
| image_path | VARCHAR(500) | Client photo |
| sort_order | INT | Display order |
| is_active | TINYINT(1) | Visibility flag |

#### `partners`
Partner/organization logos.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| name | VARCHAR(255) | Partner name |
| image_path | VARCHAR(500) | Logo path |
| url | VARCHAR(500) | Partner website |
| sort_order | INT | Display order |
| is_active | TINYINT(1) | Visibility flag |

#### `impact_numbers`
Statistics/impact metrics.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| label | VARCHAR(255) | Metric label |
| value | VARCHAR(100) | Metric value (e.g., "10,000+") |
| sublabel | VARCHAR(255) | Additional context |
| icon | VARCHAR(100) | Icon key (farmers, acres, income, offices) |
| sort_order | INT | Display order |
| is_active | TINYINT(1) | Visibility flag |

#### `office_locations`
Office/contact locations.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| name | VARCHAR(255) | Office name |
| region_or_city | VARCHAR(255) | Location |
| address | TEXT | Full address |
| phone | VARCHAR(100) | Phone number |
| email | VARCHAR(255) | Email address |
| map_url | VARCHAR(500) | Google Maps link |
| latitude | DECIMAL(10,8) | Map latitude |
| longitude | DECIMAL(11,8) | Map longitude |
| sort_order | INT | Display order |
| is_active | TINYINT(1) | Visibility flag |

#### `contact_info`
Contact information key-value store.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| key | VARCHAR(80) | Unique key (phone, email, address, whatsapp) |
| value | VARCHAR(500) | Contact value |
| label | VARCHAR(255) | Human-readable label |
| sort_order | INT | Display order |

#### `contact_submissions`
Contact form submissions.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| name | VARCHAR(255) | Submitter name |
| email | VARCHAR(255) | Submitter email |
| phone | VARCHAR(50) | Phone number |
| subject | VARCHAR(255) | Message subject |
| message | TEXT | Message content |
| submitted_at | TIMESTAMP | Submission time |

#### `faqs`
Frequently asked questions.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| question | VARCHAR(500) | FAQ question |
| answer | TEXT | FAQ answer |
| sort_order | INT | Display order |
| is_active | TINYINT(1) | Visibility flag |

#### `legal_pages`
Legal content pages.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| page_key | VARCHAR(50) | Page identifier (privacy, terms) |
| title | VARCHAR(255) | Page title |
| content | TEXT | HTML content |
| updated_at | TIMESTAMP | Last update |

#### `media`
Media library.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| file_path | VARCHAR(500) | File path (unique) |
| file_name | VARCHAR(255) | Original filename |
| type | ENUM | 'image' or 'video' |
| mime_type | VARCHAR(100) | MIME type |
| file_size | INT UNSIGNED | File size in bytes |
| title | VARCHAR(255) | Media title |
| alt_text | VARCHAR(500) | Alt text for accessibility |
| caption | TEXT | Caption text |
| uploaded_at | TIMESTAMP | Upload date |
| updated_at | TIMESTAMP | Last update |

---

## File Structure

```
tulima_php/
├── index.php                    # Front controller
├── README.md                    # Basic readme
├── COLORS-AND-FONTS.txt         # Design specifications
│
├── config/
│   ├── database.php             # Database configuration
│   └── path.php                 # Base path configuration
│
├── includes/
│   ├── functions.php            # Helper functions
│   ├── header.php               # Site header with navigation
│   ├── footer.php               # Site footer
│   └── hero.php                 # Hero section template
│
├── pages/
│   ├── home.php                 # Home page
│   ├── about.php                # About page
│   ├── products.php             # Products listing
│   ├── solar-pumps.php          # Solar pumps page
│   ├── contact.php              # Contact page with form
│   ├── testimonials.php         # Testimonials page
│   ├── privacy.php              # Privacy policy
│   ├── terms.php                # Terms & conditions
│   ├── sitemap.php              # Sitemap page
│   ├── generic.php              # Generic page template
│   └── 404.php                  # 404 error page
│
├── admin/
│   ├── index.php                # Admin dashboard
│   ├── auth.php                 # Authentication logic
│   ├── login.php                # Login page
│   ├── logout.php               # Logout handler
│   ├── pages.php                # Pages management
│   ├── sections.php             # Sections management
│   ├── products.php             # Products management
│   ├── testimonials.php         # Testimonials management
│   ├── partners.php             # Partners management
│   ├── impact_numbers.php       # Impact stats management
│   ├── office_locations.php     # Office locations management
│   ├── contact.php              # Contact info management
│   ├── contact_submissions.php  # View contact submissions
│   ├── settings.php             # Site settings
│   ├── legal_pages.php          # Legal pages management
│   ├── section_items.php        # Section items management
│   ├── upload.php               # Upload handler
│   ├── assets/
│   │   └── css/                 # Admin-specific styles
│   ├── includes/
│   │   ├── header.php           # Admin header
│   │   ├── footer.php           # Admin footer
│   │   └── upload_handler.php   # Upload processing
│   └── media/
│       ├── index.php            # Media library
│       ├── edit.php             # Media editor
│       └── picker.php           # Media picker modal
│
├── static/
│   ├── css/
│   │   └── style.css            # Main stylesheet
│   ├── images/                  # Static images (logos, patterns)
│   ├── js/                      # JavaScript files
│   └── videos/                  # Static videos
│
├── media/
│   ├── blog_images/             # Blog post images
│   ├── hero_videos/             # Hero video files
│   ├── job_applications/        # Job application files
│   ├── partners/                # Partner logos
│   ├── products/                # Product images
│   ├── team_members/            # Team member photos
│   └── testimonials/            # Testimonial photos
│
├── uploads/
│   ├── images/                  # Uploaded images
│   └── videos/                  # Uploaded videos
│
├── database/
│   ├── schema.sql               # Base database schema
│   ├── seed.sql                 # Initial data
│   ├── seed_home_tulima.sql     # Home page content
│   ├── seed_impact_partners_offices.sql
│   ├── seed_testimonials.sql
│   └── migration_*.sql          # Database migrations
│
└── install/
    └── install.php              # Installation script
```

---

## Frontend Pages

### Home Page (`pages/home.php`)

The home page is the most complex, featuring multiple sections:

**Section Keys:**
- `featured_products` - Product showcase (3 products)
- `transform_farm` - Benefits/features grid
- `how_it_works` - Step-by-step process
- `impact_numbers` - Statistics with count-up animation
- `solar_solutions` - Solution cards
- `applications` - Use case cards
- `testimonials` - Client testimonials
- `partners` - Partner logo marquee
- `office_locations` - Office map and list
- `cta_final` - Final call-to-action

**Features:**
- Hero video support (plays full-screen with transparent navbar)
- Animated partner logo marquee
- Interactive office map with Leaflet
- Count-up animation for impact numbers

### About Page (`pages/about.php`)

Simple page with:
- `about_intro` - Introduction section
- `mission` - Mission statement
- CTA section

### Products Page (`pages/products.php`)

Displays all active products from the `products` table:
- Product cards with images
- Short descriptions
- Links to details

### Solar Pumps Page (`pages/solar-pumps.php`)

Comprehensive page with multiple sections:
- Solar pump information
- Applications (animal keeping, fish farming, water selling)
- Components and considerations
- Surface vs submersible pumps
- Financing options

### Contact Page (`pages/contact.php`)

**Features:**
- Interactive map with office locations
- Contact form with math CAPTCHA
- Office location cards
- FAQ accordion
- WhatsApp integration
- Business hours display

**Form Fields:**
- Full Name (required)
- Email (required)
- Phone (optional)
- Subject (required)
- Message (required)
- Math CAPTCHA (required)

### Testimonials Page (`pages/testimonials.php`)

Displays all testimonials from the `testimonials` table:
- Quote cards with star ratings
- Client photos
- Author names and roles

### Legal Pages (`pages/privacy.php`, `pages/terms.php`)

Content loaded from `legal_pages` table:
- Sidebar with contact CTA
- Styled content blocks

### Sitemap Page (`pages/sitemap.php`)

Auto-generated sitemap with:
- Navigation links
- Product links
- Legal page links

---

## Admin Panel

### Dashboard (`admin/index.php`)

Statistics overview:
- Pages count
- Sections count
- Products count
- Media count
- Testimonials count

Quick links to all admin sections.

### Pages Management (`admin/pages.php`)

Edit page properties:
- Title and meta description
- Sort order and active status
- Hero configuration:
  - Hero title and subtitle
  - Primary CTA (text + URL)
  - Secondary CTA (text + URL)
  - Hero video path

### Sections Management (`admin/sections.php`)

Manage content blocks:
- Filter by page
- Edit title, content, content_extra
- Upload images
- Manage section items (cards, steps)

### Products Management (`admin/products.php`)

Full CRUD for products:
- Title and slug
- Short and full descriptions
- Product image
- Sort order and active status

### Testimonials Management (`admin/testimonials.php`)

Manage client testimonials:
- Quote text
- Author name and role
- Client photo

### Partners Management (`admin/partners.php`)

Manage partner logos:
- Partner name
- Logo image
- Website URL

### Impact Numbers (`admin/impact_numbers.php`)

Manage statistics:
- Label and value
- Sublabel
- Icon selection (farmers, acres, income, offices)

### Office Locations (`admin/office_locations.php`)

Manage office locations:
- Name and region
- Address, phone, email
- Map URL
- Latitude and longitude for map display

### Contact Info (`admin/contact.php`)

Manage contact details:
- Phone
- Email
- Address
- WhatsApp

### Contact Submissions (`admin/contact_submissions.php`)

View contact form submissions:
- Submission list
- Message details
- Timestamp

### Settings (`admin/settings.php`)

Global site settings:
- Site name
- Tagline
- Footer text
- WhatsApp number and message
- Business hours

### Legal Pages (`admin/legal_pages.php`)

Manage legal content:
- Privacy Policy
- Terms & Conditions
- HTML content editor

### Media Library (`admin/media/index.php`)

Centralized media management:
- Upload images and videos
- Edit metadata (title, alt text, caption)
- Delete media
- Media picker for use in other admin pages

---

## Helper Functions

### Core Functions (`includes/functions.php`)

#### Database & Settings

```php
function getDb(): PDO
```
Returns PDO database connection singleton.

```php
function getSetting(PDO $pdo, string $key): ?string
```
Returns site setting value by key.

#### Pages & Sections

```php
function getPageBySlug(PDO $pdo, string $slug): ?array
```
Fetches page data by URL slug.

```php
function getPageSections(PDO $pdo, int $pageId): array
```
Returns all sections for a page.

```php
function getSectionByKey(PDO $pdo, int $pageId, string $key): ?array
```
Fetches specific section by key.

```php
function getSectionItems(PDO $pdo, int $sectionId): array
```
Returns items within a section.

#### Content Functions

```php
function getProducts(PDO $pdo): array
```
Returns all active products.

```php
function getTestimonials(PDO $pdo, ?int $limit = null): array
```
Returns testimonials (optionally limited).

```php
function getImpactNumbers(PDO $pdo): array
```
Returns impact statistics.

```php
function getPartners(PDO $pdo): array
```
Returns partner logos.

```php
function getOfficeLocations(PDO $pdo): array
```
Returns office locations.

```php
function getContactInfo(PDO $pdo): array
```
Returns contact information.

```php
function getFaqs(PDO $pdo): array
```
Returns FAQs for accordion.

```php
function getLegalPage(PDO $pdo, string $key): ?array
```
Returns legal page content.

#### Content Parsing

```php
function sectionContentToBullets(string $content): array
```
Parses content into bullet points.

```php
function sectionBlocks(string $contentExtra, bool $splitPipes = true): array
```
Parses content_extra into blocks separated by "---".

```php
function sectionImpactLines(string $contentExtra): array
```
Parses impact stat lines (Label|Value|Sublabel).

```php
function nlToParagraphs(string $text): string
```
Converts newlines to paragraph tags.

#### Utility Functions

```php
function e(string $s): string
```
HTML escape helper (htmlspecialchars wrapper).

```php
function upload_url(string $path): string
```
Generates full URL for uploaded files.

```php
function parse_impact_value(string $value): array
```
Extracts numeric value and suffix for count-up animation.

```php
function impact_icon_svg(string $icon): string
```
Returns SVG markup for impact stat icons.

#### Contact Functions

```php
function saveContactSubmission(PDO $pdo, array $data): bool
```
Saves contact form submission to database.

```php
function getContactSubmissions(PDO $pdo): array
```
Returns all contact submissions for admin.

#### Media Functions

```php
function get_media_by_path(PDO $pdo, string $path): ?array
```
Returns media metadata by file path.

---

## Styling & Design System

### CSS Variables (`static/css/style.css`)

#### Brand Colors
```css
--primary-purple: #660099;
--secondary-green: #B0D7AB;
--accent-orange: #E98E39;
--dark-purple: #4A0066;
--light-purple: #F0E6F7;
--tulima-darker: #2d1b4e;
--dark-green: #8BC4A0;
--light-green: #E8F5E8;
--dark-orange: #D17A1F;
--light-orange: #FDF4E8;
```

#### Neutrals
```css
--text-primary: #2C2C2C;
--text-secondary: #4A4A4A;
--text-muted: #6B6B6B;
--white: #FFFFFF;
--black: #000000;
--dark-gray: #4A4A4A;
--light-gray: #f8f9fa;
```

#### Gradients
```css
--gradient-primary: linear-gradient(135deg, var(--primary-purple), var(--dark-purple));
--gradient-secondary: linear-gradient(135deg, var(--primary-purple), var(--secondary-green));
--gradient-accent: linear-gradient(135deg, var(--accent-orange), var(--dark-orange));
--gradient-hero: linear-gradient(135deg, var(--primary-purple) 0%, var(--dark-green) 50%, var(--accent-orange) 100%);
--gradient-section-title: linear-gradient(90deg, var(--accent-orange) 0%, var(--primary-purple) 50%, var(--dark-purple) 100%);
```

#### Typography
```css
--font-header: Calibri, 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
--font-body: Calibri, 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
```

#### Spacing & Borders
```css
--radius-sm: 8px;
--radius-md: 12px;
--radius-lg: 16px;
--radius-xl: 20px;
```

### Component Classes

#### Layout
- `.container` - Max-width 1400px, centered
- `.section` - Padding 4.5rem vertical
- `.section--gray` - Gray gradient background
- `.section--purple` - Purple gradient background
- `.section--white` - White background
- `.section--green` - Light green background

#### Typography
- `.section-title` - Gradient text, large heading
- `.section-subtitle` - Muted subtitle text
- `.lead` - Larger intro text

#### Cards
- `.card` - Base card component
- `.card--product` - Product card variant
- `.card--feature` - Feature card variant
- `.card-grid` - Responsive grid layout
- `.card-grid--three` - 3-column grid
- `.card-grid--four` - 4-column grid

#### Buttons
- `.btn` - Base button
- `.btn-primary` - Orange gradient button
- `.btn-outline` - Purple outline button
- `.btn-outline-light` - White outline button
- `.btn--sm` - Small button size

#### Hero
- `.hero` - Hero section
- `.hero--video` - Video hero variant
- `.hero-title` - Large hero heading
- `.hero-subtitle` - Hero subtitle

#### Navigation
- `.navbar` - Sticky navigation
- `.navbar--transparent` - Transparent for video hero
- `.navbar-nav` - Navigation list

#### Special Components
- `.impact-cards` - Statistics grid
- `.impact-card` - Individual stat card
- `.testimonials-grid` - Testimonials layout
- `.testimonial-card` - Individual testimonial
- `.partners-marquee` - Animated partner logos
- `.office-map` - Leaflet map container

### Responsive Breakpoints

```css
@media (max-width: 768px) { /* Tablet and mobile */ }
@media (max-width: 480px) { /* Mobile only */ }
```

---

## Media Management

### Upload Locations

| Directory | Purpose |
|-----------|---------|
| `uploads/images/` | General uploaded images |
| `uploads/videos/` | Uploaded videos |
| `media/products/` | Product images |
| `media/partners/` | Partner logos |
| `media/team_members/` | Team photos |
| `media/testimonials/` | Client photos |
| `media/hero_videos/` | Hero video files |

### Media Picker

The media picker (`admin/media/picker.php`) provides:
- File selection from media library
- Upload new files
- Filter by type (image/video)
- Preview thumbnails

Usage in admin forms:
```javascript
openMediaPicker('input_id', 'image') // For images
openMediaPicker('input_id', 'video') // For videos
```

### Supported File Types

**Images:** JPG, JPEG, PNG, GIF, WebP
**Videos:** MP4, WebM, MOV

---

## Security Considerations

### Authentication

- Password hashing with `password_hash()` (Bcrypt)
- Session-based authentication
- `requireAdmin()` function protects admin pages

### Form Protection

- Math CAPTCHA on contact form
- One-time CAPTCHA use (regenerated after submission)
- Input sanitization with `trim()` and `htmlspecialchars()`

### Database Security

- PDO prepared statements (SQL injection prevention)
- Parameterized queries throughout

### File Upload Security

- File type validation
- Size limits
- Secure storage outside web root (recommended)

### Production Recommendations

1. **Remove install folder** after installation
2. **Change default admin password**
3. **Enable HTTPS**
4. **Set secure session cookies**
5. **Implement rate limiting** on forms
6. **Regular backups**
7. **Keep PHP/MySQL updated**

---

## Configuration

### Database Configuration (`config/database.php`)

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tulima_solar');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
```

### Path Configuration (`config/path.php`)

```php
define('BASE_PATH', '/tulima_php'); // Subfolder path
define('UPLOAD_DIR', 'uploads');    // Upload directory
```

---

## API Reference

### URL Parameters

| Parameter | Values | Description |
|-----------|--------|-------------|
| `page` | home, about, products, solar-pumps, contact, testimonials, privacy, terms, sitemap | Page to display |
| `sent` | 1 | Contact form success flag |

### Session Variables

| Variable | Description |
|----------|-------------|
| `admin_id` | Logged-in admin user ID |
| `admin_user` | Admin username |
| `contact_captcha` | Math CAPTCHA values [n1, n2, sum] |

### JavaScript Globals

| Variable | Description |
|----------|-------------|
| `TULIMA_OFFICES` | Office locations for home page map |
| `TULIMA_OFFICES_CONTACT` | Office locations for contact page map |

---

## Troubleshooting

### Common Issues

**Database Connection Error**
- Verify database credentials in `config/database.php`
- Ensure MySQL service is running
- Check database exists

**404 Errors**
- Verify `BASE_PATH` in `config/path.php`
- Check `.htaccess` configuration (if using URL rewriting)
- Ensure page exists in database with `is_active = 1`

**Hero Video Not Playing**
- Check video path in page settings
- Ensure video file exists
- Verify video format (MP4 recommended)

**Map Not Displaying**
- Verify office locations have latitude/longitude
- Check internet connection (loads Leaflet from CDN)

**Admin Login Issues**
- Verify admin user exists in database
- Reset password directly in database:
  ```sql
  UPDATE admins SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';
  -- This sets password to 'password'
  ```

---

## Changelog

### Version History

| Version | Changes |
|---------|---------|
| 1.0 | Initial release with core functionality |
| 1.1 | Added hero video support |
| 1.2 | Added testimonials system |
| 1.3 | Added partners marquee |
| 1.4 | Added office locations with map |
| 1.5 | Added FAQ accordion |
| 1.6 | Added media library |
| 1.7 | Added impact numbers |
| 1.8 | Added legal pages management |

---

## Support

For issues or questions:
- Check this documentation
- Review database schema in `database/schema.sql`
- Examine page templates in `pages/`
- Check admin functionality in `admin/`

---

*Documentation generated for Tulima Solar Website - PHP + MySQL Dynamic Website*