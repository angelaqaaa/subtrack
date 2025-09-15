<?php

class AuditLogger {
    private $pdo;

    public function __construct($database_connection) {
        $this->pdo = $database_connection;
    }

    /**
     * Log an activity to the audit trail
     */
    public function logActivity($user_id, $action, $entity_type, $entity_id = null, $details = [], $space_id = null) {
        $sql = "INSERT INTO activity_log (user_id, space_id, action, entity_type, entity_id, details, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        if($stmt = $this->pdo->prepare($sql)) {
            $details_json = json_encode($details);
            $ip_address = $this->getClientIpAddress();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $space_id, PDO::PARAM_INT);
            $stmt->bindParam(3, $action, PDO::PARAM_STR);
            $stmt->bindParam(4, $entity_type, PDO::PARAM_STR);
            $stmt->bindParam(5, $entity_id, PDO::PARAM_INT);
            $stmt->bindParam(6, $details_json, PDO::PARAM_STR);
            $stmt->bindParam(7, $ip_address, PDO::PARAM_STR);
            $stmt->bindParam(8, $user_agent, PDO::PARAM_STR);

            return $stmt->execute();
        }
        return false;
    }

    /**
     * Get activity log for a user (personal activities)
     */
    public function getUserActivities($user_id, $limit = 50) {
        $sql = "SELECT al.*, u.username
                FROM activity_log al
                INNER JOIN users u ON al.user_id = u.id
                WHERE al.user_id = ? AND al.space_id IS NULL
                ORDER BY al.created_at DESC
                LIMIT ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    /**
     * Get activity log for a space
     */
    public function getSpaceActivities($space_id, $limit = 100) {
        $sql = "SELECT al.*, u.username
                FROM activity_log al
                INNER JOIN users u ON al.user_id = u.id
                WHERE al.space_id = ?
                ORDER BY al.created_at DESC
                LIMIT ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $space_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    /**
     * Get recent system-wide activities (admin view)
     */
    public function getSystemActivities($limit = 100) {
        $sql = "SELECT al.*, u.username, s.name as space_name
                FROM activity_log al
                INNER JOIN users u ON al.user_id = u.id
                LEFT JOIN spaces s ON al.space_id = s.id
                ORDER BY al.created_at DESC
                LIMIT ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    /**
     * Format activity for display
     */
    public function formatActivity($activity) {
        $details = json_decode($activity['details'], true) ?? [];
        $timestamp = date('M d, Y g:i A', strtotime($activity['created_at']));
        $username = htmlspecialchars($activity['username']);

        switch($activity['action']) {
            case 'subscription_created':
                return [
                    'icon' => 'bi-plus-circle',
                    'color' => 'success',
                    'message' => "{$username} added subscription \"{$details['service_name']}\"",
                    'timestamp' => $timestamp,
                    'details' => $details
                ];

            case 'subscription_updated':
                return [
                    'icon' => 'bi-pencil',
                    'color' => 'info',
                    'message' => "{$username} updated subscription \"{$details['service_name']}\"",
                    'timestamp' => $timestamp,
                    'details' => $details
                ];

            case 'subscription_deleted':
                return [
                    'icon' => 'bi-trash',
                    'color' => 'danger',
                    'message' => "{$username} deleted subscription \"{$details['service_name']}\"",
                    'timestamp' => $timestamp,
                    'details' => $details
                ];

            case 'space_created':
                return [
                    'icon' => 'bi-people',
                    'color' => 'primary',
                    'message' => "{$username} created shared space \"{$details['space_name']}\"",
                    'timestamp' => $timestamp,
                    'details' => $details
                ];

            case 'user_invited':
                return [
                    'icon' => 'bi-person-plus',
                    'color' => 'info',
                    'message' => "{$username} invited {$details['invitee_email']} as {$details['role']}",
                    'timestamp' => $timestamp,
                    'details' => $details
                ];

            case 'user_role_changed':
                return [
                    'icon' => 'bi-shield',
                    'color' => 'warning',
                    'message' => "{$username} changed {$details['target_user']}'s role to {$details['new_role']}",
                    'timestamp' => $timestamp,
                    'details' => $details
                ];

            case 'user_removed':
                return [
                    'icon' => 'bi-person-dash',
                    'color' => 'danger',
                    'message' => "{$username} removed {$details['target_user']} from the space",
                    'timestamp' => $timestamp,
                    'details' => $details
                ];

            case 'login':
                return [
                    'icon' => 'bi-box-arrow-in-right',
                    'color' => 'success',
                    'message' => "{$username} logged in",
                    'timestamp' => $timestamp,
                    'details' => $details
                ];

            case 'logout':
                return [
                    'icon' => 'bi-box-arrow-right',
                    'color' => 'secondary',
                    'message' => "{$username} logged out",
                    'timestamp' => $timestamp,
                    'details' => $details
                ];

            case 'space_left':
                return [
                    'icon' => 'bi-person-dash',
                    'color' => 'warning',
                    'message' => "{$username} left the space",
                    'timestamp' => $timestamp,
                    'details' => $details
                ];

            case 'member_removed':
                return [
                    'icon' => 'bi-person-x',
                    'color' => 'danger',
                    'message' => "{$username} removed a member from the space",
                    'timestamp' => $timestamp,
                    'details' => $details
                ];

            default:
                return [
                    'icon' => 'bi-info-circle',
                    'color' => 'secondary',
                    'message' => "{$username} performed {$activity['action']} on {$activity['entity_type']}",
                    'timestamp' => $timestamp,
                    'details' => $details
                ];
        }
    }

    /**
     * Get client IP address
     */
    private function getClientIpAddress() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Clean old audit logs (data retention)
     */
    public function cleanOldLogs($days = 365) {
        $sql = "DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $days, PDO::PARAM_INT);
            return $stmt->execute();
        }
        return false;
    }
}
?>