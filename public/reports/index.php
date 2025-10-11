<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: /public/auth/login.php");
    exit;
}

require_once "../../src/Config/database.php";

// Set default date range (last 12 months)
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-12 months'));

// Fetch filtered subscriptions (only active ones)
$sql = "SELECT * FROM subscriptions WHERE user_id = ? AND start_date BETWEEN ? AND ? AND is_active = 1 ORDER BY start_date DESC";
$filtered_subscriptions = [];

if($stmt = $pdo->prepare($sql)){
    $stmt->bindParam(1, $_SESSION["id"], PDO::PARAM_INT);
    $stmt->bindParam(2, $start_date, PDO::PARAM_STR);
    $stmt->bindParam(3, $end_date, PDO::PARAM_STR);

    if($stmt->execute()){
        $filtered_subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($stmt);
}

// Calculate filtered totals
$filtered_monthly_cost = 0;
$filtered_annual_cost = 0;
$category_breakdown = [];

foreach($filtered_subscriptions as $subscription){
    $monthly_cost = $subscription['billing_cycle'] == 'monthly' ? $subscription['cost'] : $subscription['cost'] / 12;
    $annual_cost = $subscription['billing_cycle'] == 'yearly' ? $subscription['cost'] : $subscription['cost'] * 12;

    $filtered_monthly_cost += $monthly_cost;
    $filtered_annual_cost += $annual_cost;

    $category = $subscription['category'] ?: 'Other';
    if(!isset($category_breakdown[$category])){
        $category_breakdown[$category] = 0;
    }
    $category_breakdown[$category] += $monthly_cost;
}

// Fetch historical spending data (last 12 months, only active subscriptions)
$historical_sql = "SELECT
    DATE_FORMAT(start_date, '%Y-%m') as month_key,
    SUM(CASE WHEN billing_cycle = 'monthly' THEN cost ELSE cost/12 END) as total_monthly_cost
FROM subscriptions
WHERE user_id = ? AND start_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND is_active = 1
GROUP BY DATE_FORMAT(start_date, '%Y-%m')
ORDER BY month_key ASC";

$historical_data = [];
if($stmt = $pdo->prepare($historical_sql)){
    $stmt->bindParam(1, $_SESSION["id"], PDO::PARAM_INT);

    if($stmt->execute()){
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Create full 12-month array with zeros
        for($i = 11; $i >= 0; $i--){
            $month = date('Y-m', strtotime("-$i months"));
            $historical_data[$month] = 0;
        }

        // Fill in actual data
        foreach($results as $row){
            $historical_data[$row['month_key']] = (float)$row['total_monthly_cost'];
        }
    }
    unset($stmt);
}

$page_title = "Reports & Analytics";
include "../../src/Views/layouts/header.php";
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">
                    <i class="bi bi-graph-up me-2"></i>Reports & Analytics
                </h1>
                <a href="/public/reports/export.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>"
                   class="btn btn-success">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
            </div>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-range me-2"></i>Date Range Filter
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="/public/reports/index.php" class="row g-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date"
                                   value="<?php echo htmlspecialchars($start_date); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date"
                                   value="<?php echo htmlspecialchars($end_date); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel me-1"></i>Apply Filter
                                </button>
                            </div>
                        </div>
                    </form>
                    <div class="mt-3">
                        <small class="text-muted">
                            Quick filters:
                            <a href="?start_date=<?php echo date('Y-m-d', strtotime('-1 month')); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="text-decoration-none">Last Month</a> |
                            <a href="?start_date=<?php echo date('Y-m-d', strtotime('-3 months')); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="text-decoration-none">Last 3 Months</a> |
                            <a href="?start_date=<?php echo date('Y-m-d', strtotime('-6 months')); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="text-decoration-none">Last 6 Months</a> |
                            <a href="?start_date=<?php echo date('Y-m-d', strtotime('-12 months')); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="text-decoration-none">Last Year</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards for Filtered Period -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <i class="bi bi-currency-dollar display-6"></i>
                    <h4 class="card-title mt-2">$<?php echo number_format($filtered_monthly_cost, 2); ?></h4>
                    <p class="card-text">Monthly Cost (Filtered)</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <i class="bi bi-calendar-year display-6"></i>
                    <h4 class="card-title mt-2">$<?php echo number_format($filtered_annual_cost, 2); ?></h4>
                    <p class="card-text">Annual Cost (Filtered)</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <i class="bi bi-list-ul display-6"></i>
                    <h4 class="card-title mt-2"><?php echo count($filtered_subscriptions); ?></h4>
                    <p class="card-text">Subscriptions (Filtered)</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-warning text-white">
                <div class="card-body">
                    <i class="bi bi-graph-up display-6"></i>
                    <h4 class="card-title mt-2">$<?php echo count($filtered_subscriptions) > 0 ? number_format($filtered_monthly_cost / count($filtered_subscriptions), 2) : '0.00'; ?></h4>
                    <p class="card-text">Avg Cost/Service</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Historical Spending Trend -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up-arrow me-2"></i>Monthly Spending Trend (Last 12 Months)
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="historicalChart" height="400" data-historical='<?php echo json_encode($historical_data); ?>'></canvas>
                </div>
            </div>
        </div>

        <!-- Category Breakdown -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-pie-chart me-2"></i>Category Breakdown (Filtered Period)
                    </h5>
                </div>
                <div class="card-body text-center">
                    <canvas id="categoryBreakdownChart" data-categories='<?php echo json_encode($category_breakdown); ?>'></canvas>
                    <?php if(empty($category_breakdown)): ?>
                        <p class="text-muted mt-3">No subscriptions found for selected period</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-table me-2"></i>Detailed Subscription Report
                    </h5>
                </div>
                <div class="card-body">
                    <?php if(empty($filtered_subscriptions)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox display-4 text-muted"></i>
                            <p class="text-muted mt-2">No subscriptions found for the selected date range.</p>
                            <p class="text-muted">Try adjusting your date filter or <a href="/routes/dashboard.php">add some subscriptions</a>.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Service Name</th>
                                        <th>Cost</th>
                                        <th>Billing Cycle</th>
                                        <th>Category</th>
                                        <th>Start Date</th>
                                        <th>Monthly Equivalent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($filtered_subscriptions as $subscription): ?>
                                        <?php $monthly_equiv = $subscription['billing_cycle'] == 'monthly' ? $subscription['cost'] : $subscription['cost'] / 12; ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($subscription['service_name']); ?></strong></td>
                                            <td><?php echo $subscription['currency']; ?> <?php echo number_format($subscription['cost'], 2); ?></td>
                                            <td>
                                                <span class="badge <?php echo $subscription['billing_cycle'] == 'monthly' ? 'bg-primary' : 'bg-success'; ?>">
                                                    <?php echo ucfirst($subscription['billing_cycle']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($subscription['category'] ?: 'Other'); ?></span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($subscription['start_date'])); ?></td>
                                            <td><strong>$<?php echo number_format($monthly_equiv, 2); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-success">
                                        <th colspan="5">Total Monthly Equivalent:</th>
                                        <th><strong>$<?php echo number_format($filtered_monthly_cost, 2); ?></strong></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../../src/Views/layouts/footer.php"; ?>