-- Fix missing columns in subscriptions table
ALTER TABLE subscriptions
ADD COLUMN status ENUM('active', 'cancelled', 'paused') DEFAULT 'active' AFTER category;

-- Update existing records to be active
UPDATE subscriptions SET status = 'active' WHERE status IS NULL;