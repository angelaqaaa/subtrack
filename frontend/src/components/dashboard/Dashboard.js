import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Alert, Button, Modal } from 'react-bootstrap';
import { useAuth } from '../../contexts/AuthContext';
import { subscriptionsAPI, spacesAPI, insightsAPI } from '../../services/api';
import SpendingSummary from './SpendingSummary';
import SpendingChart from './SpendingChart';
import RecentSubscriptions from './RecentSubscriptions';
import SharedSpaces from './SharedSpaces';
import InsightsWidget from './InsightsWidget';
import AddSubscriptionModal from '../subscriptions/AddSubscriptionModal';

const Dashboard = () => {
  const { user } = useAuth();
  const [data, setData] = useState({
    subscriptions: [],
    spaces: [],
    insights: [],
    summary: null,
    categoryTotals: {}
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [showAddModal, setShowAddModal] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    try {
      setLoading(true);
      setError('');

      // Load all dashboard data in parallel
      const [subscriptionsRes, spacesRes, insightsRes] = await Promise.all([
        subscriptionsAPI.getAll(),
        spacesAPI.getAll(),
        insightsAPI.getAll()
      ]);

      // Calculate summary and category totals from subscriptions
      let summary = { subscription_count: 0, monthly_cost: 0, annual_cost: 0 };
      let categoryTotals = {};

      if (subscriptionsRes.status === 'success' && subscriptionsRes.data.subscriptions) {
        const activeSubscriptions = subscriptionsRes.data.subscriptions.filter(
          sub => sub.is_active
        );

        summary.subscription_count = activeSubscriptions.length;

        activeSubscriptions.forEach(sub => {
          const monthlyCost = sub.billing_cycle === 'monthly'
            ? parseFloat(sub.cost)
            : parseFloat(sub.cost) / 12;

          summary.monthly_cost += monthlyCost;
          summary.annual_cost += monthlyCost * 12;

          // Category totals
          const category = sub.category || 'Other';
          categoryTotals[category] = (categoryTotals[category] || 0) + monthlyCost;
        });
      }

      setData({
        subscriptions: subscriptionsRes.data?.subscriptions || [],
        spaces: spacesRes.data?.spaces || [],
        insights: insightsRes.data?.insights || [],
        summary,
        categoryTotals
      });

    } catch (err) {
      console.error('Dashboard load error:', err);
      setError('Failed to load dashboard data. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleSubscriptionAdded = () => {
    setShowAddModal(false);
    setSuccessMessage('Subscription added successfully!');

    // Mark subscriptions as modified for other components to detect changes
    localStorage.setItem('subscriptionsLastModified', new Date().toISOString());

    loadDashboardData(); // Refresh data

    // Clear success message after 3 seconds
    setTimeout(() => setSuccessMessage(''), 3000);
  };

  const handleDismissInsight = async (insightId) => {
    try {
      await insightsAPI.dismissInsight(insightId);
      // Refresh insights
      const insightsRes = await insightsAPI.getAll();
      setData(prev => ({
        ...prev,
        insights: insightsRes.data?.insights || []
      }));
    } catch (err) {
      console.error('Failed to dismiss insight:', err);
    }
  };

  if (loading) {
    return (
      <Container>
        <div className="text-center py-5">
          <div className="spinner-border" role="status">
            <span className="visually-hidden">Loading...</span>
          </div>
          <div className="mt-2">Loading dashboard...</div>
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
              <i className="bi bi-speedometer2 me-2"></i>
              Dashboard
            </h1>
            <span className="text-muted">
              Welcome back, {user?.username}!
            </span>
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

      {/* Shared Spaces */}
      {data.spaces.length > 0 && (
        <Row className="mb-4">
          <Col>
            <SharedSpaces spaces={data.spaces} onRefresh={loadDashboardData} />
          </Col>
        </Row>
      )}

      {/* Main Dashboard Content */}
      <Row>
        {/* Left Column */}
        <Col lg={8}>
          {/* Spending Summary Cards */}
          <Row className="mb-4">
            <Col>
              <SpendingSummary summary={data.summary} />
            </Col>
          </Row>

          {/* Spending Chart */}
          {Object.keys(data.categoryTotals).length > 0 && (
            <Row className="mb-4">
              <Col>
                <SpendingChart categoryTotals={data.categoryTotals} />
              </Col>
            </Row>
          )}

          {/* Recent Subscriptions */}
          <Row>
            <Col>
              <RecentSubscriptions
                subscriptions={data.subscriptions}
                onRefresh={loadDashboardData}
                onAddNew={() => setShowAddModal(true)}
              />
            </Col>
          </Row>
        </Col>

        {/* Right Column */}
        <Col lg={4}>
          {/* Insights Widget */}
          <InsightsWidget
            insights={data.insights}
            onDismiss={handleDismissInsight}
          />

          {/* Quick Actions Card */}
          <Card className="mt-4">
            <Card.Header>
              <h5 className="mb-0">
                <i className="bi bi-lightning me-2"></i>
                Quick Actions
              </h5>
            </Card.Header>
            <Card.Body>
              <div className="d-grid gap-2">
                <Button
                  variant="primary"
                  onClick={() => setShowAddModal(true)}
                >
                  <i className="bi bi-plus-circle me-2"></i>
                  Add Subscription
                </Button>
                <Button variant="outline-secondary" href="/reports">
                  <i className="bi bi-bar-chart me-2"></i>
                  View Reports
                </Button>
                <Button variant="outline-secondary" href="/spaces">
                  <i className="bi bi-people me-2"></i>
                  Manage Spaces
                </Button>
              </div>
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
    </Container>
  );
};

export default Dashboard;