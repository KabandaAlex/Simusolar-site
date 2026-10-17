# Job Portal Migration - Quick Start Guide

## Overview

This guide provides quick instructions for using the Job Portal migration scripts in your tulima_php project.

## Files Created

1. **[`migrate-job-portal.sh`](migrate-job-portal.sh:1)** - Main migration script
2. **[`test-migration.sh`](test-migration.sh:1)** - Test suite for the migration script
3. **[`MIGRATION-SCRIPT-README.md`](MIGRATION-SCRIPT-README.md:1)** - Comprehensive documentation
4. **[`MIGRATION-QUICK-START.md`](MIGRATION-QUICK-START.md:1)** - This quick start guide

## Prerequisites

- Bash shell (Windows: WSL, Git Bash, or similar)
- Source files in `job-portal/` directory
- Sufficient disk space
- Appropriate file permissions

## Quick Start

### Option 1: Windows with WSL (Recommended)

If you have Windows Subsystem for Linux installed:

```bash
# Open WSL terminal
wsl

# Navigate to project directory
cd /mnt/c/wamp64/www/tulima_php

# Make scripts executable
chmod +x migrate-job-portal.sh test-migration.sh

# Run migration
./migrate-job-portal.sh

# Run tests
./test-migration.sh
```

### Option 2: Windows with Git Bash

If you have Git Bash installed:

```bash
# Navigate to project directory
cd c:/wamp64/www/tulima_php

# Make scripts executable
chmod +x migrate-job-portal.sh test-migration.sh

# Run migration
./migrate-job-portal.sh

# Run tests
./test-migration.sh
```

### Option 3: Windows with Cygwin

If you have Cygwin installed:

```bash
# Navigate to project directory
cd /cygdrive/c/wamp64/www/tulima_php

# Make scripts executable
chmod +x migrate-job-portal.sh test-migration.sh

# Run migration
./migrate-job-portal.sh

# Run tests
./test-migration.sh
```

## Basic Usage

### Run Migration with Defaults

```bash
./migrate-job-portal.sh
```

This will:
- Migrate all files with backups enabled
- Merge CSS/JS files
- Update admin menu
- Enable verbose logging

### Test Migration First (Dry Run)

```bash
./migrate-job-portal.sh --dry-run
```

This will:
- Show what would be done without making changes
- Useful for previewing the migration

### Enable Verbose Logging

```bash
./migrate-job-portal.sh --verbose
```

This will:
- Show detailed information about each operation
- Help troubleshoot issues

### Run Without Creating Backups

```bash
./migrate-job-portal.sh --no-backup
```

This will:
- Skip creating backups
- Faster migration
- Riskier (no rollback option)

### Skip CSS/JS Merging

```bash
./migrate-job-portal.sh --no-merge-css --no-merge-js
```

This will:
- Copy files without merging
- Overwrite existing files completely

### Skip Admin Menu Update

```bash
./migrate-job-portal.sh --no-menu-update
```

This will:
- Skip updating the admin navigation menu
- Keep existing menu structure

### Combine Options

```bash
./migrate-job-portal.sh --verbose --dry-run --no-backup
```

## Running Tests

### Run All Tests

```bash
./test-migration.sh
```

This will:
- Test script functionality
- Verify error handling
- Check file operations
- Report results

### Expected Output

```
========================================
Test Summary
========================================
Total Tests: 20
✓ PASSED: 20
✗ FAILED: 0
========================================
All tests passed!
```

## What Gets Migrated

### Client-Facing Pages
- `job-portal/pages/index.php` → `pages/index.php`
- `job-portal/pages/careers.php` → `pages/careers.php`
- `job-portal/pages/apply.php` → `pages/apply.php`
- `job-portal/pages/job-detail.php` → `pages/job-detail.php`

### Admin Files
- `job-portal/admin/jobs.php` → `admin/jobs.php`
- `job-portal/admin/job-post.php` → `admin/job-post.php`

### Admin Assets
- CSS: `job-portal/admin/assets/css/admin-jobs.css` → `admin/assets/css/admin-jobs.css`
- JS: `job-portal/admin/assets/js/admin-jobs.js` → `admin/assets/js/admin-jobs.js`

### Admin Includes
- `job-portal/admin/includes/layout.php` → `admin/includes/layout.php`

## Integration Features

### Header/Footer Integration
Client-facing pages automatically include:
```php
<?php include_once "includes/header.php"; ?>
<!-- Page content -->
<?php include_once "includes/footer.php"; ?>
```

### CSS Merging
Job portal CSS is merged with [`admin.css`](admin/assets/css/admin.css:1), preserving existing styles.

### JS Merging
Job portal JS is merged with [`admin.js`](admin/assets/js/admin.js:1), preserving existing functionality.

### Admin Menu Update
Admin navigation menu is updated with:
- "Job Portal" section
- "Manage Jobs" link
- "Post New Job" link

## Output Files

### Log Files
All operations are logged to:
```
logs/migration_TIMESTAMP.log
```

### Backup Files
Backups are stored in:
```
backups/migration_TIMESTAMP/
```

### Verification Report
Final summary shows:
- All migrated files
- Enabled features
- Migration statistics

## Troubleshooting

### Issue: "Command not found"

**Solution**: Ensure you're using a bash shell (WSL, Git Bash, or Cygwin)

### Issue: "Permission denied"

**Solution**: Make scripts executable:
```bash
chmod +x migrate-job-portal.sh test-migration.sh
```

### Issue: "Source file not found"

**Solution**: Verify source files exist:
```bash
ls -la job-portal/pages/
ls -la job-portal/admin/
```

### Issue: "Migration verification failed"

**Solution**: Check the log file:
```bash
cat logs/migration_TIMESTAMP.log
```

## Next Steps

1. **Review Documentation**: Read [`MIGRATION-SCRIPT-README.md`](MIGRATION-SCRIPT-README.md:1) for detailed information
2. **Run Tests**: Execute [`test-migration.sh`](test-migration.sh:1) to verify functionality
3. **Test Migration**: Run [`migrate-job-portal.sh --dry-run`](migrate-job-portal.sh:1) to preview changes
4. **Execute Migration**: Run [`migrate-job-portal.sh`](migrate-job-portal.sh:1) to perform migration
5. **Verify Results**: Check migrated files and test functionality
6. **Review Logs**: Examine log files for details

## Support

For detailed information:
- See [`MIGRATION-SCRIPT-README.md`](MIGRATION-SCRIPT-README.md:1)
- Check log files in `logs/` directory
- Use `--help` flag: `./migrate-job-portal.sh --help`

## Example Workflow

```bash
# 1. Make scripts executable
chmod +x migrate-job-portal.sh test-migration.sh

# 2. Run tests
./test-migration.sh

# 3. Preview migration
./migrate-job-portal.sh --verbose --dry-run

# 4. Run migration
./migrate-job-portal.sh --verbose

# 5. Check results
ls -la pages/
ls -la admin/
cat logs/migration_TIMESTAMP.log

# 6. Verify functionality
# Test client pages in browser
# Test admin features in browser
```

## Security Notes

- Scripts only perform file operations
- No code execution
- No network requests
- Backups are timestamped and isolated
- Can be easily removed if needed

## Performance

- Suitable for projects with up to 1000 files
- Time complexity: O(n)
- Memory efficient
- Handles large files well

## Version

Current version: 1.0.0
Release date: 2026-02-24
