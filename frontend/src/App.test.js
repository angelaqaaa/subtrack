import { render, screen } from '@testing-library/react';
import App from './App';
import { authAPI } from './services/api';

// The real module talks to the PHP API through axios, so the test replaces it
jest.mock('./services/api', () => ({
  authAPI: { getCurrentUser: jest.fn() },
}));

test('sends a signed-out visitor to the login page', async () => {
  // What api/auth.php answers for current_user without a session
  authAPI.getCurrentUser.mockRejectedValue({ response: { status: 401 } });
  // AuthContext treats that 401 as the normal signed-out case and logs nothing
  const consoleError = jest.spyOn(console, 'error');

  render(<App />);

  expect(await screen.findByRole('button', { name: 'Sign In' })).toBeInTheDocument();
  expect(consoleError).not.toHaveBeenCalled();
});
