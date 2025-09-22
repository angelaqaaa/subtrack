<?php

class SubscriptionModel {
    private $pdo;

    public function __construct($database_connection) {
        $this->pdo = $database_connection;
    }

    /**
     * Get all subscriptions for a specific user (personal only)
     */
    public function getSubscriptionsByUser($user_id) {
        $sql = "SELECT * FROM subscriptions WHERE user_id = ? AND space_id IS NULL ORDER BY created_at DESC";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        return [];
    }

    /**
     * Get only active subscriptions for a user (for insights and calculations)
     */
    public function getActiveSubscriptionsByUser($user_id) {
        $sql = "SELECT * FROM subscriptions
                WHERE user_id = ?
                AND space_id IS NULL
                AND is_active = TRUE
                AND (end_date IS NULL OR end_date > NOW())
                ORDER BY created_at DESC";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        return [];
    }

    /**
     * Get ended subscriptions for separate insights
     */
    public function getEndedSubscriptionsByUser($user_id) {
        $sql = "SELECT * FROM subscriptions
                WHERE user_id = ?
                AND space_id IS NULL
                AND (is_active = FALSE OR end_date <= NOW())
                ORDER BY end_date DESC, created_at DESC";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        return [];
    }

    /**
     * Get all subscriptions for a specific space
     */
    public function getSubscriptionsBySpace($space_id) {
        $sql = "SELECT s.*, u.username as created_by
                FROM subscriptions s
                INNER JOIN users u ON s.user_id = u.id
                WHERE s.space_id = ?
                ORDER BY s.created_at DESC";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $space_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        return [];
    }

    /**
     * Get subscriptions within a date range for a user
     */
    public function getSubscriptionsByDateRange($user_id, $start_date, $end_date) {
        $sql = "SELECT * FROM subscriptions WHERE user_id = ? AND start_date BETWEEN ? AND ? ORDER BY start_date DESC";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $start_date, PDO::PARAM_STR);
            $stmt->bindParam(3, $end_date, PDO::PARAM_STR);

            if($stmt->execute()) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        return [];
    }

    /**
     * Get a single subscription by ID and user ID
     */
    public function getSubscriptionById($subscription_id, $user_id) {
        $sql = "SELECT * FROM subscriptions WHERE id = ? AND user_id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $subscription_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $user_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }

        return false;
    }

    /**
     * Create a new subscription
     */
    public function createSubscription($data) {
        // Determine if subscription is active based on end_date
        $is_active = true;
        if (!empty($data['end_date']) && $data['end_date'] <= date('Y-m-d')) {
            $is_active = false;
        }

        $sql = "INSERT INTO subscriptions (user_id, service_name, cost, currency, billing_cycle, start_date, end_date, category, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $data['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(2, $data['service_name'], PDO::PARAM_STR);
            $stmt->bindParam(3, $data['cost'], PDO::PARAM_STR);
            $stmt->bindParam(4, $data['currency'], PDO::PARAM_STR);
            $stmt->bindParam(5, $data['billing_cycle'], PDO::PARAM_STR);
            $stmt->bindParam(6, $data['start_date'], PDO::PARAM_STR);
            $stmt->bindParam(7, $data['end_date'], PDO::PARAM_STR);
            $stmt->bindParam(8, $data['category'], PDO::PARAM_STR);
            $stmt->bindParam(9, $is_active, PDO::PARAM_BOOL);

            if($stmt->execute()) {
                return $this->pdo->lastInsertId();
            }
        }

        return false;
    }

    /**
     * Update an existing subscription
     */
    public function updateSubscription($subscription_id, $user_id, $data) {
        try {
            $this->pdo->beginTransaction();

            // Calculate is_active based on end_date
            $is_active = true;
            if (!empty($data['end_date'])) {
                $end_date = new DateTime($data['end_date']);
                $now = new DateTime();
                $is_active = $end_date > $now;
            }

            // Update subscription with recalculated active status
            $sql = "UPDATE subscriptions SET service_name=?, cost=?, currency=?, billing_cycle=?, start_date=?, end_date=?, category=?, is_active=? WHERE id=? AND user_id=?";

            if($stmt = $this->pdo->prepare($sql)) {
                $stmt->bindParam(1, $data['service_name'], PDO::PARAM_STR);
                $stmt->bindParam(2, $data['cost'], PDO::PARAM_STR);
                $stmt->bindParam(3, $data['currency'], PDO::PARAM_STR);
                $stmt->bindParam(4, $data['billing_cycle'], PDO::PARAM_STR);
                $stmt->bindParam(5, $data['start_date'], PDO::PARAM_STR);
                $stmt->bindParam(6, $data['end_date'], PDO::PARAM_STR);
                $stmt->bindParam(7, $data['category'], PDO::PARAM_STR);
                $stmt->bindParam(8, $is_active, PDO::PARAM_BOOL);
                $stmt->bindParam(9, $subscription_id, PDO::PARAM_INT);
                $stmt->bindParam(10, $user_id, PDO::PARAM_INT);

                $result = $stmt->execute();
                if ($result) {
                    $this->pdo->commit();
                    return true;
                } else {
                    $this->pdo->rollback();
                    return false;
                }
            }

            $this->pdo->rollback();
            return false;

        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log('Update subscription error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a subscription
     */
    public function deleteSubscription($subscription_id, $user_id) {
        $sql = "DELETE FROM subscriptions WHERE id = ? AND user_id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $subscription_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $user_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                return $stmt->rowCount() > 0;
            }
        }

        return false;
    }

    /**
     * Calculate spending summary for a user
     */
    public function getSpendingSummary($user_id) {
        $sql = "SELECT
            COUNT(*) as subscription_count,
            SUM(CASE WHEN billing_cycle = 'monthly' THEN cost ELSE cost/12 END) as monthly_cost,
            SUM(CASE WHEN billing_cycle = 'yearly' THEN cost ELSE cost*12 END) as annual_cost
        FROM subscriptions
        WHERE user_id = ? AND is_active = TRUE AND (end_date IS NULL OR end_date > NOW())";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return [
                    'subscription_count' => (int)$result['subscription_count'],
                    'monthly_cost' => (float)($result['monthly_cost'] ?? 0),
                    'annual_cost' => (float)($result['annual_cost'] ?? 0)
                ];
            }
        }

        return ['subscription_count' => 0, 'monthly_cost' => 0, 'annual_cost' => 0];
    }

    /**
     * Get spending by category for a user
     */
    public function getSpendingByCategory($user_id) {
        // Only include active subscriptions for category spending
        $subscriptions = $this->getActiveSubscriptionsByUser($user_id);
        $category_totals = [];

        foreach($subscriptions as $subscription) {
            $monthly_cost = $subscription['billing_cycle'] == 'monthly' ? $subscription['cost'] : $subscription['cost'] / 12;
            $category = $subscription['category'] ?: 'Other';

            if(!isset($category_totals[$category])) {
                $category_totals[$category] = 0;
            }
            $category_totals[$category] += $monthly_cost;
        }

        return $category_totals;
    }

    /**
     * Get historical spending data (last 12 months)
     */
    public function getHistoricalSpending($user_id) {
        $sql = "SELECT
            DATE_FORMAT(start_date, '%Y-%m') as month,
            SUM(CASE WHEN billing_cycle = 'monthly' THEN cost ELSE cost/12 END) as total_monthly_cost
        FROM subscriptions
        WHERE user_id = ? AND start_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(start_date, '%Y-%m')
        ORDER BY month ASC";

        $historical_data = [];

        // Create full 12-month array with zeros
        for($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $historical_data[$month] = 0;
        }

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Fill in actual data
                foreach($results as $row) {
                    $historical_data[$row['month']] = (float)$row['total_monthly_cost'];
                }
            }
        }

        return $historical_data;
    }

    /**
     * Validate subscription data
     */
    public function validateSubscriptionData($data) {
        $errors = [];

        if(empty(trim($data['service_name']))) {
            $errors['service_name'] = "Please enter a service name.";
        }

        if(empty(trim($data['cost']))) {
            $errors['cost'] = "Please enter the cost.";
        } elseif(!is_numeric($data['cost']) || $data['cost'] <= 0) {
            $errors['cost'] = "Please enter a valid cost amount.";
        }

        if(empty(trim($data['billing_cycle']))) {
            $errors['billing_cycle'] = "Please select a billing cycle.";
        } elseif(!in_array($data['billing_cycle'], ['monthly', 'yearly'])) {
            $errors['billing_cycle'] = "Please select a valid billing cycle.";
        }

        if(empty(trim($data['start_date']))) {
            $errors['start_date'] = "Please enter a start date.";
        }

        // Validate end date if provided
        if (!empty($data["end_date"])) {
            if ($data["end_date"] <= $data["start_date"]) {
                $errors["end_date"] = "End date must be after start date.";
            }
        }

        return $errors;
    }

    /**
     * Generate HTML for subscription table row
     */
    public function generateSubscriptionRowHTML($subscription) {
        return '
            <td>
                <strong>' . htmlspecialchars($subscription['service_name']) . '</strong>
                <br><small class="text-muted">Started: ' . date('M d, Y', strtotime($subscription['start_date'])) . '</small>
            </td>
            <td>
                <strong>' . htmlspecialchars($subscription['currency']) . ' ' . number_format($subscription['cost'], 2) . '</strong>
            </td>
            <td>
                <span class="badge ' . ($subscription['billing_cycle'] == 'monthly' ? 'bg-primary' : 'bg-success') . '">
                    ' . ucfirst($subscription['billing_cycle']) . '
                </span>
            </td>
            <td>
                <span class="badge bg-secondary">' . htmlspecialchars($subscription['category'] ?: 'Other') . '</span>
            </td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="edit_subscription.php?id=' . $subscription['id'] . '" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <button type="button" class="btn btn-outline-danger btn-sm delete-subscription-btn" data-id="' . $subscription['id'] . '">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        ';
    }

    /**
     * Get spending summary for a space
     */
    public function getSpaceSpendingSummary($space_id) {
        $sql = "SELECT
                    COUNT(*) as subscription_count,
                    SUM(CASE WHEN billing_cycle = 'monthly' THEN cost ELSE cost/12 END) as monthly_cost,
                    SUM(CASE WHEN billing_cycle = 'yearly' THEN cost ELSE cost*12 END) as annual_cost
                FROM subscriptions
                WHERE space_id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $space_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                return [
                    'subscription_count' => (int)$result['subscription_count'],
                    'monthly_cost' => (float)$result['monthly_cost'],
                    'annual_cost' => (float)$result['annual_cost']
                ];
            }
        }

        return [
            'subscription_count' => 0,
            'monthly_cost' => 0.0,
            'annual_cost' => 0.0
        ];
    }

    /**
     * Get spending by category for a space
     */
    public function getSpaceSpendingByCategory($space_id) {
        $sql = "SELECT
                    COALESCE(category, 'Other') as category,
                    SUM(CASE WHEN billing_cycle = 'monthly' THEN cost ELSE cost/12 END) as total
                FROM subscriptions
                WHERE space_id = ?
                GROUP BY COALESCE(category, 'Other')";

        $categories = [];

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $space_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach($results as $row) {
                    $categories[$row['category']] = (float)$row['total'];
                }
            }
        }

        return $categories;
    }

    /**
     * Create subscription with optional space_id
     */
    public function createSubscriptionWithSpace($data) {
        // Determine if subscription is active based on end_date
        $is_active = true;
        if (!empty($data['end_date']) && $data['end_date'] <= date('Y-m-d')) {
            $is_active = false;
        }

        $sql = "INSERT INTO subscriptions (user_id, service_name, cost, currency, billing_cycle, start_date, end_date, category, space_id, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $data['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(2, $data['service_name'], PDO::PARAM_STR);
            $stmt->bindParam(3, $data['cost'], PDO::PARAM_STR);
            $stmt->bindParam(4, $data['currency'], PDO::PARAM_STR);
            $stmt->bindParam(5, $data['billing_cycle'], PDO::PARAM_STR);
            $stmt->bindParam(6, $data['start_date'], PDO::PARAM_STR);
            $stmt->bindParam(7, $data['end_date'], PDO::PARAM_STR);
            $stmt->bindParam(8, $data['category'], PDO::PARAM_STR);
            $stmt->bindParam(9, $data['space_id'], PDO::PARAM_INT);
            $stmt->bindParam(10, $is_active, PDO::PARAM_BOOL);

            if($stmt->execute()) {
                return $this->pdo->lastInsertId();
            }
        }
        return false;
    }

    /**
     * End/Cancel a subscription
     */
    public function endSubscription($subscription_id, $user_id, $end_date = null, $reason = null) {
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }

        $stmt = $this->pdo->prepare("
            UPDATE subscriptions
            SET end_date = ?, is_active = FALSE, cancellation_reason = ?, updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");

        return $stmt->execute([$end_date, $reason, $subscription_id, $user_id]);
    }

    /**
     * Reactivate a cancelled subscription
     */
    public function reactivateSubscription($subscription_id, $user_id, $new_start_date = null) {
        if (!$new_start_date) {
            $new_start_date = date('Y-m-d');
        }

        $stmt = $this->pdo->prepare("
            UPDATE subscriptions
            SET is_active = TRUE, end_date = NULL, cancellation_reason = NULL,
                start_date = ?, updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");

        return $stmt->execute([$new_start_date, $subscription_id, $user_id]);
    }

    /**
     * Get subscription history (including ended ones)
     */
    public function getSubscriptionHistory($user_id, $include_active = true) {
        $sql = "SELECT *,
                CASE
                    WHEN is_active = TRUE AND (end_date IS NULL OR end_date > NOW()) THEN 'Active'
                    WHEN end_date IS NOT NULL AND end_date <= NOW() THEN 'Ended'
                    WHEN is_active = FALSE THEN 'Cancelled'
                    ELSE 'Unknown'
                END as subscription_status,
                DATEDIFF(COALESCE(end_date, NOW()), start_date) as duration_days
                FROM subscriptions
                WHERE user_id = ?";

        if (!$include_active) {
            $sql .= " AND (is_active = FALSE OR end_date <= NOW())";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        if($stmt->execute([$user_id])) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    }

    /**
     * Get active subscriptions only
     */
    public function getActiveSubscriptions($user_id) {
        $sql = "SELECT * FROM subscriptions
                WHERE user_id = ? AND is_active = TRUE AND (end_date IS NULL OR end_date > NOW())
                ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        if($stmt->execute([$user_id])) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    }

    /**
     * Get subscriptions expiring soon
     */
    public function getExpiringSubscriptions($user_id, $days_ahead = 30) {
        $sql = "SELECT * FROM subscriptions
                WHERE user_id = ? AND is_active = TRUE
                AND end_date IS NOT NULL
                AND end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)
                ORDER BY end_date ASC";

        $stmt = $this->pdo->prepare($sql);
        if($stmt->execute([$user_id, $days_ahead])) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    }

    /**
     * Calculate total lifetime spending
     */
    public function getLifetimeSpending($user_id) {
        $sql = "SELECT
                COUNT(*) as total_subscriptions,
                COUNT(CASE WHEN is_active = TRUE AND (end_date IS NULL OR end_date > NOW()) THEN 1 END) as active_subscriptions,
                COUNT(CASE WHEN is_active = FALSE OR (end_date IS NOT NULL AND end_date <= NOW()) THEN 1 END) as ended_subscriptions,
                SUM(CASE
                    WHEN billing_cycle = 'monthly' THEN
                        cost * GREATEST(1, DATEDIFF(COALESCE(end_date, NOW()), start_date) / 30.44)
                    WHEN billing_cycle = 'yearly' THEN
                        cost * GREATEST(1, DATEDIFF(COALESCE(end_date, NOW()), start_date) / 365.25)
                    WHEN billing_cycle = 'weekly' THEN
                        cost * GREATEST(1, DATEDIFF(COALESCE(end_date, NOW()), start_date) / 7)
                    ELSE cost
                END) as lifetime_spent
                FROM subscriptions
                WHERE user_id = ?";

        $stmt = $this->pdo->prepare($sql);
        if($stmt->execute([$user_id])) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'total_subscriptions' => (int)$result['total_subscriptions'],
                'active_subscriptions' => (int)$result['active_subscriptions'],
                'ended_subscriptions' => (int)$result['ended_subscriptions'],
                'lifetime_spent' => (float)$result['lifetime_spent']
            ];
        }

        return [
            'total_subscriptions' => 0,
            'active_subscriptions' => 0,
            'ended_subscriptions' => 0,
            'lifetime_spent' => 0.0
        ];
    }

    /**
     * Update subscription status (activate/deactivate)
     */
    public function updateSubscriptionStatus($subscription_id, $is_active, $user_id) {
        $sql = "UPDATE subscriptions SET is_active = ? WHERE id = ? AND user_id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $is_active, PDO::PARAM_BOOL);
            $stmt->bindParam(2, $subscription_id, PDO::PARAM_INT);
            $stmt->bindParam(3, $user_id, PDO::PARAM_INT);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Update category name across all user's subscriptions
     */
    public function updateCategoryName($user_id, $old_category_name, $new_category_name) {
        $sql = "UPDATE subscriptions SET category = ? WHERE user_id = ? AND category = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $new_category_name, PDO::PARAM_STR);
            $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(3, $old_category_name, PDO::PARAM_STR);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Also update category name in space subscriptions
     */
    public function updateSpaceCategoryName($user_id, $old_category_name, $new_category_name) {
        $sql = "UPDATE space_subscriptions SET category = ? WHERE added_by = ? AND category = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $new_category_name, PDO::PARAM_STR);
            $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(3, $old_category_name, PDO::PARAM_STR);
            return $stmt->execute();
        }
        return false;
    }


}
?>