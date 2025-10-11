# SubTrack Architecture Documentation

## Project Overview

SubTrack is a hybrid full-stack application combining:
- **React 19.1.1 SPA** for modern user interface
- **PHP 8+ MVC Backend** for server-side logic
- **RESTful API** for React frontend communication
- **Legacy PHP Pages** for backwards compatibility
- **MySQL 8.0+** database with versioned migrations

## Directory Structure

```
subtrack/
├── api/                      # RESTful JSON API endpoints for React
├── routes/                   # PHP MVC route handlers (entry points)
├── public/                   # Legacy PHP pages + static assets
├── src/                      # PHP backend (MVC architecture)
│   ├── Controllers/          # Business logic
│   ├── Models/               # Database operations
│   ├── Views/                # Server-rendered templates
│   ├── Config/               # Configuration (database, CSRF)
│   └── Utils/                # Utilities (AuditLogger)
├── frontend/                 # React SPA application
├── database/migrations/      # Versioned SQL schema files
├── tests/                    # PHP test files
├── logs/                     # Application logs (gitignored)
├── scripts/                  # Utility scripts
└── docs/                     # Additional documentation
```

## Architecture Patterns

### 1. **API Endpoints (`api/`)** - React Frontend Communication

**Purpose**: Provide JSON-only responses for the React SPA

**Files**:
- `api/auth.php` - Authentication (login, register, 2FA, session)
- `api/dashboard.php` - Dashboard data, subscriptions CRUD
- `api/spaces.php` - Shared workspace management
- `api/index.php` - Public API with API key authentication

**Key Features**:
- CORS enabled for `http://localhost:3000`
- Action-based routing: `?action=get_all`, `?action=create`
- JSON request/response format
- Session-based authentication with `credentials: include`
- Consistent response structure: `{status, message, data, timestamp}`

**Example**:
```javascript
const response = await axios.post('/api/auth.php?action=login', {
  username: 'user@example.com',
  password: 'password',
  two_factor_code: '123456'  // if 2FA enabled
}, { withCredentials: true });
```

---

### 2. **MVC Routes (`routes/`)** - PHP Server-Rendered Pages

**Purpose**: Route requests to Controllers for legacy PHP pages

**Files**:
- `routes/auth.php` - Authentication routes
- `routes/dashboard.php` - Dashboard MVC route
- `routes/space.php` - Space management
- `routes/insights.php` - Financial insights & education
- `routes/invitations.php` - Invitation handling
- `routes/categories.php` - Category management

**Pattern**:
```
User → /routes/dashboard.php
  ↓
Router loads DashboardController
  ↓
Controller calls Models (database)
  ↓
Controller includes Views (HTML templates)
  ↓
Response sent to browser
```

**Best Practice**: All internal redirects should use `/routes/` paths, not `/public/`

---

### 3. **MVC Backend (`src/`)**

#### Controllers (`src/Controllers/`)
Handle business logic and request processing.

**Files**:
- `AuthController.php` - User authentication, 2FA setup
- `DashboardController.php` - Dashboard logic
- `SpaceController.php` - Workspace management
- `InsightsController.php` - Financial insights, goals, achievements
- `InvitationController.php` - Space invitations
- `CategoryController.php` - Category CRUD

**Responsibilities**:
- Session validation
- Input validation
- Call Model methods
- Prepare data for Views
- Return JSON for AJAX requests

#### Models (`src/Models/`)
Database operations using PDO prepared statements.

**Files**:
- `UserModel.php` - User CRUD, 2FA logic, backup codes
- `SubscriptionModel.php` - Subscription CRUD, analytics
- `SpaceModel.php` - Workspace CRUD, members, permissions
- `InsightsModel.php` - Insights generation, goals, achievements
- `InvitationModel.php` - Invitation CRUD
- `CategoryModel.php` - Category CRUD

**Responsibilities**:
- All database queries (prepared statements)
- Data validation
- Business logic related to data integrity
- No HTML output - returns data only

#### Views (`src/Views/`)
HTML templates with embedded PHP for display.

**Structure**:
```
Views/
├── auth/           # Login, register templates
├── dashboard/      # Dashboard, insights templates
├── spaces/         # Space management templates
├── categories/     # Category management
├── education/      # Educational content
├── invitations/    # Invitation pages
└── layouts/        # Header, footer templates
```

**Best Practice**: Views should only display data, no business logic

---

### 4. **React Frontend (`frontend/`)**

**Tech Stack**:
- React 19.1.1 with Hooks
- React Router 7.9.1 for routing
- React-Bootstrap 2.10.10 + Bootstrap 5.3.8
- Axios 1.12.2 for API calls
- Chart.js 4.5.0 for visualizations
- jsPDF 3.0.2 for PDF exports
- QRCode 1.5.4 for 2FA setup

**Directory Structure**:
```
frontend/src/
├── components/       # Feature-based components
│   ├── auth/         # Login, Register
│   ├── dashboard/    # Dashboard widgets
│   ├── spaces/       # Workspace management
│   ├── subscriptions/# Subscription CRUD
│   ├── settings/     # User settings, 2FA
│   ├── reports/      # Reports, analytics
│   ├── insights/     # Financial insights
│   ├── categories/   # Category management
│   ├── profile/      # User profile
│   └── layout/       # Navigation, common layouts
├── contexts/         # React Context (AuthContext)
├── services/         # API integration (axios)
└── utils/            # Utility functions
```

**Key Features**:
- Protected routes with `<ProtectedRoute>` wrapper
- Global authentication state via `AuthContext`
- Axios interceptor for 401 handling (auto-redirect to login)
- All API calls via `services/api.js` centralized service

---

### 5. **Legacy Public Pages (`public/`)**

**Purpose**: Standalone PHP pages (deprecated pattern)

**Status**: ⚠️ Gradually being migrated to MVC pattern

**Files to Keep**:
- `public/reports/` - Report generation (standalone acceptable)
- `public/settings/` - Settings pages
- `public/assets/` - Static files (CSS, JS, images)

**Files to Avoid** (use `/routes/` instead):
- ❌ `public/dashboard/index.php` → Use `/routes/dashboard.php`
- ❌ `public/auth/*` → Use `/routes/auth.php?action=`

---

## Database Schema

### Core Tables

**users**
- User accounts, passwords (bcrypt), email
- Columns: `id`, `username`, `email`, `password`, `created_at`

**user_backup_codes**
- 2FA recovery codes (bcrypt hashed)
- Columns: `id`, `user_id`, `code`, `used_at`, `created_at`

**subscriptions**
- All subscriptions (personal + space)
- Unified table with `space_id` column
- Columns: `id`, `user_id`, `space_id`, `service_name`, `cost`, `currency`, `billing_cycle`, `start_date`, `end_date`, `category`, `is_active`, `status`, `created_at`, `updated_at`

**spaces**
- Shared workspaces
- Columns: `id`, `name`, `description`, `created_by`, `created_at`

**space_users**
- Space membership with roles
- Columns: `id`, `space_id`, `user_id`, `role` (owner/admin/editor/viewer), `status`, `invited_by`, `joined_at`

**space_invitations**
- Pending space invitations
- Columns: `id`, `space_id`, `email`, `token`, `role`, `invited_by`, `expires_at`, `created_at`

**custom_categories**
- User-defined subscription categories
- Columns: `id`, `user_id`, `name`, `color`, `icon`, `created_at`

**insights**
- AI-generated financial insights
- Columns: `id`, `user_id`, `type`, `title`, `description`, `impact_score`, `data`, `status`, `expires_at`, `dismissed_at`, `created_at`

**spending_goals**
- Category-based spending limits
- Columns: `id`, `user_id`, `category`, `monthly_limit`, `start_date`, `end_date`, `status`, `created_at`

**user_achievements**
- Gamification badges
- Columns: `id`, `user_id`, `achievement_type`, `title`, `description`, `data`, `earned_at`

**educational_content**
- Financial education articles
- Columns: `id`, `title`, `slug`, `content`, `category`, `difficulty_level`, `estimated_read_time`, `view_count`, `is_published`, `created_at`

**user_education_progress**
- User reading progress tracking
- Columns: `id`, `user_id`, `content_id`, `progress_percentage`, `completed_at`, `created_at`

**activity_log** (audit trail)
- Comprehensive user action logging
- Columns: `id`, `user_id`, `action`, `entity_type`, `entity_id`, `ip_address`, `user_agent`, `details`, `created_at`

**user_sessions**
- Active session management
- Columns: `id`, `user_id`, `session_token`, `ip_address`, `user_agent`, `created_at`, `expires_at`

**login_history**
- Login attempt tracking
- Columns: `id`, `user_id`, `ip_address`, `user_agent`, `success`, `created_at`

---

## Security Architecture

### Authentication Flow

**Password Authentication**:
1. User submits credentials
2. Backend verifies bcrypt hashed password
3. Session created with `$_SESSION["loggedin"] = true`
4. HttpOnly, Secure, SameSite cookie sent
5. All API requests include cookies via `credentials: include`

**Two-Factor Authentication (2FA)**:
1. User enables 2FA in settings
2. Backend generates TOTP secret
3. QR code displayed for authenticator app
4. User enters verification code
5. 8 backup codes generated (bcrypt hashed)
6. Future logins require 2FA code

**Session Security**:
- HttpOnly cookies (prevents XSS access)
- Secure flag (HTTPS only in production)
- SameSite=None for CORS (development)
- Session regeneration on login
- Automatic timeout after inactivity

### CSRF Protection

**Implementation**:
- Custom `CSRFHandler` class (`src/Config/csrf.php`)
- Tokens stored in `$_SESSION["csrf_token"]`
- Validated on all POST requests
- Timing attack prevention

**Usage**:
```php
$csrfHandler = new CSRFHandler();
$token = $csrfHandler->generateToken();
// In form: <input type="hidden" name="csrf_token" value="<?= $token ?>">
if (!$csrfHandler->validateToken($_POST['csrf_token'])) {
    die('Invalid CSRF token');
}
```

### Role-Based Access Control (RBAC)

**Space Roles** (hierarchical):
1. **Owner**: Full control, can delete space
2. **Admin**: Invite members, manage subscriptions
3. **Editor**: Add/edit subscriptions
4. **Viewer**: Read-only access

**Permission Checks**:
```php
$spaceModel->hasPermission($space_id, $user_id, 'editor');
```

### Audit Logging

**AuditLogger** (`src/Utils/AuditLogger.php`):
- Logs all significant actions
- Captures: user_id, action, entity, IP, user agent
- Stored in `activity_log` table
- Used for compliance and debugging

**Example**:
```php
$auditLogger->logActivity(
    $user_id,
    'subscription_created',
    'subscription',
    $subscription_id,
    ['service' => 'Netflix', 'cost' => 15.99]
);
```

---

## API Response Format

All API endpoints return consistent JSON:

**Success Response**:
```json
{
  "status": "success",
  "message": "Operation completed successfully",
  "data": { ... },
  "timestamp": "2025-10-11T10:30:00Z"
}
```

**Error Response**:
```json
{
  "status": "error",
  "message": "Validation failed",
  "data": {
    "errors": ["Username is required", "Invalid email"]
  },
  "timestamp": "2025-10-11T10:30:00Z"
}
```

**HTTP Status Codes**:
- 200: Success
- 400: Bad Request (validation error)
- 401: Unauthorized (not logged in)
- 403: Forbidden (insufficient permissions)
- 404: Not Found
- 500: Internal Server Error

---

## Development Workflow

### Local Development Setup

1. **Backend Server** (port 8000):
```bash
php -S localhost:8000
```

2. **Frontend Server** (port 3000):
```bash
cd frontend && npm start
```

3. **Database**:
- MySQL running on localhost:3306
- Database: `subtrack_db`
- Apply migrations in order from `database/migrations/`

### Frontend Development

**API Service** (`frontend/src/services/api.js`):
- Centralized axios instance
- Base URL: `process.env.REACT_APP_API_URL`
- Automatic 401 handling
- All API methods exported

**Example**:
```javascript
import { authAPI, dashboardAPI, spacesAPI } from './services/api';

// Login
await authAPI.login({ username, password, two_factor_code });

// Get subscriptions
const subs = await dashboardAPI.getSubscriptions();

// Create space
await spacesAPI.create({ name, description });
```

### Backend Development

**Adding New API Endpoint**:
1. Add action case in `api/[resource].php`
2. Validate session: `if (!isset($_SESSION["loggedin"]))`
3. Call Model method for data operations
4. Return JSON: `sendResponse('success', 'Done', $data)`
5. Add method to `frontend/src/services/api.js`

**Adding New MVC Route**:
1. Create route file in `routes/[name].php`
2. Create Controller in `src/Controllers/[Name]Controller.php`
3. Create Model if needed in `src/Models/[Name]Model.php`
4. Create View in `src/Views/[name]/index.php`
5. Update navigation links

---

## Testing Strategy

### PHP Backend Tests

Located in `tests/`:
- `test_api.php` - API endpoint testing
- `test_audit.php` - Audit logging verification
- `test_phase11.php` - Insights feature testing
- `test_end_subscription.php` - Subscription lifecycle testing
- `debug_subscription.php` - Subscription debugging
- `debug_insights.php` - Insights debugging

**Run Tests**:
```bash
php tests/test_api.php
php tests/test_audit.php
```

### React Frontend Tests

**Test Framework**: Jest + React Testing Library

**Run Tests**:
```bash
cd frontend
npm test              # Run all tests
npm test -- --watch   # Watch mode
npm test -- --coverage # Coverage report
```

---

## Deployment Considerations

### Production Configuration

**Backend** (.env):
- Set `DB_HOST` to production database
- Use strong `CSRF_SECRET`
- Configure `MAIL_*` for email functionality
- Set `session.cookie_secure = 1` for HTTPS

**Frontend** (.env.local):
- Set `REACT_APP_API_URL` to production API domain

**Build React**:
```bash
cd frontend && npm run build
```
Deploy `frontend/build/` directory via web server

### Web Server Configuration

**Apache** (`.htaccess`):
- Enable URL rewriting
- Set proper CORS headers
- Configure PHP session settings

**Nginx**:
- Proxy API requests to PHP-FPM
- Serve React build from `/`
- Handle API routes

---

## File Naming Conventions

### PHP
- **Controllers**: PascalCase + "Controller" suffix (`DashboardController.php`)
- **Models**: PascalCase + "Model" suffix (`SubscriptionModel.php`)
- **Routes**: lowercase, underscore (`dashboard.php`, `auth.php`)
- **Methods**: camelCase (`getUserSubscriptions()`, `createSpace()`)

### JavaScript/React
- **Components**: PascalCase (`Dashboard.js`, `SpaceDetailPage.js`)
- **Functions**: camelCase (`handleSubmit`, `fetchData`)
- **Files**: Match component name

### Database
- **Tables**: plural, snake_case (`subscriptions`, `space_users`)
- **Columns**: snake_case (`user_id`, `created_at`)

---

## Best Practices

### Always Use Absolute Paths
```php
// ✅ CORRECT
header("location: /routes/dashboard.php");
<a href="/routes/auth.php?action=logout">Logout</a>

// ❌ WRONG
header("location: dashboard.php");
<a href="auth.php">Login</a>
```

### Use __DIR__ in Backend Files
```php
// ✅ CORRECT
require_once __DIR__ . '/../src/Config/database.php';
include __DIR__ . '/../Views/layouts/header.php';

// ❌ WRONG
require_once '../src/Config/database.php';
include 'src/Views/layouts/header.php';
```

### Always Exit After JSON Response
```php
// ✅ CORRECT
echo json_encode(['status' => 'success']);
exit;

// ❌ WRONG (HTML footer will corrupt JSON)
echo json_encode(['status' => 'success']);
break;
```

### Use PDO Prepared Statements
```php
// ✅ CORRECT
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);

// ❌ WRONG (SQL injection risk)
$query = "SELECT * FROM users WHERE id = $user_id";
```

---

## Common Pitfalls

1. **Mixing public/ and routes/** - Always redirect to `/routes/`
2. **Forgetting CORS headers** - Required for React API calls
3. **Not exiting after JSON** - Corrupts JSON response
4. **Relative paths** - Breaks when directory structure changes
5. **Missing session validation** - Security vulnerability
6. **SQL injection** - Always use prepared statements

---

## Future Improvements

### Short-term
- Migrate remaining `public/` pages to MVC pattern
- Add unit tests for all Models
- Implement API rate limiting
- Add email verification for registration

### Long-term
- Implement WebSockets for real-time collaboration
- Add Redis caching layer
- Migrate to proper routing library (AltoRouter, Symfony Router)
- Consider microservices architecture for scaling
- Add GraphQL API option

---

Last Updated: 2025-10-11
Version: 2.0
