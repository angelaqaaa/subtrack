<?php

class CSRFHandler {

    /**
     * Generate a CSRF token and store it in session
     */
    public function generateToken() {
        if(!isset($_SESSION)) {
            session_start();
        }

        // Generate a random token
        $token = bin2hex(random_bytes(32));

        // Store in session
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();

        return $token;
    }

    /**
     * Validate a CSRF token
     */
    public function validateToken($submitted_token) {
        if(!isset($_SESSION)) {
            session_start();
        }

        // Check if token exists in session
        if(!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }

        // Check token age (expire after 1 hour)
        if((time() - $_SESSION['csrf_token_time']) > 3600) {
            unset($_SESSION['csrf_token']);
            unset($_SESSION['csrf_token_time']);
            return false;
        }

        // Validate token using hash_equals to prevent timing attacks
        $is_valid = hash_equals($_SESSION['csrf_token'], $submitted_token);

        // For production: optionally regenerate token after use (one-time use)
        // For development: keep token valid for multiple requests during debugging
        // Uncomment the lines below for one-time token behavior:
        /*
        if($is_valid) {
            unset($_SESSION['csrf_token']);
            unset($_SESSION['csrf_token_time']);
        }
        */

        return $is_valid;
    }

    /**
     * Get the current CSRF token from session, or generate one if it doesn't exist
     */
    public function getToken() {
        if(!isset($_SESSION)) {
            session_start();
        }

        // If no token exists or it's expired, generate a new one
        if(!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return $this->generateToken();
        }

        // Check if token is expired (after 1 hour)
        if((time() - $_SESSION['csrf_token_time']) > 3600) {
            return $this->generateToken();
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Generate HTML input field for CSRF token
     */
    public function getTokenField() {
        $token = $this->generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}
?>