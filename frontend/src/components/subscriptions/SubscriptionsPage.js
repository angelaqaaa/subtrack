import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Table, Button, Badge, Alert, Form, InputGroup } from 'react-bootstrap';
import { subscriptionsAPI } from '../../services/api';
import AddSubscriptionModal from './AddSubscriptionModal';
import EditSubscriptionModal from './EditSubscriptionModal';
import { format, parseISO } from 'date-fns';
import { ActivityLogger, ActivityTypes } from '../../utils/activityLogger';

const SubscriptionsPage = () => {
  const [subscriptions, setSubscriptions] = useState([]);
  const [filteredSubscriptions, setFilteredSubscriptions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');
  const [showAddModal, setShowAddModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [editingSubscription, setEditingSubscription] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');
  const [filterCategory, setFilterCategory] = useState('all');

  useEffect(() => {
    loadSubscriptions();
  }, []);

  useEffect(() => {
    filterSubscriptions();
  }, [subscriptions, searchTerm, filterStatus, filterCategory]);

  const loadSubscriptions = async () => {
    try {
      setLoading(true);
      setError('');
      const response = await subscriptionsAPI.getAll();

      if (response.status === 'success') {
        setSubscriptions(response.data?.subscriptions || []);
      } else {
        setError(response.message || 'Failed to load subscriptions');
      }
    } catch (err) {
      console.error('Load subscriptions error:', err);
      setError('Failed to load subscriptions. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const filterSubscriptions = () => {
    let filtered = subscriptions;

    // Search filter
    if (searchTerm) {
      filtered = filtered.filter(sub =>
        sub.service_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        sub.category.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    // Status filter
    if (filterStatus !== 'all') {
      if (filterStatus === 'active') {
        filtered = filtered.filter(sub => sub.is_active);
      } else if (filterStatus === 'inactive') {
        filtered = filtered.filter(sub => !sub.is_active);
      }
    }

    // Category filter
    if (filterCategory !== 'all') {
      filtered = filtered.filter(sub => sub.category === filterCategory);
    }

    setFilteredSubscriptions(filtered);
  };

  const handleSubscriptionAdded = () => {
    setShowAddModal(false);
    setSuccessMessage('Subscription added successfully!');
    loadSubscriptions();
    setTimeout(() => setSuccessMessage(''), 3000);
  };

  const handleEditSubscription = (subscription) => {
    setEditingSubscription(subscription);
    setShowEditModal(true);
  };

  const handleSubscriptionUpdated = () => {
    setShowEditModal(false);
    setEditingSubscription(null);
    setSuccessMessage('Subscription updated successfully!');
    loadSubscriptions();
    setTimeout(() => setSuccessMessage(''), 3000);
  };

  const handleDeleteSubscription = async (subscriptionId) => {
    // Find the subscription being deleted for logging
    const subscriptionToDelete = subscriptions.find(sub => sub.id === subscriptionId);

    if (!window.confirm('Are you sure you want to delete this subscription?')) {
      return;
    }

    try {
      const response = await subscriptionsAPI.delete(subscriptionId);

      if (response.status === 'success') {
        // Log activity
        if (subscriptionToDelete) {
          ActivityLogger.log(ActivityTypes.SUBSCRIPTION_DELETED, {
            service_name: subscriptionToDelete.service_name
          });
        }

        setSuccessMessage('Subscription deleted successfully!');
        loadSubscriptions();
        setTimeout(() => setSuccessMessage(''), 3000);
      } else {
        setError('Failed to delete subscription');
      }
    } catch (err) {
      console.error('Delete subscription error:', err);
      setError('Failed to delete subscription. Please try again.');
    }
  };

  const handleToggleStatus = async (subscriptionId, currentStatus) => {
    try {
      const response = await subscriptionsAPI.updateStatus(subscriptionId, !currentStatus);

      if (response.status === 'success') {
        const action = !currentStatus ? 'activated' : 'deactivated';
        setSuccessMessage(`Subscription ${action} successfully!`);
        loadSubscriptions();
        setTimeout(() => setSuccessMessage(''), 3000);
      } else {
        setError('Failed to update subscription status');
      }
    } catch (err) {
      console.error('Toggle status error:', err);
      setError('Failed to update subscription status. Please try again.');
    }
  };

  const formatCurrency = (amount, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency
    }).format(amount);
  };

  const formatDate = (dateString) => {
    try {
      // Use parseISO to avoid timezone issues with date strings
      return format(parseISO(dateString + 'T00:00:00'), 'MMM dd, yyyy');
    } catch {
      return dateString;
    }
  };

  const getCategories = () => {
    const categories = [...new Set(subscriptions.map(sub => sub.category))];
    return categories.sort();
  };

  if (loading) {
    return (
      <Container>
        <div className="text-center py-5">
          <div className="spinner-border" role="status">
            <span className="visually-hidden">Loading...</span>
          </div>
          <div className="mt-2">Loading subscriptions...</div>
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
              <i className="bi bi-list-ul me-2"></i>
              Subscriptions
            </h1>
            <Button
              variant="primary"
              onClick={() => setShowAddModal(true)}
            >
              <i className="bi bi-plus-circle me-2"></i>
              Add Subscription
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

      {/* Filters */}
      <Row className="mb-4">
        <Col md={4}>
          <InputGroup>
            <InputGroup.Text>
              <i className="bi bi-search"></i>
            </InputGroup.Text>
            <Form.Control
              type="text"
              placeholder="Search subscriptions..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </InputGroup>
        </Col>
        <Col md={3}>
          <Form.Select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
          >
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </Form.Select>
        </Col>
        <Col md={3}>
          <Form.Select
            value={filterCategory}
            onChange={(e) => setFilterCategory(e.target.value)}
          >
            <option value="all">All Categories</option>
            {getCategories().map(category => (
              <option key={category} value={category}>{category}</option>
            ))}
          </Form.Select>
        </Col>
        <Col md={2}>
          <Button
            variant="outline-secondary"
            onClick={() => {
              setSearchTerm('');
              setFilterStatus('all');
              setFilterCategory('all');
            }}
          >
            Clear
          </Button>
        </Col>
      </Row>

      {/* Subscriptions Table */}
      <Row>
        <Col>
          <Card>
            <Card.Header>
              <h5 className="mb-0">
                Your Subscriptions ({filteredSubscriptions.length})
              </h5>
            </Card.Header>
            <Card.Body>
              {filteredSubscriptions.length > 0 ? (
                <Table responsive hover>
                  <thead>
                    <tr>
                      <th>Service</th>
                      <th>Cost</th>
                      <th>Cycle</th>
                      <th>Category</th>
                      <th>Status</th>
                      <th>Start Date</th>
                      <th>End Date</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredSubscriptions.map((subscription) => (
                      <tr key={subscription.id}>
                        <td>
                          <strong>{subscription.service_name}</strong>
                          {subscription.subscription_type === 'space' && subscription.space_name && (
                            <span className="ms-2">
                              <Badge bg="info" pill size="sm">
                                <i className="bi bi-people-fill me-1"></i>
                                {subscription.space_name}
                              </Badge>
                            </span>
                          )}
                        </td>
                        <td>
                          {formatCurrency(subscription.cost, subscription.currency)}
                        </td>
                        <td>
                          <span className="text-capitalize">
                            {subscription.billing_cycle}
                          </span>
                        </td>
                        <td>
                          <Badge bg="secondary" pill>
                            {subscription.category || 'Other'}
                          </Badge>
                        </td>
                        <td>
                          <Badge
                            bg={subscription.is_active ? 'success' : 'danger'}
                            pill
                          >
                            {subscription.is_active ? 'Active' : 'Inactive'}
                          </Badge>
                        </td>
                        <td>
                          <small className="text-muted">
                            {formatDate(subscription.start_date)}
                          </small>
                        </td>
                        <td>
                          <small className="text-muted">
                            {subscription.end_date ? formatDate(subscription.end_date) : 'Ongoing'}
                          </small>
                        </td>
                        <td>
                          <div className="btn-group" role="group" aria-label="Actions">
                            <Button
                              variant="outline-primary"
                              size="sm"
                              onClick={() => handleEditSubscription(subscription)}
                            >
                              <i className="bi bi-pencil"></i>
                            </Button>
                            <Button
                              variant={subscription.is_active ? 'outline-warning' : 'outline-success'}
                              size="sm"
                              onClick={() => handleToggleStatus(subscription.id, subscription.is_active)}
                            >
                              <i className={`bi bi-${subscription.is_active ? 'pause' : 'play'}`}></i>
                            </Button>
                            <Button
                              variant="outline-danger"
                              size="sm"
                              onClick={() => handleDeleteSubscription(subscription.id)}
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
                  <i className="bi bi-inbox text-muted" style={{ fontSize: '3rem' }}></i>
                  <h6 className="text-muted mt-2">No subscriptions found</h6>
                  <p className="text-muted">
                    {subscriptions.length === 0
                      ? "Get started by adding your first subscription"
                      : "Try adjusting your filters or search term"
                    }
                  </p>
                  {subscriptions.length === 0 && (
                    <Button variant="primary" onClick={() => setShowAddModal(true)}>
                      <i className="bi bi-plus-circle me-2"></i>
                      Add Your First Subscription
                    </Button>
                  )}
                </div>
              )}
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Add Subscription Modal */}
      <AddSubscriptionModal
        show={showAddModal}
        onHide={() => setShowAddModal(false)}
        onSuccess={handleSubscriptionAdded}
      />

      {/* Edit Subscription Modal */}
      {editingSubscription && (
        <EditSubscriptionModal
          show={showEditModal}
          onHide={() => {
            setShowEditModal(false);
            setEditingSubscription(null);
          }}
          onSuccess={handleSubscriptionUpdated}
          subscription={editingSubscription}
        />
      )}
    </Container>
  );
};

export default SubscriptionsPage;