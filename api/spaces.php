<?php
/**
 * Spaces API endpoint for React frontend
 */

// Set JSON response headers and enable CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configure session for cross-origin requests
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', '0'); // Set to '1' in production with HTTPS
ini_set('session.cookie_httponly', '1');

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'User not authenticated',
        'data' => null
    ]);
    exit;
}

// Include required files
require_once '../src/Config/database.php';
require_once '../src/Models/SpaceModel.php';

/**
 * Send JSON response
 */
function sendResponse($status, $message, $data = null, $http_code = 200) {
    http_response_code($http_code);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

try {
    // Database connection
    $database = new Database();
    $pdo = $database->getConnection();

    // Initialize models
    $spaceModel = new SpaceModel($pdo);

    // Get current user
    $user_id = $_SESSION['id'];

    // Handle different actions
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_all':
            $spaces = $spaceModel->getUserSpaces($user_id);
            sendResponse('success', 'Spaces retrieved successfully', ['spaces' => $spaces]);
            break;

        case 'get_members':
            $space_id = $_GET['space_id'] ?? null;
            if (!$space_id) {
                sendResponse('error', 'Space ID is required', null, 400);
            }

            // Check if user has permission to view members
            if (!$spaceModel->hasPermission($space_id, $user_id, 'viewer')) {
                sendResponse('error', 'Access denied', null, 403);
            }

            $members = $spaceModel->getSpaceMembers($space_id);
            sendResponse('success', 'Members retrieved successfully', ['members' => $members]);
            break;

        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'Method not allowed', null, 405);
            }

            $input = json_decode(file_get_contents('php://input'), true);

            $name = trim($input['name'] ?? '');
            $description = trim($input['description'] ?? '');

            if (empty($name)) {
                sendResponse('error', 'Space name is required', null, 400);
            }

            $space_id = $spaceModel->createSpace($name, $description, $user_id);

            if ($space_id) {
                sendResponse('success', 'Space created successfully', ['space_id' => $space_id]);
            } else {
                sendResponse('error', 'Failed to create space', null, 500);
            }
            break;

        case 'invite':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'Method not allowed', null, 405);
            }

            $input = json_decode(file_get_contents('php://input'), true);

            $space_id = $input['space_id'] ?? null;
            $email = trim($input['email'] ?? '');
            $role = trim($input['role'] ?? 'viewer');
            $allowed_roles = ['admin', 'editor', 'viewer'];

            if (!$space_id || empty($email)) {
                sendResponse('error', 'Space ID and email are required', null, 400);
            }

            if (!in_array($role, $allowed_roles, true)) {
                sendResponse('error', 'Invalid role specified for invitation', null, 400);
            }

            if ($role === 'editor' && !$spaceModel->ensureEditorRoleSupport()) {
                sendResponse(
                    'error',
                    'Editor role is not available. Please ensure the latest database migrations have been applied.',
                    null,
                    500
                );
            }

            // Check if user has permission to invite (admin only)
            if (!$spaceModel->hasPermission($space_id, $user_id, 'admin')) {
                sendResponse('error', 'Only admins can invite users', null, 403);
            }

            // Find user by email
            $invitee = $spaceModel->findUserByEmail($email);
            if (!$invitee) {
                sendResponse('error', 'User not found with this email', null, 404);
            }

            // Check if user already has any relationship with this space (pending or accepted)
            $existing = $spaceModel->hasAnyRelationshipWithSpace($space_id, $invitee['id']);
            if ($existing) {
                $status = $existing['status'];
                if ($status === 'accepted') {
                    sendResponse('error', 'User is already a member of this space', null, 400);
                } else if ($status === 'pending') {
                    sendResponse('error', 'User already has a pending invitation to this space', null, 400);
                } else if ($status === 'declined') {
                    $resent = $spaceModel->reinviteUser($space_id, $invitee['id'], $role, $user_id);

                    if ($resent) {
                        sendResponse('success', 'Invitation resent successfully');
                    } else {
                        sendResponse('error', 'Failed to resend invitation for this user', null, 500);
                    }
                }
            }

            $success = $spaceModel->addUserToSpace($space_id, $invitee['id'], $role, $user_id);

            if ($success) {
                sendResponse('success', 'User invited successfully');
            } else {
                $errorMessage = 'Failed to invite user';
                if ($role === 'editor' && !$spaceModel->supportsEditorRole()) {
                    $errorMessage = 'Failed to invite user because the editor role is not supported by the database schema.';
                }
                sendResponse('error', $errorMessage, null, 500);
            }
            break;

        case 'remove_member':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                sendResponse('error', 'Method not allowed', null, 405);
            }

            $space_id = $_GET['space_id'] ?? null;
            $member_id = $_GET['member_id'] ?? null;

            if (!$space_id || !$member_id) {
                sendResponse('error', 'Space ID and member ID are required', null, 400);
            }

            // Check if user has permission to remove members (admin only)
            if (!$spaceModel->hasPermission($space_id, $user_id, 'admin')) {
                sendResponse('error', 'Only admins can remove members', null, 403);
            }

            $success = $spaceModel->removeUserFromSpace($space_id, $member_id);

            if ($success) {
                sendResponse('success', 'Member removed successfully');
            } else {
                sendResponse('error', 'Failed to remove member', null, 500);
            }
            break;

        case 'quit':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                sendResponse('error', 'Method not allowed', null, 405);
            }

            $space_id = $_GET['space_id'] ?? null;

            if (!$space_id) {
                sendResponse('error', 'Space ID is required', null, 400);
            }

            // Check if user is a member of the space
            $space = $spaceModel->getSpaceWithUserRole($space_id, $user_id);
            if (!$space) {
                sendResponse('error', 'You are not a member of this space', null, 403);
            }

            // Prevent owner from quitting their own space
            if ($space['owner_id'] == $user_id) {
                sendResponse('error', 'Space owners cannot quit their own space', null, 400);
            }

            $success = $spaceModel->removeUserFromSpace($space_id, $user_id);

            if ($success) {
                sendResponse('success', 'Successfully left the space');
            } else {
                sendResponse('error', 'Failed to leave space', null, 500);
            }
            break;

        case 'get_pending_invitations':
            $invitations = $spaceModel->getPendingInvitations($user_id);
            sendResponse('success', 'Pending invitations retrieved successfully', ['invitations' => $invitations]);
            break;

        case 'accept_invitation':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'Method not allowed', null, 405);
            }

            $space_id = $_GET['space_id'] ?? null;

            if (!$space_id) {
                sendResponse('error', 'Space ID is required', null, 400);
            }

            $success = $spaceModel->acceptInvitation($space_id, $user_id);

            if ($success) {
                sendResponse('success', 'Invitation accepted successfully');
            } else {
                sendResponse('error', 'Failed to accept invitation', null, 500);
            }
            break;

        case 'reject_invitation':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                sendResponse('error', 'Method not allowed', null, 405);
            }

            $space_id = $_GET['space_id'] ?? null;

            if (!$space_id) {
                sendResponse('error', 'Space ID is required', null, 400);
            }

            $success = $spaceModel->rejectInvitation($space_id, $user_id);

            if ($success) {
                sendResponse('success', 'Invitation rejected successfully');
            } else {
                sendResponse('error', 'Failed to reject invitation', null, 500);
            }
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                sendResponse('error', 'Method not allowed', null, 405);
            }

            $space_id = $_GET['space_id'] ?? null;

            if (!$space_id) {
                sendResponse('error', 'Space ID is required', null, 400);
            }

            // Check if user is the owner of the space
            $space = $spaceModel->getSpaceWithUserRole($space_id, $user_id);
            if (!$space) {
                sendResponse('error', 'Space not found or access denied', null, 404);
            }

            if ($space['owner_id'] != $user_id) {
                sendResponse('error', 'Only space owners can delete spaces', null, 403);
            }

            $success = $spaceModel->deleteSpace($space_id, $user_id);

            if ($success) {
                sendResponse('success', 'Space deleted successfully');
            } else {
                sendResponse('error', 'Failed to delete space', null, 500);
            }
            break;

        case 'add_subscription':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'Method not allowed', null, 405);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $space_id = $input['space_id'] ?? null;
            if (!$space_id) {
                sendResponse('error', 'Space ID is required', null, 400);
            }

            // Check if user has permission to add subscriptions (admin or editor)
            if (!$spaceModel->hasPermission($space_id, $user_id, 'editor')) {
                sendResponse('error', 'Access denied. Editor permissions required.', null, 403);
            }

            // Get subscription data
            $subscriptionData = [
                'service_name' => $input['service_name'] ?? '',
                'cost' => $input['cost'] ?? 0,
                'currency' => $input['currency'] ?? 'USD',
                'billing_cycle' => $input['billing_cycle'] ?? 'monthly',
                'start_date' => $input['start_date'] ?? '',
                'end_date' => $input['end_date'] ?? null,
                'category' => $input['category'] ?? 'Other',
                'added_by' => $user_id,
                'space_id' => $space_id
            ];

            // Validate required fields
            if (empty($subscriptionData['service_name']) || empty($subscriptionData['cost']) || empty($subscriptionData['start_date'])) {
                sendResponse('error', 'Service name, cost, and start date are required', null, 400);
            }

            // Add subscription to space (this would need a method in SpaceModel)
            $result = $spaceModel->addSubscription($space_id, $subscriptionData);

            if ($result) {
                sendResponse('success', 'Subscription added to space successfully', ['subscription_id' => $result]);
            } else {
                sendResponse('error', 'Failed to add subscription to space', null, 500);
            }
            break;

        case 'sync_subscriptions':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse('error', 'Method not allowed', null, 405);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $space_id = $input['space_id'] ?? null;
            $subscription_ids = $input['subscription_ids'] ?? [];

            if (!$space_id || empty($subscription_ids)) {
                sendResponse('error', 'Space ID and subscription IDs are required', null, 400);
            }

            // Check if user has permission to sync subscriptions (admin or editor)
            if (!$spaceModel->hasPermission($space_id, $user_id, 'editor')) {
                sendResponse('error', 'Access denied. Editor permissions required.', null, 403);
            }

            $result = $spaceModel->syncExistingSubscriptions($space_id, $subscription_ids, $user_id);

            if ($result !== false) {
                sendResponse('success', 'Subscriptions synced to space successfully', ['synced_count' => $result]);
            } else {
                sendResponse('error', 'Failed to sync subscriptions to space', null, 500);
            }
            break;

        case 'get_subscriptions':
            $space_id = $_GET['space_id'] ?? null;
            if (!$space_id) {
                sendResponse('error', 'Space ID is required', null, 400);
            }

            // Check if user has permission to view subscriptions (at least viewer)
            if (!$spaceModel->hasPermission($space_id, $user_id, 'viewer')) {
                sendResponse('error', 'Access denied', null, 403);
            }

            $subscriptions = $spaceModel->getSpaceSubscriptions($space_id);
            sendResponse('success', 'Space subscriptions retrieved successfully', ['subscriptions' => $subscriptions]);
            break;

        case 'update_member_role':
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                sendResponse('error', 'Method not allowed', null, 405);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $space_id = $input['space_id'] ?? null;
            $member_id = $input['member_id'] ?? null;
            $new_role = $input['new_role'] ?? null;
            $allowed_roles = ['admin', 'editor', 'viewer'];

            if (!$space_id || !$member_id || !$new_role) {
                sendResponse('error', 'Space ID, member ID, and new role are required', null, 400);
            }

            if (!in_array($new_role, $allowed_roles, true)) {
                sendResponse('error', 'Invalid role. Must be admin, editor, or viewer', null, 400);
            }

            if ($new_role === 'editor' && !$spaceModel->ensureEditorRoleSupport()) {
                sendResponse(
                    'error',
                    'Editor role is not available. Please ensure the latest database migrations have been applied.',
                    null,
                    500
                );
            }

            // Check if user has permission to update roles (admin only)
            if (!$spaceModel->hasPermission($space_id, $user_id, 'admin')) {
                sendResponse('error', 'Only admins can update member roles', null, 403);
            }

            // Prevent changing owner role
            $space = $spaceModel->getSpaceWithUserRole($space_id, $user_id);
            if ($space && $space['owner_id'] == $member_id) {
                sendResponse('error', 'Cannot change owner role', null, 400);
            }

            $success = $spaceModel->updateUserRole($space_id, $member_id, $new_role);

            if ($success) {
                sendResponse('success', 'Member role updated successfully');
            } else {
                $errorMessage = 'Failed to update member role';
                if ($new_role === 'editor' && !$spaceModel->supportsEditorRole()) {
                    $errorMessage = 'Failed to update member role because the editor role is not supported by the database schema.';
                }
                sendResponse('error', $errorMessage, null, 500);
            }
            break;

        default:
            sendResponse('error', 'Invalid action', null, 400);
    }

} catch (Exception $e) {
    error_log('Spaces API Error: ' . $e->getMessage());
    sendResponse('error', 'Internal server error', null, 500);
}
?>
