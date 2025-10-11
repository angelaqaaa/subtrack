<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white text-center py-4">
                    <i class="bi bi-envelope-open display-4 mb-3"></i>
                    <h3 class="mb-0">Space Invitation</h3>
                </div>
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h4><?= htmlspecialchars($invitation['inviter_username']) ?> invited you to join</h4>
                        <h2 class="text-primary"><?= htmlspecialchars($invitation['space_name']) ?></h2>

                        <?php if ($invitation['space_description']): ?>
                            <p class="text-muted mt-3">
                                <?= htmlspecialchars($invitation['space_description']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Invitation Details -->
                    <div class="invitation-details bg-light p-4 rounded mb-4">
                        <div class="row text-center">
                            <div class="col-4">
                                <i class="bi bi-shield-check text-primary fs-3"></i>
                                <h6 class="mt-2">Role</h6>
                                <span class="badge bg-<?= $invitation['role'] === 'admin' ? 'success' : 'info' ?> fs-6">
                                    <?= ucfirst($invitation['role']) ?>
                                </span>
                            </div>
                            <div class="col-4">
                                <i class="bi bi-calendar text-primary fs-3"></i>
                                <h6 class="mt-2">Invited</h6>
                                <small class="text-muted">
                                    <?= date('M j, Y', strtotime($invitation['invited_at'])) ?>
                                </small>
                            </div>
                            <div class="col-4">
                                <i class="bi bi-clock text-warning fs-3"></i>
                                <h6 class="mt-2">Expires</h6>
                                <small class="text-muted">
                                    <?= date('M j', strtotime($invitation['expires_at'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions Info -->
                    <div class="permissions-info mb-4">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="bi bi-info-circle text-info me-2"></i>
                            As a <?= ucfirst($invitation['role']) ?>, you will be able to:
                        </h6>

                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        <small>View space subscriptions</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        <small>See spending analytics</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        <small>Access activity history</small>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="bi bi-<?= $invitation['role'] === 'admin' ? 'check-circle text-success' : 'x-circle text-muted' ?> me-2"></i>
                                        <small>Add subscriptions</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-<?= $invitation['role'] === 'admin' ? 'check-circle text-success' : 'x-circle text-muted' ?> me-2"></i>
                                        <small>Invite other users</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-<?= $invitation['role'] === 'admin' ? 'check-circle text-success' : 'x-circle text-muted' ?> me-2"></i>
                                        <small>Manage space settings</small>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <button class="btn btn-success btn-lg px-4 me-md-2" id="acceptInvitation">
                            <i class="bi bi-check-circle me-2"></i>
                            Accept Invitation
                        </button>
                        <button class="btn btn-outline-danger btn-lg px-4" id="declineInvitation">
                            <i class="bi bi-x-circle me-2"></i>
                            Decline
                        </button>
                    </div>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="bi bi-shield-check me-1"></i>
                            This invitation is secure and can only be used once.
                        </small>
                    </div>
                </div>
            </div>

            <!-- User Info Card -->
            <div class="text-center mt-4">
                <small class="text-muted">
                    Logged in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                    • <a href="/routes/auth.php?action=logout">Not you?</a>
                </small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const acceptBtn = document.getElementById('acceptInvitation');
    const declineBtn = document.getElementById('declineInvitation');
    const token = '<?= htmlspecialchars($token) ?>';

    acceptBtn.addEventListener('click', function() {
        handleResponse('accept');
    });

    declineBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to decline this invitation?')) {
            handleResponse('decline');
        }
    });

    function handleResponse(action) {
        // Disable buttons and show loading
        acceptBtn.disabled = true;
        declineBtn.disabled = true;

        if (action === 'accept') {
            acceptBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Accepting...';
        } else {
            declineBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Declining...';
        }

        const formData = new FormData();
        formData.append('token', token);
        formData.append('action', action);
        formData.append('csrf_token', '<?= $csrf_token ?>');

        fetch('/routes/invitations.php?action=process', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const card = document.querySelector('.card');
                card.innerHTML = `
                    <div class="card-body text-center py-5">
                        <i class="bi bi-${action === 'accept' ? 'check-circle text-success' : 'x-circle text-danger'} display-1 mb-4"></i>
                        <h3 class="text-${action === 'accept' ? 'success' : 'muted'}">${data.message}</h3>
                        ${action === 'accept' ? `
                            <p class="text-muted mb-4">You are now a member of the space!</p>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <a href="${data.redirect_url}" class="btn btn-primary btn-lg">
                                    <i class="bi bi-arrow-right me-2"></i>Go to Space
                                </a>
                                <a href="/routes/dashboard.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="bi bi-house me-2"></i>Dashboard
                                </a>
                            </div>
                        ` : `
                            <p class="text-muted mb-4">The invitation has been declined.</p>
                            <a href="/routes/dashboard.php" class="btn btn-primary">
                                <i class="bi bi-house me-2"></i>Back to Dashboard
                            </a>
                        `}
                    </div>
                `;

                // Auto-redirect for acceptance after 3 seconds
                if (action === 'accept' && data.redirect_url) {
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 3000);
                }
            } else {
                showAlert(data.message, 'danger');

                // Re-enable buttons
                acceptBtn.disabled = false;
                declineBtn.disabled = false;
                acceptBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Accept Invitation';
                declineBtn.innerHTML = '<i class="bi bi-x-circle me-2"></i>Decline';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred. Please try again.', 'danger');

            // Re-enable buttons
            acceptBtn.disabled = false;
            declineBtn.disabled = false;
            acceptBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Accept Invitation';
            declineBtn.innerHTML = '<i class="bi bi-x-circle me-2"></i>Decline';
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

    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) alert.remove();
    }, 5000);
}
</script>

<style>
.card {
    border: none;
    border-radius: 15px;
}

.permissions-info {
    font-size: 0.9rem;
}

.btn-lg {
    border-radius: 10px;
}

.spinner-border-sm {
    width: 0.875rem;
    height: 0.875rem;
}
</style>