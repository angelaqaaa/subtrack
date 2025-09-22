import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Alert, Button, Table, Badge, Spinner } from 'react-bootstrap';
import { parseISO } from 'date-fns';
import { insightsAPI, subscriptionsAPI } from '../../services/api';
import SpendingChart from '../dashboard/SpendingChart';

const InsightsPage = () => {
  const [insights, setInsights] = useState([]);
  const [subscriptions, setSubscriptions] = useState([]);
  const [analytics, setAnalytics] = useState({
    categoryTotals: {},
    monthlyTrend: [],
    summary: {}
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

  useEffect(() => {
    loadInsightsData();
  }, []);

  // Refresh data when the component becomes visible (user navigates back)
  useEffect(() => {
    const handleVisibilityChange = () => {
      if (!document.hidden) {
        // Check if subscriptions were modified since last load
        const lastModified = localStorage.getItem('subscriptionsLastModified');
        const lastLoadTime = localStorage.getItem('insightsLastLoadTime');

        if (!lastLoadTime || (lastModified && new Date(lastModified) > new Date(lastLoadTime))) {
          loadInsightsData();
        }
      }
    };

    const handleFocus = () => {
      // Check for subscription changes when window gains focus
      const lastModified = localStorage.getItem('subscriptionsLastModified');
      const lastLoadTime = localStorage.getItem('insightsLastLoadTime');

      if (!lastLoadTime || (lastModified && new Date(lastModified) > new Date(lastLoadTime))) {
        loadInsightsData();
      }
    };

    document.addEventListener('visibilitychange', handleVisibilityChange);
    window.addEventListener('focus', handleFocus);

    return () => {
      document.removeEventListener('visibilitychange', handleVisibilityChange);
      window.removeEventListener('focus', handleFocus);
    };
  }, []);

  // Add a function to force refresh insights
  const refreshInsights = () => {
    loadInsightsData();
  };

  const loadInsightsData = async () => {
    try {
      setLoading(true);
      setError('');

      // Load insights and subscriptions in parallel
      const [insightsRes, subscriptionsRes] = await Promise.all([
        insightsAPI.getAll(),
        subscriptionsAPI.getAll()
      ]);

      if (insightsRes.status === 'success') {
        setInsights(insightsRes.data?.insights || []);
      }

      if (subscriptionsRes.status === 'success') {
        const subs = subscriptionsRes.data?.subscriptions || [];
        setSubscriptions(subs);
        calculateAnalytics(subs);
      }

    } catch (err) {
      console.error('Load insights data error:', err);
      setError('Failed to load insights data. Please try again.');
    } finally {
      setLoading(false);
      // Record when insights were last loaded for change detection
      localStorage.setItem('insightsLastLoadTime', new Date().toISOString());
    }
  };

  const calculateAnalytics = (subs) => {
    const activeSubscriptions = subs.filter(sub => sub.is_active);

    // Category totals
    const categoryTotals = {};
    let totalMonthly = 0;
    let totalAnnual = 0;

    activeSubscriptions.forEach(sub => {
      const monthlyCost = sub.billing_cycle === 'monthly'
        ? parseFloat(sub.cost)
        : parseFloat(sub.cost) / 12;

      totalMonthly += monthlyCost;
      totalAnnual += monthlyCost * 12;

      const category = sub.category || 'Other';
      categoryTotals[category] = (categoryTotals[category] || 0) + monthlyCost;
    });

    // Calculate monthly trend (last 6 months)
    const monthlyTrend = [];
    const now = new Date();
    for (let i = 5; i >= 0; i--) {
      const monthAnchor = new Date(now.getFullYear(), now.getMonth() - i, 1);
      const monthStart = new Date(monthAnchor.getFullYear(), monthAnchor.getMonth(), 1);
      const monthEnd = new Date(monthAnchor.getFullYear(), monthAnchor.getMonth() + 1, 0);

      const monthSubs = activeSubscriptions.filter(sub => {
        const startDate = parseISO(`${sub.start_date}T00:00:00`);
        const endDate = sub.end_date ? parseISO(`${sub.end_date}T23:59:59`) : null;

        if (Number.isNaN(startDate.getTime())) {
          return false;
        }

        const startedBeforeMonthEnds = startDate <= monthEnd;
        const stillActiveDuringMonth = !endDate || (!Number.isNaN(endDate.getTime()) && endDate >= monthStart);

        return startedBeforeMonthEnds && stillActiveDuringMonth;
      });

      const monthlyTotal = monthSubs.reduce((total, sub) => {
        let monthlyCost;
        if (sub.billing_cycle === 'monthly') {
          monthlyCost = parseFloat(sub.cost);
        } else {
          // For annual subscriptions, only count the full cost in the month it was paid
          const subStartDate = parseISO(`${sub.start_date}T00:00:00`);
          const isStartMonth =
            !Number.isNaN(subStartDate.getTime()) &&
            subStartDate.getMonth() === monthStart.getMonth() &&
            subStartDate.getFullYear() === monthStart.getFullYear();

          monthlyCost = isStartMonth ? parseFloat(sub.cost) : 0;
        }
        return total + (Number.isFinite(monthlyCost) ? monthlyCost : 0);
      }, 0);

      monthlyTrend.push({
        month: monthStart.toLocaleDateString('en', { month: 'short', year: 'numeric' }),
        amount: monthlyTotal
      });
    }

    console.log('Analytics calculated:', {
      categoryTotals,
      monthlyTrend,
      summary: {
        totalMonthly,
        totalAnnual,
        activeCount: activeSubscriptions.length,
        averagePerSubscription: activeSubscriptions.length > 0 ? totalMonthly / activeSubscriptions.length : 0
      }
    });

    setAnalytics({
      categoryTotals,
      monthlyTrend,
      summary: {
        totalMonthly,
        totalAnnual,
        activeCount: activeSubscriptions.length,
        averagePerSubscription: activeSubscriptions.length > 0 ? totalMonthly / activeSubscriptions.length : 0
      }
    });
  };

  const handleDismissInsight = async (insightId) => {
    try {
      const response = await insightsAPI.dismissInsight(insightId);

      if (response.status === 'success') {
        setSuccessMessage('Insight dismissed successfully');
        // Remove the insight from the list
        setInsights(insights.filter(insight => insight.id !== insightId));
        setTimeout(() => setSuccessMessage(''), 3000);
      } else {
        setError(response.message || 'Failed to dismiss insight');
      }
    } catch (err) {
      console.error('Dismiss insight error:', err);
      setError('Failed to dismiss insight. Please try again.');
    }
  };

  const getInsightIcon = (type) => {
    switch (type) {
      case 'cost_optimization': return 'bi-piggy-bank';
      case 'duplicate_detection': return 'bi-exclamation-triangle';
      case 'billing_cycle': return 'bi-calendar-check';
      case 'category_analysis': return 'bi-pie-chart';
      default: return 'bi-lightbulb';
    }
  };

  const getInsightVariant = (type) => {
    switch (type) {
      case 'cost_optimization': return 'success';
      case 'duplicate_detection': return 'warning';
      case 'billing_cycle': return 'info';
      case 'category_analysis': return 'primary';
      default: return 'secondary';
    }
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
          <div className="mt-2">Loading insights...</div>
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
              <i className="bi bi-graph-up me-2"></i>
              Insights & Analytics
            </h1>
            <Button
              variant="outline-secondary"
              onClick={refreshInsights}
            >
              <i className="bi bi-arrow-clockwise me-2"></i>
              Refresh
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

      {/* Analytics Summary Cards */}
      <Row className="mb-4">
        <Col md={3}>
          <Card className="text-center">
            <Card.Body>
              <h5 className="text-primary">{analytics.summary.activeCount}</h5>
              <small className="text-muted">Active Subscriptions</small>
            </Card.Body>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="text-center">
            <Card.Body>
              <h5 className="text-success">{formatCurrency(analytics.summary.totalMonthly)}</h5>
              <small className="text-muted">Monthly Total</small>
            </Card.Body>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="text-center">
            <Card.Body>
              <h5 className="text-warning">{formatCurrency(analytics.summary.totalAnnual)}</h5>
              <small className="text-muted">Annual Total</small>
            </Card.Body>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="text-center">
            <Card.Body>
              <h5 className="text-info">{formatCurrency(analytics.summary.averagePerSubscription)}</h5>
              <small className="text-muted">Avg per Subscription</small>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      <Row>
        {/* Left Column - Charts */}
        <Col lg={8}>
          {/* Spending by Category Chart */}
          {Object.keys(analytics.categoryTotals).length > 0 && (
            <Row className="mb-4">
              <Col>
                <SpendingChart categoryTotals={analytics.categoryTotals} />
              </Col>
            </Row>
          )}

          {/* Monthly Trend Chart */}
          {analytics.monthlyTrend.length > 0 && (
            <Row className="mb-4">
              <Col>
                <Card>
                  <Card.Header>
                    <h5 className="mb-0">
                      <i className="bi bi-graph-up me-2"></i>
                      Spending Trend (Last 6 Months)
                    </h5>
                  </Card.Header>
                  <Card.Body>
                    {/* Visual Chart */}
                    <div className="mb-4">
                      <div className="d-flex align-items-end justify-content-between mb-3" style={{ height: '200px', padding: '0 10px' }}>
                        {analytics.monthlyTrend.map((month, index) => {
                          const maxAmount = Math.max(...analytics.monthlyTrend.map(m => m.amount));
                          const height = maxAmount > 0 ? (month.amount / maxAmount) * 180 : 0;

                          return (
                            <div key={index} className="d-flex flex-column align-items-center" style={{ flex: 1 }}>
                              <div
                                className="bg-primary rounded-top position-relative"
                                style={{
                                  width: '40px',
                                  height: `${height}px`,
                                  minHeight: '5px',
                                  transition: 'height 0.3s ease'
                                }}
                                title={`${month.month}: ${formatCurrency(month.amount)}`}
                              >
                                {month.amount > 0 && (
                                  <small className="position-absolute text-white fw-bold"
                                         style={{
                                           top: '-20px',
                                           left: '50%',
                                           transform: 'translateX(-50%)',
                                           fontSize: '10px',
                                           whiteSpace: 'nowrap'
                                         }}>
                                    {formatCurrency(month.amount)}
                                  </small>
                                )}
                              </div>
                              <small className="text-muted mt-2 text-center" style={{ fontSize: '11px' }}>
                                {month.month}
                              </small>
                            </div>
                          );
                        })}
                      </div>
                    </div>

                    {/* Progress Bar Alternative */}
                    <div className="mb-4">
                      <h6 className="text-muted mb-3">Monthly Breakdown</h6>
                      {analytics.monthlyTrend.map((month, index) => {
                        const maxAmount = Math.max(...analytics.monthlyTrend.map(m => m.amount));
                        const percentage = maxAmount > 0 ? (month.amount / maxAmount) * 100 : 0;

                        return (
                          <div key={index} className="mb-3">
                            <div className="d-flex justify-content-between align-items-center mb-1">
                              <span className="fw-medium">{month.month}</span>
                              <span className="text-primary fw-bold">{formatCurrency(month.amount)}</span>
                            </div>
                            <div className="progress" style={{ height: '15px' }}>
                              <div
                                className="progress-bar bg-primary"
                                role="progressbar"
                                style={{ width: `${percentage}%` }}
                                aria-valuenow={percentage}
                                aria-valuemin="0"
                                aria-valuemax="100"
                              ></div>
                            </div>
                          </div>
                        );
                      })}
                    </div>

                    {/* Table view as backup */}
                    <details className="mt-3">
                      <summary className="btn btn-outline-secondary btn-sm">Show Data Table</summary>
                      <Table responsive className="mt-2">
                        <thead>
                          <tr>
                            <th>Month</th>
                            <th>Total Spending</th>
                          </tr>
                        </thead>
                        <tbody>
                          {analytics.monthlyTrend.map((month, index) => (
                            <tr key={index}>
                              <td>{month.month}</td>
                              <td>{formatCurrency(month.amount)}</td>
                            </tr>
                          ))}
                        </tbody>
                      </Table>
                    </details>
                  </Card.Body>
                </Card>
              </Col>
            </Row>
          )}

          {/* Subscription Breakdown */}
          <Row>
            <Col>
              <Card>
                <Card.Header>
                  <h5 className="mb-0">
                    <i className="bi bi-list-ul me-2"></i>
                    Subscription Breakdown
                  </h5>
                </Card.Header>
                <Card.Body>
                  {subscriptions.length > 0 ? (
                    <Table responsive hover>
                      <thead>
                        <tr>
                          <th>Service</th>
                          <th>Category</th>
                          <th>Cost</th>
                          <th>Cycle</th>
                          <th>Monthly Equivalent</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        {subscriptions.map((sub) => {
                          const monthlyEquivalent = sub.billing_cycle === 'monthly'
                            ? parseFloat(sub.cost)
                            : parseFloat(sub.cost) / 12;

                          return (
                            <tr key={sub.id}>
                              <td><strong>{sub.service_name}</strong></td>
                              <td>
                                <Badge bg="secondary" pill>
                                  {sub.category || 'Other'}
                                </Badge>
                              </td>
                              <td>{formatCurrency(sub.cost)}</td>
                              <td>
                                <span className="text-capitalize">{sub.billing_cycle}</span>
                              </td>
                              <td>{formatCurrency(monthlyEquivalent)}</td>
                              <td>
                                <Badge bg={sub.is_active ? 'success' : 'danger'} pill>
                                  {sub.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                              </td>
                            </tr>
                          );
                        })}
                      </tbody>
                    </Table>
                  ) : (
                    <div className="text-center py-4">
                      <i className="bi bi-inbox text-muted" style={{ fontSize: '2rem' }}></i>
                      <h6 className="text-muted mt-2">No subscriptions found</h6>
                    </div>
                  )}
                </Card.Body>
              </Card>
            </Col>
          </Row>
        </Col>

        {/* Right Column - Insights */}
        <Col lg={4}>
          <Card>
            <Card.Header>
              <h5 className="mb-0">
                <i className="bi bi-lightbulb me-2"></i>
                Smart Insights ({insights.length})
              </h5>
            </Card.Header>
            <Card.Body>
              {insights.length > 0 ? (
                <div className="d-grid gap-3">
                  {insights.map((insight) => (
                    <Alert
                      key={insight.id}
                      variant={getInsightVariant(insight.type)}
                      className="d-flex justify-content-between align-items-start"
                    >
                      <div>
                        <div className="d-flex align-items-center mb-2">
                          <i className={`${getInsightIcon(insight.type)} me-2`}></i>
                          <strong>{insight.title}</strong>
                        </div>
                        <small>{insight.description}</small>
                        <div className="mt-2">
                          <small className="text-muted">
                            <i className="bi bi-calendar me-1"></i>
                            {new Date(insight.created_at).toLocaleDateString()}
                          </small>
                        </div>
                      </div>
                      <Button
                        variant="outline-secondary"
                        size="sm"
                        onClick={() => handleDismissInsight(insight.id)}
                        title="Dismiss"
                      >
                        <i className="bi bi-x"></i>
                      </Button>
                    </Alert>
                  ))}
                </div>
              ) : (
                <div className="text-center py-4">
                  <i className="bi bi-lightbulb text-muted" style={{ fontSize: '2rem' }}></i>
                  <h6 className="text-muted mt-2">No insights available</h6>
                  <p className="text-muted small">
                    Insights will appear here as we analyze your subscription patterns.
                  </p>
                </div>
              )}
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </Container>
  );
};

export default InsightsPage;
