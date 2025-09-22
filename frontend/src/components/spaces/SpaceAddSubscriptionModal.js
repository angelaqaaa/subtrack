import React, { useState, useEffect } from 'react';
import { Modal, Form, Button, Alert, Spinner } from 'react-bootstrap';
import { spacesAPI } from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';

const SpaceAddSubscriptionModal = ({ show, onHide, onSuccess, spaceId }) => {
  const { user } = useAuth();
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

  useEffect(() => {
    if (show) {
      loadCategories();
    }
  }, [show]);

  const loadCategories = () => {
    try {
      const savedCategories = localStorage.getItem(`userCategories_${user?.id || 'unknown'}`);
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
      // Add subscription directly to space
      const subscriptionForSpace = {
        ...formData,
        added_by: user?.id || user?.user_id || user?.ID
      };

      const response = await spacesAPI.addSubscription(spaceId, subscriptionForSpace);

      if (response.status === 'success') {
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
        onSuccess();
      } else {
        setError(response.message || 'Failed to add subscription to space');
      }
    } catch (err) {
      console.error('Add space subscription error:', err);
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
      onHide();
    }
  };

  return (
    <Modal show={show} onHide={handleClose} size="lg">
      <Modal.Header closeButton>
        <Modal.Title>Add Subscription to Space</Modal.Title>
      </Modal.Header>

      <Modal.Body>
        {error && (
          <Alert variant="danger" dismissible onClose={() => setError('')}>
            <i className="bi bi-exclamation-triangle me-2"></i>
            {error}
          </Alert>
        )}

        <Form onSubmit={handleSubmit}>
          <Form.Group className="mb-3">
            <Form.Label>Service Name</Form.Label>
            <Form.Control
              type="text"
              name="service_name"
              value={formData.service_name}
              onChange={handleChange}
              placeholder="Enter service name"
              required
              disabled={loading}
            />
          </Form.Group>

          <div className="row">
            <div className="col-md-6">
              <Form.Group className="mb-3">
                <Form.Label>Cost</Form.Label>
                <Form.Control
                  type="number"
                  step="0.01"
                  name="cost"
                  value={formData.cost}
                  onChange={handleChange}
                  placeholder="0.00"
                  required
                  disabled={loading}
                />
              </Form.Group>
            </div>
            <div className="col-md-6">
              <Form.Group className="mb-3">
                <Form.Label>Currency</Form.Label>
                <Form.Select
                  name="currency"
                  value={formData.currency}
                  onChange={handleChange}
                  disabled={loading}
                >
                  <option value="USD">USD</option>
                  <option value="CAD">CAD</option>
                  <option value="EUR">EUR</option>
                  <option value="GBP">GBP</option>
                </Form.Select>
              </Form.Group>
            </div>
          </div>

          <div className="row">
            <div className="col-md-6">
              <Form.Group className="mb-3">
                <Form.Label>Billing Cycle</Form.Label>
                <Form.Select
                  name="billing_cycle"
                  value={formData.billing_cycle}
                  onChange={handleChange}
                  disabled={loading}
                >
                  <option value="monthly">Monthly</option>
                  <option value="yearly">Yearly</option>
                  <option value="weekly">Weekly</option>
                  <option value="daily">Daily</option>
                </Form.Select>
              </Form.Group>
            </div>
            <div className="col-md-6">
              <Form.Group className="mb-3">
                <Form.Label>Category</Form.Label>
                <Form.Select
                  name="category"
                  value={formData.category}
                  onChange={handleChange}
                  disabled={loading}
                >
                  {categories.map((category) => (
                    <option key={category.id || category.name} value={category.name}>
                      {category.name}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </div>
          </div>

          <div className="row">
            <div className="col-md-6">
              <Form.Group className="mb-3">
                <Form.Label>Start Date</Form.Label>
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
                  Leave empty for ongoing subscriptions
                </Form.Text>
              </Form.Group>
            </div>
          </div>

          <div className="d-flex justify-content-end gap-2">
            <Button variant="secondary" onClick={handleClose} disabled={loading}>
              Cancel
            </Button>
            <Button variant="primary" type="submit" disabled={loading}>
              {loading ? (
                <>
                  <Spinner animation="border" size="sm" className="me-2" />
                  Adding...
                </>
              ) : (
                'Add Subscription'
              )}
            </Button>
          </div>
        </Form>
      </Modal.Body>
    </Modal>
  );
};

export default SpaceAddSubscriptionModal;