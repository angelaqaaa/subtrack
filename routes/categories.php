<?php
session_start();

// Include required files
require_once '../src/Config/database.php';
require_once '../src/Controllers/CategoryController.php';

// Initialize controller
$categoryController = new CategoryController($pdo);

// Route requests
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'index':
        $categoryController->index();
        break;

    case 'create':
        // This is a JSON endpoint - exit immediately after
        $categoryController->createCategory();
        exit;

    case 'update':
        // This is a JSON endpoint - exit immediately after
        $categoryController->updateCategory();
        exit;

    case 'delete':
        // This is a JSON endpoint - exit immediately after
        $categoryController->deleteCategory();
        exit;

    case 'list':
        // This is a JSON endpoint - exit immediately after
        $categoryController->getCategories();
        exit;

    default:
        $categoryController->index();
        break;
}
?>