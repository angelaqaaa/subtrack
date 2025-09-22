import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Form, Button, Alert, Badge, Table, Modal, Spinner, Tab, Tabs } from 'react-bootstrap';
import { parseISO, format, formatDistanceToNow } from 'date-fns';
import QRCode from 'qrcode';
import { useAuth } from '../../contexts/AuthContext';
import { subscriptionsAPI, authAPI } from '../../services/api';
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

  // Security-related state
  const [sessions, setSessions] = useState([]);
  const [loginHistory, setLoginHistory] = useState([]);
  const [sessionsLoading, setSessionsLoading] = useState(false);
  const [historyLoading, setHistoryLoading] = useState(false);
  const [logoutAllLoading, setLogoutAllLoading] = useState(false);

  // 2FA-related state
  const [twoFAStatus, setTwoFAStatus] = useState({ enabled: false, backup_codes_remaining: 0 });
  const [twoFALoading, setTwoFALoading] = useState(false);
  const [setupSecret, setSetupSecret] = useState('');
  const [qrCodeURL, setQrCodeURL] = useState('');
  const [verificationCode, setVerificationCode] = useState('');
  const [backupCodes, setBackupCodes] = useState([]);
  const [showBackupCodes, setShowBackupCodes] = useState(false);
  const [twoFAPassword, setTwoFAPassword] = useState('');
  const [show2FASetup, setShow2FASetup] = useState(false);
  const [show2FADisable, setShow2FADisable] = useState(false);

  // Account management state
  const [showPasswordModal, setShowPasswordModal] = useState(false);
  const [showEmailModal, setShowEmailModal] = useState(false);
  const [passwordLoading, setPasswordLoading] = useState(false);
  const [emailLoading, setEmailLoading] = useState(false);
  const [passwordForm, setPasswordForm] = useState({
    currentPassword: '',
    newPassword: '',
    confirmPassword: ''
  });
  const [emailForm, setEmailForm] = useState({
    newEmail: '',
    password: ''
  });

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

  // 2FA Functions
  const load2FAStatus = async () => {
    try {
      setTwoFALoading(true);
      const response = await authAPI.get2FAStatus();
      if (response.status === 'success') {
        setTwoFAStatus(response.data);
      }
    } catch (err) {
      console.error('Failed to load 2FA status:', err);
    } finally {
      setTwoFALoading(false);
    }
  };

  const handleSetup2FA = async () => {
    try {
      setTwoFALoading(true);
      const response = await authAPI.setup2FA();
      if (response.status === 'success') {
        setSetupSecret(response.data.secret);

        // Generate QR code
        const qrCodeDataURL = await QRCode.toDataURL(response.data.qr_url);
        setQrCodeURL(qrCodeDataURL);

        setShow2FASetup(true);
      } else {
        setError(response.message);
      }
    } catch (err) {
      setError('Failed to setup 2FA');
      console.error('2FA setup error:', err);
    } finally {
      setTwoFALoading(false);
    }
  };

  const handleEnable2FA = async () => {
    try {
      setTwoFALoading(true);
      const response = await authAPI.enable2FA(setupSecret, verificationCode);
      if (response.status === 'success') {
        setBackupCodes(response.data.backup_codes);
        setShowBackupCodes(true);
        setShow2FASetup(false);
        setSuccessMessage('Two-factor authentication enabled successfully!');
        load2FAStatus();
        setVerificationCode('');
      } else {
        setError(response.message);
      }
    } catch (err) {
      setError('Failed to enable 2FA');
      console.error('Enable 2FA error:', err);
    } finally {
      setTwoFALoading(false);
    }
  };

  const handleDisable2FA = async () => {
    try {
      setTwoFALoading(true);
      const response = await authAPI.disable2FA(twoFAPassword, verificationCode);
      if (response.status === 'success') {
        setShow2FADisable(false);
        setSuccessMessage('Two-factor authentication disabled successfully!');
        load2FAStatus();
        setTwoFAPassword('');
        setVerificationCode('');
      } else {
        setError(response.message);
      }
    } catch (err) {
      setError('Failed to disable 2FA');
      console.error('Disable 2FA error:', err);
    } finally {
      setTwoFALoading(false);
    }
  };

  const handleGenerateBackupCodes = async () => {
    try {
      setTwoFALoading(true);
      const response = await authAPI.generateBackupCodes(twoFAPassword);
      if (response.status === 'success') {
        setBackupCodes(response.data.backup_codes);
        setShowBackupCodes(true);
        setSuccessMessage('New backup codes generated successfully!');
        load2FAStatus();
        setTwoFAPassword('');
      } else {
        setError(response.message);
      }
    } catch (err) {
      setError('Failed to generate backup codes');
      console.error('Generate backup codes error:', err);
    } finally {
      setTwoFALoading(false);
    }
  };

  const copyBackupCodes = () => {
    const codesText = backupCodes.join('\n');
    navigator.clipboard.writeText(codesText).then(() => {
      setSuccessMessage('Backup codes copied to clipboard!');
    }).catch(() => {
      setError('Failed to copy backup codes');
    });
  };

  const downloadBackupCodes = () => {
    const codesText = backupCodes.join('\n');
    const blob = new Blob([codesText], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'subtrack-backup-codes.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  };

  // Account management functions
  const handlePasswordFormChange = (e) => {
    setPasswordForm({
      ...passwordForm,
      [e.target.name]: e.target.value
    });
  };

  const handleEmailFormChange = (e) => {
    setEmailForm({
      ...emailForm,
      [e.target.name]: e.target.value
    });
  };

  const handleChangePassword = async (e) => {
    e.preventDefault();
    if (passwordForm.newPassword !== passwordForm.confirmPassword) {
      setError('New passwords do not match');
      return;
    }

    setPasswordLoading(true);
    setError('');

    try {
      const response = await authAPI.changePassword(
        passwordForm.currentPassword,
        passwordForm.newPassword,
        passwordForm.confirmPassword
      );

      if (response.status === 'success') {
        setShowPasswordModal(false);
        setPasswordForm({
          currentPassword: '',
          newPassword: '',
          confirmPassword: ''
        });
        setSuccessMessage('Password changed successfully!');
        setTimeout(() => setSuccessMessage(''), 5000);
      } else {
        setError(response.message || 'Failed to change password');
      }
    } catch (err) {
      console.error('Password change error:', err);
      setError(err.response?.data?.message || 'Failed to change password. Please try again.');
    } finally {
      setPasswordLoading(false);
    }
  };

  const handleChangeEmail = async (e) => {
    e.preventDefault();
    setEmailLoading(true);
    setError('');

    try {
      const response = await authAPI.changeEmail(
        emailForm.newEmail,
        emailForm.password
      );

      if (response.status === 'success') {
        setShowEmailModal(false);
        setEmailForm({
          newEmail: '',
          password: ''
        });
        setSuccessMessage('Email updated successfully!');
        setTimeout(() => setSuccessMessage(''), 5000);
      } else {
        setError(response.message || 'Failed to update email');
      }
    } catch (err) {
      console.error('Email change error:', err);
      setError(err.response?.data?.message || 'Failed to update email. Please try again.');
    } finally {
      setEmailLoading(false);
    }
  };

  const handleSecuritySettings = () => {
    setShowSecurityModal(true);
    loadSessions();
    loadLoginHistory();
    load2FAStatus();
  };

  const loadSessions = async () => {
    try {
      setSessionsLoading(true);
      const response = await authAPI.getSessions();
      if (response.status === 'success') {
        setSessions(response.data.sessions || []);
      }
    } catch (err) {
      console.error('Failed to load sessions:', err);
    } finally {
      setSessionsLoading(false);
    }
  };

  const loadLoginHistory = async () => {
    try {
      setHistoryLoading(true);
      const response = await authAPI.getLoginHistory(10);
      if (response.status === 'success') {
        setLoginHistory(response.data.history || []);
      }
    } catch (err) {
      console.error('Failed to load login history:', err);
    } finally {
      setHistoryLoading(false);
    }
  };

  const handleLogoutAllSessions = async () => {
    if (!window.confirm('Are you sure you want to log out from all other sessions? This will not affect your current session.')) {
      return;
    }

    try {
      setLogoutAllLoading(true);
      const response = await authAPI.logoutAllSessions();
      if (response.status === 'success') {
        setSuccessMessage('Successfully logged out from all other sessions');
        setTimeout(() => setSuccessMessage(''), 3000);
        loadSessions(); // Reload sessions
      } else {
        setError(response.message || 'Failed to logout sessions');
      }
    } catch (err) {
      console.error('Logout all sessions error:', err);
      setError('Failed to logout sessions. Please try again.');
    } finally {
      setLogoutAllLoading(false);
    }
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
          <Tabs defaultActiveKey="sessions" className="mb-3">
            {/* Active Sessions Tab */}
            <Tab eventKey="sessions" title="Active Sessions">
              <div className="d-flex justify-content-between align-items-center mb-3">
                <h6 className="mb-0">
                  <i className="bi bi-clock-history me-2"></i>
                  Active Login Sessions
                </h6>
                <Button
                  variant="outline-danger"
                  size="sm"
                  onClick={handleLogoutAllSessions}
                  disabled={logoutAllLoading}
                >
                  {logoutAllLoading ? (
                    <>
                      <Spinner animation="border" size="sm" className="me-2" />
                      Logging out...
                    </>
                  ) : (
                    <>
                      <i className="bi bi-box-arrow-right me-2"></i>
                      Sign Out All Sessions
                    </>
                  )}
                </Button>
              </div>

              {sessionsLoading ? (
                <div className="text-center py-4">
                  <Spinner animation="border" />
                  <p className="mt-2 text-muted">Loading sessions...</p>
                </div>
              ) : sessions.length > 0 ? (
                <div className="border rounded">
                  {sessions.map((session, index) => (
                    <div key={session.session_id} className={`p-3 ${index !== sessions.length - 1 ? 'border-bottom' : ''}`}>
                      <div className="d-flex justify-content-between align-items-start">
                        <div>
                          <div className="fw-medium">
                            {session.is_current ? 'Current Session' : 'Session'}
                            {session.is_current && <Badge bg="success" pill className="ms-2">Current</Badge>}
                          </div>
                          <small className="text-muted d-block">
                            IP: {session.ip_address}
                          </small>
                          <small className="text-muted d-block">
                            Device: {session.user_agent.substring(0, 60)}...
                          </small>
                          <small className="text-muted">
                            Last activity: {formatDistanceToNow(parseISO(session.last_activity))} ago
                          </small>
                        </div>
                        <Badge bg={session.is_current ? "success" : "secondary"} pill>
                          {session.is_current ? "Active" : "Inactive"}
                        </Badge>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <Alert variant="info">
                  <i className="bi bi-info-circle me-2"></i>
                  No active sessions found.
                </Alert>
              )}

              <div className="mt-3">
                <small className="text-muted">
                  <i className="bi bi-shield-check me-1"></i>
                  Your current session will remain active when you sign out from all other sessions.
                </small>
              </div>
            </Tab>

            {/* Login History Tab */}
            <Tab eventKey="history" title="Login History">
              <h6 className="mb-3">
                <i className="bi bi-clock-history me-2"></i>
                Recent Login Activity
              </h6>

              {historyLoading ? (
                <div className="text-center py-4">
                  <Spinner animation="border" />
                  <p className="mt-2 text-muted">Loading login history...</p>
                </div>
              ) : loginHistory.length > 0 ? (
                <Table responsive hover>
                  <thead>
                    <tr>
                      <th>Status</th>
                      <th>Date & Time</th>
                      <th>IP Address</th>
                      <th>Device</th>
                    </tr>
                  </thead>
                  <tbody>
                    {loginHistory.map((entry, index) => (
                      <tr key={index}>
                        <td>
                          <Badge bg={entry.success ? "success" : "danger"} pill>
                            {entry.success ? "Success" : "Failed"}
                          </Badge>
                        </td>
                        <td>
                          <div>{format(parseISO(entry.login_time), 'MMM dd, yyyy')}</div>
                          <small className="text-muted">{format(parseISO(entry.login_time), 'h:mm a')}</small>
                        </td>
                        <td>
                          <code className="small">{entry.ip_address}</code>
                        </td>
                        <td>
                          <small className="text-muted">{entry.user_agent.substring(0, 40)}...</small>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </Table>
              ) : (
                <Alert variant="info">
                  <i className="bi bi-info-circle me-2"></i>
                  No login history available.
                </Alert>
              )}
            </Tab>

            {/* Two-Factor Authentication Tab */}
            <Tab eventKey="security" title="Two-Factor Authentication">
              {error && (
                <Alert variant="danger" onClose={() => setError('')} dismissible>
                  {error}
                </Alert>
              )}
              {successMessage && (
                <Alert variant="success" onClose={() => setSuccessMessage('')} dismissible>
                  {successMessage}
                </Alert>
              )}

              {twoFALoading ? (
                <div className="text-center">
                  <Spinner animation="border" />
                  <p className="mt-2">Loading 2FA settings...</p>
                </div>
              ) : (
                <div>
                  <Card>
                    <Card.Body>
                      <div className="d-flex justify-content-between align-items-center mb-3">
                        <div>
                          <h6 className="mb-1">Two-Factor Authentication</h6>
                          <small className="text-muted">
                            Add an extra layer of security to your account
                          </small>
                        </div>
                        <Badge bg={twoFAStatus.enabled ? 'success' : 'secondary'}>
                          {twoFAStatus.enabled ? 'Enabled' : 'Disabled'}
                        </Badge>
                      </div>

                      {twoFAStatus.enabled ? (
                        <div>
                          <p className="mb-3">
                            <i className="bi bi-shield-check text-success me-2"></i>
                            Two-factor authentication is currently enabled for your account.
                          </p>

                          <div className="mb-3">
                            <small className="text-muted">
                              Backup codes remaining: {twoFAStatus.backup_codes_remaining}
                            </small>
                          </div>

                          <div className="d-grid gap-2 d-md-flex">
                            <Button
                              variant="outline-primary"
                              size="sm"
                              onClick={() => {
                                setTwoFAPassword('');
                                setShow2FADisable(true);
                              }}
                            >
                              <i className="bi bi-shield-x me-2"></i>
                              Disable 2FA
                            </Button>
                            <Button
                              variant="outline-secondary"
                              size="sm"
                              onClick={() => {
                                setTwoFAPassword('');
                                setShowBackupCodes(true);
                              }}
                            >
                              <i className="bi bi-key me-2"></i>
                              Generate New Backup Codes
                            </Button>
                          </div>
                        </div>
                      ) : (
                        <div>
                          <p className="mb-3">
                            Two-factor authentication is not enabled. We recommend enabling it to secure your account.
                          </p>

                          <Button
                            variant="primary"
                            size="sm"
                            onClick={handleSetup2FA}
                            disabled={twoFALoading}
                          >
                            <i className="bi bi-shield-plus me-2"></i>
                            Enable Two-Factor Authentication
                          </Button>
                        </div>
                      )}
                    </Card.Body>
                  </Card>

                  <Card className="mt-3">
                    <Card.Body>
                      <h6>What is Two-Factor Authentication?</h6>
                      <p className="mb-0 small text-muted">
                        Two-factor authentication (2FA) adds an extra layer of security to your account.
                        When enabled, you'll need to provide both your password and a time-based code from
                        your authenticator app when logging in.
                      </p>
                    </Card.Body>
                  </Card>
                </div>
              )}
            </Tab>

            {/* Account Management Tab */}
            <Tab eventKey="account" title="Account Management">
              {error && (
                <Alert variant="danger" onClose={() => setError('')} dismissible>
                  {error}
                </Alert>
              )}
              {successMessage && (
                <Alert variant="success" onClose={() => setSuccessMessage('')} dismissible>
                  {successMessage}
                </Alert>
              )}

              <div className="row g-3">
                <div className="col-md-6">
                  <Card>
                    <Card.Body>
                      <h6 className="mb-3">
                        <i className="bi bi-key me-2"></i>
                        Change Password
                      </h6>
                      <p className="text-muted small mb-3">
                        Update your account password to keep your account secure.
                      </p>
                      <Button
                        variant="outline-primary"
                        size="sm"
                        onClick={() => {
                          setPasswordForm({
                            currentPassword: '',
                            newPassword: '',
                            confirmPassword: ''
                          });
                          setShowPasswordModal(true);
                        }}
                      >
                        <i className="bi bi-shield-lock me-2"></i>
                        Change Password
                      </Button>
                    </Card.Body>
                  </Card>
                </div>

                <div className="col-md-6">
                  <Card>
                    <Card.Body>
                      <h6 className="mb-3">
                        <i className="bi bi-envelope me-2"></i>
                        Update Email
                      </h6>
                      <p className="text-muted small mb-3">
                        Current email: <strong>{user?.email}</strong>
                      </p>
                      <Button
                        variant="outline-primary"
                        size="sm"
                        onClick={() => {
                          setEmailForm({
                            newEmail: user?.email || '',
                            password: ''
                          });
                          setShowEmailModal(true);
                        }}
                      >
                        <i className="bi bi-pencil me-2"></i>
                        Update Email
                      </Button>
                    </Card.Body>
                  </Card>
                </div>
              </div>
            </Tab>
          </Tabs>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowSecurityModal(false)}>
            Close
          </Button>
        </Modal.Footer>
      </Modal>

      {/* 2FA Setup Modal */}
      <Modal show={show2FASetup} onHide={() => setShow2FASetup(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>
            <i className="bi bi-shield-plus me-2"></i>
            Setup Two-Factor Authentication
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <div className="text-center mb-4">
            <h6>Step 1: Scan QR Code</h6>
            <p className="text-muted">
              Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.)
            </p>
            {qrCodeURL && (
              <div className="mb-3">
                <img src={qrCodeURL} alt="QR Code" className="img-fluid" style={{maxWidth: '200px'}} />
              </div>
            )}
            <p className="small text-muted">
              Can't scan? Manual entry key: <code>{setupSecret}</code>
            </p>
          </div>

          <div>
            <h6>Step 2: Enter Verification Code</h6>
            <p className="text-muted">
              Enter the 6-digit code from your authenticator app to complete setup.
            </p>
            <Form.Group className="mb-3">
              <Form.Label>Verification Code</Form.Label>
              <Form.Control
                type="text"
                placeholder="Enter 6-digit code"
                value={verificationCode}
                onChange={(e) => setVerificationCode(e.target.value)}
                maxLength={6}
                className="text-center"
                style={{fontSize: '1.2em', letterSpacing: '0.2em'}}
              />
            </Form.Group>
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShow2FASetup(false)}>
            Cancel
          </Button>
          <Button
            variant="primary"
            onClick={handleEnable2FA}
            disabled={!verificationCode || verificationCode.length !== 6 || twoFALoading}
          >
            {twoFALoading ? <Spinner animation="border" size="sm" className="me-2" /> : null}
            Enable 2FA
          </Button>
        </Modal.Footer>
      </Modal>

      {/* 2FA Disable Modal */}
      <Modal show={show2FADisable} onHide={() => setShow2FADisable(false)}>
        <Modal.Header closeButton>
          <Modal.Title>
            <i className="bi bi-shield-x me-2"></i>
            Disable Two-Factor Authentication
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Alert variant="warning">
            <i className="bi bi-exclamation-triangle me-2"></i>
            <strong>Warning:</strong> Disabling 2FA will make your account less secure.
          </Alert>

          <Form.Group className="mb-3">
            <Form.Label>Current Password</Form.Label>
            <Form.Control
              type="password"
              placeholder="Enter your current password"
              value={twoFAPassword}
              onChange={(e) => setTwoFAPassword(e.target.value)}
            />
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>2FA Code (Optional)</Form.Label>
            <Form.Control
              type="text"
              placeholder="Enter 6-digit code from your authenticator app"
              value={verificationCode}
              onChange={(e) => setVerificationCode(e.target.value)}
              maxLength={6}
              className="text-center"
            />
            <Form.Text className="text-muted">
              You can also use a backup code instead of the authenticator code.
            </Form.Text>
          </Form.Group>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShow2FADisable(false)}>
            Cancel
          </Button>
          <Button
            variant="danger"
            onClick={handleDisable2FA}
            disabled={!twoFAPassword || twoFALoading}
          >
            {twoFALoading ? <Spinner animation="border" size="sm" className="me-2" /> : null}
            Disable 2FA
          </Button>
        </Modal.Footer>
      </Modal>

      {/* Backup Codes Modal */}
      <Modal show={showBackupCodes} onHide={() => setShowBackupCodes(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>
            <i className="bi bi-key me-2"></i>
            Backup Codes
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {backupCodes.length > 0 ? (
            <div>
              <Alert variant="info">
                <i className="bi bi-info-circle me-2"></i>
                <strong>Important:</strong> Save these backup codes in a safe place. Each code can only be used once.
              </Alert>

              <div className="bg-light p-3 rounded mb-3">
                <div className="row">
                  {backupCodes.map((code, index) => (
                    <div key={index} className="col-6 mb-2">
                      <code className="d-block text-center p-2">{code}</code>
                    </div>
                  ))}
                </div>
              </div>

              <div className="d-grid gap-2 d-md-flex justify-content-md-end">
                <Button variant="outline-secondary" onClick={copyBackupCodes}>
                  <i className="bi bi-clipboard me-2"></i>
                  Copy Codes
                </Button>
                <Button variant="outline-primary" onClick={downloadBackupCodes}>
                  <i className="bi bi-download me-2"></i>
                  Download Codes
                </Button>
              </div>
            </div>
          ) : (
            <div>
              <p>Generate new backup codes to replace your existing ones.</p>

              <Form.Group className="mb-3">
                <Form.Label>Current Password</Form.Label>
                <Form.Control
                  type="password"
                  placeholder="Enter your current password"
                  value={twoFAPassword}
                  onChange={(e) => setTwoFAPassword(e.target.value)}
                />
              </Form.Group>

              <Button
                variant="primary"
                onClick={handleGenerateBackupCodes}
                disabled={!twoFAPassword || twoFALoading}
              >
                {twoFALoading ? <Spinner animation="border" size="sm" className="me-2" /> : null}
                Generate New Backup Codes
              </Button>
            </div>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => {
            setShowBackupCodes(false);
            setBackupCodes([]);
            setTwoFAPassword('');
          }}>
            Close
          </Button>
        </Modal.Footer>
      </Modal>

      {/* Change Password Modal */}
      <Modal show={showPasswordModal} onHide={() => setShowPasswordModal(false)}>
        <Modal.Header closeButton>
          <Modal.Title>
            <i className="bi bi-key me-2"></i>
            Change Password
          </Modal.Title>
        </Modal.Header>
        <Form onSubmit={handleChangePassword}>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label>Current Password *</Form.Label>
              <Form.Control
                type="password"
                name="currentPassword"
                value={passwordForm.currentPassword}
                onChange={handlePasswordFormChange}
                required
                disabled={passwordLoading}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>New Password *</Form.Label>
              <Form.Control
                type="password"
                name="newPassword"
                value={passwordForm.newPassword}
                onChange={handlePasswordFormChange}
                required
                disabled={passwordLoading}
              />
              <Form.Text className="text-muted">
                Password must be at least 8 characters with uppercase, lowercase, number, and special character.
              </Form.Text>
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Confirm New Password *</Form.Label>
              <Form.Control
                type="password"
                name="confirmPassword"
                value={passwordForm.confirmPassword}
                onChange={handlePasswordFormChange}
                required
                disabled={passwordLoading}
              />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={() => setShowPasswordModal(false)} disabled={passwordLoading}>
              Cancel
            </Button>
            <Button variant="primary" type="submit" disabled={passwordLoading}>
              {passwordLoading ? (
                <>
                  <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                  Changing...
                </>
              ) : (
                'Change Password'
              )}
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>

      {/* Change Email Modal */}
      <Modal show={showEmailModal} onHide={() => setShowEmailModal(false)}>
        <Modal.Header closeButton>
          <Modal.Title>
            <i className="bi bi-envelope me-2"></i>
            Update Email Address
          </Modal.Title>
        </Modal.Header>
        <Form onSubmit={handleChangeEmail}>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label>New Email Address *</Form.Label>
              <Form.Control
                type="email"
                name="newEmail"
                value={emailForm.newEmail}
                onChange={handleEmailFormChange}
                required
                disabled={emailLoading}
                placeholder="Enter new email address"
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Password (for verification) *</Form.Label>
              <Form.Control
                type="password"
                name="password"
                value={emailForm.password}
                onChange={handleEmailFormChange}
                required
                disabled={emailLoading}
                placeholder="Enter your current password"
              />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={() => setShowEmailModal(false)} disabled={emailLoading}>
              Cancel
            </Button>
            <Button variant="primary" type="submit" disabled={emailLoading}>
              {emailLoading ? (
                <>
                  <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                  Updating...
                </>
              ) : (
                'Update Email'
              )}
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </Container>
  );
};

export default ProfilePage;