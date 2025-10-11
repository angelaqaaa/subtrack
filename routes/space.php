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
            header("location: /routes/dashboard.php?error=invalid_space");
        }
        break;

    case 'create':
        $spaceController->createSpace();
        break;

    case 'invite':
        // This is a JSON endpoint - exit immediately after
        $spaceController->inviteUser();
        exit;

    case 'add_subscription':
        // This is a JSON endpoint - exit immediately after
        $spaceController->addSpaceSubscription();
        exit;

    case 'edit_subscription':
        // This is a form endpoint
        $spaceController->editSpaceSubscription();
        break;

    case 'end_subscription':
        // This is a JSON endpoint - exit immediately after
        $spaceController->endSpaceSubscription();
        exit;

    case 'reactivate_subscription':
        // This is a JSON endpoint - exit immediately after
        $spaceController->reactivateSpaceSubscription();
        exit;

    case 'quit':
        // This is a JSON endpoint - exit immediately after
        $spaceController->quitSpace();
        exit;

    case 'remove_member':
        // This is a JSON endpoint - exit immediately after
        $spaceController->removeMember();
        exit;

    default:
        header("location: /routes/dashboard.php");
        break;
}
?>