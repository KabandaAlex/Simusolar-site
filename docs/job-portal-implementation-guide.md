# Job Portal Implementation Guide

## Overview

This guide provides step-by-step instructions for deploying and integrating the Job Portal component into the existing Tulima Solar website.

## Prerequisites

- PHP 7.4+ with PDO MySQL extension
- MySQL 5.7+ or MariaDB
- WAMP/XAMPP or similar (or PHP built-in server + MySQL)
- Existing Tulima Solar website with database

## Installation Steps

### Step 1: Database Migration

1. **Run the migration script** to create the job portal tables:

```bash
# Open your MySQL client (phpMyAdmin, MySQL Workbench, etc.)
mysql -u your_username -p your_database < database/migration-job-portal.sql
```

Or via phpMyAdmin:
- Go to your database
- Click on "Import"
- Select `database/migration-job-portal.sql`
- Click "Go"

This will create:
- `job_categories` table
- `job_locations` table
- `jobs` table
- `job_tags` table
- `job_tag_relations` table
- `job_applications` table
- `job_application_documents` table
- Default categories and locations
- Sample tags

### Step 2: Create Directory Structure

Create the following directory structure:

```
job-portal/
├── public/
│   ├── index.php                 # Job listing page
│   ├── job-detail.php            # Job detail view
│   ├── apply.php                  # Application form
│   └── assets/
│       ├── css/
│       │   └── job-portal.css    # Portal styles
│       └── js/
│           └── job-portal.js     # Frontend interactions
├── admin/
│   ├── jobs.php                  # Job management dashboard
│   ├── job-post.php              # Job posting form
│   ├── job-edit.php              # Job editing form (to be created)
│   ├── applications.php          # Application management (to be created)
│   ├── categories.php            # Category management (to be created)
│   ├── locations.php             # Location management (to be created)
│   ├── api/
│   │   ├── jobs-api.php          # Jobs REST API (to be created)
│   │   └── applications-api.php  # Applications REST API (to be created)
│   └── assets/
│       ├── css/
│       │   └── admin-jobs.css    # Admin styles
│       └── js/
│           └── admin-jobs.js     # Admin interactions
├── includes/
│   └── job-portal-functions.php  # Helper functions
└── config/
    └── job-portal-config.php     # Configuration (to be created)
```

### Step 3: Create Helper Functions

The helper functions file has already been created at `includes/job-portal-functions.php`. This file contains all the necessary functions for:

- Getting jobs with filters
- Creating and updating jobs
- Submitting applications
- Managing applications
- Category and location management
- Tag management

### Step 4: Create Public Pages

The following public pages have been created:

1. **`job-portal/public/index.php`** - Job listing page with:
   - Featured jobs section
   - Urgent jobs section
   - Advanced search and filtering
   - Responsive grid layout
   - Statistics display

2. **`job-portal/public/job-detail.php`** - Job detail page with:
   - Full job description
   - Requirements and responsibilities
   - Qualifications
   - Related jobs
   - Share functionality
   - Apply button

3. **`job-portal/public/apply.php`** - Secure application form with:
   - Multi-step form
   - File upload validation
   - Form validation
   - Success/error messages

### Step 5: Create Admin Pages

The following admin pages have been created:

1. **`job-portal/admin/jobs.php`** - Job management dashboard with:
   - Statistics overview
   - Job listing with filters
   - Status management
   - Quick actions (edit, delete, toggle status)
   - Application count per job

2. **`job-portal/admin/job-post.php`** - Job posting form with:
   - Basic information fields
   - Job details (type, category, location)
   - Salary information
   - Rich text description
   - Requirements, responsibilities, qualifications
   - Tags input
   - Featured/urgent toggles

### Step 6: Create CSS Files

1. **`job-portal/public/assets/css/job-portal.css`** - Public portal styles matching Tulima Solar's visual identity:
   - Purple, green, and orange color palette
   - Inter font family
   - Responsive design
   - Smooth transitions
   - Mobile-friendly

2. **`job-portal/admin/assets/css/admin-jobs.css`** - Admin styles:
   - Consistent with existing admin interface
   - Professional design
   - Form styling
   - Table styling
   - Modal styling

### Step 7: Create JavaScript Files

1. **`job-portal/public/assets/js/job-portal.js`** - Frontend interactions:
   - Search and filter functionality
   - Real-time search with debounce
   - Form validation
   - File upload drag and drop
   - Smooth scrolling
   - Share functionality

2. **`job-portal/admin/assets/js/admin-jobs.js`** - Admin interactions:
   - Delete confirmation modal
   - Status toggle
   - Form validation
   - Auto-save functionality
   - Keyboard shortcuts
   - Toast notifications

## Integration with Existing Website

### Step 8: Add Navigation Link

Add a "Careers" link to the main navigation:

**Option 1: Via Admin Navigation Builder**

1. Log in to admin panel
2. Navigate to "Navigation & Footer Builder"
3. Add new menu item:
   - Label: "Careers"
   - URL: `index.php?page=careers`
   - Parent: Main Menu

**Option 2: Manual Edit**

Edit `includes/navigation-new.php` or your navigation file to add:

```php
<li class="nav-item">
    <a href="index.php?page=careers" class="nav-link">
        Careers
    </a>
</li>
```

### Step 9: Create Careers Page

Create a new page in the database with slug "careers":

**Via Admin Pages:**

1. Log in to admin panel
2. Navigate to "Pages"
3. Click "Add New Page"
4. Fill in:
   - Title: "Careers"
   - Slug: "careers"
   - Hero Title: "Join Our Team"
   - Hero Subtitle: "Explore career opportunities at Tulima Solar"
   - Hero CTA Text: "View Open Positions"
   - Hero CTA URL: "job-portal/public/index.php"
5. Save and activate

### Step 10: Update Header and Footer

Ensure the job portal assets are included in the header:

**In `includes/header.php`:**

```php
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/job-portal/public/assets/css/job-portal.css">
<script src="<?php echo BASE_PATH; ?>/job-portal/public/assets/js/job-portal.js"></script>
```

**In `includes/footer.php`:**

```php
<script src="<?php echo BASE_PATH; ?>/job-portal/public/assets/js/job-portal.js"></script>
```

### Step 11: Add Admin Navigation

Add job portal links to the admin sidebar:

**In `admin/includes/sidebar.php` or your admin navigation file:**

```php
<li class="admin-nav-item <?php echo $currentNav === 'jobs' ? 'active' : ''; ?>">
    <a href="job-portal/admin/jobs.php" class="admin-nav-link">
        <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        Job Portal
    </a>
</li>
```

## Configuration

### Step 12: Create Configuration File

Create `job-portal/config/job-portal-config.php`:

```php
<?php
/**
 * Job Portal Configuration
 */

return [
    // Upload directory for resumes
    'upload_dir' => __DIR__ . '/../media/job_applications/resumes/',
    
    // Allowed file types for resumes
    'allowed_file_types' => ['pdf', 'doc', 'docx', 'txt'],
    
    // Maximum file size (in bytes)
    'max_file_size' => 5 * 1024 * 1024, // 5MB
    
    // Email notifications
    'email_notifications' => true,
    
    // Email from address
    'from_email' => 'careers@tulimasolar.com',
    
    // Email from name
    'from_name' => 'Tulima Solar Careers',
    
    // Email subject for new applications
    'new_application_subject' => 'New Job Application Received',
    
    // Email subject for application status updates
    'status_update_subject' => 'Application Status Update',
    
    // Application status email template
    'status_email_template' => 'status_update',
    
    // Enable analytics
    'analytics_enabled' => true,
    
    // Analytics tracking ID (optional)
    'analytics_tracking_id' => '',
];
```

## Testing

### Step 13: Test Public Pages

1. **Job Listing Page**
   - Navigate to `job-portal/public/index.php`
   - Verify featured and urgent jobs display
   - Test search functionality
   - Test all filters
   - Test sorting options
   - Verify responsive design on mobile

2. **Job Detail Page**
   - Click on a job title
   - Verify all information displays correctly
   - Test share functionality
   - Test apply button

3. **Application Form**
   - Click "Apply Now" on a job
   - Fill out the form
   - Test file upload
   - Submit the form
   - Verify success message

### Step 14: Test Admin Pages

1. **Job Management**
   - Navigate to `job-portal/admin/jobs.php`
   - Verify statistics display
   - Test filters
   - Test edit, delete, and status toggle buttons
   - Verify application counts

2. **Job Posting**
   - Navigate to `job-portal/admin/job-post.php`
   - Fill out the form
   - Test auto-generated slug
   - Test tag input
   - Submit and verify creation

### Step 15: Test Database

1. Verify all tables were created
2. Check default categories and locations
3. Verify sample data was inserted
4. Test CRUD operations

## Security Considerations

### Step 16: Implement Security Measures

1. **Authentication**
   - Ensure all admin pages require authentication
   - Use existing `requireAdmin()` function

2. **Input Validation**
   - All forms are validated
   - File uploads are restricted
   - SQL injection protection via PDO

3. **File Upload Security**
   - Validate file types
   - Validate file size
   - Store files outside web root
   - Use unique filenames

4. **CSRF Protection**
   - Add CSRF tokens to all forms
   - Verify tokens on submission

5. **XSS Protection**
   - Use `e()` function for output escaping
   - Validate and sanitize all inputs

## Deployment

### Step 17: Production Deployment

1. **Backup Database**
   ```bash
   mysqldump -u username -p database_name > backup.sql
   ```

2. **Deploy Files**
   - Upload all job portal files to server
   - Ensure proper file permissions (755 for directories, 644 for files)

3. **Update Configuration**
   - Update `job-portal-config.php` with production values

4. **Test in Production**
   - Test all functionality
   - Verify email notifications
   - Check file uploads

5. **Update Navigation**
   - Update navigation links to point to production URLs

6. **Monitor**
   - Monitor application submissions
   - Check error logs
   - Monitor performance

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Verify database credentials in `config/database.php`
   - Ensure database exists
   - Check database permissions

2. **File Upload Error**
   - Verify upload directory exists and is writable
   - Check file permissions
   - Verify file size limits

3. **Navigation Not Working**
   - Verify navigation files are included
   - Check URL structure
   - Verify BASE_PATH configuration

4. **CSS Not Loading**
   - Verify CSS files exist
   - Check file paths
   - Verify browser cache

5. **JavaScript Not Working**
   - Verify JS files exist
   - Check file paths
   - Check browser console for errors

## Maintenance

### Regular Tasks

1. **Review Applications**
   - Check application inbox regularly
   - Update application statuses
   - Respond to candidates

2. **Update Job Listings**
   - Close expired jobs
   - Update job details as needed
   - Remove filled positions

3. **Monitor Performance**
   - Check page load times
   - Monitor database queries
   - Review error logs

4. **Backup Database**
   - Regular database backups
   - Test restore procedures

## Additional Features (To Be Implemented)

1. **Job Edit Page** - `job-portal/admin/job-edit.php`
2. **Application Management** - `job-portal/admin/applications.php`
3. **Category Management** - `job-portal/admin/categories.php`
4. **Location Management** - `job-portal/admin/locations.php`
5. **REST API** - `job-portal/admin/api/jobs-api.php` and `applications-api.php`
6. **Email Notifications**
7. **Analytics and Reporting**
8. **Application Tracking**
9. **Bulk Actions**
10. **Advanced Search**

## Support

For issues or questions:
1. Check the error logs
2. Review this implementation guide
3. Check the architecture document
4. Contact the development team

## Version History

- **v1.0** - Initial implementation
  - Database schema
  - Public pages (listing, detail, apply)
  - Admin dashboard (jobs, job posting)
  - CSS and JavaScript files
  - Helper functions
