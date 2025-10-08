<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - SubTrack' : 'SubTrack - Subscription Manager'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/public/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/routes/dashboard.php">
                <i class="bi bi-credit-card-2-front me-2"></i>SubTrack
            </a>

            <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/routes/dashboard.php">
                            <i class="bi bi-house-door me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-people me-1"></i>Spaces
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/routes/dashboard.php#spaces">
                                <i class="bi bi-list me-1"></i>My Spaces
                            </a></li>
                            <li><a class="dropdown-item" href="/routes/invitations.php">
                                <i class="bi bi-envelope me-1"></i>Invitations
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#createSpaceModal">
                                <i class="bi bi-plus-circle me-1"></i>Create Space
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-lightbulb me-1"></i>Insights
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/routes/insights.php?action=dashboard">
                                <i class="bi bi-graph-up me-1"></i>Financial Insights
                            </a></li>
                            <li><a class="dropdown-item" href="/routes/insights.php?action=education">
                                <i class="bi bi-book me-1"></i>Education Center
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/public/reports/index.php">
                            <i class="bi bi-graph-up me-1"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/public/dashboard/subscription-history.php">
                            <i class="bi bi-clock-history me-1"></i>History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/routes/categories.php">
                            <i class="bi bi-folder2 me-1"></i>Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/public/settings/api-keys.php">
                            <i class="bi bi-key me-1"></i>API Key
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION["username"]); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/routes/auth.php?action=logout">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container-fluid py-4">