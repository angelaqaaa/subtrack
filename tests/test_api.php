<?php
/**
 * SubTrack API Test Script
 * Simple test to verify API functionality
 */

session_start();
require_once __DIR__ . '/../src/Config/database.php';

// Configuration
$api_url = 'http://localhost:8000/api.php';

// Get current user's API key dynamically
$test_api_key = null;
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $stmt = $pdo->prepare("SELECT api_key FROM users WHERE id = ?");
    $stmt->execute([$_SESSION["id"]]);
    $result = $stmt->fetch();
    $test_api_key = $result['api_key'] ?? null;
}

if (!$test_api_key) {
    $test_api_key = 'YOUR_API_KEY_HERE';
}

// Prevent self-calling and infinite loops
if (strpos($_SERVER['REQUEST_URI'], 'test_api.php') !== false) {
    // This IS the test page, don't make HTTP requests to localhost
    echo "<h1>SubTrack API Test</h1>";

    if ($test_api_key === 'YOUR_API_KEY_HERE') {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h4>⚠️ API Key Required</h4>";
        echo "<p>You need to generate an API key first:</p>";
        echo "<ol>";
        echo "<li><a href='auth.php?action=login'>Login to your account</a></li>";
        echo "<li><a href='generate_api_key.php'>Generate your API key</a></li>";
        echo "<li>Return to this page to test the API</li>";
        echo "</ol>";
        echo "</div>";
        return;
    }

    echo "<div style='background: #e7f3ff; border: 1px solid #bee5eb; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h3>🔧 API Testing Instructions</h3>";
    echo "<p>To avoid infinite loops, test the API endpoints directly in your browser:</p>";
    echo "<h4>Test These URLs:</h4>";
    echo "<ol>";
    echo "<li><strong>Health Check:</strong><br><code>{$api_url}?endpoint=health&api_key={$test_api_key}</code></li>";
    echo "<li><strong>Summary:</strong><br><code>{$api_url}?endpoint=summary&api_key={$test_api_key}</code></li>";
    echo "<li><strong>Subscriptions:</strong><br><code>{$api_url}?endpoint=subscriptions&api_key={$test_api_key}</code></li>";
    echo "<li><strong>Categories:</strong><br><code>{$api_url}?endpoint=categories&api_key={$test_api_key}</code></li>";
    echo "<li><strong>Insights:</strong><br><code>{$api_url}?endpoint=insights&api_key={$test_api_key}</code></li>";
    echo "</ol>";
    echo "<h4>Alternative: Use curl commands:</h4>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px;'>";
    echo "curl \"{$api_url}?endpoint=health&api_key={$test_api_key}\"\n";
    echo "curl \"{$api_url}?endpoint=summary&api_key={$test_api_key}\"\n";
    echo "curl \"{$api_url}?endpoint=subscriptions&api_key={$test_api_key}\"";
    echo "</pre>";
    echo "</div>";

    echo "<h2>WordPress Plugin Test</h2>";
    echo "<p>To test the WordPress plugin:</p>";
    echo "<ol>";
    echo "<li>Install WordPress locally (recommended: Local by Flywheel)</li>";
    echo "<li>Copy the subtrack-wordpress-plugin folder to /wp-content/plugins/</li>";
    echo "<li>Activate the plugin in WordPress admin</li>";
    echo "<li>Go to Settings > SubTrack Integration</li>";
    echo "<li>Enter API URL: <code>{$api_url}</code></li>";
    echo "<li>Enter API key: <code>{$test_api_key}</code></li>";
    echo "<li>Click 'Test Connection'</li>";
    echo "<li>View the dashboard widget</li>";
    echo "</ol>";
    exit;
}
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    margin: 40px;
    line-height: 1.6;
}
h1 { color: #0073aa; }
h2, h3, h4 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 5px; }
pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: 'Monaco', 'Consolas', monospace; }
ol li { margin-bottom: 8px; }
</style>