<?php
session_start();

// Include required files
require_once 'src/Config/database.php';
require_once 'src/Controllers/DashboardController.php';

// Initialize controller
$dashboardController = new DashboardController($pdo);

// Route requests
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'index':
        $dashboardController->index();
        break;

    case 'add':
        $dashboardController->addSubscription();
        break;

    case 'delete':
        $dashboardController->deleteSubscription();
        break;

    case 'create_space':
        $dashboardController->createSpace();
        break;

    case 'end_subscription':
        $dashboardController->endSubscription();
        break;

    case 'reactivate_subscription':
        $dashboardController->reactivateSubscription();
        break;

    default:
        $dashboardController->index();
        break;
}
?>