<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'src/Config/database.php';
require_once 'src/Models/SubscriptionModel.php';

$subscriptionModel = new SubscriptionModel($pdo);

// Test with subscription 32 and user 2 (who is a member of space 8)
$subscription_id = 32;
$user_id = 2;

echo "Testing endSubscription with:\n";
echo "- subscription_id: $subscription_id\n";
echo "- user_id: $user_id\n\n";

// Check subscription details first
$stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE id = ?");
$stmt->execute([$subscription_id]);
$sub = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Subscription details:\n";
print_r($sub);
echo "\n";

// Check membership
if ($sub['space_id']) {
    $member_stmt = $pdo->prepare("SELECT * FROM space_users WHERE space_id = ? AND user_id = ?");
    $member_stmt->execute([$sub['space_id'], $user_id]);
    $member = $member_stmt->fetch(PDO::FETCH_ASSOC);

    echo "Membership details:\n";
    print_r($member);
    echo "\n";
}

// Now call the method
echo "Calling endSubscription...\n";
$result = $subscriptionModel->endSubscription($subscription_id, $user_id);

echo "Result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";

// Check if it was actually updated
$stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE id = ?");
$stmt->execute([$subscription_id]);
$updated_sub = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\nUpdated subscription:\n";
print_r($updated_sub);
?>
