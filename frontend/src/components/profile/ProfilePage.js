import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Form, Button, Alert, Badge, Table, Modal } from 'react-bootstrap';
import { parseISO, format, formatDistanceToNow } from 'date-fns';
import { useAuth } from '../../contexts/AuthContext';
import { subscriptionsAPI } from '../../services/api';
import { ActivityLogger } from '../../utils/activityLogger';

const ProfilePage = () => {
  const { user } = useAuth();
  const [profile, setProfile] = useState({
    username: user?.username || '',
    email: '',
    firstName: '',
    lastName: '',
    bio: '',
    avatar: null,
    location: '',
    website: '',
    joinDate: new Date().toISOString(),
    timezone: 'UTC'
  });

  const [statistics, setStatistics] = useState({
    totalSubscriptions: 0,
    activeSubscriptions: 0,
    monthlySpend: 0,
    annualSpend: 0,
    averageSubscriptionCost: 0,
    mostExpensiveSubscription: null,
    oldestSubscription: null
  });

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');
  const [isEditing, setIsEditing] = useState(false);
  const [recentActivities, setRecentActivities] = useState([]);
  const [showSecurityModal, setShowSecurityModal] = useState(false);
  const [exportLoading, setExportLoading] = useState(false);

  useEffect(() => {
    loadProfileData();
    loadUserStatistics();
    loadRecentActivities();
  }, []);

  const loadProfileData = async () => {
    try {
      // In a real app, this would fetch from the backend
      const savedProfile = localStorage.getItem('userProfile');
      if (savedProfile) {
        setProfile({ ...profile, ...JSON.parse(savedProfile) });
      }
    } catch (err) {
      console.error('Failed to load profile:', err);
    }
  };

  const loadUserStatistics = async () => {
    try {
      const response = await subscriptionsAPI.getAll();
      if (response.status === 'success') {
        const subscriptions = response.data?.subscriptions || [];
        calculateStatistics(subscriptions);
      }
    } catch (err) {
      console.error('Failed to load statistics:', err);
    }
  };

  const loadRecentActivities = () => {
    const activities = ActivityLogger.getActivities(8, user?.id);
    setRecentActivities(activities);
  };

  const calculateStatistics = (subscriptions) => {
    const activeSubscriptions = subscriptions.filter(sub => sub.is_active);

    let monthlySpend = 0;
    activeSubscriptions.forEach(sub => {
      if (sub.billing_cycle === 'monthly') {
        monthlySpend += parseFloat(sub.cost);
      } else {
        monthlySpend += parseFloat(sub.cost) / 12;
      }
    });

    const annualSpend = monthlySpend * 12;
    const averageSubscriptionCost = activeSubscriptions.length > 0 ? monthlySpend / activeSubscriptions.length : 0;

    // Find most expensive subscription
    const mostExpensive = subscriptions.reduce((max, sub) => {
      const monthlyCost = sub.billing_cycle === 'monthly' ? parseFloat(sub.cost) : parseFloat(sub.cost) / 12;
      const maxMonthlyCost = max && max.billing_cycle === 'monthly' ? parseFloat(max.cost) : (max ? parseFloat(max.cost) / 12 : 0);
      return monthlyCost > maxMonthlyCost ? sub : max;
    }, null);

    // Find oldest subscription
    const oldestSubscription = subscriptions.reduce((oldest, sub) => {
      return !oldest || new Date(sub.start_date) < new Date(oldest.start_date) ? sub : oldest;
    }, null);

    setStatistics({
      totalSubscriptions: subscriptions.length,
      activeSubscriptions: activeSubscriptions.length,
      monthlySpend,
      annualSpend,
      averageSubscriptionCost,
      mostExpensiveSubscription: mostExpensive,
      oldestSubscription
    });
  };

  const handleProfileChange = (field, value) => {
    setProfile(prev => ({
      ...prev,
      [field]: value
    }));
  };

  const handleSaveProfile = async () => {
    try {
      setLoading(true);
      setError('');

      // In a real app, this would save to the backend
      localStorage.setItem('userProfile', JSON.stringify(profile));

      // Log activity
      ActivityLogger.log('profile_updated', { username: profile.username });
      loadRecentActivities();

      setSuccessMessage('Profile updated successfully!');
      setIsEditing(false);
      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (err) {
      console.error('Save profile error:', err);
      setError('Failed to save profile. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(amount);
  };

  const formatDate = (dateString) => {
    try {
      // Use parseISO to avoid timezone issues with date strings
      return format(parseISO(dateString + 'T00:00:00'), 'MMMM dd, yyyy');
    } catch {
      return dateString;
    }
  };

  const handleSecuritySettings = () => {
    setShowSecurityModal(true);
  };

  const handleExportData = async () => {
    try {
      setExportLoading(true);

      // Simulate export process
      setTimeout(() => {
        const exportData = {
          profile: profile,
          statistics: statistics,
          activities: recentActivities,
          exportDate: new Date().toISOString()
        };

        const dataStr = JSON.stringify(exportData, null, 2);
        const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);

        const exportFileDefaultName = `subtrack-data-${new Date().toISOString().split('T')[0]}.json`;

        const linkElement = document.createElement('a');
        linkElement.setAttribute('href', dataUri);
        linkElement.setAttribute('download', exportFileDefaultName);
        linkElement.click();

        setSuccessMessage('Data exported successfully!');
        setTimeout(() => setSuccessMessage(''), 3000);
        setExportLoading(false);
      }, 2000);

    } catch (err) {
      console.error('Export error:', err);
      setError('Failed to export data. Please try again.');
      setExportLoading(false);
    }
  };

  return (
    <Container>
      <Row>
        <Col>
          <div className="d-flex justify-content-between align-items-center mb-4">
            <h1 className="h2">
              <i className="bi bi-person-circle me-2"></i>
              Profile
            </h1>
            <Button
              variant={isEditing ? "success" : "primary"}
              onClick={isEditing ? handleSaveProfile : () => setIsEditing(true)}
              disabled={loading}
            >
              {loading ? (
                <>
                  <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                  Saving...
                </>
              ) : isEditing ? (
                <>
                  <i className="bi bi-check-circle me-2"></i>
                  Save Changes
                </>
              ) : (
                <>
                  <i className="bi bi-pencil me-2"></i>
                  Edit Profile
                </>
              )}
            </Button>
          </div>

          {successMessage && (
            <Alert variant="success" dismissible onClose={() => setSuccessMessage('')}>
              <i className="bi bi-check-circle me-2"></i>
              {successMessage}
            </Alert>
          )}

          {error && (
            <Alert variant="danger" dismissible onClose={() => setError('')}>
              <i className="bi bi-exclamation-triangle me-2"></i>
              {error}
            </Alert>
          )}
        </Col>
      </Row>

      <Row>
        {/* Profile Information */}
        <Col lg={8}>
          <Card className="mb-4">
            <Card.Header>
              <h5 className="mb-0">
                <i className="bi bi-person-vcard me-2"></i>
                Profile Information
              </h5>
            </Card.Header>
            <Card.Body>
              <Row>
                <Col md={6}>
                  <Form.Group className="mb-3">
                    <Form.Label>Username</Form.Label>
                    <Form.Control
                      type="text"
                      value={profile.username}
                      onChange={(e) => handleProfileChange('username', e.target.value)}
                      disabled={!isEditing}
                    />
                  </Form.Group>

                  <Form.Group className="mb-3">
                    <Form.Label>Email Address</Form.Label>
                    <Form.Control
                      type="email"
                      value={profile.email}
                      onChange={(e) => handleProfileChange('email', e.target.value)}
                      disabled={!isEditing}
                    />
                  </Form.Group>

                  <Form.Group className="mb-3">
                    <Form.Label>First Name</Form.Label>
                    <Form.Control
                      type="text"
                      value={profile.firstName}
                      onChange={(e) => handleProfileChange('firstName', e.target.value)}
                      disabled={!isEditing}
                    />
                  </Form.Group>

                  <Form.Group className="mb-3">
                    <Form.Label>Last Name</Form.Label>
                    <Form.Control
                      type="text"
                      value={profile.lastName}
                      onChange={(e) => handleProfileChange('lastName', e.target.value)}
                      disabled={!isEditing}
                    />
                  </Form.Group>
                </Col>

                <Col md={6}>
                  <Form.Group className="mb-3">
                    <Form.Label>Location</Form.Label>
                    <Form.Control
                      type="text"
                      value={profile.location}
                      onChange={(e) => handleProfileChange('location', e.target.value)}
                      disabled={!isEditing}
                      placeholder="City, Country"
                    />
                  </Form.Group>

                  <Form.Group className="mb-3">
                    <Form.Label>Website</Form.Label>
                    <Form.Control
                      type="url"
                      value={profile.website}
                      onChange={(e) => handleProfileChange('website', e.target.value)}
                      disabled={!isEditing}
                      placeholder="https://your-website.com"
                    />
                  </Form.Group>

                  <Form.Group className="mb-3">
                    <Form.Label>Timezone</Form.Label>
                    <Form.Select
                      value={profile.timezone}
                      onChange={(e) => handleProfileChange('timezone', e.target.value)}
                      disabled={!isEditing}
                    >
                      <option value="UTC">UTC</option>
                      <option value="America/New_York">Eastern Time (ET)</option>
                      <option value="America/Chicago">Central Time (CT)</option>
                      <option value="America/Denver">Mountain Time (MT)</option>
                      <option value="America/Los_Angeles">Pacific Time (PT)</option>
                      <option value="Europe/London">London (GMT)</option>
                      <option value="Europe/Paris">Paris (CET)</option>
                      <option value="Asia/Tokyo">Tokyo (JST)</option>
                    </Form.Select>
                  </Form.Group>
                </Col>
              </Row>

              <Form.Group className="mb-3">
                <Form.Label>Bio</Form.Label>
                <Form.Control
                  as="textarea"
                  rows={3}
                  value={profile.bio}
                  onChange={(e) => handleProfileChange('bio', e.target.value)}
                  disabled={!isEditing}
                  placeholder="Tell us about yourself..."
                />
              </Form.Group>

              {isEditing && (
                <div className="d-flex gap-2">
                  <Button variant="outline-secondary" onClick={() => setIsEditing(false)}>
                    Cancel
                  </Button>
                  <Button variant="primary" onClick={handleSaveProfile} disabled={loading}>
                    Save Changes
                  </Button>
                </div>
              )}
            </Card.Body>
          </Card>

          {/* Account Activity */}
          <Card>
            <Card.Header>
              <h5 className="mb-0">
                <i className="bi bi-activity me-2"></i>
                Recent Activity
              </h5>
            </Card.Header>
            <Card.Body>
              {recentActivities.length > 0 ? (
                <div className="activity-feed">
                  {recentActivities.map((activity) => (
                    <div key={activity.id} className="d-flex align-items-start mb-3 pb-3 border-bottom">
                      <div className="me-3">
                        <i className={`${ActivityLogger.getActivityIcon(activity)} fs-5`}></i>
                      </div>
                      <div className="flex-grow-1">
                        <div className="fw-medium">
                          {ActivityLogger.formatActivityMessage(activity)}
                        </div>
                        <small className="text-muted">
                          {formatDistanceToNow(parseISO(activity.timestamp), { addSuffix: true })}
                        </small>
                        {activity.metadata && Object.keys(activity.metadata).length > 0 && (
                          <div className="mt-1">
                            <small className="text-muted">
                              {activity.metadata.cost && (
                                <span className="me-2">
                                  <i className="bi bi-currency-dollar me-1"></i>
                                  {activity.metadata.cost}
                                </span>
                              )}
                              {activity.metadata.billing_cycle && (
                                <span className="me-2">
                                  <i className="bi bi-arrow-repeat me-1"></i>
                                  {activity.metadata.billing_cycle}
                                </span>
                              )}
                              {activity.metadata.category && (
                                <span className="me-2">
                                  <i className="bi bi-tag me-1"></i>
                                  {activity.metadata.category}
                                </span>
                              )}
                            </small>
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                  <div className="text-center">
                    <Button
                      variant="outline-secondary"
                      size="sm"
                      onClick={loadRecentActivities}
                    >
                      <i className="bi bi-arrow-clockwise me-2"></i>
                      Refresh
                    </Button>
                  </div>
                </div>
              ) : (
                <div className="text-center py-4">
                  <i className="bi bi-clock-history text-muted" style={{ fontSize: '2rem' }}></i>
                  <h6 className="text-muted mt-2">No recent activity</h6>
                  <p className="text-muted">
                    Your recent subscription changes and updates will appear here.
                  </p>
                </div>
              )}
            </Card.Body>
          </Card>
        </Col>

        {/* Statistics & Summary */}
        <Col lg={4}>
          <Card className="mb-4">
            <Card.Header>
              <h5 className="mb-0">
                <i className="bi bi-bar-chart me-2"></i>
                Your Statistics
              </h5>
            </Card.Header>
            <Card.Body>
              <div className="text-center mb-3">
                <div className="display-6 text-primary">{statistics.activeSubscriptions}</div>
                <small className="text-muted">Active Subscriptions</small>
              </div>

              <hr />

              <div className="d-flex justify-content-between mb-2">
                <span>Total Subscriptions:</span>
                <Badge bg="secondary" pill>{statistics.totalSubscriptions}</Badge>
              </div>

              <div className="d-flex justify-content-between mb-2">
                <span>Monthly Spending:</span>
                <Badge bg="success" pill>{formatCurrency(statistics.monthlySpend)}</Badge>
              </div>

              <div className="d-flex justify-content-between mb-2">
                <span>Annual Spending:</span>
                <Badge bg="warning" pill>{formatCurrency(statistics.annualSpend)}</Badge>
              </div>

              <div className="d-flex justify-content-between">
                <span>Average per Sub:</span>
                <Badge bg="info" pill>{formatCurrency(statistics.averageSubscriptionCost)}</Badge>
              </div>
            </Card.Body>
          </Card>

          <Card className="mb-4">
            <Card.Header>
              <h5 className="mb-0">
                <i className="bi bi-trophy me-2"></i>
                Highlights
              </h5>
            </Card.Header>
            <Card.Body>
              {statistics.mostExpensiveSubscription && (
                <div className="mb-3">
                  <h6 className="text-warning">Most Expensive</h6>
                  <p className="mb-1"><strong>{statistics.mostExpensiveSubscription.service_name}</strong></p>
                  <small className="text-muted">
                    {formatCurrency(statistics.mostExpensiveSubscription.cost)} / {statistics.mostExpensiveSubscription.billing_cycle}
                  </small>
                </div>
              )}

              {statistics.oldestSubscription && (
                <div>
                  <h6 className="text-info">Longest Running</h6>
                  <p className="mb-1"><strong>{statistics.oldestSubscription.service_name}</strong></p>
                  <small className="text-muted">
                    Since {formatDate(statistics.oldestSubscription.start_date)}
                  </small>
                </div>
              )}

              {!statistics.mostExpensiveSubscription && !statistics.oldestSubscription && (
                <div className="text-center py-3">
                  <i className="bi bi-inbox text-muted" style={{ fontSize: '2rem' }}></i>
                  <p className="text-muted mt-2 mb-0">No subscriptions yet</p>
                </div>
              )}
            </Card.Body>
          </Card>

          <Card>
            <Card.Header>
              <h5 className="mb-0">
                <i className="bi bi-info-circle me-2"></i>
                Account Info
              </h5>
            </Card.Header>
            <Card.Body>
              <small className="text-muted d-block mb-2">
                <strong>Member Since:</strong> {formatDate(profile.joinDate)}
              </small>
              <small className="text-muted d-block mb-2">
                <strong>Username:</strong> {profile.username}
              </small>
              <small className="text-muted d-block mb-2">
                <strong>Timezone:</strong> {profile.timezone}
              </small>

              <hr />

              <div className="d-grid gap-2">
                <Button
                  variant="outline-primary"
                  size="sm"
                  onClick={handleSecuritySettings}
                >
                  <i className="bi bi-shield-lock me-2"></i>
                  Security Settings
                </Button>
                <Button
                  variant="outline-secondary"
                  size="sm"
                  onClick={handleExportData}
                  disabled={exportLoading}
                >
                  {exportLoading ? (
                    <>
                      <div className="spinner-border spinner-border-sm me-2" role="status">
                        <span className="visually-hidden">Loading...</span>
                      </div>
                      Exporting...
                    </>
                  ) : (
                    <>
                      <i className="bi bi-download me-2"></i>
                      Export Data
                    </>
                  )}
                </Button>
              </div>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Security Settings Modal */}
      <Modal show={showSecurityModal} onHide={() => setShowSecurityModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>
            <i className="bi bi-shield-lock me-2"></i>
            Security Settings
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Row>
            <Col md={6}>
              <Card className="mb-3">
                <Card.Header>
                  <h6 className="mb-0">
                    <i className="bi bi-key me-2"></i>
                    Password Security
                  </h6>
                </Card.Header>
                <Card.Body>
                  <Form>
                    <Form.Group className="mb-3">
                      <Form.Label>Current Password</Form.Label>
                      <Form.Control type="password" placeholder="Enter current password" />
                    </Form.Group>
                    <Form.Group className="mb-3">
                      <Form.Label>New Password</Form.Label>
                      <Form.Control type="password" placeholder="Enter new password" />
                    </Form.Group>
                    <Form.Group className="mb-3">
                      <Form.Label>Confirm New Password</Form.Label>
                      <Form.Control type="password" placeholder="Confirm new password" />
                    </Form.Group>
                    <Button variant="primary" size="sm">
                      <i className="bi bi-check-circle me-2"></i>
                      Update Password
                    </Button>
                  </Form>
                </Card.Body>
              </Card>
            </Col>
            <Col md={6}>
              <Card className="mb-3">
                <Card.Header>
                  <h6 className="mb-0">
                    <i className="bi bi-shield-check me-2"></i>
                    Two-Factor Authentication
                  </h6>
                </Card.Header>
                <Card.Body>
                  <div className="d-flex justify-content-between align-items-center mb-3">
                    <span>2FA Status</span>
                    <Badge bg="danger" pill>Disabled</Badge>
                  </div>
                  <p className="text-muted small">
                    Add an extra layer of security to your account by enabling two-factor authentication.
                  </p>
                  <Button variant="outline-success" size="sm">
                    <i className="bi bi-plus-circle me-2"></i>
                    Enable 2FA
                  </Button>
                </Card.Body>
              </Card>

              <Card>
                <Card.Header>
                  <h6 className="mb-0">
                    <i className="bi bi-clock-history me-2"></i>
                    Login Sessions
                  </h6>
                </Card.Header>
                <Card.Body>
                  <div className="d-flex justify-content-between align-items-center mb-2">
                    <div>
                      <small className="fw-medium">Current Session</small>
                      <br />
                      <small className="text-muted">Chrome on macOS</small>
                    </div>
                    <Badge bg="success" pill>Active</Badge>
                  </div>
                  <hr />
                  <Button variant="outline-danger" size="sm" className="w-100">
                    <i className="bi bi-box-arrow-right me-2"></i>
                    Sign Out All Sessions
                  </Button>
                </Card.Body>
              </Card>
            </Col>
          </Row>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowSecurityModal(false)}>
            Close
          </Button>
          <Button variant="primary">
            <i className="bi bi-shield-check me-2"></i>
            Save Security Settings
          </Button>
        </Modal.Footer>
      </Modal>
    </Container>
  );
};

export default ProfilePage;