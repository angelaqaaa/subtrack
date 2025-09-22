import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Form, Button, Alert, Tab, Tabs } from 'react-bootstrap';
import { useAuth } from '../../contexts/AuthContext';

const SettingsPage = () => {
  const { user } = useAuth();
  const [settings, setSettings] = useState({
    // General Settings
    timezone: 'UTC',
    dateFormat: 'MM/DD/YYYY',
    currency: 'USD',
    language: 'en',

    // Notification Settings
    emailNotifications: true,
    reminderDays: 7,
    weeklyReport: true,
    monthlyReport: true,

    // Privacy Settings
    profileVisibility: 'private',
    shareData: false,

    // Display Settings
    theme: 'light',
    dashboardLayout: 'default',
    itemsPerPage: 10
  });

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

  useEffect(() => {
    loadUserSettings();
  }, []);

  const loadUserSettings = async () => {
    try {
      // In a real app, this would fetch user settings from the backend
      // For now, we'll use localStorage or default values
      const savedSettings = localStorage.getItem('userSettings');
      if (savedSettings) {
        setSettings({ ...settings, ...JSON.parse(savedSettings) });
      }
    } catch (err) {
      console.error('Failed to load user settings:', err);
    }
  };

  const handleSettingChange = (key, value) => {
    setSettings(prev => ({
      ...prev,
      [key]: value
    }));
  };

  const handleSaveSettings = async () => {
    try {
      setLoading(true);
      setError('');

      // In a real app, this would save to the backend
      localStorage.setItem('userSettings', JSON.stringify(settings));

      setSuccessMessage('Settings saved successfully!');
      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (err) {
      console.error('Save settings error:', err);
      setError('Failed to save settings. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleResetToDefaults = () => {
    setSettings({
      timezone: 'UTC',
      dateFormat: 'MM/DD/YYYY',
      currency: 'USD',
      language: 'en',
      emailNotifications: true,
      reminderDays: 7,
      weeklyReport: true,
      monthlyReport: true,
      profileVisibility: 'private',
      shareData: false,
      theme: 'light',
      dashboardLayout: 'default',
      itemsPerPage: 10
    });
    setSuccessMessage('Settings reset to defaults');
    setTimeout(() => setSuccessMessage(''), 3000);
  };

  const handleExportData = async () => {
    try {
      setLoading(true);

      // Create mock data export
      const exportData = {
        user: {
          username: user?.username,
          settings: settings,
          exportDate: new Date().toISOString()
        },
        subscriptions: [], // Would fetch from API
        categories: [],
        spaces: [],
        insights: []
      };

      const dataStr = JSON.stringify(exportData, null, 2);
      const dataBlob = new Blob([dataStr], { type: 'application/json' });
      const url = URL.createObjectURL(dataBlob);

      const link = document.createElement('a');
      link.href = url;
      link.download = `subtrack-data-export-${new Date().toISOString().split('T')[0]}.json`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);

      setSuccessMessage('Data exported successfully!');
      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (err) {
      console.error('Export error:', err);
      setError('Failed to export data. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteAccount = () => {
    if (window.confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
      if (window.confirm('This will permanently delete all your data. Type "DELETE" to confirm.')) {
        setError('Account deletion is not yet implemented. Please contact support.');
      }
    }
  };

  const handleChangePassword = () => {
    setSuccessMessage('Password change functionality coming soon!');
    setTimeout(() => setSuccessMessage(''), 3000);
  };

  const handleUpdateEmail = () => {
    setSuccessMessage('Email update functionality coming soon!');
    setTimeout(() => setSuccessMessage(''), 3000);
  };

  const handleEnable2FA = () => {
    setSuccessMessage('Two-factor authentication setup coming soon!');
    setTimeout(() => setSuccessMessage(''), 3000);
  };

  const handleViewSessions = () => {
    setSuccessMessage('Session management coming soon!');
    setTimeout(() => setSuccessMessage(''), 3000);
  };

  const handleViewLoginHistory = () => {
    setSuccessMessage('Login history viewing coming soon!');
    setTimeout(() => setSuccessMessage(''), 3000);
  };

  return (
    <Container>
      <Row>
        <Col>
          <div className="d-flex justify-content-between align-items-center mb-4">
            <h1 className="h2">
              <i className="bi bi-gear me-2"></i>
              Settings
            </h1>
            <div className="btn-group">
              <Button
                variant="outline-secondary"
                onClick={handleResetToDefaults}
              >
                <i className="bi bi-arrow-clockwise me-2"></i>
                Reset to Defaults
              </Button>
              <Button
                variant="primary"
                onClick={handleSaveSettings}
                disabled={loading}
              >
                {loading ? (
                  <>
                    <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                    Saving...
                  </>
                ) : (
                  <>
                    <i className="bi bi-check-circle me-2"></i>
                    Save Settings
                  </>
                )}
              </Button>
            </div>
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
        <Col>
          <Tabs defaultActiveKey="general" className="mb-4">
            {/* General Settings */}
            <Tab eventKey="general" title="General">
              <Card>
                <Card.Header>
                  <h5 className="mb-0">
                    <i className="bi bi-sliders me-2"></i>
                    General Settings
                  </h5>
                </Card.Header>
                <Card.Body>
                  <Row>
                    <Col md={6}>
                      <Form.Group className="mb-3">
                        <Form.Label>Timezone</Form.Label>
                        <Form.Select
                          value={settings.timezone}
                          onChange={(e) => handleSettingChange('timezone', e.target.value)}
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

                      <Form.Group className="mb-3">
                        <Form.Label>Date Format</Form.Label>
                        <Form.Select
                          value={settings.dateFormat}
                          onChange={(e) => handleSettingChange('dateFormat', e.target.value)}
                        >
                          <option value="MM/DD/YYYY">MM/DD/YYYY (US)</option>
                          <option value="DD/MM/YYYY">DD/MM/YYYY (UK)</option>
                          <option value="YYYY-MM-DD">YYYY-MM-DD (ISO)</option>
                          <option value="DD MMM YYYY">DD MMM YYYY</option>
                        </Form.Select>
                      </Form.Group>

                      <Form.Group className="mb-3">
                        <Form.Label>Currency</Form.Label>
                        <Form.Select
                          value={settings.currency}
                          onChange={(e) => handleSettingChange('currency', e.target.value)}
                        >
                          <option value="USD">USD - US Dollar</option>
                          <option value="EUR">EUR - Euro</option>
                          <option value="GBP">GBP - British Pound</option>
                          <option value="JPY">JPY - Japanese Yen</option>
                          <option value="CAD">CAD - Canadian Dollar</option>
                          <option value="AUD">AUD - Australian Dollar</option>
                        </Form.Select>
                      </Form.Group>
                    </Col>

                    <Col md={6}>
                      <Form.Group className="mb-3">
                        <Form.Label>Language</Form.Label>
                        <Form.Select
                          value={settings.language}
                          onChange={(e) => handleSettingChange('language', e.target.value)}
                        >
                          <option value="en">English</option>
                          <option value="es">Español</option>
                          <option value="fr">Français</option>
                          <option value="de">Deutsch</option>
                          <option value="it">Italiano</option>
                          <option value="pt">Português</option>
                        </Form.Select>
                      </Form.Group>

                      <Form.Group className="mb-3">
                        <Form.Label>Theme</Form.Label>
                        <Form.Select
                          value={settings.theme}
                          onChange={(e) => handleSettingChange('theme', e.target.value)}
                        >
                          <option value="light">Light</option>
                          <option value="dark">Dark</option>
                          <option value="auto">Auto (System)</option>
                        </Form.Select>
                      </Form.Group>

                      <Form.Group className="mb-3">
                        <Form.Label>Items per Page</Form.Label>
                        <Form.Select
                          value={settings.itemsPerPage}
                          onChange={(e) => handleSettingChange('itemsPerPage', parseInt(e.target.value))}
                        >
                          <option value={10}>10</option>
                          <option value={25}>25</option>
                          <option value={50}>50</option>
                          <option value={100}>100</option>
                        </Form.Select>
                      </Form.Group>
                    </Col>
                  </Row>
                </Card.Body>
              </Card>
            </Tab>

            {/* Notifications */}
            <Tab eventKey="notifications" title="Notifications">
              <Card>
                <Card.Header>
                  <h5 className="mb-0">
                    <i className="bi bi-bell me-2"></i>
                    Notification Settings
                  </h5>
                </Card.Header>
                <Card.Body>
                  <Row>
                    <Col md={6}>
                      <Form.Group className="mb-3">
                        <Form.Check
                          type="switch"
                          id="email-notifications"
                          label="Email Notifications"
                          checked={settings.emailNotifications}
                          onChange={(e) => handleSettingChange('emailNotifications', e.target.checked)}
                        />
                        <Form.Text className="text-muted">
                          Receive email notifications for important updates
                        </Form.Text>
                      </Form.Group>

                      <Form.Group className="mb-3">
                        <Form.Label>Reminder Days Before Renewal</Form.Label>
                        <Form.Select
                          value={settings.reminderDays}
                          onChange={(e) => handleSettingChange('reminderDays', parseInt(e.target.value))}
                        >
                          <option value={1}>1 day</option>
                          <option value={3}>3 days</option>
                          <option value={7}>1 week</option>
                          <option value={14}>2 weeks</option>
                          <option value={30}>1 month</option>
                        </Form.Select>
                      </Form.Group>
                    </Col>

                    <Col md={6}>
                      <Form.Group className="mb-3">
                        <Form.Check
                          type="switch"
                          id="weekly-report"
                          label="Weekly Reports"
                          checked={settings.weeklyReport}
                          onChange={(e) => handleSettingChange('weeklyReport', e.target.checked)}
                        />
                        <Form.Text className="text-muted">
                          Receive weekly spending summary reports
                        </Form.Text>
                      </Form.Group>

                      <Form.Group className="mb-3">
                        <Form.Check
                          type="switch"
                          id="monthly-report"
                          label="Monthly Reports"
                          checked={settings.monthlyReport}
                          onChange={(e) => handleSettingChange('monthlyReport', e.target.checked)}
                        />
                        <Form.Text className="text-muted">
                          Receive monthly spending analysis reports
                        </Form.Text>
                      </Form.Group>
                    </Col>
                  </Row>
                </Card.Body>
              </Card>
            </Tab>

            {/* Privacy */}
            <Tab eventKey="privacy" title="Privacy">
              <Card>
                <Card.Header>
                  <h5 className="mb-0">
                    <i className="bi bi-shield-lock me-2"></i>
                    Privacy Settings
                  </h5>
                </Card.Header>
                <Card.Body>
                  <Row>
                    <Col md={6}>
                      <Form.Group className="mb-3">
                        <Form.Label>Profile Visibility</Form.Label>
                        <Form.Select
                          value={settings.profileVisibility}
                          onChange={(e) => handleSettingChange('profileVisibility', e.target.value)}
                        >
                          <option value="private">Private</option>
                          <option value="friends">Friends Only</option>
                          <option value="public">Public</option>
                        </Form.Select>
                        <Form.Text className="text-muted">
                          Control who can see your profile information
                        </Form.Text>
                      </Form.Group>
                    </Col>

                    <Col md={6}>
                      <Form.Group className="mb-3">
                        <Form.Check
                          type="switch"
                          id="share-data"
                          label="Share Anonymous Usage Data"
                          checked={settings.shareData}
                          onChange={(e) => handleSettingChange('shareData', e.target.checked)}
                        />
                        <Form.Text className="text-muted">
                          Help improve SubTrack by sharing anonymous usage statistics
                        </Form.Text>
                      </Form.Group>
                    </Col>
                  </Row>

                  <hr />

                  <div className="d-flex justify-content-between align-items-center">
                    <div>
                      <h6>Data Export</h6>
                      <p className="text-muted mb-0">Download all your data in a portable format</p>
                    </div>
                    <Button
                      variant="outline-primary"
                      onClick={handleExportData}
                      disabled={loading}
                    >
                      <i className="bi bi-download me-2"></i>
                      Export Data
                    </Button>
                  </div>

                  <hr />

                  <div className="d-flex justify-content-between align-items-center">
                    <div>
                      <h6 className="text-danger">Delete Account</h6>
                      <p className="text-muted mb-0">Permanently delete your account and all data</p>
                    </div>
                    <Button
                      variant="outline-danger"
                      onClick={handleDeleteAccount}
                    >
                      <i className="bi bi-trash me-2"></i>
                      Delete Account
                    </Button>
                  </div>
                </Card.Body>
              </Card>
            </Tab>

            {/* Account */}
            <Tab eventKey="account" title="Account">
              <Card>
                <Card.Header>
                  <h5 className="mb-0">
                    <i className="bi bi-person-circle me-2"></i>
                    Account Settings
                  </h5>
                </Card.Header>
                <Card.Body>
                  <Row>
                    <Col md={6}>
                      <h6>Current Account</h6>
                      <p className="text-muted">Username: <strong>{user?.username}</strong></p>
                      <p className="text-muted">Email: <strong>{user?.email || 'Not set'}</strong></p>
                      <p className="text-muted">Member since: <strong>Recently</strong></p>

                      <hr />

                      <div className="d-grid gap-2">
                        <Button
                          variant="outline-primary"
                          onClick={handleChangePassword}
                        >
                          <i className="bi bi-key me-2"></i>
                          Change Password
                        </Button>
                        <Button
                          variant="outline-secondary"
                          onClick={handleUpdateEmail}
                        >
                          <i className="bi bi-envelope me-2"></i>
                          Update Email
                        </Button>
                      </div>
                    </Col>

                    <Col md={6}>
                      <h6>Security</h6>
                      <div className="d-flex justify-content-between align-items-center mb-3">
                        <div>
                          <span>Two-Factor Authentication</span>
                          <br />
                          <small className="text-muted">Add an extra layer of security</small>
                        </div>
                        <Button
                          variant="outline-success"
                          size="sm"
                          onClick={handleEnable2FA}
                        >
                          Enable
                        </Button>
                      </div>

                      <div className="d-flex justify-content-between align-items-center mb-3">
                        <div>
                          <span>Active Sessions</span>
                          <br />
                          <small className="text-muted">Manage your active login sessions</small>
                        </div>
                        <Button
                          variant="outline-secondary"
                          size="sm"
                          onClick={handleViewSessions}
                        >
                          View
                        </Button>
                      </div>

                      <div className="d-flex justify-content-between align-items-center">
                        <div>
                          <span>Login History</span>
                          <br />
                          <small className="text-muted">View your recent login activity</small>
                        </div>
                        <Button
                          variant="outline-secondary"
                          size="sm"
                          onClick={handleViewLoginHistory}
                        >
                          View
                        </Button>
                      </div>
                    </Col>
                  </Row>
                </Card.Body>
              </Card>
            </Tab>
          </Tabs>
        </Col>
      </Row>
    </Container>
  );
};

export default SettingsPage;