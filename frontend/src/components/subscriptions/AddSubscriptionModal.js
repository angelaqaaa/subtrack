import React, { useState, useEffect } from 'react';
import { Modal, Form, Button, Alert, Spinner, Card } from 'react-bootstrap';
import { subscriptionsAPI, spacesAPI } from '../../services/api';
import { ActivityLogger, ActivityTypes } from '../../utils/activityLogger';

const AddSubscriptionModal = ({ show, onHide, onSuccess, defaultSpaceId, defaultEnableSpaceSync }) => {
  const [formData, setFormData] = useState({
    service_name: '',
    cost: '',
    currency: 'USD',
    billing_cycle: 'monthly',
    start_date: '',
    end_date: '',
    category: 'Entertainment'
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [categories, setCategories] = useState([]);
  const [spaces, setSpaces] = useState([]);
  const [syncToSpace, setSyncToSpace] = useState('');
  const [enableSpaceSync, setEnableSpaceSync] = useState(false);

  useEffect(() => {
    if (show) {
      loadCategories();
      loadUserSpaces();

      // Set default values if provided
      if (defaultSpaceId) {
        setSyncToSpace(defaultSpaceId);
      }
      if (defaultEnableSpaceSync !== undefined) {
        setEnableSpaceSync(defaultEnableSpaceSync);
      }
    }
  }, [show, defaultSpaceId, defaultEnableSpaceSync]);

  const loadCategories = () => {
    try {
      const savedCategories = localStorage.getItem('userCategories');
      if (savedCategories) {
        setCategories(JSON.parse(savedCategories));
      } else {
        // Default categories if none saved
        const defaultCategories = [
          { id: 1, name: 'Entertainment' },
          { id: 2, name: 'Productivity' },
          { id: 3, name: 'Other' }
        ];
        setCategories(defaultCategories);
      }
    } catch (err) {
      console.error('Failed to load categories:', err);
      setCategories([{ id: 1, name: 'Other' }]);
    }
  };

  const loadUserSpaces = async () => {
    try {
      const response = await spacesAPI.getAll();
      console.log('Spaces API response:', response);
      if (response.status === 'success') {
        // Only show spaces where user has edit permissions
        const allSpaces = response.data?.spaces || [];
        console.log('All spaces from API:', allSpaces);
        const editableSpaces = allSpaces.filter(space =>
          space.user_role === 'admin' || space.user_role === 'editor'
        );
        console.log('Filtered editable spaces:', editableSpaces);
        setSpaces(editableSpaces);
      } else {
        console.log('Spaces API failed:', response);
        setSpaces([]);
      }
    } catch (err) {
      console.error('Failed to load spaces:', err);
      setSpaces([]);
    }
  };

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const response = await subscriptionsAPI.create(formData);
      console.log('Add subscription response:', response); // Debug log

      if (response.status === 'success') {
        // Log activity
        ActivityLogger.log(ActivityTypes.SUBSCRIPTION_ADDED, {
          service_name: formData.service_name
        }, {
          cost: formData.cost,
          currency: formData.currency,
          billing_cycle: formData.billing_cycle,
          category: formData.category
        });

        // If sync to space is enabled, add to selected space
        if (enableSpaceSync && syncToSpace) {
          try {
            // Use the form data since the response might not contain the full subscription object
            const subscriptionForSpace = {
              ...formData,
              id: response.data?.subscription_id || Date.now()
            };
            await spacesAPI.addSubscription(syncToSpace, subscriptionForSpace);
            console.log('Successfully synced subscription to space:', syncToSpace);
          } catch (spaceErr) {
            console.error('Failed to sync to space:', spaceErr);
            // Don't block the main flow, just log the error
          }
        }

        // Reset form
        setFormData({
          service_name: '',
          cost: '',
          currency: 'USD',
          billing_cycle: 'monthly',
          start_date: '',
          end_date: '',
          category: 'Entertainment'
        });
        setEnableSpaceSync(false);
        setSyncToSpace('');
        onSuccess();
      } else {
        setError(response.message || 'Failed to add subscription');
      }
    } catch (err) {
      console.error('Add subscription error:', err);
      setError(err.response?.data?.message || 'Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    if (!loading) {
      setError('');
      setFormData({
        service_name: '',
        cost: '',
        currency: 'USD',
        billing_cycle: 'monthly',
        start_date: '',
        end_date: '',
        category: 'Entertainment'
      });
      setEnableSpaceSync(false);
      setSyncToSpace('');
      onHide();
    }
  };

  return (
    <Modal show={show} onHide={handleClose} size="lg">
      <Modal.Header closeButton>
        <Modal.Title>Add New Subscription</Modal.Title>
      </Modal.Header>

      <Form onSubmit={handleSubmit}>
        <Modal.Body>
          {error && (
            <Alert variant="danger" className="mb-3">
              <i className="bi bi-exclamation-triangle me-2"></i>
              {error}
            </Alert>
          )}

          <Form.Group className="mb-3">
            <Form.Label>Service Name *</Form.Label>
            <Form.Control
              type="text"
              name="service_name"
              value={formData.service_name}
              onChange={handleChange}
              required
              disabled={loading}
              placeholder="e.g., Netflix, Spotify, Adobe Creative"
            />
          </Form.Group>

          <div className="row">
            <div className="col-md-8">
              <Form.Group className="mb-3">
                <Form.Label>Cost *</Form.Label>
                <Form.Control
                  type="number"
                  step="0.01"
                  name="cost"
                  value={formData.cost}
                  onChange={handleChange}
                  required
                  disabled={loading}
                  placeholder="0.00"
                />
              </Form.Group>
            </div>
            <div className="col-md-4">
              <Form.Group className="mb-3">
                <Form.Label>Currency</Form.Label>
                <Form.Select
                  name="currency"
                  value={formData.currency}
                  onChange={handleChange}
                  disabled={loading}
                >
                  <option value="USD">USD</option>
                  <option value="EUR">EUR</option>
                  <option value="GBP">GBP</option>
                  <option value="CAD">CAD</option>
                </Form.Select>
              </Form.Group>
            </div>
          </div>

          <Form.Group className="mb-3">
            <Form.Label>Billing Cycle *</Form.Label>
            <Form.Select
              name="billing_cycle"
              value={formData.billing_cycle}
              onChange={handleChange}
              required
              disabled={loading}
            >
              <option value="monthly">Monthly</option>
              <option value="yearly">Yearly</option>
            </Form.Select>
          </Form.Group>

          <div className="row">
            <div className="col-md-6">
              <Form.Group className="mb-3">
                <Form.Label>Start Date *</Form.Label>
                <Form.Control
                  type="date"
                  name="start_date"
                  value={formData.start_date}
                  onChange={handleChange}
                  required
                  disabled={loading}
                />
              </Form.Group>
            </div>
            <div className="col-md-6">
              <Form.Group className="mb-3">
                <Form.Label>End Date (Optional)</Form.Label>
                <Form.Control
                  type="date"
                  name="end_date"
                  value={formData.end_date}
                  onChange={handleChange}
                  disabled={loading}
                />
                <Form.Text className="text-muted">
                  Leave empty for ongoing subscription
                </Form.Text>
              </Form.Group>
            </div>
          </div>

          <Form.Group className="mb-3">
            <Form.Label>Category</Form.Label>
            <Form.Select
              name="category"
              value={formData.category}
              onChange={handleChange}
              disabled={loading}
            >
              {categories.map((category) => (
                <option key={category.id} value={category.name}>
                  {category.name}
                </option>
              ))}
              <option value="Other">Other</option>
            </Form.Select>
          </Form.Group>

          {(spaces.length > 0 || true) && (
            <Card className="mb-3" style={{ backgroundColor: '#f8f9fa' }}>
              <Card.Body>
                <Form.Group className="mb-3">
                  <Form.Check
                    type="checkbox"
                    id="enable-space-sync"
                    label="Also add to shared space"
                    checked={enableSpaceSync}
                    onChange={(e) => setEnableSpaceSync(e.target.checked)}
                    disabled={loading}
                  />
                </Form.Group>

                {enableSpaceSync && (
                  <Form.Group className="mb-0">
                    <Form.Label>Select Space</Form.Label>
                    <Form.Select
                      value={syncToSpace}
                      onChange={(e) => setSyncToSpace(e.target.value)}
                      disabled={loading}
                      required={enableSpaceSync}
                    >
                      <option value="">Choose a space...</option>
                      {spaces.map((space) => (
                        <option key={space.id} value={space.id}>
                          {space.name} ({space.user_role})
                        </option>
                      ))}
                    </Form.Select>
                    <Form.Text className="text-muted">
                      This subscription will also be added to the selected shared space.
                    </Form.Text>
                  </Form.Group>
                )}
              </Card.Body>
            </Card>
          )}
        </Modal.Body>

        <Modal.Footer>
          <Button variant="secondary" onClick={handleClose} disabled={loading}>
            Cancel
          </Button>
          <Button variant="primary" type="submit" disabled={loading}>
            {loading ? (
              <>
                <Spinner
                  as="span"
                  animation="border"
                  size="sm"
                  role="status"
                  className="me-2"
                />
                Adding...
              </>
            ) : (
              'Add Subscription'
            )}
          </Button>
        </Modal.Footer>
      </Form>
    </Modal>
  );
};

export default AddSubscriptionModal;