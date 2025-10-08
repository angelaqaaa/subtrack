<?php
session_start();

// Include required files
require_once '../src/Config/database.php';
require_once '../src/Controllers/SpaceController.php';

// Initialize controller
$spaceController = new SpaceController($pdo);

// Get action and space ID
$action = $_GET['action'] ?? 'view';
$space_id = $_GET['space_id'] ?? null;

// Route requests
switch($action) {
    case 'view':
        if($space_id) {
            $spaceController->viewSpace($space_id);
        } else {
            header("location: dashboard_mvc.php?error=invalid_space");
        }
        break;

    case 'create':
        $spaceController->createSpace();
        break;

    case 'invite':
        $spaceController->inviteUser();
        break;

    case 'add_subscription':
        $spaceController->addSpaceSubscription();
        break;

    case 'quit':
        $spaceController->quitSpace();
        break;

    case 'remove_member':
        $spaceController->removeMember();
        break;

    default:
        header("location: dashboard_mvc.php");
        break;
}
?>