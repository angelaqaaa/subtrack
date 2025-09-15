<?php
session_start();

// Include required files
require_once 'src/Config/database.php';
require_once 'src/Controllers/InsightsController.php';

// Initialize controller
$insightsController = new InsightsController($pdo);

// Route requests
$action = $_GET['action'] ?? 'dashboard';

switch($action) {
    case 'dashboard':
        $insightsController->dashboard();
        break;

    case 'education':
        $insightsController->educationLibrary();
        break;

    case 'content':
        $slug = $_GET['slug'] ?? '';
        if ($slug) {
            $insightsController->viewEducationalContent($slug);
        } else {
            $insightsController->educationLibrary();
        }
        break;

    case 'insight_action':
        $insightsController->handleInsightAction();
        break;

    case 'create_goal':
        $insightsController->createSpendingGoal();
        break;

    case 'mark_completed':
        $insightsController->markContentCompleted();
        break;

    default:
        $insightsController->dashboard();
        break;
}
?>