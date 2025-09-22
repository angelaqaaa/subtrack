import React, { useState, useEffect } from 'react';
import { Navbar, Nav, Container, Dropdown, Button, Badge } from 'react-bootstrap';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { spacesAPI } from '../../services/api';

const Navigation = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [pendingInvitationsCount, setPendingInvitationsCount] = useState(0);

  useEffect(() => {
    if (user) {
      loadPendingInvitationsCount();
      // Refresh count every 30 seconds
      const interval = setInterval(loadPendingInvitationsCount, 30000);
      return () => clearInterval(interval);
    }
  }, [user]);

  const loadPendingInvitationsCount = async () => {
    try {
      const response = await spacesAPI.getPendingInvitations();
      if (response.status === 'success') {
        setPendingInvitationsCount(response.data?.invitations?.length || 0);
      }
    } catch (err) {
      // Silently fail - not critical
      console.error('Failed to load pending invitations count:', err);
    }
  };

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const isActive = (path) => {
    return location.pathname === path;
  };

  return (
    <Navbar bg="dark" variant="dark" expand="lg" className="mb-4">
      <Container>
        <Navbar.Brand as={Link} to="/dashboard">
          <i className="bi bi-graph-up me-2"></i>
          SubTrack
        </Navbar.Brand>

        <Navbar.Toggle aria-controls="basic-navbar-nav" />

        <Navbar.Collapse id="basic-navbar-nav">
          <Nav className="me-auto">
            <Nav.Link
              as={Link}
              to="/dashboard"
              className={isActive('/dashboard') ? 'active' : ''}
            >
              <i className="bi bi-speedometer2 me-1"></i>
              Dashboard
            </Nav.Link>

            <Nav.Link
              as={Link}
              to="/subscriptions"
              className={isActive('/subscriptions') ? 'active' : ''}
            >
              <i className="bi bi-list-ul me-1"></i>
              Subscriptions
            </Nav.Link>

            <Nav.Link
              as={Link}
              to="/spaces"
              className={isActive('/spaces') ? 'active' : ''}
            >
              <i className="bi bi-people me-1"></i>
              Spaces
              {pendingInvitationsCount > 0 && (
                <Badge
                  bg="danger"
                  pill
                  className="ms-1"
                  style={{ fontSize: '0.6rem' }}
                >
                  {pendingInvitationsCount}
                </Badge>
              )}
            </Nav.Link>

            <Nav.Link
              as={Link}
              to="/insights"
              className={isActive('/insights') ? 'active' : ''}
            >
              <i className="bi bi-lightbulb me-1"></i>
              Insights
            </Nav.Link>

            <Nav.Link
              as={Link}
              to="/reports"
              className={isActive('/reports') ? 'active' : ''}
            >
              <i className="bi bi-bar-chart me-1"></i>
              Reports
            </Nav.Link>

            <Nav.Link
              as={Link}
              to="/categories"
              className={isActive('/categories') ? 'active' : ''}
            >
              <i className="bi bi-tags me-1"></i>
              Categories
            </Nav.Link>
          </Nav>

          <Nav>
            <Dropdown align="end">
              <Dropdown.Toggle variant="outline-light" id="user-dropdown">
                <i className="bi bi-person-circle me-1"></i>
                {user?.username || 'User'}
              </Dropdown.Toggle>

              <Dropdown.Menu>
                <Dropdown.Item as={Link} to="/profile">
                  <i className="bi bi-person me-2"></i>
                  Profile
                </Dropdown.Item>

                <Dropdown.Item as={Link} to="/settings">
                  <i className="bi bi-gear me-2"></i>
                  Settings
                </Dropdown.Item>

                <Dropdown.Item as={Link} to="/api-keys">
                  <i className="bi bi-key me-2"></i>
                  API Keys
                </Dropdown.Item>

                <Dropdown.Divider />

                <Dropdown.Item onClick={handleLogout}>
                  <i className="bi bi-box-arrow-right me-2"></i>
                  Sign Out
                </Dropdown.Item>
              </Dropdown.Menu>
            </Dropdown>
          </Nav>
        </Navbar.Collapse>
      </Container>
    </Navbar>
  );
};

export default Navigation;