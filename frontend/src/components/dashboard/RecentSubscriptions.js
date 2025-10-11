import React, { useState } from 'react';
import { Card, Table, Button, Badge, ButtonGroup } from 'react-bootstrap';
import { format, parseISO } from 'date-fns';
import { useNavigate } from 'react-router-dom';
import { subscriptionsAPI } from '../../services/api';

const RecentSubscriptions = ({ subscriptions, onRefresh, onAddNew }) => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(null);
  // Get the 5 most recent subscriptions
  const recentSubscriptions = subscriptions
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    .slice(0, 5);

  const formatCurrency = (amount, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency
    }).format(amount);
  };

  const formatDate = (dateString) => {
    try {
      return format(parseISO(dateString + 'T00:00:00'), 'MMM dd, yyyy');
    } catch {
      return dateString;
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Are you sure you want to delete this subscription?')) {
      return;
    }

    setLoading(id);
    try {
      await subscriptionsAPI.delete(id);
      onRefresh();
    } catch (error) {
      console.error('Failed to delete subscription:', error);
      alert('Failed to delete subscription');
    } finally {
      setLoading(null);
    }
  };

  const handleToggleStatus = async (id, currentStatus) => {
    const action = currentStatus ? 'end' : 'reactivate';
    const message = currentStatus
      ? 'Are you sure you want to end this subscription?'
      : 'Are you sure you want to reactivate this subscription?';

    if (!window.confirm(message)) {
      return;
    }

    setLoading(id);
    try {
      if (currentStatus) {
        await subscriptionsAPI.end(id);
      } else {
        await subscriptionsAPI.reactivate(id);
      }
      onRefresh();
    } catch (error) {
      console.error(`Failed to ${action} subscription:`, error);
      alert(`Failed to ${action} subscription`);
    } finally {
      setLoading(null);
    }
  };

  return (
    <Card>
      <Card.Header className="d-flex justify-content-between align-items-center">
        <h5 className="mb-0">
          <i className="bi bi-clock-history me-2"></i>
          Recent Subscriptions
        </h5>
        <Button
          variant="primary"
          size="sm"
          onClick={onAddNew}
        >
          <i className="bi bi-plus-circle me-1"></i>
          Add New
        </Button>
      </Card.Header>
      <Card.Body>
        {recentSubscriptions.length > 0 ? (
          <Table responsive hover>
            <thead>
              <tr>
                <th>Service</th>
                <th>Cost</th>
                <th>Cycle</th>
                <th>Category</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {recentSubscriptions.map((subscription) => (
                <tr key={subscription.id}>
                  <td>
                    <strong>{subscription.service_name}</strong>
                    {subscription.subscription_type === 'space' && (
                      <>
                        <br />
                        <Badge bg="info" className="mt-1">
                          <i className="bi bi-people-fill me-1"></i>
                          {subscription.space_name}
                        </Badge>
                      </>
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
                    {subscription.subscription_type === 'space' && subscription.created_by_username && (
                      <>
                        <br />
                        <small className="text-info">
                          By: {subscription.created_by_username}
                        </small>
                      </>
                    )}
                  </td>
                  <td>
                    {subscription.subscription_type === 'space' ? (
                      <Button
                        variant="outline-info"
                        size="sm"
                        href={`/routes/space.php?action=view&space_id=${subscription.space_id || subscription.space_id_ref}`}
                        title="Manage in space"
                      >
                        <i className="bi bi-box-arrow-up-right"></i>
                      </Button>
                    ) : (
                      <ButtonGroup size="sm">
                        <Button
                          variant="outline-primary"
                          onClick={() => navigate('/subscriptions')}
                          disabled={loading === subscription.id}
                          title="Edit"
                        >
                          <i className="bi bi-pencil"></i>
                        </Button>
                        <Button
                          variant={subscription.is_active ? 'outline-warning' : 'outline-success'}
                          onClick={() => handleToggleStatus(subscription.id, subscription.is_active)}
                          disabled={loading === subscription.id}
                          title={subscription.is_active ? 'End subscription' : 'Reactivate subscription'}
                        >
                          <i className={`bi bi-${subscription.is_active ? 'pause-circle' : 'play-circle'}`}></i>
                        </Button>
                        <Button
                          variant="outline-danger"
                          onClick={() => handleDelete(subscription.id)}
                          disabled={loading === subscription.id}
                          title="Delete"
                        >
                          <i className="bi bi-trash"></i>
                        </Button>
                      </ButtonGroup>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </Table>
        ) : (
          <div className="text-center py-4">
            <i className="bi bi-inbox text-muted" style={{ fontSize: '3rem' }}></i>
            <h6 className="text-muted mt-2">No subscriptions yet</h6>
            <p className="text-muted">
              Get started by adding your first subscription
            </p>
            <Button variant="primary" onClick={onAddNew}>
              <i className="bi bi-plus-circle me-2"></i>
              Add Your First Subscription
            </Button>
          </div>
        )}

        {recentSubscriptions.length > 0 && subscriptions.length > 5 && (
          <div className="text-center mt-3">
            <Button variant="outline-primary" href="/subscriptions">
              View All {subscriptions.length} Subscriptions
            </Button>
          </div>
        )}
      </Card.Body>
    </Card>
  );
};

export default RecentSubscriptions;