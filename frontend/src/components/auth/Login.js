import React, { useState } from 'react';
import { Container, Card, Form, Button, Alert, Spinner } from 'react-bootstrap';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';

const Login = () => {
  const [formData, setFormData] = useState({
    username: localStorage.getItem('username') || '',
    password: '',
    rememberMe: localStorage.getItem('rememberMe') === 'true'
  });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [requires2FA, setRequires2FA] = useState(false);
  const [twoFactorCode, setTwoFactorCode] = useState('');

  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  // Get the intended destination or default to dashboard
  const from = location.state?.from?.pathname || '/dashboard';

  const handleChange = (e) => {
    const value = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
    setFormData({
      ...formData,
      [e.target.name]: value
    });
    // Don't clear error on input change - let user read the message
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    const result = await login(
      formData.username,
      formData.password,
      formData.rememberMe,
      requires2FA ? twoFactorCode : null
    );

    if (result.success) {
      navigate(from, { replace: true });
    } else if (result.requires2FA) {
      setRequires2FA(true);
      setError('Please enter your two-factor authentication code');
    } else {
      setError(result.error);
    }

    setLoading(false);
  };

  const handleBackToCredentials = () => {
    setRequires2FA(false);
    setTwoFactorCode('');
    setError('');
  };

  return (
    <Container className="d-flex justify-content-center align-items-center min-vh-100">
      <div style={{ width: '100%', maxWidth: '400px' }}>
        <Card>
          <Card.Body>
            <div className="text-center mb-4">
              <h2>SubTrack</h2>
              <p className="text-muted">Sign in to your account</p>
            </div>

            {error && (
              <Alert variant="danger" className="mb-3">
                <i className="bi bi-exclamation-triangle me-2"></i>
                {error}
              </Alert>
            )}

            <Form onSubmit={handleSubmit}>
              {!requires2FA ? (
                <>
                  <Form.Group className="mb-3">
                    <Form.Label>Username</Form.Label>
                    <Form.Control
                      type="text"
                      name="username"
                      value={formData.username}
                      onChange={handleChange}
                      required
                      disabled={loading}
                      placeholder="Enter your username"
                    />
                  </Form.Group>

                  <Form.Group className="mb-3">
                    <Form.Label>Password</Form.Label>
                    <Form.Control
                      type="password"
                      name="password"
                      value={formData.password}
                      onChange={handleChange}
                      required
                      disabled={loading}
                      placeholder="Enter your password"
                    />
                  </Form.Group>

                  <Form.Group className="mb-3">
                    <Form.Check
                      type="checkbox"
                      id="rememberMe"
                      name="rememberMe"
                      checked={formData.rememberMe}
                      onChange={handleChange}
                      label="Remember me"
                      disabled={loading}
                    />
                  </Form.Group>
                </>
              ) : (
                <>
                  <div className="mb-3">
                    <p className="text-muted small mb-2">
                      <i className="bi bi-shield-lock me-2"></i>
                      Two-factor authentication is enabled for <strong>{formData.username}</strong>
                    </p>
                  </div>

                  <Form.Group className="mb-3">
                    <Form.Label>Authentication Code</Form.Label>
                    <Form.Control
                      type="text"
                      value={twoFactorCode}
                      onChange={(e) => setTwoFactorCode(e.target.value)}
                      required
                      disabled={loading}
                      placeholder="Enter 6-digit code"
                      maxLength={6}
                      className="text-center"
                      style={{fontSize: '1.2em', letterSpacing: '0.2em'}}
                    />
                    <Form.Text className="text-muted">
                      Enter the code from your authenticator app or use a backup code.
                    </Form.Text>
                  </Form.Group>

                  <div className="mb-3">
                    <Button
                      variant="link"
                      size="sm"
                      onClick={handleBackToCredentials}
                      disabled={loading}
                      className="p-0"
                    >
                      <i className="bi bi-arrow-left me-1"></i>
                      Back to login
                    </Button>
                  </div>
                </>
              )}

              <Button
                variant="primary"
                type="submit"
                className="w-100"
                disabled={loading || (requires2FA && (!twoFactorCode || twoFactorCode.length < 6))}
              >
                {loading ? (
                  <>
                    <Spinner
                      as="span"
                      animation="border"
                      size="sm"
                      role="status"
                      className="me-2"
                    />
                    {requires2FA ? 'Verifying...' : 'Signing in...'}
                  </>
                ) : (
                  requires2FA ? 'Verify Code' : 'Sign In'
                )}
              </Button>
            </Form>

            <div className="text-center mt-3">
              <p className="mb-0">
                Don't have an account?{' '}
                <Link to="/register" className="text-decoration-none">
                  Sign up here
                </Link>
              </p>
            </div>
          </Card.Body>
        </Card>
      </div>
    </Container>
  );
};

export default Login;