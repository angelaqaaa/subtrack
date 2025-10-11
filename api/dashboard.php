<?php
/**
 * Dashboard API endpoint for React frontend
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

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'User not authenticated',
        'data' => null
    ]);
    exit;
}

// Include required files
require_once '../src/Config/database.php';
require_once '../src/Models/SubscriptionModel.php';
require_once '../src/Models/SpaceModel.php';
require_once '../src/Models/InsightsModel.php';

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
    // Database connection
    $database = new Database();
    $pdo = $database->getConnection();

    $user_id = $_SESSION['id'];
    $action = $_GET['action'] ?? '';

    $subscriptionModel = new SubscriptionModel($pdo);
    $spaceModel = new SpaceModel($pdo);
    $insightsModel = new InsightsModel($pdo);

    switch ($action) {
        case 'get_subscriptions':
            // Get all subscriptions including those from spaces the user is a member of
            $subscriptions = $subscriptionModel->getAllSubscriptionsWithSpaces($user_id);
            sendResponse('success', 'Subscriptions retrieved successfully', [
                'subscriptions' => $subscriptions
            ]);
            break;

        case 'get_active_subscriptions':
            $subscriptions = $subscriptionModel->getActiveSubscriptionsByUser($user_id);
            sendResponse('success', 'Active subscriptions retrieved successfully', [
                'subscriptions' => $subscriptions
            ]);
            break;

        case 'get_spaces':
            $spaces = $spaceModel->getUserSpaces($user_id);
            sendResponse('success', 'Spaces retrieved successfully', [
                'spaces' => $spaces
            ]);
            break;

        case 'get_insights':
            $insights = $insightsModel->getUserInsights($user_id, 10);
            sendResponse('success', 'Insights retrieved successfully', [
                'insights' => $insights
            ]);
            break;

        case 'get_summary':
            $summary = $subscriptionModel->getSpendingSummary($user_id);
            sendResponse('success', 'Summary retrieved successfully', $summary);
            break;

        case 'get_categories':
            $categories = $subscriptionModel->getSpendingByCategory($user_id);
            sendResponse('success', 'Categories retrieved successfully', [
                'categories' => $categories
            ]);
            break;

        case 'add_subscription':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'POST method required', null, 405);
            }

            // Get form data
            $data = [
                'user_id' => $user_id,
                'service_name' => $_POST['service_name'] ?? '',
                'cost' => $_POST['cost'] ?? '',
                'currency' => $_POST['currency'] ?? 'USD',
                'billing_cycle' => $_POST['billing_cycle'] ?? 'monthly',
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                'category' => $_POST['category'] ?? 'Other'
            ];

            // Validate required fields
            $required = ['service_name', 'cost', 'start_date'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    sendResponse('error', "Field '$field' is required", null, 400);
                }
            }

            // Create subscription
            $subscription_id = $subscriptionModel->createSubscription($data);

            if ($subscription_id) {
                sendResponse('success', 'Subscription added successfully', [
                    'subscription_id' => $subscription_id
                ]);
            } else {
                sendResponse('error', 'Failed to add subscription', null, 500);
            }
            break;

        case 'update_subscription':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'POST method required', null, 405);
            }

            $subscription_id = $_POST['subscription_id'] ?? '';
            if (empty($subscription_id)) {
                sendResponse('error', 'Subscription ID is required', null, 400);
            }

            $data = [
                'service_name' => $_POST['service_name'] ?? '',
                'cost' => $_POST['cost'] ?? '',
                'currency' => $_POST['currency'] ?? 'USD',
                'billing_cycle' => $_POST['billing_cycle'] ?? 'monthly',
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                'category' => $_POST['category'] ?? 'Other'
            ];

            $success = $subscriptionModel->updateSubscription($subscription_id, $user_id, $data);

            if ($success) {
                sendResponse('success', 'Subscription updated successfully');
            } else {
                sendResponse('error', 'Failed to update subscription', null, 500);
            }
            break;

        case 'delete_subscription':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'POST method required', null, 405);
            }

            $subscription_id = $_POST['subscription_id'] ?? '';
            if (empty($subscription_id)) {
                sendResponse('error', 'Subscription ID is required', null, 400);
            }

            $success = $subscriptionModel->deleteSubscription($subscription_id, $user_id);

            if ($success) {
                sendResponse('success', 'Subscription deleted successfully');
            } else {
                sendResponse('error', 'Failed to delete subscription', null, 500);
            }
            break;

        case 'toggle_subscription':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'POST method required', null, 405);
            }

            $subscription_id = $_POST['subscription_id'] ?? '';
            $is_active = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true;

            if (empty($subscription_id)) {
                sendResponse('error', 'Subscription ID is required', null, 400);
            }

            $success = $subscriptionModel->updateSubscriptionStatus($subscription_id, $is_active, $user_id);

            if ($success) {
                $status = $is_active ? 'activated' : 'deactivated';
                sendResponse('success', "Subscription $status successfully");
            } else {
                sendResponse('error', 'Failed to update subscription status', null, 500);
            }
            break;

        case 'update_category':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'POST method required', null, 405);
            }

            $old_category_name = $_POST['old_category_name'] ?? '';
            $new_category_name = $_POST['new_category_name'] ?? '';

            if (empty($old_category_name) || empty($new_category_name)) {
                sendResponse('error', 'Both old and new category names are required', null, 400);
            }

            if ($old_category_name === $new_category_name) {
                sendResponse('error', 'New category name must be different from old name', null, 400);
            }

            try {
                // Update category in regular subscriptions
                $success1 = $subscriptionModel->updateCategoryName($user_id, $old_category_name, $new_category_name);

                // Update category in space subscriptions
                $success2 = $subscriptionModel->updateSpaceCategoryName($user_id, $old_category_name, $new_category_name);

                if ($success1 || $success2) {
                    sendResponse('success', 'Category updated successfully across all subscriptions');
                } else {
                    sendResponse('success', 'No subscriptions found with that category name');
                }
            } catch (Exception $e) {
                error_log('Update category error: ' . $e->getMessage());
                sendResponse('error', 'Failed to update category', null, 500);
            }
            break;

        case 'end_subscription':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'POST method required', null, 405);
            }

            $subscription_id = $_POST['subscription_id'] ?? null;
            if (!$subscription_id) {
                sendResponse('error', 'Subscription ID is required', null, 400);
            }

            $success = $subscriptionModel->endSubscription($subscription_id);
            if ($success) {
                sendResponse('success', 'Subscription ended successfully');
            } else {
                sendResponse('error', 'Failed to end subscription', null, 500);
            }
            break;

        case 'reactivate_subscription':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'POST method required', null, 405);
            }

            $subscription_id = $_POST['subscription_id'] ?? null;
            if (!$subscription_id) {
                sendResponse('error', 'Subscription ID is required', null, 400);
            }

            $success = $subscriptionModel->reactivateSubscription($subscription_id);
            if ($success) {
                sendResponse('success', 'Subscription reactivated successfully');
            } else {
                sendResponse('error', 'Failed to reactivate subscription', null, 500);
            }
            break;

        default:
            sendResponse('error', 'Invalid action', null, 400);
            break;
    }

} catch (Exception $e) {
    error_log('Dashboard API Error: ' . $e->getMessage());
    sendResponse('error', 'Internal server error: ' . $e->getMessage(), null, 500);
}
?>