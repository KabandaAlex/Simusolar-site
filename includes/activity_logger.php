<?php
/**
 * Tulima Solar - Activity Logger
 * 
 * This file contains functions for logging admin activities.
 * 
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('APP_STARTED')) {
    define('APP_STARTED', true);
}

/**
 * Log an admin activity
 * 
 * This function supports multiple call signatures for backward compatibility:
 * - logActivity($action, $type, $id, $description, $details = [])
 * - logActivity($pdo, $action, $type, $id, $description)
 * - logActivity($pdo, $adminId, $action, $description)
 * 
 * @param mixed $arg1 PDO connection or action string
 * @param mixed $arg2 Action string, type string, or admin ID
 * @param mixed $arg3 Type string, item ID, or action string
 * @param mixed $arg4 Item ID or description
 * @param mixed $arg5 Description or details
 * @param array $arg6 Details array (optional)
 * @return bool True on success, false on failure
 */
function logActivity($arg1, $arg2 = null, $arg3 = null, $arg4 = null, $arg5 = null, $arg6 = []): bool {
    global $pdo; // Use global PDO if available
    
    // Determine call signature and extract parameters
    if ($arg1 instanceof PDO) {
        $pdo = $arg1;
        
        // Check if second argument is numeric (admin ID) or string (action)
        if (is_numeric($arg2)) {
            // Signature: logActivity($pdo, $adminId, $action, $description)
            $adminId = (int)$arg2;
            $action = $arg3;
            $itemType = 'general';
            $itemId = null;
            $description = $arg4;
            $details = is_array($arg5) ? $arg5 : [];
        } else {
            // Signature: logActivity($pdo, $action, $type, $id, $description)
            $action = $arg2;
            $itemType = $arg3;
            $itemId = $arg4;
            $description = $arg5;
            $details = is_array($arg6) ? $arg6 : [];
            $adminId = $_SESSION['admin_id'] ?? null;
        }
    } else {
        // Signature: logActivity($action, $type, $id, $description, $details = [])
        // Use global PDO
        $action = $arg1;
        $itemType = $arg2;
        $itemId = $arg3;
        $description = $arg4;
        $details = is_array($arg5) ? $arg5 : [];
        $adminId = $_SESSION['admin_id'] ?? null;
    }
    
    // Validate required parameters
    if (empty($action)) {
        return false;
    }
    
    // Make sure PDO is available
    if (!($pdo instanceof PDO)) {
        // Try to get PDO from global scope or create new connection
        if (!isset($GLOBALS['pdo']) || !($GLOBALS['pdo'] instanceof PDO)) {
            // Try to load database config
            if (file_exists(__DIR__ . '/../config/database.php')) {
                require_once __DIR__ . '/../config/database.php';
                if (function_exists('getDb')) {
                    $pdo = getDb();
                }
            }
        } else {
            $pdo = $GLOBALS['pdo'];
        }
        
        // If still no PDO, return false
        if (!($pdo instanceof PDO)) {
            return false;
        }
    }
    
    try {
        // Check if activity_log table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'activity_log'");
        if ($stmt->rowCount() === 0) {
            // Table doesn't exist, create it
            $createSql = "
                CREATE TABLE IF NOT EXISTS activity_log (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    admin_id INT UNSIGNED NULL,
                    action VARCHAR(100) NOT NULL,
                    item_type VARCHAR(100) NULL,
                    item_id INT UNSIGNED NULL,
                    description TEXT NULL,
                    details JSON NULL,
                    ip_address VARCHAR(45) NULL,
                    user_agent VARCHAR(500) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_admin_id (admin_id),
                    INDEX idx_action (action),
                    INDEX idx_item (item_type, item_id),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $pdo->exec($createSql);
        }
        
        // Get IP address and user agent
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Encode details as JSON if array
        if (is_array($details)) {
            $details = json_encode($details);
        }
        
        // Insert log entry
        $stmt = $pdo->prepare('
            INSERT INTO activity_log (admin_id, action, item_type, item_id, description, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        return $stmt->execute([
            $adminId,
            $action,
            $itemType,
            $itemId,
            $description,
            $details,
            $ipAddress,
            $userAgent ? substr($userAgent, 0, 500) : null
        ]);
        
    } catch (PDOException $e) {
        // Log error but don't break the application
        error_log('Activity log error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Convenience wrapper: log a simple admin activity message.
 *
 * Many admin pages call logAdminActivity($description). This helper
 * forwards the call to logActivity() with a generic action and type.
 *
 * @param string $description Human-readable description of the action
 * @param array  $details     Optional structured details (stored as JSON)
 * @return bool
 */
if (!function_exists('logAdminActivity')) {
    function logAdminActivity(string $description, array $details = []): bool
    {
        return logActivity('admin_event', 'admin', null, $description, $details);
    }
}

/**
 * Get recent activity logs
 * 
 * @param PDO $pdo Database connection
 * @param int $limit Number of entries to return
 * @param int|null $adminId Filter by admin ID (optional)
 * @return array Array of activity log entries
 */
function getActivityLogs(PDO $pdo, int $limit = 50, ?int $adminId = null): array {
    try {
        $sql = 'SELECT al.*, a.username as admin_username 
                FROM activity_log al 
                LEFT JOIN admins a ON al.admin_id = a.id';
        
        $params = [];
        
        if ($adminId !== null) {
            $sql .= ' WHERE al.admin_id = ?';
            $params[] = $adminId;
        }
        
        $sql .= ' ORDER BY al.created_at DESC LIMIT ?';
        $params[] = $limit;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get activity log entry by ID
 * 
 * @param PDO $pdo Database connection
 * @param int $logId Log entry ID
 * @return array|null Log entry or null if not found
 */
function getActivityLogById(PDO $pdo, int $logId): ?array {
    try {
        $stmt = $pdo->prepare('
            SELECT al.*, a.username as admin_username 
            FROM activity_log al 
            LEFT JOIN admins a ON al.admin_id = a.id 
            WHERE al.id = ? 
            LIMIT 1
        ');
        $stmt->execute([$logId]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        return $log ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Delete old activity logs (cleanup function)
 * 
 * @param PDO $pdo Database connection
 * @param int $daysOld Delete logs older than this many days
 * @return int Number of deleted entries
 */
function cleanOldActivityLogs(PDO $pdo, int $daysOld = 90): int {
    try {
        $stmt = $pdo->prepare('DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)');
        $stmt->execute([$daysOld]);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get activity statistics
 * 
 * @param PDO $pdo Database connection
 * @param int $days Number of days to include
 * @return array Activity statistics
 */
function getActivityStats(PDO $pdo, int $days = 30): array {
    try {
        $stmt = $pdo->prepare('
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                COUNT(DISTINCT admin_id) as unique_admins
            FROM activity_log 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ');
        $stmt->execute([$days]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get activity counts by action type
 * 
 * @param PDO $pdo Database connection
 * @param int $days Number of days to include
 * @return array Activity counts by action
 */
function getActivityCountsByAction(PDO $pdo, int $days = 30): array {
    try {
        $stmt = $pdo->prepare('
            SELECT 
                action,
                COUNT(*) as count
            FROM activity_log 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY action
            ORDER BY count DESC
        ');
        $stmt->execute([$days]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        return [];
    }
}
