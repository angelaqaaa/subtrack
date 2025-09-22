# SubTrack Frontend

A modern React single-page application for subscription management and analytics.

## Features

- **Modern React Architecture**: Built with React 19, utilizing Context API, custom hooks, and functional components
- **Professional UI Components**: React-Bootstrap integration with responsive design
- **Advanced Authentication**: Two-factor authentication with TOTP, session management, and security features
- **Real-time Collaboration**: Shared spaces with role-based access control and member management
- **Interactive Analytics**: Chart.js visualizations for spending trends and insights
- **Data Export**: PDF generation and comprehensive reporting features
- **Progressive Enhancement**: Works seamlessly with PHP backend while providing modern SPA experience

## Technology Stack

- **React 19.1.1** - Latest React with concurrent features
- **React Router 7.9.1** - Client-side routing
- **React-Bootstrap 2.10.10** - UI component library
- **Chart.js 4.5.0** - Data visualization
- **Axios 1.12.2** - HTTP client with interceptors
- **jsPDF 3.0.2** - PDF generation
- **QRCode 1.5.4** - QR code generation for 2FA

## Architecture

### Component Structure
```
src/
├── components/
│   ├── auth/           # Authentication flows
│   ├── dashboard/      # Main dashboard and widgets
│   ├── profile/        # User profile and security settings
│   ├── settings/       # Application preferences
│   ├── spaces/         # Shared workspace management
│   ├── subscriptions/  # Subscription CRUD operations
│   └── layout/         # Navigation and layout components
├── contexts/           # React Context providers
├── services/           # API integration layer
└── utils/              # Utility functions
```

### State Management
- **AuthContext**: User authentication and session management
- **Local State**: Component-level state with useState/useEffect hooks
- **API Layer**: Centralized axios configuration with request/response interceptors

### Security Features
- **CSRF Protection**: Automatic token handling for form submissions
- **Session Validation**: Persistent authentication across page reloads
- **Two-Factor Authentication**: Complete TOTP implementation with backup codes
- **Secure API Communication**: CORS-enabled requests with credentials

## Development

### Prerequisites
- Node.js 18+ and npm 8+
- Running backend server on `http://localhost:8000`

### Getting Started
```bash
# Install dependencies
npm install

# Start development server
npm start

# Run tests
npm test

# Build for production
npm run build
```

### Environment Configuration
The application expects the backend API at `http://localhost:8000` by default. To customize:

```bash
# Create environment file
cp .env .env.local

# Set custom API URL
echo "REACT_APP_API_URL=http://your-backend-url" >> .env.local
```

### API Integration
The frontend communicates with PHP backend through RESTful endpoints:
- `/api_auth.php` - Authentication and user management
- `/api_dashboard.php` - Dashboard data and insights
- `/api_spaces.php` - Shared workspace operations

All requests include credentials for session-based authentication.

## Production Deployment

### Build Process
```bash
npm run build
```

### Deployment Considerations
- Serve the `build` folder from a web server
- Configure CORS headers on backend for production domain
- Enable HTTPS for secure cookie transmission
- Set appropriate cache headers for static assets

### Performance Optimizations
- Code splitting with React Router
- Lazy loading for non-critical components
- Optimized bundle size with tree shaking
- Progressive loading states for better UX

## Testing

### Test Structure
- **Unit Tests**: Component testing with React Testing Library
- **Integration Tests**: API interaction testing
- **E2E Testing**: User workflow validation

```bash
# Run test suite
npm test

# Run tests with coverage
npm test -- --coverage
```

## Contributing

### Code Standards
- ES6+ JavaScript with functional components
- Consistent prop-types usage
- Material design principles with Bootstrap
- Responsive design for mobile compatibility

### Development Workflow
1. Feature development in isolated components
2. API integration through services layer
3. State management through React Context
4. Testing with React Testing Library
5. Production build validation

## License

MIT License - see main project LICENSE for details