import React from 'react';
import { Card, Table, Button, Badge } from 'react-bootstrap';
import { format, parseISO } from 'date-fns';

const RecentSubscriptions = ({ subscriptions, onRefresh, onAddNew }) => {
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