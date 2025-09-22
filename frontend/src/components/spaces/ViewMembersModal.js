import React, { useState, useEffect } from 'react';
import { Modal, Table, Button, Alert, Spinner, Badge, Form } from 'react-bootstrap';
import { spacesAPI } from '../../services/api';

const ViewMembersModal = ({ show, onHide, space, members: initialMembers }) => {
  const [members, setMembers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

  useEffect(() => {
    if (show && space) {
      if (initialMembers && initialMembers.length > 0) {
        setMembers(initialMembers);
      } else {
        loadMembers();
      }
    }
  }, [show, space, initialMembers]);

  const loadMembers = async () => {
    try {
      setLoading(true);
      setError('');
      const response = await spacesAPI.getMembers(space.id);

      if (response.status === 'success') {
        setMembers(response.data?.members || []);
      } else {
        setError(response.message || 'Failed to load members');
      }
    } catch (err) {
      console.error('Load members error:', err);
      setError('Failed to load members. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleRemoveMember = async (memberId, memberName) => {
    if (!window.confirm(`Are you sure you want to remove ${memberName} from this space?`)) {
      return;
    }

    try {
      const response = await spacesAPI.removeMember(space.id, memberId);

      if (response.status === 'success') {
        setSuccessMessage('Member removed successfully');
        loadMembers(); // Refresh the list
        setTimeout(() => setSuccessMessage(''), 3000);
      } else {
        setError(response.message || 'Failed to remove member');
      }
    } catch (err) {
      console.error('Remove member error:', err);
      setError('Failed to remove member. Please try again.');
    }
  };

  const handleRoleChange = async (memberId, memberName, newRole) => {
    if (!window.confirm(`Are you sure you want to change ${memberName}'s role to ${newRole}?`)) {
      return;
    }

    try {
      const response = await spacesAPI.updateMemberRole(space.id, memberId, newRole);

      if (response.status === 'success') {
        setSuccessMessage(`${memberName}'s role updated to ${newRole} successfully`);
        loadMembers(); // Refresh the list
        setTimeout(() => setSuccessMessage(''), 3000);
      } else {
        setError(response.message || 'Failed to update member role');
      }
    } catch (err) {
      console.error('Update member role error:', err);
      setError('Failed to update member role. Please try again.');
    }
  };

  const getRoleBadgeVariant = (role) => {
    switch (role) {
      case 'admin': return 'danger';
      case 'editor': return 'warning';
      default: return 'secondary';
    }
  };

  const canRemoveMember = (member) => {
    // Can't remove the owner, and only admins can remove others
    return space.user_role === 'admin' && member.user_id !== space.owner_id;
  };

  return (
    <Modal show={show} onHide={onHide} size="lg">
      <Modal.Header closeButton>
        <Modal.Title>Members of "{space?.name}"</Modal.Title>
      </Modal.Header>

      <Modal.Body>
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

        {loading ? (
          <div className="text-center py-4">
            <Spinner animation="border" role="status">
              <span className="visually-hidden">Loading...</span>
            </Spinner>
            <div className="mt-2">Loading members...</div>
          </div>
        ) : (
          <>
            {members.length > 0 ? (
              <Table responsive hover>
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    {space.user_role === 'admin' && <th>Actions</th>}
                  </tr>
                </thead>
                <tbody>
                  {members.map((member) => (
                    <tr key={member.user_id}>
                      <td>
                        <strong>{member.username}</strong>
                        {member.user_id === space.owner_id && (
                          <Badge bg="primary" className="ms-2">Owner</Badge>
                        )}
                      </td>
                      <td>
                        <small className="text-muted">{member.email}</small>
                      </td>
                      <td>
                        {space.user_role === 'admin' && member.user_id !== space.owner_id ? (
                          <Form.Select
                            size="sm"
                            value={member.role}
                            onChange={(e) => handleRoleChange(member.user_id, member.username, e.target.value)}
                            style={{ width: 'auto', display: 'inline-block' }}
                          >
                            <option value="admin">Admin</option>
                            <option value="editor">Editor</option>
                            <option value="viewer">Viewer</option>
                          </Form.Select>
                        ) : (
                          <Badge
                            bg={getRoleBadgeVariant(member.role)}
                            pill
                          >
                            {member.role}
                          </Badge>
                        )}
                      </td>
                      <td>
                        <Badge
                          bg={member.status === 'accepted' ? 'success' : 'warning'}
                          pill
                        >
                          {member.status}
                        </Badge>
                      </td>
                      <td>
                        <small className="text-muted">
                          {member.accepted_at ?
                            new Date(member.accepted_at).toLocaleDateString() :
                            'Pending'
                          }
                        </small>
                      </td>
                      {space.user_role === 'admin' && (
                        <td>
                          {canRemoveMember(member) ? (
                            <Button
                              variant="outline-danger"
                              size="sm"
                              onClick={() => handleRemoveMember(member.user_id, member.username)}
                              title="Remove Member"
                            >
                              <i className="bi bi-person-dash"></i>
                            </Button>
                          ) : (
                            <span className="text-muted">-</span>
                          )}
                        </td>
                      )}
                    </tr>
                  ))}
                </tbody>
              </Table>
            ) : (
              <div className="text-center py-4">
                <i className="bi bi-people text-muted" style={{ fontSize: '2rem' }}></i>
                <h6 className="text-muted mt-2">No members found</h6>
                <p className="text-muted">
                  This space doesn't have any members yet.
                </p>
              </div>
            )}
          </>
        )}
      </Modal.Body>

      <Modal.Footer>
        <Button variant="secondary" onClick={onHide}>
          Close
        </Button>
      </Modal.Footer>
    </Modal>
  );
};

export default ViewMembersModal;