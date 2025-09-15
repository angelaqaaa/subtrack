<?php

class SpaceModel {
    private $pdo;

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
            $stmt->bindParam(6, $accepted_at, PDO::PARAM_STR);

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
                INNER JOIN users inviter ON su.invited_by = inviter.id
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

                // Viewer can only view
                if($required_role === 'viewer' && $user_role === 'viewer') return true;
            }
        }
        return false;
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
        $sql = "UPDATE space_users SET role = ? WHERE space_id = ? AND user_id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $new_role, PDO::PARAM_STR);
            $stmt->bindParam(2, $space_id, PDO::PARAM_INT);
            $stmt->bindParam(3, $user_id, PDO::PARAM_INT);
            return $stmt->execute();
        }
        return false;
    }
}
?>