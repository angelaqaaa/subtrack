<?php

class InvitationModel {
    private $pdo;

    public function __construct($database_connection) {
        $this->pdo = $database_connection;
    }

    /**
     * Send invitation to join space
     */
    public function createInvitation($space_id, $inviter_id, $email, $role = 'viewer') {
        // Check if user already invited or is member
        $existing = $this->checkExistingInvitation($space_id, $email);
        if ($existing) {
            return ['success' => false, 'message' => $existing['message']];
        }

        // Generate unique token
        $token = bin2hex(random_bytes(32));

        // Check if email belongs to existing user
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existing_user = $stmt->fetch();
        $invitee_id = $existing_user ? $existing_user['id'] : null;

        // Create invitation
        $stmt = $this->pdo->prepare("
            INSERT INTO space_invitations (space_id, inviter_id, invitee_email, invitee_id, role, invitation_token)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        if ($stmt->execute([$space_id, $inviter_id, $email, $invitee_id, $role, $token])) {
            return [
                'success' => true,
                'message' => 'Invitation sent successfully',
                'token' => $token,
                'existing_user' => (bool)$existing_user
            ];
        }

        return ['success' => false, 'message' => 'Failed to send invitation'];
    }

    /**
     * Check for existing invitations or membership
     */
    private function checkExistingInvitation($space_id, $email) {
        // Check if already a member
        $stmt = $this->pdo->prepare("
            SELECT u.email
            FROM space_users su
            JOIN users u ON su.user_id = u.id
            WHERE su.space_id = ? AND u.email = ? AND su.status = 'accepted'
        ");
        $stmt->execute([$space_id, $email]);
        if ($stmt->fetch()) {
            return ['message' => 'User is already a member of this space'];
        }

        // Check for pending invitation
        $stmt = $this->pdo->prepare("
            SELECT status, expires_at
            FROM space_invitations
            WHERE space_id = ? AND invitee_email = ? AND status = 'pending' AND expires_at > NOW()
        ");
        $stmt->execute([$space_id, $email]);
        if ($stmt->fetch()) {
            return ['message' => 'User already has a pending invitation for this space'];
        }

        return null;
    }

    /**
     * Get invitation by token
     */
    public function getInvitationByToken($token) {
        $stmt = $this->pdo->prepare("
            SELECT si.*, s.name as space_name, s.description as space_description,
                   u.username as inviter_username
            FROM space_invitations si
            JOIN spaces s ON si.space_id = s.id
            JOIN users u ON si.inviter_id = u.id
            WHERE si.invitation_token = ? AND si.status = 'pending' AND si.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Accept invitation
     */
    public function acceptInvitation($token, $user_id) {
        $invitation = $this->getInvitationByToken($token);
        if (!$invitation) {
            return ['success' => false, 'message' => 'Invalid or expired invitation'];
        }

        // Verify the user accepting matches the invitation
        if ($invitation['invitee_id'] && $invitation['invitee_id'] != $user_id) {
            return ['success' => false, 'message' => 'This invitation was sent to a different user'];
        }

        // If no invitee_id set, update it with current user
        if (!$invitation['invitee_id']) {
            // Verify email matches
            $stmt = $this->pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (!$user || $user['email'] !== $invitation['invitee_email']) {
                return ['success' => false, 'message' => 'Email address does not match invitation'];
            }

            // Update invitation with user ID
            $stmt = $this->pdo->prepare("
                UPDATE space_invitations SET invitee_id = ? WHERE invitation_token = ?
            ");
            $stmt->execute([$user_id, $token]);
        }

        try {
            $this->pdo->beginTransaction();

            // Add user to space
            $stmt = $this->pdo->prepare("
                INSERT INTO space_users (space_id, user_id, role, invited_by, accepted_at, status)
                VALUES (?, ?, ?, ?, NOW(), 'accepted')
                ON DUPLICATE KEY UPDATE
                    status = 'accepted',
                    accepted_at = NOW(),
                    role = VALUES(role)
            ");
            $stmt->execute([
                $invitation['space_id'],
                $user_id,
                $invitation['role'],
                $invitation['inviter_id']
            ]);

            // Update invitation status
            $stmt = $this->pdo->prepare("
                UPDATE space_invitations
                SET status = 'accepted', responded_at = NOW()
                WHERE invitation_token = ?
            ");
            $stmt->execute([$token]);

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Successfully joined space',
                'space_id' => $invitation['space_id'],
                'space_name' => $invitation['space_name']
            ];

        } catch (Exception $e) {
            $this->pdo->rollback();
            return ['success' => false, 'message' => 'Failed to accept invitation: ' . $e->getMessage()];
        }
    }

    /**
     * Decline invitation
     */
    public function declineInvitation($token, $user_id) {
        $invitation = $this->getInvitationByToken($token);
        if (!$invitation) {
            return ['success' => false, 'message' => 'Invalid or expired invitation'];
        }

        // Update invitation status
        $stmt = $this->pdo->prepare("
            UPDATE space_invitations
            SET status = 'declined', responded_at = NOW()
            WHERE invitation_token = ?
        ");

        if ($stmt->execute([$token])) {
            return ['success' => true, 'message' => 'Invitation declined'];
        }

        return ['success' => false, 'message' => 'Failed to decline invitation'];
    }

    /**
     * Get user's pending invitations
     */
    public function getUserPendingInvitations($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT si.*, s.name as space_name, s.description as space_description,
                   u.username as inviter_username
            FROM space_invitations si
            JOIN spaces s ON si.space_id = s.id
            JOIN users u ON si.inviter_id = u.id
            LEFT JOIN users u2 ON si.invitee_id = u2.id
            WHERE (si.invitee_id = ? OR (si.invitee_id IS NULL AND si.invitee_email = (SELECT email FROM users WHERE id = ?)))
              AND si.status = 'pending' AND si.expires_at > NOW()
            ORDER BY si.invited_at DESC
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get invitations sent by user for a space
     */
    public function getSpaceInvitations($space_id, $inviter_id) {
        $stmt = $this->pdo->prepare("
            SELECT si.*, u.username as invitee_username
            FROM space_invitations si
            LEFT JOIN users u ON si.invitee_id = u.id
            WHERE si.space_id = ? AND si.inviter_id = ?
            ORDER BY si.invited_at DESC
        ");
        $stmt->execute([$space_id, $inviter_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cancel/expire invitation
     */
    public function cancelInvitation($invitation_id, $inviter_id) {
        $stmt = $this->pdo->prepare("
            UPDATE space_invitations
            SET status = 'expired', responded_at = NOW()
            WHERE id = ? AND inviter_id = ? AND status = 'pending'
        ");

        if ($stmt->execute([$invitation_id, $inviter_id])) {
            return ['success' => true, 'message' => 'Invitation cancelled'];
        }

        return ['success' => false, 'message' => 'Failed to cancel invitation'];
    }
}
?>