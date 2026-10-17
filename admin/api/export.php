<?php
/**
 * Tulima Solar Admin - Export API
 * Provides data export functionality for various content types
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../auth.php';
requireAdmin();

$pdo = getDb();
$type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'csv';

try {
    switch ($type) {
        case 'contact_submissions':
            exportContactSubmissions($pdo, $format);
            break;
            
        case 'page_views':
            exportPageViews($pdo, $format);
            break;
            
        case 'activity_log':
            exportActivityLog($pdo, $format);
            break;
            
        case 'products':
            exportProducts($pdo, $format);
            break;
            
        case 'testimonials':
            exportTestimonials($pdo, $format);
            break;
            
        case 'partners':
            exportPartners($pdo, $format);
            break;
            
        case 'pages':
            exportPages($pdo, $format);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid export type']);
            http_response_code(400);
    }
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
    http_response_code(500);
}

/**
 * Export contact submissions
 */
function exportContactSubmissions($pdo, $format) {
    $stmt = $pdo->query("
        SELECT 
            id,
            name,
            email,
            phone,
            subject,
            message,
            status,
            is_read,
            submitted_at,
            replied_at
        FROM contact_submissions
        ORDER BY submitted_at DESC
    ");
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($format === 'csv') {
        $filename = 'contact_submissions_' . date('Y-m-d') . '.csv';
        $headers = [
            'ID',
            'Name',
            'Email',
            'Phone',
            'Subject',
            'Message',
            'Status',
            'Read',
            'Submitted At',
            'Replied At'
        ];
        
        $rows = [];
        foreach ($data as $row) {
            $rows[] = [
                $row['id'],
                $row['name'],
                $row['email'],
                $row['phone'],
                $row['subject'],
                $row['message'],
                $row['status'],
                $row['is_read'] ? 'Yes' : 'No',
                $row['submitted_at'],
                $row['replied_at']
            ];
        }
        
        downloadCSV($filename, $headers, $rows);
    } else {
        echo json_encode(['error' => 'Only CSV format is supported']);
        http_response_code(400);
    }
}

/**
 * Export page views
 */
function exportPageViews($pdo, $format) {
    $days = min(max((int)($_GET['days'] ?? 30), 1), 365);
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            page_slug,
            page_title,
            visitor_hash,
            ip_address,
            referrer,
            user_agent,
            time_on_page,
            viewed_at
        FROM page_views
        WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY viewed_at DESC
        LIMIT 10000
    ");
    $stmt->execute([$days]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($format === 'csv') {
        $filename = 'page_views_' . date('Y-m-d') . '.csv';
        $headers = [
            'ID',
            'Page Slug',
            'Page Title',
            'Visitor Hash',
            'IP Address',
            'Referrer',
            'User Agent',
            'Time on Page (seconds)',
            'Viewed At'
        ];
        
        $rows = [];
        foreach ($data as $row) {
            $rows[] = [
                $row['id'],
                $row['page_slug'],
                $row['page_title'],
                $row['visitor_hash'],
                $row['ip_address'],
                $row['referrer'],
                $row['user_agent'],
                $row['time_on_page'],
                $row['viewed_at']
            ];
        }
        
        downloadCSV($filename, $headers, $rows);
    } else {
        echo json_encode(['error' => 'Only CSV format is supported']);
        http_response_code(400);
    }
}

/**
 * Export activity log
 */
function exportActivityLog($pdo, $format) {
    $days = min(max((int)($_GET['days'] ?? 30), 1), 365);
    
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.action,
            a.entity_type,
            a.entity_id,
            a.ip_address,
            a.created_at,
            u.username as admin_user
        FROM activity_log a
        LEFT JOIN users u ON a.admin_id = u.id
        WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY a.created_at DESC
        LIMIT 10000
    ");
    $stmt->execute([$days]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($format === 'csv') {
        $filename = 'activity_log_' . date('Y-m-d') . '.csv';
        $headers = [
            'ID',
            'Action',
            'Entity Type',
            'Entity ID',
            'IP Address',
            'Created At',
            'Admin User'
        ];
        
        $rows = [];
        foreach ($data as $row) {
            $rows[] = [
                $row['id'],
                $row['action'],
                $row['entity_type'],
                $row['entity_id'],
                $row['ip_address'],
                $row['created_at'],
                $row['admin_user'] ?? 'System'
            ];
        }
        
        downloadCSV($filename, $headers, $rows);
    } else {
        echo json_encode(['error' => 'Only CSV format is supported']);
        http_response_code(400);
    }
}

/**
 * Export products
 */
function exportProducts($pdo, $format) {
    $stmt = $pdo->query("
        SELECT 
            id,
            title,
            slug,
            description,
            category,
            price,
            is_active,
            sort_order,
            created_at,
            updated_at
        FROM products
        ORDER BY title ASC
    ");
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($format === 'csv') {
        $filename = 'products_' . date('Y-m-d') . '.csv';
        $headers = [
            'ID',
            'Title',
            'Slug',
            'Description',
            'Category',
            'Price',
            'Active',
            'Sort Order',
            'Created At',
            'Updated At'
        ];
        
        $rows = [];
        foreach ($data as $row) {
            $rows[] = [
                $row['id'],
                $row['title'],
                $row['slug'],
                $row['description'],
                $row['category'],
                $row['price'],
                $row['is_active'] ? 'Yes' : 'No',
                $row['sort_order'],
                $row['created_at'],
                $row['updated_at']
            ];
        }
        
        downloadCSV($filename, $headers, $rows);
    } else {
        echo json_encode(['error' => 'Only CSV format is supported']);
        http_response_code(400);
    }
}

/**
 * Export testimonials
 */
function exportTestimonials($pdo, $format) {
    $stmt = $pdo->query("
        SELECT 
            id,
            name,
            role,
            company,
            testimonial,
            image,
            is_active,
            sort_order,
            created_at
        FROM testimonials
        ORDER BY sort_order, id
    ");
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($format === 'csv') {
        $filename = 'testimonials_' . date('Y-m-d') . '.csv';
        $headers = [
            'ID',
            'Name',
            'Role',
            'Company',
            'Testimonial',
            'Image',
            'Active',
            'Sort Order',
            'Created At'
        ];
        
        $rows = [];
        foreach ($data as $row) {
            $rows[] = [
                $row['id'],
                $row['name'],
                $row['role'],
                $row['company'],
                $row['testimonial'],
                $row['image'],
                $row['is_active'] ? 'Yes' : 'No',
                $row['sort_order'],
                $row['created_at']
            ];
        }
        
        downloadCSV($filename, $headers, $rows);
    } else {
        echo json_encode(['error' => 'Only CSV format is supported']);
        http_response_code(400);
    }
}

/**
 * Export partners
 */
function exportPartners($pdo, $format) {
    $stmt = $pdo->query("
        SELECT 
            id,
            name,
            website,
            logo,
            is_active,
            sort_order,
            created_at
        FROM partners
        ORDER BY sort_order, name ASC
    ");
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($format === 'csv') {
        $filename = 'partners_' . date('Y-m-d') . '.csv';
        $headers = [
            'ID',
            'Name',
            'Website',
            'Logo',
            'Active',
            'Sort Order',
            'Created At'
        ];
        
        $rows = [];
        foreach ($data as $row) {
            $rows[] = [
                $row['id'],
                $row['name'],
                $row['website'],
                $row['logo'],
                $row['is_active'] ? 'Yes' : 'No',
                $row['sort_order'],
                $row['created_at']
            ];
        }
        
        downloadCSV($filename, $headers, $rows);
    } else {
        echo json_encode(['error' => 'Only CSV format is supported']);
        http_response_code(400);
    }
}

/**
 * Export pages
 */
function exportPages($pdo, $format) {
    $stmt = $pdo->query("
        SELECT 
            id,
            slug,
            title,
            meta_description,
            is_active,
            sort_order,
            hero_title,
            hero_subtitle,
            hero_cta_text,
            hero_cta_url,
            hero_cta_secondary_text,
            hero_cta_secondary_url,
            hero_video_path,
            created_at,
            updated_at
        FROM pages
        ORDER BY sort_order, id
    ");
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($format === 'csv') {
        $filename = 'pages_' . date('Y-m-d') . '.csv';
        $headers = [
            'ID',
            'Slug',
            'Title',
            'Meta Description',
            'Active',
            'Sort Order',
            'Hero Title',
            'Hero Subtitle',
            'Hero CTA Text',
            'Hero CTA URL',
            'Hero Secondary CTA Text',
            'Hero Secondary CTA URL',
            'Hero Video Path',
            'Created At',
            'Updated At'
        ];
        
        $rows = [];
        foreach ($data as $row) {
            $rows[] = [
                $row['id'],
                $row['slug'],
                $row['title'],
                $row['meta_description'],
                $row['is_active'] ? 'Yes' : 'No',
                $row['sort_order'],
                $row['hero_title'],
                $row['hero_subtitle'],
                $row['hero_cta_text'],
                $row['hero_cta_url'],
                $row['hero_cta_secondary_text'],
                $row['hero_cta_secondary_url'],
                $row['hero_video_path'],
                $row['created_at'],
                $row['updated_at']
            ];
        }
        
        downloadCSV($filename, $headers, $rows);
    } else {
        echo json_encode(['error' => 'Only CSV format is supported']);
        http_response_code(400);
    }
}

/**
 * Download CSV file
 */
function downloadCSV($filename, $headers, $rows) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write rows
    foreach ($rows as $row) {
        // Clean and escape CSV values
        $cleanedRow = array_map(function($value) {
            // Remove newlines and tabs
            $value = preg_replace('/[\r\n\t]/', ' ', $value);
            // Remove null bytes
            $value = str_replace("\x00", '', $value);
            return $value;
        }, $row);
        
        fputcsv($output, $cleanedRow);
    }
    
    fclose($output);
    exit;
}