# Job Portal - File Structure & Access Guide

## All Created Files

### Database Files
- ✅ [`database/migration-job-portal.sql`](../database/migration-job-portal.sql) - Database schema and seed data

### Helper Functions
- ✅ [`includes/job-portal-functions.php`](../includes/job-portal-functions.php) - All portal functions

### Public Pages (Job Portal)
- ✅ [`job-portal/public/index.php`](../job-portal/public/index.php) - Job listing page
- ✅ [`job-portal/public/job-detail.php`](../job-portal/public/job-detail.php) - Job detail view
- ✅ [`job-portal/public/apply.php`](../job-portal/public/apply.php) - Application form

### Public Assets
- ✅ [`job-portal/public/assets/css/job-portal.css`](../job-portal/public/assets/css/job-portal.css) - Public portal styles
- ✅ [`job-portal/public/assets/js/job-portal.js`](../job-portal/public/assets/js/job-portal.js) - Frontend interactions

### Admin Pages (Job Portal)
- ✅ [`job-portal/admin/jobs.php`](../job-portal/admin/jobs.php) - Job management dashboard
- ✅ [`job-portal/admin/job-post.php`](../job-portal/admin/job-post.php) - Job posting form

### Admin Assets
- ✅ [`job-portal/admin/assets/css/admin-jobs.css`](../job-portal/admin/assets/css/admin-jobs.css) - Admin styles
- ✅ [`job-portal/admin/assets/js/admin-jobs.js`](../job-portal/admin/assets/js/admin-jobs.js) - Admin interactions

### Documentation
- ✅ [`docs/job-portal-architecture.md`](../docs/job-portal-architecture.md) - System architecture
- ✅ [`docs/job-portal-implementation-guide.md`](../docs/job-portal-implementation-guide.md) - Deployment guide

## How to Access Admin Pages

### Method 1: Direct URL Access
Open these URLs directly in your browser:

1. **Job Management Dashboard**
   ```
   http://localhost/tulima_php/job-portal/admin/jobs.php
   ```

2. **Job Posting Form**
   ```
   http://localhost/tulima_php/job-portal/admin/job-post.php
   ```

### Method 2: Via Admin Login
1. Go to `http://localhost/tulima_php/admin/login.php`
2. Login with your admin credentials
3. You'll see the existing admin dashboard
4. The job portal admin pages are accessible via direct URLs as shown above

### Method 3: Add to Admin Navigation (Recommended)

To add job portal links to your existing admin sidebar/navigation, edit your admin navigation file:

**Option A: Edit `admin/includes/sidebar.php`**

Add this code to the sidebar menu:

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

**Option B: Edit `admin/navigation.php`**

Add to the navigation array:

```php
'job-portal' => [
    'label' => 'Job Portal',
    'icon' => 'briefcase',
    'pages' => [
        'jobs' => [
            'label' => 'Jobs Management',
            'url' => 'job-portal/admin/jobs.php',
            'icon' => 'list'
        ],
        'job-post' => [
            'label' => 'Post New Job',
            'url' => 'job-portal/admin/job-post.php',
            'icon' => 'plus'
        ]
    ]
]
```

## Admin Pages Overview

### 1. Jobs Management ([`jobs.php`](../job-portal/admin/jobs.php))
**URL:** `job-portal/admin/jobs.php`

**Features:**
- Overview statistics (total jobs, active, draft, closed, applications)
- Filter jobs by status and category
- Search jobs by keyword
- List all jobs with details
- Quick actions:
  - Edit job
  - Delete job
  - Toggle job status
  - View applications

**What you can do:**
- View all job listings
- Change job status (draft → active → closed)
- Delete jobs
- See application count per job
- Search and filter jobs

### 2. Job Posting ([`job-post.php`](../job-portal/admin/job-post.php))
**URL:** `job-portal/admin/job-post.php`

**Features:**
- Complete job creation form
- Auto-generated URL slug
- Rich text description editor
- Job type and employment type selection
- Category and location selection
- Salary range input
- Application deadline setting
- Tags input
- Featured and urgent toggles

**What you can do:**
- Create new job listings
- Fill in all job details
- Set job status (draft/active)
- Add tags for better search
- Schedule application deadline

## Quick Start

### Step 1: Run Database Migration
```bash
mysql -u your_username -p your_database < database/migration-job-portal.sql
```

### Step 2: Access Admin Pages
Open in browser:
```
http://localhost/tulima_php/job-portal/admin/jobs.php
```

### Step 3: Create Your First Job
1. Navigate to `job-portal/admin/job-post.php`
2. Fill out the job details
3. Set status to "Active" if you want it visible immediately
4. Click "Create Job"

### Step 4: View Jobs
1. Navigate to `job-portal/public/index.php`
2. You'll see your newly created job in the listings

## Troubleshooting

### Can't Access Admin Pages?
1. Make sure you're logged into the admin panel
2. Check the file paths are correct
3. Verify the files exist in the `job-portal/admin/` directory

### Database Connection Error?
1. Check `config/database.php` has correct credentials
2. Verify the database exists
3. Ensure the migration has been run

### Styles Not Loading?
1. Check CSS files exist in `job-portal/admin/assets/css/`
2. Verify file paths in PHP files
3. Clear browser cache

## File Locations Summary

```
tulima_php/
├── database/
│   └── migration-job-portal.sql          ← Database schema
├── includes/
│   └── job-portal-functions.php          ← Helper functions
├── job-portal/
│   ├── public/
│   │   ├── index.php                      ← Job listing page
│   │   ├── job-detail.php                 ← Job detail page
│   │   ├── apply.php                      ← Application form
│   │   └── assets/
│   │       ├── css/
│   │       │   └── job-portal.css         ← Public styles
│   │       └── js/
│   │           └── job-portal.js          ← Frontend JS
│   └── admin/
│       ├── jobs.php                       ← Job management
│       ├── job-post.php                   ← Job posting
│       └── assets/
│           ├── css/
│           │   └── admin-jobs.css         ← Admin styles
│           └── js/
│               └── admin-jobs.js          ← Admin JS
└── docs/
    ├── job-portal-architecture.md         ← Architecture doc
    └── job-portal-implementation-guide.md  ← Implementation guide
```

## Need Help?

If you can't find the admin pages or have issues accessing them:

1. **Check file locations** - All files are in the `job-portal/` directory
2. **Verify database** - Run the migration script
3. **Check permissions** - Ensure files are readable
4. **Review logs** - Check PHP error logs for issues
