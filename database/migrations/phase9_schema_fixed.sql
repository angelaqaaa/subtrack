-- Phase 9: Shared Spaces & Role-Based Access Control (RBAC)
-- Database schema for shared subscription spaces

-- 1. Create spaces table
CREATE TABLE spaces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    owner_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_owner (owner_id),
    INDEX idx_created (created_at)
);

-- 2. Create space_users table for member management
CREATE TABLE space_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    space_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('admin', 'viewer') NOT NULL DEFAULT 'viewer',
    invited_by INT NOT NULL,
    invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL,
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    FOREIGN KEY (space_id) REFERENCES spaces(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_space_user (space_id, user_id),
    INDEX idx_space (space_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status)
);

-- 3. Add space_id to subscriptions table to support shared subscriptions
ALTER TABLE subscriptions
ADD COLUMN space_id INT NULL AFTER user_id,
ADD FOREIGN KEY (space_id) REFERENCES spaces(id) ON DELETE SET NULL,
ADD INDEX idx_space (space_id);

-- Note: Sample data removed to avoid foreign key constraints
-- Users can create spaces through the web interface after registering