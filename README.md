# SubTrack 🚀

> **A comprehensive subscription management platform featuring advanced security, collaborative workspaces, and modern full-stack architecture.**

[![React](https://img.shields.io/badge/React-19.1.1-blue?logo=react)](https://reactjs.org/)
[![PHP](https://img.shields.io/badge/PHP-8%2B-purple?logo=php)](https://php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-orange?logo=mysql)](https://mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple?logo=bootstrap)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

SubTrack is an enterprise-grade subscription management and analytics platform that combines a modern React SPA with a robust PHP backend. Designed for both individual users and collaborative teams, it offers advanced security features, real-time collaboration, and comprehensive analytics.

## 🌟 Key Features

### 🔐 **Enterprise Security**
- **Two-Factor Authentication (2FA)** with TOTP algorithm and backup codes
- **Advanced Session Management** with security controls and audit logging
- **CSRF Protection** using cryptographically secure tokens
- **Role-Based Access Control (RBAC)** for multi-tenant workspaces
- **Comprehensive Audit Trail** with IP tracking and user agent logging

### 👥 **Collaborative Workspaces**
- **Shared Spaces** for team subscription management
- **Invitation System** with email workflows and role assignments
- **Real-time Collaboration** with member management and permissions
- **Multi-tenant Architecture** with secure data isolation

### 📊 **Advanced Analytics**
- **Interactive Dashboards** with Chart.js visualizations
- **Spending Trends Analysis** with category breakdowns
- **PDF Export Capabilities** for reports and documentation
- **Custom Date Range Filtering** and data insights

### 🏗️ **Modern Architecture**
- **Hybrid Frontend**: React 19.1.1 SPA + PHP server-rendered pages
- **RESTful API Design** with consistent endpoint patterns
- **Database Migrations** with versioned schema management
- **Environment-Based Configuration** for deployment flexibility

## 🏁 Quick Start

### Prerequisites
- **PHP 8.1+** with `pdo_mysql`, `mbstring`, and `json` extensions
- **MySQL 8.0+** (MariaDB ≥10.6 compatible)
- **Node.js 18+** and npm 8+ for React frontend
- **Web Server** (Apache/Nginx) for production deployment

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/angelaqaaa/subtrack.git
cd subtrack
```

2. **Configure environment**
```bash
# Backend configuration
cp .env.example .env
# Edit .env with your database credentials

# Frontend configuration
cd frontend
cp .env.example .env.local
# Adjust API URL if needed (default: http://localhost:8000)
```

3. **Setup database**
```bash
mysql -u root -p -e "CREATE DATABASE subtrack_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations in order
mysql -u root -p subtrack_db < database/migrations/database_setup.sql
mysql -u root -p subtrack_db < database/migrations/improvements_schema.sql
mysql -u root -p subtrack_db < database/migrations/phase9_schema_fixed.sql
mysql -u root -p subtrack_db < database/migrations/phase10_schema_fixed.sql
mysql -u root -p subtrack_db < database/migrations/phase11_schema.sql
mysql -u root -p subtrack_db < database/migrations/fix_missing_columns.sql
mysql -u root -p subtrack_db < database/migrations/add_api_key_migration.sql
mysql -u root -p subtrack_db < database/migrations/database_2fa_migration.sql
mysql -u root -p subtrack_db < database/migrations/missing_tables_migration.sql
mysql -u root -p subtrack_db < database/migrations/phase12_space_role_updates.sql
```

4. **Start the application**
```bash
# Backend (from project root)
php -S localhost:8000

# Frontend (in new terminal)
cd frontend && npm install && npm start
```

5. **Access the application**
- **React SPA**: http://localhost:3000
- **PHP Interface**: http://localhost:8000
- **API Endpoints**: http://localhost:8000/api/

## 🏗️ Architecture

### Technology Stack

**Frontend**
- **React 19.1.1** with Hooks and Context API
- **React Router 7.9.1** for client-side routing
- **React-Bootstrap 2.10.10** for responsive UI components
- **Bootstrap 5.3.8** for styling
- **Bootstrap Icons 1.13.1** for iconography
- **Chart.js 4.5.0** with React-ChartJS-2 5.3.0 for interactive data visualizations
- **Axios 1.12.2** with request/response interceptors
- **jsPDF 3.0.2** with jsPDF-AutoTable 5.0.2 for PDF generation
- **QRCode 1.5.4** for 2FA QR code generation
- **date-fns 4.1.0** for date formatting and manipulation

**Backend**
- **PHP 8+** with modern OOP patterns and MVC architecture
- **PDO** with prepared statements for secure database access
- **Custom CSRF Handler** with timing attack protection
- **Session Security** with HttpOnly, Secure, and SameSite cookies
- **TOTP Algorithm** for two-factor authentication
- **Audit Logger** for comprehensive activity tracking

**Database**
- **MySQL 8.0+** with InnoDB engine
- **Versioned Migrations** for schema management
- **Foreign Key Constraints** for data integrity
- **Optimized Indexes** for query performance

### Project Structure
```
subtrack/
├── 📁 api/                      # RESTful API endpoints for React frontend
│   ├── auth.php                 # Authentication (login, register, 2FA)
│   ├── dashboard.php            # Dashboard data and subscriptions
│   ├── spaces.php               # Shared workspace management
│   └── index.php                # API key-based access
├── 📁 routes/                   # PHP MVC route handlers for legacy pages
│   ├── auth.php                 # Legacy authentication routes
│   ├── dashboard.php            # Legacy dashboard routes
│   ├── insights.php             # Financial insights routes
│   ├── space.php                # Space management routes
│   ├── invitations.php          # Invitation handling routes
│   └── categories.php           # Category management routes
├── 📁 public/                   # Legacy PHP pages and static assets
│   ├── 📁 auth/                 # Login/registration pages
│   ├── 📁 dashboard/            # PHP dashboard views
│   ├── 📁 reports/              # Report generation pages
│   ├── 📁 settings/             # Settings pages (API keys)
│   ├── 📁 subscriptions/        # Subscription CRUD pages
│   └── 📁 assets/               # CSS, JavaScript, images
├── 📁 frontend/                 # React SPA application
│   ├── 📁 src/
│   │   ├── 📁 components/       # React components by feature
│   │   │   ├── auth/            # Login, Register
│   │   │   ├── dashboard/       # Dashboard widgets
│   │   │   ├── spaces/          # Workspace management
│   │   │   ├── subscriptions/   # Subscription CRUD
│   │   │   ├── settings/        # User settings, 2FA
│   │   │   ├── reports/         # Reports and analytics
│   │   │   ├── insights/        # Financial insights
│   │   │   ├── categories/      # Category management
│   │   │   └── layout/          # Navigation, common layouts
│   │   ├── 📁 contexts/         # React Context providers (AuthContext)
│   │   ├── 📁 services/         # API integration layer (Axios)
│   │   └── 📁 utils/            # Utility functions (ActivityLogger)
│   └── 📄 package.json          # Frontend dependencies
├── 📁 src/                      # PHP backend MVC structure
│   ├── 📁 Controllers/          # Business logic controllers
│   │   ├── AuthController.php   # Authentication logic
│   │   ├── DashboardController.php
│   │   ├── SpaceController.php
│   │   ├── InsightsController.php
│   │   ├── InvitationController.php
│   │   └── CategoryController.php
│   ├── 📁 Models/               # Data access models
│   │   ├── UserModel.php        # User CRUD, 2FA logic
│   │   ├── SubscriptionModel.php
│   │   ├── SpaceModel.php
│   │   ├── InsightsModel.php
│   │   ├── InvitationModel.php
│   │   └── CategoryModel.php
│   ├── 📁 Views/                # Server-rendered templates
│   │   ├── auth/, dashboard/, spaces/, etc.
│   │   └── layouts/             # Header, footer templates
│   ├── 📁 Config/               # Configuration classes
│   │   ├── database.php         # PDO connection
│   │   └── csrf.php             # CSRF token handler
│   └── 📁 Utils/                # Utility classes
│       └── AuditLogger.php      # Activity logging
├── 📁 database/migrations/      # Versioned SQL schema files
├── 📁 tests/                    # PHP testing utilities
│   ├── test_api.php             # API endpoint tests
│   ├── test_audit.php           # Audit logging tests
│   ├── test_phase11.php         # Insights feature tests
│   ├── test_end_subscription.php
│   ├── debug_subscription.php
│   └── debug_insights.php
├── 📁 logs/                     # Application logs (gitignored)
├── 📁 scripts/                  # Utility scripts
├── 📁 docs/                     # Additional documentation
├── 📄 index.php                 # Landing page
├── 📄 .env.example              # Environment configuration template
├── 📄 ARCHITECTURE.md           # Detailed architecture documentation
└── 📄 CLAUDE.md                 # Development guidelines for AI assistance
```

## 🔐 Security Features

### Authentication & Authorization
- **Multi-factor Authentication**: TOTP-based 2FA with QR code setup
- **Backup Codes**: 8 one-time recovery codes (bcrypt hashed)
- **Secure Password Handling**: bcrypt hashing with salt
- **Session Management**: Secure cookies with regeneration
- **CSRF Protection**: Token-based with timing attack prevention
- **Role-Based Access**: Granular permissions for shared spaces (admin, editor, viewer)

### Data Protection
- **SQL Injection Prevention**: PDO prepared statements with parameter binding
- **XSS Protection**: Input sanitization and output encoding
- **Environment Variables**: Secure credential management via .env files
- **Audit Logging**: Comprehensive activity tracking with IP addresses
- **Rate Limiting**: API endpoint protection (configurable)
- **Session Security**: HttpOnly, Secure, SameSite cookie attributes

## 📊 API Documentation

### Authentication Endpoints (`/api/auth.php`)
```http
POST /api/auth.php?action=login              # User login
POST /api/auth.php?action=register           # User registration
POST /api/auth.php?action=logout             # User logout
POST /api/auth.php?action=setup_2fa          # Generate 2FA secret
POST /api/auth.php?action=verify_2fa_setup   # Verify 2FA setup
POST /api/auth.php?action=enable_2fa         # Enable 2FA
POST /api/auth.php?action=disable_2fa        # Disable 2FA
GET  /api/auth.php?action=current_user       # Get current user info
POST /api/auth.php?action=change_password    # Change password
```

### Dashboard Endpoints (`/api/dashboard.php`)
```http
GET  /api/dashboard.php?action=get_summary        # Dashboard summary stats
GET  /api/dashboard.php?action=get_subscriptions # User subscriptions
GET  /api/dashboard.php?action=get_insights      # Financial insights
POST /api/dashboard.php?action=add_subscription  # Create subscription
PUT  /api/dashboard.php?action=update_subscription
POST /api/dashboard.php?action=delete_subscription
POST /api/dashboard.php?action=end_subscription  # Mark as ended
```

### Spaces Endpoints (`/api/spaces.php`)
```http
GET  /api/spaces.php?action=get_all              # Get user's spaces
POST /api/spaces.php?action=create               # Create new space
GET  /api/spaces.php?action=get_members          # Get space members
POST /api/spaces.php?action=invite               # Invite user to space
POST /api/spaces.php?action=sync_subscriptions   # Add subscriptions to space
POST /api/spaces.php?action=unsync_subscription  # Remove from space
DELETE /api/spaces.php?action=remove_member      # Remove member
DELETE /api/spaces.php?action=delete             # Delete space
```

### Example API Usage
```javascript
// Authentication with 2FA
const response = await fetch('/api/auth.php?action=login', {
  method: 'POST',
  credentials: 'include',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    username: 'user@example.com',
    password: 'securepassword',
    two_factor_code: '123456'  // Optional, required if 2FA enabled
  })
});

// Create subscription
const subResponse = await fetch('/api/dashboard.php?action=add_subscription', {
  method: 'POST',
  credentials: 'include',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    service_name: 'Netflix',
    cost: 15.99,
    currency: 'USD',
    billing_cycle: 'monthly',
    start_date: '2025-01-01',
    category: 'Entertainment'
  })
});
```

## 🧪 Testing

### PHP Backend Tests
```bash
# API endpoint tests
php tests/test_api.php

# Audit logging tests
php tests/test_audit.php

# Financial insights feature tests
php tests/test_phase11.php

# Subscription lifecycle tests
php tests/test_end_subscription.php

# Debug utilities
php tests/debug_subscription.php
php tests/debug_insights.php
```

### React Frontend Tests
```bash
cd frontend

# Run all tests
npm test

# Run tests in watch mode
npm test -- --watch

# Generate coverage report
npm test -- --coverage
```

## 🐛 Troubleshooting

### Common Issues

**Database Connection Errors**
```bash
# Check MySQL is running
sudo systemctl status mysql  # Linux
brew services list | grep mysql  # macOS

# Verify credentials in .env
DB_HOST=localhost
DB_NAME=subtrack_db
DB_USER=your_username
DB_PASS=your_password
```

**CORS Issues in Development**
- Ensure React dev server runs on `localhost:3000`
- PHP backend runs on `localhost:8000`
- Check `Access-Control-Allow-Origin` headers in API files

**Session/Cookie Issues**
- Clear browser cookies for localhost
- Check `session.cookie_samesite` in PHP configuration
- Ensure `credentials: 'include'` in Axios requests

**Migration Errors**
```bash
# If migrations fail, check which have been applied
mysql -u root -p subtrack_db -e "SHOW TABLES;"

# Reset database (WARNING: deletes all data)
mysql -u root -p -e "DROP DATABASE subtrack_db; CREATE DATABASE subtrack_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

**Frontend Build Issues**
```bash
# Clear cache and reinstall
cd frontend
rm -rf node_modules package-lock.json
npm cache clean --force
npm install
```

### Logs Location
- **PHP Errors**: `logs/api_dashboard.log` (or check PHP error_log)
- **React Console**: Browser DevTools Console
- **Audit Logs**: Database `audit_logs` table

## 🤝 Contributing

### Development Workflow
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes following code standards below
4. Test thoroughly using the testing section above
5. Commit changes with descriptive messages
6. Push to your branch and open a Pull Request

### Code Standards
- **PHP**: PSR-12 coding standards with type hints
- **JavaScript**: ES6+ with consistent formatting
- **React**: Functional components with Hooks
- **Security**: Follow OWASP guidelines for web applications
- **Documentation**: Update README and inline comments for new features
- **Git Commits**: Use conventional commit messages

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙋‍♂️ Support

For questions, issues, or contributions:
- **GitHub Issues**: [Report bugs or request features](https://github.com/angelaqaaa/subtrack/issues)
- **Documentation**: See [ARCHITECTURE.md](ARCHITECTURE.md) for detailed architecture
- **Security Issues**: Please report privately via GitHub Security Advisories

## 🌟 Acknowledgments

- Built with modern web technologies and best practices
- Inspired by the need for better subscription management tools
- Designed for scalability, security, and user experience

---

**Built by Qian (Angela) Su** | **Showcasing modern full-stack development with enterprise-grade security**
