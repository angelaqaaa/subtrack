# SubTrack Architecture Documentation

## Project Overview

SubTrack is a hybrid full-stack application with **dual frontend architecture**:
- **React 19.1.1 SPA** (localhost:3000) - Modern single-page application
- **PHP Server-Rendered Pages** (localhost:8000) - Traditional MVC web pages
- **RESTful API** (`api/`) - JSON endpoints serving the React frontend
- **PHP 8+ MVC Backend** (`src/`) - Business logic, controllers, models
- **MySQL 8.0+** database with versioned migrations

Both frontends coexist and are fully functional, accessing the same backend and database.

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

### 2. **MVC Routes (`routes/`)** - PHP Server-Rendered Frontend

**Purpose**: Entry points for PHP server-rendered pages using MVC pattern

**Files**:
- `routes/auth.php` - Authentication routes
- `routes/dashboard.php` - Dashboard MVC route
- `routes/space.php` - Space management
- `routes/insights.php` - Financial insights & education
- `routes/invitations.php` - Invitation handling
- `routes/categories.php` - Category management

**Request Flow**:
```
User → /routes/dashboard.php
  ↓
Router loads DashboardController
  ↓
Controller calls Models (database)
  ↓
Controller includes Views (HTML templates)
  ↓
HTML response sent to browser
```

**When to Use**: Traditional server-rendered pages, SEO-critical pages, or when JavaScript is not available

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

### 5. **Public Directory (`public/`)** - PHP Frontend Pages & Assets

**Purpose**: PHP server-rendered pages and static assets

**Status**: Active PHP frontend alongside React SPA

**Directory Structure**:
- `public/auth/` - Authentication pages (login, register)
- `public/dashboard/` - Dashboard PHP pages
- `public/reports/` - Report generation pages
- `public/settings/` - User settings pages
- `public/subscriptions/` - Subscription management pages
- `public/spaces/` - Space management pages
- `public/assets/` - Static files (CSS, JS, images)

**Relationship with `/routes/`**:
- `routes/` files are the **entry points** that load controllers
- `public/` files are **direct PHP pages** or **views** rendered by controllers
- Both are part of the same PHP frontend system

**When to Use**: Can be accessed directly or via `/routes/` for MVC pattern

---

## Dual Frontend Architecture Explained

SubTrack maintains **two fully functional frontends** that operate independently but share the same backend:

### Frontend 1: React SPA (Modern Web App)
- **URL**: http://localhost:3000 (development)
- **Tech**: React 19.1.1, React Router, Bootstrap 5
- **Communication**: REST API via `api/` endpoints (JSON)
- **Navigation**: Client-side routing (no page reloads)
- **Authentication**: Session cookies via `credentials: include`
- **Target Users**: Users preferring modern, responsive single-page experience

**Example Flow**:
```
User opens localhost:3000
  ↓
React Router loads Dashboard component
  ↓
Component calls dashboardAPI.getSubscriptions()
  ↓
Axios sends GET to /api/dashboard.php?action=get_subscriptions
  ↓
Backend returns JSON
  ↓
React renders data
```

### Frontend 2: PHP Server-Rendered (Traditional Web Pages)
- **URL**: http://localhost:8000 (development)
- **Tech**: PHP 8+, MVC pattern, Bootstrap 5, jQuery
- **Communication**: Direct PHP execution or AJAX to `api/` endpoints
- **Navigation**: Server-side routing (page reloads)
- **Authentication**: Session cookies via `$_SESSION`
- **Target Users**: Users preferring traditional web pages, SEO needs

**Example Flow**:
```
User opens localhost:8000/routes/dashboard.php
  ↓
PHP loads DashboardController
  ↓
Controller calls SubscriptionModel->getUserSubscriptions()
  ↓
Model queries database
  ↓
Controller includes src/Views/dashboard/index.php
  ↓
HTML rendered and sent to browser
```

### Shared Backend Components
Both frontends access:
- **Same Database**: `subtrack_db` MySQL database
- **Same Models**: `src/Models/*.php` for data operations
- **Same Controllers**: `src/Controllers/*.php` for business logic
- **Same Session Store**: PHP `$_SESSION` for authentication
- **Same API Endpoints**: `api/` for AJAX operations (PHP frontend can use these too)

### Why Dual Frontend?
1. **Flexibility**: Users can choose their preferred experience
2. **Showcase Skills**: Demonstrates both modern SPA and traditional web development
3. **Progressive Enhancement**: PHP pages work without JavaScript
4. **SEO**: Server-rendered pages better for search engines
5. **Redundancy**: If one frontend has issues, the other still works

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

SubTrack requires running servers for both frontends:

1. **PHP Backend + PHP Frontend** (port 8000):
```bash
php -S localhost:8000
```
This serves:
- PHP frontend pages (`/routes/`, `/public/`)
- REST API endpoints (`/api/`)
- Access at: http://localhost:8000

2. **React SPA Frontend** (port 3000):
```bash
cd frontend && npm start
```
This serves:
- React single-page application
- Proxies API calls to localhost:8000
- Access at: http://localhost:3000

3. **Database**:
- MySQL running on localhost:3306
- Database: `subtrack_db`
- Apply migrations in order from `database/migrations/`

**Development Flow**:
- Develop React features: Work in `frontend/src/`, test at localhost:3000
- Develop PHP features: Work in `routes/`, `public/`, `src/`, test at localhost:8000
- Develop API features: Work in `api/`, test from both frontends
- Develop Models: Work in `src/Models/`, affects both frontends equally

### React Frontend Development

**API Service** (`frontend/src/services/api.js`):
- Centralized axios instance
- Base URL: `process.env.REACT_APP_API_URL` (default: http://localhost:8000)
- Automatic 401 handling with redirect to login
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

**Adding New React Component**:
1. Create component in `frontend/src/components/{feature}/`
2. Import AuthContext if authentication needed
3. Use API methods from `services/api.js`
4. Add route in `App.js` if it's a page-level component
5. Wrap with `<ProtectedRoute>` if authentication required

### PHP Frontend Development

**Adding New PHP Page**:
1. Create route entry in `routes/{name}.php`
2. Create or use existing controller in `src/Controllers/`
3. Create view template in `src/Views/{name}/`
4. Ensure proper session validation
5. Follow MVC pattern (route → controller → model → view)

**Example Route** (`routes/example.php`):
```php
<?php
session_start();
require_once __DIR__ . '/../src/Controllers/ExampleController.php';

if (!isset($_SESSION['loggedin'])) {
    header('Location: /routes/auth.php?action=login');
    exit;
}

$controller = new ExampleController($pdo);
$controller->index();
```

### Backend/API Development

**Adding New API Endpoint**:
1. Add action case in `api/{resource}.php`
2. Validate session: `if (!isset($_SESSION["loggedin"]))`
3. Call Model method for data operations
4. Return JSON: `sendResponse('success', 'Done', $data)`
5. Add method to `frontend/src/services/api.js` for React frontend
6. PHP frontend can also call this endpoint via AJAX

**Example API Endpoint**:
```php
// api/example.php
case 'get_data':
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendResponse('error', 'Method not allowed', null, 405);
    }

    if (!isset($_SESSION['loggedin'])) {
        sendResponse('error', 'Unauthorized', null, 401);
    }

    $model = new ExampleModel($pdo);
    $data = $model->getData($_SESSION['user_id']);

    sendResponse('success', 'Data retrieved', $data);
    break;
```

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

1. **Confusing the two frontends**:
   - React frontend (localhost:3000) uses API endpoints exclusively
   - PHP frontend (localhost:8000) can use direct PHP or API via AJAX
   - Don't mix frontend concerns - keep React and PHP separate

2. **Forgetting CORS headers** - Required for React API calls from localhost:3000

3. **Not exiting after JSON** - Corrupts JSON response with trailing HTML

4. **Relative paths in PHP** - Use absolute paths with `/routes/` or `__DIR__`

5. **Missing session validation** - Both frontends share the same session, always validate

6. **SQL injection** - Always use PDO prepared statements in all Models

7. **Testing on wrong frontend** - Feature might work on one frontend but not the other

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
