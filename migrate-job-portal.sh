#!/bin/bash

################################################################################
# Job Portal Migration Script
# Migrates job-portal structure into tulima_php project with error handling
# and integration features
################################################################################

set -e  # Exit on error
set -u  # Exit on undefined variable

################################################################################
# Configuration
################################################################################

SCRIPT_NAME="$(basename "$0")"
SCRIPT_VERSION="1.0.0"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="backups/migration_${TIMESTAMP}"
LOG_FILE="logs/migration_${TIMESTAMP}.log"
VERBOSE=false
DRY_RUN=false
BACKUP_FILES=true
MERGE_CSS=true
MERGE_JS=true
UPDATE_ADMIN_MENU=true

# Source and destination paths
SOURCE_DIR="job-portal"
DEST_PAGES_DIR="pages"
DEST_ADMIN_DIR="admin"
DEST_ADMIN_ASSETS_DIR="admin/assets"
DEST_ADMIN_INCLUDES_DIR="admin/includes"

# Source paths
SOURCE_PAGES_DIR="${SOURCE_DIR}/pages"
SOURCE_ADMIN_DIR="${SOURCE_DIR}/admin"
SOURCE_ADMIN_ASSETS_DIR="${SOURCE_DIR}/admin/assets"
SOURCE_ADMIN_INCLUDES_DIR="${SOURCE_DIR}/admin/includes"

# Destination paths
DEST_PAGES_DIR_FULL="${DEST_PAGES_DIR}"
DEST_ADMIN_DIR_FULL="${DEST_ADMIN_DIR}"
DEST_ADMIN_ASSETS_DIR_FULL="${DEST_ADMIN_DIR}/${DEST_ADMIN_ASSETS_DIR}"
DEST_ADMIN_INCLUDES_DIR_FULL="${DEST_ADMIN_DIR}/${DEST_ADMIN_INCLUDES_DIR}"

# Files to migrate
PAGES_FILES=(
    "index.php"
    "careers.php"
    "apply.php"
    "job-detail.php"
)

ADMIN_FILES=(
    "jobs.php"
    "job-post.php"
)

ADMIN_ASSETS_CSS_FILES=(
    "admin-jobs.css"
)

ADMIN_ASSETS_JS_FILES=(
    "admin-jobs.js"
)

ADMIN_INCLUDES_FILES=(
    "layout.php"
)

################################################################################
# Logging Functions
################################################################################

log() {
    local level="$1"
    shift
    local message="$*"
    local timestamp=$(date +"%Y-%m-%d %H:%M:%S")
    echo "[${timestamp}] [${level}] ${message}" | tee -a "${LOG_FILE}"
}

log_info() {
    log "INFO" "$@"
}

log_warn() {
    log "WARN" "$@"
}

log_error() {
    log "ERROR" "$@"
}

log_success() {
    log "SUCCESS" "$@"
}

log_debug() {
    if [ "$VERBOSE" = true ]; then
        log "DEBUG" "$@"
    fi
}

################################################################################
# Error Handling Functions
################################################################################

error_exit() {
    log_error "$1"
    log_error "Migration failed at $(date)"
    exit 1
}

check_command() {
    if ! command -v "$1" &> /dev/null; then
        error_exit "Required command '$1' not found. Please install it."
    fi
}

################################################################################
# Directory and File Operations
################################################################################

create_directory() {
    local dir="$1"
    if [ ! -d "$dir" ]; then
        log_info "Creating directory: $dir"
        if [ "$DRY_RUN" = false ]; then
            mkdir -p "$dir" || error_exit "Failed to create directory: $dir"
        fi
    else
        log_debug "Directory already exists: $dir"
    fi
}

create_backup_dir() {
    log_info "Creating backup directory: $BACKUP_DIR"
    if [ "$DRY_RUN" = false ]; then
        mkdir -p "$BACKUP_DIR" || error_exit "Failed to create backup directory"
    fi
}

backup_file() {
    local source_file="$1"
    local backup_path="$2"
    
    if [ -f "$source_file" ]; then
        log_info "Backing up: $source_file -> $backup_path"
        if [ "$DRY_RUN" = false ]; then
            cp "$source_file" "$backup_path" || error_exit "Failed to backup file: $source_file"
        fi
    else
        log_warn "Source file not found: $source_file (skipping backup)"
    fi
}

verify_source_file() {
    local file="$1"
    if [ ! -f "$file" ]; then
        log_error "Source file not found: $file"
        return 1
    fi
    return 0
}

verify_source_directory() {
    local dir="$1"
    if [ ! -d "$dir" ]; then
        log_error "Source directory not found: $dir"
        return 1
    fi
    return 0
}

################################################################################
# File Migration Functions
################################################################################

copy_file() {
    local source_file="$1"
    local dest_dir="$2"
    local dest_file="${dest_dir}/$(basename "$source_file")"
    
    log_info "Copying: $source_file -> $dest_file"
    
    if [ "$DRY_RUN" = false ]; then
        # Create destination directory if needed
        create_directory "$(dirname "$dest_file")"
        
        # Check if destination file exists
        if [ -f "$dest_file" ]; then
            if [ "$BACKUP_FILES" = true ]; then
                local backup_file="${BACKUP_DIR}/$(basename "$dest_file")"
                backup_file "$dest_file" "$backup_file"
            fi
            
            log_warn "Destination file exists: $dest_file (will be overwritten)"
        fi
        
        # Copy the file
        cp "$source_file" "$dest_file" || error_exit "Failed to copy file: $source_file -> $dest_file"
    fi
    
    log_success "Copied: $source_file -> $dest_file"
}

merge_css_files() {
    local source_file="$1"
    local dest_file="$2"
    
    if [ ! -f "$source_file" ]; then
        log_warn "Source CSS file not found: $source_file (skipping merge)"
        return 1
    fi
    
    if [ ! -f "$dest_file" ]; then
        log_info "Destination CSS file not found, copying: $source_file -> $dest_file"
        copy_file "$source_file" "$(dirname "$dest_file")"
        return 0
    fi
    
    log_info "Merging CSS files: $source_file + $dest_file"
    
    if [ "$DRY_RUN" = false ]; then
        # Create backup
        local backup_file="${BACKUP_DIR}/$(basename "$dest_file")"
        backup_file "$dest_file" "$backup_file"
        
        # Append source CSS to destination CSS
        cat "$source_file" >> "$dest_file" || error_exit "Failed to merge CSS files"
        
        log_success "Merged CSS: $source_file -> $dest_file"
    fi
}

merge_js_files() {
    local source_file="$1"
    local dest_file="$2"
    
    if [ ! -f "$source_file" ]; then
        log_warn "Source JS file not found: $source_file (skipping merge)"
        return 1
    fi
    
    if [ ! -f "$dest_file" ]; then
        log_info "Destination JS file not found, copying: $source_file -> $dest_file"
        copy_file "$source_file" "$(dirname "$dest_file")"
        return 0
    fi
    
    log_info "Merging JS files: $source_file + $dest_file"
    
    if [ "$DRY_RUN" = false ]; then
        # Create backup
        local backup_file="${BACKUP_DIR}/$(basename "$dest_file")"
        backup_file "$dest_file" "$backup_file"
        
        # Append source JS to destination JS
        cat "$source_file" >> "$dest_file" || error_exit "Failed to merge JS files"
        
        log_success "Merged JS: $source_file -> $dest_file"
    fi
}

integrate_header_footer() {
    local page_file="$1"
    
    if [ ! -f "$page_file" ]; then
        log_warn "Page file not found: $page_file (skipping header/footer integration)"
        return 1
    fi
    
    log_info "Integrating header/footer into: $page_file"
    
    if [ "$DRY_RUN" = false ]; then
        # Check if page already has header/footer integration
        if grep -q "includes/header.php" "$page_file" && grep -q "includes/footer.php" "$page_file"; then
            log_debug "Page already has header/footer integration: $page_file"
            return 0
        fi
        
        # Create backup
        local backup_file="${BACKUP_DIR}/$(basename "$page_file")"
        backup_file "$page_file" "$backup_file"
        
        # Add header include at the top
        sed -i '1i <?php include_once "includes/header.php"; ?>' "$page_file" || error_exit "Failed to add header to: $page_file"
        
        # Add footer include at the end
        echo "<?php include_once "includes/footer.php"; ?>" >> "$page_file" || error_exit "Failed to add footer to: $page_file"
        
        log_success "Integrated header/footer into: $page_file"
    fi
}

update_admin_menu() {
    local menu_file="${DEST_ADMIN_DIR}/navigation.php"
    
    if [ ! -f "$menu_file" ]; then
        log_warn "Admin menu file not found: $menu_file (skipping menu update)"
        return 1
    fi
    
    log_info "Updating admin menu: $menu_file"
    
    if [ "$DRY_RUN" = false ]; then
        # Create backup
        local backup_file="${BACKUP_DIR}/navigation.php"
        backup_file "$menu_file" "$backup_file"
        
        # Check if job portal menu item already exists
        if grep -q "Job Portal" "$menu_file"; then
            log_debug "Job Portal menu item already exists: $menu_file"
            return 0
        fi
        
        # Find the appropriate location to insert (after "Pages" section)
        # This is a simplified approach - in production, you'd want more sophisticated logic
        local insertion_point=$(grep -n "Pages\|pages" "$menu_file" | head -1 | cut -d: -f1)
        
        if [ -z "$insertion_point" ]; then
            log_warn "Could not find insertion point in menu file, appending at end"
            # Append at the end
            echo "" >> "$menu_file"
            echo "    'Job Portal' => array(" >> "$menu_file"
            echo "        'label' => 'Job Portal'," >> "$menu_file"
            echo "        'icon' => 'fa-briefcase'," >> "$menu_file"
            echo "        'items' => array(" >> "$menu_file"
            echo "            'jobs' => array(" >> "$menu_file"
            echo "                'label' => 'Manage Jobs'," >> "$menu_file"
            echo "                'url' => 'jobs.php'," >> "$menu_file"
            echo "            )," >> "$menu_file"
            echo "            'job-post' => array(" >> "$menu_file"
            echo "                'label' => 'Post New Job'," >> "$menu_file"
            echo "                'url' => 'job-post.php'," >> "$menu_file"
            echo "            )," >> "$menu_file"
            echo "        )," >> "$menu_file"
            echo "    )," >> "$menu_file"
        else
            # Insert after the found line
            sed -i "${insertion_point}a\\    'Job Portal' => array(\\n        'label' => 'Job Portal',\\n        'icon' => 'fa-briefcase',\\n        'items' => array(\\n            'jobs' => array(\\n                'label' => 'Manage Jobs',\\n                'url' => 'jobs.php',\\n            ),\\n            'job-post' => array(\\n                'label' => 'Post New Job',\\n                'url' => 'job-post.php',\\n            ),\\n        ),\\n    )," "$menu_file"
        fi
        
        log_success "Updated admin menu: $menu_file"
    fi
}

################################################################################
# Verification Functions
################################################################################

verify_migration() {
    log_info "Verifying migration..."
    
    local errors=0
    
    # Verify pages migration
    for file in "${PAGES_FILES[@]}"; do
        local source="${SOURCE_PAGES_DIR}/${file}"
        local dest="${DEST_PAGES_DIR_FULL}/${file}"
        
        if [ ! -f "$dest" ]; then
            log_error "Verification failed: Destination file not found: $dest"
            errors=$((errors + 1))
        else
            log_debug "Verified: $dest"
        fi
    done
    
    # Verify admin files migration
    for file in "${ADMIN_FILES[@]}"; do
        local source="${SOURCE_ADMIN_DIR}/${file}"
        local dest="${DEST_ADMIN_DIR_FULL}/${file}"
        
        if [ ! -f "$dest" ]; then
            log_error "Verification failed: Destination file not found: $dest"
            errors=$((errors + 1))
        else
            log_debug "Verified: $dest"
        fi
    done
    
    # Verify admin assets
    for file in "${ADMIN_ASSETS_CSS_FILES[@]}"; do
        local source="${SOURCE_ADMIN_ASSETS_DIR}/${file}"
        local dest="${DEST_ADMIN_ASSETS_DIR_FULL}/${file}"
        
        if [ ! -f "$dest" ]; then
            log_error "Verification failed: Destination file not found: $dest"
            errors=$((errors + 1))
        else
            log_debug "Verified: $dest"
        fi
    done
    
    # Verify admin includes
    for file in "${ADMIN_INCLUDES_FILES[@]}"; do
        local source="${SOURCE_ADMIN_INCLUDES_DIR}/${file}"
        local dest="${DEST_ADMIN_INCLUDES_DIR_FULL}/${file}"
        
        if [ ! -f "$dest" ]; then
            log_error "Verification failed: Destination file not found: $dest"
            errors=$((errors + 1))
        else
            log_debug "Verified: $dest"
        fi
    done
    
    if [ $errors -gt 0 ]; then
        log_error "Migration verification failed with $errors error(s)"
        return 1
    else
        log_success "Migration verification completed successfully"
        return 0
    fi
}

show_summary() {
    echo ""
    echo "=========================================="
    echo "Migration Summary"
    echo "=========================================="
    echo "Version: $SCRIPT_VERSION"
    echo "Timestamp: $TIMESTAMP"
    echo "Log File: $LOG_FILE"
    echo "Backup Directory: $BACKUP_DIR"
    echo ""
    echo "Files Migrated:"
    echo "  Pages:"
    for file in "${PAGES_FILES[@]}"; do
        echo "    - $file"
    done
    echo "  Admin:"
    for file in "${ADMIN_FILES[@]}"; do
        echo "    - $file"
    done
    echo "  Admin Assets CSS:"
    for file in "${ADMIN_ASSETS_CSS_FILES[@]}"; do
        echo "    - $file"
    done
    echo "  Admin Assets JS:"
    for file in "${ADMIN_ASSETS_JS_FILES[@]}"; do
        echo "    - $file"
    done
    echo "  Admin Includes:"
    for file in "${ADMIN_INCLUDES_FILES[@]}"; do
        echo "    - $file"
    done
    echo ""
    echo "Features Enabled:"
    echo "  - Backup: $([ "$BACKUP_FILES" = true ] && echo "Yes" || echo "No")"
    echo "  - CSS Merge: $([ "$MERGE_CSS" = true ] && echo "Yes" || echo "No")"
    echo "  - JS Merge: $([ "$MERGE_JS" = true ] && echo "Yes" || echo "No")"
    echo "  - Header/Footer Integration: Yes"
    echo "  - Admin Menu Update: $([ "$UPDATE_ADMIN_MENU" = true ] && echo "Yes" || echo "No")"
    echo "  - Verbose Logging: $([ "$VERBOSE" = true ] && echo "Yes" || echo "No")"
    echo "  - Dry Run: $([ "$DRY_RUN" = true ] && echo "Yes" || echo "No")"
    echo "=========================================="
}

################################################################################
# Main Migration Function
################################################################################

migrate_job_portal() {
    log_info "=========================================="
    log_info "Starting Job Portal Migration"
    log_info "Version: $SCRIPT_VERSION"
    log_info "=========================================="
    
    # Create necessary directories
    create_directory "$DEST_PAGES_DIR_FULL"
    create_directory "$DEST_ADMIN_DIR_FULL"
    create_directory "$DEST_ADMIN_ASSETS_DIR_FULL"
    create_directory "$DEST_ADMIN_INCLUDES_DIR_FULL"
    create_directory "backups"
    create_directory "logs"
    
    # Create backup directory
    if [ "$BACKUP_FILES" = true ]; then
        create_backup_dir
    fi
    
    # Verify source directories
    log_info "Verifying source directories..."
    verify_source_directory "$SOURCE_PAGES_DIR" || error_exit "Source pages directory not found"
    verify_source_directory "$SOURCE_ADMIN_DIR" || error_exit "Source admin directory not found"
    verify_source_directory "$SOURCE_ADMIN_ASSETS_DIR" || error_exit "Source admin assets directory not found"
    verify_source_directory "$SOURCE_ADMIN_INCLUDES_DIR" || error_exit "Source admin includes directory not found"
    
    log_info "Source directories verified successfully"
    
    # Migrate pages
    log_info "Migrating pages..."
    for file in "${PAGES_FILES[@]}"; do
        local source="${SOURCE_PAGES_DIR}/${file}"
        local dest="${DEST_PAGES_DIR_FULL}/${file}"
        
        if verify_source_file "$source"; then
            copy_file "$source" "$DEST_PAGES_DIR_FULL"
            integrate_header_footer "$dest"
        fi
    done
    
    # Migrate admin files
    log_info "Migrating admin files..."
    for file in "${ADMIN_FILES[@]}"; do
        local source="${SOURCE_ADMIN_DIR}/${file}"
        local dest="${DEST_ADMIN_DIR_FULL}/${file}"
        
        if verify_source_file "$source"; then
            copy_file "$source" "$DEST_ADMIN_DIR_FULL"
        fi
    done
    
    # Migrate admin assets CSS
    log_info "Migrating admin assets CSS..."
    for file in "${ADMIN_ASSETS_CSS_FILES[@]}"; do
        local source="${SOURCE_ADMIN_ASSETS_DIR}/${file}"
        local dest="${DEST_ADMIN_ASSETS_DIR_FULL}/${file}"
        
        if verify_source_file "$source"; then
            if [ "$MERGE_CSS" = true ]; then
                # Merge with existing admin.css if it exists
                local master_css="${DEST_ADMIN_ASSETS_DIR_FULL}/admin.css"
                merge_css_files "$source" "$master_css"
            else
                copy_file "$source" "$DEST_ADMIN_ASSETS_DIR_FULL"
            fi
        fi
    done
    
    # Migrate admin assets JS
    log_info "Migrating admin assets JS..."
    for file in "${ADMIN_ASSETS_JS_FILES[@]}"; do
        local source="${SOURCE_ADMIN_ASSETS_DIR}/${file}"
        local dest="${DEST_ADMIN_ASSETS_DIR_FULL}/${file}"
        
        if verify_source_file "$source"; then
            if [ "$MERGE_JS" = true ]; then
                # Merge with existing admin.js if it exists
                local master_js="${DEST_ADMIN_ASSETS_DIR_FULL}/admin.js"
                merge_js_files "$source" "$master_js"
            else
                copy_file "$source" "$DEST_ADMIN_ASSETS_DIR_FULL"
            fi
        fi
    done
    
    # Migrate admin includes
    log_info "Migrating admin includes..."
    for file in "${ADMIN_INCLUDES_FILES[@]}"; do
        local source="${SOURCE_ADMIN_INCLUDES_DIR}/${file}"
        local dest="${DEST_ADMIN_INCLUDES_DIR_FULL}/${file}"
        
        if verify_source_file "$source"; then
            copy_file "$source" "$DEST_ADMIN_INCLUDES_DIR_FULL"
        fi
    done
    
    # Update admin menu
    if [ "$UPDATE_ADMIN_MENU" = true ]; then
        update_admin_menu
    fi
    
    # Verify migration
    log_info "Running verification..."
    verify_migration
    
    # Show summary
    show_summary
    
    log_info "=========================================="
    log_success "Migration completed successfully!"
    log_info "=========================================="
}

################################################################################
# Help Function
################################################################################

show_help() {
    cat << EOF
Usage: $SCRIPT_NAME [OPTIONS]

Migrate Job Portal structure into tulima_php project with error handling
and integration features.

Options:
    -h, --help              Show this help message
    -v, --verbose           Enable verbose logging
    -n, --dry-run           Perform a dry run (no actual changes)
    --no-backup             Skip creating backups
    --no-merge-css          Skip CSS file merging
    --no-merge-js           Skip JS file merging
    --no-menu-update        Skip admin menu update

Examples:
    $SCRIPT_NAME                    # Run migration with defaults
    $SCRIPT_NAME --verbose           # Run with verbose logging
    $SCRIPT_NAME --dry-run           # Test migration without changes
    $SCRIPT_NAME --no-backup         # Run without creating backups

EOF
}

################################################################################
# Parse Command Line Arguments
################################################################################

while [[ $# -gt 0 ]]; do
    case $1 in
        -h|--help)
            show_help
            exit 0
            ;;
        -v|--verbose)
            VERBOSE=true
            shift
            ;;
        -n|--dry-run)
            DRY_RUN=true
            shift
            ;;
        --no-backup)
            BACKUP_FILES=false
            shift
            ;;
        --no-merge-css)
            MERGE_CSS=false
            shift
            ;;
        --no-merge-js)
            MERGE_JS=false
            shift
            ;;
        --no-menu-update)
            UPDATE_ADMIN_MENU=false
            shift
            ;;
        *)
            log_error "Unknown option: $1"
            show_help
            exit 1
            ;;
    esac
done

################################################################################
# Main Execution
################################################################################

# Check required commands
check_command "mkdir"
check_command "cp"
check_command "sed"
check_command "grep"

# Run migration
migrate_job_portal

exit 0
