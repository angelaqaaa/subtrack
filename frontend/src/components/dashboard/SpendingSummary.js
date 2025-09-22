import React from 'react';
import { Row, Col, Card } from 'react-bootstrap';

const SpendingSummary = ({ summary }) => {
  if (!summary) {
    return null;
  }

  const { subscription_count, monthly_cost, annual_cost } = summary;

  return (
    <Row>
      <Col md={4} className="mb-3">
        <Card className="text-center h-100">
          <Card.Body>
            <div className="text-primary mb-2">
              <i className="bi bi-list-ul" style={{ fontSize: '2rem' }}></i>
            </div>
            <h4 className="card-title">{subscription_count}</h4>
            <p className="card-text text-muted">
              Active Subscription{subscription_count !== 1 ? 's' : ''}
            </p>
          </Card.Body>
        </Card>
      </Col>

      <Col md={4} className="mb-3">
        <Card className="text-center h-100">
          <Card.Body>
            <div className="text-success mb-2">
              <i className="bi bi-calendar-month" style={{ fontSize: '2rem' }}></i>
            </div>
            <h4 className="card-title">${monthly_cost.toFixed(2)}</h4>
            <p className="card-text text-muted">Monthly Cost</p>
          </Card.Body>
        </Card>
      </Col>

      <Col md={4} className="mb-3">
        <Card className="text-center h-100">
          <Card.Body>
            <div className="text-info mb-2">
              <i className="bi bi-calendar-year" style={{ fontSize: '2rem' }}></i>
            </div>
            <h4 className="card-title">${annual_cost.toFixed(2)}</h4>
            <p className="card-text text-muted">Annual Cost</p>
          </Card.Body>
        </Card>
      </Col>
    </Row>
  );
};

export default SpendingSummary;