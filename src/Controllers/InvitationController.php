<?php

require_once __DIR__ . '/../Models/InvitationModel.php';
require_once __DIR__ . '/../Models/SpaceModel.php';
require_once __DIR__ . '/../Utils/AuditLogger.php';
require_once __DIR__ . '/../Config/csrf.php';

class InvitationController {
    private $invitationModel;
    private $spaceModel;
    private $auditLogger;
    private $csrfHandler;
    private $pdo;

    public function __construct($database_connection) {
        $this->pdo = $database_connection;
        $this->invitationModel = new InvitationModel($database_connection);
        $this->spaceModel = new SpaceModel($database_connection);
        $this->auditLogger = new AuditLogger($database_connection);
        $this->csrfHandler = new CSRFHandler();
    }

    /**
     * Display user's pending invitations
     */
    public function dashboard() {
        // Check authentication
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            header("location: auth.php?action=login");
            exit;
        }

        $user_id = $_SESSION["id"];

        // Get pending invitations
        $pending_invitations = $this->invitationModel->getUserPendingInvitations($user_id);

        // Generate CSRF token
        $csrf_token = $this->csrfHandler->generateToken();

        // Prepare data for view
        $view_data = [
            'pending_invitations' => $pending_invitations,
            'csrf_token' => $csrf_token,
            'page_title' => 'Space Invitations'
        ];

        // Load view
        $this->loadView('invitations_dashboard', $view_data);
    }

    /**
     * Handle invitation via token (from email link)
     */
    public function handleInvitation($token) {
        // Check authentication
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            // Store token in session and redirect to login
            $_SESSION['pending_invitation_token'] = $token;
            header("location: auth.php?action=login&redirect=invitation");
            exit;
        }

        $user_id = $_SESSION["id"];

        // Get invitation details
        $invitation = $this->invitationModel->getInvitationByToken($token);

        if (!$invitation) {
            $this->showInvitationError('Invalid or expired invitation');
            return;
        }

        // Generate CSRF token
        $csrf_token = $this->csrfHandler->generateToken();

        // Prepare data for view
        $view_data = [
            'invitation' => $invitation,
            'token' => $token,
            'csrf_token' => $csrf_token,
            'page_title' => 'Space Invitation'
        ];

        // Load view
        $this->loadView('invitation_response', $view_data);
    }

    /**
     * Process invitation response (accept/decline)
     */
    public function processResponse() {
        header('Content-Type: application/json');

        // Check authentication
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            echo json_encode(['status' => 'error', 'message' => 'Please login first']);
            exit;
        }

        if($_SERVER["REQUEST_METHOD"] !== "POST") {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            exit;
        }

        // Verify CSRF token
        if(!$this->csrfHandler->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
            exit;
        }

        $user_id = $_SESSION["id"];
        $token = $_POST["token"] ?? null;
        $action = $_POST["action"] ?? null;

        if (!$token || !in_array($action, ['accept', 'decline'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            exit;
        }

        if ($action === 'accept') {
            $result = $this->invitationModel->acceptInvitation($token, $user_id);
        } else {
            $result = $this->invitationModel->declineInvitation($token, $user_id);
        }

        if ($result['success']) {
            // Log the activity
            $this->auditLogger->logActivity(
                $user_id,
                $action === 'accept' ? 'invitation_accepted' : 'invitation_declined',
                'invitation',
                null,
                [
                    'action' => $action,
                    'space_name' => $result['space_name'] ?? 'Unknown'
                ],
                $result['space_id'] ?? null
            );

            if ($action === 'accept') {
                $result['redirect_url'] = "space.php?action=view&space_id=" . $result['space_id'];
            } else {
                $result['redirect_url'] = "invitations.php";
            }
        }

        echo json_encode($result);
    }

    /**
     * Send invitation (called from space management)
     */
    public function sendInvitation() {
        header('Content-Type: application/json');

        // Check authentication
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        if($_SERVER["REQUEST_METHOD"] !== "POST") {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            exit;
        }

        // Verify CSRF token
        if(!$this->csrfHandler->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
            exit;
        }

        $user_id = $_SESSION["id"];
        $space_id = $_POST["space_id"] ?? null;
        $email = trim($_POST["email"] ?? '');
        $role = $_POST["role"] ?? 'viewer';

        // Validate input
        if (!$space_id || !$email) {
            echo json_encode(['status' => 'error', 'message' => 'Space ID and email are required']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
            exit;
        }

        // Check if user has admin permission in this space
        if (!$this->spaceModel->hasPermission($space_id, $user_id, 'admin')) {
            echo json_encode(['status' => 'error', 'message' => 'Insufficient permissions']);
            exit;
        }

        // Send invitation
        $result = $this->invitationModel->createInvitation($space_id, $user_id, $email, $role);

        if ($result['success']) {
            // Log the activity
            $this->auditLogger->logActivity(
                $user_id,
                'invitation_sent',
                'invitation',
                null,
                [
                    'email' => $email,
                    'role' => $role,
                    'existing_user' => $result['existing_user']
                ],
                $space_id
            );

            // Generate invitation URL
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") .
                       "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
            $invitation_url = $base_url . "/invitations.php?action=respond&token=" . $result['token'];

            $result['invitation_url'] = $invitation_url;
            $result['instructions'] = $result['existing_user']
                ? 'User can login and visit the invitation URL to accept'
                : 'User needs to register first, then visit the invitation URL';
        }

        echo json_encode($result);
    }

    /**
     * Show invitation error page
     */
    private function showInvitationError($message) {
        $view_data = [
            'error_message' => $message,
            'page_title' => 'Invitation Error'
        ];
        $this->loadView('invitation_error', $view_data);
    }

    /**
     * Load view file
     */
    private function loadView($view_name, $data = []) {
        // Extract data to variables
        extract($data);
        // Include header
        include __DIR__ . '/../Views/layouts/header.php';
        // Include view (map view names to correct locations)
        switch($view_name) {
            case 'invitations_dashboard':
                include __DIR__ . '/../Views/invitations/dashboard.php';
                break;
            case 'invitation_response':
                include __DIR__ . '/../Views/invitations/response.php';
                break;
            case 'invitation_error':
                include __DIR__ . '/../Views/invitations/error.php';
                break;
            default:
                include __DIR__ . "/../Views/invitations/{$view_name}.php";
                break;
        }
        // Include footer
        include __DIR__ . '/../Views/layouts/footer.php';
    }
}
?>