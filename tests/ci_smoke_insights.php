<?php
// Smoke test for InsightsModel against a database built from the migration
// list in the README. CI runs it after applying those migrations to a fresh
// MySQL 8.0 service. It can also run against a local database, since the rows
// it writes are rolled back at the end.
//
// The calls below touch user_achievements (which improvements_schema.sql used
// to rename to user_milestones), insights, spending_goals and
// subscriptions.status (added by fix_missing_columns.sql). The model's other
// public methods and the rest of the app are not exercised here.
//
// Usage: php tests/ci_smoke_insights.php (reads .env, exit code 1 on any failure)

// database.php calls die() when it cannot connect, and die() with a message
// exits with status 0, so any stop before the end is turned into a failure.
$finished = false;
register_shutdown_function(function () use (&$finished) {
    if (!$finished) {
        echo "\nFAIL: stopped before the last check\n";
        exit(1);
    }
});

require_once __DIR__ . '/../src/Config/database.php';
require_once __DIR__ . '/../src/Models/InsightsModel.php';

$failed = false;

function check($ok, $message) {
    global $failed;
    echo ($ok ? 'PASS' : 'FAIL') . "  $message\n";
    if (!$ok) {
        $failed = true;
    }
}

try {
    $pdo->beginTransaction();

    // username, email and password_hash are the required columns (database_setup.sql)
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->execute(['ci_smoke_user', 'ci_smoke_user@example.com', password_hash('ci_smoke_password', PASSWORD_DEFAULT)]);
    $user_id = (int)$pdo->lastInsertId();

    $insightsModel = new InsightsModel($pdo);

    check($insightsModel->awardAchievement($user_id, 'first_subscription', 'title', 'desc', []) === true,
        'awardAchievement inserts into user_achievements');

    $achievements = $insightsModel->getUserAchievements($user_id);
    check(count($achievements) === 1 && $achievements[0]['title'] === 'title',
        'getUserAchievements returns exactly one row titled "title"');

    // Same type again: the unique key on (user_id, achievement_type) sends it
    // through ON DUPLICATE KEY UPDATE instead of adding a second row
    $insightsModel->awardAchievement($user_id, 'first_subscription', 'title', 'desc', []);
    check(count($insightsModel->getUserAchievements($user_id)) === 1,
        'awarding the same achievement again keeps one row');

    check(is_array($insightsModel->getUserInsights($user_id)), 'getUserInsights runs');
    check(is_array($insightsModel->getUserSpendingGoals($user_id)), 'getUserSpendingGoals runs');

    // With no subscriptions generateInsights returns early, but only after
    // selecting s.status, the column fix_missing_columns.sql adds
    check($insightsModel->generateInsights($user_id) === [],
        'generateInsights runs for a user with no subscriptions');

    // One active monthly subscription, so generateInsights goes on to saveInsights
    $stmt = $pdo->prepare("
        INSERT INTO subscriptions (user_id, service_name, cost, billing_cycle, start_date, status)
        VALUES (?, ?, ?, ?, ?, 'active')
    ");
    $stmt->execute([$user_id, 'Netflix', 15.99, 'monthly', '2025-01-01']);

    check(is_array($insightsModel->generateInsights($user_id)),
        'generateInsights runs for a user with an active subscription');
} catch (PDOException $e) {
    check(false, 'database error: ' . $e->getMessage());
}

// Roll back the rows this script wrote
if ($pdo->inTransaction()) {
    $pdo->rollBack();
}

$finished = true;

if ($failed) {
    echo "FAIL: InsightsModel smoke test\n";
    exit(1);
}

echo "PASS: the InsightsModel calls above ran against the migrated schema\n";
