<?php
/**
 * SubTrack API Endpoint
 * Provides secure access to subscription data for external applications
 */

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include required files
require_once '../src/Config/database.php';
require_once '../src/Models/SubscriptionModel.php';

/**
 * API Response Helper
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

/**
 * Authenticate API request
 */
function authenticateRequest($pdo) {
    // Check for API key in headers or GET parameter
    $api_key = null;

    // Try X-API-Key header first
    if (isset($_SERVER['HTTP_X_API_KEY'])) {
        $api_key = $_SERVER['HTTP_X_API_KEY'];
    }
    // Fall back to GET parameter
    elseif (isset($_GET['api_key'])) {
        $api_key = $_GET['api_key'];
    }
    // Try Authorization header
    elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        if (strpos($auth_header, 'Bearer ') === 0) {
            $api_key = substr($auth_header, 7);
        }
    }

    if (!$api_key) {
        sendResponse('error', 'API key required', null, 401);
    }

    // Validate API key and get user
    $sql = "SELECT id, username FROM users WHERE api_key = ? AND api_key IS NOT NULL";

    if ($stmt = $pdo->prepare($sql)) {
        $stmt->bindParam(1, $api_key, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                return $user;
            }
        }
    }

    sendResponse('error', 'Invalid API key', null, 401);
}

/**
 * Rate limiting (simple implementation)
 */
function checkRateLimit($user_id) {
    $cache_file = sys_get_temp_dir() . '/subtrack_api_' . $user_id . '.cache';
    $current_time = time();
    $rate_limit = 100; // requests per hour
    $time_window = 3600; // 1 hour

    if (file_exists($cache_file)) {
        $cache_data = json_decode(file_get_contents($cache_file), true);

        // Clean old requests
        $cache_data = array_filter($cache_data, function($timestamp) use ($current_time, $time_window) {
            return ($current_time - $timestamp) < $time_window;
        });

        if (count($cache_data) >= $rate_limit) {
            sendResponse('error', 'Rate limit exceeded. Maximum ' . $rate_limit . ' requests per hour.', null, 429);
        }
    } else {
        $cache_data = [];
    }

    // Add current request
    $cache_data[] = $current_time;
    file_put_contents($cache_file, json_encode($cache_data));
}

// Main API logic
try {
    error_log("API: Starting request for endpoint: " . ($_GET['endpoint'] ?? 'summary'));

    // Authenticate request
    $user = authenticateRequest($pdo);
    $user_id = $user['id'];
    error_log("API: Authenticated user ID: $user_id");

    // Check rate limiting
    checkRateLimit($user_id);

    // Initialize subscription model
    $subscriptionModel = new SubscriptionModel($pdo);

    // Get endpoint from URL path
    $endpoint = $_GET['endpoint'] ?? 'summary';

    switch ($endpoint) {
        case 'summary':
            // Get spending summary
            error_log("API: Getting summary for user $user_id");
            $summary = $subscriptionModel->getSpendingSummary($user_id);
            error_log("API: Summary retrieved: " . json_encode($summary));
            $summary['username'] = $user['username'];

            sendResponse('success', 'Summary data retrieved successfully', $summary);
            break;

        case 'subscriptions':
            // Get all subscriptions
            $subscriptions = $subscriptionModel->getSubscriptionsByUser($user_id);

            // Filter sensitive data
            $filtered_subscriptions = array_map(function($sub) {
                return [
                    'id' => $sub['id'],
                    'service_name' => $sub['service_name'],
                    'cost' => $sub['cost'],
                    'currency' => $sub['currency'],
                    'billing_cycle' => $sub['billing_cycle'],
                    'category' => $sub['category'],
                    'start_date' => $sub['start_date']
                ];
            }, $subscriptions);

            sendResponse('success', 'Subscriptions retrieved successfully', [
                'subscriptions' => $filtered_subscriptions,
                'count' => count($filtered_subscriptions)
            ]);
            break;

        case 'categories':
            // Get spending by category
            $categories = $subscriptionModel->getSpendingByCategory($user_id);

            sendResponse('success', 'Category data retrieved successfully', [
                'categories' => $categories,
                'total_categories' => count($categories)
            ]);
            break;

        case 'historical':
            // Get historical spending data
            $historical = $subscriptionModel->getHistoricalSpending($user_id);

            sendResponse('success', 'Historical data retrieved successfully', [
                'monthly_spending' => $historical,
                'period_months' => count($historical)
            ]);
            break;

        case 'insights':
            // Get detailed insights
            $subscriptions = $subscriptionModel->getSubscriptionsByUser($user_id);
            $summary = $subscriptionModel->getSpendingSummary($user_id);

            if (!empty($subscriptions)) {
                $most_expensive = array_reduce($subscriptions, function($carry, $item) {
                    return ($carry === null || $item['cost'] > $carry['cost']) ? $item : $carry;
                }, null);

                $avg_cost = $summary['subscription_count'] > 0 ? $summary['monthly_cost'] / $summary['subscription_count'] : 0;
                $total_saved_annually = $summary['annual_cost'] - ($summary['monthly_cost'] * 12);

                $insights = [
                    'most_expensive_service' => $most_expensive['service_name'],
                    'most_expensive_cost' => $most_expensive['cost'],
                    'most_expensive_cycle' => $most_expensive['billing_cycle'],
                    'average_monthly_cost' => round($avg_cost, 2),
                    'potential_annual_savings' => round($total_saved_annually, 2),
                    'total_services' => $summary['subscription_count']
                ];
            } else {
                $insights = [
                    'message' => 'No subscriptions found for analysis'
                ];
            }

            sendResponse('success', 'Insights generated successfully', $insights);
            break;

        case 'health':
            // API health check
            sendResponse('success', 'API is healthy', [
                'version' => '1.0.0',
                'timestamp' => date('c'),
                'user' => $user['username']
            ]);
            break;

        default:
            sendResponse('error', 'Invalid endpoint. Available endpoints: summary, subscriptions, categories, historical, insights, health', null, 400);
            break;
    }

} catch (Exception $e) {
    error_log('SubTrack API Error: ' . $e->getMessage());
    sendResponse('error', 'Internal server error', null, 500);
}
?>