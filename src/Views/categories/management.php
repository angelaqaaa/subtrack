<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 mb-1">📁 Manage Categories</h1>
            <p class="text-muted">Create and organize your subscription categories</p>
        </div>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                <i class="bi bi-plus-circle"></i> Add Category
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Categories List -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Your Categories</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($categories)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-folder2-open display-4 text-muted"></i>
                            <h5 class="mt-3 text-muted">No custom categories yet</h5>
                            <p class="text-muted">Create your first category to organize subscriptions</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                                <i class="bi bi-plus-circle"></i> Create Category
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="categoriesTable">
                                <thead>
                                    <tr>
                                        <th width="60">Preview</th>
                                        <th>Name</th>
                                        <th>Usage</th>
                                        <th>Monthly Total</th>
                                        <th width="120">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <?php
                                        // Find usage stats for this category
                                        $usage = null;
                                        foreach ($usage_stats as $stat) {
                                            if ($stat['name'] === $category['name']) {
                                                $usage = $stat;
                                                break;
                                            }
                                        }
                                        ?>
                                        <tr data-category-id="<?= $category['id'] ?>">
                                            <td>
                                                <div class="category-preview d-flex align-items-center">
                                                    <i class="<?= htmlspecialchars($category['icon']) ?> me-2"
                                                       style="color: <?= htmlspecialchars($category['color']) ?>"></i>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($category['name']) ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?= $usage ? $usage['subscription_count'] : 0 ?> subscriptions
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($usage && $usage['monthly_total'] > 0): ?>
                                                    <span class="fw-bold">
                                                        $<?= number_format($usage['monthly_total'], 2) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Not used</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary edit-category"
                                                            data-category-id="<?= $category['id'] ?>"
                                                            data-name="<?= htmlspecialchars($category['name']) ?>"
                                                            data-color="<?= htmlspecialchars($category['color']) ?>"
                                                            data-icon="<?= htmlspecialchars($category['icon']) ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-category"
                                                            data-category-id="<?= $category['id'] ?>"
                                                            data-name="<?= htmlspecialchars($category['name']) ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
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

        <!-- Usage Statistics -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="card-title mb-0">Category Statistics</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($usage_stats)): ?>
                        <p class="text-muted text-center">No usage data available</p>
                    <?php else: ?>
                        <?php foreach ($usage_stats as $stat): ?>
                            <?php if ($stat['subscription_count'] > 0): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-sm fw-bold">
                                            <i class="fas fa-circle me-1" style="color: <?= htmlspecialchars($stat['color']) ?>"></i>
                                            <?= htmlspecialchars($stat['name']) ?>
                                        </span>
                                        <span class="text-sm">$<?= number_format($stat['monthly_total'], 2) ?></span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" role="progressbar"
                                             style="width: <?= min(100, ($stat['monthly_total'] / max(1, array_sum(array_column($usage_stats, 'monthly_total')))) * 100) ?>%;
                                                    background-color: <?= htmlspecialchars($stat['color']) ?>"></div>
                                    </div>
                                    <small class="text-muted"><?= $stat['subscription_count'] ?> subscriptions</small>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tips -->
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightbulb text-warning"></i> Category Tips
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-1"></i>
                            Use specific names (e.g., "Work Tools" vs "Business")
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-1"></i>
                            Choose distinct colors for easy identification
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-1"></i>
                            Categories in use cannot be deleted
                        </li>
                        <li class="mb-0">
                            <i class="bi bi-check-circle text-success me-1"></i>
                            Icons support FontAwesome classes
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Category Modal -->
<div class="modal fade" id="createCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createCategoryForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="categoryName" name="name"
                               placeholder="e.g., Work Tools" required maxlength="100">
                        <div class="invalid-feedback" id="nameError"></div>
                    </div>

                    <div class="mb-3">
                        <label for="categoryColor" class="form-label">Color</label>
                        <div class="d-flex align-items-center">
                            <input type="color" class="form-control form-control-color me-2"
                                   id="categoryColor" name="color" value="#6c757d">
                            <span class="text-muted small">Choose a distinctive color</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="categoryIcon" class="form-label">Icon (FontAwesome class)</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="categoryIcon" name="icon"
                                   value="fas fa-tag" placeholder="fas fa-briefcase">
                            <span class="input-group-text">
                                <i id="iconPreview" class="fas fa-tag"></i>
                            </span>
                        </div>
                        <div class="form-text">
                            Examples: fas fa-briefcase, fas fa-gamepad, fas fa-heart, fas fa-graduation-cap
                        </div>
                    </div>

                    <div class="category-preview-section">
                        <label class="form-label">Preview:</label>
                        <div class="preview-box p-3 border rounded">
                            <i id="previewIcon" class="fas fa-tag me-2" style="color: #6c757d;"></i>
                            <span id="previewName">Category Name</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCategoryForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" id="editCategoryId" name="category_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editCategoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="editCategoryName" name="name"
                               required maxlength="100">
                        <div class="invalid-feedback" id="editNameError"></div>
                    </div>

                    <div class="mb-3">
                        <label for="editCategoryColor" class="form-label">Color</label>
                        <div class="d-flex align-items-center">
                            <input type="color" class="form-control form-control-color me-2"
                                   id="editCategoryColor" name="color">
                            <span class="text-muted small">Choose a distinctive color</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editCategoryIcon" class="form-label">Icon (FontAwesome class)</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="editCategoryIcon" name="icon">
                            <span class="input-group-text">
                                <i id="editIconPreview" class="fas fa-tag"></i>
                            </span>
                        </div>
                    </div>

                    <div class="category-preview-section">
                        <label class="form-label">Preview:</label>
                        <div class="preview-box p-3 border rounded">
                            <i id="editPreviewIcon" class="fas fa-tag me-2"></i>
                            <span id="editPreviewName">Category Name</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Real-time preview updates for create modal
    const categoryName = document.getElementById('categoryName');
    const categoryColor = document.getElementById('categoryColor');
    const categoryIcon = document.getElementById('categoryIcon');
    const previewName = document.getElementById('previewName');
    const previewIcon = document.getElementById('previewIcon');
    const iconPreview = document.getElementById('iconPreview');

    function updateCreatePreview() {
        previewName.textContent = categoryName.value || 'Category Name';
        const color = categoryColor.value;
        const iconClass = categoryIcon.value || 'fas fa-tag';

        previewIcon.className = iconClass;
        previewIcon.style.color = color;
        iconPreview.className = iconClass;
    }

    categoryName.addEventListener('input', updateCreatePreview);
    categoryColor.addEventListener('input', updateCreatePreview);
    categoryIcon.addEventListener('input', updateCreatePreview);

    // Similar for edit modal
    const editCategoryName = document.getElementById('editCategoryName');
    const editCategoryColor = document.getElementById('editCategoryColor');
    const editCategoryIcon = document.getElementById('editCategoryIcon');
    const editPreviewName = document.getElementById('editPreviewName');
    const editPreviewIcon = document.getElementById('editPreviewIcon');
    const editIconPreview = document.getElementById('editIconPreview');

    function updateEditPreview() {
        editPreviewName.textContent = editCategoryName.value || 'Category Name';
        const color = editCategoryColor.value;
        const iconClass = editCategoryIcon.value || 'fas fa-tag';

        editPreviewIcon.className = iconClass;
        editPreviewIcon.style.color = color;
        editIconPreview.className = iconClass;
    }

    editCategoryName.addEventListener('input', updateEditPreview);
    editCategoryColor.addEventListener('input', updateEditPreview);
    editCategoryIcon.addEventListener('input', updateEditPreview);

    // Handle create category form
    document.getElementById('createCategoryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('/routes/categories.php?action=create', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showAlert(data.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('createCategoryModal')).hide();
                this.reset();
                location.reload(); // Reload to show new category
            } else if (data.status === 'validation_error') {
                // Show validation errors
                for (const [field, message] of Object.entries(data.errors)) {
                    const errorDiv = document.getElementById(field + 'Error');
                    if (errorDiv) {
                        errorDiv.textContent = message;
                        errorDiv.parentElement.classList.add('was-validated');
                    }
                }
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred. Please try again.', 'danger');
        });
    });

    // Handle edit category buttons
    document.querySelectorAll('.edit-category').forEach(button => {
        button.addEventListener('click', function() {
            const categoryId = this.dataset.categoryId;
            const name = this.dataset.name;
            const color = this.dataset.color;
            const icon = this.dataset.icon;

            document.getElementById('editCategoryId').value = categoryId;
            document.getElementById('editCategoryName').value = name;
            document.getElementById('editCategoryColor').value = color;
            document.getElementById('editCategoryIcon').value = icon;

            updateEditPreview();

            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        });
    });

    // Handle edit category form
    document.getElementById('editCategoryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('/routes/categories.php?action=update', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showAlert(data.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('editCategoryModal')).hide();
                location.reload(); // Reload to show updated category
            } else if (data.status === 'validation_error') {
                // Show validation errors
                for (const [field, message] of Object.entries(data.errors)) {
                    const errorDiv = document.getElementById('edit' + field.charAt(0).toUpperCase() + field.slice(1) + 'Error');
                    if (errorDiv) {
                        errorDiv.textContent = message;
                        errorDiv.parentElement.classList.add('was-validated');
                    }
                }
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred. Please try again.', 'danger');
        });
    });

    // Handle delete category buttons
    document.querySelectorAll('.delete-category').forEach(button => {
        button.addEventListener('click', function() {
            const categoryId = this.dataset.categoryId;
            const categoryName = this.dataset.name;

            if (confirm(`Are you sure you want to delete the "${categoryName}" category?\n\nThis action cannot be undone.`)) {
                fetch(`/routes/categories.php?action=delete&id=${categoryId}`, {
                    method: 'GET'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        // Remove the row from table
                        document.querySelector(`tr[data-category-id="${categoryId}"]`).remove();
                    } else {
                        showAlert(data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred. Please try again.', 'danger');
                });
            }
        });
    });
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

    // Auto-remove after 3 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) alert.remove();
    }, 3000);
}
</script>

<style>
.category-preview .fa,
.category-preview .fas,
.category-preview .far {
    font-size: 1.2em;
}

.preview-box {
    background: #f8f9fa;
    font-size: 1.1em;
}

.progress {
    background-color: rgba(0,0,0,0.1);
}

.form-control-color {
    width: 3rem;
    height: calc(2.25rem + 2px);
}
</style>