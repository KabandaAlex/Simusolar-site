# Tulima Solar - Blog Management System Documentation

## Table of Contents
1. [Overview](#overview)
2. [Database Schema](#database-schema)
3. [Admin Portal Guide](#admin-portal-guide)
4. [Public Website Features](#public-website-features)
5. [API Reference](#api-reference)
6. [Security](#security)
7. [Configuration](#configuration)
8. [Developer Notes](#developer-notes)
9. [Troubleshooting](#troubleshooting)

---

## Overview

### System Description
The Blog Management System is a full-featured Content Management System (CMS) integrated into the Tulima Solar website. It provides comprehensive tools for content creation, organization, and engagement tracking.

### Features Summary

| Feature | Description |
|---------|-------------|
| **Post Management** | Create, edit, and delete blog posts with rich text editing |
| **Categories** | Hierarchical category structure for content organization |
| **Tags** | Flexible tagging system for cross-referencing content |
| **Comments** | Threaded comment system with moderation tools |
| **Likes** | User engagement through post likes/reactions |
| **SEO Tools** | Meta titles, descriptions, and Open Graph support |
| **Scheduling** | Schedule posts for future publication |
| **Media Integration** | Featured images with alt text support |
| **Analytics** | View counts, like counts, and engagement metrics |
| **RBAC** | Role-based access control for team collaboration |

### Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        BLOG SYSTEM ARCHITECTURE                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐     ┌──────────────┐     ┌──────────────┐     │
│  │   Public     │     │    Admin     │     │     API      │     │
│  │   Website    │     │    Portal    │     │   Endpoints  │     │
│  │              │     │              │     │              │     │
│  │ blog/        │     │ admin/       │     │ blog/api.php │     │
│  │ index.php    │     │ blog_*.php   │     │              │     │
│  │ post.php     │     │              │     │              │     │
│  └──────┬───────┘     └──────┬───────┘     └──────┬───────┘     │
│         │                    │                    │              │
│         └────────────────────┼────────────────────┘              │
│                              │                                   │
│                    ┌─────────▼─────────┐                        │
│                    │   Helper Files    │                        │
│                    │                   │                        │
│                    │ blog_helpers.php  │                        │
│                    │ auth_helpers.php  │                        │
│                    └─────────┬─────────┘                        │
│                              │                                   │
│                    ┌─────────▼─────────┐                        │
│                    │     Database      │                        │
│                    │   MySQL/MariaDB   │                        │
│                    │                   │                        │
│                    │ blog_posts        │                        │
│                    │ blog_categories   │                        │
│                    │ blog_tags         │                        │
│                    │ blog_comments     │                        │
│                    │ blog_post_likes   │                        │
│                    └───────────────────┘                        │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### Table Descriptions

#### blog_posts
Main content table storing all blog post data.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT UNSIGNED | Primary key, auto-increment |
| `title` | VARCHAR(255) | Post title |
| `slug` | VARCHAR(200) | URL-friendly identifier, unique |
| `content` | LONGTEXT | Full post content with HTML |
| `excerpt` | TEXT | Short summary for listings and SEO |
| `category_id` | INT UNSIGNED | Foreign key to blog_categories |
| `meta_title` | VARCHAR(255) | SEO title override |
| `meta_description` | VARCHAR(500) | SEO meta description |
| `canonical_url` | VARCHAR(500) | Canonical URL for duplicate content |
| `featured_image_path` | VARCHAR(500) | Path to featured image |
| `featured_image_alt` | VARCHAR(255) | Alt text for featured image |
| `status` | ENUM | draft, published, scheduled, archived |
| `publish_at` | TIMESTAMP | Scheduled publish datetime |
| `published_at` | TIMESTAMP | Actual publish datetime |
| `view_count` | INT UNSIGNED | Denormalized view counter |
| `like_count` | INT UNSIGNED | Denormalized like counter |
| `comment_count` | INT UNSIGNED | Denormalized approved comment counter |
| `created_by` | INT UNSIGNED | Foreign key to admins |
| `updated_by` | INT UNSIGNED | Foreign key to admins |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |
| `deleted_at` | TIMESTAMP | Soft delete timestamp |

#### blog_categories
Hierarchical categories for organizing posts.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT UNSIGNED | Primary key, auto-increment |
| `name` | VARCHAR(100) | Category name |
| `slug` | VARCHAR(100) | URL-friendly identifier, unique |
| `description` | TEXT | Category description |
| `image_path` | VARCHAR(500) | Optional category image |
| `parent_id` | INT UNSIGNED | Self-referencing for hierarchy |
| `sort_order` | INT | Display order |
| `is_active` | TINYINT(1) | Active status flag |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

#### blog_tags
Simple tag table for flexible content organization.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT UNSIGNED | Primary key, auto-increment |
| `name` | VARCHAR(50) | Tag name, unique |
| `slug` | VARCHAR(50) | URL-friendly identifier, unique |
| `description` | VARCHAR(255) | Optional tag description |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

#### blog_post_tags
Junction table for many-to-many post-tag relationship.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT UNSIGNED | Primary key, auto-increment |
| `post_id` | INT UNSIGNED | Foreign key to blog_posts |
| `tag_id` | INT UNSIGNED | Foreign key to blog_tags |
| `created_at` | TIMESTAMP | Creation timestamp |

#### blog_comments
Threaded comments with guest support.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT UNSIGNED | Primary key, auto-increment |
| `post_id` | INT UNSIGNED | Foreign key to blog_posts |
| `parent_id` | INT UNSIGNED | Self-referencing for threading |
| `user_id` | INT UNSIGNED | Foreign key to admins (nullable) |
| `author_name` | VARCHAR(100) | Guest commenter name |
| `author_email` | VARCHAR(255) | Guest commenter email |
| `author_url` | VARCHAR(500) | Guest commenter website |
| `content` | TEXT | Comment content |
| `status` | ENUM | pending, approved, spam, trash |
| `ip_address` | VARCHAR(45) | Commenter IP address |
| `user_agent` | TEXT | Browser user agent |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

#### blog_post_likes
User-to-post like relationship.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT UNSIGNED | Primary key, auto-increment |
| `post_id` | INT UNSIGNED | Foreign key to blog_posts |
| `user_id` | INT UNSIGNED | Foreign key to admins |
| `reaction_type` | VARCHAR(20) | Reaction type (default: like) |
| `created_at` | TIMESTAMP | Creation timestamp |

### Relationships Diagram

```
┌─────────────────┐       ┌─────────────────┐
│  blog_categories│       │     admins      │
│                 │       │                 │
│ id              │◄──┐   │ id              │
│ name            │   │   │ username        │
│ slug            │   │   │ email           │
│ parent_id       │───┘   │ role            │
└────────┬────────┘       └────────┬────────┘
         │                         │
         │ category_id             │ created_by/updated_by
         │                         │
         ▼                         ▼
┌─────────────────────────────────────────────┐
│                 blog_posts                   │
│                                              │
│ id  title  slug  content  excerpt  status   │
│ view_count  like_count  comment_count       │
└────────┬───────────────────────┬────────────┘
         │                       │
    ┌────┴────┐             ┌────┴────┐
    │         │             │         │
    ▼         ▼             ▼         ▼
┌───────┐ ┌───────┐   ┌──────────┐ ┌────────────┐
│ likes │ │comments│   │post_tags │ │   tags     │
│       │ │        │   │          │ │            │
│post_id│ │post_id │   │post_id   │ │id          │
│user_id│ │parent_id│  │tag_id    │ │name        │
└───────┘ │user_id │   └──────────┘ │slug        │
          │content │         │      └────────────┘
          │status  │         │             │
          └────────┘         └─────────────┘
```

### Indexes and Performance Considerations

The schema includes several performance optimizations:

#### Single-Column Indexes
- `idx_blog_posts_slug` - Fast slug lookups
- `idx_blog_posts_status` - Filter by status
- `idx_blog_posts_category` - Filter by category
- `idx_blog_posts_created_at` - Date-based ordering

#### Composite Indexes
- `idx_blog_posts_status_published (status, published_at)` - Published posts listing
- `idx_blog_posts_status_category (status, category_id)` - Category filtering
- `idx_blog_posts_category_status_date (category_id, status, published_at)` - Combined queries

#### Full-Text Search
- `ft_blog_posts_search (title, content, excerpt)` - Full-text search capability

#### Automatic Counter Updates
Database triggers automatically maintain denormalized counters:
- `trg_blog_comment_count_insert/update/delete` - Updates `comment_count`
- `trg_blog_like_count_insert/delete` - Updates `like_count`

---

## Admin Portal Guide

### Accessing the Admin Panel

1. Navigate to `/admin/` in your browser
2. Log in with your admin credentials
3. Access blog features from the sidebar under "Blog" section

### Managing Categories

**Access:** `admin/blog_categories.php`

Categories support hierarchical structure (parent-child relationships).

#### Creating a Category
1. Click "Add Category" button
2. Fill in the required fields:
   - **Name** (required): Display name
   - **Slug** (auto-generated or custom): URL identifier
   - **Description**: Optional category description
   - **Parent Category**: Select for subcategories
   - **Sort Order**: Display order (lower = earlier)
   - **Status**: Active/Inactive

#### Category Hierarchy Example
```
Solar Energy (parent)
├── Solar Panels (child)
└── Solar Pumps (child)

Agriculture (parent)
├── Irrigation (child)
└── Equipment (child)
```

### Managing Tags

**Access:** `admin/blog_tags.php`

Tags provide flexible, non-hierarchical content organization.

#### Creating a Tag
1. Click "Add Tag" button
2. Enter:
   - **Name** (required): Tag name
   - **Slug** (auto-generated): URL identifier
   - **Description**: Optional tag description

#### Best Practices
- Use consistent naming conventions
- Avoid duplicate tags with similar meanings
- Keep tags short and descriptive

### Creating and Editing Posts

**Access:** `admin/blog_post_edit.php` (New Post)
**Access:** `admin/blog_post_edit.php?id=X` (Edit Post)

#### TinyMCE Rich Text Editor

The post editor uses TinyMCE for rich content creation:

| Feature | Description |
|---------|-------------|
| **Formatting** | Bold, italic, underline, strikethrough |
| **Headings** | H1-H6 heading levels |
| **Lists** | Ordered and unordered lists |
| **Links** | Internal and external links |
| **Images** | Insert and align images |
| **Tables** | Create data tables |
| **Code** | Code blocks and inline code |
| **Embed** | Video and media embeds |

#### Post Fields

**Content Section:**
| Field | Required | Max Length | Description |
|-------|----------|------------|-------------|
| Title | Yes | 255 | Post headline |
| Slug | Yes | 200 | URL identifier (auto-generated) |
| Content | Yes | - | Full post body |
| Excerpt | No | 500 | Summary for listings |
| Category | No | - | Primary category |
| Tags | No | - | Multiple tags selection |

**SEO Section:**
| Field | Max Length | Description |
|-------|------------|-------------|
| Meta Title | 255 | SEO title (defaults to post title) |
| Meta Description | 500 | Search result snippet |
| Meta Keywords | - | Comma-separated keywords |

**Featured Image Section:**
| Field | Description |
|-------|-------------|
| Featured Image | Main post image (via media picker) |
| Alt Text | Accessibility description |

#### Post Workflow States

```
┌─────────┐  Publish   ┌───────────┐
│  DRAFT  │───────────▶│ PUBLISHED │
└────┬────┘            └─────┬─────┘
     │                       │
     │  Schedule             │ Archive
     │                       │
     ▼                       ▼
┌──────────┐           ┌──────────┐
│SCHEDULED │           │ ARCHIVED │
└──────────┘           └──────────┘
     │                       │
     │ Due time reached      │
     │                       │
     ▼                       │
┌───────────┐                │
│ PUBLISHED │◀───────────────┘
└───────────┘
```

| Status | Description | Visibility |
|--------|-------------|------------|
| **Draft** | Work in progress | Admin only |
| **Published** | Live and visible | Public |
| **Scheduled** | Auto-publish at set time | Admin until publish time |
| **Archived** | Removed from public view | Admin only |

### Comment Moderation

**Access:** `admin/blog_comments.php`

#### Comment Statuses
| Status | Description |
|--------|-------------|
| **Pending** | Awaiting moderation |
| **Approved** | Visible on post |
| **Spam** | Marked as spam |
| **Trash** | Soft deleted |

#### Moderation Actions
- **Approve**: Make comment visible
- **Reject/Spam**: Mark as spam
- **Reply**: Respond to comment
- **Delete**: Move to trash
- **Bulk Actions**: Process multiple comments

### Engagement Metrics Dashboard

**Access:** `admin/blog_engagement.php`

View comprehensive analytics:

| Metric | Description |
|--------|-------------|
| **Total Views** | Aggregate post views |
| **Likes** | User reactions count |
| **Comments** | Approved comments count |
| **Top Posts** | Most viewed content |
| **Recent Activity** | Latest engagement trends |

---

## Public Website Features

### Blog Listing Page

**URL:** `/blog/index.php`

#### Features
- Paginated post listing (10 posts per page)
- Category filtering
- Tag filtering
- Archive browsing (by month)
- Search functionality
- Sort options (newest, most popular, most commented)

#### Sidebar Components
- Categories with post counts
- Popular tags cloud
- Recent posts
- Monthly archives

#### URL Parameters
| Parameter | Example | Description |
|-----------|---------|-------------|
| `category` | `?category=solar-energy` | Filter by category slug |
| `tag` | `?tag=tips` | Filter by tag slug |
| `archive` | `?archive=2026-01` | Filter by month (YYYY-MM) |
| `search` | `?search=solar` | Search term |
| `sort` | `?sort=popular` | Sort order |
| `page` | `?page=2` | Pagination |

### Individual Post Pages

**URL:** `/blog/post.php?slug=post-slug`

#### SEO Features
- Semantic HTML structure
- Meta title and description
- Open Graph tags for social sharing
- Canonical URL support
- Schema.org structured data

#### Open Graph Integration
```html
<meta property="og:title" content="Post Title" />
<meta property="og:description" content="Post excerpt..." />
<meta property="og:image" content="/media/blog_images/image.jpg" />
<meta property="og:url" content="https://example.com/blog/post.php?slug=..." />
<meta property="og:type" content="article" />
```

### Like System

Users can like/unlike posts:
- Requires login (admin session)
- One like per user per post
- Toggle on/off functionality
- Real-time count updates via AJAX

### Comment System

#### Features
- Threaded (nested) comments
- Guest commenting support
- Auto-moderation (comments pending by default)
- Reply functionality
- Content sanitization

#### Comment Form Fields
| Field | Required | For |
|-------|----------|-----|
| Name | Yes | Guests |
| Email | Yes | Guests |
| Website | No | Guests |
| Content | Yes | All |

---

## API Reference

### Base URL
```
/blog/api.php
```

### Authentication
- All POST requests require CSRF token
- Like endpoint requires admin session
- Comments support guest submission

### Endpoints

#### Like Toggle
Toggle like status for a post.

**Endpoint:** `POST ?action=like`

**Request Body:**
```javascript
{
    "action": "like",
    "post_id": 123,
    "csrf_token": "token_here"
}
```

**Success Response:**
```javascript
{
    "success": true,
    "liked": true,
    "like_count": 42,
    "message": "Post liked!"
}
```

**Error Response:**
```javascript
{
    "success": false,
    "message": "You must be logged in to like this post."
}
```

#### Submit Comment
Submit a new comment or reply.

**Endpoint:** `POST ?action=comment`

**Request Body:**
```javascript
{
    "action": "comment",
    "post_id": 123,
    "parent_id": null,  // For replies, include parent comment ID
    "content": "Great article!",
    "guest_name": "John Doe",  // Required for guests
    "guest_email": "john@example.com",  // Required for guests
    "csrf_token": "token_here"
}
```

**Success Response:**
```javascript
{
    "success": true,
    "comment_id": 456,
    "message": "Your comment has been submitted and is awaiting moderation.",
    "moderation": true
}
```

#### Get Comments
Retrieve comments for a post.

**Endpoint:** `GET ?action=comments&post_id=123`

**Response:**
```javascript
{
    "success": true,
    "comments": [
        {
            "id": 1,
            "author_name": "John",
            "content": "Great post!",
            "created_at": "2026-02-23 10:00:00",
            "replies": [
                {
                    "id": 2,
                    "author_name": "Admin",
                    "content": "Thanks!",
                    "created_at": "2026-02-23 11:00:00"
                }
            ]
        }
    ],
    "total": 5
}
```

---

## Security

### RBAC Roles and Permissions

#### Role Hierarchy
```
Admin (100)
    └── Editor (50)
            └── Author (10)
```

#### Permission Matrix

| Permission | Admin | Editor | Author |
|------------|:-----:|:------:|:------:|
| blog_manage_categories | ✓ | ✓ | ✗ |
| blog_manage_tags | ✓ | ✓ | ✗ |
| blog_manage_posts | ✓ | ✓ | ✓ |
| blog_edit_any_post | ✓ | ✓ | ✗ |
| blog_edit_own_post | ✓ | ✓ | ✓ |
| blog_manage_comments | ✓ | ✓ | ✗ |
| blog_view_engagement | ✓ | ✓ | ✓ |
| blog_delete_content | ✓ | ✓ | ✗ |
| user_manage | ✓ | ✗ | ✗ |
| settings_manage | ✓ | ✗ | ✗ |

### Input Validation

All user inputs are validated using functions in [`includes/blog_helpers.php`](includes/blog_helpers.php):

```php
// Title validation
validatePostTitle($title);  // 3-255 chars, required

// Slug validation
validateSlug($slug, $excludePostId);  // URL-safe, unique

// Content validation
validatePostContent($content, $allowHtml);  // Min 10 chars

// Excerpt validation
validateExcerpt($excerpt);  // Max 500 chars
```

### XSS Prevention

1. **HTML Sanitization**: Using `sanitizeHtml()` function
2. **Output Escaping**: `htmlspecialchars()` on all outputs
3. **Content Security Policy**: Headers configured
4. **Allowed HTML Tags**: Limited set for comments

```php
// Allowed tags in comments
$allowedTags = '<p><br><strong><b><em><i><u><a>';
```

### CSRF Protection

All forms include CSRF tokens:

```php
// Generate token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Validate token
if (!hash_equals($_SESSION['csrf_token'], $token)) {
    // Reject request
}
```

---

## Configuration

### Required Database Migrations

Run migrations in order:

```bash
# 1. Main blog tables
mysql -u root -p tulima_solar < database/migration_blog_system.sql

# 2. RBAC documentation (reference only)
# migration_blog_rbac.sql is documentation-only
```

### Role Setup for Admin Users

Update admin user roles in the database:

```sql
-- Set user as admin (full access)
UPDATE admins SET role = 'admin' WHERE id = 1;

-- Set user as editor (content manager)
UPDATE admins SET role = 'editor' WHERE id = 2;

-- Set user as author (content creator)
UPDATE admins SET role = 'author' WHERE id = 3;
```

### Media Upload Paths

| Directory | Purpose |
|-----------|---------|
| `/media/blog_images/` | Blog post featured images |
| `/media/uploads/` | General uploads |

Ensure directories are writable:
```bash
chmod 755 media/blog_images
chmod 755 media/uploads
```

---

## Developer Notes

### File Structure

```
tulima_php/
├── admin/
│   ├── blog_categories.php    # Category management
│   ├── blog_tags.php          # Tag management
│   ├── blog_posts.php         # Post listing
│   ├── blog_post_edit.php     # Post editor
│   ├── blog_comments.php      # Comment moderation
│   ├── blog_engagement.php    # Analytics dashboard
│   └── includes/
│       └── auth_helpers.php   # RBAC functions
├── blog/
│   ├── index.php              # Public listing
│   ├── post.php               # Single post view
│   └── api.php                # AJAX endpoints
├── includes/
│   └── blog_helpers.php       # Validation functions
├── database/
│   ├── migration_blog_system.sql
│   └── migration_blog_rbac.sql
└── media/
    └── blog_images/           # Upload directory
```

### Helper Functions Available

#### Auth Helpers (`admin/includes/auth_helpers.php`)

```php
// Role checking
getCurrentAdminRole(): ?string
hasRole(string $role): bool
hasPermission(string $permission): bool

// Role shortcuts
isBlogAdmin(): bool
isEditor(): bool
isAuthor(): bool

// Access control
requirePermission(string $permission): void
requireRole(string $role): void
requireCategoryAccess(): void
requireTagAccess(): void
requirePostAccess(): void
requirePostEditAccess(int $postId): void
requireCommentAccess(): void

// Permission checks
canManageCategories(): bool
canManageTags(): bool
canEditAnyPost(): bool
canEditPost(int $postId): bool
canManageComments(): bool
canViewEngagement(): bool
```

#### Blog Helpers (`includes/blog_helpers.php`)

```php
// Validation functions
validatePostTitle(?string $title): array
validateSlug(?string $slug, ?int $excludePostId): array
validatePostContent(?string $content, bool $allowHtml): array
validateExcerpt(?string $excerpt): array

// Utility functions
generateSlugFromTitle(string $title): string
sanitizeHtml(string $content): string
```

### Extending the System

#### Adding a New Post Status

1. Update database ENUM:
```sql
ALTER TABLE blog_posts 
MODIFY COLUMN status ENUM('draft', 'published', 'scheduled', 'archived', 'review') 
DEFAULT 'draft';
```

2. Update admin UI in `blog_post_edit.php`
3. Update public queries in `blog/index.php` and `blog/post.php`

#### Adding New Permissions

1. Define in `auth_helpers.php`:
```php
const ROLE_PERMISSIONS = [
    'admin' => [
        // ... existing permissions
        'new_permission',
    ],
    // ...
];
```

2. Create helper function:
```php
function canUseNewFeature(): bool {
    return hasPermission('new_permission');
}
```

---

## Troubleshooting

### Common Issues and Solutions

#### Posts Not Appearing Publicly

**Symptoms:** Published posts don't show on public blog

**Solutions:**
1. Check post status is "published" not "draft"
2. Verify `published_at` is set and in the past
3. Ensure `deleted_at` is NULL
4. Check category is active

```sql
-- Debug query
SELECT id, title, status, published_at, deleted_at 
FROM blog_posts 
WHERE status = 'published';
```

#### Scheduled Posts Not Publishing

**Symptoms:** Scheduled posts remain in scheduled status

**Solutions:**
1. Scheduled posts are auto-published when `blog_posts.php` is loaded
2. For automatic publishing, set up a cron job:

```bash
# Run every 5 minutes
*/5 * * * * curl -s https://yoursite.com/admin/blog_posts.php > /dev/null
```

Or create a dedicated cron endpoint.

#### Comments Not Appearing

**Symptoms:** Submitted comments don't show

**Solutions:**
1. Comments default to "pending" status
2. Approve comments in admin panel
3. Check comment status in database:

```sql
SELECT id, post_id, status, content 
FROM blog_comments 
WHERE status = 'pending';
```

#### Permission Denied Errors

**Symptoms:** Users can't access certain features

**Solutions:**
1. Verify user role in database:
```sql
SELECT id, username, role FROM admins WHERE id = ?;
```

2. Check role is not NULL (defaults to admin for backward compatibility)
3. Clear session and re-login

#### CSRF Token Errors

**Symptoms:** Form submissions fail with "Invalid security token"

**Solutions:**
1. Clear browser cookies and cache
2. Ensure session is started
3. Check token is included in form:
```php
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
```

#### Like Count Mismatch

**Symptoms:** Like count doesn't match actual likes

**Solutions:**
1. Database triggers should maintain counts
2. Manual recount:
```sql
UPDATE blog_posts p
SET like_count = (
    SELECT COUNT(*) FROM blog_post_likes WHERE post_id = p.id
);
```

#### Image Upload Issues

**Symptoms:** Featured images not saving or displaying

**Solutions:**
1. Check directory permissions: `chmod 755 media/blog_images`
2. Verify file size limits in PHP configuration
3. Check allowed file types in upload handler

---

## Support

For additional support or bug reports, contact the development team or create an issue in the project repository.

---

*Documentation Version: 1.0*  
*Last Updated: February 2026*
