-- Add editor role support to shared space tables

-- Update existing space_users records to allow the editor role
ALTER TABLE space_users
MODIFY COLUMN role ENUM('admin', 'editor', 'viewer') NOT NULL DEFAULT 'viewer';

-- Update invitations so editors can be invited through the React UI
ALTER TABLE space_invitations
MODIFY COLUMN role ENUM('admin', 'editor', 'viewer') NOT NULL DEFAULT 'viewer';
