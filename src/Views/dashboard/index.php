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

            <?php if(isset($_GET['success']) && $_GET['success'] == 'quit_space'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>You have successfully left the space!
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

    <!-- Spaces Navigation -->
    <?php if(!empty($spaces)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-people me-2"></i>Shared Spaces
                    </h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createSpaceModal">
                        <i class="bi bi-plus-circle me-1"></i>Create Space
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach($spaces as $space): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card border-<?php echo $space['user_role'] === 'admin' ? 'success' : 'info'; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-0"><?php echo htmlspecialchars($space['name']); ?></h6>
                                            <span class="badge bg-<?php echo $space['user_role'] === 'admin' ? 'success' : 'info'; ?>">
                                                <?php echo ucfirst($space['user_role']); ?>
                                            </span>
                                        </div>
                                        <?php if($space['description']): ?>
                                            <p class="card-text small text-muted"><?php echo htmlspecialchars($space['description']); ?></p>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="bi bi-people me-1"></i><?php echo $space['member_count']; ?> member<?php echo $space['member_count'] !== 1 ? 's' : ''; ?>
                                            </small>
                                            <a href="/routes/space.php?action=view&space_id=<?php echo $space['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-arrow-right me-1"></i>Enter
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Personal Subscriptions Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="bi bi-person me-2"></i>Personal Subscriptions
                </h3>
                <?php if(empty($spaces)): ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#createSpaceModal">
                        <i class="bi bi-plus-circle me-1"></i>Create Shared Space
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <i class="bi bi-currency-dollar display-4"></i>
                    <h4 class="card-title mt-2">$<?php echo number_format($summary['monthly_cost'], 2); ?></h4>
                    <p class="card-text">Total Monthly Cost</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <i class="bi bi-calendar-year display-4"></i>
                    <h4 class="card-title mt-2">$<?php echo number_format($summary['annual_cost'], 2); ?></h4>
                    <p class="card-text">Total Annual Cost</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <i class="bi bi-list-ul display-4"></i>
                    <h4 class="card-title mt-2"><?php echo $summary['subscription_count']; ?></h4>
                    <p class="card-text">Total Subscriptions</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Subscriptions Table -->
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
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($subscriptions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                        No subscriptions found. Add your first subscription to get started!
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach($subscriptions as $subscription): ?>
                                    <tr class="<?php echo (!$subscription['is_active']) ? 'table-secondary' : ''; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($subscription['service_name']); ?></strong>
                                            <?php if ($subscription['subscription_type'] === 'space'): ?>
                                                <span class="badge bg-info ms-2" title="From space: <?php echo htmlspecialchars($subscription['space_name']); ?>">
                                                    <i class="bi bi-people-fill"></i> <?php echo htmlspecialchars($subscription['space_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <br><small class="text-muted">Started: <?php echo date('M d, Y', strtotime($subscription['start_date'])); ?></small>
                                            <?php if ($subscription['subscription_type'] === 'space'): ?>
                                                <br><small class="text-info">Added by: <?php echo htmlspecialchars($subscription['created_by_username']); ?></small>
                                            <?php endif; ?>
                                            <?php if (!$subscription['is_active'] && $subscription['end_date']): ?>
                                                <br><small class="text-danger">Ended: <?php echo date('M d, Y', strtotime($subscription['end_date'])); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo $subscription['currency']; ?> <?php echo number_format($subscription['cost'], 2); ?></strong>
                                            <?php if (!$subscription['is_active']): ?>
                                                <br><small class="text-muted">(Not counted)</small>
                                            <?php endif; ?>
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
                                            <?php if ($subscription['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Ended</span>
                                                <?php if ($subscription['cancellation_reason']): ?>
                                                    <br><small class="text-muted" title="<?php echo htmlspecialchars($subscription['cancellation_reason']); ?>">
                                                        <i class="bi bi-info-circle"></i> Reason
                                                    </small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($subscription['subscription_type'] === 'space'): ?>
                                                <a href="/routes/space.php?action=view&space_id=<?php echo $subscription['space_id']; ?>" class="btn btn-outline-info btn-sm" title="Manage in space">
                                                    <i class="bi bi-box-arrow-up-right"></i> View in Space
                                                </a>
                                            <?php else: ?>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="/public/subscriptions/edit.php?id=<?php echo $subscription['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php if ($subscription['is_active']): ?>
                                                        <button type="button" class="btn btn-outline-warning btn-sm end-subscription-btn" data-id="<?php echo $subscription['id']; ?>" data-name="<?php echo htmlspecialchars($subscription['service_name']); ?>">
                                                            <i class="bi bi-pause-circle"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-outline-success btn-sm reactivate-subscription-btn" data-id="<?php echo $subscription['id']; ?>" data-name="<?php echo htmlspecialchars($subscription['service_name']); ?>">
                                                            <i class="bi bi-play-circle"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-outline-danger btn-sm delete-subscription-btn" data-id="<?php echo $subscription['id']; ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            <?php endif; ?>
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

        <!-- Sidebar -->
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

            <?php if(!empty($subscriptions) && $insights): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-lightbulb me-2"></i>Insights
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Most Expensive</h6>
                        <p class="mb-0">
                            <strong><?php echo htmlspecialchars($insights['most_expensive']['service_name']); ?></strong><br>
                            <small class="text-muted"><?php echo $insights['most_expensive']['currency']; ?> <?php echo number_format($insights['most_expensive']['cost'], 2); ?> / <?php echo $insights['most_expensive']['billing_cycle']; ?></small>
                        </p>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Average Monthly Cost</h6>
                        <p class="mb-0"><strong>$<?php echo number_format($insights['avg_cost'], 2); ?></strong></p>
                    </div>

                    <?php if($insights['total_saved_annually'] > 0): ?>
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Annual Savings</h6>
                        <p class="mb-0 text-success">
                            <i class="bi bi-arrow-down-circle me-1"></i>
                            <strong>$<?php echo number_format($insights['total_saved_annually'], 2); ?></strong>
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
            <form action="/routes/dashboard.php?action=add" method="post">
                <?php echo $csrf_token ? '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf_token) . '">' : ''; ?>
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
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date (Optional)</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                                <div class="form-text">Leave empty for ongoing subscription</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-control" id="category" name="category">
                            <!-- Default categories -->
                            <option value="Entertainment">Entertainment</option>
                            <option value="Productivity">Productivity</option>
                            <option value="Health & Fitness">Health & Fitness</option>
                            <option value="News & Media">News & Media</option>
                            <option value="Cloud Storage">Cloud Storage</option>
                            <option value="Other">Other</option>

                            <!-- Custom categories -->
                            <?php if (!empty($custom_categories)): ?>
                                <optgroup label="Custom Categories">
                                    <?php foreach ($custom_categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
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

<!-- Create Space Modal -->
<div class="modal fade" id="createSpaceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Shared Space</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createSpaceForm" action="/routes/dashboard.php?action=create_space" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="space_name" class="form-label">Space Name</label>
                        <input type="text" class="form-control" id="space_name" name="space_name" required>
                        <div class="form-text">Choose a descriptive name like "Family Finances" or "Startup Costs"</div>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="space_description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="space_description" name="space_description" rows="3"></textarea>
                        <div class="form-text">Briefly describe what this space will be used for</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Space</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- End Subscription Modal -->
<div class="modal fade" id="endSubscriptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">End Subscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="endSubscriptionForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="subscription_id" id="end_subscription_id">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Are you sure you want to end this subscription?</strong>
                        <p class="mb-0 mt-2">This will stop counting it in your spending calculations, but keep it in your history for reference.</p>
                    </div>
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                        <div class="form-text">When did or will this subscription end?</div>
                    </div>
                    <div class="mb-3">
                        <label for="end_reason" class="form-label">Reason (Optional)</label>
                        <select class="form-control" id="end_reason" name="reason">
                            <option value="">Select a reason...</option>
                            <option value="No longer needed">No longer needed</option>
                            <option value="Too expensive">Too expensive</option>
                            <option value="Found better alternative">Found better alternative</option>
                            <option value="Service quality issues">Service quality issues</option>
                            <option value="Temporary pause">Temporary pause</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">End Subscription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reactivate Subscription Modal -->
<div class="modal fade" id="reactivateSubscriptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reactivate Subscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="reactivateSubscriptionForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="subscription_id" id="reactivate_subscription_id">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Reactivate this subscription?</strong>
                        <p class="mb-0 mt-2">This will start counting it in your spending calculations again.</p>
                    </div>
                    <div class="mb-3">
                        <label for="reactivate_start_date" class="form-label">New Start Date</label>
                        <input type="date" class="form-control" id="reactivate_start_date" name="start_date" required>
                        <div class="form-text">When did or will this subscription restart?</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Reactivate Subscription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Set default dates
document.getElementById('end_date').valueAsDate = new Date();
document.getElementById('reactivate_start_date').valueAsDate = new Date();

// Handle End Subscription
document.addEventListener('click', function(e) {
    if (e.target.closest('.end-subscription-btn')) {
        const btn = e.target.closest('.end-subscription-btn');
        const subscriptionId = btn.dataset.id;
        const subscriptionName = btn.dataset.name;

        document.getElementById('end_subscription_id').value = subscriptionId;
        document.querySelector('#endSubscriptionModal .modal-title').textContent = `End ${subscriptionName}`;

        const modal = new bootstrap.Modal(document.getElementById('endSubscriptionModal'));
        modal.show();
    }
});

// Handle Reactivate Subscription
document.addEventListener('click', function(e) {
    if (e.target.closest('.reactivate-subscription-btn')) {
        const btn = e.target.closest('.reactivate-subscription-btn');
        const subscriptionId = btn.dataset.id;
        const subscriptionName = btn.dataset.name;

        document.getElementById('reactivate_subscription_id').value = subscriptionId;
        document.querySelector('#reactivateSubscriptionModal .modal-title').textContent = `Reactivate ${subscriptionName}`;

        const modal = new bootstrap.Modal(document.getElementById('reactivateSubscriptionModal'));
        modal.show();
    }
});

// Handle End Subscription form submission
document.getElementById('endSubscriptionForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;

    // Show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Ending...';

    fetch('/routes/dashboard.php?action=end_subscription', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Close modal and reload page to show updated data
            bootstrap.Modal.getInstance(document.getElementById('endSubscriptionModal')).hide();
            location.reload();
        } else {
            showAlert('danger', data.message || 'Failed to end subscription.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred. Please try again.');
    })
    .finally(() => {
        // Reset button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    });
});

// Handle Reactivate Subscription form submission
document.getElementById('reactivateSubscriptionForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;

    // Show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Reactivating...';

    fetch('/routes/dashboard.php?action=reactivate_subscription', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Close modal and reload page to show updated data
            bootstrap.Modal.getInstance(document.getElementById('reactivateSubscriptionModal')).hide();
            location.reload();
        } else {
            showAlert('danger', data.message || 'Failed to reactivate subscription.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred. Please try again.');
    })
    .finally(() => {
        // Reset button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    });
});

// Utility function to show alerts
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Insert at the top of the container
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);

    // Auto-hide after 5 seconds
    setTimeout(() => {
        if (alertDiv && alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Handle Create Space form submission
document.getElementById('createSpaceForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;

    // Show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Creating...';

    fetch('/routes/dashboard.php?action=create_space', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Redirect to the new space
            window.location.href = data.redirect_url;
        } else {
            showAlert('danger', data.message || 'Failed to create space.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred. Please try again.');
    })
    .finally(() => {
        // Reset button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    });
});
</script>