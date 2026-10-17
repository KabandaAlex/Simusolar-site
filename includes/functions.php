<?php
/**
 * Core Functions for Tulima Solar
 * 
 * This file contains essential helper functions used throughout the application.
 * 
 * @version 1.0.0
 */

if (!defined('APP_STARTED')) {
    define('APP_STARTED', true);
}

/**
 * Escape HTML entities for safe output
 * 
 * @param string|null $string The string to escape
 * @return string The escaped string
 */
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Get a setting value from the database
 *
 * @param PDO $pdo Database connection
 * @param string $key Setting key
 * @param mixed $default Default value if not found
 * @return mixed The setting value or default
 */
if (!function_exists('getSetting')) {
    function getSetting(PDO $pdo, string $key, $default = null) {
        try {
            $stmt = $pdo->prepare('SELECT value FROM site_settings WHERE `key` = ?');
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['value'] : $default;
        } catch (PDOException $e) {
            error_log('Error getting setting: ' . $e->getMessage());
            return $default;
        }
    }
}

/**
 * Get navigation items from the database
 *
 * @param PDO $pdo Database connection
 * @param string $location Navigation location ('main', 'footer', etc.)
 * @param bool $activeOnly Whether to return only active items
 * @return array Array of navigation items
 */
if (!function_exists('getNavigationItems')) {
    function getNavigationItems(PDO $pdo, string $location = 'main', bool $activeOnly = true): array {
        try {
            $sql = '
                SELECT id, title, url, target, parent_id, sort_order, icon as icon_class, page_id
                FROM menu_items
                WHERE menu_type = ?
            ';
            
            if ($activeOnly) {
                $sql .= ' AND is_active = 1';
            }
            
            $sql .= ' ORDER BY sort_order ASC';
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$location]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error getting navigation items: ' . $e->getMessage());
            return [];
        }
    }
}

/**
 * Build a tree of navigation items from a flat list (parent_id -> children)
 *
 * @param array $items Flat list of items with id, parent_id, etc.
 * @return array Top-level items with 'children' key where applicable
 */
if (!function_exists('buildNavigationTree')) {
    function buildNavigationTree(array $items): array {
        $byId = [];
        foreach ($items as $item) {
            $item['children'] = [];
            $byId[$item['id']] = $item;
        }
        $tree = [];
        foreach ($byId as $id => $item) {
            $parentId = $item['parent_id'] ?? null;
            if (empty($parentId)) {
                $tree[] = &$byId[$id];
            } else {
                if (isset($byId[$parentId])) {
                    $byId[$parentId]['children'][] = &$byId[$id];
                } else {
                    $tree[] = &$byId[$id];
                }
            }
        }
        return $tree;
    }
}

/**
 * Get a page by slug from the database
 *
 * @param PDO $pdo Database connection
 * @param string $slug Page slug
 * @return array|null Page data or null if not found
 */
if (!function_exists('getPageBySlug')) {
    function getPageBySlug(PDO $pdo, string $slug): ?array {
        try {
            $stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = ? AND is_active = 1');
            $stmt->execute([$slug]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log('Error getting page: ' . $e->getMessage());
            return null;
        }
    }
}

/**
 * Get a page by full path from the database (for nested pages)
 *
 * @param PDO $pdo Database connection
 * @param string $path Full page path (e.g., 'products/solar-pumps')
 * @return array|null Page data or null if not found
 */
if (!function_exists('getPageByFullPath')) {
    function getPageByFullPath(PDO $pdo, string $path): ?array {
        try {
            // Try to find by full path as slug first
            $stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = ? AND is_active = 1');
            $stmt->execute([$path]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                return $result;
            }
            
            // Try to find by last segment of path
            $segments = explode('/', $path);
            $lastSegment = end($segments);
            $stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = ? AND is_active = 1');
            $stmt->execute([$lastSegment]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log('Error getting page by full path: ' . $e->getMessage());
            return null;
        }
    }
}

/**
 * Get sections for a page from the database
 *
 * @param PDO $pdo Database connection
 * @param int $pageId Page ID
 * @return array Array of sections
 */
if (!function_exists('getPageSections')) {
    function getPageSections(PDO $pdo, int $pageId): array {
        try {
            $stmt = $pdo->prepare('
                SELECT * FROM page_sections
                WHERE page_id = ?
                ORDER BY sort_order ASC
            ');
            $stmt->execute([$pageId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error getting page sections: ' . $e->getMessage());
            return [];
        }
    }
}

/**
 * Get a legal page (privacy, terms, cookies, sitemap) by key
 *
 * @param PDO $pdo Database connection
 * @param string $pageKey Legal page key (e.g. 'privacy', 'terms', 'cookies', 'sitemap')
 * @return array|null Legal page row or null if not found
 */
if (!function_exists('getLegalPage')) {
    function getLegalPage(PDO $pdo, string $pageKey): ?array {
        try {
            $stmt = $pdo->prepare('SELECT * FROM legal_pages WHERE page_key = ? LIMIT 1');
            $stmt->execute([$pageKey]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log('Error getting legal page: ' . $e->getMessage());
            return null;
        }
    }
}

/**
 * Get a page by ID
 *
 * @param PDO $pdo Database connection
 * @param int $id Page ID
 * @return array|null Page data or null if not found
 */
if (!function_exists('getPageById')) {
    function getPageById(PDO $pdo, int $id): ?array {
        try {
            $stmt = $pdo->prepare('SELECT * FROM pages WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $page = $stmt->fetch(PDO::FETCH_ASSOC);
            return $page ?: null;
        } catch (PDOException $e) {
            error_log('Error getting page by id: ' . $e->getMessage());
            return null;
        }
    }
}

/**
 * Check if a page slug is available (optionally excluding a page ID)
 *
 * @param PDO $pdo Database connection
 * @param string $slug Slug to check
 * @param int|null $excludeId Page ID to exclude from the check
 * @return bool True if slug is available
 */
if (!function_exists('isSlugAvailable')) {
    function isSlugAvailable(PDO $pdo, string $slug, ?int $excludeId = null): bool {
        try {
            $sql = 'SELECT COUNT(*) FROM pages WHERE slug = ?';
            $params = [$slug];

            if ($excludeId !== null) {
                $sql .= ' AND id != ?';
                $params[] = $excludeId;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn() === 0;
        } catch (PDOException $e) {
            error_log('Error checking slug availability: ' . $e->getMessage());
            return false;
        }
    }
}

/**
 * Generate a unique slug for a page
 *
 * @param PDO $pdo Database connection
 * @param string $titleOrSlug Base title or slug
 * @param int|null $excludeId Optional page ID to exclude from uniqueness check
 * @return string Unique slug
 */
if (!function_exists('generateUniqueSlug')) {
    function generateUniqueSlug(PDO $pdo, string $titleOrSlug, ?int $excludeId = null): string {
        $base = generateSlug($titleOrSlug);
        if ($base === '') {
            $base = 'page';
        }

        $slug = $base;
        $suffix = 1;

        while (!isSlugAvailable($pdo, $slug, $excludeId)) {
            $slug = $base . '-' . $suffix;
            $suffix++;

            if ($suffix > 1000) {
                break;
            }
        }

        return $slug;
    }
}

/**
 * Create a new page
 *
 * @param PDO $pdo Database connection
 * @param array $data Page data
 * @return int New page ID (0 on failure)
 */
if (!function_exists('createPage')) {
    function createPage(PDO $pdo, array $data): int {
        try {
            $allowed = [
                'slug',
                'parent_id',
                'title',
                'meta_description',
                'meta_title',
                'sort_order',
                'is_active',
                'show_in_footer',
                'template_type',
                'custom_template',
                'layout_file',
                'requires_auth',
                'hero_title',
                'hero_subtitle',
                'hero_cta_text',
                'hero_cta_url',
                'hero_cta_secondary_text',
                'hero_cta_secondary_url',
                'hero_video_path',
            ];

            $fields = [];
            $placeholders = [];
            $values = [];

            foreach ($allowed as $column) {
                if (array_key_exists($column, $data)) {
                    $fields[] = $column;
                    $placeholders[] = '?';
                    $values[] = $data[$column];
                }
            }

            if (empty($fields)) {
                return 0;
            }

            $sql = 'INSERT INTO pages (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log('Error creating page: ' . $e->getMessage());
            return 0;
        }
    }
}

/**
 * Update an existing page
 *
 * @param PDO $pdo Database connection
 * @param int $id Page ID
 * @param array $data Page data
 * @return bool True on success
 */
if (!function_exists('updatePage')) {
    function updatePage(PDO $pdo, int $id, array $data): bool {
        try {
            $allowed = [
                'slug',
                'parent_id',
                'title',
                'meta_description',
                'meta_title',
                'sort_order',
                'is_active',
                'show_in_footer',
                'template_type',
                'custom_template',
                'layout_file',
                'requires_auth',
                'hero_title',
                'hero_subtitle',
                'hero_cta_text',
                'hero_cta_url',
                'hero_cta_secondary_text',
                'hero_cta_secondary_url',
                'hero_video_path',
            ];

            $sets = [];
            $values = [];

            foreach ($allowed as $column) {
                if (array_key_exists($column, $data)) {
                    $sets[] = $column . ' = ?';
                    $values[] = $data[$column];
                }
            }

            if (empty($sets)) {
                return false;
            }

            $values[] = $id;

            $sql = 'UPDATE pages SET ' . implode(', ', $sets) . ' WHERE id = ?';
            $stmt = $pdo->prepare($sql);

            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log('Error updating page: ' . $e->getMessage());
            return false;
        }
    }
}

/**
 * Delete a page
 *
 * @param PDO $pdo Database connection
 * @param int $id Page ID
 * @return bool True on success
 */
if (!function_exists('deletePage')) {
    function deletePage(PDO $pdo, int $id): bool {
        try {
            $stmt = $pdo->prepare('DELETE FROM pages WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log('Error deleting page: ' . $e->getMessage());
            return false;
        }
    }
}

/**
 * Duplicate a page (and its sections)
 *
 * @param PDO $pdo Database connection
 * @param int $sourceId Source page ID
 * @param string $newTitle Title for duplicated page
 * @return int|null New page ID or null on failure
 */
if (!function_exists('duplicatePage')) {
    function duplicatePage(PDO $pdo, int $sourceId, string $newTitle): ?int {
        try {
            $source = getPageById($pdo, $sourceId);
            if (!$source) {
                return null;
            }

            $pdo->beginTransaction();

            // Prepare new page data
            $data = $source;
            unset($data['id'], $data['updated_at']);

            $data['title'] = $newTitle;
            $data['slug'] = generateUniqueSlug($pdo, $newTitle);
            $data['is_active'] = 0;

            $newPageId = createPage($pdo, $data);
            if (!$newPageId) {
                $pdo->rollBack();
                return null;
            }

            // Duplicate sections for the page
            $stmt = $pdo->prepare('SELECT * FROM page_sections WHERE page_id = ? ORDER BY sort_order, id');
            $stmt->execute([$sourceId]);
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($sections as $section) {
                unset($section['id'], $section['updated_at']);
                $section['page_id'] = $newPageId;

                $columns = array_keys($section);
                $placeholders = array_fill(0, count($columns), '?');
                $values = array_values($section);

                $sql = 'INSERT INTO page_sections (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
                $insertStmt = $pdo->prepare($sql);
                $insertStmt->execute($values);
            }

            $pdo->commit();
            return $newPageId;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error duplicating page: ' . $e->getMessage());
            return null;
        }
    }
}

/**
 * Get available page templates
 *
 * @param PDO $pdo Database connection
 * @return array List of templates
 */
if (!function_exists('getPageTemplates')) {
    function getPageTemplates(PDO $pdo): array {
        try {
            $stmt = $pdo->query('
                SELECT *
                FROM page_templates
                WHERE is_active = 1
                ORDER BY sort_order ASC, id ASC
            ');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error getting page templates: ' . $e->getMessage());
            return [];
        }
    }
}

/**
 * Get contact submissions from the database
 *
 * @param PDO $pdo Database connection
 * @param array $filters Optional filters (status, search, date_from, date_to)
 * @return array Array of contact submissions
 */
if (!function_exists('getContactSubmissions')) {
    function getContactSubmissions(PDO $pdo, array $filters = []): array {
        try {
            $sql = 'SELECT * FROM contact_submissions WHERE 1=1';
            $params = [];
            
            if (!empty($filters['status'])) {
                $sql .= ' AND status = ?';
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= ' AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)';
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= ' AND submitted_at >= ?';
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= ' AND submitted_at <= ?';
                $params[] = $filters['date_to'];
            }
            
            $sql .= ' ORDER BY submitted_at DESC';
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error getting contact submissions: ' . $e->getMessage());
            return [];
        }
    }
}

/**
 * Format a date string
 *
 * @param string $dateString Date string
 * @param string $format Output format
 * @return string Formatted date
 */
if (!function_exists('formatDate')) {
    function formatDate(string $dateString, string $format = 'F j, Y'): string {
        try {
            $date = new DateTime($dateString);
            return $date->format($format);
        } catch (Exception $e) {
            return $dateString;
        }
    }
}

/**
 * Generate a URL-friendly slug from a string
 * 
 * @param string $string Input string
 * @return string URL-friendly slug
 */
if (!function_exists('generateSlug')) {
    function generateSlug(string $string): string {
        // Convert to lowercase
        $slug = strtolower($string);
        // Replace non-alphanumeric characters with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        // Remove leading/trailing hyphens
        $slug = trim($slug, '-');
        return $slug;
    }
}

/**
 * Truncate a string to a specified length
 * 
 * @param string $string Input string
 * @param int $length Maximum length
 * @param string $append String to append if truncated
 * @return string Truncated string
 */
if (!function_exists('truncate')) {
    function truncate(string $string, int $length = 100, string $append = '...'): string {
        if (strlen($string) <= $length) {
            return $string;
        }
        return substr($string, 0, $length) . $append;
    }
}

/**
 * Check if the current request is an AJAX request
 * 
 * @return bool True if AJAX request
 */
if (!function_exists('isAjax')) {
    function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

/**
 * Return JSON response and exit
 * 
 * @param array $data Response data
 * @param int $statusCode HTTP status code
 */
if (!function_exists('jsonResponse')) {
    function jsonResponse(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

/**
 * Redirect to a URL
 * 
 * @param string $url Target URL
 * @param int $statusCode HTTP status code
 */
if (!function_exists('redirect')) {
    function redirect(string $url, int $statusCode = 302): void {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }
}

/**
 * Get the current URL
 * 
 * @return string Current URL
 */
if (!function_exists('getCurrentUrl')) {
    function getCurrentUrl(): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
}

/**
 * Sanitize input string
 * 
 * @param string $input Input string
 * @return string Sanitized string
 */
if (!function_exists('sanitize')) {
    function sanitize(string $input): string {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Generate a random token
 * 
 * @param int $length Token length
 * @return string Random token
 */
if (!function_exists('generateToken')) {
    function generateToken(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if valid
 */
if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken(string $token): bool {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * Get client IP address
 * 
 * @return string Client IP address
 */
if (!function_exists('getClientIp')) {
    function getClientIp(): string {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }
}

/**
 * Check if a string starts with a given substring
 * 
 * @param string $haystack String to search in
 * @param string $needle String to search for
 * @return bool True if starts with needle
 */
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

/**
 * Check if a string ends with a given substring
 * 
 * @param string $haystack String to search in
 * @param string $needle String to search for
 * @return bool True if ends with needle
 */
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}
