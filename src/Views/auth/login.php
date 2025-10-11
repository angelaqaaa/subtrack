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
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-header">
                        <h2 class="text-center">Login to SubTrack</h2>
                    </div>
                    <div class="card-body">
                        <?php if(isset($_GET['success']) && $_GET['success'] == 'registered'): ?>
                            <div class="alert alert-success">Registration successful! Please log in with your credentials.</div>
                        <?php endif; ?>

                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if(isset($errors['general'])): ?>
                            <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                        <?php endif; ?>

                        <form action="/routes/auth.php?action=login" method="post">
                            <?php echo $csrf_token ? '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf_token) . '">' : ''; ?>

                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                                <?php if(isset($errors['username'])): ?>
                                    <span class="invalid-feedback"><?php echo $errors['username']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>">
                                <?php if(isset($errors['password'])): ?>
                                    <span class="invalid-feedback"><?php echo $errors['password']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <input type="submit" class="btn btn-primary w-100" value="Login">
                            </div>

                            <p class="text-center">Don't have an account? <a href="/routes/auth.php?action=register">Sign up now</a>.</p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>