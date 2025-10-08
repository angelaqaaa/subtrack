<?php

require_once __DIR__ . '/../Models/SubscriptionModel.php';
require_once __DIR__ . '/../Models/SpaceModel.php';
require_once __DIR__ . '/../Models/CategoryModel.php';
require_once __DIR__ . '/../Utils/AuditLogger.php';
require_once __DIR__ . '/../Config/csrf.php';

class DashboardController {
    private $subscriptionModel;
    private $spaceModel;
    private $categoryModel;
    private $auditLogger;
    private $csrfHandler;

    public function __construct($database_connection) {
        $this->subscriptionModel = new SubscriptionModel($database_connection);
        $this->spaceModel = new SpaceModel($database_connection);
        $this->categoryModel = new CategoryModel($database_connection);
        $this->auditLogger = new AuditLogger($database_connection);
        $this->csrfHandler = new CSRFHandler();
    }

    /**
     * Display the main dashboard
     */
    public function index() {
        // Check authentication
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            header("location: /public/auth/login.php");
            exit;
        }

        $user_id = $_SESSION["id"];

        // Get dashboard data
        $subscriptions = $this->subscriptionModel->getSubscriptionsByUser($user_id); // All subscriptions for display
        $active_subscriptions = $this->subscriptionModel->getActiveSubscriptionsByUser($user_id); // Only active for insights
        $ended_subscriptions = $this->subscriptionModel->getEndedSubscriptionsByUser($user_id); // Ended for separate insights
        $summary = $this->subscriptionModel->getSpendingSummary($user_id);
        $category_totals = $this->subscriptionModel->getSpendingByCategory($user_id);
        $spaces = $this->spaceModel->getUserSpaces($user_id);
        $custom_categories = $this->categoryModel->getUserCategories($user_id);

        // Calculate insights based on active subscriptions only
        $insights = $this->calculateInsights($active_subscriptions, $summary);

        // Calculate insights for ended subscriptions
        $ended_insights = $this->calculateEndedSubscriptionInsights($ended_subscriptions);

        // Generate CSRF token for forms
        $csrf_token = $this->csrfHandler->generateToken();

        // Prepare data for view
        $view_data = [
            'subscriptions' => $subscriptions,
            'active_subscriptions' => $active_subscriptions,
            'ended_subscriptions' => $ended_subscriptions,
            'summary' => $summary,
            'category_totals' => $category_totals,
            'spaces' => $spaces,
            'custom_categories' => $custom_categories,
            'insights' => $insights,
            'ended_insights' => $ended_insights,
            'csrf_token' => $csrf_token,
            'page_title' => 'Personal Dashboard'
        ];

        // Load view
        $this->loadView('dashboard', $view_data);
    }

    /**
     * Handle AJAX subscription creation
     */
    public function addSubscription() {
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

        // Prepare data
        $data = [
            'user_id' => $user_id,
            'service_name' => trim($_POST["service_name"]),
            'cost' => trim($_POST["cost"]),
            'currency' => trim($_POST["currency"]),
            'billing_cycle' => trim($_POST["billing_cycle"]),
            'start_date' => trim($_POST["start_date"]),
            'end_date' => !empty(trim($_POST["end_date"])) ? trim($_POST["end_date"]) : null,
            'category' => trim($_POST["category"])
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
        $subscription_id = $this->subscriptionModel->createSubscription($data);

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
                ]
            );

            // Get the created subscription
            $subscription = $this->subscriptionModel->getSubscriptionById($subscription_id, $user_id);
            $subscription_html = $this->subscriptionModel->generateSubscriptionRowHTML($subscription);

            // Get updated summary and category data
            $summary = $this->subscriptionModel->getSpendingSummary($user_id);
            $category_totals = $this->subscriptionModel->getSpendingByCategory($user_id);

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
     * Handle AJAX subscription deletion
     */
    public function deleteSubscription() {
        header('Content-Type: application/json');

        // Check authentication
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $subscription_id = $_GET["id"] ?? null;
        $user_id = $_SESSION["id"];

        if(!$subscription_id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid subscription ID']);
            exit;
        }

        // Verify subscription exists and belongs to user
        $subscription = $this->subscriptionModel->getSubscriptionById($subscription_id, $user_id);
        if(!$subscription) {
            echo json_encode(['status' => 'error', 'message' => 'Subscription not found or access denied']);
            exit;
        }

        // Delete subscription
        if($this->subscriptionModel->deleteSubscription($subscription_id, $user_id)) {
            // Log the activity
            $this->auditLogger->logActivity(
                $user_id,
                'subscription_deleted',
                'subscription',
                $subscription_id,
                [
                    'service_name' => $subscription['service_name'],
                    'cost' => $subscription['cost'],
                    'currency' => $subscription['currency'],
                    'billing_cycle' => $subscription['billing_cycle']
                ]
            );
            // Get updated summary and category data
            $summary = $this->subscriptionModel->getSpendingSummary($user_id);
            $category_totals = $this->subscriptionModel->getSpendingByCategory($user_id);

            echo json_encode([
                'status' => 'success',
                'message' => 'Subscription deleted successfully',
                'summary' => $summary,
                'category_totals' => $category_totals
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete subscription']);
        }
    }

    /**
     * Handle space creation from main dashboard
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
            // Log the activity
            $this->auditLogger->logActivity(
                $user_id,
                'space_created',
                'space',
                $space_id,
                [
                    'space_name' => $name,
                    'description' => $description
                ]
            );

            echo json_encode([
                'status' => 'success',
                'message' => 'Space created successfully',
                'space_id' => $space_id,
                'redirect_url' => "space.php?action=view&space_id={$space_id}"
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create space']);
        }
    }

    /**
     * Calculate dashboard insights
     */
    private function calculateInsights($subscriptions, $summary) {
        if(empty($subscriptions)) {
            return null;
        }

        // Find most expensive subscription
        $most_expensive = array_reduce($subscriptions, function($carry, $item) {
            return ($carry === null || $item['cost'] > $carry['cost']) ? $item : $carry;
        }, null);

        // Calculate average cost per service
        $avg_cost = $summary['subscription_count'] > 0 ? $summary['monthly_cost'] / $summary['subscription_count'] : 0;

        // Calculate potential annual savings from yearly plans
        $total_saved_annually = $summary['annual_cost'] - ($summary['monthly_cost'] * 12);

        return [
            'most_expensive' => $most_expensive,
            'avg_cost' => $avg_cost,
            'total_saved_annually' => $total_saved_annually
        ];
    }

    /**
     * Calculate insights for ended subscriptions
     */
    private function calculateEndedSubscriptionInsights($ended_subscriptions) {
        if(empty($ended_subscriptions)) {
            return null;
        }

        $total_ended = count($ended_subscriptions);
        $monthly_savings = 0;
        $most_recent_cancellation = null;
        $most_expensive_cancelled = null;

        foreach($ended_subscriptions as $subscription) {
            $monthly_cost = $subscription['billing_cycle'] == 'monthly' ? $subscription['cost'] : $subscription['cost'] / 12;
            $monthly_savings += $monthly_cost;

            if($most_expensive_cancelled === null || $subscription['cost'] > $most_expensive_cancelled['cost']) {
                $most_expensive_cancelled = $subscription;
            }

            if($most_recent_cancellation === null ||
               ($subscription['end_date'] && $subscription['end_date'] > ($most_recent_cancellation['end_date'] ?? '1970-01-01'))) {
                $most_recent_cancellation = $subscription;
            }
        }

        return [
            'total_ended' => $total_ended,
            'monthly_savings' => $monthly_savings,
            'annual_savings' => $monthly_savings * 12,
            'most_expensive_cancelled' => $most_expensive_cancelled,
            'most_recent_cancellation' => $most_recent_cancellation
        ];
    }

    /**
     * Load a view template
     */
    private function loadView($view_name, $data = []) {
        // Extract data to variables
        extract($data);

        // Include header
        include __DIR__ . '/../Views/layouts/header.php';

        // Include view (map view names to correct locations)
        switch($view_name) {
            case 'dashboard':
                include __DIR__ . '/../Views/dashboard/index.php';
                break;
            case 'subscription_history':
                include __DIR__ . '/../Views/dashboard/subscription-history.php';
                break;
            default:
                include __DIR__ . "/../Views/dashboard/{$view_name}.php";
                break;
        }

        // Include footer
        include __DIR__ . '/../Views/layouts/footer.php';
    }

    /**
     * End/Cancel a subscription
     */
    public function endSubscription() {
        header('Content-Type: application/json');

        // Enable error reporting for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

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
        $subscription_id = $_POST["subscription_id"] ?? null;
        $end_date = $_POST["end_date"] ?? date('Y-m-d');
        $reason = $_POST["reason"] ?? null;

        if (!$subscription_id) {
            echo json_encode(['status' => 'error', 'message' => 'Subscription ID is required']);
            exit;
        }

        // Verify subscription exists and belongs to user
        $subscription = $this->subscriptionModel->getSubscriptionById($subscription_id, $user_id);
        if (!$subscription) {
            echo json_encode(['status' => 'error', 'message' => 'Subscription not found or access denied']);
            exit;
        }

        // End the subscription
        $success = $this->subscriptionModel->endSubscription($subscription_id, $user_id, $end_date, $reason);

        if ($success) {
            // Log the activity
            $this->auditLogger->logActivity(
                $user_id,
                'subscription_ended',
                'subscription',
                $subscription_id,
                [
                    'service_name' => $subscription['service_name'],
                    'end_date' => $end_date,
                    'reason' => $reason
                ]
            );

            // Get updated summary and category data
            $summary = $this->subscriptionModel->getSpendingSummary($user_id);
            $category_totals = $this->subscriptionModel->getSpendingByCategory($user_id);

            echo json_encode([
                'status' => 'success',
                'message' => 'Subscription ended successfully',
                'summary' => $summary,
                'category_totals' => $category_totals
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to end subscription']);
        }
    }

    /**
     * Reactivate a subscription
     */
    public function reactivateSubscription() {
        header('Content-Type: application/json');

        // Enable error reporting for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

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
        $subscription_id = $_POST["subscription_id"] ?? null;
        $new_start_date = $_POST["start_date"] ?? date('Y-m-d');

        if (!$subscription_id) {
            echo json_encode(['status' => 'error', 'message' => 'Subscription ID is required']);
            exit;
        }

        // Verify subscription exists and belongs to user
        $subscription = $this->subscriptionModel->getSubscriptionById($subscription_id, $user_id);
        if (!$subscription) {
            echo json_encode(['status' => 'error', 'message' => 'Subscription not found or access denied']);
            exit;
        }

        // Reactivate the subscription
        $success = $this->subscriptionModel->reactivateSubscription($subscription_id, $user_id, $new_start_date);

        if ($success) {
            // Log the activity
            $this->auditLogger->logActivity(
                $user_id,
                'subscription_reactivated',
                'subscription',
                $subscription_id,
                [
                    'service_name' => $subscription['service_name'],
                    'new_start_date' => $new_start_date
                ]
            );

            // Get updated summary and category data
            $summary = $this->subscriptionModel->getSpendingSummary($user_id);
            $category_totals = $this->subscriptionModel->getSpendingByCategory($user_id);

            echo json_encode([
                'status' => 'success',
                'message' => 'Subscription reactivated successfully',
                'summary' => $summary,
                'category_totals' => $category_totals
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to reactivate subscription']);
        }
    }
}
?>