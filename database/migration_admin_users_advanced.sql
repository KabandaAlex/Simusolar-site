-- Migration for Advanced User Management
-- Adds support for roles, locking, disabling, and other advanced user operations

-- Add columns to admins table if they don't exist
ALTER TABLE admins 
ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'admin' AFTER email,
ADD COLUMN IF NOT EXISTS is_locked TINYINT(1) DEFAULT 0 AFTER role,
ADD COLUMN IF NOT EXISTS is_disabled TINYINT(1) DEFAULT 0 AFTER is_locked,
ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL AFTER is_disabled,
ADD COLUMN IF NOT EXISTS login_attempts INT DEFAULT 0 AFTER last_login,
ADD COLUMN IF NOT EXISTS locked_until TIMESTAMP NULL AFTER login_attempts,
ADD COLUMN IF NOT EXISTS password_reset_token VARCHAR(255) NULL AFTER locked_until,
ADD COLUMN IF NOT EXISTS password_reset_expires TIMESTAMP NULL AFTER password_reset_token,
ADD COLUMN IF NOT EXISTS created_by INT UNSIGNED NULL AFTER created_at,
ADD COLUMN IF NOT EXISTS updated_by INT UNSIGNED NULL AFTER updated_at;

-- Add index for role column
CREATE INDEX IF NOT EXISTS idx_admins_role ON admins(role);

-- Add index for status columns
CREATE INDEX IF NOT EXISTS idx_admins_is_locked ON admins(is_locked);
CREATE INDEX IF NOT EXISTS idx_admins_is_disabled ON admins(is_disabled);