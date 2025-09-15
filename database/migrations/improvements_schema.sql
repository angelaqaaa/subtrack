-- Improvements for SubTrack: Categories, Invitations, Subscription End Dates
-- Run this after all existing schema files

-- 1. Create custom categories table
CREATE TABLE IF NOT EXISTS custom_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#6c757d',
    icon VARCHAR(50) DEFAULT 'fas fa-tag',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_category (user_id, name),
    INDEX idx_user_active (user_id, is_active)
);

-- 2. Insert default categories for existing users
INSERT IGNORE INTO custom_categories (user_id, name, color, icon)
SELECT u.id, 'Entertainment', '#e74c3c', 'fas fa-film' FROM users u
UNION ALL
SELECT u.id, 'Productivity', '#3498db', 'fas fa-briefcase' FROM users u
UNION ALL
SELECT u.id, 'Education', '#f39c12', 'fas fa-graduation-cap' FROM users u
UNION ALL
SELECT u.id, 'Health & Fitness', '#27ae60', 'fas fa-heartbeat' FROM users u
UNION ALL
SELECT u.id, 'Business', '#9b59b6', 'fas fa-building' FROM users u
UNION ALL
SELECT u.id, 'Other', '#95a5a6', 'fas fa-tag' FROM users u;

-- 3. Create space invitations table for proper invitation workflow
CREATE TABLE IF NOT EXISTS space_invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    space_id INT NOT NULL,
    inviter_id INT NOT NULL,
    invitee_email VARCHAR(255) NOT NULL,
    invitee_id INT NULL, -- Set when email matches existing user
    role ENUM('admin', 'viewer') NOT NULL DEFAULT 'viewer',
    status ENUM('pending', 'accepted', 'declined', 'expired') DEFAULT 'pending',
    invitation_token VARCHAR(64) NOT NULL UNIQUE,
    invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 7 DAY),
    responded_at TIMESTAMP NULL,
    FOREIGN KEY (space_id) REFERENCES spaces(id) ON DELETE CASCADE,
    FOREIGN KEY (inviter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invitee_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_invitee_email (invitee_email),
    INDEX idx_token (invitation_token),
    INDEX idx_status (status),
    INDEX idx_expires (expires_at)
);

-- 4. Add end_date to subscriptions for lifecycle management
ALTER TABLE subscriptions
ADD COLUMN end_date DATE NULL AFTER start_date,
ADD COLUMN is_active BOOLEAN DEFAULT TRUE,
ADD COLUMN cancellation_reason VARCHAR(255) NULL,
ADD INDEX idx_active (is_active),
ADD INDEX idx_end_date (end_date);

-- 5. Update existing subscriptions to be active
UPDATE subscriptions SET is_active = TRUE WHERE is_active IS NULL;

-- 6. Fix spending_goals concept - make it about budget limits, not achievements
ALTER TABLE spending_goals
MODIFY COLUMN monthly_limit DECIMAL(10,2) NOT NULL COMMENT 'Maximum amount to spend per month',
ADD COLUMN alert_threshold DECIMAL(3,2) DEFAULT 0.80 COMMENT 'Alert when spending reaches this % of limit',
ADD COLUMN alert_sent BOOLEAN DEFAULT FALSE,
ADD INDEX idx_alert_threshold (alert_threshold);

-- 7. Rename user_achievements to make the concept clearer
RENAME TABLE user_achievements TO user_milestones;

-- 8. Update insights table to track which user owns each insight properly
ALTER TABLE insights
ADD COLUMN insight_category ENUM('saving', 'budget', 'optimization', 'warning') DEFAULT 'optimization',
ADD INDEX idx_category (insight_category);

-- 9. Create audit improvements
ALTER TABLE activity_log
ADD COLUMN session_id VARCHAR(128) NULL,
ADD INDEX idx_session (session_id);