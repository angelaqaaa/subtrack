<?php

class SpaceModel {
    private $pdo;
    private $editorRoleSupported = null;

    public function __construct($database_connection) {
        $this->pdo = $database_connection;
    }

    /**
     * Create a new shared space
     */
    public function createSpace($name, $description, $owner_id) {
        $sql = "INSERT INTO spaces (name, description, owner_id) VALUES (?, ?, ?)";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $name, PDO::PARAM_STR);
            $stmt->bindParam(2, $description, PDO::PARAM_STR);
            $stmt->bindParam(3, $owner_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                $space_id = $this->pdo->lastInsertId();

                // Automatically add owner as admin
                $this->addUserToSpace($space_id, $owner_id, 'admin', $owner_id, true);

                return $space_id;
            }
        }
        return false;
    }

    /**
     * Get all spaces for a user (owned + member of)
     */
    public function getUserSpaces($user_id) {
        $sql = "SELECT s.*,
                       su.role as user_role,
                       u.username as owner_username,
                       (SELECT COUNT(*) FROM space_users WHERE space_id = s.id AND status = 'accepted') as member_count
                FROM spaces s
                INNER JOIN space_users su ON s.id = su.space_id
                INNER JOIN users u ON s.owner_id = u.id
                WHERE su.user_id = ? AND su.status = 'accepted'
                ORDER BY s.created_at DESC";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    /**
     * Get space details with user's role
     */
    public function getSpaceWithUserRole($space_id, $user_id) {
        $sql = "SELECT s.*,
                       su.role as user_role,
                       u.username as owner_username
                FROM spaces s
                INNER JOIN space_users su ON s.id = su.space_id
                INNER JOIN users u ON s.owner_id = u.id
                WHERE s.id = ? AND su.user_id = ? AND su.status = 'accepted'";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $space_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Add user to space with role
     */
    public function addUserToSpace($space_id, $user_id, $role, $invited_by, $auto_accept = false) {
        if (!$this->ensureRoleIsSupported($role)) {
            return false;
        }

        $allowed_roles = ['admin', 'editor', 'viewer'];
        if (!in_array($role, $allowed_roles, true)) {
            $role = 'viewer';
        }

        $status = $auto_accept ? 'accepted' : 'pending';
        $accepted_at = $auto_accept ? date('Y-m-d H:i:s') : null;

        $sql = "INSERT INTO space_users (space_id, user_id, role, invited_by, status, accepted_at)
                VALUES (?, ?, ?, ?, ?, ?)";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $space_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(3, $role, PDO::PARAM_STR);
            $stmt->bindParam(4, $invited_by, PDO::PARAM_INT);
            $stmt->bindParam(5, $status, PDO::PARAM_STR);
            if ($accepted_at === null) {
                $stmt->bindValue(6, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(6, $accepted_at, PDO::PARAM_STR);
            }

            return $stmt->execute();
        }
        return false;
    }

    /**
     * Re-send an invitation by updating an existing declined membership
     */
    public function reinviteUser($space_id, $user_id, $role, $invited_by) {
        if (!$this->ensureRoleIsSupported($role)) {
            return false;
        }

        $allowed_roles = ['admin', 'editor', 'viewer'];
        if (!in_array($role, $allowed_roles, true)) {
            $role = 'viewer';
        }

        $sql = "UPDATE space_users
                SET role = ?, invited_by = ?, status = 'pending', invited_at = NOW(), accepted_at = NULL
                WHERE space_id = ? AND user_id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $role, PDO::PARAM_STR);
            $stmt->bindParam(2, $invited_by, PDO::PARAM_INT);
            $stmt->bindParam(3, $space_id, PDO::PARAM_INT);
            $stmt->bindParam(4, $user_id, PDO::PARAM_INT);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Get space members
     */
    public function getSpaceMembers($space_id) {
        $sql = "SELECT su.*, u.username, u.email, inviter.username as invited_by_username
                FROM space_users su
                INNER JOIN users u ON su.user_id = u.id
                LEFT JOIN users inviter ON su.invited_by = inviter.id
                WHERE su.space_id = ?
                ORDER BY su.role DESC, su.invited_at ASC";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $space_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    /**
     * Check if user has permission in space
     */
    public function hasPermission($space_id, $user_id, $required_role = 'viewer') {
        $sql = "SELECT role FROM space_users
                WHERE space_id = ? AND user_id = ? AND status = 'accepted'";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $space_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if($result) {
                $user_role = $result['role'];

                // Admin can do everything
                if($user_role === 'admin') return true;

                // Editor can edit and view
                if($user_role === 'editor' && in_array($required_role, ['editor', 'viewer'])) return true;

                // Viewer can only view
                if($required_role === 'viewer' && $user_role === 'viewer') return true;
            }
        }
        return false;
    }

    /**
     * Determine if the database schema supports the editor role
     */
    public function supportsEditorRole() {
        if ($this->editorRoleSupported !== null) {
            return $this->editorRoleSupported;
        }

        $sql = "SELECT COLUMN_TYPE
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'space_users'
                  AND COLUMN_NAME = 'role'";

        if($stmt = $this->pdo->query($sql)) {
            $columnType = $stmt->fetchColumn();
            if (is_string($columnType)) {
                $this->editorRoleSupported = stripos($columnType, "'editor'") !== false;
                return $this->editorRoleSupported;
            }
        }

        $this->editorRoleSupported = false;
        return $this->editorRoleSupported;
    }

    /**
     * Ensure the requested role exists in the database enum definition
     */
    private function ensureRoleIsSupported($role) {
        if ($role !== 'editor') {
            return true;
        }

        if ($this->supportsEditorRole()) {
            return true;
        }

        try {
            $this->pdo->exec("ALTER TABLE space_users MODIFY COLUMN role ENUM('admin','editor','viewer') NOT NULL DEFAULT 'viewer'");
            $this->editorRoleSupported = true;
            return true;
        } catch (Exception $e) {
            error_log('Failed to add editor role to space_users table: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Public helper to make sure the editor role exists before performing operations
     */
    public function ensureEditorRoleSupport() {
        return $this->ensureRoleIsSupported('editor');
    }

    /**
     * Find user by email for invitations
     */
    public function findUserByEmail($email) {
        $sql = "SELECT id, username, email FROM users WHERE email = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Remove user from space
     */
    public function removeUserFromSpace($space_id, $user_id) {
        $sql = "DELETE FROM space_users WHERE space_id = ? AND user_id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $space_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Update user role in space
     */
    public function updateUserRole($space_id, $user_id, $new_role) {
        if (!$this->ensureRoleIsSupported($new_role)) {
            return false;
        }

        $allowed_roles = ['admin', 'editor', 'viewer'];
        if (!in_array($new_role, $allowed_roles, true)) {
            return false;
        }

        $sql = "UPDATE space_users SET role = ? WHERE space_id = ? AND user_id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $new_role, PDO::PARAM_STR);
            $stmt->bindParam(2, $space_id, PDO::PARAM_INT);
            $stmt->bindParam(3, $user_id, PDO::PARAM_INT);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Get pending invitations for a user
     */
    public function getPendingInvitations($user_id) {
        $sql = "SELECT s.*, su.role, su.invited_at, inviter.username as invited_by_username
                FROM spaces s
                INNER JOIN space_users su ON s.id = su.space_id
                INNER JOIN users inviter ON su.invited_by = inviter.id
                WHERE su.user_id = ? AND su.status = 'pending'
                ORDER BY su.invited_at DESC";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    /**
     * Accept space invitation
     */
    public function acceptInvitation($space_id, $user_id) {
        $sql = "UPDATE space_users SET status = 'accepted', accepted_at = NOW()
                WHERE space_id = ? AND user_id = ? AND status = 'pending'";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $space_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Reject space invitation
     */
    public function rejectInvitation($space_id, $user_id) {
        $sql = "DELETE FROM space_users
                WHERE space_id = ? AND user_id = ? AND status = 'pending'";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $space_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Check if user has any relationship with space (pending or accepted)
     */
    public function hasAnyRelationshipWithSpace($space_id, $user_id) {
        $sql = "SELECT status FROM space_users
                WHERE space_id = ? AND user_id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $space_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Delete a space (only owner can delete)
     */
    public function deleteSpace($space_id, $user_id) {
        try {
            // Start transaction
            $this->pdo->beginTransaction();

            // First remove all space members
            $sql1 = "DELETE FROM space_users WHERE space_id = ?";
            if($stmt1 = $this->pdo->prepare($sql1)) {
                $stmt1->bindParam(1, $space_id, PDO::PARAM_INT);
                $stmt1->execute();
            }

            // Then delete the space itself
            $sql2 = "DELETE FROM spaces WHERE id = ? AND owner_id = ?";
            if($stmt2 = $this->pdo->prepare($sql2)) {
                $stmt2->bindParam(1, $space_id, PDO::PARAM_INT);
                $stmt2->bindParam(2, $user_id, PDO::PARAM_INT);
                $result = $stmt2->execute();

                if($result && $stmt2->rowCount() > 0) {
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
            error_log('Delete space error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add a subscription to a space
     */
    public function addSubscription($space_id, $subscriptionData) {
        // Calculate is_active based on end_date
        $is_active = true;
        if (!empty($subscriptionData['end_date'])) {
            try {
                $end_date = new DateTime($subscriptionData['end_date']);
                $now = new DateTime();
                $is_active = $end_date > $now;
            } catch (Exception $e) {
                // If date parsing fails, default to active
                $is_active = true;
            }
        }

        $sql = "INSERT INTO space_subscriptions (space_id, service_name, cost, currency, billing_cycle, start_date, end_date, category, added_by, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $space_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $subscriptionData['service_name'], PDO::PARAM_STR);
            $stmt->bindParam(3, $subscriptionData['cost'], PDO::PARAM_STR);
            $stmt->bindParam(4, $subscriptionData['currency'], PDO::PARAM_STR);
            $stmt->bindParam(5, $subscriptionData['billing_cycle'], PDO::PARAM_STR);
            $stmt->bindParam(6, $subscriptionData['start_date'], PDO::PARAM_STR);
            $stmt->bindParam(7, $subscriptionData['end_date'], PDO::PARAM_STR);
            $stmt->bindParam(8, $subscriptionData['category'], PDO::PARAM_STR);
            $stmt->bindParam(9, $subscriptionData['added_by'], PDO::PARAM_INT);
            $stmt->bindParam(10, $is_active, PDO::PARAM_BOOL);

            if($stmt->execute()) {
                return $this->pdo->lastInsertId();
            }
        }
        return false;
    }

    /**
     * Sync existing user subscriptions to a space
     */
    public function syncExistingSubscriptions($space_id, $subscription_ids, $user_id) {
        try {
            $this->pdo->beginTransaction();
            $synced_count = 0;

            foreach($subscription_ids as $sub_id) {
                // Get the subscription data from user's subscriptions
                $sql = "SELECT * FROM subscriptions WHERE id = ? AND user_id = ?";
                if($stmt = $this->pdo->prepare($sql)) {
                    $stmt->bindParam(1, $sub_id, PDO::PARAM_INT);
                    $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
                    $stmt->execute();
                    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

                    if($subscription) {
                        // Check if already synced to this space
                        $check_sql = "SELECT id FROM space_subscriptions WHERE space_id = ? AND service_name = ? AND added_by = ?";
                        if($check_stmt = $this->pdo->prepare($check_sql)) {
                            $check_stmt->bindParam(1, $space_id, PDO::PARAM_INT);
                            $check_stmt->bindParam(2, $subscription['service_name'], PDO::PARAM_STR);
                            $check_stmt->bindParam(3, $user_id, PDO::PARAM_INT);
                            $check_stmt->execute();

                            if(!$check_stmt->fetch()) {
                                // Not already synced, so add it
                                $subscriptionData = [
                                    'service_name' => $subscription['service_name'],
                                    'cost' => $subscription['cost'],
                                    'currency' => $subscription['currency'],
                                    'billing_cycle' => $subscription['billing_cycle'],
                                    'start_date' => $subscription['start_date'],
                                    'end_date' => $subscription['end_date'],
                                    'category' => $subscription['category'],
                                    'added_by' => $user_id
                                ];

                                if($this->addSubscription($space_id, $subscriptionData)) {
                                    $synced_count++;
                                }
                            }
                        }
                    }
                }
            }

            $this->pdo->commit();
            return $synced_count;

        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log('Sync subscriptions error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all subscriptions in a space
     */
    public function getSpaceSubscriptions($space_id) {
        $sql = "SELECT ss.*, u.username as added_by_username
                FROM space_subscriptions ss
                INNER JOIN users u ON ss.added_by = u.id
                WHERE ss.space_id = ?
                ORDER BY ss.created_at DESC";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $space_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }
}
?>
