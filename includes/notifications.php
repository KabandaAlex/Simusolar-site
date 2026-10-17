<?php
/**
 * Tulima Solar - Notifications System
 * 
 * This file contains functions for managing user notifications in the admin panel.
 * 
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('APP_STARTED')) {
    define('APP_STARTED', true);
}

/**
 * Ensure the notifications table exists
 * Creates the table if it doesn't exist
 * 
 * @param PDO $pdo Database connection
 * @return bool True if table exists or was created, false on failure
 */
function ensureNotificationsTableExists(PDO $pdo): bool {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
        if ($stmt->rowCount() === 0) {
            // Table doesn't exist, create it
            $createSql = "
                CREATE TABLE IF NOT EXISTS notifications (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    type VARCHAR(50) NOT NULL DEFAULT 'general',
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    link VARCHAR(500) NULL,
                    is_read TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    read_at TIMESTAMP NULL,
                    INDEX idx_user_id (user_id),
                    INDEX idx_is_read (is_read),
                    INDEX idx_created_at (created_at),
                    INDEX idx_user_read (user_id, is_read)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            $pdo->exec($createSql);
        }
        return true;
    } catch (PDOException $e) {
        error_log("Failed to create notifications table: " . $e->getMessage());
        return false;
    }
}

/**
 * Get notifications for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID to get notifications for
 * @param int $limit Maximum number of notifications to return (default: 10)
 * @param bool $unreadOnly Whether to return only unread notifications (default: false)
 * @return array Array of notifications
 */
function getNotifications(PDO $pdo, int $userId, int $limit = 10, bool $unreadOnly = false): array {
    ensureNotificationsTableExists($pdo);
    
    try {
        $sql = "SELECT * FROM notifications WHERE user_id = :user_id";
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        $sql .= " ORDER BY created_at DESC LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("Failed to get notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Add a new notification
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID to send notification to
 * @param string $type Notification type (e.g., 'info', 'warning', 'success', 'error', 'general')
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string|null $link Optional link for the notification
 * @return int|false The new notification ID on success, false on failure
 */
function addNotification(PDO $pdo, int $userId, string $type, string $title, string $message, ?string $link = null) {
    ensureNotificationsTableExists($pdo);
    
    try {
        $sql = "INSERT INTO notifications (user_id, type, title, message, link) 
                VALUES (:user_id, :type, :title, :message, :link)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':type' => $type,
            ':title' => $title,
            ':message' => $message,
            ':link' => $link
        ]);
        
        return (int) $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Failed to add notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark a notification as read
 * 
 * @param PDO $pdo Database connection
 * @param int $notificationId Notification ID to mark as read
 * @param int|null $userId Optional user ID for additional security check
 * @return bool True on success, false on failure
 */
function markNotificationRead(PDO $pdo, int $notificationId, ?int $userId = null): bool {
    ensureNotificationsTableExists($pdo);
    
    try {
        $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() 
                WHERE id = :id";
        $params = [':id' => $notificationId];
        
        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Failed to mark notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID to mark all notifications as read for
 * @return int Number of notifications marked as read
 */
function markAllNotificationsRead(PDO $pdo, int $userId): int {
    ensureNotificationsTableExists($pdo);
    
    try {
        $sql = "UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Failed to mark all notifications as read: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get unread notification count for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID to get count for
 * @return int Number of unread notifications
 */
function getUnreadNotificationCount(PDO $pdo, int $userId): int {
    ensureNotificationsTableExists($pdo);
    
    try {
        $sql = "SELECT COUNT(*) FROM notifications 
                WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Failed to get unread notification count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Delete old notifications
 * 
 * @param PDO $pdo Database connection
 * @param int $daysOld Delete notifications older than this many days (default: 30)
 * @return int Number of notifications deleted
 */
function deleteOldNotifications(PDO $pdo, int $daysOld = 30): int {
    ensureNotificationsTableExists($pdo);
    
    try {
        $sql = "DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':days', $daysOld, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Failed to delete old notifications: " . $e->getMessage());
        return 0;
    }
}

/**
 * Delete a specific notification
 * 
 * @param PDO $pdo Database connection
 * @param int $notificationId Notification ID to delete
 * @param int|null $userId Optional user ID for additional security check
 * @return bool True on success, false on failure
 */
function deleteNotification(PDO $pdo, int $notificationId, ?int $userId = null): bool {
    ensureNotificationsTableExists($pdo);
    
    try {
        $sql = "DELETE FROM notifications WHERE id = :id";
        $params = [':id' => $notificationId];
        
        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Failed to delete notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get notification by ID
 * 
 * @param PDO $pdo Database connection
 * @param int $notificationId Notification ID
 * @return array|null Notification data or null if not found
 */
function getNotificationById(PDO $pdo, int $notificationId): ?array {
    ensureNotificationsTableExists($pdo);
    
    try {
        $sql = "SELECT * FROM notifications WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $notificationId]);
        
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);
        return $notification ?: null;
    } catch (PDOException $e) {
        error_log("Failed to get notification by ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Send notification to all admin users
 * 
 * @param PDO $pdo Database connection
 * @param string $type Notification type
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string|null $link Optional link for the notification
 * @return int Number of notifications sent
 */
function notifyAllAdmins(PDO $pdo, string $type, string $title, string $message, ?string $link = null): int {
    ensureNotificationsTableExists($pdo);
    
    try {
        // Get all admin user IDs
        $stmt = $pdo->query("SELECT id FROM admin_users WHERE is_active = 1");
        $adminIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $count = 0;
        foreach ($adminIds as $adminId) {
            if (addNotification($pdo, (int) $adminId, $type, $title, $message, $link)) {
                $count++;
            }
        }
        
        return $count;
    } catch (PDOException $e) {
        error_log("Failed to notify all admins: " . $e->getMessage());
        return 0;
    }
}

/**
 * Create a notification for common events
 * 
 * @param PDO $pdo Database connection
 * @param string $event Event type (e.g., 'new_contact', 'new_order', 'new_comment')
 * @param array $data Event data
 * @return int|false Notification ID or false on failure
 */
function createEventNotification(PDO $pdo, string $event, array $data = []) {
    $notifications = [
        'new_contact' => [
            'type' => 'info',
            'title' => 'New Contact Submission',
            'message' => 'A new contact form submission has been received.',
            'link' => '/admin/contact_submissions.php'
        ],
        'new_comment' => [
            'type' => 'info',
            'title' => 'New Blog Comment',
            'message' => 'A new comment has been posted on a blog.',
            'link' => '/admin/blog_comments.php'
        ],
        'new_job_application' => [
            'type' => 'info',
            'title' => 'New Job Application',
            'message' => 'A new job application has been submitted.',
            'link' => '/admin/jobs.php'
        ],
        'system_update' => [
            'type' => 'warning',
            'title' => 'System Update',
            'message' => 'A system update is available.',
            'link' => null
        ],
        'error' => [
            'type' => 'error',
            'title' => 'System Error',
            'message' => 'An error has occurred in the system.',
            'link' => null
        ]
    ];
    
    if (!isset($notifications[$event])) {
        return false;
    }
    
    $notification = $notifications[$event];
    
    // Override with custom data if provided
    $type = $data['type'] ?? $notification['type'];
    $title = $data['title'] ?? $notification['title'];
    $message = $data['message'] ?? $notification['message'];
    $link = $data['link'] ?? $notification['link'];
    
    // Notify all admins for system events
    return notifyAllAdmins($pdo, $type, $title, $message, $link);
}
