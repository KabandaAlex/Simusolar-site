-- Migration: Notifications System
-- Created: 2026-02-25
-- Description: Creates the notifications table for the admin panel notification system

-- Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'FK to admin_users.id',
    `type` VARCHAR(50) NOT NULL DEFAULT 'general' COMMENT 'Notification type: info, warning, success, error, general',
    `title` VARCHAR(255) NOT NULL COMMENT 'Short notification title',
    `message` TEXT NOT NULL COMMENT 'Detailed notification message',
    `link` VARCHAR(500) NULL COMMENT 'Optional URL to related content',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether notification has been read',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When notification was created',
    `read_at` TIMESTAMP NULL COMMENT 'When notification was read',
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_is_read` (`is_read`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin panel notifications';

-- Optional: Add foreign key constraint (uncomment if admin_users table exists)
-- ALTER TABLE `notifications` 
-- ADD CONSTRAINT `fk_notifications_user` 
-- FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;
