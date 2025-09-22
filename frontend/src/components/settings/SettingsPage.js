import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Form, Button, Alert, Tab, Tabs, Modal, Table } from 'react-bootstrap';
import { useAuth } from '../../contexts/AuthContext';
import { authAPI } from '../../services/api';

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

  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [deleteForm, setDeleteForm] = useState({
    password: '',
    confirmation: ''
  });
  const [deleteLoading, setDeleteLoading] = useState(false);

  const handleDeleteAccount = () => {
    setShowDeleteModal(true);
    setDeleteForm({
      password: '',
      confirmation: ''
    });
    setError('');
  };

  const handleDeleteFormChange = (e) => {
    setDeleteForm({
      ...deleteForm,
      [e.target.name]: e.target.value
    });
  };

  const handleSubmitDeleteAccount = async (e) => {
    e.preventDefault();
    setDeleteLoading(true);
    setError('');

    try {
      const response = await authAPI.deleteAccount(deleteForm.password, deleteForm.confirmation);

      if (response.status === 'success') {
        // Account deleted successfully - redirect to login
        window.location.href = '/login';
      } else {
        setError(response.message || 'Failed to delete account');
      }
    } catch (err) {
      console.error('Delete account error:', err);
      setError(err.response?.data?.message || 'Failed to delete account. Please try again.');
    } finally {
      setDeleteLoading(false);
    }
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
                    <Col md={12}>
                      <div className="text-center py-4">
                        <i className="bi bi-person-circle" style={{fontSize: '3rem', color: '#6c757d'}}></i>
                        <h5 className="mt-3">Account Overview</h5>
                        <p className="text-muted mb-3">Basic account information</p>
                        <div className="row justify-content-center">
                          <div className="col-md-6">
                            <div className="card bg-light">
                              <div className="card-body">
                                <div className="mb-2">
                                  <strong>Username:</strong> <span className="text-muted">{user?.username}</span>
                                </div>
                                <div className="mb-2">
                                  <strong>Email:</strong> <span className="text-muted">{user?.email || 'Not set'}</span>
                                </div>
                                <div className="mb-3">
                                  <strong>Member since:</strong> <span className="text-muted">Recently</span>
                                </div>
                                <Alert variant="info" className="mb-0">
                                  <i className="bi bi-info-circle me-2"></i>
                                  For security settings including password changes, 2FA, and session management, please visit your <strong><a href="/profile" className="alert-link">Profile page</a></strong>.
                                </Alert>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </Col>
                  </Row>
                </Card.Body>
              </Card>
            </Tab>
          </Tabs>
        </Col>
      </Row>


      {/* Account Deletion Modal */}
      <Modal show={showDeleteModal} onHide={() => setShowDeleteModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title className="text-danger">Delete Account</Modal.Title>
        </Modal.Header>
        <Form onSubmit={handleSubmitDeleteAccount}>
          <Modal.Body>
            <Alert variant="danger">
              <i className="bi bi-exclamation-triangle me-2"></i>
              <strong>Warning:</strong> This action cannot be undone. All your data including subscriptions, spaces, and settings will be permanently deleted.
            </Alert>

            {error && (
              <Alert variant="danger" dismissible onClose={() => setError('')}>
                <i className="bi bi-exclamation-triangle me-2"></i>
                {error}
              </Alert>
            )}

            <Form.Group className="mb-3">
              <Form.Label>Password (for verification) *</Form.Label>
              <Form.Control
                type="password"
                name="password"
                value={deleteForm.password}
                onChange={handleDeleteFormChange}
                required
                disabled={deleteLoading}
                placeholder="Enter your current password"
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Type "DELETE" to confirm *</Form.Label>
              <Form.Control
                type="text"
                name="confirmation"
                value={deleteForm.confirmation}
                onChange={handleDeleteFormChange}
                required
                disabled={deleteLoading}
                placeholder="Type DELETE to confirm"
              />
              <Form.Text className="text-muted">
                This confirmation is required to prevent accidental deletions.
              </Form.Text>
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={() => setShowDeleteModal(false)} disabled={deleteLoading}>
              Cancel
            </Button>
            <Button variant="danger" type="submit" disabled={deleteLoading}>
              {deleteLoading ? (
                <>
                  <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                  Deleting Account...
                </>
              ) : (
                <>
                  <i className="bi bi-trash me-2"></i>
                  Delete Account
                </>
              )}
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>

    </Container>
  );
};

export default SettingsPage;