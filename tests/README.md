# SubTrack Test Files

This directory contains PHP test files for verifying backend functionality.

## Test Files

### `test_api.php`
**Purpose**: API endpoint testing

**What it tests**:
- API key generation and validation
- Authentication endpoints (`/api/auth.php`)
- Session management
- Database connection

**Usage**:
```bash
php tests/test_api.php
```

---

### `test_audit.php`
**Purpose**: Audit logging verification

**What it tests**:
- Activity logging functionality
- `AuditLogger` class methods
- User activity retrieval
- Space activity tracking
- Database schema verification for `activity_log` table

**Usage**:
```bash
php tests/test_audit.php
```

**Expected Output**:
- ✅ Test activity logged successfully
- ✅ Recent activities retrieved
- ✅ Activity log table structure verified

---

### `test_phase11.php`
**Purpose**: Financial insights feature testing

**What it tests**:
- Database schema for insights features
- Educational content retrieval
- Insights generation for users
- Spending goals creation
- User achievements system
- File structure verification

**Tables Tested**:
- `insights`
- `educational_content`
- `user_education_progress`
- `spending_goals`
- `user_achievements`

**Usage**:
```bash
php tests/test_phase11.php
```

---

### `test_end_subscription.php`
**Purpose**: Subscription lifecycle testing

**What it tests**:
- `endSubscription()` method
- Subscription status updates
- Space member permissions
- Date handling for subscription end dates

**Usage**:
```bash
php tests/test_end_subscription.php
```

**Note**: Requires manual adjustment of `$subscription_id` and `$user_id` in the file to test with actual data.

---

### `debug_subscription.php`
**Purpose**: Subscription creation and validation debugging

**What it tests**:
- Subscription data validation
- Subscription creation workflow
- Database retrieval of created subscriptions
- User session validation

**Features**:
- Creates a test Netflix subscription
- Validates subscription data
- Retrieves and displays created subscription
- Shows current subscriptions for logged-in user

**Usage**:
```bash
php tests/debug_subscription.php
```

**Requirements**:
- User must be logged in (active session)
- Access via browser at: `http://localhost:8000/tests/debug_subscription.php`

---

### `debug_insights.php`
**Purpose**: Insights feature debugging

**What it tests**:
- Insight generation and storage
- Insight dismissal functionality
- CSRF token validation
- Audit logging for insights
- Database state verification

**Features**:
- Generates test insights
- Tests dismiss functionality
- Simulates AJAX requests
- Verifies database schema
- Shows insights status distribution

**Usage**:
```bash
php tests/debug_insights.php
```

**Requirements**:
- User must be logged in (active session)
- Access via browser at: `http://localhost:8000/tests/debug_insights.php`

---

## Running All Tests

**Sequential Execution**:
```bash
php tests/test_api.php
php tests/test_audit.php
php tests/test_phase11.php
```

## Test Requirements

All test files require:
1. **Database Connection**: MySQL running with `subtrack_db` database
2. **Migrations Applied**: All schema migrations must be run
3. **Configuration**: `.env` file properly configured
4. **PHP Session**: For tests requiring authentication

## Debug Files vs Test Files

### Test Files
- Automated tests that can run from command line
- Return success/failure status
- Don't require browser access
- Examples: `test_api.php`, `test_audit.php`

### Debug Files
- Interactive debugging tools
- Require browser access and active session
- Display HTML formatted output
- Provide step-by-step execution details
- Examples: `debug_subscription.php`, `debug_insights.php`

## Common Issues

### "User not logged in"
**Solution**: Debug files require an active session. Log in via:
- React frontend: `http://localhost:3000`
- PHP frontend: `http://localhost:8000/routes/auth.php?action=login`

### "Database connection failed"
**Solution**: Check `.env` file for correct database credentials
```env
DB_HOST=localhost
DB_NAME=subtrack_db
DB_USER=your_username
DB_PASS=your_password
```

### "Table doesn't exist"
**Solution**: Run all migrations:
```bash
mysql -u root -p subtrack_db < database/migrations/database_setup.sql
mysql -u root -p subtrack_db < database/migrations/improvements_schema.sql
# ... (run all migrations in order)
```

### "No users found"
**Solution**: Register a user first:
- Visit `http://localhost:3000` (React)
- OR visit `http://localhost:8000/routes/auth.php?action=register` (PHP)

## Adding New Tests

To add a new test file:

1. **Create File**: `tests/test_[feature].php`

2. **Include Dependencies**:
```php
<?php
session_start();
require_once __DIR__ . '/../src/Config/database.php';
require_once __DIR__ . '/../src/Models/YourModel.php';
```

3. **Test Structure**:
```php
try {
    echo "<h2>Testing [Feature]</h2>";

    // Test code here
    $model = new YourModel($pdo);
    $result = $model->testMethod();

    echo "✅ Test passed";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
```

4. **Update This README**: Add documentation for your test

## Best Practices

1. **Always use `__DIR__` for paths**:
   ```php
   require_once __DIR__ . '/../src/Config/database.php';
   ```

2. **Handle exceptions properly**:
   ```php
   try {
       // test code
   } catch (Exception $e) {
       echo "Error: " . $e->getMessage();
   }
   ```

3. **Provide clear output**:
   - Use ✅ for success
   - Use ❌ for failures
   - Use ⚠️ for warnings

4. **Don't modify production data**:
   - Use test data or create temporary records
   - Clean up after tests when possible

5. **Document requirements**:
   - What user roles needed
   - What data must exist
   - What configurations required

---

Last Updated: 2025-10-11
