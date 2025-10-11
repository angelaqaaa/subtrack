-- Fix user_achievements table structure
-- This migration ensures the table has all required columns

-- Check if title column exists, if not add it
ALTER TABLE user_achievements
ADD COLUMN IF NOT EXISTS title VARCHAR(200) NOT NULL AFTER achievement_type;

-- Check if description column exists, if not add it
ALTER TABLE user_achievements
ADD COLUMN IF NOT EXISTS description TEXT AFTER title;

-- Verify the table structure is correct
-- Expected columns: id, user_id, achievement_type, title, description, achieved_at, data
