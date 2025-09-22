import React from 'react';
import { Card, Row, Col, Badge, Button } from 'react-bootstrap';

const SharedSpaces = ({ spaces, onRefresh }) => {
  if (!spaces || spaces.length === 0) {
    return null;
  }

  return (
    <Card>
      <Card.Header className="d-flex justify-content-between align-items-center">
        <h5 className="mb-0">
          <i className="bi bi-people me-2"></i>
          Shared Spaces
        </h5>
        <Button variant="primary" size="sm" href="/spaces">
          <i className="bi bi-plus-circle me-1"></i>
          Create Space
        </Button>
      </Card.Header>
      <Card.Body>
        <Row>
          {spaces.map((space) => (
            <Col md={4} key={space.id} className="mb-3">
              <Card className={`border-${(space.user_role || space.role) === 'admin' ? 'success' : 'info'} h-100`}>
                <Card.Body>
                  <div className="d-flex justify-content-between align-items-start mb-2">
                    <h6 className="card-title mb-0">{space.name}</h6>
                    <Badge bg={(space.user_role || space.role) === 'admin' ? 'success' : 'info'}>
                      {((space.user_role || space.role) || 'viewer').charAt(0).toUpperCase() + ((space.user_role || space.role) || 'viewer').slice(1)}
                    </Badge>
                  </div>

                  {space.description && (
                    <p className="card-text small text-muted mb-2">
                      {space.description}
                    </p>
                  )}

                  <div className="d-flex justify-content-between align-items-center">
                    <small className="text-muted">
                      <i className="bi bi-people me-1"></i>
                      {space.member_count} member{space.member_count !== 1 ? 's' : ''}
                    </small>
                    <Button
                      variant="outline-primary"
                      size="sm"
                      href={`/spaces/${space.id}`}
                    >
                      <i className="bi bi-arrow-right me-1"></i>
                      Enter
                    </Button>
                  </div>
                </Card.Body>
              </Card>
            </Col>
          ))}
        </Row>
      </Card.Body>
    </Card>
  );
};

export default SharedSpaces;