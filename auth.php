<?php
session_start();

// Include required files
require_once 'src/Config/database.php';
require_once 'src/Controllers/AuthController.php';

// Initialize controller
$authController = new AuthController($pdo);

// Route requests
$action = $_GET['action'] ?? 'login';

switch($action) {
    case 'register':
        if($_SERVER["REQUEST_METHOD"] == "POST") {
            $authController->register();
        } else {
            $authController->showRegister();
        }
        break;

    case 'login':
        if($_SERVER["REQUEST_METHOD"] == "POST") {
            $authController->login();
        } else {
            $authController->showLogin();
        }
        break;

    case 'logout':
        $authController->logout();
        break;

    default:
        $authController->showLogin();
        break;
}
?>