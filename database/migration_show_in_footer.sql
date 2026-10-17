-- Migration: Add show_in_footer column to pages table
-- This allows pages to be shown/hidden in the footer navigation

ALTER TABLE pages ADD COLUMN show_in_footer TINYINT(1) DEFAULT 1 AFTER is_active;
