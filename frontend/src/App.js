import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { Button } from 'react-bootstrap';
import { AuthProvider } from './contexts/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import Navigation from './components/layout/Navigation';
import Login from './components/auth/Login';
import Register from './components/auth/Register';
import Dashboard from './components/dashboard/Dashboard';
import SubscriptionsPage from './components/subscriptions/SubscriptionsPage';
import SpacesPage from './components/spaces/SpacesPage';
import SpaceDetailPage from './components/spaces/SpaceDetailPage';
import InsightsPage from './components/insights/InsightsPage';
import ReportsPage from './components/reports/ReportsPage';
import SettingsPage from './components/settings/SettingsPage';
import ProfilePage from './components/profile/ProfilePage';
import CategoriesPage from './components/categories/CategoriesPage';
import ApiKeysPage from './components/apikeys/ApiKeysPage';

// Import Bootstrap CSS
import 'bootstrap/dist/css/bootstrap.min.css';

function App() {
  return (
    <AuthProvider>
      <Router>
        <div className="App">
          <Routes>
            {/* Public Routes */}
            <Route path="/login" element={<Login />} />
            <Route path="/register" element={<Register />} />

            {/* Protected Routes */}
            <Route path="/dashboard" element={
              <ProtectedRoute>
                <Navigation />
                <Dashboard />
              </ProtectedRoute>
            } />

            <Route path="/subscriptions" element={
              <ProtectedRoute>
                <Navigation />
                <SubscriptionsPage />
              </ProtectedRoute>
            } />

            <Route path="/spaces" element={
              <ProtectedRoute>
                <Navigation />
                <SpacesPage />
              </ProtectedRoute>
            } />

            <Route path="/spaces/:spaceId" element={
              <ProtectedRoute>
                <Navigation />
                <SpaceDetailPage />
              </ProtectedRoute>
            } />

            <Route path="/insights" element={
              <ProtectedRoute>
                <Navigation />
                <InsightsPage />
              </ProtectedRoute>
            } />

            <Route path="/reports" element={
              <ProtectedRoute>
                <Navigation />
                <ReportsPage />
              </ProtectedRoute>
            } />

            <Route path="/profile" element={
              <ProtectedRoute>
                <Navigation />
                <ProfilePage />
              </ProtectedRoute>
            } />

            <Route path="/settings" element={
              <ProtectedRoute>
                <Navigation />
                <SettingsPage />
              </ProtectedRoute>
            } />

            <Route path="/categories" element={
              <ProtectedRoute>
                <Navigation />
                <CategoriesPage />
              </ProtectedRoute>
            } />

            <Route path="/api-keys" element={
              <ProtectedRoute>
                <Navigation />
                <ApiKeysPage />
              </ProtectedRoute>
            } />

            {/* Default redirect */}
            <Route path="/" element={<Navigate to="/dashboard" replace />} />
            <Route path="*" element={<Navigate to="/dashboard" replace />} />
          </Routes>
        </div>
      </Router>
    </AuthProvider>
  );
}

export default App;
