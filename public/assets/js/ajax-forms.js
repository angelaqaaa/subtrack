// AJAX Form Handling for SubTrack
document.addEventListener('DOMContentLoaded', function() {

    // Add Subscription Form AJAX
    const addSubscriptionForm = document.querySelector('#addSubscriptionModal form');
    const addSubscriptionModal = document.getElementById('addSubscriptionModal');

    if (addSubscriptionForm) {
        addSubscriptionForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            // Ensure CSRF token is included
            const csrfToken = this.querySelector('input[name="csrf_token"]');
            if (csrfToken && !formData.has('csrf_token')) {
                formData.append('csrf_token', csrfToken.value);
            }

            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;

            // Show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Adding...';

            // Clear previous error messages
            clearFormErrors(this);

            fetch('/routes/dashboard.php?action=add', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    return data;
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', text);
                    throw new Error('Invalid JSON response from server');
                }
            })
            .then(data => {
                if (data.status === 'success') {
                    // Success - add new row to table
                    const tableBody = document.querySelector('#subscriptions-table tbody');
                    const emptyState = tableBody.querySelector('tr td[colspan]');

                    if (emptyState) {
                        // Remove empty state message
                        emptyState.parentElement.remove();
                    }

                    // Add new row with animation
                    const newRow = document.createElement('tr');
                    newRow.innerHTML = data.subscription_html;
                    newRow.style.opacity = '0';
                    newRow.style.transform = 'translateY(-20px)';
                    tableBody.insertBefore(newRow, tableBody.firstChild);

                    // Animate in
                    setTimeout(() => {
                        newRow.style.transition = 'all 0.3s ease';
                        newRow.style.opacity = '1';
                        newRow.style.transform = 'translateY(0)';
                    }, 10);

                    // Update summary cards
                    updateSummaryCards(data.summary);

                    // Update category chart with new data
                    updateCategoryChart(data.category_totals);

                    // Show success message
                    showAlert('success', 'Subscription added successfully!');

                    // Reset form and close modal
                    addSubscriptionForm.reset();
                    bootstrap.Modal.getInstance(addSubscriptionModal).hide();

                } else {
                    // Validation errors
                    displayFormErrors(addSubscriptionForm, data.errors);
                    showAlert('danger', 'Please fix the errors and try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                console.error('Error details:', error.message);
                showAlert('danger', 'An error occurred. Please try again. Check console for details.');
            })
            .finally(() => {
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            });
        });
    }

    // Delete Subscription AJAX
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-subscription-btn')) {
            e.preventDefault();

            const button = e.target.closest('.delete-subscription-btn');
            const subscriptionId = button.dataset.id;
            const row = button.closest('tr');
            const serviceName = row.querySelector('td:first-child strong').textContent;

            // Confirm deletion
            if (!confirm(`Are you sure you want to delete "${serviceName}"?`)) {
                return;
            }

            // Show loading state
            button.disabled = true;
            button.innerHTML = '<i class="bi bi-hourglass-split"></i>';

            fetch(`/routes/dashboard.php?action=delete&id=${subscriptionId}`, {
                method: 'GET'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Animate row removal
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-100%)';

                    setTimeout(() => {
                        row.remove();

                        // Check if table is now empty
                        const tableBody = document.querySelector('#subscriptions-table tbody');
                        if (tableBody.children.length === 0) {
                            const emptyRow = document.createElement('tr');
                            emptyRow.innerHTML = `
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                    No subscriptions found. Add your first subscription to get started!
                                </td>
                            `;
                            tableBody.appendChild(emptyRow);
                        }

                        // Update summary cards
                        updateSummaryCards(data.summary);

                        // Update category chart
                        updateCategoryChart(data.category_totals);

                        // Show success message
                        showAlert('success', 'Subscription deleted successfully!');
                    }, 300);

                } else {
                    showAlert('danger', data.message || 'Failed to delete subscription.');
                    // Reset button state
                    button.disabled = false;
                    button.innerHTML = '<i class="bi bi-trash"></i>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred. Please try again.');
                // Reset button state
                button.disabled = false;
                button.innerHTML = '<i class="bi bi-trash"></i>';
            });
        }
    });
});

// Utility Functions
function clearFormErrors(form) {
    // Remove is-invalid class from all inputs
    form.querySelectorAll('.is-invalid').forEach(input => {
        input.classList.remove('is-invalid');
    });

    // Clear all error messages
    form.querySelectorAll('.invalid-feedback').forEach(error => {
        error.textContent = '';
    });
}

function displayFormErrors(form, errors) {
    Object.keys(errors).forEach(fieldName => {
        if (errors[fieldName]) {
            const field = form.querySelector(`[name="${fieldName}"]`);
            const feedback = form.querySelector(`[name="${fieldName}"] + .invalid-feedback`);

            if (field) {
                field.classList.add('is-invalid');
            }

            if (feedback) {
                feedback.textContent = errors[fieldName];
            }
        }
    });
}

function updateSummaryCards(summary) {
    // Update monthly cost (primary card)
    const monthlyCard = document.querySelector('.card.bg-primary .card-title');
    if (monthlyCard && summary.monthly_cost !== undefined) {
        animateCounterUpdate(monthlyCard, summary.monthly_cost, '$');
    }

    // Update annual cost (success card)
    const annualCard = document.querySelector('.card.bg-success .card-title');
    if (annualCard && summary.annual_cost !== undefined) {
        animateCounterUpdate(annualCard, summary.annual_cost, '$');
    }

    // Update subscription count (info card)
    const countCard = document.querySelector('.card.bg-info .card-title');
    if (countCard && summary.subscription_count !== undefined) {
        animateCounterUpdate(countCard, summary.subscription_count, '');
    }
}

function animateCounterUpdate(element, newValue, prefix = '') {
    const currentText = element.textContent;
    const currentValue = parseFloat(currentText.replace(/[^0-9.]/g, '')) || 0;
    const targetValue = parseFloat(newValue);

    if (currentValue === targetValue) return;

    const duration = 800;
    const startTime = performance.now();

    function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        const current = currentValue + (targetValue - currentValue) * easeOutCubic(progress);

        if (prefix === '$') {
            element.textContent = prefix + current.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        } else {
            element.textContent = prefix + Math.round(current).toLocaleString();
        }

        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        }
    }

    requestAnimationFrame(updateCounter);
}

function easeOutCubic(t) {
    return 1 - Math.pow(1 - t, 3);
}

function updateCategoryChart(categoryData) {
    if (typeof Chart === 'undefined') return;

    const categoryCanvas = document.getElementById('categoryChart');
    if (!categoryCanvas) return;

    // Destroy existing chart if it exists
    if (window.categoryChart) {
        window.categoryChart.destroy();
    }

    // If no data, hide chart
    if (!categoryData || Object.keys(categoryData).length === 0) {
        categoryCanvas.style.display = 'none';
        return;
    }

    // Show chart and create new one
    categoryCanvas.style.display = 'block';

    const labels = Object.keys(categoryData);
    const data = Object.values(categoryData);

    const colors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
    ];

    const ctx = categoryCanvas.getContext('2d');

    window.categoryChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: $${value.toFixed(2)} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '50%'
        }
    });
}

function showAlert(type, message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.dynamic-alert');
    existingAlerts.forEach(alert => alert.remove());

    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show dynamic-alert`;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.minWidth = '300px';
    alertDiv.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alertDiv);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }
    }, 5000);
}