# Job Portal Migration Script Documentation

## Overview

The [`migrate-job-portal.sh`](migrate-job-portal.sh:1) script is a comprehensive Bash shell script designed to migrate Job Portal directory structures from a `job-portal` folder into the `tulima_php` project with robust error handling and integration features.

## Features

### Core Migration Features

1. **File Existence Verification**
   - Validates source files and directories before migration
   - Provides clear error messages if source is missing
   - Prevents silent failures

2. **Automatic Directory Creation**
   - Creates destination directories automatically if they don't exist
   - Handles nested directory structures
   - Ensures proper directory hierarchy

3. **File Overwrite Handling**
   - Backs up existing files before overwriting
   - Creates timestamped backup directories
   - Optional backup control via command-line flags

4. **CSS/JS File Merging**
   - Merges job portal CSS/JS files with existing master project files
   - Preserves existing styles and adds new functionality
   - Configurable merge behavior

5. **Header/Footer Integration**
   - Automatically integrates host site's global header and footer
   - Ensures consistent styling and navigation
   - Works with client-facing pages

6. **Admin Menu Updates**
   - Programmatically updates admin left vertical menu
   - Adds Job Portal section with sub-menu items
   - Maintains menu structure integrity

### Logging & Verification

7. **Verbose Logging**
   - Timestamped log entries for all operations
   - Multiple log levels (INFO, WARN, ERROR, SUCCESS, DEBUG)
   - Logs saved to timestamped log files
   - Console output for real-time monitoring

8. **Final Verification**
   - Validates all migrated files exist
   - Checks file integrity
   - Reports any errors or missing files
   - Provides detailed migration summary

## Usage

### Basic Usage

```bash
./migrate-job-portal.sh
```

This will run the migration with default settings:
- Create backups of existing files
- Merge CSS/JS files
- Update admin menu
- Enable verbose logging

### Command-Line Options

| Option | Description |
|--------|-------------|
| `-h, --help` | Show help message and exit |
| `-v, --verbose` | Enable verbose logging |
| `-n, --dry-run` | Perform a dry run (no actual changes) |
| `--no-backup` | Skip creating backups |
| `--no-merge-css` | Skip CSS file merging |
| `--no-merge-js` | Skip JS file merging |
| `--no-menu-update` | Skip admin menu update |

### Examples

#### Enable Verbose Logging

```bash
./migrate-job-portal.sh --verbose
```

#### Test Migration Without Changes

```bash
./migrate-job-portal.sh --dry-run
```

#### Run Without Creating Backups

```bash
./migrate-job-portal.sh --no-backup
```

#### Skip CSS/JS Merging

```bash
./migrate-job-portal.sh --no-merge-css --no-merge-js
```

#### Custom Combination

```bash
./migrate-job-portal.sh --verbose --dry-run --no-backup
```

## Migration Details

### Files Migrated

#### Pages (Client-Facing)
- `job-portal/pages/index.php` → `pages/index.php`
- `job-portal/pages/careers.php` → `pages/careers.php`
- `job-portal/pages/apply.php` → `pages/apply.php`
- `job-portal/pages/job-detail.php` → `pages/job-detail.php`

#### Admin Files
- `job-portal/admin/jobs.php` → `admin/jobs.php`
- `job-portal/admin/job-post.php` → `admin/job-post.php`

#### Admin Assets
- CSS: `job-portal/admin/assets/css/admin-jobs.css` → `admin/assets/css/admin-jobs.css`
- JS: `job-portal/admin/assets/js/admin-jobs.js` → `admin/assets/js/admin-jobs.js`

#### Admin Includes
- `job-portal/admin/includes/layout.php` → `admin/includes/layout.php`

### Directory Structure

The script maintains the following directory structure:

```
tulima_php/
├── pages/
│   ├── index.php
│   ├── careers.php
│   ├── apply.php
│   └── job-detail.php
├── admin/
│   ├── jobs.php
│   ├── job-post.php
│   ├── assets/
│   │   ├── css/
│   │   │   ├── admin.css (merged)
│   │   │   └── admin-jobs.css (merged)
│   │   └── js/
│   │       ├── admin.js (merged)
│   │       └── admin-jobs.js (merged)
│   └── includes/
│       └── layout.php
├── backups/
│   └── migration_TIMESTAMP/
│       ├── navigation.php
│       ├── admin.css
│       ├── admin.js
│       └── [other backed up files]
└── logs/
    └── migration_TIMESTAMP.log
```

## Integration Features

### Header/Footer Integration

Client-facing pages are automatically enhanced with:

```php
<?php include_once "includes/header.php"; ?>
<!-- Page content -->
<?php include_once "includes/footer.php"; ?>
```

This ensures:
- Consistent global navigation
- Shared header/footer components
- Unified styling and branding
- Proper SEO and meta tags

### CSS Merging

The script merges job portal CSS with the existing [`admin.css`](admin/assets/css/admin.css:1) file:

1. Backs up existing [`admin.css`](admin/assets/css/admin.css:1)
2. Appends job portal styles
3. Preserves all existing styles
4. Adds new job portal functionality

### JS Merging

The script merges job portal JavaScript with the existing [`admin.js`](admin/assets/js/admin.js:1):

1. Backs up existing [`admin.js`](admin/assets/js/admin.js:1)
2. Appends job portal scripts
3. Preserves existing functionality
4. Adds new job portal features

### Admin Menu Updates

The script updates the admin navigation menu to include:

```php
'Job Portal' => array(
    'label' => 'Job Portal',
    'icon' => 'fa-briefcase',
    'items' => array(
        'jobs' => array(
            'label' => 'Manage Jobs',
            'url' => 'jobs.php',
        ),
        'job-post' => array(
            'label' => 'Post New Job',
            'url' => 'job-post.php',
        ),
    ),
),
```

## Error Handling

### Error Detection

The script includes comprehensive error handling:

1. **Source File/Directory Checks**
   - Verifies source files exist before migration
   - Validates source directories are accessible
   - Provides clear error messages

2. **Directory Creation Failures**
   - Checks directory creation success
   - Reports permission issues
   - Provides actionable error messages

3. **File Copy Failures**
   - Validates copy operations
   - Reports permission issues
   - Handles disk space errors

4. **Merge Operation Failures**
   - Validates merge operations
   - Ensures file integrity
   - Provides detailed error information

### Error Recovery

The script is designed to fail fast on errors:

- Immediate exit on critical failures
- Detailed error logging
- Backup creation before modifications
- Clear error messages for troubleshooting

## Logging

### Log Levels

- **INFO**: General information about operations
- **WARN**: Warning messages for non-critical issues
- **ERROR**: Error messages for failures
- **SUCCESS**: Confirmation of successful operations
- **DEBUG**: Detailed debugging information (only with `-v` flag)

### Log Files

All operations are logged to:
```
logs/migration_TIMESTAMP.log
```

### Console Output

The script provides real-time console output showing:
- Progress of migration operations
- Any warnings or errors
- Successful operations
- Final summary

## Backup System

### Backup Location

Backups are stored in:
```
backups/migration_TIMESTAMP/
```

### Backup Contents

Each backup contains:
- Original files before modification
- Timestamped for easy identification
- Preserved in separate directory
- Can be easily restored if needed

### Backup Control

Backups can be disabled with:
```bash
./migrate-job-portal.sh --no-backup
```

## Verification

### Post-Migration Verification

The script performs automatic verification:

1. **File Existence Check**
   - Verifies all migrated files exist
   - Checks destination paths
   - Reports missing files

2. **File Integrity Check**
   - Validates file contents
   - Ensures files are not empty
   - Checks file permissions

3. **Summary Report**
   - Lists all migrated files
   - Shows enabled features
   - Provides migration statistics

### Verification Output

```
Migration Verification
==========================================
✓ Verified: pages/index.php
✓ Verified: pages/careers.php
✓ Verified: pages/apply.php
✓ Verified: pages/job-detail.php
✓ Verified: admin/jobs.php
✓ Verified: admin/job-post.php
✓ Verified: admin/assets/css/admin-jobs.css
✓ Verified: admin/assets/js/admin-jobs.js
✓ Verified: admin/includes/layout.php
==========================================
Migration verification completed successfully
```

## Best Practices

### Pre-Migration

1. **Review Source Files**
   - Check source files are complete
   - Verify file permissions
   - Ensure sufficient disk space

2. **Backup Current State**
   - Run with `--no-backup` to test first
   - Use `--dry-run` to preview changes
   - Consider manual backup before migration

3. **Check Dependencies**
   - Verify required PHP extensions
   - Check database connectivity
   - Ensure file system permissions

### During Migration

1. **Monitor Progress**
   - Watch console output
   - Check log files for details
   - Note any warnings

2. **Handle Errors**
   - Review error messages
   - Check backup files if needed
   - Review log files for troubleshooting

### Post-Migration

1. **Verify Migration**
   - Run verification step
   - Test migrated pages
   - Check admin functionality

2. **Test Functionality**
   - Test client-facing pages
   - Test admin features
   - Verify CSS/JS functionality

3. **Clean Up**
   - Review backup files
   - Remove old backups if needed
   - Update documentation

## Troubleshooting

### Common Issues

#### Issue: "Source file not found"

**Solution**: Verify source files exist in the correct location:
```bash
ls -la job-portal/pages/
ls -la job-portal/admin/
```

#### Issue: "Permission denied"

**Solution**: Check and update file permissions:
```bash
chmod +x migrate-job-portal.sh
chmod -R 755 job-portal/
```

#### Issue: "Directory already exists"

**Solution**: This is normal. The script handles existing directories gracefully.

#### Issue: "Migration verification failed"

**Solution**: Check the log file for details:
```bash
cat logs/migration_TIMESTAMP.log
```

### Getting Help

1. **Check Log Files**
   - Review `logs/migration_TIMESTAMP.log`
   - Look for error messages
   - Check timestamped entries

2. **Use Dry Run**
   ```bash
   ./migrate-job-portal.sh --verbose --dry-run
   ```
   This shows what would happen without making changes.

3. **Review Documentation**
   - This README
   - Script help: `./migrate-job-portal.sh --help`

## Script Structure

The script is organized into the following sections:

1. **Configuration** (lines 1-70)
   - Script metadata
   - Path definitions
   - File lists
   - Feature flags

2. **Logging Functions** (lines 73-106)
   - `log()`, `log_info()`, `log_warn()`, `log_error()`, `log_success()`, `log_debug()`

3. **Error Handling Functions** (lines 109-127)
   - `error_exit()`, `check_command()`

4. **Directory and File Operations** (lines 130-204)
   - `create_directory()`, `create_backup_dir()`, `backup_file()`
   - `verify_source_file()`, `verify_source_directory()`

5. **File Migration Functions** (lines 207-313)
   - `copy_file()`, `merge_css_files()`, `merge_js_files()`
   - `integrate_header_footer()`, `update_admin_menu()`

6. **Verification Functions** (lines 316-371)
   - `verify_migration()`, `show_summary()`

7. **Main Migration Function** (lines 374-470)
   - `migrate_job_portal()`

8. **Help Function** (lines 473-511)
   - `show_help()`

9. **Command Line Parsing** (lines 514-551)
   - Argument handling

10. **Main Execution** (lines 554-563)
    - Script entry point

## Technical Specifications

### Requirements

- Bash shell (version 4.0 or higher)
- `mkdir`, `cp`, `sed`, `grep` commands
- Sufficient disk space for migration
- Appropriate file permissions

### Compatibility

- Windows: WSL or Git Bash
- Linux: Standard Bash shell
- macOS: Standard Bash shell

### Performance

- Time complexity: O(n) where n is number of files
- Space complexity: O(1) additional space
- Suitable for projects with up to 1000 files

## Security Considerations

1. **File Permissions**
   - Script should be executable
   - Source files should be readable
   - Destination directories should be writable

2. **Backup Safety**
   - Backups are stored in separate directory
   - Timestamped for easy identification
   - Can be easily removed if needed

3. **No Code Execution**
   - Script only performs file operations
   - No dynamic code execution
   - No network requests

## Version History

- **v1.0.0** (2026-02-24)
  - Initial release
  - Basic migration functionality
  - Error handling
  - Logging and verification
  - Integration features

## License

This script is provided as-is for use with the tulima_php project.

## Support

For issues or questions:
1. Review this documentation
2. Check the log files
3. Use the `--help` flag for usage information
4. Contact project maintainers

## Changelog

### Version 1.0.0 (2026-02-24)
- Initial implementation
- Complete migration functionality
- Comprehensive error handling
- Integration features
- Logging and verification
