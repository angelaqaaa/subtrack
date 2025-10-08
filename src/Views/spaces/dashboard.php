<div class="container">
    <div class="row">
        <div class="col-12">
            <!-- Space Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2">
                        <i class="bi bi-people me-2"></i><?php echo htmlspecialchars($space['name']); ?>
                        <span class="badge bg-<?php echo $space['user_role'] === 'admin' ? 'success' : 'info'; ?> ms-2">
                            <?php echo ucfirst($space['user_role']); ?>
                        </span>
                    </h1>
                    <?php if($space['description']): ?>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($space['description']); ?></p>
                    <?php endif; ?>
                    <small class="text-muted">
                        Owned by <?php echo htmlspecialchars($space['owner_username']); ?> •
                        <?php echo count($members); ?> member<?php echo count($members) !== 1 ? 's' : ''; ?>
                    </small>
                </div>
                <div>
                    <a href="/routes/dashboard.php" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left me-1"></i>Back to Personal
                    </a>
                    <?php if($space['user_role'] === 'admin'): ?>
                        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#inviteUserModal">
                            <i class="bi bi-person-plus me-1"></i>Invite User
                        </button>
                    <?php endif; ?>
                    <!-- Only show quit button if user is not the owner -->
                    <?php if($space['owner_id'] != $_SESSION['id']): ?>
                        <button type="button" class="btn btn-outline-danger" id="quitSpaceBtn" data-space-id="<?php echo $space['id']; ?>" data-space-name="<?php echo htmlspecialchars($space['name']); ?>">
                            <i class="bi bi-door-open me-1"></i>Quit Space
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php
                    switch($_GET['success']) {
                        case 'user_invited': echo 'User invited successfully!'; break;
                        case 'subscription_added': echo 'Subscription added successfully!'; break;
                        default: echo 'Operation completed successfully!';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php
                    switch($_GET['error']) {
                        case 'insufficient_permissions': echo 'You do not have permission to perform this action.'; break;
                        case 'user_not_found': echo 'User not found.'; break;
                        case 'invite_failed': echo 'Failed to invite user.'; break;
                        default: echo 'An error occurred.';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
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
                        <i class="bi bi-table me-2"></i>Shared Subscriptions
                    </h5>
                    <?php if($space['user_role'] === 'admin'): ?>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSubscriptionModal">
                            <i class="bi bi-plus-circle me-1"></i>Add Subscription
                        </button>
                    <?php endif; ?>
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
                                    <th>Added By</th>
                                    <?php if($space['user_role'] === 'admin'): ?>
                                        <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($subscriptions)): ?>
                                <tr>
                                    <td colspan="<?php echo $space['user_role'] === 'admin' ? '6' : '5'; ?>" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                        No subscriptions found. <?php echo $space['user_role'] === 'admin' ? 'Add the first subscription to get started!' : ''; ?>
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
                                            <small class="text-muted"><?php echo htmlspecialchars($subscription['created_by']); ?></small>
                                        </td>
                                        <?php if($space['user_role'] === 'admin'): ?>
                                        <td>
                                            <button type="button" class="btn btn-outline-danger btn-sm delete-subscription-btn" data-id="<?php echo $subscription['id']; ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                        <?php endif; ?>
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
            <!-- Category Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-pie-chart me-2"></i>Spending by Category
                    </h5>
                </div>
                <div class="card-body text-center">
                    <canvas id="categoryChart" width="300" height="300" data-categories='<?php echo json_encode($category_totals); ?>'></canvas>
                    <?php if(empty($category_totals)): ?>
                        <p class="text-muted mt-3">Add subscriptions to see spending breakdown</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Members List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-people me-2"></i>Members (<?php echo count($members); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach($members as $member): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong><?php echo htmlspecialchars($member['username']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($member['email']); ?></small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-<?php echo $member['role'] === 'admin' ? 'success' : 'info'; ?>">
                                    <?php echo ucfirst($member['role']); ?>
                                </span>
                                <?php if($space['user_role'] === 'admin' && $member['user_id'] != $space['owner_id'] && $member['user_id'] != $_SESSION['id']): ?>
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="showRemoveMemberModal(<?php echo $member['user_id']; ?>, '<?php echo htmlspecialchars($member['username'], ENT_QUOTES); ?>')"
                                            title="Remove member">
                                        <i class="bi bi-person-x"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if($member !== end($members)): ?>
                            <hr class="my-2">
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>Recent Activity
                    </h5>
                </div>
                <div class="card-body">
                    <?php if(empty($activities)): ?>
                        <div class="text-center py-3">
                            <i class="bi bi-journal-text display-4 text-muted"></i>
                            <p class="text-muted mt-2">No activity yet</p>
                        </div>
                    <?php else: ?>
                        <div class="activity-feed">
                            <?php foreach($formatted_activities as $index => $formatted): ?>
                                <div class="activity-item d-flex align-items-start mb-3">
                                    <div class="activity-icon me-3">
                                        <i class="bi <?php echo $formatted['icon']; ?> text-<?php echo $formatted['color']; ?>"></i>
                                    </div>
                                    <div class="activity-content flex-grow-1">
                                        <div class="activity-message">
                                            <?php echo $formatted['message']; ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i><?php echo $formatted['timestamp']; ?>
                                        </small>
                                        <?php if(!empty($formatted['details'])): ?>
                                            <div class="activity-details mt-1">
                                                <small class="text-muted">
                                                    <?php if(isset($formatted['details']['cost'])): ?>
                                                        Cost: <?php echo $formatted['details']['currency'] ?? 'USD'; ?> <?php echo number_format($formatted['details']['cost'], 2); ?>
                                                        • Cycle: <?php echo ucfirst($formatted['details']['billing_cycle']); ?>
                                                        <?php if($formatted['details']['category']): ?>
                                                            • Category: <?php echo htmlspecialchars($formatted['details']['category']); ?>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if($index < count($formatted_activities) - 1): ?>
                                    <hr class="my-2">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <?php if(count($activities) >= 20): ?>
                            <div class="text-center mt-3">
                                <small class="text-muted">Showing last 20 activities</small>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Subscription Modal (Admin Only) -->
<?php if($space['user_role'] === 'admin'): ?>
<div class="modal fade" id="addSubscriptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Subscription to <?php echo htmlspecialchars($space['name']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="space.php?action=add_subscription" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="space_id" value="<?php echo $space['id']; ?>">
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


<!-- Invite User Modal -->
<div class="modal fade" id="inviteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Invite User to <?php echo htmlspecialchars($space['name']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="inviteUserForm" action="space.php?action=invite" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="space_id" value="<?php echo $space['id']; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="user_email" class="form-label">User Email</label>
                        <input type="email" class="form-control" id="user_email" name="email" required>
                        <div class="form-text">Enter the email of an existing SubTrack user</div>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="viewer">Viewer (can only view subscriptions and reports)</option>
                            <option value="admin">Admin (can add/edit/delete subscriptions and manage users)</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="inviteSubmitBtn">Send Invitation</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Quit Space Modal (Available to all users) -->
<div class="modal fade" id="quitSpaceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quit Space</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Are you sure you want to quit this space?</strong>
                </div>
                <p>You will no longer have access to <strong id="quitSpaceName"></strong> and its subscriptions. Only space admins can re-invite you.</p>
                <p class="text-muted mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmQuitSpace">Quit Space</button>
            </div>
        </div>
    </div>
</div>

<!-- Remove Member Modal -->
<div class="modal fade" id="removeMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Remove Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Are you sure you want to remove this member?</strong>
                </div>
                <p>You are about to remove <strong id="removeMemberName"></strong> from this space.</p>
                <p class="text-muted mb-0">They will lose access to all subscriptions and data in this space. Only space admins can re-invite them.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRemoveMember">Remove Member</button>
            </div>
        </div>
    </div>
</div>

<script>
// Set the current space context for AJAX forms
window.currentSpaceId = <?php echo $space['id']; ?>;
window.userRole = '<?php echo $space['user_role']; ?>';

// Handle quit space button
const quitSpaceBtn = document.getElementById('quitSpaceBtn');
if (quitSpaceBtn) {
    quitSpaceBtn.addEventListener('click', function() {
        console.log('Quit space button clicked');
        const spaceId = this.dataset.spaceId;
        const spaceName = this.dataset.spaceName;

        console.log('Space name:', spaceName);

        const quitSpaceNameElement = document.getElementById('quitSpaceName');
        if (quitSpaceNameElement) {
            quitSpaceNameElement.textContent = spaceName;
        } else {
            console.error('quitSpaceName element not found');
        }

        // Show the modal
        const modalElement = document.getElementById('quitSpaceModal');
        if (modalElement) {
            console.log('Modal element found, trying to show');
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            console.error('quitSpaceModal element not found');
        }
    });
} else {
    console.log('quitSpaceBtn not found');
}

// Handle confirm quit space
document.getElementById('confirmQuitSpace')?.addEventListener('click', function() {
    const spaceId = window.currentSpaceId;
    const submitButton = this;
    const originalButtonText = submitButton.innerHTML;

    // Show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Leaving...';

    // Send quit request
    fetch('space.php?action=quit', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `space_id=${spaceId}&csrf_token=<?php echo htmlspecialchars($csrf_token); ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Redirect to dashboard
            window.location.href = '/routes/dashboard.php?success=quit_space';
        } else {
            alert('Error: ' + (data.message || 'Failed to quit space.'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    })
    .finally(() => {
        // Reset button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    });
});

// Remove member functions
let memberToRemove = null;

function showRemoveMemberModal(userId, username) {
    memberToRemove = userId;
    document.getElementById('removeMemberName').textContent = username;

    const modal = new bootstrap.Modal(document.getElementById('removeMemberModal'));
    modal.show();
}

// Handle confirm remove member
document.getElementById('confirmRemoveMember')?.addEventListener('click', function() {
    if (!memberToRemove) return;

    const spaceId = window.currentSpaceId;
    const submitButton = this;
    const originalButtonText = submitButton.innerHTML;

    // Show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Removing...';

    // Send remove request
    fetch('space.php?action=remove_member', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `space_id=${spaceId}&user_id=${memberToRemove}&csrf_token=<?php echo htmlspecialchars($csrf_token); ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Close modal and reload page
            bootstrap.Modal.getInstance(document.getElementById('removeMemberModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to remove member.'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    })
    .finally(() => {
        // Reset button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
        memberToRemove = null;
    });
});

// Handle invite user form submission
document.getElementById('inviteUserForm')?.addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent default form submission

    const form = this;
    const submitButton = document.getElementById('inviteSubmitBtn');
    const originalButtonText = submitButton.innerHTML;

    // Show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Sending...';

    // Prepare form data
    const formData = new FormData(form);

    // Send invitation
    fetch('space.php?action=invite', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' || data.success === true) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('inviteUserModal'));
            modal.hide();

            // Show success message
            showNotification('success', data.message || 'Invitation sent successfully!');

            // Reset form
            form.reset();
        } else {
            showNotification('error', data.message || 'Failed to send invitation');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'An error occurred. Please try again.');
    })
    .finally(() => {
        // Reset button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    });
});

// Simple notification function
function showNotification(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const iconClass = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';

    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="bi ${iconClass} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(notification);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}
</script>