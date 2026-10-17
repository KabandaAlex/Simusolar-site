<?php
/**
 * Check and remove contact page from database if it exists
 * This allows the pages/contact.php file to be used instead
 */

require_once __DIR__ . '/config/database.php';

$pdo = getDb();

echo "<h1>Database Contact Page Check</h1>";

// Check if contact page exists in database
try {
    $stmt = $pdo->query('SELECT id, slug, title, template_type, layout_file, custom_template, is_active FROM pages WHERE slug = "contact" OR slug = "contact-us"');
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pages)) {
        echo "<p>✓ No contact pages found in database. The pages/contact.php file will be used.</p>";
    } else {
        echo "<h2>Found Contact Pages in Database:</h2>";
        echo "<p>These pages will OVERRIDE the pages/contact.php file:</p>";
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
        
        echo "<h2>Options:</h2>";
        echo "<ol>";
        echo "<li><strong>Disable the database page:</strong> Set is_active = 0 for the contact page in the database</li>";
        echo "<li><strong>Delete the database page:</strong> Remove the contact page from the database</li>";
        echo "<li><strong>Update the database page:</strong> Change the custom_template to use pages/contact.php</li>";
        echo "</ol>";
        
        // Provide SQL commands to disable/delete
        echo "<h2>SQL Commands:</h2>";
        echo "<p>To disable all contact pages:</p>";
        echo "<code>UPDATE pages SET is_active = 0 WHERE slug = 'contact' OR slug = 'contact-us';</code>";
        echo "<br><br>";
        echo "<p>To delete all contact pages:</p>";
        echo "<code>DELETE FROM pages WHERE slug = 'contact' OR slug = 'contact-us';</code>";
    }
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
