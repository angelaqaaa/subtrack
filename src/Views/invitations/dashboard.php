<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 mb-1">📨 Space Invitations</h1>
            <p class="text-muted">Manage your space invitations</p>
        </div>
        <div>
            <a href="/routes/dashboard.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-envelope"></i> Pending Invitations
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($pending_invitations)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <h4 class="mt-3 text-muted">No pending invitations</h4>
                            <p class="text-muted">
                                You don't have any pending space invitations at the moment.<br>
                                When someone invites you to their space, it will appear here.
                            </p>
                            <a href="/routes/dashboard.php" class="btn btn-primary">
                                <i class="bi bi-house"></i> Go to Dashboard
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pending_invitations as $invitation): ?>
                            <div class="invitation-card border rounded p-4 mb-3" data-token="<?= htmlspecialchars($invitation['invitation_token']) ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-2">
                                            <i class="bi bi-people-fill text-primary me-2"></i>
                                            <?= htmlspecialchars($invitation['space_name']) ?>
                                        </h5>

                                        <?php if ($invitation['space_description']): ?>
                                            <p class="text-muted mb-2">
                                                <?= htmlspecialchars($invitation['space_description']) ?>
                                            </p>
                                        <?php endif; ?>

                                        <div class="invitation-details">
                                            <small class="text-muted">
                                                <i class="bi bi-person me-1"></i>
                                                Invited by <strong><?= htmlspecialchars($invitation['inviter_username']) ?></strong>
                                                as <span class="badge bg-<?= $invitation['role'] === 'admin' ? 'success' : 'info' ?>">
                                                    <?= ucfirst($invitation['role']) ?>
                                                </span>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar me-1"></i>
                                                Invited <?= date('M j, Y \a\t g:i A', strtotime($invitation['invited_at'])) ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                Expires <?= date('M j, Y \a\t g:i A', strtotime($invitation['expires_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="invitation-actions">
                                            <button class="btn btn-success me-2 accept-invitation"
                                                    data-token="<?= htmlspecialchars($invitation['invitation_token']) ?>">
                                                <i class="bi bi-check-circle"></i> Accept
                                            </button>
                                            <button class="btn btn-outline-danger decline-invitation"
                                                    data-token="<?= htmlspecialchars($invitation['invitation_token']) ?>">
                                                <i class="bi bi-x-circle"></i> Decline
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Role Permissions Info -->
                                <div class="mt-3 p-3 bg-light rounded">
                                    <h6 class="mb-2">
                                        <i class="bi bi-shield-check text-info me-1"></i>
                                        As a <?= ucfirst($invitation['role']) ?>, you will be able to:
                                    </h6>
                                    <ul class="list-unstyled mb-0 small">
                                        <li><i class="bi bi-check text-success me-1"></i> View space subscriptions and spending</li>
                                        <li><i class="bi bi-check text-success me-1"></i> See space activity history</li>
                                        <?php if ($invitation['role'] === 'admin'): ?>
                                            <li><i class="bi bi-check text-success me-1"></i> Add and manage subscriptions</li>
                                            <li><i class="bi bi-check text-success me-1"></i> Invite other users to the space</li>
                                            <li><i class="bi bi-check text-success me-1"></i> Manage space settings</li>
                                        <?php else: ?>
                                            <li><i class="bi bi-x text-muted me-1"></i> Add subscriptions (viewer only)</li>
                                            <li><i class="bi bi-x text-muted me-1"></i> Invite other users (viewer only)</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle accept invitation
    document.querySelectorAll('.accept-invitation').forEach(button => {
        button.addEventListener('click', function() {
            const token = this.dataset.token;
            handleInvitationResponse(token, 'accept', this);
        });
    });

    // Handle decline invitation
    document.querySelectorAll('.decline-invitation').forEach(button => {
        button.addEventListener('click', function() {
            const token = this.dataset.token;
            const spaceName = this.closest('.invitation-card').querySelector('h5').textContent.trim();

            if (confirm(`Are you sure you want to decline the invitation to "${spaceName}"?`)) {
                handleInvitationResponse(token, 'decline', this);
            }
        });
    });

    function handleInvitationResponse(token, action, buttonElement) {
        const formData = new FormData();
        formData.append('token', token);
        formData.append('action', action);
        formData.append('csrf_token', '<?= $csrf_token ?>');

        // Disable buttons
        const invitationCard = buttonElement.closest('.invitation-card');
        const buttons = invitationCard.querySelectorAll('button');
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';
        });

        fetch('/routes/invitations.php?action=process', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');

                // Remove the invitation card with animation
                invitationCard.style.opacity = '0';
                invitationCard.style.transform = 'translateY(-20px)';
                invitationCard.style.transition = 'all 0.3s ease';

                setTimeout(() => {
                    invitationCard.remove();

                    // If this was the last invitation, show empty state
                    const remainingInvitations = document.querySelectorAll('.invitation-card');
                    if (remainingInvitations.length === 0) {
                        location.reload();
                    }
                }, 300);

                // Redirect if accepted
                if (action === 'accept' && data.redirect_url) {
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 1500);
                }
            } else {
                showAlert(data.message, 'danger');

                // Re-enable buttons
                buttons.forEach(btn => {
                    btn.disabled = false;
                });

                // Reset button text
                invitationCard.querySelector('.accept-invitation').innerHTML = '<i class="bi bi-check-circle"></i> Accept';
                invitationCard.querySelector('.decline-invitation').innerHTML = '<i class="bi bi-x-circle"></i> Decline';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred. Please try again.', 'danger');

            // Re-enable buttons
            buttons.forEach(btn => {
                btn.disabled = false;
            });

            // Reset button text
            invitationCard.querySelector('.accept-invitation').innerHTML = '<i class="bi bi-check-circle"></i> Accept';
            invitationCard.querySelector('.decline-invitation').innerHTML = '<i class="bi bi-x-circle"></i> Decline';
        });
    }
});

function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show position-fixed"
             style="top: 20px; right: 20px; z-index: 1050; max-width: 350px;" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    document.body.insertAdjacentHTML('afterbegin', alertHtml);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) alert.remove();
    }, 5000);
}
</script>

<style>
.invitation-card {
    transition: all 0.2s ease;
}

.invitation-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.invitation-details {
    line-height: 1.6;
}

.invitation-actions button {
    min-width: 90px;
}

.spinner-border-sm {
    width: 0.875rem;
    height: 0.875rem;
}
</style>