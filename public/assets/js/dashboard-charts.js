document.addEventListener('DOMContentLoaded', function() {
    const chartCanvas = document.getElementById('categoryChart');
    if (!chartCanvas) return;

    const categoryData = JSON.parse(chartCanvas.dataset.categories || '{}');

    if (Object.keys(categoryData).length === 0) {
        chartCanvas.style.display = 'none';
        return;
    }

    const labels = Object.keys(categoryData);
    const data = Object.values(categoryData);

    // Generate colors for each category
    const colors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
    ];

    const ctx = chartCanvas.getContext('2d');

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
});

// Animate number counters on page load
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('.card-title');

    counters.forEach(counter => {
        const target = counter.textContent;
        const isNumber = target.match(/[\d,]+\.?\d*/);

        if (isNumber) {
            const numericValue = parseFloat(isNumber[0].replace(/,/g, ''));
            const prefix = target.includes('$') ? '$' : '';

            animateCounter(counter, 0, numericValue, 1000, prefix);
        }
    });
});

function animateCounter(element, start, end, duration, prefix = '') {
    const startTime = performance.now();
    const isDecimal = end % 1 !== 0;

    function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        const current = start + (end - start) * easeOutCubic(progress);

        if (isDecimal) {
            element.textContent = prefix + current.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        } else {
            element.textContent = prefix + Math.floor(current).toLocaleString();
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

// Historical Spending Chart for Reports Page
document.addEventListener('DOMContentLoaded', function() {
    const historicalChart = document.getElementById('historicalChart');
    if (!historicalChart) return;

    const historicalData = JSON.parse(historicalChart.dataset.historical || '{}');

    const labels = Object.keys(historicalData);
    const data = Object.values(historicalData);

    const ctx = historicalChart.getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.map(label => {
                // Parse year and month to avoid timezone issues
                const [year, month] = label.split('-');
                const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                                  'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                return `${monthNames[parseInt(month) - 1]} ${year}`;
            }),
            datasets: [{
                label: 'Monthly Spending',
                data: data,
                borderColor: '#36A2EB',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#36A2EB',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Monthly Spending: $${context.parsed.y.toFixed(2)}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toFixed(0);
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            },
            elements: {
                point: {
                    hoverRadius: 8
                }
            }
        }
    });
});

// Category Breakdown Chart for Reports Page
document.addEventListener('DOMContentLoaded', function() {
    const categoryBreakdownChart = document.getElementById('categoryBreakdownChart');
    if (!categoryBreakdownChart) return;

    const categoryData = JSON.parse(categoryBreakdownChart.dataset.categories || '{}');

    if (Object.keys(categoryData).length === 0) {
        categoryBreakdownChart.style.display = 'none';
        return;
    }

    const labels = Object.keys(categoryData);
    const data = Object.values(categoryData);

    const colors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
    ];

    const ctx = categoryBreakdownChart.getContext('2d');

    new Chart(ctx, {
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
                        padding: 15,
                        usePointStyle: true,
                        font: {
                            size: 11
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
            cutout: '60%'
        }
    });
});