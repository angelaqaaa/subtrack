<?php
session_start();
ob_start(); // Start output buffering to catch any warnings

// CORS headers for React frontend
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include required files
require_once '../src/Config/database.php';
require_once '../src/Controllers/InsightsController.php';

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
        // This is a JSON endpoint - exit immediately after
        $insightsController->handleInsightAction();
        exit;

    case 'create_goal':
        // This is a JSON endpoint - exit immediately after
        $insightsController->createSpendingGoal();
        exit;

    case 'mark_completed':
        // This is a JSON endpoint - clean output buffer and exit immediately
        ob_clean();
        $insightsController->markContentCompleted();
        exit;

    case 'toggle_bookmark':
        // This is a JSON endpoint - clean output buffer and exit immediately
        ob_clean();
        $insightsController->toggleBookmark();
        exit;

    default:
        $insightsController->dashboard();
        break;
}
?>