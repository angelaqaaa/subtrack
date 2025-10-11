# SubTrack Architecture Documentation

## Directory Structure Overview

SubTrack uses a **hybrid dual-frontend architecture** with three main PHP directory types:

### 📁 Directory Purposes

#### 1. **`routes/` - MVC Router Files** ⭐ **PRIMARY ENTRY POINTS**
Modern MVC pattern router files that users should access.

**Purpose**: Route incoming requests to appropriate Controllers
**Pattern**: `routes/[name].php` → Controller → View
**Access**: Always use `/routes/[name].php`

**Files**:
- `/routes/auth.php` - Authentication (login, register, logout)
- `/routes/dashboard.php` - Main dashboard (MVC)
- `/routes/space.php` - Shared workspaces
- `/routes/insights.php` - Financial insights & education
- `/routes/invitations.php` - Space invitations
- `/routes/categories.php` - Category management

**Example Flow**:
```
User visits: /routes/dashboard.php
    ↓
Route loads: DashboardController
    ↓
Controller: Processes logic, prepares data
    ↓
View: src/Views/dashboard/index.php (rendered)
```

---

#### 2. **`api/` - JSON API Endpoints**
RESTful API endpoints for the React frontend.

**Purpose**: Provide JSON-only responses (no HTML)
**Pattern**: Action-based routing with `?action=` parameter
**Access**: From React frontend via axios

**Files**:
- `/api/auth.php` - Authentication API
- `/api/dashboard.php` - Dashboard data API
- `/api/spaces.php` - Spaces API
- `/api/index.php` - Public API (with API key)

**CORS Configuration**: Allows `http://localhost:3000` (React dev server)

**Example**:
```javascript
// React frontend
axios.post('/api/auth.php', {
  action: 'login',
  username: 'user',
  password: 'pass'
});
```

---

#### 3. **`public/` - Standalone PHP Pages** ⚠️ **LEGACY/DEPRECATED**
Legacy standalone PHP pages with embedded logic (not following MVC pattern).

**Purpose**: Direct-access pages from earlier development
**Status**: Should be considered deprecated
**Issue**: Causes confusion with dual entry points

**Why They Exist**:
- Project evolved from standalone PHP to MVC pattern
- Not yet fully migrated to avoid breaking changes
- Kept for specific utility pages (reports, export)

**Files to Keep**:
- `/public/reports/` - Report pages (standalone is acceptable)
- `/public/subscriptions/edit.php` - Direct edit form (can stay)
- `/public/settings/` - Settings pages (can stay)

**Files to Avoid**:
- ❌ `/public/dashboard/index.php` - Use `/routes/dashboard.php` instead
- ❌ `/public/auth/*` - Use `/routes/auth.php?action=` instead

---

## 🎯 Correct Usage Patterns

### Authentication
```php
// ✅ CORRECT - Use MVC route
header("location: /routes/auth.php?action=login");
<a href="/routes/auth.php?action=logout">Logout</a>

// ❌ WRONG - Don't use standalone
header("location: /public/auth/login.php");
```

### Dashboard
```php
// ✅ CORRECT - Use MVC route
header("location: /routes/dashboard.php");
<a href="/routes/dashboard.php">Dashboard</a>

// ❌ WRONG - Don't use standalone
header("location: /public/dashboard/index.php");
```

### Spaces
```php
// ✅ CORRECT
<a href="/routes/space.php?action=view&space_id=123">View Space</a>

// ❌ WRONG
<a href="space.php?action=view&space_id=123">View Space</a>
```

### Forms
```html
<!-- ✅ CORRECT - Absolute paths to routes -->
<form action="/routes/dashboard.php?action=add" method="post">

<!-- ❌ WRONG - Relative paths -->
<form action="dashboard.php?action=add" method="post">
```

---

## 🔧 MVC Pattern Structure

### Controllers (`src/Controllers/`)
Business logic and request handling.

**Responsibilities**:
- Validate user authentication
- Process POST data
- Call Model methods for database operations
- Prepare data for Views
- Handle JSON responses (for AJAX)

**Example**:
```php
class DashboardController {
    public function index() {
        // 1. Check auth
        if (!isset($_SESSION["loggedin"])) {
            header("location: /public/auth/login.php");
            exit;
        }

        // 2. Get data from Model
        $subscriptions = $this->subscriptionModel->getUserSubscriptions($_SESSION["id"]);

        // 3. Render View
        include __DIR__ . '/../Views/layouts/header.php';
        include __DIR__ . '/../Views/dashboard/index.php';
        include __DIR__ . '/../Views/layouts/footer.php';
    }
}
```

### Models (`src/Models/`)
Database operations using PDO.

**Responsibilities**:
- Database queries (prepared statements)
- Data validation
- Business logic related to data

**No HTML output** - Models never echo or include views.

### Views (`src/Views/`)
HTML templates with minimal PHP.

**Responsibilities**:
- Display data passed from Controllers
- Form rendering
- User interface

**No business logic** - Views only display, don't process.

---

## 📊 Request Flow Comparison

### MVC Pattern (Correct Way)
```
Browser → /routes/dashboard.php
    ↓
Router loads DashboardController
    ↓
Controller validates session
    ↓
Controller calls SubscriptionModel
    ↓
Model queries database with PDO
    ↓
Model returns data array
    ↓
Controller passes data to View
    ↓
View renders HTML
    ↓
Browser displays page
```

### Standalone Pattern (Legacy)
```
Browser → /public/dashboard/index.php
    ↓
Page checks session directly
    ↓
Page queries database directly
    ↓
Page generates HTML inline
    ↓
Browser displays page
```

**Problem**: Mixing business logic with presentation, harder to maintain.

---

## 🔒 Authentication Flow

1. User submits login form → `/routes/auth.php?action=login`
2. Route calls `AuthController::login()`
3. Controller validates credentials via `UserModel`
4. On success: Creates `$_SESSION["loggedin"] = true`
5. Redirects to: `/routes/dashboard.php`
6. All subsequent requests checked by Controllers

**Session-Based Auth**:
- PHP sessions with HttpOnly cookies
- `$_SESSION["id"]` - User ID
- `$_SESSION["username"]` - Username
- `$_SESSION["loggedin"]` - Auth flag

---

## 📝 File Naming Conventions

### Routes
- Lowercase, underscore-separated: `dashboard.php`, `auth.php`
- Action via query param: `?action=login`

### Controllers
- PascalCase with "Controller" suffix: `DashboardController.php`
- Methods: camelCase: `addSubscription()`, `deleteCategory()`

### Models
- PascalCase with "Model" suffix: `SubscriptionModel.php`
- Methods: camelCase: `getUserSubscriptions()`, `createSpace()`

### Views
- Lowercase, hyphen-separated directories
- Files: lowercase: `dashboard/index.php`, `auth/login.php`

---

## 🚀 Best Practices

### Always Use Absolute Paths
```php
// ✅ CORRECT
header("location: /routes/dashboard.php");
<a href="/routes/auth.php?action=logout">Logout</a>
<link href="/public/assets/css/style.css" rel="stylesheet">

// ❌ WRONG
header("location: dashboard.php");
<a href="auth.php?action=logout">Logout</a>
<link href="css/style.css" rel="stylesheet">
```

### Use __DIR__ in Controllers
```php
// ✅ CORRECT
include __DIR__ . '/../Views/layouts/header.php';
require_once __DIR__ . '/../Models/UserModel.php';

// ❌ WRONG
include '../Views/layouts/header.php';
require_once 'src/Models/UserModel.php';
```

### JSON Endpoints Must Exit
```php
// ✅ CORRECT
case 'add_subscription':
    $dashboardController->addSubscription();
    exit; // Prevent HTML footer from appending

// ❌ WRONG
case 'add_subscription':
    $dashboardController->addSubscription();
    break; // HTML footer will corrupt JSON!
```

---

## 🐛 Common Pitfalls

### 1. Using Relative Paths
**Problem**: Breaks when file location changes
**Solution**: Always use absolute paths starting with `/`

### 2. Mixing public/ and routes/
**Problem**: Users land on different dashboards
**Solution**: All redirects should go to `/routes/dashboard.php`

### 3. JSON Endpoints Not Exiting
**Problem**: HTML layout appends to JSON, causes parse errors
**Solution**: Add `exit;` after all JSON responses in routes

### 4. Forgetting CORS Headers
**Problem**: React frontend can't access PHP APIs
**Solution**: Add CORS headers to routes used by React

---

## 📦 Future Improvements

### Short-term
1. ✅ Redirect all `public/dashboard/index.php` to `/routes/dashboard.php`
2. ✅ Add CORS to all routes used by React
3. ✅ Use `exit;` after all JSON responses

### Long-term
1. Remove redundant `public/auth/` pages (use routes exclusively)
2. Convert remaining `public/` pages to MVC pattern
3. Consider adding URL rewriting (.htaccess) for cleaner URLs
4. Implement proper routing library (e.g., AltoRouter)

---

## 🎓 Learning Resources

**MVC Pattern**:
- Controllers handle requests
- Models handle data
- Views handle display

**Why MVC?**
- Separation of concerns
- Easier testing
- Better code organization
- Easier collaboration

**Current Implementation**:
- Lightweight MVC (no framework)
- PHP native routing
- PDO for database
- Session-based authentication

---

Last Updated: 2025-10-11
