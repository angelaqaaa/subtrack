import React from 'react';
import { Card, Alert, Button } from 'react-bootstrap';
import { format } from 'date-fns';

const InsightsWidget = ({ insights, onDismiss }) => {
  if (!insights || insights.length === 0) {
    return (
      <Card>
        <Card.Header>
          <h5 className="mb-0">
            <i className="bi bi-lightbulb me-2"></i>
            Insights
          </h5>
        </Card.Header>
        <Card.Body>
          <div className="text-center py-3">
            <i className="bi bi-lightbulb text-muted" style={{ fontSize: '2rem' }}></i>
            <p className="text-muted mt-2 mb-0">
              No insights available yet
            </p>
            <small className="text-muted">
              Add more subscriptions to get personalized insights
            </small>
          </div>
        </Card.Body>
      </Card>
    );
  }

  const getImpactColor = (score) => {
    if (score >= 8) return 'danger';
    if (score >= 6) return 'warning';
    if (score >= 4) return 'info';
    return 'secondary';
  };

  const getInsightIcon = (type) => {
    switch (type) {
      case 'saving_opportunity':
        return 'bi-piggy-bank';
      case 'spending_alert':
        return 'bi-exclamation-triangle';
      case 'category_analysis':
        return 'bi-pie-chart';
      case 'trend_analysis':
        return 'bi-graph-up-arrow';
      default:
        return 'bi-lightbulb';
    }
  };

  return (
    <Card>
      <Card.Header>
        <h5 className="mb-0">
          <i className="bi bi-lightbulb me-2"></i>
          Insights
        </h5>
      </Card.Header>
      <Card.Body>
        {insights.slice(0, 3).map((insight) => (
          <Alert
            key={insight.id}
            variant={getImpactColor(insight.impact_score)}
            className="mb-3"
            dismissible
            onClose={() => onDismiss(insight.id)}
          >
            <div className="d-flex align-items-start">
              <i className={`bi ${getInsightIcon(insight.type)} me-2`}></i>
              <div className="flex-grow-1">
                <h6 className="alert-heading mb-1">
                  {insight.title}
                </h6>
                <p className="mb-2">{insight.description}</p>
                <div className="d-flex justify-content-between align-items-center">
                  <small className="text-muted">
                    Impact: {insight.impact_score}/10
                  </small>
                  <small className="text-muted">
                    {insight.created_at && format(new Date(insight.created_at), 'MMM dd, yyyy')}
                  </small>
                </div>
              </div>
            </div>
          </Alert>
        ))}

        {insights.length > 3 && (
          <div className="text-center">
            <Button variant="outline-primary" size="sm" href="/insights">
              View All {insights.length} Insights
            </Button>
          </div>
        )}
      </Card.Body>
    </Card>
  );
};

export default InsightsWidget;