# Phase 8: Product Categories System

## Overview

This phase implements a comprehensive product category system for the Tulima Solar CMS. The system allows products to be organized into hierarchical categories with support for subcategories, featured products, and SEO-friendly category URLs.

## Implementation Date

**Completed:** February 2026

## Features Implemented

### 1. Database Structure

#### New Table: `product_categories`

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT UNSIGNED | Primary key, auto-increment |
| `name` | VARCHAR(100) | Category name |
| `slug` | VARCHAR(100) | URL-friendly identifier (unique) |
| `description` | TEXT | Category description (nullable) |
| `image` | VARCHAR(255) | Category image path (nullable) |
| `parent_id` | INT UNSIGNED | Parent category ID for subcategories (nullable) |
| `sort_order` | INT | Display order (default 0) |
| `is_active` | TINYINT(1) | Active status (default TRUE) |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

#### Modified Table: `products`

| Column | Type | Description |
|--------|------|-------------|
| `category_id` | INT UNSIGNED | Foreign key to product_categories (nullable) |
| `featured` | TINYINT(1) | Featured product status (default 0) |

### 2. Admin Features

#### Category Management (`admin/product_categories.php`)

- **Full CRUD Operations**: Create, read, update, and delete categories
- **Drag-and-Drop Reordering**: Uses SortableJS for intuitive ordering
- **Hierarchical Support**: Parent/child category relationships
- **Image Upload**: Category images via media picker integration
- **Slug Auto-Generation**: Automatic URL-safe slug creation
- **Active/Inactive Toggle**: Control category visibility
- **Product Count Display**: Shows number of products per category
- **Tree View**: Visual hierarchy display

#### Enhanced Products Admin (`admin/products.php`)

- **Category Filter**: Dropdown to filter products by category
- **Category Selector**: Assign products to categories
- **Featured Checkbox**: Mark products as featured
- **Category Display**: Shows category name in products list
- **Bulk Category Assignment**: Assign multiple products to a category
- **Bulk Featured Toggle**: Mark/unmark multiple products as featured
- **Enhanced Stats**: Product counts by category, featured products

### 3. Frontend Features

#### Dynamic Product Listing (`pages/products-listing.php`)

- **Category Overview**: Main page showing all categories with images
- **Category Pages**: Filter products by category
- **Product Detail View**: Individual product pages
- **Breadcrumb Navigation**: Category hierarchy breadcrumbs
- **Featured Products Section**: Highlighted products on main page
- **Subcategory Display**: Shows subcategories within parent categories
- **Related Products**: Shows related products on product detail page
- **SEO-Friendly URLs**: `/pages/products-listing.php?category=slug` and `/pages/products-listing.php?product=slug`

### 4. Helper Functions (`includes/functions.php`)

#### Category Functions

```php
// Get all categories with optional filtering
getProductCategories(PDO $pdo, bool $includeEmpty = true, bool $activeOnly = false): array

// Get category by slug
getCategoryBySlug(PDO $pdo, string $slug): ?array

// Get category by ID
getCategoryById(PDO $pdo, int $id): ?array

// Get products in a category
getProductsByCategory(PDO $pdo, int $categoryId, bool $activeOnly = true): array

// Get featured products
getFeaturedProducts(PDO $pdo, int $limit = 6): array

// Get nested category hierarchy
getCategoryHierarchy(PDO $pdo, bool $activeOnly = true): array

// Get subcategories of a parent
getSubcategories(PDO $pdo, int $parentId, bool $activeOnly = true): array

// Get category breadcrumb path
getCategoryBreadcrumb(PDO $pdo, array $category): array

// Get all products with category info
getAllProductsWithCategories(PDO $pdo, bool $activeOnly = true): array

// Get products grouped by category
getProductsGroupedByCategory(PDO $pdo, bool $activeOnly = true): array

// Get product by slug
getProductBySlug(PDO $pdo, string $slug): ?array

// Get related products (same category)
getRelatedProducts(PDO $pdo, int $productId, int $limit = 4): array

// Generate unique category slug
generateCategorySlug(PDO $pdo, string $name, ?int $excludeId = null): string

// Count products in category (including subcategories)
countProductsInCategory(PDO $pdo, int $categoryId): int
```

## Default Categories

The migration includes these default categories:

### Parent Categories
1. **Solar Water Pumps** (`solar-water-pumps`)
2. **Agricultural Processing Machines** (`agricultural-processing-machines`)
3. **Solar Systems** (`solar-systems`)
4. **Accessories** (`accessories`)

### Subcategories
- **Submersible Pumps** (under Solar Water Pumps)
- **Surface Pumps** (under Solar Water Pumps)
- **Milling Machines** (under Agricultural Processing Machines)
- **Threshing Machines** (under Agricultural Processing Machines)
- **Chaff Cutters** (under Agricultural Processing Machines)

## Files Created/Modified

### New Files
| File | Description |
|------|-------------|
| `database/migration_product_categories.sql` | Database migration script |
| `admin/product_categories.php` | Category management admin page |
| `pages/products-listing.php` | Dynamic frontend product listing |

### Modified Files
| File | Changes |
|------|---------|
| `admin/products.php` | Added category support, featured products, bulk operations |
| `admin/includes/header.php` | Added "Product Categories" menu item |
| `includes/functions.php` | Added 14 new helper functions for categories |

## Database Migration Instructions

1. **Backup Database**: Always backup before running migrations
   ```bash
   mysqldump -u user -p tulima_solar > backup_$(date +%Y%m%d).sql
   ```

2. **Run Migration**: Execute the migration SQL
   ```bash
   mysql -u user -p tulima_solar < database/migration_product_categories.sql
   ```

3. **Verify Migration**: Check that tables were created/modified correctly
   ```sql
   SHOW TABLES LIKE 'product_categories';
   DESCRIBE product_categories;
   DESCRIBE products;
   SELECT * FROM product_categories;
   ```

## Usage Guide

### Admin: Managing Categories

1. Navigate to **Admin Panel → Product Categories**
2. Click **Add Category** to create a new category
3. Fill in the category details:
   - **Name**: Display name
   - **Slug**: URL identifier (auto-generated)
   - **Description**: Category description
   - **Parent Category**: Select for subcategories
   - **Image**: Category image
   - **Sort Order**: Display order
   - **Active**: Visibility toggle
4. Drag categories to reorder
5. Click **Save Order** to persist changes

### Admin: Assigning Products to Categories

1. Navigate to **Admin Panel → Products**
2. Use the **Category** dropdown to filter by category
3. Edit a product and select its category
4. Or use bulk actions:
   - Select multiple products with checkboxes
   - Choose a category from the dropdown
   - Click **Apply Category**

### Admin: Marking Featured Products

1. Navigate to **Admin Panel → Products**
2. Click the star icon to toggle featured status
3. Or use bulk actions for multiple products

### Frontend: Viewing Products

- **All Products**: `/pages/products-listing.php`
- **Category View**: `/pages/products-listing.php?category=solar-water-pumps`
- **Product Detail**: `/pages/products-listing.php?product=product-slug`

## API Integration

The helper functions can be used anywhere in the application:

```php
// Get all active categories
$categories = getProductCategories($pdo, true, true);

// Get featured products for homepage
$featured = getFeaturedProducts($pdo, 6);

// Get products in a specific category
$category = getCategoryBySlug($pdo, 'solar-water-pumps');
$products = getProductsByCategory($pdo, $category['id']);

// Build navigation menu
$_hierarchy = getCategoryHierarchy($pdo);
```

## SEO Considerations

- **URL Structure**: Clean, SEO-friendly URLs with category/product slugs
- **Meta Titles**: Category and product names used in page titles
- **Meta Descriptions**: Category descriptions used for meta descriptions
- **Breadcrumbs**: Proper breadcrumb navigation for search engines
- **Hierarchy**: Clear parent-child relationships for better indexing

## Future Enhancements

Potential improvements for future phases:

1. **Category Icons**: SVG icon support for categories
2. **Multi-level Hierarchy**: Support for deeper nesting
3. **Category Templates**: Custom display templates per category
4. **Product Variants**: Size/color variations within products
5. **Category SEO**: Custom meta titles and descriptions per category
6. **Product Search**: Full-text search with category filters
7. **Inventory Tracking**: Stock levels and availability
8. **Price Management**: Pricing with discounts and specials

## Troubleshooting

### Common Issues

1. **Categories not showing**: Check `is_active` flag in database
2. **Products not categorized**: Run migration to assign existing products
3. **Slug conflicts**: Slugs must be unique; check for duplicates
4. **Parent category not found**: Ensure parent exists before creating subcategories

### Debug Queries

```sql
-- Find uncategorized products
SELECT * FROM products WHERE category_id IS NULL;

-- Find categories with no products
SELECT c.* FROM product_categories c 
LEFT JOIN products p ON c.id = p.category_id 
WHERE p.id IS NULL;

-- Check category hierarchy
SELECT c.name AS category, p.name AS parent 
FROM product_categories c 
LEFT JOIN product_categories p ON c.parent_id = p.id;
```

## Testing Checklist

- [ ] Database migration runs without errors
- [ ] Categories display correctly in admin
- [ ] Categories can be created, edited, deleted
- [ ] Drag-and-drop reordering works
- [ ] Products can be assigned to categories
- [ ] Category filter works in products admin
- [ ] Bulk category assignment works
- [ ] Featured products display correctly
- [ ] Frontend category pages display correctly
- [ ] Product detail pages show related products
- [ ] Breadcrumbs display correctly
- [ ] Slug auto-generation works
- [ ] Parent/child relationships work
- [ ] Active/inactive toggle works

## Support

For issues or questions about the product categories system:

1. Check this documentation
2. Review the code comments in modified files
3. Check database structure with `DESCRIBE` queries
4. Review browser console for JavaScript errors

---

*Documentation last updated: February 2026*
