<?php
/**
 * Tulima Solar Admin - Analytics API
 * Provides analytics data for the dashboard
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/path.php';
require_once __DIR__ . '/../auth.php';
requireAdmin();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$pdo = getDb();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'overview':
            echo json_encode(getAnalyticsOverview($pdo));
            break;
            
        case 'traffic-trends':
            $days = min(max((int)($_GET['days'] ?? 30), 1), 365);
            echo json_encode(getTrafficTrends($pdo, $days));
            break;
            
        case 'top-pages':
            $limit = min(max((int)($_GET['limit'] ?? 10), 1), 100);
            echo json_encode(getTopPages($pdo, $limit));
            break;
            
        case 'contact-analytics':
            echo json_encode(getContactAnalytics($pdo));
            break;
            
        case 'content-performance':
            echo json_encode(getContentPerformance($pdo));
            break;
            
        case 'visitor-stats':
            echo json_encode(getVisitorStats($pdo));
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
            http_response_code(400);
    }
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
    http_response_code(500);
}

/**
 * Get analytics overview stats
 */
function getAnalyticsOverview($pdo) {
    // Total page views (last 30 days)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM page_views 
        WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $totalViews = (int) $stmt->fetchColumn();
    
    // Unique visitors (last 30 days)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT visitor_hash) 
        FROM page_views 
        WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $uniqueVisitors = (int) $stmt->fetchColumn();
    
    // Total contact submissions
    $stmt = $pdo->query("SELECT COUNT(*) FROM contact_submissions");
    $totalSubmissions = (int) $stmt->fetchColumn();
    
    // Unread contact submissions
    $stmt = $pdo->query("SELECT COUNT(*) FROM contact_submissions WHERE status = 'new'");
    $unreadSubmissions = (int) $stmt->fetchColumn();
    
    // Contact rate (submissions per 1000 views)
    $contactRate = $totalViews > 0 
        ? round(($totalSubmissions / $totalViews) * 1000, 2) 
        : 0;
    
    // Compare with previous period
    $prevViews = (int) $pdo->query("
        SELECT COUNT(*) 
        FROM page_views 
        WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
        AND viewed_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetchColumn();
    
    $viewsChange = $prevViews > 0 
        ? round((($totalViews - $prevViews) / $prevViews) * 100, 1) 
        : 0;
    
    $prevVisitors = (int) $pdo->query("
        SELECT COUNT(DISTINCT visitor_hash) 
        FROM page_views 
        WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
        AND viewed_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetchColumn();
    
    $visitorsChange = $prevVisitors > 0 
        ? round((($uniqueVisitors - $prevVisitors) / $prevVisitors) * 100, 1) 
        : 0;
    
    return [
        'totalViews' => $totalViews,
        'uniqueVisitors' => $uniqueVisitors,
        'totalSubmissions' => $totalSubmissions,
        'unreadSubmissions' => $unreadSubmissions,
        'contactRate' => $contactRate,
        'viewsChange' => $viewsChange,
        'visitorsChange' => $visitorsChange
    ];
}

/**
 * Get traffic trends over time
 */
function getTrafficTrends($pdo, $days) {
    $stmt = $pdo->prepare("
        SELECT 
            DATE(viewed_at) as date,
            COUNT(*) as views,
            COUNT(DISTINCT visitor_hash) as unique_visitors
        FROM page_views
        WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY DATE(viewed_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$days]);
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fill in missing dates
    $result = [];
    $startDate = date('Y-m-d', strtotime("-$days days"));
    $endDate = date('Y-m-d');
    $current = strtotime($startDate);
    $end = strtotime($endDate);
    
    $dataMap = [];
    foreach ($data as $row) {
        $dataMap[$row['date']] = $row;
    }
    
    while ($current <= $end) {
        $date = date('Y-m-d', $current);
        $result[] = $dataMap[$date] ?? [
            'date' => $date,
            'views' => 0,
            'unique_visitors' => 0
        ];
        $current = strtotime('+1 day', $current);
    }
    
    return $result;
}

/**
 * Get top performing pages
 */
function getTopPages($pdo, $limit) {
    $stmt = $pdo->prepare("
        SELECT 
            page_slug,
            page_title,
            COUNT(*) as views,
            COUNT(DISTINCT visitor_hash) as unique_visitors,
            AVG(time_on_page) as avg_time_on_page
        FROM page_views
        WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY page_slug, page_title
        ORDER BY views DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get contact analytics
 */
function getContactAnalytics($pdo) {
    // Total submissions
    $stmt = $pdo->query("SELECT COUNT(*) FROM contact_submissions");
    $totalSubmissions = (int) $stmt->fetchColumn();
    
    // Submissions by status
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM contact_submissions 
        GROUP BY status
    ");
    $byStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Average response time
    $stmt = $pdo->query("
        SELECT AVG(TIMESTAMPDIFF(HOUR, submitted_at, replied_at)) as avg_hours
        FROM contact_submissions 
        WHERE replied_at IS NOT NULL
    ");
    $avgResponseTime = (float) $stmt->fetchColumn();
    
    // Response rate
    $repliedCount = (int) $pdo->query("
        SELECT COUNT(*) 
        FROM contact_submissions 
        WHERE status = 'replied'
    ")->fetchColumn();
    
    $responseRate = $totalSubmissions > 0 
        ? round(($repliedCount / $totalSubmissions) * 100, 1) 
        : 0;
    
    // Submissions over time
    $stmt = $pdo->query("
        SELECT 
            DATE(submitted_at) as date,
            COUNT(*) as count
        FROM contact_submissions
        WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(submitted_at)
        ORDER BY date ASC
    ");
    $submissionsTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Peak hours
    $stmt = $pdo->query("
        SELECT 
            HOUR(submitted_at) as hour,
            COUNT(*) as count
        FROM contact_submissions
        GROUP BY HOUR(submitted_at)
        ORDER BY count DESC
        LIMIT 5
    ");
    $peakHours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'total' => $totalSubmissions,
        'byStatus' => $byStatus,
        'avgResponseTime' => $avgResponseTime,
        'responseRate' => $responseRate,
        'trend' => $submissionsTrend,
        'peakHours' => $peakHours
    ];
}

/**
 * Get content performance metrics
 */
function getContentPerformance($pdo) {
    // Page performance
    $stmt = $pdo->query("
        SELECT 
            page_slug,
            page_title,
            COUNT(*) as views,
            COUNT(DISTINCT visitor_hash) as unique_visitors,
            AVG(time_on_page) as avg_time_on_page
        FROM page_views
        WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY page_slug, page_title
        ORDER BY views DESC
    ");
    $pagePerformance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate bounce rate (single page view per visitor)
    $bounceRate = [];
    foreach ($pagePerformance as &$page) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM page_views
            WHERE page_slug = ? 
            AND viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$page['page_slug']]);
        $totalPageViews = (int) $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT visitor_hash) 
            FROM page_views
            WHERE page_slug = ? 
            AND viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$page['page_slug']]);
        $uniqueVisitors = (int) $stmt->fetchColumn();
        
        $page['bounce_rate'] = $uniqueVisitors > 0 
            ? round((1 - ($totalPageViews / ($uniqueVisitors * 2))) * 100, 1) 
            : 0;
    }
    
    // Product performance
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.title,
            SUM(pa.view_count) as total_views,
            SUM(pa.inquiry_count) as total_inquiries
        FROM products p
        LEFT JOIN product_analytics pa ON p.id = pa.product_id
        GROUP BY p.id, p.title
        ORDER BY total_views DESC
    ");
    $productPerformance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'pages' => $pagePerformance,
        'products' => $productPerformance
    ];
}

/**
 * Get visitor statistics
 */
function getVisitorStats($pdo) {
    // Device breakdown (basic user agent parsing)
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN user_agent LIKE '%Mobile%' OR user_agent LIKE '%Android%' OR user_agent LIKE '%iPhone%' THEN 'Mobile'
                WHEN user_agent LIKE '%Tablet%' OR user_agent LIKE '%iPad%' THEN 'Tablet'
                ELSE 'Desktop'
            END as device,
            COUNT(*) as count
        FROM page_views
        WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY device
    ");
    $deviceBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top referrers
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN referrer = '' OR referrer IS NULL THEN 'Direct'
                WHEN referrer LIKE '%google%' THEN 'Google'
                WHEN referrer LIKE '%facebook%' THEN 'Facebook'
                WHEN referrer LIKE '%twitter%' THEN 'Twitter'
                WHEN referrer LIKE '%linkedin%' THEN 'LinkedIn'
                ELSE 'Other'
            END as source,
            COUNT(*) as count
        FROM page_views
        WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY source
        ORDER BY count DESC
        LIMIT 10
    ");
    $topReferrers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'devices' => $deviceBreakdown,
        'referrers' => $topReferrers
    ];
}
