# Phase 4 & 5 Setup Guide

**Date:** February 22, 2026  
**Status:** Implementation Complete - Setup Required

## Overview

Phase 4 and Phase 5 of the Tulima Solar Admin Portal Redesign have been fully implemented. This guide will help you set up the database migrations and verify that all features are working correctly.

---

## Phase 4: Advanced Features & Reporting

### Features Implemented

1. **Reports Page** (`admin/reports.php`)
   - Overview, Traffic, Content, Contacts, and Products reports
   - Interactive charts using Chart.js
   - Date range filtering (7, 30, 90 days)
   - Export functionality for all report data

2. **Enhanced Analytics API** (`admin/api/analytics.php`)
   - `traffic-trends`: Daily traffic data with unique visitor tracking
   - `content-performance`: Page and product analytics
   - `contact-analytics`: Detailed contact submission analytics
   - `visitor-stats`: Device and browser statistics
   - `top-pages`: Most viewed pages with performance metrics
   - `peak-hours`: Contact submission patterns by hour

3. **Advanced Activity Log Viewer** (`admin/activity-log.php`)
   - Action type filter (All, Create, Update, Delete, View, Login, Logout)
   - Entity type filter (All, Page, Product, Contact, etc.)
   - User filter
   - Date range filter (from/to dates)
   - Export functionality with filters applied
   - Quick-filter buttons for common scenarios

4. **Data Export System** (`admin/api/export.php`)
   - Contact Submissions export
   - Page Views export
   - Activity Log export
   - Products export
   - Testimonials export
   - Partners export
   - Pages export

5. **Bulk Operations** (`admin/contact_submissions.php`)
   - Select all/individual submissions with checkboxes
   - Bulk action toolbar (Mark as Read, Mark as Unread, Delete Selected)
   - Server-side bulk processing with prepared statements

---

## Phase 5: Polish & Optimization

### Features Implemented

1. **Accessibility Improvements**
   - Skip to content link
   - Focus styles with 3px purple outline
   - ARIA labels on all interactive elements
   - Semantic HTML structure
   - Keyboard navigation support

2. **Performance Optimizations**
   - Database indexes for efficient queries
   - Prepared statements for security and performance
   - Lazy loading of charts
   - Efficient data fetching
   - CSS variables for theming

3. **Responsive Design Improvements**
   - Mobile-friendly chart layouts
   - Responsive table layouts
   - Mobile-first form layouts
   - Touch-friendly input sizes

4. **Dark Mode Toggle**
   - Sun/moon icon toggle
   - LocalStorage persistence
   - System preference detection
   - Smooth transitions between modes

5. **Notification System**
   - Unread count badge
   - Dropdown menu with notifications list
   - "Mark all as read" action
   - "View All" link to submissions page

---

## Setup Instructions

### Step 1: Run Database Migrations

You need to run the database migrations to create the required tables for Phase 4 and Phase 5 features.

#### Option A: Using MySQL Command Line

```bash
# Run the analytics migration
mysql -u username -p tulima_solar < database/migration_analytics.sql

# Run the contact submissions migration (if not already done)
mysql -u username -p tulima_solar < database/migration_contact_submissions.sql
```

#### Option B: Using phpMyAdmin

1. Open phpMyAdmin
2. Select the `tulima_solar` database
3. Click on the "Import" tab
4. Choose `database/migration_analytics.sql` and click "Go"
5. Repeat for `database/migration_contact_submissions.sql` (if needed)

#### Option B: Using WAMP Server

1. Open WAMP Server
2. Click on phpMyAdmin
3. Select the `tulima_solar` database
4. Click on the "Import" tab
5. Choose `database/migration_analytics.sql` and click "Go"
6. Repeat for `database/migration_contact_submissions.sql` (if needed)

### Step 2: Verify Tables Created

After running the migrations, verify that the following tables exist in your database:

- `page_views` - Tracks page views for analytics
- `activity_log` - Logs all admin actions
- `notifications` - Admin notifications and alerts
- `product_analytics` - Daily analytics for products
- `contact_submissions` - Contact form submissions (with status tracking)

You can verify this by running:

```sql
SHOW TABLES;
```

### Step 3: Verify Site Settings

The migration should have added the following settings to the `site_settings` table:

- `analytics_enabled` - Enable Analytics Tracking (default: '1')
- `analytics_retention_days` - Analytics Data Retention (Days) (default: '90')

You can verify this by running:

```sql
SELECT * FROM site_settings WHERE `key` IN ('analytics_enabled', 'analytics_retention_days');
```

### Step 4: Include Analytics Tracking (Optional)

To track page views on your frontend, add the following to your frontend pages (e.g., in `includes/header.php`):

```php
<?php
require_once __DIR__ . '/includes/analytics.php';
?>
```

**Note:** The analytics tracking is already included in the `includes/analytics.php` file, which will automatically track page views when included.

---

## Testing Checklist

### Phase 4 Testing

- [ ] Access the Reports page (`admin/reports.php`)
- [ ] Verify all report types load correctly (Overview, Traffic, Content, Contacts, Products)
- [ ] Test date range filtering (7, 30, 90 days)
- [ ] Verify charts render with accurate data
- [ ] Test export functionality for each report type
- [ ] Access the Activity Log page (`admin/activity-log.php`)
- [ ] Test activity log filters (action, entity type, date range)
- [ ] Verify export functionality for activity log
- [ ] Access the Contact Submissions page (`admin/contact_submissions.php`)
- [ ] Test bulk operations (Mark as Read, Mark as Unread, Delete Selected)
- [ ] Test export functionality for contact submissions
- [ ] Verify analytics API endpoints return correct data

### Phase 5 Testing

- [ ] Test keyboard navigation throughout the admin panel
- [ ] Verify focus indicators are visible on all interactive elements
- [ ] Test skip to content link functionality
- [ ] Verify ARIA labels are present on icon-only buttons
- [ ] Test dark mode toggle functionality
- [ ] Verify dark mode persists across page reloads
- [ ] Test responsive design on mobile devices (768px, 1024px, 1280px breakpoints)
- [ ] Verify notification dropdown works correctly
- [ ] Test "Mark all as read" functionality
- [ ] Verify page load times are under 2 seconds
- [ ] Check for any JavaScript errors in browser console

---

## Troubleshooting

### Issue: "Table 'tulima_solar.activity_log' doesn't exist"

**Solution:** Run the database migration:
```bash
mysql -u username -p tulima_solar < database/migration_analytics.sql
```

### Issue: "Table 'tulima_solar.page_views' doesn't exist"

**Solution:** Run the database migration:
```bash
mysql -u username -p tulima_solar < database/migration_analytics.sql
```

### Issue: "Table 'tulima_solar.notifications' doesn't exist"

**Solution:** Run the database migration:
```bash
mysql -u username -p tulima_solar < database/migration_analytics.sql
```

### Issue: Charts not rendering

**Solution:** 
1. Check that Chart.js is loaded in the browser console
2. Verify the analytics API is returning data
3. Check for JavaScript errors in the browser console

### Issue: Export functionality not working

**Solution:**
1. Verify you're logged in as admin
2. Check browser console for JavaScript errors
3. Verify the export API endpoint is accessible

### Issue: Bulk operations not working

**Solution:**
1. Verify you're logged in as admin
2. Check that the `contact_submissions` table has the required columns
3. Check browser console for JavaScript errors

---

## File Structure

### Phase 4 Files

- `admin/reports.php` - Reports page with multiple report types
- `admin/api/analytics.php` - Enhanced analytics API endpoints
- `admin/activity-log.php` - Activity log viewer with advanced filtering
- `admin/api/export.php` - Data export API
- `admin/contact_submissions.php` - Bulk operations for contact submissions
- `admin/pages.php` - Export functionality for pages

### Phase 5 Files

- `admin/includes/header.php` - Accessibility improvements (skip to content, ARIA labels)
- `admin/assets/css/admin.css` - Focus styles, responsive design improvements
- `admin/assets/css/charts.css` - Chart styles
- `admin/assets/js/charts.js` - Chart initialization and rendering

### Support Files

- `includes/activity_logger.php` - Activity logging functions
- `includes/notifications.php` - Notification system functions
- `includes/analytics.php` - Analytics tracking functions
- `database/migration_analytics.sql` - Database migration for Phase 4 & 5

---

## Next Steps

After completing the setup:

1. **Test all features** using the testing checklist above
2. **Customize the dashboard** to your needs
3. **Set up regular backups** of your database
4. **Monitor analytics** to gain insights into your website's performance
5. **Train your team** on using the new features (see `docs/user-training-guide.md`)

---

## Support

If you encounter any issues:

1. Check the browser console for JavaScript errors
2. Check the PHP error logs for server-side errors
3. Verify all database migrations have been run
4. Ensure you're logged in as an admin user
5. Review the troubleshooting section above

---

**Implementation Status:** ✅ Complete  
**Setup Required:** Database migrations  
**Next Phase:** Production deployment and monitoring

*Last Updated: February 22, 2026*
