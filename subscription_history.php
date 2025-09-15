<?php
session_start();

// Check authentication
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include required files
require_once 'src/Config/database.php';
require_once 'src/Models/SubscriptionModel.php';
require_once 'src/Config/csrf.php';

// Initialize models
$subscriptionModel = new SubscriptionModel($pdo);
$csrfHandler = new CSRFHandler();

$user_id = $_SESSION["id"];

// Get subscription history data
$active_subscriptions = $subscriptionModel->getActiveSubscriptionsByUser($user_id);
$ended_subscriptions = $subscriptionModel->getEndedSubscriptionsByUser($user_id);
$lifetime_spending = $subscriptionModel->getLifetimeSpending($user_id);

// Get current spending summary
$current_summary = $subscriptionModel->getSpendingSummary($user_id);

// Calculate insights
$total_subscriptions = count($active_subscriptions) + count($ended_subscriptions);
$reactivation_rate = $total_subscriptions > 0 ? (count($active_subscriptions) / $total_subscriptions) * 100 : 0;

// Generate CSRF token
$csrf_token = $csrfHandler->generateToken();

// Prepare data for view
$view_data = [
    'active_subscriptions' => $active_subscriptions,
    'ended_subscriptions' => $ended_subscriptions,
    'lifetime_spending' => $lifetime_spending,
    'current_summary' => $current_summary,
    'total_subscriptions' => $total_subscriptions,
    'reactivation_rate' => $reactivation_rate,
    'csrf_token' => $csrf_token,
    'page_title' => 'Subscription History'
];

// Load view
include 'src/Views/layouts/header.php';
include 'src/Views/dashboard/subscription-history.php';
include 'src/Views/layouts/footer.php';
?>