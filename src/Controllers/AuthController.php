<?php

require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Utils/AuditLogger.php';
require_once __DIR__ . '/../Config/csrf.php';

class AuthController {
    private $userModel;
    private $auditLogger;
    private $csrfHandler;

    public function __construct($database_connection) {
        $this->userModel = new UserModel($database_connection);
        $this->auditLogger = new AuditLogger($database_connection);
        $this->csrfHandler = new CSRFHandler();
    }

    /**
     * Display registration form
     */
    public function showRegister() {
        $csrf_token = $this->csrfHandler->generateToken();
        $this->loadView('register', ['csrf_token' => $csrf_token, 'page_title' => 'Register']);
    }

    /**
     * Handle registration form submission
     */
    public function register() {
        if($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->showRegister();
            return;
        }

        // Verify CSRF token
        if(!$this->csrfHandler->validateToken($_POST['csrf_token'] ?? '')) {
            $this->loadView('register', [
                'error' => 'Invalid CSRF token. Please try again.',
                'csrf_token' => $this->csrfHandler->generateToken(),
                'page_title' => 'Register'
            ]);
            return;
        }

        $username = trim($_POST["username"]);
        $email = trim($_POST["email"]);
        $password = trim($_POST["password"]);
        $confirm_password = trim($_POST["confirm_password"]);

        // Validate input
        $errors = $this->userModel->validateRegistrationData($username, $email, $password, $confirm_password);

        if(empty($errors)) {
            // Create user
            $user_id = $this->userModel->createUser($username, $email, $password);
            if($user_id) {
                // Log registration
                $this->auditLogger->logActivity(
                    $user_id,
                    'user_registered',
                    'user',
                    $user_id,
                    [
                        'username' => $username,
                        'email' => $email
                    ]
                );

                header("location: public/auth/login.php?success=registered");
                exit;
            } else {
                $errors['general'] = "Registration failed. Please try again.";
            }
        }

        // Show form with errors
        $this->loadView('register', [
            'errors' => $errors,
            'username' => $username,
            'email' => $email,
            'csrf_token' => $this->csrfHandler->generateToken(),
            'page_title' => 'Register'
        ]);
    }

    /**
     * Display login form
     */
    public function showLogin() {
        // Redirect if already logged in
        if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
            header("location: routes/dashboard.php");
            exit;
        }

        $csrf_token = $this->csrfHandler->generateToken();
        $this->loadView('login', ['csrf_token' => $csrf_token, 'page_title' => 'Login']);
    }

    /**
     * Handle login form submission
     */
    public function login() {
        // Redirect if already logged in
        if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
            header("location: routes/dashboard.php");
            exit;
        }

        if($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->showLogin();
            return;
        }

        // Verify CSRF token
        if(!$this->csrfHandler->validateToken($_POST['csrf_token'] ?? '')) {
            $this->loadView('login', [
                'error' => 'Invalid CSRF token. Please try again.',
                'csrf_token' => $this->csrfHandler->generateToken(),
                'page_title' => 'Login'
            ]);
            return;
        }

        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);

        // Validate input
        $errors = $this->userModel->validateLoginData($username, $password);

        if(empty($errors)) {
            // Authenticate user
            $user = $this->userModel->authenticateUser($username, $password);

            if($user) {
                // Log successful login
                $this->auditLogger->logActivity(
                    $user['id'],
                    'user_login',
                    'user',
                    $user['id'],
                    [
                        'username' => $user['username'],
                        'login_method' => 'password'
                    ]
                );

                // Start session
                $_SESSION["loggedin"] = true;
                $_SESSION["id"] = $user['id'];
                $_SESSION["username"] = $user['username'];

                header("location: routes/dashboard.php");
                exit;
            } else {
                $errors['general'] = "Invalid username or password.";
            }
        }

        // Show form with errors
        $this->loadView('login', [
            'errors' => $errors,
            'username' => $username,
            'csrf_token' => $this->csrfHandler->generateToken(),
            'page_title' => 'Login'
        ]);
    }

    /**
     * Handle logout
     */
    public function logout() {
        // Log logout activity if user is logged in
        if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
            $this->auditLogger->logActivity(
                $_SESSION["id"],
                'user_logout',
                'user',
                $_SESSION["id"],
                [
                    'username' => $_SESSION["username"]
                ]
            );
        }

        $_SESSION = array();
        session_destroy();
        header("location: auth.php?action=login");
        exit;
    }

    /**
     * Load a view template
     */
    private function loadView($view_name, $data = []) {
        // Extract data to variables
        extract($data);

        // Include header
        include __DIR__ . '/../Views/layouts/header.php';

        // Include the view file (map view names to correct locations)
        switch($view_name) {
            case 'login':
                include __DIR__ . '/../Views/auth/login.php';
                break;
            case 'register':
                include __DIR__ . '/../Views/auth/register.php';
                break;
            default:
                include __DIR__ . "/../Views/auth/{$view_name}.php";
                break;
        }

        // Include footer
        include __DIR__ . '/../Views/layouts/footer.php';
    }
}
?>