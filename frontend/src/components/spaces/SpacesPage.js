import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Table, Button, Badge, Alert, Modal, Form, Spinner } from 'react-bootstrap';
import { spacesAPI } from '../../services/api';
import CreateSpaceModal from './CreateSpaceModal';
import InviteUserModal from './InviteUserModal';
import ViewMembersModal from './ViewMembersModal';

const SpacesPage = () => {
  const [spaces, setSpaces] = useState([]);
  const [pendingInvitations, setPendingInvitations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showInviteModal, setShowInviteModal] = useState(false);
  const [showMembersModal, setShowMembersModal] = useState(false);
  const [selectedSpace, setSelectedSpace] = useState(null);

  useEffect(() => {
    loadSpaces();
    loadPendingInvitations();
  }, []);

  const loadSpaces = async () => {
    try {
      setLoading(true);
      setError('');
      const response = await spacesAPI.getAll();

      if (response.status === 'success') {
        setSpaces(response.data?.spaces || []);
      } else {
        setError(response.message || 'Failed to load spaces');
      }
    } catch (err) {
      console.error('Load spaces error:', err);
      setError('Failed to load spaces. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const loadPendingInvitations = async () => {
    try {
      const response = await spacesAPI.getPendingInvitations();
      if (response.status === 'success') {
        setPendingInvitations(response.data?.invitations || []);
      }
    } catch (err) {
      console.error('Load pending invitations error:', err);
    }
  };

  const handleSpaceCreated = () => {
    setShowCreateModal(false);
    setSuccessMessage('Space created successfully!');
    loadSpaces();
    setTimeout(() => setSuccessMessage(''), 3000);
  };

  const handleUserInvited = () => {
    setShowInviteModal(false);
    setSelectedSpace(null);
    setSuccessMessage('User invited successfully!');
    setTimeout(() => setSuccessMessage(''), 3000);
  };

  const handleInviteUser = (space) => {
    setSelectedSpace(space);
    setShowInviteModal(true);
  };

  const handleViewMembers = (space) => {
    setSelectedSpace(space);
    setShowMembersModal(true);
  };

  const handleLeaveSpace = async (spaceId, spaceName) => {
    if (!window.confirm(`Are you sure you want to leave "${spaceName}"?`)) {
      return;
    }

    try {
      const response = await spacesAPI.leaveSpace(spaceId);

      if (response.status === 'success') {
        setSuccessMessage('Successfully left the space');
        loadSpaces();
        setTimeout(() => setSuccessMessage(''), 3000);
      } else {
        setError(response.message || 'Failed to leave space');
      }
    } catch (err) {
      console.error('Leave space error:', err);
      setError('Failed to leave space. Please try again.');
    }
  };

  const handleAcceptInvitation = async (spaceId, spaceName) => {
    try {
      const response = await spacesAPI.acceptInvitation(spaceId);

      if (response.status === 'success') {
        setSuccessMessage(`Successfully joined "${spaceName}"!`);
        loadSpaces();
        loadPendingInvitations();
        setTimeout(() => setSuccessMessage(''), 3000);
      } else {
        setError(response.message || 'Failed to accept invitation');
      }
    } catch (err) {
      console.error('Accept invitation error:', err);
      setError('Failed to accept invitation. Please try again.');
    }
  };

  const handleRejectInvitation = async (spaceId, spaceName) => {
    if (!window.confirm(`Are you sure you want to reject the invitation to "${spaceName}"?`)) {
      return;
    }

    try {
      const response = await spacesAPI.rejectInvitation(spaceId);

      if (response.status === 'success') {
        setSuccessMessage(`Invitation to "${spaceName}" rejected`);
        loadPendingInvitations();
        setTimeout(() => setSuccessMessage(''), 3000);
      } else {
        setError(response.message || 'Failed to reject invitation');
      }
    } catch (err) {
      console.error('Reject invitation error:', err);
      setError('Failed to reject invitation. Please try again.');
    }
  };

  const getRoleBadgeVariant = (role) => {
    switch (role) {
      case 'admin': return 'danger';
      case 'editor': return 'warning';
      default: return 'secondary';
    }
  };

  if (loading) {
    return (
      <Container>
        <div className="text-center py-5">
          <div className="spinner-border" role="status">
            <span className="visually-hidden">Loading...</span>
          </div>
          <div className="mt-2">Loading spaces...</div>
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
              <i className="bi bi-people me-2"></i>
              Shared Spaces
            </h1>
            <Button
              variant="primary"
              onClick={() => setShowCreateModal(true)}
            >
              <i className="bi bi-plus-circle me-2"></i>
              Create Space
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

      {/* Pending Invitations */}
      {pendingInvitations.length > 0 && (
        <Row className="mb-4">
          <Col>
            <Card className="border-warning">
              <Card.Header className="bg-warning text-dark">
                <h5 className="mb-0">
                  <i className="bi bi-envelope me-2"></i>
                  Pending Invitations ({pendingInvitations.length})
                </h5>
              </Card.Header>
              <Card.Body>
                <div className="row g-3">
                  {pendingInvitations.map((invitation) => (
                    <div key={invitation.id} className="col-md-6">
                      <div className="border rounded p-3 bg-light">
                        <div className="d-flex justify-content-between align-items-start mb-2">
                          <div>
                            <h6 className="mb-1">{invitation.name}</h6>
                            <small className="text-muted">
                              {invitation.description || 'No description'}
                            </small>
                          </div>
                          <Badge bg="warning" pill>
                            {invitation.role}
                          </Badge>
                        </div>
                        <div className="mb-3">
                          <small className="text-muted">
                            <i className="bi bi-person me-1"></i>
                            Invited by {invitation.invited_by_username}
                          </small>
                          <br />
                          <small className="text-muted">
                            <i className="bi bi-calendar me-1"></i>
                            {new Date(invitation.invited_at).toLocaleDateString()}
                          </small>
                        </div>
                        <div className="d-flex gap-2">
                          <Button
                            variant="success"
                            size="sm"
                            onClick={() => handleAcceptInvitation(invitation.id, invitation.name)}
                          >
                            <i className="bi bi-check-circle me-1"></i>
                            Accept
                          </Button>
                          <Button
                            variant="outline-danger"
                            size="sm"
                            onClick={() => handleRejectInvitation(invitation.id, invitation.name)}
                          >
                            <i className="bi bi-x-circle me-1"></i>
                            Reject
                          </Button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      )}

      <Row>
        <Col>
          <Card>
            <Card.Header>
              <h5 className="mb-0">
                Your Spaces ({spaces.length})
              </h5>
            </Card.Header>
            <Card.Body>
              {spaces.length > 0 ? (
                <Table responsive hover>
                  <thead>
                    <tr>
                      <th>Space Name</th>
                      <th>Description</th>
                      <th>Owner</th>
                      <th>Your Role</th>
                      <th>Members</th>
                      <th>Created</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {spaces.map((space) => (
                      <tr key={space.id}>
                        <td>
                          <strong>{space.name}</strong>
                        </td>
                        <td>
                          <span className="text-muted">
                            {space.description || 'No description'}
                          </span>
                        </td>
                        <td>
                          <small className="text-muted">
                            {space.owner_username}
                          </small>
                        </td>
                        <td>
                          <Badge
                            bg={getRoleBadgeVariant(space.user_role)}
                            pill
                          >
                            {space.user_role}
                          </Badge>
                        </td>
                        <td>
                          <Badge bg="info" pill>
                            {space.member_count}
                          </Badge>
                        </td>
                        <td>
                          <small className="text-muted">
                            {new Date(space.created_at).toLocaleDateString()}
                          </small>
                        </td>
                        <td>
                          <div className="btn-group" role="group" aria-label="Actions">
                            <Button
                              variant="outline-primary"
                              size="sm"
                              onClick={() => window.location.href = `/spaces/${space.id}`}
                              title="Enter Space"
                            >
                              <i className="bi bi-box-arrow-in-right"></i>
                            </Button>

                            <Button
                              variant="outline-info"
                              size="sm"
                              onClick={() => handleViewMembers(space)}
                              title="View Members"
                            >
                              <i className="bi bi-people-fill"></i>
                            </Button>

                            {space.user_role === 'admin' && (
                              <Button
                                variant="outline-primary"
                                size="sm"
                                onClick={() => handleInviteUser(space)}
                                title="Invite User"
                              >
                                <i className="bi bi-person-plus"></i>
                              </Button>
                            )}

                            {space.user_role !== 'admin' && (
                              <Button
                                variant="outline-danger"
                                size="sm"
                                onClick={() => handleLeaveSpace(space.id, space.name)}
                                title="Leave Space"
                              >
                                <i className="bi bi-box-arrow-right"></i>
                              </Button>
                            )}
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </Table>
              ) : (
                <div className="text-center py-4">
                  <i className="bi bi-people text-muted" style={{ fontSize: '3rem' }}></i>
                  <h6 className="text-muted mt-2">No shared spaces found</h6>
                  <p className="text-muted">
                    Create your first shared space to collaborate with others on subscription tracking.
                  </p>
                  <Button variant="primary" onClick={() => setShowCreateModal(true)}>
                    <i className="bi bi-plus-circle me-2"></i>
                    Create Your First Space
                  </Button>
                </div>
              )}
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Create Space Modal */}
      <CreateSpaceModal
        show={showCreateModal}
        onHide={() => setShowCreateModal(false)}
        onSuccess={handleSpaceCreated}
      />

      {/* Invite User Modal */}
      {selectedSpace && (
        <InviteUserModal
          show={showInviteModal}
          onHide={() => {
            setShowInviteModal(false);
            setSelectedSpace(null);
          }}
          onSuccess={handleUserInvited}
          space={selectedSpace}
        />
      )}

      {/* View Members Modal */}
      {selectedSpace && (
        <ViewMembersModal
          show={showMembersModal}
          onHide={() => {
            setShowMembersModal(false);
            setSelectedSpace(null);
          }}
          space={selectedSpace}
        />
      )}
    </Container>
  );
};

export default SpacesPage;