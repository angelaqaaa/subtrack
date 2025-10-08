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
        $categoryController->createCategory();
        break;

    case 'update':
        $categoryController->updateCategory();
        break;

    case 'delete':
        $categoryController->deleteCategory();
        break;

    case 'list':
        $categoryController->getCategories();
        break;

    default:
        $categoryController->index();
        break;
}
?>