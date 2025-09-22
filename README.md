# SubTrack

SubTrack is a subscription management and analytics platform for individuals and collaborative teams. The project pairs a PHP-driven experience with a modern React dashboard to help you organize recurring services, surface spending insights, and expose data via a secure API.

## Dual Interface Overview
- **Classic PHP Interface**: Procedural pages (`index.php`, `dashboard.php`, etc.) and MVC controllers (`dashboard_mvc.php`, `src/Controllers/*`) render server-side views from `src/Views`. This interface is session-first, requires no build step, and is ideal for constrained hosting or rapid iteration.
- **React Single-Page App**: Lives under `frontend/`, bootstrapped with Create React App. It consumes the same PHP endpoints (`api_auth.php`, `api_dashboard.php`, `api_spaces.php`) over JSON, adopts React-Bootstrap for UI, and layers richer visualizations (Chart.js, jsPDF exports).
- Both UIs run against the same MySQL database and share the PHP session cookie (`PHPSESSID`). The SPA communicates over CORS (`http://localhost:3003` → `http://localhost:8000`) using `withCredentials: true` so login state remains consistent between experiences.

## Feature Highlights
- Personal dashboards summarizing active and ended subscriptions, monthly totals, and category breakdowns.
- Shared Spaces for collaborative subscription management with invitations and role-aware access controls.
- In-depth Insights and Reports with trends, filters, and export helpers (server-rendered tables plus SPA charts/PDFs).
- API key generation and rate-limited REST endpoints for external integrations.
- Security primitives including CSRF tokens, session hardening, audit logging, and granular validation.
- Legacy PHP flows retained alongside the SPA for gradual modernization without feature loss.

## Architecture Overview
- **Backend (`src/`)**: Plain PHP structured around controllers, models, and views. Procedural endpoints (`api_auth.php`, `api_dashboard.php`, `api_spaces.php`, `api.php`) expose JSON APIs to the React app and third parties.
- **Database (`database/migrations/`)**: MySQL schema maintained with ordered SQL migration files; data access uses PDO with prepared statements.
- **Frontend (`frontend/`)**: React 19 application using React Router, React-Bootstrap, Chart.js, axios, and Testing Library. Auth context stores session-backed user state.
- **Supporting Assets**: Server-rendered views in the project root/public, diagnostic scripts under `tests/`, and enhancement notes in `docs/`.

## Prerequisites
- PHP 8.1+ with `pdo_mysql`, `mbstring`, and `json` extensions enabled.
- MySQL 8.0+ (MariaDB ≥10.6 is also compatible).
- Node.js 18+ and npm 8+ for the React frontend.
- Optional: A modern web server (Apache/Nginx) for production deployment.

## Getting Started

### 1. Clone the repository
```bash
git clone <repository-url>
cd subtrack
```

### 2. Configure the backend
- Update `src/Config/database.php` with credentials for your MySQL instance.
- Review session and CSRF defaults in `src/Config/csrf.php`, especially when deploying behind HTTPS.
- (Optional) Adjust `src/Utils/AuditLogger.php` paths if you store audit logs outside the repo.

### 3. Provision the database
Create the database and run the migrations in order:

```bash
mysql -u <user> -p -e "CREATE DATABASE subtrack_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u <user> -p subtrack_db < database/migrations/database_setup.sql
mysql -u <user> -p subtrack_db < database/migrations/improvements_schema.sql
mysql -u <user> -p subtrack_db < database/migrations/phase9_schema.sql
mysql -u <user> -p subtrack_db < database/migrations/phase9_schema_fixed.sql
mysql -u <user> -p subtrack_db < database/migrations/phase10_schema.sql
mysql -u <user> -p subtrack_db < database/migrations/phase10_schema_fixed.sql
mysql -u <user> -p subtrack_db < database/migrations/phase11_schema.sql
mysql -u <user> -p subtrack_db < database/migrations/fix_missing_columns.sql
mysql -u <user> -p subtrack_db < database/migrations/add_api_key_migration.sql
```

Seed data or run diagnostics with the helper scripts in `tests/` as needed.

### 4. Run the PHP interface
From the project root:

```bash
php -S localhost:8000
```

Navigate to `http://localhost:8000/index.php` for the marketing/landing page. Auth flows (`login.php`, `register.php`) and dashboards (`dashboard.php`, `dashboard_mvc.php?action=index`) are fully server-rendered.

### 5. Launch the React dashboard (optional)
```bash
cd frontend
cp .env .env.local   # adjust REACT_APP_API_URL or other overrides
npm install
PORT=3003 npm start
```

The SPA expects the backend at `http://localhost:8000` and uses cookies (`withCredentials: true`) for authentication. Update the CORS header in `api_dashboard.php`/`api_auth.php` if you host the frontend on a different origin. Build for production with `npm run build` and serve `frontend/build` using your web server of choice.

### 6. Create an account
- Use either the PHP UI (`register.php`) or the SPA's registration flow.
- Once logged in via one interface, the other recognizes the session automatically (shared cookie).
- Generate API keys via `generate_api_key.php` or explore the API Keys screen inside the SPA (currently backed by local storage for mock data until full API endpoints are wired).

## Working with the Application

### Classic PHP Interface
- Entry points such as `dashboard.php`, `categories.php`, `reports.php`, and `subscription_history.php` deliver Bootstrap-based pages.
- `dashboard_mvc.php` routes to `DashboardController`, which gathers data from `SubscriptionModel`, `SpaceModel`, and `CategoryModel` before rendering `src/Views/dashboard/index.php`.
- Controllers/Views handle CSRF validation (`src/Config/csrf.php`), flash messaging, and audit logging (`src/Utils/AuditLogger.php`).

### React Dashboard
- `frontend/src/components/dashboard/` renders cards and charts fed by `subscriptionsAPI` (`frontend/src/services/api.js`).
- Protected routes (`frontend/src/ProtectedRoute.js`) guard SPA routes using the session-aware `AuthContext`.
- Spaces, invitations, and API key UIs reside in `frontend/src/components/spaces/` and `frontend/src/components/apikeys/`. Some flows currently stub data via `localStorage` so UI work can continue before wiring additional endpoints.

### Shared Services and Data Flow
- **Authentication**: `api_auth.php` mirrors the PHP form login; both update the same `users` table and session. CSRF tokens protect form submissions, while the SPA relies on credentialed XHR requests.
- **Subscriptions**: `SubscriptionModel.php` powers CRUD, status toggling, and HTML row generation for PHP views. The same model feeds `api_dashboard.php` for SPA consumers.
- **Spaces & Invitations**: `SpaceModel.php` and `InvitationModel.php` back both `spaces.php` (server-rendered) and SPA modals; JSON endpoints live in `api_spaces.php`.
- **Insights & Reports**: `InsightsModel.php` and `reports.php` provide historical spending data; SPA charts call into `/api_dashboard.php?action=get_insights` and `/api_dashboard.php?action=get_summary`.

## API Access
`api.php` exposes read endpoints (`summary`, `subscriptions`, `categories`, `insights`) protected by API keys and a simple rate limiter. Example request:

```bash
curl -H "X-API-Key: <your-key>" \
     http://localhost:8000/api.php?endpoint=summary
```

Generate keys through `generate_api_key.php` (PHP UI) or the SPA's API Keys section.

## Security Considerations
- Review `session.cookie_secure`, `session.cookie_samesite`, and `session.cookie_httponly` before deploying to HTTPS. The SPA requires `SameSite=None` when hosted on a different origin.
- CSRF tokens (`src/Config/csrf.php`) are injected into PHP forms; ensure SPA forms include hidden tokens when posting multipart data to PHP endpoints.
- Audit logs (`storage/audit.log` by default) capture key user actions for compliance.

## Current Frontend Status
Some SPA modules ship with mock or local-storage data to showcase UI flows ahead of backend endpoints:
- API key CRUD in `ApiKeysPage` persists to `localStorage`.
- Category catalogs via `categoriesAPI` return seeded data.
- Space management falls back to demo data if `/api_spaces.php` is unreachable.

These stubs make it easy to iterate on UI while wiring the corresponding PHP APIs.

## Testing and Tooling
- **React**: `npm test` uses React Testing Library.
- **PHP utilities**: Run scripts in `tests/` (e.g., `php tests/test_api.php`) to validate endpoints and migrations during development.
- **Data scripts**: `loadSpaceDataBackup.js` can sync or inspect space data backups.

## Project Structure
```
├── api.php / api_auth.php / api_dashboard.php / api_spaces.php
├── database/
│   └── migrations/        # SQL schema files
├── docs/                  # Additional design and enhancement notes
├── frontend/              # React 19 SPA
├── public/                # Public assets for PHP pages
├── src/
│   ├── Config/            # Database, CSRF, and configuration helpers
│   ├── Controllers/       # MVC controllers (Auth, Dashboard, Spaces, etc.)
│   ├── Models/            # Domain models (Subscription, Space, Category, ...)
│   ├── Utils/             # Audit logger, helpers
│   └── Views/             # Blade-like PHP templates
├── tests/                 # Diagnostic PHP scripts
└── storage/               # Runtime storage/log folders (gitignored)
```

## Deployment Checklist
- Serve the PHP app behind Apache or Nginx with HTTPS and optimized PHP-FPM settings.
- Update CORS headers in `api_*.php` to whitelist production origins and align with your SPA host.
  - Enable `session.cookie_secure=1` and `session.cookie_samesite=Lax` (or `None` for cross-site cookies).
- Run `npm run build` and serve the `frontend/build` output (optionally from the same domain to reuse cookies without `SameSite=None`).
- Rotate the default API key secret and enforce environment-specific credentials.
- Set up automated backups for the `subtrack_db` database and `storage/` logs.

## License
Licensed under the MIT License. See `LICENSE` for full terms.
