<?php
/**
 * Tulima Solar - Blog Helper Functions
 * 
 * This file contains helper functions specific to the blog system.
 * Used by admin blog management pages.
 * 
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('APP_STARTED')) {
    define('APP_STARTED', true);
}

/**
 * Sanitize HTML content for safe storage and display
 * 
 * Allows a predefined set of HTML tags and attributes while removing
 * potentially dangerous content like scripts and iframes.
 * 
 * @param string $html The HTML content to sanitize
 * @return string The sanitized HTML content
 */
if (!function_exists('sanitizeHtmlContent')) {
    function sanitizeHtmlContent($html) {
        // Return empty string if input is null or empty
        if (empty($html)) {
            return '';
        }
        
        // Define allowed HTML tags (suitable for blog content from TinyMCE)
        $allowedTags = '<p><br><strong><b><em><i><u><s><strike><sub><sup>' .
            '<h1><h2><h3><h4><h5><h6>' .
            '<ul><ol><li><dl><dt><dd>' .
            '<blockquote><pre><code>' .
            '<a><img><figure><figcaption>' .
            '<table><thead><tbody><tfoot><tr><th><td><caption>' .
            '<div><span><section><article><aside><header><footer><nav><main>' .
            '<hr><address><cite><q><abbr><time><mark><small><del><ins>';
        
        // Strip all tags except allowed ones
        $sanitized = strip_tags($html, $allowedTags);
        
        // Remove any on* event handlers (onclick, onload, onerror, etc.)
        $sanitized = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $sanitized);
        $sanitized = preg_replace('/\s+on\w+\s*=\s*[^\s>]*/i', '', $sanitized);
        
        // Remove javascript: URLs
        $sanitized = preg_replace('/javascript\s*:/i', '', $sanitized);
        
        // Remove data: URLs (can be used for XSS)
        $sanitized = preg_replace('/data\s*:/i', '', $sanitized);
        
        // Remove vbscript: URLs
        $sanitized = preg_replace('/vbscript\s*:/i', '', $sanitized);
        
        // Remove expression() CSS (IE specific XSS)
        $sanitized = preg_replace('/expression\s*\(/i', '', $sanitized);
        
        // Clean up any remaining dangerous attributes
        $sanitized = preg_replace('/\s+(style|class|id|data-\w+)\s*=\s*["\'][^"\']*expression[^"\']*["\']/i', '', $sanitized);
        
        return $sanitized;
    }
}

/**
 * Get blog post status badge HTML
 * 
 * Returns an HTML badge element for displaying post status.
 * 
 * @param string $status The post status (draft, published, scheduled, archived)
 * @return string HTML badge element
 */
if (!function_exists('getBlogStatusBadge')) {
    function getBlogStatusBadge($status) {
        $badges = [
            'draft' => '<span class="badge badge-draft">Draft</span>',
            'published' => '<span class="badge badge-published">Published</span>',
            'scheduled' => '<span class="badge badge-scheduled">Scheduled</span>',
            'archived' => '<span class="badge badge-archived">Archived</span>'
        ];
        
        return $badges[$status] ?? '<span class="badge badge-unknown">' . htmlspecialchars($status) . '</span>';
    }
}

/**
 * Format a blog date for display
 * 
 * @param string $dateString The date string to format
 * @param string $format The date format (default: 'F j, Y')
 * @return string Formatted date string
 */
if (!function_exists('formatBlogDate')) {
    function formatBlogDate($dateString, $format = 'F j, Y') {
        if (empty($dateString) || $dateString === '0000-00-00 00:00:00') {
            return '';
        }
        
        try {
            $date = new DateTime($dateString);
            return $date->format($format);
        } catch (Exception $e) {
            return '';
        }
    }
}

/**
 * Calculate estimated read time for blog content
 * 
 * @param string $content The blog content
 * @param int $wordsPerMinute Average reading speed (default: 200)
 * @return int Estimated read time in minutes
 */
if (!function_exists('calculateReadTime')) {
    function calculateReadTime($content, $wordsPerMinute = 200) {
        if (empty($content)) {
            return 1;
        }
        
        // Strip HTML tags and count words
        $plainText = strip_tags($content);
        $wordCount = str_word_count($plainText);
        
        // Calculate minutes, minimum 1 minute
        $minutes = max(1, ceil($wordCount / $wordsPerMinute));
        
        return $minutes;
    }
}

/**
 * Generate an excerpt from blog content
 * 
 * @param string $content The full blog content
 * @param int $maxLength Maximum length of excerpt (default: 160)
 * @return string Generated excerpt
 */
if (!function_exists('generateExcerpt')) {
    function generateExcerpt($content, $maxLength = 160) {
        if (empty($content)) {
            return '';
        }
        
        // Strip HTML tags
        $plainText = strip_tags($content);
        
        // Decode HTML entities
        $plainText = html_entity_decode($plainText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Collapse whitespace
        $plainText = preg_replace('/\s+/', ' ', $plainText);
        $plainText = trim($plainText);
        
        // Truncate to max length
        if (strlen($plainText) > $maxLength) {
            $plainText = substr($plainText, 0, $maxLength);
            // Find last space to avoid cutting words
            $lastSpace = strrpos($plainText, ' ');
            if ($lastSpace !== false) {
                $plainText = substr($plainText, 0, $lastSpace);
            }
            $plainText .= '...';
        }
        
        return $plainText;
    }
}

/**
 * Get all blog categories with post counts
 * 
 * @param PDO $pdo Database connection
 * @return array Array of categories with post counts
 */
if (!function_exists('getBlogCategories')) {
    function getBlogCategories($pdo) {
        try {
            $stmt = $pdo->query('
                SELECT c.id, c.name, c.slug, c.description, c.is_active,
                       COUNT(p.id) as post_count
                FROM blog_categories c
                LEFT JOIN blog_posts p ON c.id = p.category_id AND p.deleted_at IS NULL
                GROUP BY c.id
                ORDER BY c.name
            ');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}

/**
 * Get all blog tags with post counts
 * 
 * @param PDO $pdo Database connection
 * @return array Array of tags with post counts
 */
if (!function_exists('getBlogTags')) {
    function getBlogTags($pdo) {
        try {
            $stmt = $pdo->query('
                SELECT t.id, t.name, t.slug,
                       COUNT(pt.post_id) as post_count
                FROM blog_tags t
                LEFT JOIN blog_post_tags pt ON t.id = pt.tag_id
                LEFT JOIN blog_posts p ON pt.post_id = p.id AND p.deleted_at IS NULL
                GROUP BY t.id
                ORDER BY t.name
            ');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}

/**
 * Get blog statistics for dashboard
 * 
 * @param PDO $pdo Database connection
 * @return array Array with blog statistics
 */
if (!function_exists('getBlogStats')) {
    function getBlogStats($pdo) {
        try {
            $stats = [];
            
            // Total posts
            $stmt = $pdo->query('SELECT COUNT(*) FROM blog_posts WHERE deleted_at IS NULL');
            $stats['total_posts'] = (int)$stmt->fetchColumn();
            
            // Published posts
            $stmt = $pdo->query('SELECT COUNT(*) FROM blog_posts WHERE status = "published" AND deleted_at IS NULL');
            $stats['published_posts'] = (int)$stmt->fetchColumn();
            
            // Draft posts
            $stmt = $pdo->query('SELECT COUNT(*) FROM blog_posts WHERE status = "draft" AND deleted_at IS NULL');
            $stats['draft_posts'] = (int)$stmt->fetchColumn();
            
            // Scheduled posts
            $stmt = $pdo->query('SELECT COUNT(*) FROM blog_posts WHERE status = "scheduled" AND deleted_at IS NULL');
            $stats['scheduled_posts'] = (int)$stmt->fetchColumn();
            
            // Total views
            $stmt = $pdo->query('SELECT COALESCE(SUM(view_count), 0) FROM blog_posts WHERE deleted_at IS NULL');
            $stats['total_views'] = (int)$stmt->fetchColumn();
            
            // Total comments
            $stmt = $pdo->query('SELECT COUNT(*) FROM blog_comments');
            $stats['total_comments'] = (int)$stmt->fetchColumn();
            
            // Categories count
            $stmt = $pdo->query('SELECT COUNT(*) FROM blog_categories WHERE is_active = 1');
            $stats['categories'] = (int)$stmt->fetchColumn();
            
            // Tags count
            $stmt = $pdo->query('SELECT COUNT(*) FROM blog_tags');
            $stats['tags'] = (int)$stmt->fetchColumn();
            
            return $stats;
        } catch (PDOException $e) {
            return [
                'total_posts' => 0,
                'published_posts' => 0,
                'draft_posts' => 0,
                'scheduled_posts' => 0,
                'total_views' => 0,
                'total_comments' => 0,
                'categories' => 0,
                'tags' => 0
            ];
        }
    }
}
