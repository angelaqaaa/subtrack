import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Table, Button, Alert, Modal, Form, Badge, InputGroup } from 'react-bootstrap';
import { format, parseISO, isBefore, isAfter, addDays } from 'date-fns';
import { useAuth } from '../../contexts/AuthContext';

const ApiKeysPage = () => {
  const { user } = useAuth();
  const [apiKeys, setApiKeys] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

  // Modal states
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showUsageModal, setShowUsageModal] = useState(false);
  const [selectedKey, setSelectedKey] = useState(null);

  // Form state
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    permissions: ['read'],
    expiresAt: ''
  });

  const [submitting, setSubmitting] = useState(false);
  const [newKeyValue, setNewKeyValue] = useState('');

  const permissionOptions = [
    { value: 'read', label: 'Read Only', description: 'View subscriptions and reports' },
    { value: 'write', label: 'Read & Write', description: 'Create and modify subscriptions' },
    { value: 'admin', label: 'Full Access', description: 'Complete access to your account' }
  ];

  useEffect(() => {
    loadApiKeys();
  }, []);

  const loadApiKeys = async () => {
    try {
      setLoading(true);
      setError('');

      // Make API keys user-specific by including user ID in localStorage key
      const userSpecificKey = `userApiKeys_${user?.id || 'unknown'}`;
      const savedKeys = localStorage.getItem(userSpecificKey);
      if (savedKeys) {
        setApiKeys(JSON.parse(savedKeys));
      } else {
        // Demo data
        setApiKeys([
          {
            id: '1',
            name: 'Mobile App',
            description: 'API key for mobile application',
            key: 'st_live_...abc123',
            permissions: ['read'],
            createdAt: new Date().toISOString(),
            lastUsedAt: new Date(Date.now() - 86400000).toISOString(), // 1 day ago
            usageCount: 42,
            isActive: true,
            expiresAt: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString() // 30 days from now
          },
          {
            id: '2',
            name: 'Production API',
            description: 'Main production API key',
            key: 'st_live_...def456',
            permissions: ['write'],
            createdAt: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString(), // 30 days ago
            lastUsedAt: new Date(Date.now() - 3600000).toISOString(), // 1 hour ago
            usageCount: 1238,
            isActive: true,
            expiresAt: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString() // 3 days from now (expiring soon)
          },
          {
            id: '3',
            name: 'Legacy Integration',
            description: 'Old integration that should be updated',
            key: 'st_live_...ghi789',
            permissions: ['admin'],
            createdAt: new Date(Date.now() - 90 * 24 * 60 * 60 * 1000).toISOString(), // 90 days ago
            lastUsedAt: new Date(Date.now() - 10 * 24 * 60 * 60 * 1000).toISOString(), // 10 days ago
            usageCount: 2156,
            isActive: false,
            expiresAt: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString() // Already expired
          },
          {
            id: '4',
            name: 'Development Testing',
            description: 'Key for development and testing',
            key: 'st_live_...jkl012',
            permissions: ['read'],
            createdAt: new Date().toISOString(),
            lastUsedAt: null,
            usageCount: 0,
            isActive: true,
            expiresAt: null // Never expires
          }
        ]);
      }
    } catch (err) {
      console.error('Failed to load API keys:', err);
      setError('Failed to load API keys. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const generateApiKey = () => {
    const prefix = 'st_live_';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < 32; i++) {
      result += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    return prefix + result;
  };

  const handleFormChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const handlePermissionChange = (permission) => {
    setFormData(prev => ({
      ...prev,
      permissions: [permission] // For simplicity, only one permission level at a time
    }));
  };

  const handleCreateApiKey = async () => {
    try {
      setSubmitting(true);
      setError('');

      const newKey = {
        id: Date.now().toString(),
        name: formData.name,
        description: formData.description,
        key: generateApiKey(),
        permissions: formData.permissions,
        createdAt: new Date().toISOString(),
        lastUsedAt: null,
        usageCount: 0,
        isActive: true,
        expiresAt: formData.expiresAt || null
      };

      const updatedKeys = [...apiKeys, newKey];
      setApiKeys(updatedKeys);
      localStorage.setItem(`userApiKeys_${user?.id || 'unknown'}`, JSON.stringify(updatedKeys));

      setNewKeyValue(newKey.key);
      setSuccessMessage('API key created successfully!');
      resetForm();
      setTimeout(() => setSuccessMessage(''), 5000);

    } catch (err) {
      console.error('Create API key error:', err);
      setError('Failed to create API key. Please try again.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleDeleteApiKey = async () => {
    try {
      setSubmitting(true);
      const updatedKeys = apiKeys.filter(key => key.id !== selectedKey.id);
      setApiKeys(updatedKeys);
      localStorage.setItem(`userApiKeys_${user?.id || 'unknown'}`, JSON.stringify(updatedKeys));

      setSuccessMessage(`API key "${selectedKey.name}" deleted successfully!`);
      setShowDeleteModal(false);
      setSelectedKey(null);
      setTimeout(() => setSuccessMessage(''), 3000);

    } catch (err) {
      console.error('Delete API key error:', err);
      setError('Failed to delete API key. Please try again.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleToggleApiKey = async (keyId) => {
    try {
      const updatedKeys = apiKeys.map(key =>
        key.id === keyId ? { ...key, isActive: !key.isActive } : key
      );
      setApiKeys(updatedKeys);
      localStorage.setItem(`userApiKeys_${user?.id || 'unknown'}`, JSON.stringify(updatedKeys));

      const key = updatedKeys.find(k => k.id === keyId);
      setSuccessMessage(`API key ${key.isActive ? 'activated' : 'deactivated'} successfully!`);
      setTimeout(() => setSuccessMessage(''), 3000);

    } catch (err) {
      console.error('Toggle API key error:', err);
      setError('Failed to update API key status. Please try again.');
    }
  };

  const resetForm = () => {
    setFormData({
      name: '',
      description: '',
      permissions: ['read'],
      expiresAt: ''
    });
  };

  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(() => {
      setSuccessMessage('API key copied to clipboard!');
      setTimeout(() => setSuccessMessage(''), 2000);
    });
  };

  const maskApiKey = (key) => {
    if (key.length <= 12) return key;
    return key.substring(0, 8) + '...' + key.substring(key.length - 4);
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getPermissionBadge = (permissions) => {
    const permission = permissions[0];
    switch (permission) {
      case 'read': return <Badge bg="info">Read Only</Badge>;
      case 'write': return <Badge bg="warning">Read & Write</Badge>;
      case 'admin': return <Badge bg="danger">Full Access</Badge>;
      default: return <Badge bg="secondary">Unknown</Badge>;
    }
  };

  const getExpirationInfo = (apiKey) => {
    if (!apiKey.expiresAt) {
      return (
        <small className="text-muted">
          <i className="bi bi-infinity me-1"></i>
          Never expires
        </small>
      );
    }

    const expirationDate = parseISO(apiKey.expiresAt);
    const now = new Date();
    const warningDate = addDays(now, 7); // Warning 7 days before expiration

    if (isBefore(expirationDate, now)) {
      // Already expired
      return (
        <div>
          <Badge bg="danger">
            <i className="bi bi-exclamation-triangle me-1"></i>
            Expired
          </Badge>
          <div>
            <small className="text-danger">
              {format(expirationDate, 'MMM dd, yyyy')}
            </small>
          </div>
        </div>
      );
    } else if (isBefore(expirationDate, warningDate)) {
      // Expiring soon
      return (
        <div>
          <Badge bg="warning">
            <i className="bi bi-clock me-1"></i>
            Expires soon
          </Badge>
          <div>
            <small className="text-warning">
              {format(expirationDate, 'MMM dd, yyyy')}
            </small>
          </div>
        </div>
      );
    } else {
      // Normal expiration
      return (
        <div>
          <small className="text-muted">
            <i className="bi bi-calendar-event me-1"></i>
            {format(expirationDate, 'MMM dd, yyyy')}
          </small>
        </div>
      );
    }
  };

  if (loading) {
    return (
      <Container>
        <div className="text-center py-5">
          <div className="spinner-border" role="status">
            <span className="visually-hidden">Loading...</span>
          </div>
          <div className="mt-2">Loading API keys...</div>
        </div>
      </Container>
    );
  }

  return (
    <Container>
      <Row>
        <Col>
          <div className="d-flex justify-content-between align-items-center mb-4">
            <h1 className="h2">
              <i className="bi bi-key me-2"></i>
              API Keys
            </h1>
            <div className="d-flex gap-2">
              <Button
                variant="outline-info"
                size="sm"
                onClick={() => setShowUsageModal(true)}
              >
                <i className="bi bi-info-circle me-2"></i>
                API Usage Guide
              </Button>
              <Button
                variant="primary"
                onClick={() => setShowCreateModal(true)}
              >
                <i className="bi bi-plus-circle me-2"></i>
                Create API Key
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

          {newKeyValue && (
            <Alert variant="warning" dismissible onClose={() => setNewKeyValue('')}>
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <strong>Your new API key:</strong> Make sure to copy it now - you won't be able to see it again!
                </div>
                <Button
                  variant="outline-dark"
                  size="sm"
                  onClick={() => copyToClipboard(newKeyValue)}
                >
                  Copy
                </Button>
              </div>
              <code className="d-block mt-2 p-2 bg-light border rounded">{newKeyValue}</code>
            </Alert>
          )}
        </Col>
      </Row>

      <Row>
        <Col lg={8}>
          <Card className="mb-4">
            <Card.Header>
              <h5 className="mb-0">
                <i className="bi bi-list-ul me-2"></i>
                Your API Keys ({apiKeys.length})
              </h5>
            </Card.Header>
            <Card.Body>
              {apiKeys.length > 0 ? (
                <Table responsive hover>
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Key</th>
                      <th>Permissions</th>
                      <th>Usage</th>
                      <th>Last Used</th>
                      <th>Expires</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {apiKeys.map((apiKey) => (
                      <tr key={apiKey.id}>
                        <td>
                          <div>
                            <strong>{apiKey.name}</strong>
                            {apiKey.description && (
                              <div>
                                <small className="text-muted">{apiKey.description}</small>
                              </div>
                            )}
                          </div>
                        </td>
                        <td>
                          <div className="d-flex align-items-center">
                            <code className="me-2">{maskApiKey(apiKey.key)}</code>
                            <Button
                              variant="outline-secondary"
                              size="sm"
                              onClick={() => copyToClipboard(apiKey.key)}
                              title="Copy full key"
                            >
                              <i className="bi bi-clipboard"></i>
                            </Button>
                          </div>
                        </td>
                        <td>{getPermissionBadge(apiKey.permissions)}</td>
                        <td>
                          <Badge bg="secondary" pill>
                            {apiKey.usageCount} calls
                          </Badge>
                        </td>
                        <td>
                          <small className="text-muted">
                            {apiKey.lastUsedAt ? formatDate(apiKey.lastUsedAt) : 'Never'}
                          </small>
                        </td>
                        <td>
                          {getExpirationInfo(apiKey)}
                        </td>
                        <td>
                          <Badge bg={apiKey.isActive ? 'success' : 'secondary'} pill>
                            {apiKey.isActive ? 'Active' : 'Inactive'}
                          </Badge>
                        </td>
                        <td>
                          <div className="btn-group" role="group">
                            <Button
                              variant={apiKey.isActive ? "outline-warning" : "outline-success"}
                              size="sm"
                              onClick={() => handleToggleApiKey(apiKey.id)}
                              title={apiKey.isActive ? "Deactivate" : "Activate"}
                            >
                              <i className={`bi ${apiKey.isActive ? 'bi-pause' : 'bi-play'}`}></i>
                            </Button>
                            <Button
                              variant="outline-danger"
                              size="sm"
                              onClick={() => {
                                setSelectedKey(apiKey);
                                setShowDeleteModal(true);
                              }}
                              title="Delete"
                            >
                              <i className="bi bi-trash"></i>
                            </Button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </Table>
              ) : (
                <div className="text-center py-4">
                  <i className="bi bi-key text-muted" style={{ fontSize: '3rem' }}></i>
                  <h6 className="text-muted mt-2">No API keys found</h6>
                  <p className="text-muted">
                    Create your first API key to start integrating with SubTrack.
                  </p>
                  <Button variant="primary" onClick={() => setShowCreateModal(true)}>
                    <i className="bi bi-plus-circle me-2"></i>
                    Create Your First API Key
                  </Button>
                </div>
              )}
            </Card.Body>
          </Card>
        </Col>

        <Col lg={4}>
          <Card className="mb-4">
            <Card.Header>
              <h5 className="mb-0">
                <i className="bi bi-info-circle me-2"></i>
                API Documentation
              </h5>
            </Card.Header>
            <Card.Body>
              <h6>Getting Started</h6>
              <p className="text-muted small">
                Use your API key to access SubTrack data programmatically. Include the key in your requests:
              </p>
              <code className="d-block mb-3 p-2 bg-light border rounded small">
                curl -H "Authorization: Bearer your_api_key" https://api.subtrack.com/v1/subscriptions
              </code>

              <h6>Endpoints</h6>
              <ul className="list-unstyled small text-muted">
                <li className="mb-1">
                  <code>GET /v1/subscriptions</code><br />
                  <small>List all subscriptions</small>
                </li>
                <li className="mb-1">
                  <code>POST /v1/subscriptions</code><br />
                  <small>Create a new subscription</small>
                </li>
                <li className="mb-1">
                  <code>GET /v1/insights</code><br />
                  <small>Get spending insights</small>
                </li>
                <li className="mb-1">
                  <code>GET /v1/reports</code><br />
                  <small>Generate reports</small>
                </li>
              </ul>

              <Button
                variant="outline-primary"
                size="sm"
                className="w-100"
                onClick={() => setShowUsageModal(true)}
              >
                <i className="bi bi-book me-2"></i>
                View Full Documentation
              </Button>
            </Card.Body>
          </Card>

          <Card>
            <Card.Header>
              <h5 className="mb-0">
                <i className="bi bi-shield-check me-2"></i>
                Security Best Practices
              </h5>
            </Card.Header>
            <Card.Body>
              <ul className="list-unstyled small">
                <li className="mb-2">
                  <i className="bi bi-check-circle text-success me-2"></i>
                  Keep your API keys secure and never share them
                </li>
                <li className="mb-2">
                  <i className="bi bi-check-circle text-success me-2"></i>
                  Use environment variables to store keys
                </li>
                <li className="mb-2">
                  <i className="bi bi-check-circle text-success me-2"></i>
                  Rotate keys regularly for better security
                </li>
                <li className="mb-2">
                  <i className="bi bi-check-circle text-success me-2"></i>
                  Use read-only keys when possible
                </li>
                <li className="mb-2">
                  <i className="bi bi-check-circle text-success me-2"></i>
                  Monitor key usage for suspicious activity
                </li>
              </ul>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Create API Key Modal */}
      <Modal show={showCreateModal} onHide={() => setShowCreateModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>
            <i className="bi bi-plus-circle me-2"></i>
            Create New API Key
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form.Group className="mb-3">
            <Form.Label>Key Name *</Form.Label>
            <Form.Control
              type="text"
              placeholder="e.g., Mobile App, Dashboard Integration"
              value={formData.name}
              onChange={(e) => handleFormChange('name', e.target.value)}
            />
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>Description</Form.Label>
            <Form.Control
              as="textarea"
              rows={2}
              placeholder="Brief description of how this key will be used..."
              value={formData.description}
              onChange={(e) => handleFormChange('description', e.target.value)}
            />
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>Permissions *</Form.Label>
            {permissionOptions.map((option) => (
              <Form.Check
                key={option.value}
                type="radio"
                name="permissions"
                id={`permission-${option.value}`}
                label={
                  <div>
                    <strong>{option.label}</strong>
                    <div className="text-muted small">{option.description}</div>
                  </div>
                }
                checked={formData.permissions.includes(option.value)}
                onChange={() => handlePermissionChange(option.value)}
                className="mb-2"
              />
            ))}
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>Expiration Date (Optional)</Form.Label>
            <Form.Control
              type="date"
              value={formData.expiresAt}
              onChange={(e) => handleFormChange('expiresAt', e.target.value)}
              min={new Date().toISOString().split('T')[0]}
            />
            <Form.Text className="text-muted">
              Leave empty for a key that never expires
            </Form.Text>
          </Form.Group>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowCreateModal(false)}>
            Cancel
          </Button>
          <Button
            variant="primary"
            onClick={handleCreateApiKey}
            disabled={!formData.name.trim() || submitting}
          >
            {submitting ? (
              <>
                <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                Creating...
              </>
            ) : (
              <>
                <i className="bi bi-check-circle me-2"></i>
                Create API Key
              </>
            )}
          </Button>
        </Modal.Footer>
      </Modal>

      {/* Delete API Key Modal */}
      <Modal show={showDeleteModal} onHide={() => setShowDeleteModal(false)}>
        <Modal.Header closeButton>
          <Modal.Title>
            <i className="bi bi-exclamation-triangle text-danger me-2"></i>
            Delete API Key
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p>
            Are you sure you want to delete the API key <strong>"{selectedKey?.name}"</strong>?
          </p>
          <div className="alert alert-warning">
            <i className="bi bi-exclamation-triangle me-2"></i>
            This action cannot be undone. Any applications using this key will stop working immediately.
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowDeleteModal(false)}>
            Cancel
          </Button>
          <Button
            variant="danger"
            onClick={handleDeleteApiKey}
            disabled={submitting}
          >
            {submitting ? (
              <>
                <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                Deleting...
              </>
            ) : (
              <>
                <i className="bi bi-trash me-2"></i>
                Delete API Key
              </>
            )}
          </Button>
        </Modal.Footer>
      </Modal>

      {/* API Usage Guide Modal */}
      <Modal show={showUsageModal} onHide={() => setShowUsageModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>
            <i className="bi bi-code-square me-2"></i>
            API Usage Guide
          </Modal.Title>
        </Modal.Header>
        <Modal.Body style={{ maxHeight: '70vh', overflowY: 'auto' }}>
          <div className="mb-4">
            <h5 className="text-primary">
              <i className="bi bi-info-circle me-2"></i>
              Getting Started
            </h5>
            <p>
              The SubTrack API allows you to programmatically manage your subscriptions.
              All API endpoints require authentication using your API key.
            </p>
          </div>

          <div className="mb-4">
            <h5 className="text-success">
              <i className="bi bi-react me-2"></i>
              Using with React/JavaScript
            </h5>
            <p>Perfect for building custom dashboards, mobile apps, or integrating with existing React applications.</p>

            <div className="bg-light p-3 rounded mb-3">
              <h6>Basic Setup:</h6>
              <pre className="mb-2"><code>{`// Install axios for HTTP requests
npm install axios

// Create an API client
const apiClient = axios.create({
  baseURL: 'https://your-domain.com/api',
  headers: {
    'Authorization': 'Bearer YOUR_API_KEY',
    'Content-Type': 'application/json'
  }
});`}</code></pre>
            </div>

            <div className="bg-light p-3 rounded mb-3">
              <h6>Fetching Subscriptions:</h6>
              <pre className="mb-2"><code>{`// Get all subscriptions
const getSubscriptions = async () => {
  try {
    const response = await apiClient.get('/subscriptions');
    console.log(response.data);
  } catch (error) {
    console.error('Error:', error);
  }
};

// Create a new subscription
const createSubscription = async (subscriptionData) => {
  try {
    const response = await apiClient.post('/subscriptions', subscriptionData);
    return response.data;
  } catch (error) {
    console.error('Error:', error);
  }
};`}</code></pre>
            </div>

            <div className="alert alert-info">
              <strong>React Hook Example:</strong>
              <pre className="mt-2 mb-0"><code>{`const useSubscriptions = () => {
  const [subscriptions, setSubscriptions] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchSubscriptions = async () => {
      try {
        const response = await apiClient.get('/subscriptions');
        setSubscriptions(response.data.subscriptions);
      } catch (error) {
        console.error('Error fetching subscriptions:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchSubscriptions();
  }, []);

  return { subscriptions, loading };
};`}</code></pre>
            </div>
          </div>

          <div className="mb-4">
            <h5 className="text-warning">
              <i className="bi bi-wordpress me-2"></i>
              Using with WordPress
            </h5>
            <p>Ideal for WordPress sites, plugins, or themes that need subscription data integration.</p>

            <div className="bg-light p-3 rounded mb-3">
              <h6>PHP Setup:</h6>
              <pre className="mb-2"><code>{`<?php
// Add to functions.php or plugin file
function subtrack_api_request($endpoint, $method = 'GET', $data = null) {
    $api_key = 'YOUR_API_KEY';
    $base_url = 'https://your-domain.com/api';

    $args = array(
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ),
    );

    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($base_url . $endpoint, $args);

    if (is_wp_error($response)) {
        return false;
    }

    return json_decode(wp_remote_retrieve_body($response), true);
}`}</code></pre>
            </div>

            <div className="bg-light p-3 rounded mb-3">
              <h6>WordPress Usage Examples:</h6>
              <pre className="mb-2"><code>{`// Get subscriptions in a shortcode
function subtrack_subscriptions_shortcode() {
    $subscriptions = subtrack_api_request('/subscriptions');

    if (!$subscriptions || !isset($subscriptions['data']['subscriptions'])) {
        return 'Unable to load subscriptions.';
    }

    $output = '<div class="subtrack-subscriptions">';
    foreach ($subscriptions['data']['subscriptions'] as $sub) {
        $output .= '<div class="subscription-item">';
        $output .= '<h4>' . esc_html($sub['service_name']) . '</h4>';
        $output .= '<p>Cost: $' . esc_html($sub['cost']) . ' / ' . esc_html($sub['billing_cycle']) . '</p>';
        $output .= '</div>';
    }
    $output .= '</div>';

    return $output;
}
add_shortcode('subtrack_subscriptions', 'subtrack_subscriptions_shortcode');

// Create a WordPress widget
class SubTrack_Widget extends WP_Widget {
    public function widget($args, $instance) {
        $subscriptions = subtrack_api_request('/subscriptions');

        echo $args['before_widget'];
        echo $args['before_title'] . 'My Subscriptions' . $args['after_title'];

        if ($subscriptions && isset($subscriptions['data']['subscriptions'])) {
            echo '<ul>';
            foreach ($subscriptions['data']['subscriptions'] as $sub) {
                if ($sub['is_active']) {
                    echo '<li>' . esc_html($sub['service_name']) . ' - $' . esc_html($sub['cost']) . '</li>';
                }
            }
            echo '</ul>';
        }

        echo $args['after_widget'];
    }
}`}</code></pre>
            </div>

            <div className="alert alert-warning">
              <strong>WordPress Best Practices:</strong>
              <ul className="mb-0">
                <li>Use <code>wp_remote_request()</code> instead of cURL</li>
                <li>Always sanitize and escape output data</li>
                <li>Implement proper error handling</li>
                <li>Consider caching API responses with WordPress transients</li>
                <li>Store API keys securely in wp-config.php or options table</li>
              </ul>
            </div>
          </div>

          <div className="mb-4">
            <h5 className="text-danger">
              <i className="bi bi-shield-lock me-2"></i>
              Security Best Practices
            </h5>
            <ul>
              <li><strong>Keep API keys secure:</strong> Never commit them to version control</li>
              <li><strong>Use environment variables:</strong> Store keys in .env files or server environment</li>
              <li><strong>Rotate keys regularly:</strong> Create new keys and deactivate old ones periodically</li>
              <li><strong>Use appropriate permissions:</strong> Give each key only the minimum required access</li>
              <li><strong>Monitor usage:</strong> Check the usage statistics regularly for unusual activity</li>
              <li><strong>HTTPS only:</strong> Always use secure connections for API requests</li>
            </ul>
          </div>

          <div className="mb-4">
            <h5 className="text-info">
              <i className="bi bi-link me-2"></i>
              Available Endpoints
            </h5>
            <div className="table-responsive">
              <table className="table table-sm table-striped">
                <thead>
                  <tr>
                    <th>Method</th>
                    <th>Endpoint</th>
                    <th>Description</th>
                    <th>Required Permission</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><Badge bg="success">GET</Badge></td>
                    <td>/subscriptions</td>
                    <td>Get all subscriptions</td>
                    <td>Read</td>
                  </tr>
                  <tr>
                    <td><Badge bg="primary">POST</Badge></td>
                    <td>/subscriptions</td>
                    <td>Create a subscription</td>
                    <td>Write</td>
                  </tr>
                  <tr>
                    <td><Badge bg="warning">PUT</Badge></td>
                    <td>/subscriptions/:id</td>
                    <td>Update a subscription</td>
                    <td>Write</td>
                  </tr>
                  <tr>
                    <td><Badge bg="danger">DELETE</Badge></td>
                    <td>/subscriptions/:id</td>
                    <td>Delete a subscription</td>
                    <td>Write</td>
                  </tr>
                  <tr>
                    <td><Badge bg="success">GET</Badge></td>
                    <td>/insights</td>
                    <td>Get spending insights</td>
                    <td>Read</td>
                  </tr>
                  <tr>
                    <td><Badge bg="success">GET</Badge></td>
                    <td>/categories</td>
                    <td>Get all categories</td>
                    <td>Read</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div className="alert alert-primary">
            <h6><i className="bi bi-lightbulb me-2"></i>Pro Tips:</h6>
            <ul className="mb-0">
              <li><strong>React:</strong> Use React Query or SWR for better data fetching and caching</li>
              <li><strong>WordPress:</strong> Leverage WordPress hooks and filters for better integration</li>
              <li><strong>Rate Limiting:</strong> Implement client-side rate limiting to avoid hitting API limits</li>
              <li><strong>Error Handling:</strong> Always implement comprehensive error handling and user feedback</li>
              <li><strong>Testing:</strong> Test your integration with different API key permission levels</li>
            </ul>
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowUsageModal(false)}>
            Close
          </Button>
          <Button variant="primary" onClick={() => window.open('https://docs.github.com/en/rest/guides/getting-started-with-the-rest-api', '_blank')}>
            <i className="bi bi-book me-2"></i>
            Full API Documentation
          </Button>
        </Modal.Footer>
      </Modal>
    </Container>
  );
};

export default ApiKeysPage;