<?php
/**
 * RBAC (Role-Based Access Control) Helper Functions
 * 
 * This file provides permission checking functions for the admin panel.
 * Roles hierarchy: admin > editor > author
 * 
 * Permissions:
 * - admin: Full access to all features including user management
 * - editor: Can manage all content (posts, categories, tags, comments)
 * - author: Can create/edit own posts only, view engagement
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../../config/path.php';
}

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/database.php';

// Role hierarchy for comparison
const ROLE_HIERARCHY = [
    'admin' => 100,
    'editor' => 50,
    'author' => 10
];

// Permission definitions by role
const ROLE_PERMISSIONS = [
    'admin' => [
        'blog_manage_categories',
        'blog_manage_tags',
        'blog_manage_posts',
        'blog_edit_any_post',
        'blog_manage_comments',
        'blog_view_engagement',
        'blog_delete_content',
        'user_manage',
        'settings_manage'
    ],
    'editor' => [
        'blog_manage_categories',
        'blog_manage_tags',
        'blog_manage_posts',
        'blog_edit_any_post',
        'blog_manage_comments',
        'blog_view_engagement',
        'blog_delete_content'
    ],
    'author' => [
        'blog_manage_posts',
        'blog_edit_own_post',
        'blog_view_engagement'
    ]
];

/**
 * Get the current admin user's role from database
 * 
 * @return string|null Role name or null if not logged in
 */
function getCurrentAdminRole(): ?string {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    // Check if role is already in session
    if (isset($_SESSION['admin_role']) && !empty($_SESSION['admin_role'])) {
        return $_SESSION['admin_role'];
    }
    
    // Fetch role from database and cache in session
    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT role FROM admins WHERE id = ?');
    $stmt->execute([getAdminId()]);
    $row = $stmt->fetch();
    
    if ($row && !empty($row['role'])) {
        $_SESSION['admin_role'] = $row['role'];
        return $row['role'];
    }
    
    // Default to admin for backward compatibility (existing admins)
    $_SESSION['admin_role'] = 'admin';
    return 'admin';
}

/**
 * Get role level from hierarchy
 * 
 * @param string $role Role name
 * @return int Role level (higher = more permissions)
 */
function getRoleLevel(string $role): int {
    return ROLE_HIERARCHY[$role] ?? 0;
}

/**
 * Check if current user has a specific role or higher
 * 
 * @param string $requiredRole Minimum required role
 * @return bool
 */
function hasRole(string $requiredRole): bool {
    $currentRole = getCurrentAdminRole();
    if (!$currentRole) {
        return false;
    }
    
    return getRoleLevel($currentRole) >= getRoleLevel($requiredRole);
}

/**
 * Check if current user has a specific permission
 * 
 * @param string $permission Permission to check
 * @return bool
 */
function hasPermission(string $permission): bool {
    $role = getCurrentAdminRole();
    if (!$role) {
        return false;
    }
    
    $permissions = ROLE_PERMISSIONS[$role] ?? [];
    return in_array($permission, $permissions);
}

/**
 * Require a specific permission, redirect if lacking
 * 
 * @param string $permission Permission required
 * @param string $redirectUrl URL to redirect to on failure
 */
function requirePermission(string $permission, string $redirectUrl = 'index.php'): void {
    if (!hasPermission($permission)) {
        $_SESSION['flash_message'] = 'You do not have permission to access this page.';
        $_SESSION['flash_type'] = 'error';
        header('Location: ' . $redirectUrl);
        exit;
    }
}

/**
 * Require a minimum role level, redirect if lacking
 * 
 * @param string $role Minimum required role
 * @param string $redirectUrl URL to redirect to on failure
 */
function requireRole(string $role, string $redirectUrl = 'index.php'): void {
    if (!hasRole($role)) {
        $_SESSION['flash_message'] = 'You do not have permission to access this page.';
        $_SESSION['flash_type'] = 'error';
        header('Location: ' . $redirectUrl);
        exit;
    }
}

/**
 * Check if user is an admin (full access)
 * 
 * @return bool
 */
function isBlogAdmin(): bool {
    return getCurrentAdminRole() === 'admin';
}

/**
 * Check if user is an editor or higher
 * 
 * @return bool
 */
function isEditor(): bool {
    return hasRole('editor');
}

/**
 * Check if user is an author or higher
 * 
 * @return bool
 */
function isAuthor(): bool {
    return hasRole('author');
}

/**
 * Check if user can manage blog categories
 * 
 * @return bool
 */
function canManageCategories(): bool {
    return hasPermission('blog_manage_categories');
}

/**
 * Check if user can manage blog tags
 * 
 * @return bool
 */
function canManageTags(): bool {
    return hasPermission('blog_manage_tags');
}

/**
 * Check if user can manage blog posts
 * 
 * @return bool
 */
function canManagePosts(): bool {
    return hasPermission('blog_manage_posts');
}

/**
 * Check if user can manage comments
 * 
 * @return bool
 */
function canManageComments(): bool {
    return hasPermission('blog_manage_comments');
}

/**
 * Check if user can view engagement analytics
 * 
 * @return bool
 */
function canViewEngagement(): bool {
    return hasPermission('blog_view_engagement');
}

/**
 * Check if user can edit a specific post
 * Authors can only edit their own posts, editors/admins can edit any
 * 
 * @param int $postId Post ID to check
 * @return bool
 */
function canEditPost(int $postId): bool {
    // Editors and admins can edit any post
    if (hasPermission('blog_edit_any_post')) {
        return true;
    }
    
    // Authors can only edit their own posts
    if (hasPermission('blog_edit_own_post')) {
        $pdo = getDb();
        $stmt = $pdo->prepare('SELECT created_by FROM blog_posts WHERE id = ?');
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        
        return $post && $post['created_by'] == getAdminId();
    }
    
    return false;
}

/**
 * Check if user can delete blog content
 * 
 * @return bool
 */
function canDeleteContent(): bool {
    return hasPermission('blog_delete_content');
}

/**
 * Require category management permission
 */
function requireCategoryAccess(): void {
    requirePermission('blog_manage_categories', 'index.php');
}

/**
 * Require tag management permission
 */
function requireTagAccess(): void {
    requirePermission('blog_manage_tags', 'index.php');
}

/**
 * Require post management permission (minimum author)
 */
function requirePostAccess(): void {
    requireRole('author', 'index.php');
}

/**
 * Require comment management permission
 */
function requireCommentAccess(): void {
    requirePermission('blog_manage_comments', 'index.php');
}

/**
 * Require ability to edit a specific post
 * 
 * @param int $postId Post ID
 */
function requirePostEditAccess(int $postId): void {
    if (!canEditPost($postId)) {
        $_SESSION['flash_message'] = 'You can only edit your own posts.';
        $_SESSION['flash_type'] = 'error';
        header('Location: blog_posts.php');
        exit;
    }
}

/**
 * Get all available roles
 * 
 * @return array List of role names
 */
function getAllRoles(): array {
    return array_keys(ROLE_HIERARCHY);
}

/**
 * Get role display name
 * 
 * @param string $role Role key
 * @return string Human-readable role name
 */
function getRoleDisplayName(string $role): string {
    $names = [
        'admin' => 'Administrator',
        'editor' => 'Editor',
        'author' => 'Author'
    ];
    
    return $names[$role] ?? ucfirst($role);
}

/**
 * Get role description
 * 
 * @param string $role Role key
 * @return string Role description
 */
function getRoleDescription(string $role): string {
    $descriptions = [
        'admin' => 'Full access to all features including user management and settings',
        'editor' => 'Can manage all content: posts, categories, tags, and comments',
        'author' => 'Can create and edit own posts, view engagement reports'
    ];
    
    return $descriptions[$role] ?? '';
}

/**
 * Get current admin user info
 * 
 * @return array|null User info array or null
 */
function getCurrentAdminInfo(): ?array {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    static $userInfo = null;
    
    if ($userInfo === null) {
        $pdo = getDb();
        $stmt = $pdo->prepare('SELECT id, username, email, role, created_at FROM admins WHERE id = ?');
        $stmt->execute([getAdminId()]);
        $userInfo = $stmt->fetch() ?: null;
    }
    
    return $userInfo;
}
