<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 mb-1">💡 Financial Insights</h1>
            <p class="text-muted">Personalized recommendations and educational content</p>
        </div>
        <div>
            <a href="insights.php?action=education" class="btn btn-outline-primary">
                <i class="fas fa-graduation-cap"></i> Education Center
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Insights Panel -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-lightbulb"></i> Your Personal Insights
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($insights)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No insights available</h5>
                            <p class="text-muted">Add more subscriptions to get personalized financial insights.</p>
                            <a href="dashboard_mvc.php" class="btn btn-primary">Add Subscriptions</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($insights as $insight): ?>
                            <div class="insight-card mb-3 p-3 border rounded" data-insight-id="<?= $insight['id'] ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-2">
                                            <?php
                                            $icon = match($insight['type']) {
                                                'saving_opportunity' => 'fas fa-piggy-bank text-success',
                                                'spending_alert' => 'fas fa-exclamation-triangle text-warning',
                                                'category_analysis' => 'fas fa-chart-pie text-info',
                                                'trend_analysis' => 'fas fa-trending-up text-primary',
                                                default => 'fas fa-info-circle text-secondary'
                                            };
                                            ?>
                                            <i class="<?= $icon ?> me-2"></i>
                                            <h6 class="mb-0"><?= htmlspecialchars($insight['title']) ?></h6>
                                            <span class="badge bg-secondary ms-2">Impact: <?= $insight['impact_score'] ?>/10</span>
                                        </div>
                                        <p class="text-muted mb-2"><?= htmlspecialchars($insight['description']) ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i>
                                            <?= date('M j, Y', strtotime($insight['created_at'])) ?>
                                        </small>
                                    </div>
                                    <div class="insight-actions ms-3">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-success apply-insight"
                                                    data-insight-id="<?= $insight['id'] ?>">
                                                <i class="fas fa-check"></i> Apply
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary dismiss-insight"
                                                    data-insight-id="<?= $insight['id'] ?>">
                                                <i class="fas fa-times"></i> Dismiss
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Spending Goals -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-bullseye"></i> Spending Goals
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($spending_goals)): ?>
                        <p class="text-muted text-center mb-3">No spending goals set</p>
                    <?php else: ?>
                        <?php foreach ($spending_goals as $goal): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="fw-bold"><?= htmlspecialchars($goal['category']) ?></small>
                                    <small class="text-muted">
                                        $<?= number_format($goal['current_spending'], 2) ?> / $<?= number_format($goal['monthly_limit'], 2) ?>
                                    </small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar <?= $goal['progress_percent'] > 100 ? 'bg-danger' : ($goal['progress_percent'] > 80 ? 'bg-warning' : 'bg-success') ?>"
                                         style="width: <?= min(100, $goal['progress_percent']) ?>%"></div>
                                </div>
                                <small class="text-muted"><?= $goal['progress_percent'] ?>% of goal</small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <button class="btn btn-sm btn-outline-success w-100" data-bs-toggle="modal" data-bs-target="#createGoalModal">
                        <i class="fas fa-plus"></i> Set New Goal
                    </button>
                </div>
            </div>

            <!-- Achievements -->
            <div class="card shadow mb-4">
                <div class="card-header bg-warning text-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-trophy"></i> Achievements
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($achievements)): ?>
                        <p class="text-muted text-center">No achievements yet</p>
                    <?php else: ?>
                        <?php foreach (array_slice($achievements, 0, 3) as $achievement): ?>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-medal text-warning me-2"></i>
                                <div>
                                    <small class="fw-bold d-block"><?= htmlspecialchars($achievement['title']) ?></small>
                                    <small class="text-muted"><?= htmlspecialchars($achievement['description']) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($achievements) > 3): ?>
                            <small class="text-muted">+<?= count($achievements) - 3 ?> more achievements</small>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Featured Education -->
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-graduation-cap"></i> Featured Education
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($featured_content)): ?>
                        <p class="text-muted text-center">No content available</p>
                    <?php else: ?>
                        <?php foreach ($featured_content as $content): ?>
                            <div class="mb-3">
                                <a href="insights.php?action=content&slug=<?= $content['slug'] ?>"
                                   class="text-decoration-none">
                                    <h6 class="text-primary mb-1"><?= htmlspecialchars($content['title']) ?></h6>
                                </a>
                                <p class="text-muted small mb-1"><?= htmlspecialchars($content['summary']) ?></p>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> <?= $content['estimated_read_time'] ?> min
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-eye"></i> <?= $content['view_count'] ?> views
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <a href="insights.php?action=education" class="btn btn-sm btn-outline-info w-100">
                            View All Content
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Goal Modal -->
<div class="modal fade" id="createGoalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Set Spending Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createGoalForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="goalCategory" class="form-label">Category</label>
                        <select class="form-select" id="goalCategory" name="category" required>
                            <option value="">Select category...</option>
                            <option value="Entertainment">Entertainment</option>
                            <option value="Productivity">Productivity</option>
                            <option value="Education">Education</option>
                            <option value="Health & Fitness">Health & Fitness</option>
                            <option value="Business">Business</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="monthlyLimit" class="form-label">Monthly Limit</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="monthlyLimit"
                                   name="monthly_limit" min="0" step="0.01" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Goal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle insight actions
    document.querySelectorAll('.apply-insight, .dismiss-insight').forEach(button => {
        button.addEventListener('click', function() {
            const insightId = this.dataset.insightId;
            const action = this.classList.contains('apply-insight') ? 'apply' : 'dismiss';
            const insightCard = document.querySelector(`[data-insight-id="${insightId}"]`);

            const formData = new FormData();
            formData.append('insight_id', insightId);
            formData.append('action', action);
            formData.append('csrf_token', '<?= $csrf_token ?>');

            fetch('insights.php?action=insight_action', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    insightCard.style.opacity = '0.5';
                    insightCard.style.transition = 'opacity 0.3s';
                    setTimeout(() => insightCard.remove(), 300);

                    // Show success message
                    showAlert(data.message, 'success');
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            });
        });
    });

    // Handle goal creation
    document.getElementById('createGoalForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('insights.php?action=create_goal', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showAlert(data.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('createGoalModal')).hide();
                this.reset();
                location.reload(); // Reload to show new goal
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred. Please try again.', 'danger');
        });
    });
});

function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    // Insert at top of container
    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) alert.remove();
    }, 5000);
}
</script>