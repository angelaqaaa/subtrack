<?php

class InsightsModel {
    private $pdo;

    public function __construct($database_connection) {
        $this->pdo = $database_connection;
    }

    /**
     * Generate financial insights for a user
     */
    public function generateInsights($user_id) {
        $insights = [];

        // Get user's subscription data
        $stmt = $this->pdo->prepare("
            SELECT s.*,
                   CASE
                       WHEN s.billing_cycle = 'monthly' THEN s.cost
                       WHEN s.billing_cycle = 'yearly' THEN s.cost / 12
                       WHEN s.billing_cycle = 'weekly' THEN s.cost * 52 / 12
                   END as monthly_cost
            FROM subscriptions s
            WHERE s.user_id = ? AND s.status = 'active'
        ");
        $stmt->execute([$user_id]);
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($subscriptions)) {
            return $insights;
        }

        // 1. Saving Opportunity: Annual vs Monthly billing
        $insights = array_merge($insights, $this->findAnnualSavingOpportunities($user_id, $subscriptions));

        // 2. Spending Alert: High-cost subscriptions
        $insights = array_merge($insights, $this->findHighCostAlerts($user_id, $subscriptions));

        // 3. Category Analysis: Overspending in categories
        $insights = array_merge($insights, $this->analyzeCategorySpending($user_id, $subscriptions));

        // 4. Trend Analysis: Recent spending increases
        $insights = array_merge($insights, $this->analyzeTrends($user_id));

        // Save new insights to database
        $this->saveInsights($user_id, $insights);

        return $insights;
    }

    /**
     * Find opportunities to save by switching to annual billing
     */
    private function findAnnualSavingOpportunities($user_id, $subscriptions) {
        $opportunities = [];
        $total_potential_savings = 0;

        foreach ($subscriptions as $subscription) {
            if ($subscription['billing_cycle'] === 'monthly' && $subscription['cost'] >= 5) {
                // Assume 15% average discount for annual billing
                $annual_cost = $subscription['cost'] * 12 * 0.85; // 15% discount
                $current_annual_cost = $subscription['cost'] * 12;
                $potential_savings = $current_annual_cost - $annual_cost;
                $total_potential_savings += $potential_savings;
            }
        }

        if ($total_potential_savings > 20) {
            $opportunities[] = [
                'type' => 'saving_opportunity',
                'title' => 'Switch to Annual Billing',
                'description' => "You could save approximately $" . number_format($total_potential_savings, 2) . " per year by switching eligible subscriptions to annual billing.",
                'impact_score' => min(10, floor($total_potential_savings / 10)),
                'data' => [
                    'potential_savings' => $total_potential_savings,
                    'affected_subscriptions' => count($subscriptions)
                ]
            ];
        }

        return $opportunities;
    }

    /**
     * Find high-cost subscription alerts
     */
    private function findHighCostAlerts($user_id, $subscriptions) {
        $alerts = [];
        $total_monthly = array_sum(array_column($subscriptions, 'monthly_cost'));
        $average_cost = $total_monthly / count($subscriptions);

        // Find subscriptions that are significantly above average
        foreach ($subscriptions as $subscription) {
            if ($subscription['monthly_cost'] > $average_cost * 2 && $subscription['monthly_cost'] > 25) {
                $alerts[] = [
                    'type' => 'spending_alert',
                    'title' => 'High-Cost Subscription Alert',
                    'description' => "Your {$subscription['service_name']} subscription costs " .
                                   number_format($subscription['monthly_cost'], 2) . "/month, which is " .
                                   number_format(($subscription['monthly_cost'] / $average_cost - 1) * 100, 0) .
                                   "% above your average subscription cost. Consider if you're getting full value.",
                    'impact_score' => min(10, floor($subscription['monthly_cost'] / 10)),
                    'data' => [
                        'subscription_id' => $subscription['id'],
                        'service_name' => $subscription['service_name'],
                        'monthly_cost' => $subscription['monthly_cost'],
                        'average_cost' => $average_cost
                    ]
                ];
            }
        }

        return $alerts;
    }

    /**
     * Analyze category spending patterns
     */
    private function analyzeCategorySpending($user_id, $subscriptions) {
        $analysis = [];

        // Group by category
        $categories = [];
        foreach ($subscriptions as $subscription) {
            $category = $subscription['category'] ?: 'Other';
            if (!isset($categories[$category])) {
                $categories[$category] = ['count' => 0, 'cost' => 0];
            }
            $categories[$category]['count']++;
            $categories[$category]['cost'] += $subscription['monthly_cost'];
        }

        // Find categories with high spending
        $total_spending = array_sum(array_column($categories, 'cost'));
        foreach ($categories as $category => $data) {
            $percentage = ($data['cost'] / $total_spending) * 100;

            if ($percentage > 40 && $data['cost'] > 30) { // More than 40% of budget in one category
                $analysis[] = [
                    'type' => 'category_analysis',
                    'title' => 'Category Concentration Alert',
                    'description' => "You're spending " . number_format($percentage, 1) .
                                   "% of your subscription budget on {$category} services (" .
                                   number_format($data['cost'], 2) . "/month). Consider diversifying or reviewing these subscriptions.",
                    'impact_score' => min(10, floor($percentage / 5)),
                    'data' => [
                        'category' => $category,
                        'monthly_cost' => $data['cost'],
                        'percentage' => $percentage,
                        'subscription_count' => $data['count']
                    ]
                ];
            }
        }

        return $analysis;
    }

    /**
     * Analyze spending trends over time
     */
    private function analyzeTrends($user_id) {
        $trends = [];

        // Compare current month to previous months
        $stmt = $this->pdo->prepare("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as subscription_count,
                SUM(CASE
                    WHEN billing_cycle = 'monthly' THEN cost
                    WHEN billing_cycle = 'yearly' THEN cost / 12
                    WHEN billing_cycle = 'weekly' THEN cost * 52 / 12
                END) as monthly_cost
            FROM subscriptions
            WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
            LIMIT 6
        ");
        $stmt->execute([$user_id]);
        $monthly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($monthly_data) >= 2) {
            $current_month = $monthly_data[0];
            $previous_month = $monthly_data[1];

            $cost_change = $current_month['monthly_cost'] - $previous_month['monthly_cost'];
            $count_change = $current_month['subscription_count'] - $previous_month['subscription_count'];

            if ($cost_change > 20) { // Significant increase
                $trends[] = [
                    'type' => 'trend_analysis',
                    'title' => 'Spending Increase Detected',
                    'description' => "Your subscription spending increased by $" .
                                   number_format($cost_change, 2) . " this month. " .
                                   ($count_change > 0 ? "You added {$count_change} new subscription(s)." : "This was due to cost increases in existing subscriptions."),
                    'impact_score' => min(10, floor($cost_change / 10)),
                    'data' => [
                        'cost_change' => $cost_change,
                        'count_change' => $count_change,
                        'current_total' => $current_month['monthly_cost'],
                        'previous_total' => $previous_month['monthly_cost']
                    ]
                ];
            }
        }

        return $trends;
    }

    /**
     * Save insights to database
     */
    private function saveInsights($user_id, $insights) {
        // First, mark old insights as dismissed (except applied ones)
        $stmt = $this->pdo->prepare("
            UPDATE insights
            SET status = 'dismissed'
            WHERE user_id = ? AND status = 'active' AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$user_id]);

        // Insert new insights
        foreach ($insights as $insight) {
            // Check if similar insight already exists by title and type
            $stmt = $this->pdo->prepare("
                SELECT id FROM insights
                WHERE user_id = ? AND type = ? AND title = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([
                $user_id,
                $insight['type'],
                $insight['title']
            ]);

            if (!$stmt->fetch()) {
                // Insert new insight
                $stmt = $this->pdo->prepare("
                    INSERT INTO insights (user_id, type, title, description, impact_score, data, expires_at)
                    VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
                ");
                $stmt->execute([
                    $user_id,
                    $insight['type'],
                    $insight['title'],
                    $insight['description'],
                    $insight['impact_score'],
                    json_encode($insight['data'])
                ]);
            }
        }
    }

    /**
     * Get active insights for a user
     */
    public function getUserInsights($user_id, $limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM insights
            WHERE user_id = ? AND status = 'active' AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY impact_score DESC, created_at DESC
            LIMIT " . (int)$limit . "
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Dismiss an insight
     */
    public function dismissInsight($insight_id, $user_id) {
        $stmt = $this->pdo->prepare("
            UPDATE insights SET status = 'dismissed'
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$insight_id, $user_id]);
    }

    /**
     * Mark insight as applied
     */
    public function applyInsight($insight_id, $user_id) {
        $stmt = $this->pdo->prepare("
            UPDATE insights SET status = 'applied'
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$insight_id, $user_id]);
    }

    /**
     * Get educational content
     */
    public function getEducationalContent($category = null, $featured_only = false, $limit = 10) {
        $sql = "SELECT * FROM educational_content WHERE 1=1";
        $params = [];

        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        if ($featured_only) {
            $sql .= " AND is_featured = 1";
        }

        $sql .= " ORDER BY is_featured DESC, view_count DESC, created_at DESC LIMIT " . (int)$limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get single educational content by slug
     */
    public function getEducationalContentBySlug($slug) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM educational_content WHERE slug = ?
        ");
        $stmt->execute([$slug]);

        $content = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($content) {
            // Increment view count
            $update_stmt = $this->pdo->prepare("
                UPDATE educational_content SET view_count = view_count + 1 WHERE id = ?
            ");
            $update_stmt->execute([$content['id']]);
        }

        return $content;
    }

    /**
     * Track user's educational progress
     */
    public function updateEducationalProgress($user_id, $content_id, $status, $progress_percent = 0) {
        $stmt = $this->pdo->prepare("
            INSERT INTO user_education_progress (user_id, content_id, status, progress_percent, completed_at)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                progress_percent = VALUES(progress_percent),
                completed_at = VALUES(completed_at)
        ");

        $completed_at = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
        return $stmt->execute([$user_id, $content_id, $status, $progress_percent, $completed_at]);
    }

    /**
     * Create or update spending goal
     */
    public function createSpendingGoal($user_id, $category, $monthly_limit, $period_months = 1) {
        $period_start = date('Y-m-01'); // First day of current month
        $period_end = date('Y-m-t', strtotime("+{$period_months} months")); // Last day of target month

        $stmt = $this->pdo->prepare("
            INSERT INTO spending_goals (user_id, category, monthly_limit, period_start, period_end)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                monthly_limit = VALUES(monthly_limit),
                period_end = VALUES(period_end),
                updated_at = NOW()
        ");

        return $stmt->execute([$user_id, $category, $monthly_limit, $period_start, $period_end]);
    }

    /**
     * Get user's spending goals
     */
    public function getUserSpendingGoals($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT sg.*,
                   COALESCE(spent.category_spending, 0) as current_spending,
                   ROUND((COALESCE(spent.category_spending, 0) / sg.monthly_limit) * 100, 1) as progress_percent
            FROM spending_goals sg
            LEFT JOIN (
                SELECT
                    s.category,
                    SUM(CASE
                        WHEN s.billing_cycle = 'monthly' THEN s.cost
                        WHEN s.billing_cycle = 'yearly' THEN s.cost / 12
                        WHEN s.billing_cycle = 'weekly' THEN s.cost * 52 / 12
                    END) as category_spending
                FROM subscriptions s
                WHERE s.user_id = ? AND s.status = 'active'
                GROUP BY s.category
            ) spent ON sg.category = spent.category
            WHERE sg.user_id = ? AND sg.status = 'active'
            ORDER BY sg.created_at DESC
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Award achievement to user
     */
    public function awardAchievement($user_id, $achievement_type, $title, $description, $data = []) {
        $stmt = $this->pdo->prepare("
            INSERT INTO user_achievements (user_id, achievement_type, title, description, data)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE achieved_at = NOW()
        ");

        return $stmt->execute([
            $user_id,
            $achievement_type,
            $title,
            $description,
            json_encode($data)
        ]);
    }

    /**
     * Get user's achievements
     */
    public function getUserAchievements($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM user_achievements
            WHERE user_id = ?
            ORDER BY achieved_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>