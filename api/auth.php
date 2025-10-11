<?php
/**
 * Authentication API endpoint for React frontend
 */

// Set JSON response headers and enable CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configure session for cross-origin requests
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', '0'); // Set to '1' in production with HTTPS
ini_set('session.cookie_httponly', '1');

// Start session
session_start();

// Include required files
require_once '../src/Config/database.php';
require_once '../src/Models/UserModel.php';
require_once '../src/Config/csrf.php';

/**
 * Send JSON response
 */
function sendResponse($status, $message, $data = null, $http_code = 200) {
    http_response_code($http_code);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
    exit;
}

try {
    $userModel = new UserModel($pdo);

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Determine action from JSON input or query parameters
    $action = ($input && isset($input['action'])) ? $input['action'] : ($_POST['action'] ?? $_GET['action'] ?? '');

    switch ($action) {
        case 'login':
            if ($input) {
                $username = $input['username'] ?? '';
                $password = $input['password'] ?? '';
            } else {
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
            }

            if (empty($username) || empty($password)) {
                sendResponse('error', 'Username and password are required', null, 400);
            }

            // First check if user requires 2FA
            $auth_result = $userModel->authenticateUserWith2FA($username, $password);

            if ($auth_result['status'] === 'requires_2fa') {
                sendResponse('requires_2fa', 'Two-factor authentication required', [
                    'user_id' => $auth_result['user_id']
                ], 200);
            } elseif ($auth_result['status'] === 'success') {
                $user = $auth_result['user'];
                // Set session variables
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];

                // Generate session ID for frontend
                $session_id = session_id();

                // Get client information
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

                // Log successful login
                $userModel->logLoginActivity($user['id'], $ip_address, $user_agent, true);

                // Create/update session record
                $userModel->createSession($user['id'], $session_id, $ip_address, $user_agent);

                sendResponse('success', 'Login successful', [
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email']
                    ],
                    'session_id' => $session_id
                ]);
            } else {
                // Log failed login attempt (if we can identify the user)
                $user_id = null;
                $check_user = $userModel->findUserByUsername($username);
                if ($check_user) {
                    $user_id = $check_user['id'];
                    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                    $userModel->logLoginActivity($user_id, $ip_address, $user_agent, false);
                }

                sendResponse('error', 'Invalid username or password', null, 401);
            }
            break;

        case 'register':
            if ($input) {
                $userData = $input;
            } else {
                $userData = $_POST;
            }

            $required_fields = ['username', 'email', 'password', 'confirm_password'];
            foreach ($required_fields as $field) {
                if (empty($userData[$field])) {
                    sendResponse('error', "Field '$field' is required", null, 400);
                }
            }

            // Username validation
            if (strlen($userData['username']) < 3) {
                sendResponse('error', 'Username must be at least 3 characters long', null, 400);
            }

            if (strlen($userData['username']) > 50) {
                sendResponse('error', 'Username cannot exceed 50 characters', null, 400);
            }

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $userData['username'])) {
                sendResponse('error', 'Username can only contain letters, numbers, and underscores', null, 400);
            }

            // Password validation
            if ($userData['password'] !== $userData['confirm_password']) {
                sendResponse('error', 'Passwords do not match', null, 400);
            }

            if (strlen($userData['password']) < 8) {
                sendResponse('error', 'Password must be at least 8 characters long', null, 400);
            }

            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $userData['password'])) {
                sendResponse('error', 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character', null, 400);
            }

            // Check if user already exists
            if ($userModel->usernameExists($userData['username'])) {
                sendResponse('error', 'Username already exists', null, 409);
            }

            if ($userModel->emailExists($userData['email'])) {
                sendResponse('error', 'Email already exists', null, 409);
            }

            // Create user
            $user_id = $userModel->createUser($userData['username'], $userData['email'], $userData['password']);

            if ($user_id) {
                sendResponse('success', 'User registered successfully', [
                    'user_id' => $user_id
                ]);
            } else {
                sendResponse('error', 'Failed to create user', null, 500);
            }
            break;

        case 'logout':
            session_destroy();
            sendResponse('success', 'Logged out successfully');
            break;

        case 'current_user':
            if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                sendResponse('success', 'User authenticated', [
                    'user' => [
                        'id' => $_SESSION['id'],
                        'username' => $_SESSION['username'],
                        'email' => $_SESSION['email']
                    ]
                ]);
            } else {
                sendResponse('error', 'User not authenticated', null, 401);
            }
            break;

        case 'change_password':
            if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
                sendResponse('error', 'User not authenticated', null, 401);
                break;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'POST method required', null, 405);
            }

            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                sendResponse('error', 'All password fields are required', null, 400);
            }

            if ($new_password !== $confirm_password) {
                sendResponse('error', 'New passwords do not match', null, 400);
            }

            // Validate new password strength
            $password_errors = $userModel->validatePassword($new_password);
            if (!empty($password_errors)) {
                sendResponse('error', implode('. ', $password_errors), null, 400);
            }

            $user_id = $_SESSION['id'];
            $success = $userModel->updatePassword($user_id, $current_password, $new_password);

            if ($success) {
                sendResponse('success', 'Password updated successfully');
            } else {
                sendResponse('error', 'Current password is incorrect or update failed', null, 400);
            }
            break;

        case 'change_email':
            if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
                sendResponse('error', 'User not authenticated', null, 401);
                break;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'POST method required', null, 405);
            }

            $new_email = $_POST['new_email'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($new_email) || empty($password)) {
                sendResponse('error', 'Email and password are required', null, 400);
            }

            if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                sendResponse('error', 'Invalid email format', null, 400);
            }

            $user_id = $_SESSION['id'];
            $result = $userModel->updateEmail($user_id, $new_email, $password);

            if ($result['success']) {
                // Update session email
                $_SESSION['email'] = $new_email;
                sendResponse('success', $result['message']);
            } else {
                sendResponse('error', $result['message'], null, 400);
            }
            break;

        case 'get_login_history':
            if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
                sendResponse('error', 'User not authenticated', null, 401);
                break;
            }

            $user_id = $_SESSION['id'];
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

            $history = $userModel->getLoginHistory($user_id, $limit);
            sendResponse('success', 'Login history retrieved successfully', ['history' => $history]);
            break;

        case 'get_sessions':
            if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
                sendResponse('error', 'User not authenticated', null, 401);
                break;
            }

            $user_id = $_SESSION['id'];
            $sessions = $userModel->getUserSessions($user_id);

            // Mark current session
            $current_session_id = session_id();
            foreach($sessions as &$session) {
                $session['is_current'] = $session['session_id'] === $current_session_id;
            }

            sendResponse('success', 'Sessions retrieved successfully', ['sessions' => $sessions]);
            break;

        case 'logout_all_sessions':
            if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
                sendResponse('error', 'User not authenticated', null, 401);
                break;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'POST method required', null, 405);
            }

            $user_id = $_SESSION['id'];
            $current_session_id = session_id();

            $success = $userModel->logoutAllSessions($user_id, $current_session_id);

            if ($success) {
                sendResponse('success', 'All other sessions logged out successfully');
            } else {
                sendResponse('error', 'Failed to logout sessions', null, 500);
            }
            break;

        case 'delete_account':
            if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
                sendResponse('error', 'User not authenticated', null, 401);
                break;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'POST method required', null, 405);
            }

            $password = $_POST['password'] ?? '';
            $confirmation = $_POST['confirmation'] ?? '';

            if (empty($password)) {
                sendResponse('error', 'Password is required for account deletion', null, 400);
            }

            if ($confirmation !== 'DELETE') {
                sendResponse('error', 'Please type "DELETE" to confirm account deletion', null, 400);
            }

            $user_id = $_SESSION['id'];
            $result = $userModel->deleteUserAccount($user_id, $password);

            if ($result['success']) {
                // Destroy session after successful deletion
                session_destroy();
                sendResponse('success', $result['message']);
            } else {
                sendResponse('error', $result['message'], null, 400);
            }
            break;

        case 'setup_2fa':
            if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
                sendResponse('error', 'User not authenticated', null, 401);
                break;
            }

            $user_id = $_SESSION['id'];
            $username = $_SESSION['username'];

            // Generate new secret
            $secret = $userModel->generate2FASecret();

            // Generate QR code URL
            $qr_url = $userModel->generate2FAQRCodeURL($username, $secret);

            sendResponse('success', '2FA setup initiated', [
                'secret' => $secret,
                'qr_url' => $qr_url,
                'manual_entry_key' => $secret
            ]);
            break;

        case 'enable_2fa':
            if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
                sendResponse('error', 'User not authenticated', null, 401);
                break;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'POST method required', null, 405);
            }

            $user_id = $_SESSION['id'];
            $secret = $_POST['secret'] ?? '';
            $verification_code = $_POST['verification_code'] ?? '';

            if (empty($secret) || empty($verification_code)) {
                sendResponse('error', 'Secret and verification code are required', null, 400);
            }

            $result = $userModel->enable2FA($user_id, $secret, $verification_code);

            if ($result['success']) {
                // Generate backup codes
                $backup_codes = $userModel->generateBackupCodes($user_id);
                sendResponse('success', $result['message'], [
                    'backup_codes' => $backup_codes
                ]);
            } else {
                sendResponse('error', $result['message'], null, 400);
            }
            break;

        case 'disable_2fa':
            if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
                sendResponse('error', 'User not authenticated', null, 401);
                break;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'POST method required', null, 405);
            }

            $user_id = $_SESSION['id'];
            $password = $_POST['password'] ?? '';
            $verification_code = $_POST['verification_code'] ?? '';

            if (empty($password)) {
                sendResponse('error', 'Password is required', null, 400);
            }

            $result = $userModel->disable2FA($user_id, $password, $verification_code);

            if ($result['success']) {
                sendResponse('success', $result['message']);
            } else {
                sendResponse('error', $result['message'], null, 400);
            }
            break;

        case 'get_2fa_status':
            if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
                sendResponse('error', 'User not authenticated', null, 401);
                break;
            }

            $user_id = $_SESSION['id'];
            $status = $userModel->get2FAStatus($user_id);

            if ($status !== false) {
                sendResponse('success', '2FA status retrieved', [
                    'enabled' => (bool)$status['two_factor_enabled'],
                    'backup_codes_remaining' => (int)$status['unused_backup_codes']
                ]);
            } else {
                sendResponse('error', 'Failed to get 2FA status', null, 500);
            }
            break;

        case 'generate_backup_codes':
            if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
                sendResponse('error', 'User not authenticated', null, 401);
                break;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'POST method required', null, 405);
            }

            $user_id = $_SESSION['id'];
            $password = $_POST['password'] ?? '';

            if (empty($password)) {
                sendResponse('error', 'Password is required', null, 400);
            }

            // Verify password first
            $sql = "SELECT password_hash FROM users WHERE id = ?";
            if($stmt = $pdo->prepare($sql)) {
                $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
                if($stmt->execute() && $stmt->rowCount() == 1) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!password_verify($password, $row['password_hash'])) {
                        sendResponse('error', 'Password is incorrect', null, 400);
                    }
                } else {
                    sendResponse('error', 'User not found', null, 400);
                }
            } else {
                sendResponse('error', 'Database error', null, 500);
            }

            $backup_codes = $userModel->generateBackupCodes($user_id);
            sendResponse('success', 'New backup codes generated', [
                'backup_codes' => $backup_codes
            ]);
            break;

        case 'verify_2fa_login':
            if ($input) {
                $username = $input['username'] ?? '';
                $password = $input['password'] ?? '';
                $two_factor_code = $input['two_factor_code'] ?? '';
            } else {
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                $two_factor_code = $_POST['two_factor_code'] ?? '';
            }

            if (empty($username) || empty($password) || empty($two_factor_code)) {
                sendResponse('error', 'Username, password, and 2FA code are required', null, 400);
            }

            $auth_result = $userModel->authenticateUserWith2FA($username, $password, $two_factor_code);

            if ($auth_result['status'] === 'success') {
                $user = $auth_result['user'];

                // Set session variables
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];

                // Generate session ID for frontend
                $session_id = session_id();

                // Get client information
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

                // Log successful login
                $userModel->logLoginActivity($user['id'], $ip_address, $user_agent, true);

                // Create/update session record
                $userModel->createSession($user['id'], $session_id, $ip_address, $user_agent);

                sendResponse('success', 'Login successful', [
                    'user' => $user,
                    'session_id' => $session_id
                ]);
            } elseif ($auth_result['status'] === 'invalid_2fa') {
                sendResponse('error', 'Invalid 2FA code', null, 401);
            } else {
                sendResponse('error', 'Invalid credentials', null, 401);
            }
            break;

        case 'get_csrf_token':
            // This endpoint provides CSRF token for React to use when calling PHP routes
            if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
                sendResponse('error', 'User not authenticated', null, 401);
                break;
            }

            $csrfHandler = new CSRFHandler();
            $token = $csrfHandler->getToken();

            sendResponse('success', 'CSRF token retrieved', ['csrf_token' => $token]);
            break;

        default:
            sendResponse('error', 'Invalid action', null, 400);
            break;
    }

} catch (Exception $e) {
    error_log('Auth API Error: ' . $e->getMessage());
    sendResponse('error', 'Internal server error', null, 500);
}
?>