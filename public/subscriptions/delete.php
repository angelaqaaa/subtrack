<?php
session_start();

// Set content type for JSON response
header('Content-Type: application/json');

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    require_once "../../src/Config/database.php";

    $subscription_id = trim($_GET["id"]);
    $user_id = $_SESSION["id"];

    // Validate that the subscription exists and belongs to the user
    $check_sql = "SELECT id FROM subscriptions WHERE id = ? AND user_id = ?";
    if($check_stmt = $pdo->prepare($check_sql)){
        $check_stmt->bindParam(1, $subscription_id, PDO::PARAM_INT);
        $check_stmt->bindParam(2, $user_id, PDO::PARAM_INT);

        if($check_stmt->execute()){
            if($check_stmt->rowCount() === 0){
                echo json_encode(['status' => 'error', 'message' => 'Subscription not found or access denied']);
                exit;
            }
        }
        unset($check_stmt);
    }

    // Delete the subscription
    $sql = "DELETE FROM subscriptions WHERE id = ? AND user_id = ?";

    if($stmt = $pdo->prepare($sql)){
        $stmt->bindParam(1, $param_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $param_user_id, PDO::PARAM_INT);

        $param_id = $subscription_id;
        $param_user_id = $user_id;

        if($stmt->execute()){
            if($stmt->rowCount() > 0){
                // Calculate updated summary after deletion
                $summary_sql = "SELECT
                    COUNT(*) as subscription_count,
                    SUM(CASE WHEN billing_cycle = 'monthly' THEN cost ELSE cost/12 END) as monthly_cost,
                    SUM(CASE WHEN billing_cycle = 'yearly' THEN cost ELSE cost*12 END) as annual_cost
                FROM subscriptions WHERE user_id = ?";

                $summary = ['subscription_count' => 0, 'monthly_cost' => 0, 'annual_cost' => 0];
                if($summary_stmt = $pdo->prepare($summary_sql)){
                    $summary_stmt->bindParam(1, $user_id, PDO::PARAM_INT);
                    if($summary_stmt->execute()){
                        $result = $summary_stmt->fetch(PDO::FETCH_ASSOC);
                        $summary = [
                            'subscription_count' => (int)$result['subscription_count'],
                            'monthly_cost' => (float)$result['monthly_cost'],
                            'annual_cost' => (float)$result['annual_cost']
                        ];
                    }
                    unset($summary_stmt);
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Subscription deleted successfully',
                    'summary' => $summary
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete subscription']);
            }
        } else{
            echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare database statement']);
    }

    unset($stmt);
    unset($pdo);
} else{
    echo json_encode(['status' => 'error', 'message' => 'Invalid subscription ID']);
}
?>