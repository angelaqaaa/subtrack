<?php
/**
 * Additional methods for SubscriptionModel to handle lifecycle management
 * Add these methods to the existing SubscriptionModel.php file
 */

/*
    // Add these methods to the SubscriptionModel class:

    /**
     * Cancel/End a subscription
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
                    WHEN is_active = TRUE THEN 'Active'
                    WHEN end_date IS NOT NULL THEN 'Ended'
                    ELSE 'Unknown'
                END as subscription_status,
                DATEDIFF(COALESCE(end_date, NOW()), start_date) as duration_days
                FROM subscriptions
                WHERE user_id = ?";

        if (!$include_active) {
            $sql .= " AND is_active = FALSE";
        }

        $sql .= " ORDER BY start_date DESC";

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
                COUNT(CASE WHEN is_active = TRUE THEN 1 END) as active_subscriptions,
                COUNT(CASE WHEN is_active = FALSE THEN 1 END) as ended_subscriptions,
                SUM(CASE
                    WHEN billing_cycle = 'monthly' THEN
                        cost * GREATEST(1, DATEDIFF(COALESCE(end_date, NOW()), start_date) / 30)
                    WHEN billing_cycle = 'yearly' THEN
                        cost * GREATEST(1, DATEDIFF(COALESCE(end_date, NOW()), start_date) / 365)
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
     * Update the existing getSpendingSummary to only include active subscriptions
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
                    'monthly_cost' => (float)$result['monthly_cost'],
                    'annual_cost' => (float)$result['annual_cost']
                ];
            }
        }

        return ['subscription_count' => 0, 'monthly_cost' => 0, 'annual_cost' => 0];
    }

    /**
     * Validate subscription data with end date support
     */
    public function validateSubscriptionData($data) {
        $errors = [];

        // Existing validation...
        if(empty($data["service_name"])) {
            $errors["service_name"] = "Service name is required.";
        }

        if(empty($data["cost"]) || !is_numeric($data["cost"]) || $data["cost"] <= 0) {
            $errors["cost"] = "Please enter a valid cost.";
        }

        if(empty($data["billing_cycle"])) {
            $errors["billing_cycle"] = "Billing cycle is required.";
        }

        if(empty($data["start_date"])) {
            $errors["start_date"] = "Start date is required.";
        }

        // New validation for end date
        if (!empty($data["end_date"])) {
            if ($data["end_date"] <= $data["start_date"]) {
                $errors["end_date"] = "End date must be after start date.";
            }
        }

        return $errors;
    }
*/
?>