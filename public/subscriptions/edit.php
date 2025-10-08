<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../auth/login.php");
    exit;
}

require_once "../../src/Config/database.php";

$service_name = $cost = $currency = $billing_cycle = $start_date = $end_date = $category = "";
$service_name_err = $cost_err = $billing_cycle_err = $start_date_err = $end_date_err = "";

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $id = trim($_GET["id"]);

    $sql = "SELECT * FROM subscriptions WHERE id = ? AND user_id = ?";
    if($stmt = $pdo->prepare($sql)){
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->bindParam(2, $_SESSION["id"], PDO::PARAM_INT);

        if($stmt->execute()){
            if($stmt->rowCount() == 1){
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                $service_name = $row["service_name"];
                $cost = $row["cost"];
                $currency = $row["currency"];
                $billing_cycle = $row["billing_cycle"];
                $start_date = $row["start_date"];
                $end_date = $row["end_date"];
                $category = $row["category"];
            } else {
                header("location: ../dashboard/index.php?error=not_found");
                exit();
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
    unset($stmt);
} else {
    header("location: ../dashboard/index.php");
    exit();
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $service_name = trim($_POST["service_name"]);
    $cost = trim($_POST["cost"]);
    $currency = trim($_POST["currency"]);
    $billing_cycle = trim($_POST["billing_cycle"]);
    $start_date = trim($_POST["start_date"]);
    $end_date = !empty(trim($_POST["end_date"])) ? trim($_POST["end_date"]) : null;
    $category = trim($_POST["category"]);

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

    // Validate end date if provided
    if (!empty($end_date) && $end_date <= $start_date) {
        $end_date_err = "End date must be after start date.";
    }

    if(empty($service_name_err) && empty($cost_err) && empty($billing_cycle_err) && empty($start_date_err) && empty($end_date_err)){
        $sql = "UPDATE subscriptions SET service_name=?, cost=?, currency=?, billing_cycle=?, start_date=?, end_date=?, category=?, is_active=? WHERE id=? AND user_id=?";

        if($stmt = $pdo->prepare($sql)){
            // Determine if subscription is active based on end_date
            $is_active = true;
            if (!empty($end_date) && $end_date <= date('Y-m-d')) {
                $is_active = false;
            }

            $stmt->bindParam(1, $param_service_name, PDO::PARAM_STR);
            $stmt->bindParam(2, $param_cost, PDO::PARAM_STR);
            $stmt->bindParam(3, $param_currency, PDO::PARAM_STR);
            $stmt->bindParam(4, $param_billing_cycle, PDO::PARAM_STR);
            $stmt->bindParam(5, $param_start_date, PDO::PARAM_STR);
            $stmt->bindParam(6, $param_end_date, PDO::PARAM_STR);
            $stmt->bindParam(7, $param_category, PDO::PARAM_STR);
            $stmt->bindParam(8, $param_is_active, PDO::PARAM_BOOL);
            $stmt->bindParam(9, $param_id, PDO::PARAM_INT);
            $stmt->bindParam(10, $param_user_id, PDO::PARAM_INT);

            $param_service_name = $service_name;
            $param_cost = $cost;
            $param_currency = $currency;
            $param_billing_cycle = $billing_cycle;
            $param_start_date = $start_date;
            $param_end_date = $end_date;
            $param_category = $category;
            $param_is_active = $is_active;
            $param_id = $id;
            $param_user_id = $_SESSION["id"];

            if($stmt->execute()){
                header("location: ../dashboard/index.php?success=updated");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }

        unset($stmt);
    }

    unset($pdo);
}

$page_title = "Edit Subscription";
include "src/Views/layouts/header.php";
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Edit Subscription
                    </h3>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?>" method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="service_name" class="form-label">Service Name</label>
                                    <input type="text" name="service_name" class="form-control <?php echo (!empty($service_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $service_name; ?>" required>
                                    <span class="invalid-feedback"><?php echo $service_name_err; ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <select name="category" class="form-control">
                                        <!-- Default categories -->
                                        <option value="Entertainment" <?php echo ($category == 'Entertainment') ? 'selected' : ''; ?>>Entertainment</option>
                                        <option value="Productivity" <?php echo ($category == 'Productivity') ? 'selected' : ''; ?>>Productivity</option>
                                        <option value="Health & Fitness" <?php echo ($category == 'Health & Fitness') ? 'selected' : ''; ?>>Health & Fitness</option>
                                        <option value="News & Media" <?php echo ($category == 'News & Media') ? 'selected' : ''; ?>>News & Media</option>
                                        <option value="Cloud Storage" <?php echo ($category == 'Cloud Storage') ? 'selected' : ''; ?>>Cloud Storage</option>
                                        <option value="Other" <?php echo ($category == 'Other') ? 'selected' : ''; ?>>Other</option>

                                        <!-- Custom categories would need to be fetched here -->
                                        <?php
                                        // Fetch custom categories for this user
                                        require_once '../../src/Models/CategoryModel.php';
                                        $categoryModel = new CategoryModel($pdo);
                                        $custom_categories = $categoryModel->getUserCategories($_SESSION["id"]);

                                        if (!empty($custom_categories)): ?>
                                            <optgroup label="Custom Categories">
                                                <?php foreach ($custom_categories as $custom_cat): ?>
                                                    <option value="<?php echo htmlspecialchars($custom_cat['name']); ?>" <?php echo ($category == $custom_cat['name']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($custom_cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="cost" class="form-label">Cost</label>
                                    <input type="number" step="0.01" name="cost" class="form-control <?php echo (!empty($cost_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $cost; ?>" required>
                                    <span class="invalid-feedback"><?php echo $cost_err; ?></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">Currency</label>
                                    <select name="currency" class="form-control">
                                        <option value="USD" <?php echo ($currency == 'USD') ? 'selected' : ''; ?>>USD</option>
                                        <option value="EUR" <?php echo ($currency == 'EUR') ? 'selected' : ''; ?>>EUR</option>
                                        <option value="GBP" <?php echo ($currency == 'GBP') ? 'selected' : ''; ?>>GBP</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="billing_cycle" class="form-label">Billing Cycle</label>
                                    <select name="billing_cycle" class="form-control <?php echo (!empty($billing_cycle_err)) ? 'is-invalid' : ''; ?>" required>
                                        <option value="monthly" <?php echo ($billing_cycle == 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                                        <option value="yearly" <?php echo ($billing_cycle == 'yearly') ? 'selected' : ''; ?>>Yearly</option>
                                    </select>
                                    <span class="invalid-feedback"><?php echo $billing_cycle_err; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control <?php echo (!empty($start_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $start_date; ?>" required>
                                    <span class="invalid-feedback"><?php echo $start_date_err; ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date (Optional)</label>
                                    <input type="date" name="end_date" class="form-control <?php echo (!empty($end_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $end_date; ?>">
                                    <div class="form-text">Leave empty for ongoing subscription</div>
                                    <span class="invalid-feedback"><?php echo $end_date_err; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Update Subscription
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "src/Views/layouts/footer.php"; ?>