<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 mb-1">📚 Education Center</h1>
            <p class="text-muted">Learn about financial management and subscription optimization</p>
        </div>
        <div>
            <a href="/routes/insights.php?action=dashboard" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Insights
            </a>
        </div>
    </div>

    <!-- Category Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap gap-2">
                <a href="/routes/insights.php?action=education"
                   class="btn <?= empty($selected_category) ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm">
                    All Content
                </a>
                <?php foreach ($categories as $key => $label): ?>
                    <a href="/routes/insights.php?action=education&category=<?= $key ?>"
                       class="btn <?= $selected_category === $key ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm">
                        <?= htmlspecialchars($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="row">
        <?php if (empty($content)): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No content available</h5>
                        <p class="text-muted">
                            <?= $selected_category ? "No content found in the {$categories[$selected_category]} category." : "No educational content is currently available." ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($content as $item): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm education-card">
                        <?php if ($item['is_featured']): ?>
                            <div class="card-header bg-warning text-white">
                                <small><i class="fas fa-star"></i> Featured</small>
                            </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <span class="badge bg-<?= match($item['category']) {
                                    'budgeting' => 'success',
                                    'saving_tips' => 'info',
                                    'subscription_management' => 'primary',
                                    'financial_planning' => 'warning',
                                    default => 'secondary'
                                } ?> mb-2">
                                    <?= htmlspecialchars($categories[$item['category']] ?? $item['category']) ?>
                                </span>

                                <span class="badge bg-light text-dark ms-1">
                                    <?= ucfirst($item['difficulty_level']) ?>
                                </span>

                                <?php if (isset($progress_map[$item['id']])): ?>
                                    <?php $status = $progress_map[$item['id']]['status']; ?>
                                    <?php if ($status === 'completed'): ?>
                                        <span class="badge bg-success ms-1" title="Completed">
                                            <i class="bi bi-check-circle-fill"></i> Completed
                                        </span>
                                    <?php elseif ($status === 'bookmarked'): ?>
                                        <span class="badge bg-warning ms-1" title="Bookmarked">
                                            <i class="bi bi-bookmark-fill"></i> Bookmarked
                                        </span>
                                    <?php elseif ($status === 'started'): ?>
                                        <span class="badge bg-info ms-1" title="In Progress">
                                            <i class="bi bi-hourglass-split"></i> In Progress
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <h5 class="card-title">
                                <a href="/routes/insights.php?action=content&slug=<?= $item['slug'] ?>"
                                   class="text-decoration-none">
                                    <?= htmlspecialchars($item['title']) ?>
                                </a>
                            </h5>

                            <p class="card-text text-muted flex-grow-1">
                                <?= htmlspecialchars($item['summary']) ?>
                            </p>

                            <div class="card-meta text-muted small">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-clock"></i>
                                        <?= $item['estimated_read_time'] ?> min read
                                    </div>
                                    <div>
                                        <i class="fas fa-eye"></i>
                                        <?= number_format($item['view_count']) ?> views
                                    </div>
                                </div>
                                <small class="text-muted">
                                    Updated <?= date('M j, Y', strtotime($item['updated_at'])) ?>
                                </small>
                            </div>
                        </div>

                        <div class="card-footer bg-transparent">
                            <a href="/routes/insights.php?action=content&slug=<?= $item['slug'] ?>"
                               class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-book-open"></i> Read Article
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Learning Tips -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-lightbulb text-warning"></i>
                        Learning Tips
                    </h5>
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="text-primary">Start with Basics</h6>
                            <p class="small text-muted">
                                New to financial management? Start with our beginner-level content on budgeting and subscription basics.
                            </p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-primary">Apply What You Learn</h6>
                            <p class="small text-muted">
                                Use the insights in your SubTrack dashboard to implement strategies from the articles you read.
                            </p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-primary">Track Your Progress</h6>
                            <p class="small text-muted">
                                Complete articles to earn achievements and track your financial education journey.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.education-card {
    transition: transform 0.2s;
}

.education-card:hover {
    transform: translateY(-2px);
}

.card-meta {
    border-top: 1px solid #eee;
    padding-top: 0.75rem;
    margin-top: 0.75rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth hover effects
    document.querySelectorAll('.education-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.15)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.boxShadow = '';
        });
    });
});
</script>