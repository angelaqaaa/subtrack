<?php
session_start();

// Set content type for JSON response
header('Content-Type: application/json');

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    require_once "../../src/Config/database.php";

    $service_name = trim($_POST["service_name"]);
    $cost = trim($_POST["cost"]);
    $currency = trim($_POST["currency"]);
    $billing_cycle = trim($_POST["billing_cycle"]);
    $start_date = trim($_POST["start_date"]);
    $category = trim($_POST["category"]);
    $user_id = $_SESSION["id"];

    $service_name_err = $cost_err = $billing_cycle_err = $start_date_err = "";

    // Validation
    if(empty($service_name)){
        $service_name_err = "Please enter a service name.";
    }

    if(empty($cost)){
        $cost_err = "Please enter the cost.";
    } elseif(!is_numeric($cost) || $cost <= 0){
        $cost_err = "Please enter a valid cost amount.";
    }

    if(empty($billing_cycle)){
        $billing_cycle_err = "Please select a billing cycle.";
    } elseif(!in_array($billing_cycle, ['monthly', 'yearly'])){
        $billing_cycle_err = "Please select a valid billing cycle.";
    }

    if(empty($start_date)){
        $start_date_err = "Please enter a start date.";
    }

    // Check for validation errors
    if(empty($service_name_err) && empty($cost_err) && empty($billing_cycle_err) && empty($start_date_err)){

        $sql = "INSERT INTO subscriptions (user_id, service_name, cost, currency, billing_cycle, start_date, category) VALUES (?, ?, ?, ?, ?, ?, ?)";

        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(1, $param_user_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $param_service_name, PDO::PARAM_STR);
            $stmt->bindParam(3, $param_cost, PDO::PARAM_STR);
            $stmt->bindParam(4, $param_currency, PDO::PARAM_STR);
            $stmt->bindParam(5, $param_billing_cycle, PDO::PARAM_STR);
            $stmt->bindParam(6, $param_start_date, PDO::PARAM_STR);
            $stmt->bindParam(7, $param_category, PDO::PARAM_STR);

            $param_user_id = $user_id;
            $param_service_name = $service_name;
            $param_cost = $cost;
            $param_currency = $currency;
            $param_billing_cycle = $billing_cycle;
            $param_start_date = $start_date;
            $param_category = $category;

            if($stmt->execute()){
                $subscription_id = $pdo->lastInsertId();

                // Generate HTML for new subscription row
                $monthly_equiv = $billing_cycle == 'monthly' ? $cost : $cost / 12;
                $subscription_html = '
                    <td>
                        <strong>' . htmlspecialchars($service_name) . '</strong>
                        <br><small class="text-muted">Started: ' . date('M d, Y', strtotime($start_date)) . '</small>
                    </td>
                    <td>
                        <strong>' . htmlspecialchars($currency) . ' ' . number_format($cost, 2) . '</strong>
                    </td>
                    <td>
                        <span class="badge ' . ($billing_cycle == 'monthly' ? 'bg-primary' : 'bg-success') . '">
                            ' . ucfirst($billing_cycle) . '
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-secondary">' . htmlspecialchars($category ?: 'Other') . '</span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="edit_subscription.php?id=' . $subscription_id . '" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger btn-sm delete-subscription-btn" data-id="' . $subscription_id . '">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                ';

                // Calculate updated summary
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
                    'message' => 'Subscription added successfully',
                    'subscription_html' => $subscription_html,
                    'summary' => $summary
                ]);
            } else{
                echo json_encode(['status' => 'error', 'message' => 'Failed to add subscription']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }

        unset($stmt);
    } else {
        // Return validation errors
        echo json_encode([
            'status' => 'validation_error',
            'errors' => [
                'service_name' => $service_name_err,
                'cost' => $cost_err,
                'billing_cycle' => $billing_cycle_err,
                'start_date' => $start_date_err
            ]
        ]);
    }

    unset($pdo);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>