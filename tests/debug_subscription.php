<?php
session_start();

// Include required files
require_once 'config/database.php';
require_once 'models/SubscriptionModel.php';

echo "<h1>Subscription Creation Debug</h1>";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo "<p>❌ User not logged in. Please <a href='auth.php?action=login'>login first</a>.</p>";
    exit;
}

$user_id = $_SESSION["id"];
echo "<p>✅ User logged in: ID {$user_id}, Username: {$_SESSION['username']}</p>";

// Test data
$test_data = [
    'user_id' => $user_id,
    'service_name' => 'Test Netflix',
    'cost' => '15.99',
    'currency' => 'USD',
    'billing_cycle' => 'monthly',
    'start_date' => date('Y-m-d'),
    'category' => 'Entertainment'
];

echo "<h2>Testing Subscription Creation</h2>";
echo "<p>Test data:</p>";
echo "<pre>" . print_r($test_data, true) . "</pre>";

try {
    $subscriptionModel = new SubscriptionModel($pdo);

    // Test validation
    echo "<h3>1. Testing Validation</h3>";
    $errors = $subscriptionModel->validateSubscriptionData($test_data);

    if(empty($errors)) {
        echo "<p>✅ Validation passed</p>";

        // Test creation
        echo "<h3>2. Testing Creation</h3>";
        $subscription_id = $subscriptionModel->createSubscription($test_data);

        if($subscription_id) {
            echo "<p>✅ Subscription created successfully! ID: {$subscription_id}</p>";

            // Test retrieval
            echo "<h3>3. Testing Retrieval</h3>";
            $subscription = $subscriptionModel->getSubscriptionById($subscription_id, $user_id);
            if($subscription) {
                echo "<p>✅ Subscription retrieved successfully</p>";
                echo "<pre>" . print_r($subscription, true) . "</pre>";
            } else {
                echo "<p>❌ Failed to retrieve created subscription</p>";
            }
        } else {
            echo "<p>❌ Failed to create subscription</p>";
        }
    } else {
        echo "<p>❌ Validation failed:</p>";
        echo "<pre>" . print_r($errors, true) . "</pre>";
    }

} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Database Check</h2>";
try {
    // Check current subscriptions
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $subscriptions = $stmt->fetchAll();

    echo "<p>Current subscriptions for user {$user_id}:</p>";
    if(empty($subscriptions)) {
        echo "<p>No subscriptions found</p>";
    } else {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Service</th><th>Cost</th><th>Status</th><th>Created</th></tr>";
        foreach($subscriptions as $sub) {
            echo "<tr>";
            echo "<td>{$sub['id']}</td>";
            echo "<td>{$sub['service_name']}</td>";
            echo "<td>{$sub['cost']}</td>";
            echo "<td>{$sub['status']}</td>";
            echo "<td>{$sub['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?>