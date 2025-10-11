<?php
session_start();

// Include required files
require_once '../src/Config/database.php';
require_once '../src/Controllers/InvitationController.php';

// Initialize controller
$invitationController = new InvitationController($pdo);

// Route requests
$action = $_GET['action'] ?? 'dashboard';

switch($action) {
    case 'dashboard':
        $invitationController->dashboard();
        break;

    case 'respond':
        $token = $_GET['token'] ?? null;
        if ($token) {
            $invitationController->handleInvitation($token);
        } else {
            $invitationController->dashboard();
        }
        break;

    case 'process':
        // This is a JSON endpoint - exit immediately after
        $invitationController->processResponse();
        exit;

    case 'send':
        // This is a JSON endpoint - exit immediately after
        $invitationController->sendInvitation();
        exit;

    default:
        $invitationController->dashboard();
        break;
}
?>