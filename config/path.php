<?php
/**
 * Base URL path for links and assets (no trailing slash).
 * Use '' if site is at document root, or e.g. '/tulima_php' if in subfolder.
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/tulima_php');
}

/**
 * Uploads folder name (relative to site root). Files are saved under this folder
 * and served at: BASE_PATH . '/' . UPLOAD_DIR . '/videos|images/...'
 */
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', 'uploads');
}
