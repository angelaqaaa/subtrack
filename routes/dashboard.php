<?php
session_start();

// Include required files
require_once '../src/Config/database.php';
require_once '../src/Controllers/DashboardController.php';

// Initialize controller
$dashboardController = new DashboardController($pdo);

// Route requests
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'index':
        $dashboardController->index();
        break;

    case 'add':
        // This is a JSON endpoint - exit immediately after
        $dashboardController->addSubscription();
        exit;

    case 'delete':
        // This is a JSON endpoint - exit immediately after
        $dashboardController->deleteSubscription();
        exit;

    case 'create_space':
        // This is a JSON endpoint - exit immediately after
        $dashboardController->createSpace();
        exit;

    case 'end_subscription':
        // This is a JSON endpoint - exit immediately after
        $dashboardController->endSubscription();
        exit;

    case 'reactivate_subscription':
        // This is a JSON endpoint - exit immediately after
        $dashboardController->reactivateSubscription();
        exit;

    default:
        $dashboardController->index();
        break;
}
?>