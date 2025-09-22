export const ActivityTypes = {
  SUBSCRIPTION_ADDED: 'subscription_added',
  SUBSCRIPTION_UPDATED: 'subscription_updated',
  SUBSCRIPTION_DELETED: 'subscription_deleted',
  CATEGORY_ADDED: 'category_added',
  CATEGORY_UPDATED: 'category_updated',
  CATEGORY_DELETED: 'category_deleted',
  SPACE_CREATED: 'space_created',
  SPACE_JOINED: 'space_joined',
  SPACE_LEFT: 'space_left',
  PROFILE_UPDATED: 'profile_updated'
};

export const ActivityLogger = {
  log: (type, details, metadata = {}, userId = null) => {
    try {
      // Allow passing userId directly, otherwise try to get from user context stored in localStorage
      let finalUserId = userId;
      if (!finalUserId) {
        try {
          // Note: In actual usage, the calling component should pass the user ID from AuthContext
          const user = JSON.parse(localStorage.getItem('user') || '{}');
          finalUserId = user?.id || 'unknown';
        } catch {
          finalUserId = 'unknown';
        }
      }
      const userActivitiesKey = `userActivities_${finalUserId}`;

      const activities = JSON.parse(localStorage.getItem(userActivitiesKey) || '[]');

      const newActivity = {
        id: Date.now() + Math.random(),
        type,
        details,
        metadata,
        timestamp: new Date().toISOString(),
        date: new Date().toISOString().split('T')[0]
      };

      activities.unshift(newActivity);

      // Keep only last 50 activities
      const trimmedActivities = activities.slice(0, 50);

      localStorage.setItem(userActivitiesKey, JSON.stringify(trimmedActivities));

      return newActivity;
    } catch (err) {
      console.error('Failed to log activity:', err);
    }
  },

  getActivities: (limit = 10, userId = null) => {
    try {
      // Allow passing userId directly, otherwise try to get from user context
      let finalUserId = userId;
      if (!finalUserId) {
        try {
          const user = JSON.parse(localStorage.getItem('user') || '{}');
          finalUserId = user?.id || 'unknown';
        } catch {
          finalUserId = 'unknown';
        }
      }
      const userActivitiesKey = `userActivities_${finalUserId}`;

      const activities = JSON.parse(localStorage.getItem(userActivitiesKey) || '[]');
      return activities.slice(0, limit);
    } catch (err) {
      console.error('Failed to get activities:', err);
      return [];
    }
  },

  clearActivities: () => {
    try {
      localStorage.removeItem('userActivities');
    } catch (err) {
      console.error('Failed to clear activities:', err);
    }
  },

  formatActivityMessage: (activity) => {
    switch (activity.type) {
      case ActivityTypes.SUBSCRIPTION_ADDED:
        return `Added subscription "${activity.details.service_name}"`;
      case ActivityTypes.SUBSCRIPTION_UPDATED:
        return `Updated subscription "${activity.details.service_name}"`;
      case ActivityTypes.SUBSCRIPTION_DELETED:
        return `Deleted subscription "${activity.details.service_name}"`;
      case ActivityTypes.CATEGORY_ADDED:
        return `Created category "${activity.details.name}"`;
      case ActivityTypes.CATEGORY_UPDATED:
        return `Updated category "${activity.details.name}"`;
      case ActivityTypes.CATEGORY_DELETED:
        return `Deleted category "${activity.details.name}"`;
      case ActivityTypes.SPACE_CREATED:
        return `Created space "${activity.details.name}"`;
      case ActivityTypes.SPACE_JOINED:
        return `Joined space "${activity.details.name}"`;
      case ActivityTypes.SPACE_LEFT:
        return `Left space "${activity.details.name}"`;
      case ActivityTypes.PROFILE_UPDATED:
        return 'Updated profile information';
      default:
        return 'Unknown activity';
    }
  },

  getActivityIcon: (activity) => {
    switch (activity.type) {
      case ActivityTypes.SUBSCRIPTION_ADDED:
        return 'bi-plus-circle text-success';
      case ActivityTypes.SUBSCRIPTION_UPDATED:
        return 'bi-pencil text-primary';
      case ActivityTypes.SUBSCRIPTION_DELETED:
        return 'bi-trash text-danger';
      case ActivityTypes.CATEGORY_ADDED:
        return 'bi-tag text-success';
      case ActivityTypes.CATEGORY_UPDATED:
        return 'bi-tag text-primary';
      case ActivityTypes.CATEGORY_DELETED:
        return 'bi-tag text-danger';
      case ActivityTypes.SPACE_CREATED:
        return 'bi-building text-success';
      case ActivityTypes.SPACE_JOINED:
        return 'bi-door-open text-success';
      case ActivityTypes.SPACE_LEFT:
        return 'bi-door-closed text-warning';
      case ActivityTypes.PROFILE_UPDATED:
        return 'bi-person-check text-primary';
      default:
        return 'bi-activity text-muted';
    }
  }
};