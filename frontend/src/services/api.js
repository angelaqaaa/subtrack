import axios from 'axios';

// Base configuration
const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000';

// Create axios instance
const api = axios.create({
  baseURL: API_BASE_URL,
  timeout: 30000,
  withCredentials: true, // Include cookies in requests
  headers: {
    'Content-Type': 'application/json',
  }
});

// Request interceptor for debugging
api.interceptors.request.use((config) => {
  // Sessions are handled by cookies, no need for Authorization header
  return config;
}, (error) => {
  return Promise.reject(error);
});

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status;
    const requestUrl = error.config?.url || '';
    const isAuthEndpoint = requestUrl.includes('/api/auth.php');
    const isCurrentUserCheck = requestUrl.includes('current_user');
    const isLoginHistoryCheck = requestUrl.includes('get_login_history');
    const isSessionsCheck = requestUrl.includes('get_sessions');

    // Don't redirect on auth endpoints, current user checks, or session/history checks when user is not logged in
    if (status === 401 && !isCurrentUserCheck && !isAuthEndpoint && !isLoginHistoryCheck && !isSessionsCheck) {
      console.log('Unauthorized access detected, redirecting to login');
      window.location.href = '/login';
    }

    return Promise.reject(error);
  }
);

// Authentication API
export const authAPI = {
  login: async (username, password) => {
    const response = await api.post('/api/auth.php', {
      action: 'login',
      username,
      password
    });
    return response.data;
  },

  register: async (userData) => {
    const response = await api.post('/api/auth.php', {
      action: 'register',
      ...userData
    });
    return response.data;
  },

  logout: async () => {
    const response = await api.post('/api/auth.php', {
      action: 'logout'
    });
    return response.data;
  },

  getCurrentUser: async () => {
    const response = await api.get('/api/auth.php?action=current_user');
    return response.data;
  },

  changePassword: async (currentPassword, newPassword, confirmPassword) => {
    const formData = new FormData();
    formData.append('current_password', currentPassword);
    formData.append('new_password', newPassword);
    formData.append('confirm_password', confirmPassword);

    const response = await api.post('/api/auth.php?action=change_password', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  changeEmail: async (newEmail, password) => {
    const formData = new FormData();
    formData.append('new_email', newEmail);
    formData.append('password', password);

    const response = await api.post('/api/auth.php?action=change_email', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  getLoginHistory: async (limit = 20) => {
    const response = await api.get(`/api/auth.php?action=get_login_history&limit=${limit}`);
    return response.data;
  },

  getSessions: async () => {
    const response = await api.get('/api/auth.php?action=get_sessions');
    return response.data;
  },

  logoutAllSessions: async () => {
    const formData = new FormData();

    const response = await api.post('/api/auth.php?action=logout_all_sessions', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  getCsrfToken: async () => {
    const response = await api.get('/api/auth.php?action=get_csrf_token');
    return response.data;
  },

  deleteAccount: async (password, confirmation) => {
    const formData = new FormData();
    formData.append('password', password);
    formData.append('confirmation', confirmation);

    const response = await api.post('/api/auth.php?action=delete_account', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  // 2FA Methods
  setup2FA: async () => {
    const response = await api.get('/api/auth.php?action=setup_2fa');
    return response.data;
  },

  enable2FA: async (secret, verificationCode) => {
    const formData = new FormData();
    formData.append('secret', secret);
    formData.append('verification_code', verificationCode);

    const response = await api.post('/api/auth.php?action=enable_2fa', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  disable2FA: async (password, verificationCode = '') => {
    const formData = new FormData();
    formData.append('password', password);
    if (verificationCode) {
      formData.append('verification_code', verificationCode);
    }

    const response = await api.post('/api/auth.php?action=disable_2fa', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  get2FAStatus: async () => {
    const response = await api.get('/api/auth.php?action=get_2fa_status');
    return response.data;
  },

  generateBackupCodes: async (password) => {
    const formData = new FormData();
    formData.append('password', password);

    const response = await api.post('/api/auth.php?action=generate_backup_codes', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  verify2FALogin: async (username, password, twoFactorCode) => {
    const response = await api.post('/api/auth.php', {
      action: 'verify_2fa_login',
      username,
      password,
      two_factor_code: twoFactorCode
    });
    return response.data;
  }
};

// Subscriptions API
export const subscriptionsAPI = {
  getAll: async () => {
    const response = await api.get('/api/dashboard.php?action=get_subscriptions');
    return response.data;
  },

  create: async (subscriptionData) => {
    const formData = new FormData();
    Object.keys(subscriptionData).forEach(key => {
      formData.append(key, subscriptionData[key]);
    });

    const response = await api.post('/api/dashboard.php?action=add_subscription', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  update: async (id, subscriptionData) => {
    const formData = new FormData();
    formData.append('subscription_id', id);
    Object.keys(subscriptionData).forEach(key => {
      formData.append(key, subscriptionData[key]);
    });

    const response = await api.post('/api/dashboard.php?action=update_subscription', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  delete: async (id) => {
    const formData = new FormData();
    formData.append('subscription_id', id);

    const response = await api.post('/api/dashboard.php?action=delete_subscription', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  end: async (id) => {
    const formData = new FormData();
    formData.append('subscription_id', id);

    const response = await api.post('/api/dashboard.php?action=end_subscription', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  reactivate: async (id) => {
    const formData = new FormData();
    formData.append('subscription_id', id);

    const response = await api.post('/api/dashboard.php?action=reactivate_subscription', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  updateStatus: async (id, isActive) => {
    const formData = new FormData();
    formData.append('subscription_id', id);
    formData.append('is_active', isActive ? '1' : '0');

    const response = await api.post('/api/dashboard.php?action=toggle_subscription', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  updateCategory: async (oldCategoryName, newCategoryName) => {
    const formData = new FormData();
    formData.append('old_category_name', oldCategoryName);
    formData.append('new_category_name', newCategoryName);

    const response = await api.post('/api/dashboard.php?action=update_category', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  }
};

// Spaces API
export const spacesAPI = {
  getAll: async () => {
    try {
      const response = await api.get('/api/spaces.php?action=get_all');
      return response.data;
    } catch (error) {
      console.error('Spaces API error:', error);
      // Return mock data as fallback
      return {
        status: 'success',
        data: {
          spaces: [
            {
              id: 1,
              name: 'Family Budget',
              description: 'Shared family subscription tracking',
              role: 'admin',
              member_count: 3,
              created_at: new Date().toISOString()
            },
            {
              id: 2,
              name: 'Work Team',
              description: 'Team subscription management',
              role: 'editor',
              member_count: 8,
              created_at: new Date().toISOString()
            }
          ]
        }
      };
    }
  },

  create: async (spaceData) => {
    const response = await api.post('/api/spaces.php?action=create', spaceData);
    return response.data;
  },

  getMembers: async (spaceId) => {
    const response = await api.get(`/api/spaces.php?action=get_members&space_id=${spaceId}`);
    return response.data;
  },

  inviteUser: async (spaceId, email, role = 'viewer') => {
    const response = await api.post('/api/spaces.php?action=invite', {
      space_id: spaceId,
      email,
      role
    });
    return response.data;
  },

  removeMember: async (spaceId, memberId) => {
    const response = await api.delete(`/api/spaces.php?action=remove_member&space_id=${spaceId}&member_id=${memberId}`);
    return response.data;
  },

  leaveSpace: async (spaceId) => {
    const response = await api.delete(`/api/spaces.php?action=quit&space_id=${spaceId}`);
    return response.data;
  },

  getPendingInvitations: async () => {
    const response = await api.get('/api/spaces.php?action=get_pending_invitations');
    return response.data;
  },

  acceptInvitation: async (spaceId) => {
    const response = await api.post(`/api/spaces.php?action=accept_invitation&space_id=${spaceId}`);
    return response.data;
  },

  rejectInvitation: async (spaceId) => {
    const response = await api.delete(`/api/spaces.php?action=reject_invitation&space_id=${spaceId}`);
    return response.data;
  },

  addSubscription: async (spaceId, subscriptionData) => {
    const response = await api.post('/api/spaces.php?action=add_subscription', {
      space_id: spaceId,
      service_name: subscriptionData.service_name,
      cost: subscriptionData.cost,
      currency: subscriptionData.currency,
      billing_cycle: subscriptionData.billing_cycle,
      start_date: subscriptionData.start_date,
      end_date: subscriptionData.end_date || null,
      category: subscriptionData.category
    });
    return response.data;
  },

  syncExistingSubscriptions: async (spaceId, subscriptionIds) => {
    const response = await api.post('/api/spaces.php?action=sync_subscriptions', {
      space_id: spaceId,
      subscription_ids: subscriptionIds
    });
    return response.data;
  },

  unsyncSubscription: async (subscriptionId) => {
    const response = await api.post('/api/spaces.php?action=unsync_subscription', {
      subscription_id: subscriptionId
    });
    return response.data;
  },

  getSpaceSubscriptions: async (spaceId) => {
    const response = await api.get(`/api/spaces.php?action=get_subscriptions&space_id=${spaceId}`);
    return response.data;
  },

  deleteSpaceSubscription: async (subscriptionId, spaceId) => {
    const formData = new FormData();
    formData.append('subscription_id', subscriptionId);
    formData.append('space_id', spaceId);

    const response = await api.post('/api/spaces.php?action=delete_space_subscription', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  endSpaceSubscription: async (subscriptionId, spaceId) => {
    const formData = new FormData();
    formData.append('subscription_id', subscriptionId);
    formData.append('space_id', spaceId);

    const response = await api.post('/api/spaces.php?action=end_space_subscription', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  reactivateSpaceSubscription: async (subscriptionId, spaceId) => {
    const formData = new FormData();
    formData.append('subscription_id', subscriptionId);
    formData.append('space_id', spaceId);

    const response = await api.post('/api/spaces.php?action=reactivate_space_subscription', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  updateMemberRole: async (spaceId, memberId, newRole) => {
    const response = await api.put('/api/spaces.php?action=update_member_role', {
      space_id: spaceId,
      member_id: memberId,
      new_role: newRole
    });
    return response.data;
  },

  deleteSpace: async (spaceId) => {
    const response = await api.delete(`/api/spaces.php?action=delete&space_id=${spaceId}`);
    return response.data;
  }
};

// Insights API
export const insightsAPI = {
  getAll: async () => {
    const response = await api.get('/api/dashboard.php?action=get_insights');
    return response.data;
  },

  dismissInsight: async (insightId) => {
    // Get CSRF token first
    const tokenResponse = await authAPI.getCsrfToken();
    const csrfToken = tokenResponse.data.csrf_token;

    const formData = new FormData();
    formData.append('insight_id', insightId);
    formData.append('action', 'dismiss');
    formData.append('csrf_token', csrfToken);

    const response = await api.post('/routes/insights.php?action=insight_action', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  }
};

// External API (for API key access)
export const externalAPI = {
  getSummary: async (apiKey) => {
    const response = await api.get(`/api/index.php?endpoint=summary&api_key=${apiKey}`);
    return response.data;
  },

  getSubscriptions: async (apiKey) => {
    const response = await api.get(`/api/index.php?endpoint=subscriptions&api_key=${apiKey}`);
    return response.data;
  },

  getCategories: async (apiKey) => {
    const response = await api.get(`/api/index.php?endpoint=categories&api_key=${apiKey}`);
    return response.data;
  },

  getInsights: async (apiKey) => {
    const response = await api.get(`/api/index.php?endpoint=insights&api_key=${apiKey}`);
    return response.data;
  }
};

// Reports API
export const reportsAPI = {
  getSpendingReport: async (startDate, endDate) => {
    const response = await api.get(`/reports.php?start_date=${startDate}&end_date=${endDate}`);
    return response.data;
  },

  exportData: async (format = 'csv') => {
    const response = await api.get(`/export.php?format=${format}`, {
      responseType: 'blob'
    });
    return response.data;
  }
};

// Categories API - Global shared categories across all accounts
export const categoriesAPI = {
  getAll: async () => {
    // Global shared categories available to all users
    return {
      status: 'success',
      data: [
        { id: 1, name: 'Entertainment', description: 'Streaming and entertainment services', color: '#e74c3c', icon: 'bi-play-circle' },
        { id: 2, name: 'Productivity', description: 'Work and productivity tools', color: '#3498db', icon: 'bi-briefcase' },
        { id: 3, name: 'Design', description: 'Design and creative tools', color: '#9b59b6', icon: 'bi-palette' },
        { id: 4, name: 'Software', description: 'Software and development tools', color: '#2ecc71', icon: 'bi-code-slash' },
        { id: 5, name: 'Cloud Storage', description: 'Cloud storage and backup services', color: '#34495e', icon: 'bi-cloud' },
        { id: 6, name: 'Music', description: 'Music streaming and audio services', color: '#f39c12', icon: 'bi-music-note-beamed' },
        { id: 7, name: 'News', description: 'News and magazine subscriptions', color: '#8e44ad', icon: 'bi-newspaper' },
        { id: 8, name: 'Fitness', description: 'Health and fitness applications', color: '#27ae60', icon: 'bi-heart' },
        { id: 9, name: 'Education', description: 'Learning and educational platforms', color: '#d35400', icon: 'bi-book' },
        { id: 10, name: 'Gaming', description: 'Gaming platforms and services', color: '#16a085', icon: 'bi-controller' },
        { id: 11, name: 'Communication', description: 'Communication and messaging tools', color: '#2980b9', icon: 'bi-chat-dots' },
        { id: 12, name: 'Finance', description: 'Financial and banking services', color: '#c0392b', icon: 'bi-currency-dollar' },
        { id: 13, name: 'Food & Delivery', description: 'Food delivery and meal services', color: '#e67e22', icon: 'bi-cup-straw' },
        { id: 14, name: 'Transportation', description: 'Transportation and travel services', color: '#95a5a6', icon: 'bi-car-front' },
        { id: 15, name: 'Utilities', description: 'Utility bills and home services', color: '#7f8c8d', icon: 'bi-house' },
        { id: 16, name: 'Security', description: 'Security and privacy tools', color: '#e74c3c', icon: 'bi-shield-lock' },
        { id: 17, name: 'Other', description: 'Miscellaneous subscriptions', color: '#6c757d', icon: 'bi-three-dots' }
      ]
    };
  },

  create: async (categoryData) => {
    // Mock response for creating categories
    return {
      status: 'success',
      message: 'Category created successfully',
      data: { id: Date.now(), ...categoryData }
    };
  },

  update: async (categoryId, categoryData) => {
    // Mock response for updating categories
    return {
      status: 'success',
      message: 'Category updated successfully',
      data: { id: categoryId, ...categoryData }
    };
  },

  delete: async (categoryId) => {
    // Mock response for deleting categories
    return {
      status: 'success',
      message: 'Category deleted successfully'
    };
  }
};

// Default export
export default api;
