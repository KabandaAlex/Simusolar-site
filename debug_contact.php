<?php
/**
 * Debug script for contact page routing
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDb();

echo "<h1>Contact Page Debug</h1>";

// Check if contact page exists in database
echo "<h2>Database Contact Pages:</h2>";
try {
    $stmt = $pdo->query('SELECT id, slug, title, template_type, layout_file, custom_template, is_active FROM pages WHERE slug LIKE "%contact%"');
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pages)) {
        echo "<p>No contact pages found in database.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Slug</th><th>Title</th><th>Template Type</th><th>Layout File</th><th>Custom Template</th><th>Active</th></tr>";
        foreach ($pages as $page) {
            echo "<tr>";
            echo "<td>{$page['id']}</td>";
            echo "<td>{$page['slug']}</td>";
            echo "<td>{$page['title']}</td>";
            echo "<td>{$page['template_type']}</td>";
            echo "<td>{$page['layout_file']}</td>";
            echo "<td>{$page['custom_template']}</td>";
            echo "<td>{$page['is_active']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check if contact.php file exists
echo "<h2>File System Check:</h2>";
$contactFile = __DIR__ . '/pages/contact.php';
if (file_exists($contactFile)) {
    echo "<p>✓ pages/contact.php exists</p>";
    echo "<p>Size: " . filesize($contactFile) . " bytes</p>";
    echo "<p>Modified: " . date('Y-m-d H:i:s', filemtime($contactFile)) . "</p>";
} else {
    echo "<p>✗ pages/contact.php NOT FOUND</p>";
}

// Check if contact.css file exists
$contactCss = __DIR__ . '/assets/css/pages/contact.css';
if (file_exists($contactCss)) {
    echo "<p>✓ assets/css/pages/contact.css exists</p>";
    echo "<p>Size: " . filesize($contactCss) . " bytes</p>";
    echo "<p>Modified: " . date('Y-m-d H:i:s', filemtime($contactCss)) . "</p>";
} else {
    echo "<p>✗ assets/css/pages/contact.css NOT FOUND</p>";
}

// Check routing
echo "<h2>Routing Test:</h2>";
$pageSlug = 'contact';
$currentPage = getPageBySlug($pdo, $pageSlug);
if ($currentPage) {
    echo "<p>Page found in database:</p>";
    echo "<pre>" . print_r($currentPage, true) . "</pre>";
} else {
    echo "<p>No page found in database for slug 'contact' - will use pages/contact.php</p>";
}

// Check BASE_PATH
echo "<h2>Configuration:</h2>";
echo "<p>BASE_PATH: " . (defined('BASE_PATH') ? BASE_PATH : 'NOT DEFINED') . "</p>";
echo "<p>Current URL: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "</p>";

// Check CSS loading
echo "<h2>CSS Loading Test:</h2>";
$siteBase = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
$pageCss = 'contact.css';
$cssUrl = $siteBase . '/assets/css/pages/' . $pageCss;
echo "<p>Expected CSS URL: <a href='$cssUrl' target='_blank'>$cssUrl</a></p>";
echo "<p>Try opening the CSS file in a new tab to verify it loads.</p>";
?>
