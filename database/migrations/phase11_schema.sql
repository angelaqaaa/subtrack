-- Phase 11: Financial Insights & Educational Content Module
-- Database schema for insights, recommendations, and educational content

-- Table for storing financial insights and recommendations
CREATE TABLE insights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('saving_opportunity', 'spending_alert', 'category_analysis', 'trend_analysis') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    impact_score INT DEFAULT 0, -- 1-10 scale for potential impact
    status ENUM('active', 'dismissed', 'applied') DEFAULT 'active',
    data JSON, -- Store analysis data
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_type_created (type, created_at)
);

-- Table for educational content
CREATE TABLE educational_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    category ENUM('budgeting', 'saving_tips', 'subscription_management', 'financial_planning') NOT NULL,
    content TEXT NOT NULL,
    summary VARCHAR(500),
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    estimated_read_time INT DEFAULT 5, -- in minutes
    tags JSON, -- Array of tags
    is_featured BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_featured (is_featured),
    INDEX idx_difficulty (difficulty_level)
);

-- Table for user progress on educational content
CREATE TABLE user_education_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_id INT NOT NULL,
    status ENUM('started', 'completed', 'bookmarked') DEFAULT 'started',
    progress_percent INT DEFAULT 0,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES educational_content(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_content (user_id, content_id),
    INDEX idx_user_status (user_id, status)
);

-- Table for spending goals and budgets
CREATE TABLE spending_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    monthly_limit DECIMAL(10,2) NOT NULL,
    current_spending DECIMAL(10,2) DEFAULT 0.00,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    status ENUM('active', 'completed', 'exceeded', 'paused') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_period (user_id, period_start, period_end),
    INDEX idx_status (status)
);

-- Table for financial milestones and achievements
CREATE TABLE user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_type ENUM('first_subscription', 'cost_saver', 'budget_keeper', 'category_master', 'trend_analyzer') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    achieved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data JSON, -- Store achievement-specific data
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_type),
    INDEX idx_user_achieved (user_id, achieved_at)
);

-- Insert sample educational content
INSERT INTO educational_content (title, slug, category, content, summary, difficulty_level, estimated_read_time, tags, is_featured) VALUES
('Understanding Subscription Costs', 'understanding-subscription-costs', 'subscription_management',
'<h3>The Hidden Cost of Subscriptions</h3>
<p>Many people underestimate how much they spend on subscriptions each year. A $10 monthly subscription costs $120 annually, and most people have 5-10 active subscriptions.</p>
<h3>Key Strategies</h3>
<ul>
<li><strong>Audit Regularly:</strong> Review all subscriptions quarterly</li>
<li><strong>Annual vs Monthly:</strong> Many services offer 10-20% discounts for annual payments</li>
<li><strong>Share Family Plans:</strong> Split costs with family or trusted friends</li>
<li><strong>Cancel Unused Services:</strong> If you haven''t used it in 30 days, consider canceling</li>
</ul>
<h3>Quick Calculation</h3>
<p>Take your monthly subscription costs and multiply by 12. This number might surprise you!</p>',
'Learn how small monthly subscriptions add up and strategies to optimize your spending.',
'beginner', 5, '["budgeting", "subscriptions", "cost-saving"]', TRUE),

('Setting Realistic Budgets', 'setting-realistic-budgets', 'budgeting',
'<h3>The 50/30/20 Rule</h3>
<p>A popular budgeting framework allocates:</p>
<ul>
<li><strong>50%</strong> for needs (housing, utilities, groceries)</li>
<li><strong>30%</strong> for wants (entertainment, dining out, subscriptions)</li>
<li><strong>20%</strong> for savings and debt repayment</li>
</ul>
<h3>Subscription Budgeting</h3>
<p>Subscriptions typically fall into the "wants" category. A good rule of thumb is to limit subscriptions to 5-8% of your take-home pay.</p>
<h3>Tips for Success</h3>
<ul>
<li>Start with your actual spending, not ideal goals</li>
<li>Build in buffer room for occasional overspending</li>
<li>Review and adjust monthly</li>
<li>Use tools like SubTrack to monitor spending automatically</li>
</ul>',
'Learn how to create budgets that actually work and where subscriptions fit in.',
'beginner', 7, '["budgeting", "financial-planning", "goals"]', TRUE),

('Maximizing Subscription Value', 'maximizing-subscription-value', 'subscription_management',
'<h3>Getting More for Less</h3>
<p>The goal isn''t always to spend less, but to get maximum value from what you do spend.</p>
<h3>Value Optimization Strategies</h3>
<ul>
<li><strong>Feature Audit:</strong> Are you using premium features? If not, downgrade</li>
<li><strong>Usage Tracking:</strong> Monitor how often you actually use each service</li>
<li><strong>Bundle Opportunities:</strong> Sometimes bundles save money (Disney+, Hulu, ESPN+)</li>
<li><strong>Seasonal Subscriptions:</strong> Cancel and resubscribe based on usage patterns</li>
</ul>
<h3>The Value Formula</h3>
<p>Value = (Usage Frequency × Enjoyment/Utility) ÷ Cost</p>
<p>Services with low value scores are candidates for cancellation.</p>',
'Strategies to maximize the value you get from your subscription spending.',
'intermediate', 6, '["optimization", "value", "subscriptions"]', FALSE),

('Understanding Spending Patterns', 'understanding-spending-patterns', 'financial_planning',
'<h3>Your Spending Personality</h3>
<p>Everyone has different spending patterns. Understanding yours is key to better financial management.</p>
<h3>Common Patterns</h3>
<ul>
<li><strong>Seasonal Spender:</strong> Higher spending during holidays or specific seasons</li>
<li><strong>Impulse Subscriber:</strong> Signs up for services spontaneously</li>
<li><strong>Bundle Collector:</strong> Attracted to package deals and bundles</li>
<li><strong>Premium Seeker:</strong> Always upgrades to the highest tier</li>
</ul>
<h3>Using Data</h3>
<p>Tools like SubTrack help you see patterns in your spending over time. Look for:</p>
<ul>
<li>Months with unusually high spending</li>
<li>Categories that consistently exceed budgets</li>
<li>Services you sign up for but rarely use</li>
</ul>',
'Learn to identify and understand your unique spending patterns for better decision making.',
'intermediate', 8, '["analysis", "patterns", "self-awareness"]', FALSE);