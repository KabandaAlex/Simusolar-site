<?php
/**
 * One-time install: create database and load schema + seed.
 * Run from browser: http://localhost/tulima_php/install/install.php
 * Delete or protect the install folder after use.
 */
$base = dirname(__DIR__);
$configDir = $base . '/config';
$schemaFile = $base . '/database/schema.sql';
$seedFile = $base . '/database/seed.sql';

if (!file_exists($configDir . '/database.php')) {
    die('Config not found. Copy config/database.php and set DB credentials.');
}

require_once $configDir . '/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Tulima Solar – Install</h1>\n";

function runSqlFile(PDO $pdo, $file, $useDb = false) {
    $sql = file_get_contents($file);
    if ($useDb) $pdo->exec('USE ' . DB_NAME);
    $pdo->exec($sql);
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);
    runSqlFile($pdo, $schemaFile);
    echo "<p>Schema executed.</p>\n";
    runSqlFile($pdo, $seedFile, true);
    echo "<p>Seed data loaded.</p>\n";
    $basePath = dirname($_SERVER['SCRIPT_NAME'], 2);
    echo "<p><strong>Done.</strong> Default admin: <code>admin</code> / <code>password</code> (change in production).</p>\n";
    echo "<p><a href='{$basePath}/admin/login.php'>Go to Admin</a> | <a href='{$basePath}/index.php'>View site</a></p>\n";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
