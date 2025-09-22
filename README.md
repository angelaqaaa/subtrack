# SubTrack 🚀

> **A comprehensive subscription management platform featuring advanced security, collaborative workspaces, and modern full-stack architecture.**

[![React](https://img.shields.io/badge/React-19.1.1-blue?logo=react)](https://reactjs.org/)
[![PHP](https://img.shields.io/badge/PHP-8%2B-purple?logo=php)](https://php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-orange?logo=mysql)](https://mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple?logo=bootstrap)](https://getbootstrap.com/)

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
git clone https://github.com/yourusername/subtrack.git
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
# Adjust API URL if needed
```

3. **Setup database**
```bash
mysql -u root -p -e "CREATE DATABASE subtrack_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations in order
mysql -u root -p subtrack_db < database/migrations/database_setup.sql
mysql -u root -p subtrack_db < database/migrations/improvements_schema.sql
mysql -u root -p subtrack_db < database/migrations/phase9_schema.sql
mysql -u root -p subtrack_db < database/migrations/phase9_schema_fixed.sql
mysql -u root -p subtrack_db < database/migrations/phase10_schema.sql
mysql -u root -p subtrack_db < database/migrations/phase10_schema_fixed.sql
mysql -u root -p subtrack_db < database/migrations/phase11_schema.sql
mysql -u root -p subtrack_db < database/migrations/fix_missing_columns.sql
mysql -u root -p subtrack_db < database/migrations/add_api_key_migration.sql
mysql -u root -p subtrack_db < database/migrations/database_2fa_migration.sql
mysql -u root -p subtrack_db < database/migrations/missing_tables_migration.sql
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
- **API Endpoints**: http://localhost:8000/api_*.php

## 🏗️ Architecture

### Technology Stack

**Frontend**
- **React 19.1.1** with Hooks and Context API
- **React Router 7.9.1** for client-side routing
- **React-Bootstrap 2.10.10** for responsive UI components
- **Chart.js 4.5.0** for interactive data visualizations
- **Axios 1.12.2** with request/response interceptors
- **jsPDF 3.0.2** for PDF generation and exports

**Backend**
- **PHP 8+** with modern OOP patterns and MVC architecture
- **PDO** with prepared statements for secure database access
- **Custom CSRF Handler** with timing attack protection
- **Session Security** with HttpOnly, Secure, and SameSite cookies
- **TOTP Algorithm** for two-factor authentication

**Database**
- **MySQL 8.0+** with InnoDB engine
- **Versioned Migrations** for schema management
- **Foreign Key Constraints** for data integrity
- **Optimized Indexes** for query performance

### Project Structure
```
├── 📁 frontend/                 # React SPA application
│   ├── 📁 src/components/       # React components by feature
│   ├── 📁 src/contexts/         # React Context providers
│   ├── 📁 src/services/         # API integration layer
│   └── 📄 package.json          # Frontend dependencies
├── 📁 src/                      # PHP backend MVC structure
│   ├── 📁 Controllers/          # Business logic controllers
│   ├── 📁 Models/              # Data access models
│   ├── 📁 Views/               # Server-rendered templates
│   ├── 📁 Config/              # Configuration classes
│   └── 📁 Utils/               # Utility classes (audit, etc.)
├── 📁 database/migrations/      # Versioned SQL schema files
├── 📁 tests/                   # PHP testing utilities
├── 📄 api_*.php                # RESTful API endpoints
├── 📄 *.php                    # Server-rendered pages
└── 📄 TESTING_GUIDE.md         # Comprehensive testing procedures
```

## 🔐 Security Features

### Authentication & Authorization
- **Multi-factor Authentication**: TOTP-based 2FA with QR code setup
- **Secure Password Handling**: bcrypt hashing with salt
- **Session Management**: Secure cookies with regeneration
- **CSRF Protection**: Token-based with timing attack prevention
- **Role-Based Access**: Granular permissions for shared spaces

### Data Protection
- **SQL Injection Prevention**: PDO prepared statements
- **XSS Protection**: Input sanitization and output encoding
- **Environment Variables**: Secure credential management
- **Audit Logging**: Comprehensive activity tracking
- **Rate Limiting**: API endpoint protection

## 📊 API Documentation

### Authentication Endpoints (`/api_auth.php`)
```http
POST /api_auth.php?action=login
POST /api_auth.php?action=register
POST /api_auth.php?action=setup_2fa
POST /api_auth.php?action=enable_2fa
GET  /api_auth.php?action=current_user
```

### Dashboard Endpoints (`/api_dashboard.php`)
```http
GET  /api_dashboard.php?action=get_summary
GET  /api_dashboard.php?action=get_subscriptions
GET  /api_dashboard.php?action=get_insights
POST /api_dashboard.php?action=add_subscription
```

### Spaces Endpoints (`/api_spaces.php`)
```http
GET  /api_spaces.php?action=get_all
POST /api_spaces.php?action=create
POST /api_spaces.php?action=invite
GET  /api_spaces.php?action=get_members
```

### Example API Usage
```javascript
// Authentication with 2FA
const response = await fetch('/api_auth.php?action=login', {
  method: 'POST',
  credentials: 'include',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    username: 'user@example.com',
    password: 'securepassword',
    two_factor_code: '123456'
  })
});
```

## 🚀 Deployment

### Production Environment
```bash
# Build React application
cd frontend && npm run build

# Configure web server to serve frontend/build
# Update CORS headers for production domain
# Enable HTTPS for secure cookies
# Set environment variables for production
```

### Docker Deployment (Optional)
```dockerfile
# Example Dockerfile structure
FROM php:8.1-apache
# Configure PHP extensions and Apache
# Copy application files
# Set up production environment
```

### Security Checklist
- [ ] Update CORS origins for production domain
- [ ] Enable `session.cookie_secure=1` for HTTPS
- [ ] Set strong `CSRF_SECRET` in environment
- [ ] Configure database with restricted user permissions
- [ ] Enable error logging and monitoring
- [ ] Set up automated backups for database and audit logs

## 🧪 Testing

### Automated Testing
```bash
# PHP unit tests
php tests/test_api.php
php tests/test_audit.php

# React component tests
cd frontend && npm test

# Integration testing
# Follow TESTING_GUIDE.md for comprehensive scenarios
```

### Manual Testing Guide
Comprehensive testing procedures are documented in [`TESTING_GUIDE.md`](TESTING_GUIDE.md), covering:
- Authentication flows with 2FA
- Multi-user collaboration scenarios
- Error handling and edge cases
- Cross-browser compatibility
- Performance benchmarking

## 🤝 Contributing

### Development Workflow
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Follow the testing procedures in `TESTING_GUIDE.md`
4. Commit changes with descriptive messages
5. Push to your branch and open a Pull Request

### Code Standards
- **PHP**: PSR-12 coding standards with type hints
- **JavaScript**: ES6+ with consistent formatting
- **Security**: Follow OWASP guidelines for web applications
- **Documentation**: Update README and inline comments for new features

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙋‍♂️ Support

For questions, issues, or contributions:
- **GitHub Issues**: [Report bugs or request features](https://github.com/yourusername/subtrack/issues)
- **Documentation**: Check `TESTING_GUIDE.md` for troubleshooting
- **Security Issues**: Please report privately via email

---

**Built by Qian (Angela) Su** | **Showcasing modern full-stack development with enterprise-grade security**
