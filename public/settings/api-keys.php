<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: /routes/auth.php?action=login");
    exit;
}

require_once "../../src/Config/database.php";

$user_id = $_SESSION["id"];

// Generate a secure API key
$api_key = bin2hex(random_bytes(32)); // 64 character hex string

// Update user with API key
$sql = "UPDATE users SET api_key = ? WHERE id = ?";

if($stmt = $pdo->prepare($sql)){
    $stmt->bindParam(1, $api_key, PDO::PARAM_STR);
    $stmt->bindParam(2, $user_id, PDO::PARAM_INT);

    if($stmt->execute()){
        $success_message = "API key generated successfully!";
    } else {
        $error_message = "Failed to generate API key.";
    }
    unset($stmt);
} else {
    $error_message = "Database error occurred.";
}

// Get current API key
$sql = "SELECT api_key FROM users WHERE id = ?";
$current_api_key = null;

if($stmt = $pdo->prepare($sql)){
    $stmt->bindParam(1, $user_id, PDO::PARAM_INT);

    if($stmt->execute()){
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_api_key = $result['api_key'] ?? null;
    }
    unset($stmt);
}

$page_title = "API Key Management";
include "../../src/Views/layouts/header.php";
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="bi bi-key me-2"></i>API Key Management
                    </h3>
                </div>
                <div class="card-body">
                    <?php if(isset($success_message)): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <p class="text-muted">
                        Your API key allows external applications (like WordPress plugins) to securely access your SubTrack data.
                        Keep this key private and secure.
                    </p>

                    <?php if($current_api_key): ?>
                        <div class="mb-4">
                            <label class="form-label"><strong>Your Current API Key:</strong></label>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace" value="<?php echo htmlspecialchars($current_api_key); ?>" readonly id="apiKeyField">
                                <button class="btn btn-outline-secondary" type="button" onclick="copyApiKey()">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5>API Endpoints:</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Endpoint</th>
                                            <th>Description</th>
                                            <th>Example URL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>summary</code></td>
                                            <td>Get spending summary</td>
                                            <td><small><?php echo $_SERVER['HTTP_HOST']; ?>/subtrack/api.php?endpoint=summary&api_key=YOUR_KEY</small></td>
                                        </tr>
                                        <tr>
                                            <td><code>subscriptions</code></td>
                                            <td>Get all subscriptions</td>
                                            <td><small><?php echo $_SERVER['HTTP_HOST']; ?>/subtrack/api.php?endpoint=subscriptions&api_key=YOUR_KEY</small></td>
                                        </tr>
                                        <tr>
                                            <td><code>categories</code></td>
                                            <td>Get spending by category</td>
                                            <td><small><?php echo $_SERVER['HTTP_HOST']; ?>/subtrack/api.php?endpoint=categories&api_key=YOUR_KEY</small></td>
                                        </tr>
                                        <tr>
                                            <td><code>insights</code></td>
                                            <td>Get detailed insights</td>
                                            <td><small><?php echo $_SERVER['HTTP_HOST']; ?>/subtrack/api.php?endpoint=insights&api_key=YOUR_KEY</small></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2">
                        <form method="POST" class="d-inline">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-arrow-clockwise me-1"></i>
                                <?php echo $current_api_key ? 'Regenerate API Key' : 'Generate API Key'; ?>
                            </button>
                        </form>

                        <a href="/routes/dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                        </a>
                    </div>

                    <?php if($current_api_key): ?>
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6><i class="bi bi-info-circle me-2"></i>Security Notes:</h6>
                            <ul class="mb-0 small">
                                <li>Never share your API key publicly or commit it to version control</li>
                                <li>API requests are rate-limited to 100 requests per hour</li>
                                <li>Regenerating your key will invalidate the previous one</li>
                                <li>Use HTTPS when making API calls in production</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyApiKey() {
    const apiKeyField = document.getElementById('apiKeyField');
    apiKeyField.select();
    apiKeyField.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(apiKeyField.value).then(function() {
        // Show temporary success message
        const button = document.querySelector('button[onclick="copyApiKey()"]');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="bi bi-check"></i> Copied!';
        button.classList.replace('btn-outline-secondary', 'btn-success');

        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.replace('btn-success', 'btn-outline-secondary');
        }, 2000);
    });
}
</script>

<?php include "../../src/Views/layouts/footer.php"; ?>