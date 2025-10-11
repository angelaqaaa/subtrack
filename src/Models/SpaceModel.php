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

        // Insert into subscriptions table with space_id
        $sql = "INSERT INTO subscriptions (user_id, space_id, service_name, cost, currency, billing_cycle, start_date, end_date, category, is_active, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $subscriptionData['added_by'], PDO::PARAM_INT);
            $stmt->bindParam(2, $space_id, PDO::PARAM_INT);
            $stmt->bindParam(3, $subscriptionData['service_name'], PDO::PARAM_STR);
            $stmt->bindParam(4, $subscriptionData['cost'], PDO::PARAM_STR);
            $stmt->bindParam(5, $subscriptionData['currency'], PDO::PARAM_STR);
            $stmt->bindParam(6, $subscriptionData['billing_cycle'], PDO::PARAM_STR);
            $stmt->bindParam(7, $subscriptionData['start_date'], PDO::PARAM_STR);
            $stmt->bindParam(8, $subscriptionData['end_date'], PDO::PARAM_STR);
            $stmt->bindParam(9, $subscriptionData['category'], PDO::PARAM_STR);
            $stmt->bindParam(10, $is_active, PDO::PARAM_BOOL);

            if($stmt->execute()) {
                error_log("Added subscription to space: sub_id=" . $this->pdo->lastInsertId() . ", space_id=$space_id");
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
                // Check if subscription exists and belongs to user
                $sql = "SELECT * FROM subscriptions WHERE id = ? AND user_id = ?";
                if($stmt = $this->pdo->prepare($sql)) {
                    $stmt->bindParam(1, $sub_id, PDO::PARAM_INT);
                    $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
                    $stmt->execute();
                    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

                    if($subscription) {
                        // Check if already synced to a space
                        if(empty($subscription['space_id']) || $subscription['space_id'] === null) {
                            // Update the subscription to link it to this space
                            $update_sql = "UPDATE subscriptions SET space_id = ?, updated_at = NOW() WHERE id = ?";
                            if($update_stmt = $this->pdo->prepare($update_sql)) {
                                $update_stmt->bindParam(1, $space_id, PDO::PARAM_INT);
                                $update_stmt->bindParam(2, $sub_id, PDO::PARAM_INT);
                                if($update_stmt->execute()) {
                                    $synced_count++;
                                    error_log("Synced subscription $sub_id to space $space_id");
                                }
                            }
                        } else {
                            error_log("Subscription $sub_id already synced to space {$subscription['space_id']}");
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
     * Remove subscription from space (unsync)
     * Sets space_id to NULL for the subscription
     */
    public function unsyncSubscription($subscription_id, $user_id) {
        try {
            // First get subscription details to verify ownership/access
            $check_sql = "SELECT space_id, user_id FROM subscriptions WHERE id = ?";
            $stmt = $this->pdo->prepare($check_sql);
            $stmt->execute([$subscription_id]);
            $sub = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$sub) {
                error_log("Unsync subscription: Subscription $subscription_id not found");
                return false;
            }

            // Verify user has permission
            if ($sub['space_id']) {
                // If subscription is in a space, verify user is a member
                if (!$this->hasPermission($sub['space_id'], $user_id, 'viewer')) {
                    error_log("Unsync subscription: User $user_id not authorized for space {$sub['space_id']}");
                    return false;
                }
            } else if ($sub['user_id'] != $user_id) {
                // If not in space, verify user owns the subscription
                error_log("Unsync subscription: User $user_id doesn't own subscription $subscription_id");
                return false;
            }

            // Remove from space by setting space_id to NULL
            $sql = "UPDATE subscriptions SET space_id = NULL, updated_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$subscription_id]);

            if ($stmt->rowCount() > 0) {
                error_log("Unsynced subscription $subscription_id from space {$sub['space_id']}");
                return true;
            }

            return false;
        } catch (Exception $e) {
            error_log("Unsync subscription error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all subscriptions in a space
     */
    public function getSpaceSubscriptions($space_id) {
        $sql = "SELECT s.*, u.username as added_by_username
                FROM subscriptions s
                INNER JOIN users u ON s.user_id = u.id
                WHERE s.space_id = ?
                ORDER BY s.created_at DESC";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $space_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    /**
     * Delete a space subscription
     * Requires user to be a member of the space
     */
    public function deleteSpaceSubscription($subscription_id, $space_id, $user_id) {
        try {
            // Verify user is a member of the space
            if (!$this->hasPermission($space_id, $user_id, 'viewer')) {
                error_log("Delete space subscription: User $user_id is not a member of space $space_id");
                return false;
            }

            // Delete from subscriptions table
            $sql = "DELETE FROM subscriptions WHERE id = ? AND space_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$subscription_id, $space_id]);

            error_log("Delete space subscription: sub_id=$subscription_id, space_id=$space_id, rows affected: " . $stmt->rowCount());
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Delete space subscription error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * End (pause) a space subscription
     * Requires user to be a member of the space
     */
    public function endSpaceSubscription($subscription_id, $space_id, $user_id, $end_date = null) {
        try {
            // Verify user is a member of the space
            if (!$this->hasPermission($space_id, $user_id, 'viewer')) {
                error_log("End space subscription: User $user_id is not a member of space $space_id");
                return false;
            }

            if (!$end_date) {
                $end_date = date('Y-m-d');
            }

            // Update subscription
            $sql = "UPDATE subscriptions
                    SET end_date = ?, is_active = 0, updated_at = NOW()
                    WHERE id = ? AND space_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$end_date, $subscription_id, $space_id]);

            error_log("End space subscription: sub_id=$subscription_id, space_id=$space_id, rows affected: " . $stmt->rowCount());
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("End space subscription error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reactivate a space subscription
     * Requires user to be a member of the space
     */
    public function reactivateSpaceSubscription($subscription_id, $space_id, $user_id) {
        try {
            // Verify user is a member of the space
            if (!$this->hasPermission($space_id, $user_id, 'viewer')) {
                error_log("Reactivate space subscription: User $user_id is not a member of space $space_id");
                return false;
            }

            // Update subscription
            $sql = "UPDATE subscriptions
                    SET end_date = NULL, is_active = 1, updated_at = NOW()
                    WHERE id = ? AND space_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$subscription_id, $space_id]);

            error_log("Reactivate space subscription: sub_id=$subscription_id, space_id=$space_id, rows affected: " . $stmt->rowCount());
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Reactivate space subscription error: " . $e->getMessage());
            return false;
        }
    }
}
?>
