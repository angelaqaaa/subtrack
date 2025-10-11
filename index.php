<?php
session_start();

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: /routes/dashboard.php");
    exit;
}

$page_title = "Welcome to SubTrack";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-credit-card-2-front me-2"></i>SubTrack
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="public/auth/login.php">Login</a>
                <a class="nav-link" href="public/auth/register.php">Register</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-primary mb-4">
                        Take Control of Your Subscriptions
                    </h1>
                    <p class="lead mb-4">
                        Track, manage, and optimize all your subscription services in one place.
                        Stop losing money on forgotten subscriptions and get insights into your spending patterns.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="public/auth/register.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-plus me-2"></i>Get Started Free
                        </a>
                        <a href="public/auth/login.php" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="row g-3 mt-3">
                        <div class="col-6">
                            <div class="card text-center bg-primary text-white">
                                <div class="card-body">
                                    <i class="bi bi-currency-dollar display-6"></i>
                                    <h5 class="mt-2">Track Costs</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card text-center bg-success text-white">
                                <div class="card-body">
                                    <i class="bi bi-pie-chart display-6"></i>
                                    <h5 class="mt-2">Visualize Data</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card text-center bg-info text-white">
                                <div class="card-body">
                                    <i class="bi bi-bell display-6"></i>
                                    <h5 class="mt-2">Get Insights</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card text-center bg-warning text-white">
                                <div class="card-body">
                                    <i class="bi bi-shield-check display-6"></i>
                                    <h5 class="mt-2">Stay Secure</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="fw-bold">Why Choose SubTrack?</h2>
                    <p class="text-muted">Everything you need to manage your subscriptions effectively</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="bi bi-list-ul text-primary fs-4"></i>
                            </div>
                            <h5 class="fw-bold">Easy Management</h5>
                            <p class="text-muted">Add, edit, and delete subscriptions with a simple, intuitive interface.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="bi bi-graph-up text-success fs-4"></i>
                            </div>
                            <h5 class="fw-bold">Smart Analytics</h5>
                            <p class="text-muted">Get detailed insights into your spending patterns and identify savings opportunities.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="bi bi-shield-lock text-info fs-4"></i>
                            </div>
                            <h5 class="fw-bold">Secure & Private</h5>
                            <p class="text-muted">Your financial data is encrypted and secure. We never share your information.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 mb-4">
                    <div class="h2 fw-bold text-primary">$200+</div>
                    <p class="text-muted">Average Monthly Savings</p>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="h2 fw-bold text-primary">15+</div>
                    <p class="text-muted">Average Subscriptions per User</p>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="h2 fw-bold text-primary">99.9%</div>
                    <p class="text-muted">Uptime Reliability</p>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="h2 fw-bold text-primary">24/7</div>
                    <p class="text-muted">Data Protection</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h2 class="fw-bold mb-4">Ready to Take Control?</h2>
                    <p class="lead text-muted mb-4">Join thousands of users who have already started saving money with SubTrack.</p>
                    <a href="public/auth/register.php" class="btn btn-primary btn-lg px-5">
                        <i class="bi bi-rocket-takeoff me-2"></i>Start Tracking Now
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="fw-bold">
                        <i class="bi bi-credit-card-2-front me-2"></i>SubTrack
                    </h5>
                    <p class="mb-0">Your personal subscription management solution.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> SubTrack. Built with PHP & Bootstrap.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>