<?php
/**
 * Tulima Solar Admin - Search API
 * Provides global search functionality across multiple content types
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/path.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../auth.php';

// Ensure user is authenticated for admin search
requireAdmin();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Rate limiting - simple implementation using session
session_start();
$rateLimitKey = 'search_rate_limit';
$maxRequestsPerMinute = 30;
$now = time();

if (!isset($_SESSION[$rateLimitKey])) {
    $_SESSION[$rateLimitKey] = [];
}

// Clean old requests
$_SESSION[$rateLimitKey] = array_filter($_SESSION[$rateLimitKey], fn($t) => ($now - $t) < 60);

// Check rate limit
if (count($_SESSION[$rateLimitKey]) >= $maxRequestsPerMinute) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests. Please wait a moment.']);
    exit;
}

$_SESSION[$rateLimitKey][] = $now;

$pdo = getDb();
$action = $_GET['action'] ?? 'search';

try {
    switch ($action) {
        case 'search':
            handleSearch($pdo);
            break;
            
        case 'suggestions':
            handleSuggestions($pdo);
            break;
            
        case 'recent':
            handleRecentSearches($pdo);
            break;
            
        case 'popular':
            handlePopularSearches($pdo);
            break;
            
        case 'instant':
            handleInstantSearch($pdo);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
            http_response_code(400);
    }
} catch (Throwable $e) {
    error_log('Search API Error: ' . $e->getMessage());
    echo json_encode(['error' => 'An error occurred while searching']);
    http_response_code(500);
}

/**
 * Handle full search request
 */
function handleSearch($pdo) {
    $query = $_GET['q'] ?? '';
    $types = isset($_GET['types']) ? explode(',', $_GET['types']) : [];
    $limit = min(max((int)($_GET['limit'] ?? 50), 1), 100);
    $page = max((int)($_GET['page'] ?? 1), 1);
    $offset = ($page - 1) * $limit;
    
    // Validate query
    $query = sanitizeSearchQuery($query);
    
    if (strlen($query) < 2) {
        echo json_encode([
            'error' => 'Please enter at least 2 characters',
            'query' => $query,
            'results' => [],
            'total' => 0
        ]);
        return;
    }
    
    // Perform search
    $results = globalSearch($pdo, $query, $types, $limit, true);
    $total = getTotalSearchResults($results);
    
    // Log the search
    $userId = $_SESSION['admin_user_id'] ?? null;
    logSearch($pdo, $query, $total, $userId);
    
    // Apply pagination to each result set
    foreach ($results as $type => &$items) {
        $items = array_slice($items, $offset, $limit);
    }
    
    echo json_encode([
        'query' => $query,
        'results' => $results,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'has_more' => $total > ($page * $limit)
    ]);
}

/**
 * Handle autocomplete suggestions
 */
function handleSuggestions($pdo) {
    $query = $_GET['q'] ?? '';
    $limit = min(max((int)($_GET['limit'] ?? 10), 1), 20);
    
    $query = sanitizeSearchQuery($query);
    
    if (strlen($query) < 2) {
        echo json_encode(['suggestions' => []]);
        return;
    }
    
    $suggestions = getSearchSuggestions($pdo, $query, $limit);
    
    echo json_encode([
        'query' => $query,
        'suggestions' => $suggestions
    ]);
}

/**
 * Handle recent searches request
 */
function handleRecentSearches($pdo) {
    $limit = min(max((int)($_GET['limit'] ?? 5), 1), 20);
    $userId = $_SESSION['admin_user_id'] ?? null;
    
    $recent = getRecentSearches($pdo, $userId, session_id(), $limit);
    
    echo json_encode([
        'recent' => $recent
    ]);
}

/**
 * Handle popular searches request
 */
function handlePopularSearches($pdo) {
    $limit = min(max((int)($_GET['limit'] ?? 10), 1), 50);
    $days = min(max((int)($_GET['days'] ?? 30), 1), 365);
    
    $popular = getPopularSearches($pdo, $limit, $days);
    
    echo json_encode([
        'popular' => $popular,
        'days' => $days
    ]);
}

/**
 * Handle instant search (quick preview for dropdown)
 */
function handleInstantSearch($pdo) {
    $query = $_GET['q'] ?? '';
    $limit = min(max((int)($_GET['limit'] ?? 5), 1), 10);
    
    $query = sanitizeSearchQuery($query);
    
    if (strlen($query) < 2) {
        echo json_encode(['results' => [], 'suggestions' => []]);
        return;
    }
    
    // Get quick results from most common types
    $results = [];
    
    // Products
    $products = searchProducts($pdo, $query, $limit, true);
    if (!empty($products)) {
        $results['products'] = array_slice($products, 0, 3);
    }
    
    // Pages
    $pages = searchPages($pdo, $query, $limit, true);
    if (!empty($pages)) {
        $results['pages'] = array_slice($pages, 0, 3);
    }
    
    // Get suggestions
    $suggestions = getSearchSuggestions($pdo, $query, 5);
    
    // Total count
    $total = getTotalSearchResults($results);
    
    echo json_encode([
        'query' => $query,
        'results' => $results,
        'suggestions' => $suggestions,
        'total' => $total
    ]);
}
