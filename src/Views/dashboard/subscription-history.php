<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">
                    <i class="bi bi-clock-history me-2"></i>Subscription History
                </h1>
                <a href="/routes/dashboard.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <i class="bi bi-check-circle display-4"></i>
                    <h4 class="card-title mt-2"><?php echo count($active_subscriptions); ?></h4>
                    <p class="card-text">Active Subscriptions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-secondary text-white">
                <div class="card-body">
                    <i class="bi bi-pause-circle display-4"></i>
                    <h4 class="card-title mt-2"><?php echo count($ended_subscriptions); ?></h4>
                    <p class="card-text">Ended Subscriptions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <i class="bi bi-currency-dollar display-4"></i>
                    <h4 class="card-title mt-2">$<?php echo number_format($lifetime_spending['lifetime_spent'] ?? 0, 2); ?></h4>
                    <p class="card-text">Lifetime Spending</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-warning text-white">
                <div class="card-body">
                    <i class="bi bi-percent display-4"></i>
                    <h4 class="card-title mt-2"><?php echo number_format($reactivation_rate, 1); ?>%</h4>
                    <p class="card-text">Retention Rate</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Active Subscriptions -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-check-circle text-success me-2"></i>Active Subscriptions
                    </h5>
                    <span class="badge bg-success"><?php echo count($active_subscriptions); ?></span>
                </div>
                <div class="card-body">
                    <?php if(empty($active_subscriptions)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox display-4 d-block mb-2"></i>
                            No active subscriptions
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Cost</th>
                                        <th>Started</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($active_subscriptions as $sub): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($sub['service_name']); ?></strong>
                                            <?php if ($sub['subscription_type'] === 'space'): ?>
                                                <br><span class="badge bg-info">
                                                    <i class="bi bi-people-fill"></i> <?php echo htmlspecialchars($sub['space_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($sub['category'] ?: 'Other'); ?></small>
                                        </td>
                                        <td>
                                            <?php echo $sub['currency']; ?> <?php echo number_format($sub['cost'], 2); ?>
                                            <br><small class="text-muted"><?php echo $sub['billing_cycle']; ?></small>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($sub['start_date'])); ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-outline-warning btn-sm end-subscription-btn" data-id="<?php echo $sub['id']; ?>" data-name="<?php echo htmlspecialchars($sub['service_name']); ?>">
                                                <i class="bi bi-pause-circle"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ended Subscriptions -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-pause-circle text-secondary me-2"></i>Ended Subscriptions
                    </h5>
                    <span class="badge bg-secondary"><?php echo count($ended_subscriptions); ?></span>
                </div>
                <div class="card-body">
                    <?php if(empty($ended_subscriptions)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox display-4 d-block mb-2"></i>
                            No ended subscriptions
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Cost</th>
                                        <th>Duration</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($ended_subscriptions as $sub): ?>
                                    <tr class="table-secondary">
                                        <td>
                                            <strong><?php echo htmlspecialchars($sub['service_name']); ?></strong>
                                            <?php if ($sub['subscription_type'] === 'space'): ?>
                                                <br><span class="badge bg-info">
                                                    <i class="bi bi-people-fill"></i> <?php echo htmlspecialchars($sub['space_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($sub['category'] ?: 'Other'); ?></small>
                                            <?php if($sub['cancellation_reason']): ?>
                                                <br><small class="text-muted" title="Reason: <?php echo htmlspecialchars($sub['cancellation_reason']); ?>">
                                                    <i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($sub['cancellation_reason']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $sub['currency']; ?> <?php echo number_format($sub['cost'], 2); ?>
                                            <br><small class="text-muted"><?php echo $sub['billing_cycle']; ?></small>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($sub['start_date'])); ?>
                                            <?php if ($sub['end_date']): ?>
                                                <br><small class="text-danger">to <?php echo date('M d, Y', strtotime($sub['end_date'])); ?></small>
                                                <?php
                                                $start = new DateTime($sub['start_date']);
                                                $end = new DateTime($sub['end_date']);
                                                $duration = $start->diff($end);
                                                ?>
                                                <br><small class="text-muted"><?php echo $duration->format('%a days'); ?></small>
                                            <?php else: ?>
                                                <br><small class="text-muted">No end date</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-outline-success btn-sm reactivate-subscription-btn" data-id="<?php echo $sub['id']; ?>" data-name="<?php echo htmlspecialchars($sub['service_name']); ?>">
                                                <i class="bi bi-play-circle"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Spending Timeline Chart -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Spending Overview
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Current Monthly Spending</h6>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Monthly Cost:</span>
                                <strong>$<?php echo number_format($current_summary['monthly_cost'], 2); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Annual Cost:</span>
                                <strong>$<?php echo number_format($current_summary['annual_cost'], 2); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Active Subscriptions:</span>
                                <strong><?php echo $current_summary['subscription_count']; ?></strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Lifetime Statistics</h6>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Total Spent:</span>
                                <strong>$<?php echo number_format($lifetime_spending['lifetime_spent'] ?? 0, 2); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Total Subscriptions:</span>
                                <strong><?php echo $total_subscriptions; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Retention Rate:</span>
                                <strong><?php echo number_format($reactivation_rate, 1); ?>%</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('end_date').valueAsDate = new Date();
    document.getElementById('reactivate_start_date').valueAsDate = new Date();
});

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
</script>