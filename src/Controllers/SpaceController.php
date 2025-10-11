<?php

require_once __DIR__ . '/../Models/SpaceModel.php';
require_once __DIR__ . '/../Models/SubscriptionModel.php';
require_once __DIR__ . '/../Models/CategoryModel.php';
require_once __DIR__ . '/../Utils/AuditLogger.php';
require_once __DIR__ . '/../Config/csrf.php';

class SpaceController {
    private $spaceModel;
    private $subscriptionModel;
    private $categoryModel;
    private $auditLogger;
    private $csrfHandler;
    private $pdo;

    public function __construct($database_connection) {
        $this->pdo = $database_connection;
        $this->spaceModel = new SpaceModel($database_connection);
        $this->subscriptionModel = new SubscriptionModel($database_connection);
        $this->categoryModel = new CategoryModel($database_connection);
        $this->auditLogger = new AuditLogger($database_connection);
        $this->csrfHandler = new CSRFHandler();
    }

    /**
     * Display space dashboard
     */
    public function viewSpace($space_id) {
        // Check authentication
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            header("location: /public/auth/login.php");
            exit;
        }

        $user_id = $_SESSION["id"];

        // Get space details and verify access
        $space = $this->spaceModel->getSpaceWithUserRole($space_id, $user_id);
        if(!$space) {
            header("location: /routes/dashboard.php?error=space_access_denied");
            exit;
        }

        // Get space data
        $subscriptions = $this->subscriptionModel->getSubscriptionsBySpace($space_id);
        $summary = $this->subscriptionModel->getSpaceSpendingSummary($space_id);
        $category_totals = $this->subscriptionModel->getSpaceSpendingByCategory($space_id);
        $members = $this->spaceModel->getSpaceMembers($space_id);
        $activities = $this->auditLogger->getSpaceActivities($space_id, 20);
        $custom_categories = $this->categoryModel->getUserCategories($user_id);

        // Generate CSRF token
        $csrf_token = $this->csrfHandler->getToken();

        // Format activities for display
        $formatted_activities = [];
        foreach($activities as $activity) {
            $formatted_activities[] = $this->auditLogger->formatActivity($activity);
        }

        // Prepare data for view
        $view_data = [
            'space' => $space,
            'subscriptions' => $subscriptions,
            'summary' => $summary,
            'category_totals' => $category_totals,
            'members' => $members,
            'activities' => $activities,
            'formatted_activities' => $formatted_activities,
            'custom_categories' => $custom_categories,
            'csrf_token' => $csrf_token,
            'page_title' => $space['name'] . ' - Shared Space'
        ];

        // Load view
        $this->loadView('space_dashboard', $view_data);
    }

    /**
     * Handle space creation
     */
    public function createSpace() {
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

        // Validate input
        $name = trim($_POST["space_name"] ?? '');
        $description = trim($_POST["space_description"] ?? '');

        if(empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'Space name is required']);
            exit;
        }

        // Create space
        $space_id = $this->spaceModel->createSpace($name, $description, $user_id);

        if($space_id) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Space created successfully',
                'space_id' => $space_id
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create space']);
        }
    }

    /**
     * Handle user invitation to space (now uses proper invitation system)
     */
    public function inviteUser() {
        // Redirect to the invitation controller
        require_once __DIR__ . '/InvitationController.php';
        $invitationController = new InvitationController($this->pdo);
        $invitationController->sendInvitation();
    }

    /**
     * Add subscription to space
     */
    public function addSpaceSubscription() {
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

        // Check if user has admin permission in this space
        if(!$this->spaceModel->hasPermission($space_id, $user_id, 'admin')) {
            echo json_encode(['status' => 'error', 'message' => 'Insufficient permissions']);
            exit;
        }

        // Prepare data
        $data = [
            'user_id' => $user_id,
            'service_name' => trim($_POST["service_name"]),
            'cost' => trim($_POST["cost"]),
            'currency' => trim($_POST["currency"]),
            'billing_cycle' => trim($_POST["billing_cycle"]),
            'start_date' => trim($_POST["start_date"]),
            'end_date' => isset($_POST["end_date"]) && !empty(trim($_POST["end_date"])) ? trim($_POST["end_date"]) : null,
            'category' => trim($_POST["category"]),
            'space_id' => $space_id
        ];

        // Validate data
        $errors = $this->subscriptionModel->validateSubscriptionData($data);

        if(!empty($errors)) {
            echo json_encode([
                'status' => 'validation_error',
                'errors' => $errors
            ]);
            exit;
        }

        // Create subscription
        $subscription_id = $this->subscriptionModel->createSubscriptionWithSpace($data);

        if($subscription_id) {
            // Log the activity
            $this->auditLogger->logActivity(
                $user_id,
                'subscription_created',
                'subscription',
                $subscription_id,
                [
                    'service_name' => $data['service_name'],
                    'cost' => $data['cost'],
                    'currency' => $data['currency'],
                    'billing_cycle' => $data['billing_cycle'],
                    'category' => $data['category']
                ],
                $space_id
            );

            // Get the created subscription
            $subscription = $this->subscriptionModel->getSubscriptionById($subscription_id, $user_id);
            $subscription_html = $this->subscriptionModel->generateSubscriptionRowHTML($subscription);

            // Get updated summary
            $summary = $this->subscriptionModel->getSpaceSpendingSummary($space_id);
            $category_totals = $this->subscriptionModel->getSpaceSpendingByCategory($space_id);

            echo json_encode([
                'status' => 'success',
                'message' => 'Subscription added successfully',
                'subscription_html' => $subscription_html,
                'summary' => $summary,
                'category_totals' => $category_totals
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add subscription']);
        }
    }

    /**
     * Handle user quitting a space
     */
    public function quitSpace() {
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

        if(!$space_id) {
            echo json_encode(['status' => 'error', 'message' => 'Space ID is required']);
            exit;
        }

        // Get space details and verify user is a member
        $space = $this->spaceModel->getSpaceWithUserRole($space_id, $user_id);
        if(!$space) {
            echo json_encode(['status' => 'error', 'message' => 'Space not found or access denied']);
            exit;
        }

        // Don't allow owner to quit their own space
        if($space['owner_id'] == $user_id) {
            echo json_encode(['status' => 'error', 'message' => 'Space owners cannot quit their own space. Transfer ownership first.']);
            exit;
        }

        // Remove user from space
        if($this->spaceModel->removeUserFromSpace($space_id, $user_id)) {
            // Log the activity
            $this->auditLogger->logActivity(
                $user_id,
                'space_left',
                'space',
                $space_id,
                [
                    'space_name' => $space['name'],
                    'user_role' => $space['user_role']
                ],
                $space_id
            );

            echo json_encode([
                'status' => 'success',
                'message' => 'You have successfully left the space.'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to leave space. Please try again.']);
        }
    }

    /**
     * Handle removing a member from space (admin only)
     */
    public function removeMember() {
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

        $current_user_id = $_SESSION["id"];
        $space_id = $_POST["space_id"] ?? null;
        $user_to_remove = $_POST["user_id"] ?? null;

        if(!$space_id || !$user_to_remove) {
            echo json_encode(['status' => 'error', 'message' => 'Space ID and User ID are required']);
            exit;
        }

        // Check if current user has admin permission in this space
        if(!$this->spaceModel->hasPermission($space_id, $current_user_id, 'admin')) {
            echo json_encode(['status' => 'error', 'message' => 'Insufficient permissions']);
            exit;
        }

        // Get space details
        $space = $this->spaceModel->getSpaceWithUserRole($space_id, $current_user_id);
        if(!$space) {
            echo json_encode(['status' => 'error', 'message' => 'Space not found or access denied']);
            exit;
        }

        // Prevent removing the space owner
        if($space['owner_id'] == $user_to_remove) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot remove the space owner']);
            exit;
        }

        // Prevent removing yourself
        if($current_user_id == $user_to_remove) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot remove yourself. Use "Quit Space" instead.']);
            exit;
        }

        // Get user info for logging
        $user_to_remove_info = $this->spaceModel->getSpaceWithUserRole($space_id, $user_to_remove);

        // Remove user from space
        if($this->spaceModel->removeUserFromSpace($space_id, $user_to_remove)) {
            // Log the activity
            $this->auditLogger->logActivity(
                $current_user_id,
                'member_removed',
                'space',
                $space_id,
                [
                    'removed_user_id' => $user_to_remove,
                    'removed_user_role' => $user_to_remove_info['user_role'] ?? 'unknown',
                    'space_name' => $space['name']
                ],
                $space_id
            );

            echo json_encode([
                'status' => 'success',
                'message' => 'Member removed successfully'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to remove member. Please try again.']);
        }
    }

    /**
     * Load view file
     */
    /**
     * Edit space subscription
     */
    public function editSpaceSubscription() {
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            header("location: /routes/auth.php?action=login");
            exit;
        }

        if($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("location: /routes/dashboard.php");
            exit;
        }

        // Verify CSRF token
        if(!$this->csrfHandler->validateToken($_POST['csrf_token'] ?? '')) {
            header("location: /routes/dashboard.php?error=invalid_token");
            exit;
        }

        $user_id = $_SESSION["id"];
        $space_id = $_POST["space_id"] ?? null;
        $subscription_id = $_POST["subscription_id"] ?? null;

        if(!$space_id || !$subscription_id) {
            header("location: /routes/dashboard.php?error=missing_data");
            exit;
        }

        // Verify user is admin of this space
        $space = $this->spaceModel->getSpaceWithUserRole($space_id, $user_id);
        if(!$space || $space['user_role'] !== 'admin') {
            header("location: /routes/dashboard.php?error=unauthorized");
            exit;
        }

        // Update subscription
        $success = $this->subscriptionModel->updateSubscription($subscription_id, $user_id, [
            'service_name' => $_POST["service_name"],
            'cost' => $_POST["cost"],
            'currency' => $_POST["currency"] ?? 'USD',
            'billing_cycle' => $_POST["billing_cycle"],
            'start_date' => $_POST["start_date"],
            'end_date' => isset($_POST["end_date"]) && !empty(trim($_POST["end_date"])) ? trim($_POST["end_date"]) : null,
            'category' => $_POST["category"] ?? null
        ]);

        if($success) {
            header("location: /routes/space.php?action=view&space_id={$space_id}&success=subscription_updated");
        } else {
            header("location: /routes/space.php?action=view&space_id={$space_id}&error=update_failed");
        }
    }

    /**
     * End space subscription (mark as inactive)
     */
    public function endSpaceSubscription() {
        if (ob_get_level()) {
            ob_clean();
        }
        header('Content-Type: application/json');

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
        $subscription_id = $_POST["subscription_id"] ?? null;

        if(!$space_id || !$subscription_id) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
            exit;
        }

        // Verify user is admin of this space
        $space = $this->spaceModel->getSpaceWithUserRole($space_id, $user_id);
        if(!$space || $space['user_role'] !== 'admin') {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        // End subscription
        $success = $this->subscriptionModel->endSubscription($subscription_id);

        if($success) {
            echo json_encode(['status' => 'success', 'message' => 'Subscription ended successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to end subscription']);
        }
    }

    /**
     * Reactivate space subscription
     */
    public function reactivateSpaceSubscription() {
        if (ob_get_level()) {
            ob_clean();
        }
        header('Content-Type: application/json');

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
        $subscription_id = $_POST["subscription_id"] ?? null;

        if(!$space_id || !$subscription_id) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
            exit;
        }

        // Verify user is admin of this space
        $space = $this->spaceModel->getSpaceWithUserRole($space_id, $user_id);
        if(!$space || $space['user_role'] !== 'admin') {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        // Reactivate subscription
        $success = $this->subscriptionModel->reactivateSubscription($subscription_id);

        if($success) {
            echo json_encode(['status' => 'success', 'message' => 'Subscription reactivated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to reactivate subscription']);
        }
    }

    private function loadView($view_name, $data = []) {
        // Extract data to variables
        extract($data);
        // Include header
        include __DIR__ . '/../Views/layouts/header.php';
        // Include view (map view names to correct locations)
        switch($view_name) {
            case 'space_dashboard':
                include __DIR__ . '/../Views/spaces/dashboard.php';
                break;
            default:
                include __DIR__ . "/../Views/spaces/{$view_name}.php";
                break;
        }
        // Include footer
        include __DIR__ . '/../Views/layouts/footer.php';
    }
}
?>