-- 2FA Migration Script for SubTrack
-- Run this script to add Two-Factor Authentication support

-- Add 2FA columns to users table
ALTER TABLE users
ADD COLUMN two_factor_enabled BOOLEAN DEFAULT FALSE,
ADD COLUMN two_factor_secret VARCHAR(32) NULL,
ADD COLUMN two_factor_backup_codes TEXT NULL;

-- Create table for 2FA backup codes (alternative approach for better security)
CREATE TABLE IF NOT EXISTS user_backup_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_code_hash (code_hash)
);

-- Add index for 2FA fields
ALTER TABLE users ADD INDEX idx_two_factor_enabled (two_factor_enabled);