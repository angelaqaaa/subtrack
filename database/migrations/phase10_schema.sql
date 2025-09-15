-- Phase 10: Audit Trail & Activity Logging Schema
-- Execute these SQL commands to add audit trail functionality

USE subtrack_db;

-- 1. Create activity_log table for comprehensive audit trail
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    space_id INT NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NULL,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (space_id) REFERENCES spaces(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_space (space_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at)
);

-- 2. Add sample audit entries for existing data
INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address) VALUES
(2, 'user_registered', 'user', 2, '{"username": "Angelaqaaa1", "email": "user@example.com"}', '127.0.0.1'),
(2, 'space_created', 'space', 1, '{"space_name": "Family Finances", "description": "Shared family subscription tracking"}', '127.0.0.1');