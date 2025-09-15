<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <i class="bi bi-exclamation-triangle text-warning display-1 mb-4"></i>
                    <h3 class="text-muted mb-3">Invitation Error</h3>
                    <p class="text-muted mb-4"><?= htmlspecialchars($error_message) ?></p>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="invitations.php" class="btn btn-primary">
                            <i class="bi bi-envelope me-2"></i>My Invitations
                        </a>
                        <a href="dashboard_mvc.php" class="btn btn-outline-secondary">
                            <i class="bi bi-house me-2"></i>Dashboard
                        </a>
                    </div>

                    <div class="mt-4">
                        <small class="text-muted">
                            Common reasons for invalid invitations:
                        </small>
                        <ul class="list-unstyled mt-2 text-muted small">
                            <li>• Invitation has expired (valid for 7 days)</li>
                            <li>• Invitation has already been used</li>
                            <li>• Invalid or corrupted invitation link</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>