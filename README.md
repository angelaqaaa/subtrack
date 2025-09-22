# SubTrack

SubTrack is a subscription management and analytics platform for individuals and collaborative teams. The project pairs a PHP MVC backend with a React dashboard to help you organize recurring services, surface spending insights, and expose data via a secure API.

## Feature Highlights
- Personal dashboards summarizing active and ended subscriptions, monthly totals, and category breakdowns.
- Shared Spaces for collaborative subscription management with invitations and role-aware access controls.
- In-depth Insights and Reports with trends, filters, and export helpers (server-rendered reports plus SPA visualizations).
- API key generation and rate-limited REST endpoints for external integrations.
- Security primitives including CSRF tokens, session hardening, audit logging, and granular validation.
- Legacy PHP pages retained alongside the SPA for gradual modernization.

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

### 4. Run the PHP application
From the project root:

```bash
php -S localhost:8000
```

This serves the classic PHP pages (`index.php`, `dashboard.php`, etc.) and JSON endpoints (`/api_*.php`). For production, configure Apache/Nginx to point at the project root, enable HTTPS, and lock down CORS origins.

### 5. Launch the React dashboard (optional)
```bash
cd frontend
cp .env .env.local   # adjust REACT_APP_API_URL or other overrides
npm install
PORT=3003 npm start
```

The SPA expects the backend at `http://localhost:8000` and uses cookies (`withCredentials: true`) for authentication. Update the CORS header in `api_dashboard.php`/`api_auth.php` if you host the frontend on a different origin. Build for production with `npm run build`.

### 6. Create an account
- Use the PHP UI (`register.php`) or the SPA's registration flow.
- Generate API keys via `generate_api_key.php` or explore the API Keys screen inside the SPA (currently backed by local storage for mock data until full API endpoints are wired).

## Working with the Application

- **Subscriptions**: `src/Models/SubscriptionModel.php` powers CRUD operations, status toggling, and cost calculations. The SPA components under `frontend/src/components/subscriptions/` provide modals for add/edit/delete actions.
- **Spaces and Invitations**: `SpaceModel.php` plus `InvitationModel.php` manage collaborative spaces. React components under `frontend/src/components/spaces/` surface creation, invitations, and membership management.
- **Insights and Reports**: `InsightsModel.php` and `reports.php` produce spending trends, while the SPA dashboards visualize charts via Chart.js and can export PDFs with jsPDF.
- **API Access**: `api.php` exposes read endpoints (`summary`, `subscriptions`, `categories`, `insights`) protected by API keys and a simple rate limiter. Example request:

  ```bash
  curl -H "X-API-Key: <your-key>" \
       http://localhost:8000/api.php?endpoint=summary
  ```

- **Security**: CSRF tokens (`src/Config/csrf.php`), audit logging (`src/Utils/AuditLogger.php`), password hashing, and conservative validation guard sensitive flows. Review cookie flags (`session.cookie_secure`, `session.cookie_samesite`) before enabling HTTPS.

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
- Update CORS headers in `api_*.php` to whitelist production origins.
  - Enable `session.cookie_secure=1` and `session.cookie_samesite=Lax` (or `None` for cross-site cookies).
- Run `npm run build` and serve the `frontend/build` output (optionally from the same domain to reuse cookies).
- Rotate the default API key secret and enforce environment-specific credentials.
- Set up automated backups for the `subtrack_db` database and `storage/` logs.

## License
Licensed under the MIT License. See `LICENSE` for full terms.
