import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Table, Button, Badge, Alert, Tab, Tabs } from 'react-bootstrap';
import { useParams, useNavigate } from 'react-router-dom';
import { spacesAPI, subscriptionsAPI } from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';
import InviteUserModal from './InviteUserModal';
import ViewMembersModal from './ViewMembersModal';
import AddSubscriptionModal from '../subscriptions/AddSubscriptionModal';
import SyncExistingSubscriptionsModal from './SyncExistingSubscriptionsModal';
import SpaceAddSubscriptionModal from './SpaceAddSubscriptionModal';

const SpaceDetailPage = () => {
  const { spaceId } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();

  const [space, setSpace] = useState(null);
  const [members, setMembers] = useState([]);
  const [subscriptions, setSubscriptions] = useState([]);
  const [currentUserRole, setCurrentUserRole] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');
  const [showInviteModal, setShowInviteModal] = useState(false);
  const [showMembersModal, setShowMembersModal] = useState(false);
  const [showAddSubscriptionModal, setShowAddSubscriptionModal] = useState(false);
  const [showSyncExistingModal, setShowSyncExistingModal] = useState(false);
  const [showSpaceAddModal, setShowSpaceAddModal] = useState(false);

  useEffect(() => {
    loadSpaceData();
  }, [spaceId]);

  const loadSpaceData = async () => {
    try {
      setLoading(true);
      setError('');

      // Load space members from API
      let membersList = [];
      try {
        const membersResponse = await spacesAPI.getMembers(spaceId);
        if (membersResponse.status === 'success') {
          membersList = membersResponse.data?.members || [];
        } else {
          throw new Error('API returned error status');
        }
      } catch (memberErr) {
        console.error('Members API failed:', memberErr);
        const status = memberErr.response?.status;

        if (status === 403) {
          setError('You do not have access to this space or your invitation is still pending.');
        } else if (status === 404) {
          setError('We could not find that space or you do not have permission to view it.');
        } else {
          setError('Failed to load space members. Please try again.');
        }
        setLoading(false);
        return;
      }

      setMembers(membersList);

      // Get current user's role and space details
      console.log('Current user from AuthContext:', user);
      console.log('All user properties:', user ? Object.keys(user) : 'No user');
      console.log('Current user ID type:', typeof user?.id, 'value:', user?.id);

      // Try different possible ID fields from AuthContext user
      const userId = user?.id || user?.user_id || user?.ID || user?.userId;
      console.log('Resolved user ID:', userId, 'type:', typeof userId);
      console.log('Members list:', membersList);
      membersList.forEach((member, index) => {
        console.log(`Member ${index}: user_id type: ${typeof member.user_id}, value: ${member.user_id}, role: ${member.role}, status: ${member.status}`);
      });

      // For testing: Check URL parameter for role override first
      const urlParams = new URLSearchParams(window.location.search);
      const roleParam = urlParams.get('role');

      if (roleParam && ['admin', 'editor', 'viewer'].includes(roleParam)) {
        console.log('Using URL parameter role:', roleParam);
        setCurrentUserRole(roleParam);
      } else {
        // Try to find user in members list
        console.log('Searching for user ID:', userId, 'in members list');

        if (!userId) {
          console.log('No user ID found - defaulting to viewer role');
          setCurrentUserRole('viewer');
          setError('Warning: Could not verify your membership status in this space.');
        } else {
          const currentMember = membersList.find(member => {
            const match = member.user_id === userId && member.status === 'accepted';
            console.log(`Checking member ${member.user_id} (${typeof member.user_id}) === ${userId} (${typeof userId}) && ${member.status} === 'accepted': ${match}`);
            return match;
          });
          console.log('Current member found:', currentMember);

          if (currentMember) {
            setCurrentUserRole(currentMember.role);
            console.log('Set current user role to:', currentMember.role);
          } else {
            // Try type conversion - sometimes IDs come as strings vs numbers
            const currentMemberStr = membersList.find(member =>
              String(member.user_id) === String(userId) && member.status === 'accepted'
            );
            console.log('Trying string comparison, found:', currentMemberStr);

            if (currentMemberStr) {
              setCurrentUserRole(currentMemberStr.role);
              console.log('Set current user role to (string match):', currentMemberStr.role);
            } else {
              console.log('User not found in members list - defaulting to viewer role for testing');
              // For now, default to viewer to allow testing - in production this should show error
              setCurrentUserRole('viewer');
              setError('Warning: Could not verify your membership status in this space.');
            }
          }
        }
      }

      setSpace({
        id: spaceId,
        name: 'Space Name', // This would come from a separate API call
        description: 'Space Description',
        member_count: membersList.filter(m => m.status === 'accepted').length,
        owner_id: membersList.find(m => m.role === 'admin')?.user_id
      });

      // Load space subscriptions
      try {
        const subscriptionsResponse = await spacesAPI.getSpaceSubscriptions(spaceId);
        if (subscriptionsResponse.status === 'success') {
          setSubscriptions(subscriptionsResponse.data?.subscriptions || []);
        } else {
          console.error('Failed to load space subscriptions:', subscriptionsResponse);
          setSubscriptions([]);
        }
      } catch (subscriptionsErr) {
        console.error('Space subscriptions API failed:', subscriptionsErr);
        setSubscriptions([]);
      }

    } catch (err) {
      console.error('Load space data error:', err);
      setError('Failed to load space data. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleUserInvited = () => {
    setShowInviteModal(false);
    setSuccessMessage('User invited successfully!');
    loadSpaceData();
    setTimeout(() => setSuccessMessage(''), 3000);
  };

  const handleSubscriptionAdded = () => {
    setShowAddSubscriptionModal(false);
    setSuccessMessage('Subscription added to space successfully!');
    loadSpaceData();
    setTimeout(() => setSuccessMessage(''), 3000);
  };

  const handleToggleSubscriptionStatus = async (subscriptionId, currentStatus) => {
    const action = currentStatus ? 'end' : 'reactivate';
    const message = currentStatus
      ? 'Are you sure you want to end this subscription?'
      : 'Are you sure you want to reactivate this subscription?';

    if (!window.confirm(message)) {
      return;
    }

    try {
      if (currentStatus) {
        await subscriptionsAPI.end(subscriptionId);
      } else {
        await subscriptionsAPI.reactivate(subscriptionId);
      }
      setSuccessMessage(`Subscription ${action}ed successfully!`);
      loadSpaceData();
      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (error) {
      console.error(`Failed to ${action} subscription:`, error);
      setError(`Failed to ${action} subscription. Please try again.`);
      setTimeout(() => setError(''), 3000);
    }
  };

  const handleDeleteSubscription = async (subscriptionId) => {
    if (!window.confirm('Are you sure you want to delete this subscription?')) {
      return;
    }

    try {
      await subscriptionsAPI.delete(subscriptionId);
      setSuccessMessage('Subscription deleted successfully!');
      loadSpaceData();
      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (error) {
      console.error('Failed to delete subscription:', error);
      setError('Failed to delete subscription. Please try again.');
      setTimeout(() => setError(''), 3000);
    }
  };

  const handleSubscriptionsSynced = (syncedSubscriptions = []) => {
    setShowSyncExistingModal(false);
    setSuccessMessage(`${syncedSubscriptions.length} subscription(s) synced to space successfully!`);

    // Add synced subscriptions to the local state for immediate visual feedback
    setSubscriptions(prev => [...prev, ...syncedSubscriptions.map(sub => ({
      ...sub,
      id: `synced_${Date.now()}_${Math.random()}`,
      synced_from_personal: true
    }))]);

    setTimeout(() => setSuccessMessage(''), 3000);
  };

  const handleSpaceSubscriptionAdded = () => {
    setShowSpaceAddModal(false);
    setSuccessMessage('Subscription added to space successfully!');
    setTimeout(() => setSuccessMessage(''), 3000);
    loadSpaceData(); // Refresh the space data
  };

  const handleDeleteSpace = async () => {
    if (currentUserRole !== 'admin') {
      setError('Only space owners can delete spaces.');
      return;
    }

    if (!window.confirm(`Are you sure you want to delete this space? This action cannot be undone and will remove all space data and memberships.`)) {
      return;
    }

    try {
      const response = await spacesAPI.deleteSpace(spaceId);

      if (response.status === 'success') {
        setSuccessMessage('Space deleted successfully');
        setTimeout(() => {
          navigate('/spaces');
        }, 1500);
      } else {
        setError(response.message || 'Failed to delete space');
      }
    } catch (err) {
      console.error('Delete space error:', err);
      setError('Failed to delete space. Please try again.');
    }
  };

  const handleLeaveSpace = async () => {
    if (!window.confirm(`Are you sure you want to leave this space? You will need to be re-invited to access it again.`)) {
      return;
    }

    try {
      const response = await spacesAPI.leaveSpace(spaceId);

      if (response.status === 'success') {
        setSuccessMessage('Successfully left the space');
        setTimeout(() => {
          navigate('/spaces');
        }, 1500);
      } else {
        setError(response.message || 'Failed to leave space');
      }
    } catch (err) {
      console.error('Leave space error:', err);
      setError('Failed to leave space. Please try again.');
    }
  };

  const handleRemoveMember = async (memberId, username) => {
    if (!window.confirm(`Are you sure you want to remove ${username} from this space?`)) {
      return;
    }

    try {
      const response = await spacesAPI.removeMember(spaceId, memberId);

      if (response.status === 'success') {
        setSuccessMessage(`${username} removed successfully`);
        loadSpaceData();
        setTimeout(() => setSuccessMessage(''), 3000);
      } else {
        setError(response.message || 'Failed to remove member');
      }
    } catch (err) {
      console.error('Remove member error:', err);
      setError('Failed to remove member. Please try again.');
    }
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(amount);
  };

  const getRoleBadgeVariant = (role) => {
    switch (role) {
      case 'admin': return 'danger';
      case 'editor': return 'warning';
      default: return 'secondary';
    }
  };

  const getStatusBadgeVariant = (status) => {
    switch (status) {
      case 'accepted': return 'success';
      case 'pending': return 'warning';
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
          <div className="mt-2">Loading space...</div>
        </div>
      </Container>
    );
  }

  return (
    <Container>
      <Row>
        <Col>
          <div className="d-flex justify-content-between align-items-center mb-4">
            <div>
              <Button
                variant="outline-secondary"
                onClick={() => navigate('/spaces')}
                className="me-3"
              >
                <i className="bi bi-arrow-left me-2"></i>
                Back to Spaces
              </Button>
              <h1 className="h2 d-inline">
                <i className="bi bi-people me-2"></i>
                {space?.name || 'Space Details'}
              </h1>
            </div>
            <div className="btn-group">
              <Button
                variant="outline-success"
                onClick={() => navigate('/dashboard')}
              >
                <i className="bi bi-speedometer2 me-2"></i>
                Dashboard
              </Button>
              <Button
                variant="outline-info"
                onClick={() => setShowMembersModal(true)}
              >
                <i className="bi bi-people-fill me-2"></i>
                View Members ({members.filter(m => m.status === 'accepted').length})
              </Button>
              {(() => {
                console.log('Rendering buttons - currentUserRole:', currentUserRole, 'type:', typeof currentUserRole);
                return currentUserRole === 'admin';
              })() && (
                <Button
                  variant="primary"
                  onClick={() => setShowInviteModal(true)}
                >
                  <i className="bi bi-person-plus me-2"></i>
                  Invite User
                </Button>
              )}
              {(() => {
                console.log('Delete/Leave button decision - currentUserRole:', currentUserRole);
                return currentUserRole === 'admin';
              })() ? (
                <Button
                  variant="outline-danger"
                  onClick={() => handleDeleteSpace()}
                  title="Delete Space"
                >
                  <i className="bi bi-trash me-2"></i>
                  Delete Space
                </Button>
              ) : (
                <Button
                  variant="outline-warning"
                  onClick={() => handleLeaveSpace()}
                  title="Leave Space"
                >
                  <i className="bi bi-box-arrow-right me-2"></i>
                  Leave Space
                </Button>
              )}
            </div>
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
        <Col>
          <Tabs defaultActiveKey="overview" className="mb-4">
            {/* Overview Tab */}
            <Tab eventKey="overview" title="Overview">
              <Row>
                <Col md={8}>
                  <Card className="mb-4">
                    <Card.Header>
                      <div className="d-flex justify-content-between align-items-center">
                        <h5 className="mb-0">
                          <i className="bi bi-list-ul me-2"></i>
                          Space Subscriptions ({subscriptions.length})
                        </h5>
                        {(currentUserRole === 'admin' || currentUserRole === 'editor') && (
                          <div className="btn-group">
                            <Button
                              variant="primary"
                              size="sm"
                              onClick={() => setShowSpaceAddModal(true)}
                            >
                              <i className="bi bi-plus-circle me-2"></i>
                              Add New
                            </Button>
                            <Button
                              variant="outline-primary"
                              size="sm"
                              onClick={() => setShowSyncExistingModal(true)}
                            >
                              <i className="bi bi-arrow-repeat me-2"></i>
                              Sync Existing
                            </Button>
                          </div>
                        )}
                      </div>
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
                              <th>Status</th>
                              <th>Added By</th>
                              {currentUserRole === 'admin' && <th>Actions</th>}
                            </tr>
                          </thead>
                          <tbody>
                            {subscriptions.slice(0, 10).map((subscription) => (
                              <tr key={subscription.id}>
                                <td><strong>{subscription.service_name}</strong></td>
                                <td>
                                  <Badge bg="secondary" pill>
                                    {subscription.category || 'Other'}
                                  </Badge>
                                </td>
                                <td>{formatCurrency(subscription.cost)}</td>
                                <td>
                                  <span className="text-capitalize">{subscription.billing_cycle}</span>
                                </td>
                                <td>
                                  <Badge
                                    bg={subscription.is_active ? 'success' : 'danger'}
                                    pill
                                  >
                                    {subscription.is_active ? 'Active' : 'Inactive'}
                                  </Badge>
                                </td>
                                <td>
                                  {subscription.synced_from_personal ? (
                                    <Badge bg="info" pill>
                                      <i className="bi bi-arrow-repeat me-1"></i>
                                      Synced
                                    </Badge>
                                  ) : (
                                    <small className="text-muted">
                                      {subscription.added_by || 'Unknown'}
                                    </small>
                                  )}
                                </td>
                                {currentUserRole === 'admin' && (
                                  <td>
                                    <div className="d-flex gap-1">
                                      <Button
                                        variant="outline-primary"
                                        size="sm"
                                        title="Edit subscription"
                                        onClick={() => {
                                          // Navigate to PHP space page for editing
                                          window.location.href = `/routes/space.php?action=view&space_id=${spaceId}`;
                                        }}
                                      >
                                        <i className="bi bi-pencil"></i>
                                      </Button>
                                      <Button
                                        variant={subscription.is_active ? 'outline-warning' : 'outline-success'}
                                        size="sm"
                                        title={subscription.is_active ? 'End subscription' : 'Reactivate subscription'}
                                        onClick={() => handleToggleSubscriptionStatus(subscription.id, subscription.is_active)}
                                      >
                                        <i className={`bi bi-${subscription.is_active ? 'pause-circle' : 'play-circle'}`}></i>
                                      </Button>
                                      <Button
                                        variant="outline-danger"
                                        size="sm"
                                        title="Delete subscription"
                                        onClick={() => handleDeleteSubscription(subscription.id)}
                                      >
                                        <i className="bi bi-trash"></i>
                                      </Button>
                                    </div>
                                  </td>
                                )}
                              </tr>
                            ))}
                          </tbody>
                        </Table>
                      ) : (
                        <div className="text-center py-4">
                          <i className="bi bi-inbox text-muted" style={{ fontSize: '3rem' }}></i>
                          <h6 className="text-muted mt-2">No subscriptions in this space</h6>
                          <p className="text-muted">
                            Add subscriptions to start tracking them in this shared space.
                          </p>
                          {(currentUserRole === 'admin' || currentUserRole === 'editor') && (
                            <Button
                              variant="primary"
                              onClick={() => setShowSpaceAddModal(true)}
                            >
                              <i className="bi bi-plus-circle me-2"></i>
                              Add Your First Subscription
                            </Button>
                          )}
                        </div>
                      )}
                    </Card.Body>
                  </Card>
                </Col>

                <Col md={4}>
                  <Card className="mb-4">
                    <Card.Header>
                      <h5 className="mb-0">
                        <i className="bi bi-info-circle me-2"></i>
                        Space Info
                      </h5>
                    </Card.Header>
                    <Card.Body>
                      <h6>{space?.name || 'Space Name'}</h6>
                      <p className="text-muted">{space?.description || 'No description available'}</p>

                      <hr />

                      <div className="d-flex justify-content-between mb-2">
                        <span>Total Members:</span>
                        <Badge bg="info" pill>{members.filter(m => m.status === 'accepted').length}</Badge>
                      </div>

                      <div className="d-flex justify-content-between mb-2">
                        <span>Pending Invites:</span>
                        <Badge bg="warning" pill>{members.filter(m => m.status === 'pending').length}</Badge>
                      </div>

                      <div className="d-flex justify-content-between">
                        <span>Total Subscriptions:</span>
                        <Badge bg="primary" pill>{subscriptions.length}</Badge>
                      </div>
                    </Card.Body>
                  </Card>

                  <Card>
                    <Card.Header>
                      <h5 className="mb-0">
                        <i className="bi bi-people me-2"></i>
                        Recent Members
                      </h5>
                    </Card.Header>
                    <Card.Body>
                      {members.filter(m => m.status === 'accepted').slice(0, 5).map((member) => (
                        <div key={member.user_id} className="d-flex justify-content-between align-items-center mb-2">
                          <div>
                            <small className="fw-medium">{member.username}</small>
                            <br />
                            <Badge bg={getRoleBadgeVariant(member.role)} pill size="sm">
                              {member.role}
                            </Badge>
                          </div>
                        </div>
                      ))}

                      {members.filter(m => m.status === 'accepted').length > 5 && (
                        <Button
                          variant="outline-secondary"
                          size="sm"
                          onClick={() => setShowMembersModal(true)}
                          className="w-100 mt-2"
                        >
                          View All Members
                        </Button>
                      )}
                    </Card.Body>
                  </Card>
                </Col>
              </Row>
            </Tab>

            {/* Members Tab */}
            <Tab eventKey="members" title="Members">
              <Card>
                <Card.Header>
                  <div className="d-flex justify-content-between align-items-center">
                    <h5 className="mb-0">
                      <i className="bi bi-people me-2"></i>
                      Space Members ({members.length})
                    </h5>
                    {currentUserRole === 'admin' && (
                      <Button
                        variant="primary"
                        onClick={() => setShowInviteModal(true)}
                      >
                        <i className="bi bi-person-plus me-2"></i>
                        Invite User
                      </Button>
                    )}
                  </div>
                </Card.Header>
                <Card.Body>
                  {members.length > 0 ? (
                    <Table responsive hover>
                      <thead>
                        <tr>
                          <th>User</th>
                          <th>Email</th>
                          <th>Role</th>
                          <th>Status</th>
                          <th>Invited By</th>
                          <th>Joined Date</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {members.map((member) => (
                          <tr key={member.user_id}>
                            <td><strong>{member.username}</strong></td>
                            <td><small className="text-muted">{member.email}</small></td>
                            <td>
                              <Badge bg={getRoleBadgeVariant(member.role)} pill>
                                {member.role}
                              </Badge>
                            </td>
                            <td>
                              <Badge bg={getStatusBadgeVariant(member.status)} pill>
                                {member.status}
                              </Badge>
                            </td>
                            <td>
                              <small className="text-muted">
                                {member.role === 'admin' && (member.invited_by === member.user_id || !member.invited_by_username) ? 'Owner' : member.invited_by_username || '-'}
                              </small>
                            </td>
                            <td>
                              <small className="text-muted">
                                {member.accepted_at ? new Date(member.accepted_at).toLocaleDateString() : '-'}
                              </small>
                            </td>
                            <td>
                              {member.role !== 'admin' && (
                                <Button
                                  variant="outline-danger"
                                  size="sm"
                                  onClick={() => handleRemoveMember(member.user_id, member.username)}
                                  title="Remove Member"
                                >
                                  <i className="bi bi-person-dash"></i>
                                </Button>
                              )}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </Table>
                  ) : (
                    <div className="text-center py-4">
                      <i className="bi bi-people text-muted" style={{ fontSize: '3rem' }}></i>
                      <h6 className="text-muted mt-2">No members found</h6>
                    </div>
                  )}
                </Card.Body>
              </Card>
            </Tab>
          </Tabs>
        </Col>
      </Row>

      {/* Invite User Modal */}
      {space && (
        <InviteUserModal
          show={showInviteModal}
          onHide={() => setShowInviteModal(false)}
          onSuccess={handleUserInvited}
          space={space}
        />
      )}

      {/* View Members Modal */}
      <ViewMembersModal
        show={showMembersModal}
        onHide={() => setShowMembersModal(false)}
        space={{
          ...(space || { id: spaceId, name: 'Space Details' }),
          user_role: currentUserRole,
          owner_id: members.find(m => m.role === 'admin')?.user_id
        }}
        members={members}
      />

      {/* Add Subscription Modal */}
      <AddSubscriptionModal
        show={showAddSubscriptionModal}
        onHide={() => setShowAddSubscriptionModal(false)}
        onSuccess={handleSubscriptionAdded}
        defaultSpaceId={spaceId}
        defaultEnableSpaceSync={true}
      />

      {/* Sync Existing Subscriptions Modal */}
      <SyncExistingSubscriptionsModal
        show={showSyncExistingModal}
        onHide={() => setShowSyncExistingModal(false)}
        onSuccess={handleSubscriptionsSynced}
        spaceId={spaceId}
      />

      {/* Space Add Subscription Modal */}
      <SpaceAddSubscriptionModal
        show={showSpaceAddModal}
        onHide={() => setShowSpaceAddModal(false)}
        onSuccess={handleSpaceSubscriptionAdded}
        spaceId={spaceId}
      />
    </Container>
  );
};

export default SpaceDetailPage;
