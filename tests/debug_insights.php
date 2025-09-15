<?php
session_start();

// Include required files
require_once 'config/database.php';
require_once 'models/InsightsModel.php';
require_once 'utils/AuditLogger.php';
require_once 'config/csrf.php';

echo "<h1>Insight Dismiss Debug</h1>";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo "<p>❌ User not logged in. Please <a href='auth.php?action=login'>login first</a>.</p>";
    exit;
}

$user_id = $_SESSION["id"];
echo "<p>✅ User logged in: ID {$user_id}, Username: {$_SESSION['username']}</p>";

try {
    $insightsModel = new InsightsModel($pdo);
    $auditLogger = new AuditLogger($pdo);
    $csrfHandler = new CSRFHandler();

    echo "<h2>1. Testing Insight Generation</h2>";

    // Generate some insights
    $insights = $insightsModel->generateInsights($user_id);
    echo "<p>Generated " . count($insights) . " new insights</p>";

    // Get stored insights
    $stored_insights = $insightsModel->getUserInsights($user_id, 5);
    echo "<p>Found " . count($stored_insights) . " stored insights in database</p>";

    if (empty($stored_insights)) {
        echo "<p>⚠️ No insights found. Let's create a test insight:</p>";

        // Insert a test insight
        $stmt = $pdo->prepare("
            INSERT INTO insights (user_id, type, title, description, impact_score, data, expires_at)
            VALUES (?, 'saving_opportunity', 'Test Insight', 'This is a test insight for debugging', 5, '{}', DATE_ADD(NOW(), INTERVAL 30 DAY))
        ");
        if ($stmt->execute([$user_id])) {
            echo "<p>✅ Test insight created</p>";

            // Get insights again
            $stored_insights = $insightsModel->getUserInsights($user_id, 5);
            echo "<p>Now found " . count($stored_insights) . " insights</p>";
        } else {
            echo "<p>❌ Failed to create test insight</p>";
        }
    }

    if (!empty($stored_insights)) {
        echo "<h2>2. Testing Insight Details</h2>";

        $test_insight = $stored_insights[0];
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($test_insight as $key => $value) {
            echo "<tr><td>{$key}</td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";

        echo "<h2>3. Testing Dismiss Functionality</h2>";

        $insight_id = $test_insight['id'];
        echo "<p>Testing dismiss for insight ID: {$insight_id}</p>";

        // Test the dismiss method directly
        echo "<h3>Direct Method Test:</h3>";
        $result = $insightsModel->dismissInsight($insight_id, $user_id);
        echo "<p>dismissInsight() returned: " . ($result ? 'TRUE' : 'FALSE') . "</p>";

        if ($result) {
            echo "<p>✅ Direct dismiss worked</p>";

            // Check if it was actually updated
            $stmt = $pdo->prepare("SELECT status FROM insights WHERE id = ? AND user_id = ?");
            $stmt->execute([$insight_id, $user_id]);
            $updated_insight = $stmt->fetch();

            if ($updated_insight) {
                echo "<p>Updated insight status: {$updated_insight['status']}</p>";
            } else {
                echo "<p>❌ Could not find updated insight</p>";
            }

            // Reset it back to active for further testing
            $stmt = $pdo->prepare("UPDATE insights SET status = 'active' WHERE id = ? AND user_id = ?");
            $stmt->execute([$insight_id, $user_id]);
            echo "<p>Reset insight back to active for testing</p>";
        } else {
            echo "<p>❌ Direct dismiss failed</p>";
        }

        echo "<h3>CSRF Token Test:</h3>";
        $csrf_token = $csrfHandler->generateToken();
        echo "<p>Generated CSRF token: " . substr($csrf_token, 0, 20) . "...</p>";

        $is_valid = $csrfHandler->validateToken($csrf_token);
        echo "<p>CSRF token validation: " . ($is_valid ? 'VALID' : 'INVALID') . "</p>";

        echo "<h3>Simulated AJAX Request Test:</h3>";

        // Simulate the exact POST data that would be sent
        $_POST['insight_id'] = $insight_id;
        $_POST['action'] = 'dismiss';
        $_POST['csrf_token'] = $csrf_token;

        echo "<p>Simulating POST data:</p>";
        echo "<pre>";
        print_r($_POST);
        echo "</pre>";

        // Test the validation logic
        if (!$csrfHandler->validateToken($_POST['csrf_token'] ?? '')) {
            echo "<p>❌ CSRF validation failed in simulation</p>";
        } else {
            echo "<p>✅ CSRF validation passed in simulation</p>";

            $sim_insight_id = $_POST["insight_id"] ?? null;
            $sim_action = $_POST["action"] ?? null;

            if (!$sim_insight_id || !$sim_action) {
                echo "<p>❌ Missing parameters in simulation</p>";
            } else {
                echo "<p>✅ Parameters present in simulation</p>";

                if ($sim_action === 'dismiss') {
                    $success = $insightsModel->dismissInsight($sim_insight_id, $user_id);
                    echo "<p>Simulation dismiss result: " . ($success ? 'SUCCESS' : 'FAILED') . "</p>";

                    if ($success) {
                        echo "<p>✅ Full simulation worked!</p>";

                        // Try logging
                        try {
                            $auditLogger->logActivity(
                                $user_id,
                                'insight_dismissed',
                                'insight',
                                $sim_insight_id,
                                ['action' => 'dismiss']
                            );
                            echo "<p>✅ Audit logging worked</p>";
                        } catch (Exception $e) {
                            echo "<p>❌ Audit logging failed: " . $e->getMessage() . "</p>";
                        }
                    }
                }
            }
        }

        // Clean up POST data
        unset($_POST['insight_id'], $_POST['action'], $_POST['csrf_token']);
    }

    echo "<h2>4. Database State Check</h2>";

    // Check insights table structure
    echo "<h3>Insights Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE insights");
    $columns = $stmt->fetchAll();

    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
    }
    echo "</table>";

    // Check current insights count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, status, COUNT(*) as count FROM insights WHERE user_id = ? GROUP BY status");
    $stmt->execute([$user_id]);
    $status_counts = $stmt->fetchAll();

    echo "<h3>Insights Status Distribution:</h3>";
    if (empty($status_counts)) {
        echo "<p>No insights found for this user</p>";
    } else {
        echo "<table border='1'>";
        echo "<tr><th>Status</th><th>Count</th></tr>";
        foreach ($status_counts as $row) {
            echo "<tr><td>{$row['status']}</td><td>{$row['count']}</td></tr>";
        }
        echo "</table>";
    }

} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>🔧 Testing Instructions</h2>";
echo "<p>1. Go to <a href='insights.php?action=dashboard' target='_blank'>insights dashboard</a></p>";
echo "<p>2. Try to dismiss an insight</p>";
echo "<p>3. Open browser console (F12) and look for JavaScript errors</p>";
echo "<p>4. Check network tab to see the actual request being sent</p>";
?>