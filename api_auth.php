<?php
/**
 * Authentication API endpoint for React frontend
 */

// Set JSON response headers and enable CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3003');
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
require_once 'src/Config/database.php';
require_once 'src/Models/UserModel.php';

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

            // Authenticate user
            $user = $userModel->authenticateUser($username, $password);

            if ($user) {
                // Set session variables
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];

                // Generate session ID for frontend
                $session_id = session_id();

                sendResponse('success', 'Login successful', [
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email']
                    ],
                    'session_id' => $session_id
                ]);
            } else {
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

        default:
            sendResponse('error', 'Invalid action', null, 400);
            break;
    }

} catch (Exception $e) {
    error_log('Auth API Error: ' . $e->getMessage());
    sendResponse('error', 'Internal server error', null, 500);
}
?>