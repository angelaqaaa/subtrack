-- Add API key field to users table
ALTER TABLE users ADD COLUMN api_key VARCHAR(64) NULL UNIQUE;

-- Add index for performance
CREATE INDEX idx_users_api_key ON users(api_key);

-- Optional: Generate API keys for existing users
-- UPDATE users SET api_key = SHA2(CONCAT(id, username, NOW(), RAND()), 256) WHERE api_key IS NULL;