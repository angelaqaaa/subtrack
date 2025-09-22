import React, { useState } from 'react';
import { Modal, Form, Button, Alert, Spinner } from 'react-bootstrap';
import { spacesAPI } from '../../services/api';

const InviteUserModal = ({ show, onHide, onSuccess, space }) => {
  const [formData, setFormData] = useState({
    email: '',
    role: 'viewer'
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

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
      const response = await spacesAPI.inviteUser(space.id, formData.email, formData.role);

      if (response.status === 'success') {
        // Reset form
        setFormData({
          email: '',
          role: 'viewer'
        });
        onSuccess();
      } else {
        setError(response.message || 'Failed to invite user');
      }
    } catch (err) {
      console.error('Invite user error:', err);
      setError(err.response?.data?.message || 'Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    if (!loading) {
      setError('');
      setFormData({
        email: '',
        role: 'viewer'
      });
      onHide();
    }
  };

  return (
    <Modal show={show} onHide={handleClose}>
      <Modal.Header closeButton>
        <Modal.Title>Invite User to "{space?.name}"</Modal.Title>
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
            <Form.Label>Email Address *</Form.Label>
            <Form.Control
              type="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              required
              disabled={loading}
              placeholder="Enter user's email address"
            />
            <Form.Text className="text-muted">
              The user must already have an account to be invited.
            </Form.Text>
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>Role *</Form.Label>
            <Form.Select
              name="role"
              value={formData.role}
              onChange={handleChange}
              required
              disabled={loading}
            >
              <option value="viewer">Viewer - Can view subscriptions only</option>
              <option value="editor">Editor - Can add and edit subscriptions</option>
              <option value="admin">Admin - Full access including user management</option>
            </Form.Select>
          </Form.Group>

          <div className="bg-light p-3 rounded">
            <small className="text-muted">
              <i className="bi bi-info-circle me-1"></i>
              <strong>Role Permissions:</strong>
              <ul className="mb-0 mt-2">
                <li><strong>Viewer:</strong> Can view all subscriptions in the space</li>
                <li><strong>Editor:</strong> Can add, edit, and manage subscriptions</li>
                <li><strong>Admin:</strong> Can manage subscriptions and invite/remove users</li>
              </ul>
            </small>
          </div>
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
                Inviting...
              </>
            ) : (
              'Send Invitation'
            )}
          </Button>
        </Modal.Footer>
      </Form>
    </Modal>
  );
};

export default InviteUserModal;