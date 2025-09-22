import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Table, Button, Alert, Modal, Form, Badge } from 'react-bootstrap';
import { categoriesAPI, subscriptionsAPI } from '../../services/api';
import { ActivityLogger, ActivityTypes } from '../../utils/activityLogger';
import { useAuth } from '../../contexts/AuthContext';

const CategoriesPage = () => {
  const { user } = useAuth();
  const [categories, setCategories] = useState([]);
  const [subscriptions, setSubscriptions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

  // Modal states
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState(null);

  // Form state
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    color: '#6c757d',
    icon: 'bi-tag'
  });

  const [submitting, setSubmitting] = useState(false);

  const predefinedCategories = [
    { name: 'Entertainment', color: '#e74c3c', icon: 'bi-play-circle' },
    { name: 'Productivity', color: '#3498db', icon: 'bi-briefcase' },
    { name: 'Design', color: '#9b59b6', icon: 'bi-palette' },
    { name: 'Development', color: '#2ecc71', icon: 'bi-code-slash' },
    { name: 'Health & Fitness', color: '#f39c12', icon: 'bi-heart-pulse' },
    { name: 'News & Media', color: '#34495e', icon: 'bi-newspaper' },
    { name: 'Education', color: '#16a085', icon: 'bi-book' },
    { name: 'Music', color: '#e67e22', icon: 'bi-music-note' },
    { name: 'Gaming', color: '#8e44ad', icon: 'bi-controller' },
    { name: 'Cloud Storage', color: '#3498db', icon: 'bi-cloud' }
  ];

  const iconOptions = [
    'bi-tag', 'bi-play-circle', 'bi-briefcase', 'bi-palette', 'bi-code-slash',
    'bi-heart-pulse', 'bi-newspaper', 'bi-book', 'bi-music-note', 'bi-controller',
    'bi-cloud', 'bi-camera', 'bi-phone', 'bi-laptop', 'bi-house', 'bi-car-front',
    'bi-cart', 'bi-credit-card', 'bi-graph-up', 'bi-lightbulb'
  ];

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      setError('');

      // Load categories from localStorage for persistence
      const savedCategories = localStorage.getItem(`userCategories_${user?.id || 'unknown'}`);
      if (savedCategories) {
        setCategories(JSON.parse(savedCategories));
      } else {
        // Initialize with default categories
        const defaultCategories = [
          { id: 1, name: 'Entertainment', description: 'Streaming and entertainment services', color: '#e74c3c', icon: 'bi-play-circle' },
          { id: 2, name: 'Productivity', description: 'Work and productivity tools', color: '#3498db', icon: 'bi-briefcase' },
          { id: 3, name: 'Design', description: 'Design and creative tools', color: '#9b59b6', icon: 'bi-palette' }
        ];
        setCategories(defaultCategories);
        localStorage.setItem(`userCategories_${user?.id || 'unknown'}`, JSON.stringify(defaultCategories));
      }

      // Load subscriptions
      const subscriptionsResponse = await subscriptionsAPI.getAll();
      if (subscriptionsResponse.status === 'success') {
        setSubscriptions(subscriptionsResponse.data?.subscriptions || []);
      }

    } catch (err) {
      console.error('Load data error:', err);
      setError('Failed to load categories. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const getCategoryUsageCount = (categoryName) => {
    return subscriptions.filter(sub => sub.category === categoryName).length;
  };

  const getCategorySpending = (categoryName) => {
    return subscriptions
      .filter(sub => sub.category === categoryName && sub.is_active)
      .reduce((total, sub) => {
        const monthlyCost = sub.billing_cycle === 'monthly'
          ? parseFloat(sub.cost)
          : parseFloat(sub.cost) / 12;
        return total + monthlyCost;
      }, 0);
  };

  const handleFormChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const handleCreateCategory = async () => {
    try {
      setSubmitting(true);
      setError('');

      // Create new category with unique ID
      const newCategory = {
        id: Date.now(),
        name: formData.name,
        description: formData.description,
        color: formData.color,
        icon: formData.icon
      };

      // Add to current categories and save to localStorage
      const updatedCategories = [...categories, newCategory];
      setCategories(updatedCategories);
      localStorage.setItem(`userCategories_${user?.id || 'unknown'}`, JSON.stringify(updatedCategories));

      // Log activity
      ActivityLogger.log(ActivityTypes.CATEGORY_ADDED, {
        name: formData.name
      });

      setSuccessMessage('Category created successfully!');
      setShowCreateModal(false);
      resetForm();
      setTimeout(() => setSuccessMessage(''), 3000);

    } catch (err) {
      console.error('Create category error:', err);
      setError('Failed to create category. Please try again.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleEditCategory = (category) => {
    setSelectedCategory(category);
    setFormData({
      name: category.name,
      description: category.description || '',
      color: category.color || '#6c757d',
      icon: category.icon || 'bi-tag'
    });
    setShowEditModal(true);
  };

  const handleUpdateCategory = async () => {
    try {
      setSubmitting(true);
      setError('');

      // Update the category in the local state and save to localStorage
      const updatedCategories = categories.map(cat =>
        cat.id === selectedCategory.id
          ? { ...cat,
              name: formData.name,
              description: formData.description,
              color: formData.color,
              icon: formData.icon
            }
          : cat
      );
      setCategories(updatedCategories);
      localStorage.setItem(`userCategories_${user?.id || 'unknown'}`, JSON.stringify(updatedCategories));

      // Log activity
      ActivityLogger.log(ActivityTypes.CATEGORY_UPDATED, {
        name: formData.name
      });

      setSuccessMessage('Category updated successfully!');
      setShowEditModal(false);
      resetForm();
      setSelectedCategory(null);
      setTimeout(() => setSuccessMessage(''), 3000);

    } catch (err) {
      console.error('Update category error:', err);
      setError('Failed to update category. Please try again.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleDeleteCategory = async (categoryId, categoryName) => {
    const usageCount = getCategoryUsageCount(categoryName);

    if (usageCount > 0) {
      setError(`Cannot delete "${categoryName}" because it's used by ${usageCount} subscription(s). Please reassign or delete those subscriptions first.`);
      return;
    }

    if (!window.confirm(`Are you sure you want to delete the category "${categoryName}"?`)) {
      return;
    }

    try {
      // Remove category from list and update localStorage
      const updatedCategories = categories.filter(cat => cat.id !== categoryId);
      setCategories(updatedCategories);
      localStorage.setItem(`userCategories_${user?.id || 'unknown'}`, JSON.stringify(updatedCategories));

      // Log activity
      ActivityLogger.log(ActivityTypes.CATEGORY_DELETED, {
        name: categoryName
      });

      setSuccessMessage(`Category "${categoryName}" deleted successfully!`);
      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (err) {
      console.error('Delete category error:', err);
      setError('Failed to delete category. Please try again.');
    }
  };

  const handleAddPredefinedCategory = async (predefinedCategory) => {
    try {
      setSubmitting(true);

      // Create new category with unique ID
      const newCategory = {
        id: Date.now(),
        name: predefinedCategory.name,
        description: '',
        color: predefinedCategory.color,
        icon: predefinedCategory.icon
      };

      // Add to current categories and save to localStorage
      const updatedCategories = [...categories, newCategory];
      setCategories(updatedCategories);
      localStorage.setItem(`userCategories_${user?.id || 'unknown'}`, JSON.stringify(updatedCategories));

      setSuccessMessage(`"${predefinedCategory.name}" category added successfully!`);
      setTimeout(() => setSuccessMessage(''), 3000);

    } catch (err) {
      console.error('Add predefined category error:', err);
      setError('Failed to add category. Please try again.');
    } finally {
      setSubmitting(false);
    }
  };

  const resetForm = () => {
    setFormData({
      name: '',
      description: '',
      color: '#6c757d',
      icon: 'bi-tag'
    });
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(amount);
  };

  if (loading) {
    return (
      <Container>
        <div className="text-center py-5">
          <div className="spinner-border" role="status">
            <span className="visually-hidden">Loading...</span>
          </div>
          <div className="mt-2">Loading categories...</div>
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
              <i className="bi bi-tags me-2"></i>
              Categories
            </h1>
            <Button
              variant="primary"
              onClick={() => setShowCreateModal(true)}
            >
              <i className="bi bi-plus-circle me-2"></i>
              Create Category
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

      <Row>
        {/* Current Categories */}
        <Col lg={8}>
          <Card className="mb-4">
            <Card.Header>
              <h5 className="mb-0">
                <i className="bi bi-list-ul me-2"></i>
                Your Categories ({categories.length})
              </h5>
            </Card.Header>
            <Card.Body>
              {categories.length > 0 ? (
                <Table responsive hover>
                  <thead>
                    <tr>
                      <th>Category</th>
                      <th>Description</th>
                      <th>Usage</th>
                      <th>Monthly Spend</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {categories.map((category) => {
                      const usageCount = getCategoryUsageCount(category.name);
                      const monthlySpend = getCategorySpending(category.name);

                      return (
                        <tr key={category.id || category.name}>
                          <td>
                            <div className="d-flex align-items-center">
                              <i
                                className={`${category.icon || 'bi-tag'} me-2`}
                                style={{ color: category.color || '#6c757d' }}
                              ></i>
                              <strong>{category.name}</strong>
                            </div>
                          </td>
                          <td>
                            <small className="text-muted">
                              {category.description || 'No description'}
                            </small>
                          </td>
                          <td>
                            <Badge bg={usageCount > 0 ? 'primary' : 'secondary'} pill>
                              {usageCount} subscription{usageCount !== 1 ? 's' : ''}
                            </Badge>
                          </td>
                          <td>
                            <strong>{formatCurrency(monthlySpend)}</strong>
                          </td>
                          <td>
                            <div className="btn-group" role="group">
                              <Button
                                variant="outline-primary"
                                size="sm"
                                onClick={() => handleEditCategory(category)}
                                title="Edit Category"
                              >
                                <i className="bi bi-pencil"></i>
                              </Button>
                              <Button
                                variant="outline-danger"
                                size="sm"
                                onClick={() => handleDeleteCategory(category.id, category.name)}
                                title="Delete Category"
                                disabled={usageCount > 0}
                              >
                                <i className="bi bi-trash"></i>
                              </Button>
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </Table>
              ) : (
                <div className="text-center py-4">
                  <i className="bi bi-tags text-muted" style={{ fontSize: '3rem' }}></i>
                  <h6 className="text-muted mt-2">No categories found</h6>
                  <p className="text-muted">
                    Create your first category to organize your subscriptions.
                  </p>
                  <Button variant="primary" onClick={() => setShowCreateModal(true)}>
                    <i className="bi bi-plus-circle me-2"></i>
                    Create Your First Category
                  </Button>
                </div>
              )}
            </Card.Body>
          </Card>
        </Col>

        {/* Quick Add Predefined Categories */}
        <Col lg={4}>
          <Card>
            <Card.Header>
              <h5 className="mb-0">
                <i className="bi bi-lightning me-2"></i>
                Quick Add Categories
              </h5>
            </Card.Header>
            <Card.Body>
              <p className="text-muted small mb-3">
                Add popular categories with pre-configured icons and colors.
              </p>

              <div className="d-grid gap-2">
                {predefinedCategories.map((predefined, index) => {
                  const alreadyExists = categories.some(cat => cat.name === predefined.name);

                  return (
                    <Button
                      key={index}
                      variant={alreadyExists ? "outline-secondary" : "outline-primary"}
                      size="sm"
                      onClick={() => handleAddPredefinedCategory(predefined)}
                      disabled={alreadyExists || submitting}
                      className="text-start"
                    >
                      <i
                        className={`${predefined.icon} me-2`}
                        style={{ color: predefined.color }}
                      ></i>
                      {predefined.name}
                      {alreadyExists && (
                        <Badge bg="success" className="ms-2">Added</Badge>
                      )}
                    </Button>
                  );
                })}
              </div>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Create Category Modal */}
      <Modal show={showCreateModal} onHide={() => setShowCreateModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>
            <i className="bi bi-plus-circle me-2"></i>
            Create New Category
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Row>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Category Name *</Form.Label>
                <Form.Control
                  type="text"
                  placeholder="e.g., Entertainment"
                  value={formData.name}
                  onChange={(e) => handleFormChange('name', e.target.value)}
                />
              </Form.Group>

              <Form.Group className="mb-3">
                <Form.Label>Description</Form.Label>
                <Form.Control
                  as="textarea"
                  rows={2}
                  placeholder="Brief description of this category..."
                  value={formData.description}
                  onChange={(e) => handleFormChange('description', e.target.value)}
                />
              </Form.Group>
            </Col>

            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Color</Form.Label>
                <Form.Control
                  type="color"
                  value={formData.color}
                  onChange={(e) => handleFormChange('color', e.target.value)}
                />
              </Form.Group>

              <Form.Group className="mb-3">
                <Form.Label>Icon</Form.Label>
                <Form.Select
                  value={formData.icon}
                  onChange={(e) => handleFormChange('icon', e.target.value)}
                >
                  {iconOptions.map(icon => (
                    <option key={icon} value={icon}>
                      {icon.replace('bi-', '').replace('-', ' ')}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>

              <div className="text-center p-3 border rounded">
                <div className="mb-2">Preview:</div>
                <i
                  className={`${formData.icon} display-6`}
                  style={{ color: formData.color }}
                ></i>
                <div className="mt-1 fw-bold">{formData.name || 'Category Name'}</div>
              </div>
            </Col>
          </Row>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowCreateModal(false)}>
            Cancel
          </Button>
          <Button
            variant="primary"
            onClick={handleCreateCategory}
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
                Create Category
              </>
            )}
          </Button>
        </Modal.Footer>
      </Modal>

      {/* Edit Category Modal */}
      <Modal show={showEditModal} onHide={() => setShowEditModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>
            <i className="bi bi-pencil me-2"></i>
            Edit Category
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Row>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Category Name *</Form.Label>
                <Form.Control
                  type="text"
                  value={formData.name}
                  onChange={(e) => handleFormChange('name', e.target.value)}
                />
              </Form.Group>

              <Form.Group className="mb-3">
                <Form.Label>Description</Form.Label>
                <Form.Control
                  as="textarea"
                  rows={2}
                  value={formData.description}
                  onChange={(e) => handleFormChange('description', e.target.value)}
                />
              </Form.Group>
            </Col>

            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Color</Form.Label>
                <Form.Control
                  type="color"
                  value={formData.color}
                  onChange={(e) => handleFormChange('color', e.target.value)}
                />
              </Form.Group>

              <Form.Group className="mb-3">
                <Form.Label>Icon</Form.Label>
                <Form.Select
                  value={formData.icon}
                  onChange={(e) => handleFormChange('icon', e.target.value)}
                >
                  {iconOptions.map(icon => (
                    <option key={icon} value={icon}>
                      {icon.replace('bi-', '').replace('-', ' ')}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>

              <div className="text-center p-3 border rounded">
                <div className="mb-2">Preview:</div>
                <i
                  className={`${formData.icon} display-6`}
                  style={{ color: formData.color }}
                ></i>
                <div className="mt-1 fw-bold">{formData.name || 'Category Name'}</div>
              </div>
            </Col>
          </Row>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowEditModal(false)}>
            Cancel
          </Button>
          <Button
            variant="primary"
            onClick={handleUpdateCategory}
            disabled={!formData.name.trim() || submitting}
          >
            {submitting ? (
              <>
                <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                Updating...
              </>
            ) : (
              <>
                <i className="bi bi-check-circle me-2"></i>
                Update Category
              </>
            )}
          </Button>
        </Modal.Footer>
      </Modal>
    </Container>
  );
};

export default CategoriesPage;