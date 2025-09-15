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
            $stmt->bindParam(1, $username, PDO::PARAM_STR);
            $stmt->bindParam(2, $email, PDO::PARAM_STR);
            $stmt->bindParam(3, $password_hash, PDO::PARAM_STR);

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            return $stmt->execute();
        }

        return false;
    }

    /**
     * Authenticate user login
     */
    public function authenticateUser($username, $password) {
        $sql = "SELECT id, username, password_hash FROM users WHERE username = ?";

        if($stmt = $this->pdo->prepare($sql)) {
            $stmt->bindParam(1, $username, PDO::PARAM_STR);

            if($stmt->execute()) {
                if($stmt->rowCount() == 1) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if(password_verify($password, $row["password_hash"])) {
                        return [
                            'id' => $row['id'],
                            'username' => $row['username']
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
}
?>