import React, { useState, useEffect } from 'react';
import { Modal, Button, Alert, Table, Form } from 'react-bootstrap';
import { subscriptionsAPI, spacesAPI } from '../../services/api';

const SyncExistingSubscriptionsModal = ({ show, onHide, onSuccess, spaceId }) => {
  const [subscriptions, setSubscriptions] = useState([]);
  const [selectedSubscriptions, setSelectedSubscriptions] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [syncing, setSyncing] = useState(false);

  useEffect(() => {
    if (show) {
      loadUserSubscriptions();
    }
  }, [show]);

  const loadUserSubscriptions = async () => {
    try {
      setLoading(true);
      const response = await subscriptionsAPI.getAll();
      if (response.status === 'success') {
        setSubscriptions(response.data?.subscriptions || []);
      } else {
        setError('Failed to load your subscriptions');
      }
    } catch (err) {
      console.error('Failed to load subscriptions:', err);
      setError('Failed to load your subscriptions');
    } finally {
      setLoading(false);
    }
  };

  const handleSubscriptionToggle = (subscriptionId) => {
    setSelectedSubscriptions(prev => {
      if (prev.includes(subscriptionId)) {
        return prev.filter(id => id !== subscriptionId);
      } else {
        return [...prev, subscriptionId];
      }
    });
  };

  const handleSyncSelected = async () => {
    if (selectedSubscriptions.length === 0) {
      setError('Please select at least one subscription to sync');
      return;
    }

    try {
      setSyncing(true);
      setError('');

      // Use the bulk sync API method
      const response = await spacesAPI.syncExistingSubscriptions(spaceId, selectedSubscriptions);

      if (response.status === 'success') {
        // Collect the subscriptions that were synced for UI feedback
        const syncedSubscriptions = subscriptions.filter(sub =>
          selectedSubscriptions.includes(sub.id)
        );

        onSuccess(syncedSubscriptions);
        handleClose();
      } else {
        setError(response.message || 'Failed to sync subscriptions');
      }
    } catch (err) {
      console.error('Failed to sync subscriptions:', err);
      setError('Failed to sync subscriptions. Please try again.');
    } finally {
      setSyncing(false);
    }
  };

  const handleClose = () => {
    if (!syncing) {
      setSelectedSubscriptions([]);
      setError('');
      onHide();
    }
  };

  const formatCurrency = (amount, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency
    }).format(amount);
  };

  return (
    <Modal show={show} onHide={handleClose} size="lg">
      <Modal.Header closeButton>
        <Modal.Title>Sync Existing Subscriptions to Space</Modal.Title>
      </Modal.Header>

      <Modal.Body>
        {error && (
          <Alert variant="danger" dismissible onClose={() => setError('')}>
            <i className="bi bi-exclamation-triangle me-2"></i>
            {error}
          </Alert>
        )}

        <p className="text-muted mb-3">
          Select which of your personal subscriptions you'd like to add to this shared space.
        </p>

        {loading ? (
          <div className="text-center py-4">
            <div className="spinner-border" role="status">
              <span className="visually-hidden">Loading...</span>
            </div>
            <div className="mt-2">Loading your subscriptions...</div>
          </div>
        ) : subscriptions.length > 0 ? (
          <Table responsive hover>
            <thead>
              <tr>
                <th width="50">
                  <Form.Check
                    type="checkbox"
                    checked={selectedSubscriptions.length === subscriptions.length}
                    onChange={(e) => {
                      if (e.target.checked) {
                        setSelectedSubscriptions(subscriptions.map(sub => sub.id));
                      } else {
                        setSelectedSubscriptions([]);
                      }
                    }}
                  />
                </th>
                <th>Service</th>
                <th>Category</th>
                <th>Cost</th>
                <th>Cycle</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              {subscriptions.map((subscription) => (
                <tr key={subscription.id}>
                  <td>
                    <Form.Check
                      type="checkbox"
                      checked={selectedSubscriptions.includes(subscription.id)}
                      onChange={() => handleSubscriptionToggle(subscription.id)}
                    />
                  </td>
                  <td><strong>{subscription.service_name}</strong></td>
                  <td>
                    <span className="badge bg-secondary rounded-pill">
                      {subscription.category || 'Other'}
                    </span>
                  </td>
                  <td>{formatCurrency(subscription.cost, subscription.currency)}</td>
                  <td className="text-capitalize">{subscription.billing_cycle}</td>
                  <td>
                    <span className={`badge ${subscription.is_active ? 'bg-success' : 'bg-danger'} rounded-pill`}>
                      {subscription.is_active ? 'Active' : 'Inactive'}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </Table>
        ) : (
          <div className="text-center py-4">
            <i className="bi bi-inbox text-muted" style={{ fontSize: '3rem' }}></i>
            <h6 className="text-muted mt-2">No personal subscriptions found</h6>
            <p className="text-muted">
              You need to have personal subscriptions to sync them to this space.
            </p>
          </div>
        )}
      </Modal.Body>

      <Modal.Footer>
        <Button variant="secondary" onClick={handleClose} disabled={syncing}>
          Cancel
        </Button>
        <Button
          variant="primary"
          onClick={handleSyncSelected}
          disabled={syncing || selectedSubscriptions.length === 0}
        >
          {syncing ? (
            <>
              <span className="spinner-border spinner-border-sm me-2" role="status"></span>
              Syncing...
            </>
          ) : (
            `Sync ${selectedSubscriptions.length} Subscription${selectedSubscriptions.length !== 1 ? 's' : ''}`
          )}
        </Button>
      </Modal.Footer>
    </Modal>
  );
};

export default SyncExistingSubscriptionsModal;