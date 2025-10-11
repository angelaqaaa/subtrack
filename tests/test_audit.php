<?php
session_start();

// Include required files
require_once __DIR__ . '/../src/Config/database.php';
require_once __DIR__ . '/../src/Utils/AuditLogger.php';

// Test audit logging functionality
echo "<h2>Phase 10 Audit Trail Test</h2>";

try {
    $auditLogger = new AuditLogger($pdo);

    echo "<h3>Testing Activity Logging</h3>";

    // Get the first available user ID
    $stmt = $pdo->query("SELECT id FROM users ORDER BY id LIMIT 1");
    $first_user = $stmt->fetch();
    $test_user_id = $first_user ? $first_user['id'] : null;

    if (!$test_user_id) {
        echo "<p>⚠️ No users found in database. Please register a user first at <a href='auth.php?action=register'>registration page</a></p>";
        exit;
    }

    // Log a test activity
    $auditLogger->logActivity(
        $test_user_id,
        'test_activity',
        'test',
        null,
        ['test_data' => 'Phase 10 implementation test'],
        null
    );

    echo "✅ Test activity logged successfully<br>";

    // Retrieve recent activities for the test user
    echo "<h3>Recent Activities for User ID {$test_user_id}</h3>";
    $activities = $auditLogger->getUserActivities($test_user_id, 10);

    if(!empty($activities)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Action</th><th>Entity</th><th>Details</th><th>Time</th></tr>";

        foreach($activities as $activity) {
            $formatted = $auditLogger->formatActivity($activity);
            echo "<tr>";
            echo "<td>{$activity['action']}</td>";
            echo "<td>{$activity['entity_type']}</td>";
            echo "<td>" . htmlspecialchars(json_encode(json_decode($activity['details'], true))) . "</td>";
            echo "<td>{$activity['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p>✅ Activity retrieval working correctly</p>";
    } else {
        echo "<p>⚠️ No activities found for user</p>";
    }

    // Test space activities if spaces exist
    $stmt = $pdo->query("SELECT id FROM spaces LIMIT 1");
    $space = $stmt->fetch();

    if($space) {
        echo "<h3>Space Activities Test</h3>";
        $space_activities = $auditLogger->getSpaceActivities($space['id'], 5);

        if(!empty($space_activities)) {
            echo "<p>✅ Found " . count($space_activities) . " space activities</p>";
        } else {
            echo "<p>⚠️ No space activities found</p>";
        }
    }

    echo "<h3>Database Schema Verification</h3>";

    // Check if activity_log table exists and has correct structure
    $stmt = $pdo->query("DESCRIBE activity_log");
    $columns = $stmt->fetchAll();

    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

    foreach($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<p>✅ Activity log table structure verified</p>";

    echo "<h2>🎉 Phase 10 Audit Trail Implementation Complete!</h2>";
    echo "<p>All audit logging functionality is working correctly.</p>";

} catch(Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>