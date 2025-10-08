<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../auth/login.php");
    exit;
}

require_once "../../src/Config/database.php";

// Fetch user subscriptions
$sql = "SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC";
$subscriptions = [];
$total_monthly_cost = 0;
$total_annual_cost = 0;
$category_totals = [];

if($stmt = $pdo->prepare($sql)){
    $stmt->bindParam(1, $_SESSION["id"], PDO::PARAM_INT);

    if($stmt->execute()){
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate totals
        foreach($subscriptions as $subscription){
            $monthly_cost = $subscription['billing_cycle'] == 'monthly' ? $subscription['cost'] : $subscription['cost'] / 12;
            $annual_cost = $subscription['billing_cycle'] == 'yearly' ? $subscription['cost'] : $subscription['cost'] * 12;

            $total_monthly_cost += $monthly_cost;
            $total_annual_cost += $annual_cost;

            // Group by category for chart
            $category = $subscription['category'] ?: 'Other';
            if(!isset($category_totals[$category])){
                $category_totals[$category] = 0;
            }
            $category_totals[$category] += $monthly_cost;
        }
    }
    unset($stmt);
}

$page_title = "Dashboard";
include "src/Views/layouts/header.php";
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </h1>
                <span class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</span>
            </div>

            <?php if(isset($_GET['success']) && $_GET['success'] == 'added'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>Subscription added successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['success']) && $_GET['success'] == 'updated'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>Subscription updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>Subscription deleted successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php if($_GET['error'] == 'add_failed'): ?>
                        Failed to add subscription. Please try again.
                    <?php elseif($_GET['error'] == 'validation'): ?>
                        Please fix the validation errors and try again.
                    <?php else: ?>
                        An error occurred. Please try again.
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <i class="bi bi-currency-dollar display-4"></i>
                    <h4 class="card-title mt-2">$<?php echo number_format($total_monthly_cost, 2); ?></h4>
                    <p class="card-text">Total Monthly Cost</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <i class="bi bi-calendar-year display-4"></i>
                    <h4 class="card-title mt-2">$<?php echo number_format($total_annual_cost, 2); ?></h4>
                    <p class="card-text">Total Annual Cost</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <i class="bi bi-list-ul display-4"></i>
                    <h4 class="card-title mt-2"><?php echo count($subscriptions); ?></h4>
                    <p class="card-text">Total Subscriptions</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-table me-2"></i>Your Subscriptions
                    </h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSubscriptionModal">
                        <i class="bi bi-plus-circle me-1"></i>Add Subscription
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="subscriptions-table">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Cost</th>
                                    <th>Billing</th>
                                    <th>Category</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($subscriptions)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                        No subscriptions found. Add your first subscription to get started!
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach($subscriptions as $subscription): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($subscription['service_name']); ?></strong>
                                            <br><small class="text-muted">Started: <?php echo date('M d, Y', strtotime($subscription['start_date'])); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo $subscription['currency']; ?> <?php echo number_format($subscription['cost'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $subscription['billing_cycle'] == 'monthly' ? 'bg-primary' : 'bg-success'; ?>">
                                                <?php echo ucfirst($subscription['billing_cycle']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($subscription['category'] ?: 'Other'); ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="edit_subscription.php?id=<?php echo $subscription['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger btn-sm delete-subscription-btn" data-id="<?php echo $subscription['id']; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-pie-chart me-2"></i>Spending by Category
                    </h5>
                </div>
                <div class="card-body text-center">
                    <canvas id="categoryChart" width="300" height="300" data-categories='<?php echo json_encode($category_totals); ?>'></canvas>
                    <?php if(empty($category_totals)): ?>
                        <p class="text-muted mt-3">Add subscriptions to see your spending breakdown</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if(!empty($subscriptions)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-lightbulb me-2"></i>Insights
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    $most_expensive = array_reduce($subscriptions, function($carry, $item) {
                        return ($carry === null || $item['cost'] > $carry['cost']) ? $item : $carry;
                    }, null);

                    $avg_cost = $total_monthly_cost / count($subscriptions);
                    $total_saved_annually = $total_annual_cost - ($total_monthly_cost * 12);
                    ?>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Most Expensive</h6>
                        <p class="mb-0">
                            <strong><?php echo htmlspecialchars($most_expensive['service_name']); ?></strong><br>
                            <small class="text-muted"><?php echo $most_expensive['currency']; ?> <?php echo number_format($most_expensive['cost'], 2); ?> / <?php echo $most_expensive['billing_cycle']; ?></small>
                        </p>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Average Monthly Cost</h6>
                        <p class="mb-0"><strong>$<?php echo number_format($avg_cost, 2); ?></strong></p>
                    </div>

                    <?php if($total_saved_annually > 0): ?>
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Annual Savings</h6>
                        <p class="mb-0 text-success">
                            <i class="bi bi-arrow-down-circle me-1"></i>
                            <strong>$<?php echo number_format($total_saved_annually, 2); ?></strong>
                            <br><small>by choosing annual plans</small>
                        </p>
                    </div>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="bi bi-calendar-check me-1"></i>
                            Tracking <?php echo count($subscriptions); ?> subscription<?php echo count($subscriptions) !== 1 ? 's' : ''; ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Subscription Modal -->
<div class="modal fade" id="addSubscriptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Subscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="add_subscription.php" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="service_name" class="form-label">Service Name</label>
                        <input type="text" class="form-control" id="service_name" name="service_name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cost" class="form-label">Cost</label>
                                <input type="number" step="0.01" class="form-control" id="cost" name="cost" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-control" id="currency" name="currency">
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GBP">GBP</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="billing_cycle" class="form-label">Billing Cycle</label>
                        <select class="form-control" id="billing_cycle" name="billing_cycle" required>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-control" id="category" name="category">
                            <option value="Entertainment">Entertainment</option>
                            <option value="Productivity">Productivity</option>
                            <option value="Health & Fitness">Health & Fitness</option>
                            <option value="News & Media">News & Media</option>
                            <option value="Cloud Storage">Cloud Storage</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Subscription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "src/Views/layouts/footer.php"; ?>