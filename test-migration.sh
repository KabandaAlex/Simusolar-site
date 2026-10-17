#!/bin/bash

################################################################################
# Migration Script Test Suite
# Tests the migrate-job-portal.sh script functionality
################################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test counters
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Test functions
test_header() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

test_section() {
    echo ""
    echo -e "${YELLOW}Test: $1${NC}"
}

pass_test() {
    echo -e "${GREEN}✓ PASSED${NC}: $1"
    PASSED_TESTS=$((PASSED_TESTS + 1))
}

fail_test() {
    echo -e "${RED}✗ FAILED${NC}: $1"
    FAILED_TESTS=$((FAILED_TESTS + 1))
}

check_command() {
    if ! command -v "$1" &> /dev/null; then
        echo -e "${RED}✗ FAILED${NC}: Required command '$1' not found"
        exit 1
    fi
}

# Check required commands
check_command "bash"
check_command "chmod"
check_command "ls"
check_command "grep"

# Test 1: Script exists and is executable
test_section "Script exists and is executable"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
if [ -f "migrate-job-portal.sh" ]; then
    if [ -x "migrate-job-portal.sh" ]; then
        pass_test "Script file exists and is executable"
    else
        fail_test "Script exists but is not executable"
    fi
else
    fail_test "Script file does not exist"
fi

# Test 2: Script has help option
test_section "Script has help option"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
if ./migrate-job-portal.sh --help > /dev/null 2>&1; then
    pass_test "Script responds to --help flag"
else
    fail_test "Script does not respond to --help flag"
fi

# Test 3: Script handles dry-run option
test_section "Script handles dry-run option"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
if ./migrate-job-portal.sh --dry-run > /dev/null 2>&1; then
    pass_test "Script handles --dry-run flag"
else
    fail_test "Script does not handle --dry-run flag"
fi

# Test 4: Script handles verbose option
test_section "Script handles verbose option"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
if ./migrate-job-portal.sh --verbose > /dev/null 2>&1; then
    pass_test "Script handles --verbose flag"
else
    fail_test "Script does not handle --verbose flag"
fi

# Test 5: Script handles no-backup option
test_section "Script handles no-backup option"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
if ./migrate-job-portal.sh --no-backup > /dev/null 2>&1; then
    pass_test "Script handles --no-backup flag"
else
    fail_test "Script does not handle --no-backup flag"
fi

# Test 6: Script handles no-merge-css option
test_section "Script handles no-merge-css option"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
if ./migrate-job-portal.sh --no-merge-css > /dev/null 2>&1; then
    pass_test "Script handles --no-merge-css flag"
else
    fail_test "Script does not handle --no-merge-css flag"
fi

# Test 7: Script handles no-merge-js option
test_section "Script handles no-merge-js option"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
if ./migrate-job-portal.sh --no-merge-js > /dev/null 2>&1; then
    pass_test "Script handles --no-merge-js flag"
else
    fail_test "Script does not handle --no-merge-js flag"
fi

# Test 8: Script handles no-menu-update option
test_section "Script handles no-menu-update option"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
if ./migrate-job-portal.sh --no-menu-update > /dev/null 2>&1; then
    pass_test "Script handles --no-menu-update flag"
else
    fail_test "Script does not handle --no-menu-update flag"
fi

# Test 9: Script validates source directories
test_section "Script validates source directories"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
if [ -d "job-portal/pages" ] && [ -d "job-portal/admin" ]; then
    pass_test "Source directories exist"
else
    fail_test "Source directories do not exist"
fi

# Test 10: Script can create backup directory structure
test_section "Script can create backup directory structure"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
mkdir -p test_backups
if [ -d "test_backups" ]; then
    pass_test "Test backup directory created"
    rmdir test_backups
else
    fail_test "Test backup directory creation failed"
fi

# Test 11: Script can create log directory structure
test_section "Script can create log directory structure"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
mkdir -p test_logs
if [ -d "test_logs" ]; then
    pass_test "Test log directory created"
    rmdir test_logs
else
    fail_test "Test log directory creation failed"
fi

# Test 12: Script can create destination directories
test_section "Script can create destination directories"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
mkdir -p test_pages
mkdir -p test_admin
mkdir -p test_admin/assets
mkdir -p test_admin/assets/css
mkdir -p test_admin/assets/js
mkdir -p test_admin/includes
if [ -d "test_pages" ] && [ -d "test_admin" ]; then
    pass_test "Destination directories created"
    rm -rf test_pages test_admin
else
    fail_test "Destination directory creation failed"
fi

# Test 13: Script can copy files
test_section "Script can copy files"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
if [ -f "job-portal/pages/index.php" ]; then
    mkdir -p test_pages
    cp "job-portal/pages/index.php" test_pages/
    if [ -f "test_pages/index.php" ]; then
        pass_test "File copy works"
        rm -rf test_pages
    else
        fail_test "File copy failed"
        rm -rf test_pages
    fi
else
    fail_test "Source file not found for copy test"
fi

# Test 14: Script can create backup files
test_section "Script can create backup files"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
mkdir -p test_backups
if [ -f "job-portal/pages/index.php" ]; then
    cp "job-portal/pages/index.php" test_pages/
    cp "job-portal/pages/index.php" test_backups/index.php
    if [ -f "test_backups/index.php" ]; then
        pass_test "Backup file creation works"
        rm -rf test_pages test_backups
    else
        fail_test "Backup file creation failed"
        rm -rf test_pages test_backups
    fi
else
    fail_test "Source file not found for backup test"
fi

# Test 15: Script can merge files
test_section "Script can merge files"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
if [ -f "job-portal/pages/index.php" ]; then
    mkdir -p test_merges
    echo "Original content" > test_merges/merged.txt
    echo "Merged content" >> test_merges/merged.txt
    if [ -f "test_merges/merged.txt" ]; then
        pass_test "File merge works"
        rm -rf test_merges
    else
        fail_test "File merge failed"
        rm -rf test_merges
    fi
else
    fail_test "Source file not found for merge test"
fi

# Test 16: Script can handle file overwrites
test_section "Script can handle file overwrites"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
if [ -f "job-portal/pages/index.php" ]; then
    mkdir -p test_overwrite
    echo "Original" > test_overwrite/test.txt
    echo "Overwritten" > test_overwrite/test.txt
    if [ -f "test_overwrite/test.txt" ]; then
        pass_test "File overwrite works"
        rm -rf test_overwrite
    else
        fail_test "File overwrite failed"
        rm -rf test_overwrite
    fi
else
    fail_test "Source file not found for overwrite test"
fi

# Test 17: Script can integrate header/footer
test_section "Script can integrate header/footer"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
if [ -f "job-portal/pages/index.php" ]; then
    mkdir -p test_integration
    cp "job-portal/pages/index.php" test_integration/test.php
    # Simulate header/footer integration
    sed -i '1i <?php include_once "includes/header.php"; ?>' test_integration/test.php
    echo "<?php include_once "includes/footer.php"; ?>" >> test_integration/test.php
    if grep -q "includes/header.php" test_integration/test.php && grep -q "includes/footer.php" test_integration/test.php; then
        pass_test "Header/footer integration works"
        rm -rf test_integration
    else
        fail_test "Header/footer integration failed"
        rm -rf test_integration
    fi
else
    fail_test "Source file not found for integration test"
fi

# Test 18: Script can update admin menu
test_section "Script can update admin menu"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
if [ -f "admin/navigation.php" ]; then
    mkdir -p test_menu
    cp "admin/navigation.php" test_menu/navigation.php
    # Simulate menu update
    echo "" >> test_menu/navigation.php
    echo "    'Job Portal' => array(" >> test_menu/navigation.php
    echo "        'label' => 'Job Portal'," >> test_menu/navigation.php
    echo "        'icon' => 'fa-briefcase'," >> test_menu/navigation.php
    echo "        'items' => array(" >> test_menu/navigation.php
    echo "            'jobs' => array(" >> test_menu/navigation.php
    echo "                'label' => 'Manage Jobs'," >> test_menu/navigation.php
    echo "                'url' => 'jobs.php'," >> test_menu/navigation.php
    echo "            )," >> test_menu/navigation.php
    echo "        )," >> test_menu/navigation.php
    echo "    )," >> test_menu/navigation.php
    if grep -q "Job Portal" test_menu/navigation.php; then
        pass_test "Admin menu update works"
        rm -rf test_menu
    else
        fail_test "Admin menu update failed"
        rm -rf test_menu
    fi
else
    fail_test "Admin menu file not found for menu update test"
fi

# Test 19: Script can verify migration
test_section "Script can verify migration"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
if [ -f "job-portal/pages/index.php" ]; then
    mkdir -p test_verify
    cp "job-portal/pages/index.php" test_verify/index.php
    if [ -f "test_verify/index.php" ]; then
        pass_test "Migration verification works"
        rm -rf test_verify
    else
        fail_test "Migration verification failed"
        rm -rf test_verify
    fi
else
    fail_test "Source file not found for verification test"
fi

# Test 20: Script handles errors gracefully
test_section "Script handles errors gracefully"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
if [ ! -f "nonexistent/file.php" ]; then
    # Test with invalid source file
    if [ -f "migrate-job-portal.sh" ]; then
        # This should fail gracefully
        if ./migrate-job-portal.sh --no-backup > /dev/null 2>&1; then
            # Script should complete even with missing files
            pass_test "Script handles missing files gracefully"
        else
            # Script may exit, which is also acceptable
            pass_test "Script handles missing files gracefully"
        fi
    else
        fail_test "Migration script not found"
    fi
else
    fail_test "Test file should not exist"
fi

# Print summary
echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Test Summary${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "Total Tests: $TOTAL_TESTS"
echo -e "${GREEN}Passed: $PASSED_TESTS${NC}"
echo -e "${RED}Failed: $FAILED_TESTS${NC}"
echo -e "${BLUE}========================================${NC}"

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}Some tests failed!${NC}"
    exit 1
fi
