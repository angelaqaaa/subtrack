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
                        <h2 class="text-center">Register for SubTrack</h2>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if(isset($errors['general'])): ?>
                            <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                        <?php endif; ?>

                        <form action="/routes/auth.php?action=register" method="post">
                            <?php echo $csrf_token ? '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf_token) . '">' : ''; ?>

                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                                <?php if(isset($errors['username'])): ?>
                                    <span class="invalid-feedback"><?php echo $errors['username']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                                <?php if(isset($errors['email'])): ?>
                                    <span class="invalid-feedback"><?php echo $errors['email']; ?></span>
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
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>">
                                <?php if(isset($errors['confirm_password'])): ?>
                                    <span class="invalid-feedback"><?php echo $errors['confirm_password']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <input type="submit" class="btn btn-primary w-100" value="Register">
                            </div>

                            <p class="text-center">Already have an account? <a href="/routes/auth.php?action=login">Login here</a>.</p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>