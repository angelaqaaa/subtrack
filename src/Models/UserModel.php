<?php

class UserModel {
    private $pdo;

    public function __construct($database_connection) {
        $this->pdo = $database_connection;
    }

    /**
     * Create a new user account
     */
    public function createUser($username, $email, $password) {
        $sql = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";

        if($stmt = $this->pdo->prepare($sql)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt->bindParam(1, $username, PDO::PARAM_STR);
            $stmt->bindParam(2, $email, PDO::PARAM_STR);
            $stmt->bindParam(3, $password_hash, PDO::PARAM_STR);

            if ($stmt->execute()) {
                return $this->pdo->lastInsertId();
            }
        }

        return false;
    }

    /**
     * Authenticate user login
     */
    public function authenticateUser($username, $password) {
        $sql = "SELECT id, username, email, password_hash FROM users WHERE username = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $username, PDO::PARAM_STR);

            if($stmt->execute()) {
                if($stmt->rowCount() == 1) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if(password_verify($password, $row["password_hash"])) {
                        return [
                            'id' => $row['id'],
                            'username' => $row['username'],
                            'email' => $row['email']
                        ];
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if username exists
     */
    public function usernameExists($username) {
        $sql = "SELECT id FROM users WHERE username = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $username, PDO::PARAM_STR);

            if($stmt->execute()) {
                return $stmt->rowCount() > 0;
            }
        }

        return false;
    }

    /**
     * Check if email exists
     */
    public function emailExists($email) {
        $sql = "SELECT id FROM users WHERE email = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $email, PDO::PARAM_STR);

            if($stmt->execute()) {
                return $stmt->rowCount() > 0;
            }
        }

        return false;
    }

    /**
     * Validate registration data
     */
    public function validateRegistrationData($username, $email, $password, $confirm_password) {
        $errors = [];

        // Username validation
        if(empty($username)) {
            $errors['username'] = "Please enter a username.";
        } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors['username'] = "Username can only contain letters, numbers, and underscores.";
        } elseif($this->usernameExists($username)) {
            $errors['username'] = "This username is already taken.";
        }

        // Email validation
        if(empty($email)) {
            $errors['email'] = "Please enter an email.";
        } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Please enter a valid email address.";
        } elseif($this->emailExists($email)) {
            $errors['email'] = "This email is already registered.";
        }

        // Password validation
        if(empty($password)) {
            $errors['password'] = "Please enter a password.";
        } elseif(strlen($password) < 6) {
            $errors['password'] = "Password must have at least 6 characters.";
        }

        // Confirm password validation
        if(empty($confirm_password)) {
            $errors['confirm_password'] = "Please confirm password.";
        } elseif($password !== $confirm_password) {
            $errors['confirm_password'] = "Password did not match.";
        }

        return $errors;
    }

    /**
     * Validate login data
     */
    public function validateLoginData($username, $password) {
        $errors = [];

        if(empty($username)) {
            $errors['username'] = "Please enter username.";
        }

        if(empty($password)) {
            $errors['password'] = "Please enter your password.";
        }

        return $errors;
    }

    /**
     * Update user password
     */
    public function updatePassword($user_id, $current_password, $new_password) {
        // First verify current password
        $sql = "SELECT password_hash FROM users WHERE id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                if($stmt->rowCount() == 1) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Verify current password
                    if(password_verify($current_password, $row['password_hash'])) {
                        // Update to new password
                        $update_sql = "UPDATE users SET password_hash = ? WHERE id = ?";
                        if($update_stmt = $this->pdo->prepare($update_sql)) {
                            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                            $update_stmt->bindParam(1, $new_password_hash, PDO::PARAM_STR);
                            $update_stmt->bindParam(2, $user_id, PDO::PARAM_INT);

                            return $update_stmt->execute();
                        }
                    } else {
                        return false; // Current password is incorrect
                    }
                }
            }
        }

        return false;
    }

    /**
     * Update user email
     */
    public function updateEmail($user_id, $new_email, $password) {
        // First verify password
        $sql = "SELECT password_hash FROM users WHERE id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                if($stmt->rowCount() == 1) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Verify password
                    if(password_verify($password, $row['password_hash'])) {
                        // Check if new email already exists
                        if($this->emailExists($new_email)) {
                            return ['success' => false, 'message' => 'Email address already in use'];
                        }

                        // Update email
                        $update_sql = "UPDATE users SET email = ? WHERE id = ?";
                        if($update_stmt = $this->pdo->prepare($update_sql)) {
                            $update_stmt->bindParam(1, $new_email, PDO::PARAM_STR);
                            $update_stmt->bindParam(2, $user_id, PDO::PARAM_INT);

                            if($update_stmt->execute()) {
                                return ['success' => true, 'message' => 'Email updated successfully'];
                            }
                        }
                    } else {
                        return ['success' => false, 'message' => 'Password is incorrect'];
                    }
                }
            }
        }

        return ['success' => false, 'message' => 'Failed to update email'];
    }

    /**
     * Get user by ID
     */
    public function getUserById($user_id) {
        $sql = "SELECT id, username, email, created_at FROM users WHERE id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }

        return false;
    }

    /**
     * Validate password requirements
     */
    public function validatePassword($password) {
        $errors = [];

        if(strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }

        if(!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }

        if(!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }

        if(!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }

        if(!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }

        return $errors;
    }

    /**
     * Find user by username
     */
    public function findUserByUsername($username) {
        $sql = "SELECT id, username, email FROM users WHERE username = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $username, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Log user login activity
     */
    public function logLoginActivity($user_id, $ip_address, $user_agent, $success = true) {
        $sql = "INSERT INTO login_history (user_id, ip_address, user_agent, success, login_time)
                VALUES (?, ?, ?, ?, NOW())";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $ip_address, PDO::PARAM_STR);
            $stmt->bindParam(3, $user_agent, PDO::PARAM_STR);
            $stmt->bindParam(4, $success, PDO::PARAM_BOOL);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Get user login history
     */
    public function getLoginHistory($user_id, $limit = 20) {
        $sql = "SELECT ip_address, user_agent, success, login_time
                FROM login_history
                WHERE user_id = ?
                ORDER BY login_time DESC
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
     * Create or update user session
     */
    public function createSession($user_id, $session_id, $ip_address, $user_agent) {
        $sql = "INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, created_at, last_activity)
                VALUES (?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                last_activity = NOW()";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $session_id, PDO::PARAM_STR);
            $stmt->bindParam(3, $ip_address, PDO::PARAM_STR);
            $stmt->bindParam(4, $user_agent, PDO::PARAM_STR);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Get user active sessions
     */
    public function getUserSessions($user_id) {
        $sql = "SELECT session_id, ip_address, user_agent, created_at, last_activity
                FROM user_sessions
                WHERE user_id = ?
                ORDER BY last_activity DESC";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    /**
     * Delete all sessions for a user except current
     */
    public function logoutAllSessions($user_id, $current_session_id) {
        $sql = "DELETE FROM user_sessions WHERE user_id = ? AND session_id != ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $current_session_id, PDO::PARAM_STR);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Delete specific session
     */
    public function deleteSession($session_id) {
        $sql = "DELETE FROM user_sessions WHERE session_id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $session_id, PDO::PARAM_STR);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Clean up old sessions (older than 30 days)
     */
    public function cleanupOldSessions() {
        $sql = "DELETE FROM user_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        return $this->pdo->exec($sql);
    }

    /**
     * Generate a new 2FA secret for the user
     */
    public function generate2FASecret() {
        // Generate a random 32-character base32 secret
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }

    /**
     * Enable 2FA for a user
     */
    public function enable2FA($user_id, $secret, $verification_code) {
        // Verify the code first
        if (!$this->verify2FACode($secret, $verification_code)) {
            return ['success' => false, 'message' => 'Invalid verification code'];
        }

        $sql = "UPDATE users SET two_factor_enabled = 1, two_factor_secret = ? WHERE id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $secret, PDO::PARAM_STR);
            $stmt->bindParam(2, $user_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                return ['success' => true, 'message' => 'Two-factor authentication enabled successfully'];
            }
        }

        return ['success' => false, 'message' => 'Failed to enable 2FA'];
    }

    /**
     * Disable 2FA for a user
     */
    public function disable2FA($user_id, $password, $verification_code = null) {
        // First verify password
        $sql = "SELECT password_hash, two_factor_secret FROM users WHERE id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                if($stmt->rowCount() == 1) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Verify password
                    if(!password_verify($password, $row['password_hash'])) {
                        return ['success' => false, 'message' => 'Password is incorrect'];
                    }

                    // If 2FA is enabled, require verification code
                    if($row['two_factor_secret'] && $verification_code) {
                        if(!$this->verify2FACode($row['two_factor_secret'], $verification_code)) {
                            return ['success' => false, 'message' => 'Invalid verification code'];
                        }
                    }

                    // Disable 2FA
                    $update_sql = "UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?";
                    if($update_stmt = $this->pdo->prepare($update_sql)) {
                        $update_stmt->bindParam(1, $user_id, PDO::PARAM_INT);

                        if($update_stmt->execute()) {
                            // Also delete all backup codes
                            $this->deleteBackupCodes($user_id);
                            return ['success' => true, 'message' => 'Two-factor authentication disabled successfully'];
                        }
                    }
                }
            }
        }

        return ['success' => false, 'message' => 'Failed to disable 2FA'];
    }

    /**
     * Verify 2FA code using TOTP algorithm
     */
    public function verify2FACode($secret, $code, $window = 1) {
        $time = floor(time() / 30);

        // Check current time window and adjacent windows for clock drift
        for ($i = -$window; $i <= $window; $i++) {
            if ($this->generateTOTP($secret, $time + $i) === str_pad($code, 6, '0', STR_PAD_LEFT)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate TOTP code
     */
    private function generateTOTP($secret, $time) {
        // Convert base32 secret to binary
        $key = $this->base32Decode($secret);

        // Pack time as 64-bit big-endian
        $time = pack('N*', 0, $time);

        // Generate HMAC-SHA1
        $hash = hash_hmac('sha1', $time, $key, true);

        // Dynamic truncation
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;

        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Base32 decode for TOTP
     */
    private function base32Decode($data) {
        $map = [
            'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7,
            'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15,
            'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23,
            'Y' => 24, 'Z' => 25, '2' => 26, '3' => 27, '4' => 28, '5' => 29, '6' => 30, '7' => 31
        ];

        $data = strtoupper($data);
        $l = strlen($data);
        $n = 0;
        $j = 0;
        $binary = '';

        for ($i = 0; $i < $l; $i++) {
            $n = $n << 5;
            $n = $n + $map[$data[$i]];
            $j = $j + 5;
            if ($j >= 8) {
                $j = $j - 8;
                $binary .= chr(($n & (0xFF << $j)) >> $j);
            }
        }

        return $binary;
    }

    /**
     * Generate backup codes for 2FA recovery
     */
    public function generateBackupCodes($user_id) {
        // Delete existing backup codes
        $this->deleteBackupCodes($user_id);

        $codes = [];
        $code_hashes = [];

        // Generate 10 backup codes
        for ($i = 0; $i < 10; $i++) {
            $code = '';
            for ($j = 0; $j < 8; $j++) {
                $code .= random_int(0, 9);
            }
            $codes[] = $code;
            $code_hashes[] = password_hash($code, PASSWORD_DEFAULT);
        }

        // Save hashed codes to database
        $sql = "INSERT INTO user_backup_codes (user_id, code_hash) VALUES (?, ?)";
        if($stmt = $this->pdo->prepare($sql)) {
            foreach($code_hashes as $hash) {
                $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
                $stmt->bindParam(2, $hash, PDO::PARAM_STR);
                $stmt->execute();
            }
        }

        return $codes;
    }

    /**
     * Verify backup code
     */
    public function verifyBackupCode($user_id, $code) {
        $sql = "SELECT id, code_hash FROM user_backup_codes WHERE user_id = ? AND used_at IS NULL";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                $backup_codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach($backup_codes as $backup_code) {
                    if(password_verify($code, $backup_code['code_hash'])) {
                        // Mark code as used
                        $update_sql = "UPDATE user_backup_codes SET used_at = NOW() WHERE id = ?";
                        if($update_stmt = $this->pdo->prepare($update_sql)) {
                            $update_stmt->bindParam(1, $backup_code['id'], PDO::PARAM_INT);
                            $update_stmt->execute();
                        }
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Delete all backup codes for a user
     */
    public function deleteBackupCodes($user_id) {
        $sql = "DELETE FROM user_backup_codes WHERE user_id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            return $stmt->execute();
        }

        return false;
    }

    /**
     * Get 2FA status for a user
     */
    public function get2FAStatus($user_id) {
        $sql = "SELECT two_factor_enabled,
                       (SELECT COUNT(*) FROM user_backup_codes WHERE user_id = ? AND used_at IS NULL) as unused_backup_codes
                FROM users WHERE id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $user_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }

        return false;
    }

    /**
     * Generate QR code URL for 2FA setup
     */
    public function generate2FAQRCodeURL($username, $secret, $issuer = 'SubTrack') {
        $qr_url = 'otpauth://totp/' . urlencode($issuer) . ':' . urlencode($username)
                . '?secret=' . $secret
                . '&issuer=' . urlencode($issuer)
                . '&algorithm=SHA1'
                . '&digits=6'
                . '&period=30';

        return $qr_url;
    }

    /**
     * Authenticate user with 2FA support
     */
    public function authenticateUserWith2FA($username, $password, $two_factor_code = null) {
        $sql = "SELECT id, username, email, password_hash, two_factor_enabled, two_factor_secret FROM users WHERE username = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $username, PDO::PARAM_STR);

            if($stmt->execute()) {
                if($stmt->rowCount() == 1) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    if(password_verify($password, $row["password_hash"])) {
                        // If 2FA is enabled, verify the code
                        if($row['two_factor_enabled']) {
                            if(!$two_factor_code) {
                                return ['status' => 'requires_2fa', 'user_id' => $row['id']];
                            }

                            // Try TOTP code first
                            if($this->verify2FACode($row['two_factor_secret'], $two_factor_code)) {
                                return [
                                    'status' => 'success',
                                    'user' => [
                                        'id' => $row['id'],
                                        'username' => $row['username'],
                                        'email' => $row['email']
                                    ]
                                ];
                            }

                            // Try backup code
                            if($this->verifyBackupCode($row['id'], $two_factor_code)) {
                                return [
                                    'status' => 'success',
                                    'user' => [
                                        'id' => $row['id'],
                                        'username' => $row['username'],
                                        'email' => $row['email']
                                    ]
                                ];
                            }

                            return ['status' => 'invalid_2fa'];
                        }

                        // No 2FA required
                        return [
                            'status' => 'success',
                            'user' => [
                                'id' => $row['id'],
                                'username' => $row['username'],
                                'email' => $row['email']
                            ]
                        ];
                    }
                }
            }
        }

        return ['status' => 'invalid_credentials'];
    }

    /**
     * Delete user account and all related data
     */
    public function deleteUserAccount($user_id, $password) {
        // First verify password
        $sql = "SELECT password_hash FROM users WHERE id = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                if($stmt->rowCount() == 1) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Verify password
                    if(password_verify($password, $row['password_hash'])) {
                        try {
                            $this->pdo->beginTransaction();

                            // Delete all user data in order (to handle foreign keys)
                            $tables = [
                                'user_backup_codes',
                                'user_sessions',
                                'login_history',
                                'space_users',
                                'space_subscriptions',
                                'subscriptions',
                                'custom_categories',
                                'users'
                            ];

                            foreach($tables as $table) {
                                $delete_sql = "DELETE FROM $table WHERE " .
                                    ($table === 'space_subscriptions' ? 'added_by' : 'user_id') . " = ?";
                                if($table === 'users') {
                                    $delete_sql = "DELETE FROM $table WHERE id = ?";
                                }

                                $delete_stmt = $this->pdo->prepare($delete_sql);
                                $delete_stmt->bindParam(1, $user_id, PDO::PARAM_INT);
                                $delete_stmt->execute();
                            }

                            $this->pdo->commit();
                            return ['success' => true, 'message' => 'Account deleted successfully'];

                        } catch (Exception $e) {
                            $this->pdo->rollback();
                            error_log('Delete account error: ' . $e->getMessage());
                            return ['success' => false, 'message' => 'Failed to delete account'];
                        }
                    } else {
                        return ['success' => false, 'message' => 'Password is incorrect'];
                    }
                }
            }
        }

        return ['success' => false, 'message' => 'Failed to delete account'];
    }
}
?>