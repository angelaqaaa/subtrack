<div class="container py-4">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="/routes/insights.php?action=dashboard">Insights</a>
            </li>
            <li class="breadcrumb-item">
                <a href="/routes/insights.php?action=education">Education Center</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= htmlspecialchars($content['title']) ?>
            </li>
        </ol>
    </nav>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8 mb-4">
            <article class="card shadow">
                <!-- Article Header -->
                <div class="card-header bg-white border-bottom">
                    <div class="row align-items-center">
                        <div class="col">
                            <h1 class="h4 mb-2"><?= htmlspecialchars($content['title']) ?></h1>
                            <div class="article-meta text-muted">
                                <span class="badge bg-<?= match($content['category']) {
                                    'budgeting' => 'success',
                                    'saving_tips' => 'info',
                                    'subscription_management' => 'primary',
                                    'financial_planning' => 'warning',
                                    default => 'secondary'
                                } ?> me-2">
                                    <?= ucfirst(str_replace('_', ' ', $content['category'])) ?>
                                </span>
                                <span class="badge bg-light text-dark me-2">
                                    <?= ucfirst($content['difficulty_level']) ?>
                                </span>
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i>
                                    <?= $content['estimated_read_time'] ?> min read
                                    •
                                    <i class="fas fa-eye"></i>
                                    <?= number_format($content['view_count']) ?> views
                                </small>
                            </div>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-success" id="markCompleted"
                                    data-content-id="<?= $content['id'] ?>">
                                <i class="fas fa-check"></i> Mark as Complete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Article Content -->
                <div class="card-body">
                    <!-- Summary -->
                    <?php if ($content['summary']): ?>
                        <div class="alert alert-info" role="alert">
                            <h6 class="alert-heading">
                                <i class="fas fa-info-circle"></i> Article Summary
                            </h6>
                            <p class="mb-0"><?= htmlspecialchars($content['summary']) ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Main Content -->
                    <div class="article-content">
                        <?= $content['content'] ?>
                    </div>

                    <!-- Tags -->
                    <?php if ($content['tags']): ?>
                        <div class="mt-4 pt-3 border-top">
                            <h6 class="text-muted mb-2">Tags:</h6>
                            <?php
                            $tags = json_decode($content['tags'], true) ?? [];
                            foreach ($tags as $tag):
                            ?>
                                <span class="badge bg-light text-dark me-1">#<?= htmlspecialchars($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Article Actions -->
                <div class="card-footer bg-light">
                    <div class="row align-items-center">
                        <div class="col">
                            <small class="text-muted">
                                Last updated: <?= date('F j, Y', strtotime($content['updated_at'])) ?>
                            </small>
                        </div>
                        <div class="col-auto">
                            <div class="btn-group" role="group">
                                <button class="btn btn-<?= ($user_progress['status'] ?? '') === 'bookmarked' ? 'primary' : 'outline-primary' ?> btn-sm" id="bookmarkContent"
                                        data-content-id="<?= $content['id'] ?>">
                                    <i class="fas fa-bookmark"></i> <?= ($user_progress['status'] ?? '') === 'bookmarked' ? 'Bookmarked' : 'Bookmark' ?>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Completion Success Message -->
            <div id="completionMessage" class="alert alert-success mt-3 d-none">
                <div class="d-flex align-items-center">
                    <i class="fas fa-trophy fa-2x text-warning me-3"></i>
                    <div>
                        <h6 class="mb-1">Great job! Article completed!</h6>
                        <p class="mb-0">You've successfully completed this educational content. Keep learning to unlock more achievements!</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Progress Card -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-line"></i> Your Learning Progress
                    </h6>
                </div>
                <div class="card-body text-center">
                    <div class="reading-progress mb-3">
                        <div class="progress-ring" data-progress="0">
                            <svg class="progress-ring__svg" width="80" height="80">
                                <circle class="progress-ring__circle-bg" cx="40" cy="40" r="30"></circle>
                                <circle class="progress-ring__circle" cx="40" cy="40" r="30"></circle>
                            </svg>
                            <div class="progress-ring__percent">0%</div>
                        </div>
                    </div>
                    <p class="text-muted mb-0">Reading Progress</p>
                    <small class="text-muted">Scroll to advance progress</small>
                </div>
            </div>

            <!-- Related Content -->
            <?php if (!empty($related_content)): ?>
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-book"></i> Related Content
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($related_content as $related): ?>
                            <?php if ($related['id'] != $content['id']): ?>
                                <div class="p-3 border-bottom">
                                    <a href="/routes/insights.php?action=content&slug=<?= $related['slug'] ?>"
                                       class="text-decoration-none">
                                        <h6 class="text-primary mb-1"><?= htmlspecialchars($related['title']) ?></h6>
                                    </a>
                                    <p class="text-muted small mb-1"><?= htmlspecialchars($related['summary']) ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> <?= $related['estimated_read_time'] ?> min
                                    </small>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Learning Tips -->
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb text-warning"></i> Quick Tips
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <small>Take notes while reading</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <small>Apply concepts to your own finances</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <small>Return to review key concepts</small>
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <small>Complete the article to earn achievements</small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.article-content {
    line-height: 1.7;
    font-size: 1.1rem;
}

.article-content h3 {
    color: #495057;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.article-content ul {
    padding-left: 1.5rem;
}

.progress-ring {
    position: relative;
    display: inline-block;
}

.progress-ring__svg {
    transform: rotate(-90deg);
}

.progress-ring__circle-bg {
    fill: transparent;
    stroke: #e9ecef;
    stroke-width: 4;
}

.progress-ring__circle {
    fill: transparent;
    stroke: #28a745;
    stroke-width: 4;
    stroke-linecap: round;
    stroke-dasharray: 188.5; /* 2 * PI * 30 */
    stroke-dashoffset: 188.5;
    transition: stroke-dashoffset 0.3s;
}

.progress-ring__percent {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-weight: bold;
    font-size: 14px;
    color: #28a745;
}

@media print {
    .card-header, .card-footer, .col-lg-4, nav, .btn {
        display: none !important;
    }

    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let readingProgress = 0;
    const progressRing = document.querySelector('.progress-ring');
    const progressCircle = document.querySelector('.progress-ring__circle');
    const progressPercent = document.querySelector('.progress-ring__percent');
    const circumference = 2 * Math.PI * 30; // 30 is the radius

    // Update reading progress based on scroll
    function updateReadingProgress() {
        const articleContent = document.querySelector('.article-content');
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight - windowHeight;
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        const progress = Math.min(100, Math.max(0, (scrollTop / documentHeight) * 100));

        readingProgress = Math.round(progress);
        progressPercent.textContent = readingProgress + '%';

        const offset = circumference - (progress / 100) * circumference;
        progressCircle.style.strokeDashoffset = offset;
    }

    // Track scroll for reading progress
    window.addEventListener('scroll', updateReadingProgress);
    updateReadingProgress(); // Initial calculation

    // Mark content as completed
    document.getElementById('markCompleted').addEventListener('click', function() {
        const contentId = this.dataset.contentId;
        const button = this;

        const formData = new FormData();
        formData.append('content_id', contentId);
        formData.append('csrf_token', '<?= $csrf_token ?>');

        fetch('/routes/insights.php?action=mark_completed', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Check if response is ok before parsing
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text(); // Get as text first to debug
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.status === 'success') {
                    button.classList.remove('btn-success');
                    button.classList.add('btn-secondary');
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-check"></i> Completed';

                    // Show completion message
                    document.getElementById('completionMessage').classList.remove('d-none');

                    // Update progress to 100%
                    readingProgress = 100;
                    progressPercent.textContent = '100%';
                    progressCircle.style.strokeDashoffset = 0;

                    // Scroll to completion message
                    document.getElementById('completionMessage').scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                } else {
                    showAlert('danger', data.message);
                }
            } catch (e) {
                console.error('JSON Parse Error:', e);
                console.error('Response text:', text);
                showAlert('danger', 'Server returned invalid response');
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            showAlert('danger', 'An error occurred. Please try again.');
        });
    });

    // Bookmark functionality
    document.getElementById('bookmarkContent').addEventListener('click', function() {
        const button = this;
        const contentId = button.dataset.contentId;

        const formData = new FormData();
        formData.append('content_id', contentId);
        formData.append('csrf_token', '<?= $csrf_token ?>');

        fetch('/routes/insights.php?action=toggle_bookmark', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.is_bookmarked) {
                    button.classList.remove('btn-outline-primary');
                    button.classList.add('btn-primary');
                    button.innerHTML = '<i class="fas fa-bookmark"></i> Bookmarked';
                } else {
                    button.classList.remove('btn-primary');
                    button.classList.add('btn-outline-primary');
                    button.innerHTML = '<i class="fas fa-bookmark"></i> Bookmark';
                }
                showAlert('success', data.message);
            } else {
                showAlert('danger', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'An error occurred. Please try again.');
        });
    });
});
</script>