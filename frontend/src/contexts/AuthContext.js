import React, { createContext, useContext, useState, useEffect } from 'react';
import { authAPI } from '../services/api';

const AuthContext = createContext();

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [authChecked, setAuthChecked] = useState(false);

  // Check if user is logged in on app start
  useEffect(() => {
    if (!authChecked) {
      checkAuthStatus();
    }
  }, [authChecked]);

  const checkAuthStatus = async () => {
    // Prevent multiple simultaneous auth checks
    if (authChecked) return;

    try {
      setAuthChecked(true);
      // Try to get current user info using session cookies
      const response = await authAPI.getCurrentUser();
      if (response.status === 'success') {
        setUser(response.data.user);
        setIsAuthenticated(true);
      } else {
        setUser(null);
        setIsAuthenticated(false);
      }
    } catch (error) {
      // Don't log auth check failures as errors - they're expected when not logged in
      if (error.response?.status !== 401) {
        console.error('Auth check failed:', error);
      }
      setUser(null);
      setIsAuthenticated(false);
    } finally {
      setLoading(false);
    }
  };

  const login = async (username, password, rememberMe = false) => {
    try {
      const response = await authAPI.login(username, password);

      if (response.status === 'success') {
        // Session is automatically managed by cookies, no need for localStorage
        setUser(response.data.user);
        setIsAuthenticated(true);
        setAuthChecked(true); // Mark as checked since we just authenticated

        // If remember me is checked, store a flag in localStorage
        if (rememberMe) {
          localStorage.setItem('rememberMe', 'true');
          localStorage.setItem('username', username);
        } else {
          localStorage.removeItem('rememberMe');
          localStorage.removeItem('username');
        }

        return { success: true };
      } else {
        return {
          success: false,
          error: response.message || 'Login failed'
        };
      }
    } catch (error) {
      console.error('Login error:', error);
      return {
        success: false,
        error: error.response?.data?.message || 'Network error. Please try again.'
      };
    }
  };

  const register = async (userData) => {
    try {
      const response = await authAPI.register(userData);

      if (response.status === 'success') {
        return { success: true };
      } else {
        return {
          success: false,
          error: response.message || 'Registration failed'
        };
      }
    } catch (error) {
      console.error('Registration error:', error);
      return {
        success: false,
        error: error.response?.data?.message || 'Network error. Please try again.'
      };
    }
  };

  const logout = async () => {
    try {
      await authAPI.logout();
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      // Clear local state regardless of API call result
      setUser(null);
      setIsAuthenticated(false);
      setAuthChecked(false); // Allow auth check to run again after logout
    }
  };

  const value = {
    user,
    isAuthenticated,
    loading,
    login,
    register,
    logout,
    checkAuthStatus
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
};

export default AuthContext;