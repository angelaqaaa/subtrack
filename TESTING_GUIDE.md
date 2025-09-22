# SubTrack Comprehensive Testing Guide

## Overview
This guide covers all possible user scenarios, edge cases, and troubleshooting steps for the SubTrack subscription management application.

## Table of Contents
1. [Prerequisites & Setup](#prerequisites--setup)
2. [Authentication Testing](#authentication-testing)
3. [Dashboard Testing](#dashboard-testing)
4. [Subscription Management Testing](#subscription-management-testing)
5. [Shared Spaces Testing](#shared-spaces-testing)
6. [Reports & Export Testing](#reports--export-testing)
7. [Settings & Profile Testing](#settings--profile-testing)
8. [Error Handling & Edge Cases](#error-handling--edge-cases)
9. [Cross-Browser Testing](#cross-browser-testing)
10. [Performance Testing](#performance-testing)
11. [Troubleshooting Guide](#troubleshooting-guide)

---

## Prerequisites & Setup

### Development Environment
- **Frontend**: React app running on `http://localhost:3003`
- **Backend**: PHP server with database connection
- **Database**: MySQL with proper schema
- **Browser**: Chrome/Firefox with Developer Tools enabled

### Initial Setup Verification
1. **Start Services**:
   ```bash
   # Frontend
   cd frontend && PORT=3003 npm start

   # Backend (ensure PHP server is running)
   # Verify database connection
   ```

2. **Verify Endpoints**:
   - Frontend: `http://localhost:3003`
   - API endpoints: `/api_auth.php`, `/api_spaces.php`, `/api_dashboard.php`

3. **Check Browser Console**:
   - Open Developer Tools (F12)
   - Monitor Console tab for errors
   - Check Network tab for failed requests

---

## Authentication Testing

### User Registration
**Test Cases:**
1. **Valid Registration**
   - Username: 6+ characters, alphanumeric
   - Email: Valid format
   - Password: 8+ characters with special chars
   - **Expected**: Success message, redirect to login

2. **Invalid Registration**
   - Duplicate username/email
   - Weak password
   - Invalid email format
   - **Expected**: Appropriate error messages

3. **Edge Cases**
   - Very long usernames (>50 chars)
   - Special characters in username
   - International characters

**Troubleshooting:**
- **401 Errors**: Check CORS headers in PHP files
- **Network Errors**: Verify backend server is running
- **No Response**: Check API endpoint URLs

### User Login
**Test Cases:**
1. **Valid Login**
   - Correct username/password
   - **Expected**: Redirect to dashboard, user context populated

2. **Invalid Login**
   - Wrong username/password
   - Non-existent user
   - **Expected**: Error message, no redirect

3. **Session Management**
   - Login persistence across browser tabs
   - Auto-logout on session expiry
   - Remember me functionality

**Debug Steps:**
```javascript
// Check user context in browser console
console.log('Auth Context User:', user);
console.log('Is Authenticated:', isAuthenticated);

// Check localStorage (should be empty - using cookies)
console.log('LocalStorage:', localStorage.getItem('user'));

// Check cookies
document.cookie;
```

### Logout
**Test Cases:**
1. **Normal Logout**
   - Click logout button
   - **Expected**: Redirect to login, context cleared

2. **Session Expiry**
   - Wait for session timeout
   - **Expected**: Auto-redirect to login

---

## Dashboard Testing

### Initial Load
**Test Cases:**
1. **First Time User**
   - No subscriptions
   - No spaces
   - **Expected**: Welcome message, empty states

2. **Existing User**
   - Display recent subscriptions
   - Display shared spaces
   - Display activity feed

### Subscription Overview
**Test Cases:**
1. **Subscription Cards**
   - Service name, cost, billing cycle
   - Active/inactive status badges
   - Edit/delete buttons

2. **Quick Actions**
   - Add subscription button
   - "View All" subscriptions link

### Shared Spaces Overview
**Test Cases:**
1. **Space Cards**
   - Space name, member count
   - User role badge (Admin/Editor/Viewer)
   - "Enter" button for each space

2. **Role-Based Display**
   - Admin: Border green, "Admin" badge
   - Editor: Border blue, "Editor" badge
   - Viewer: Border blue, "Viewer" badge

**Troubleshooting:**
- **Empty Spaces**: Check if user is member of any spaces
- **Wrong Role Display**: Verify `user_role` field in API response
- **No Data**: Check API responses in Network tab

---

## Subscription Management Testing

### Adding Subscriptions
**Test Cases:**
1. **Basic Subscription**
   - Service name, cost, billing cycle
   - Start date, category
   - **Expected**: Success message, appears in list

2. **Advanced Options**
   - End date (optional)
   - Custom categories
   - Different currencies
   - **Expected**: All fields saved correctly

3. **Space Sync Option**
   - Enable "Add to shared space"
   - Select space from dropdown
   - **Expected**: Subscription added to both personal and space

**Debug Steps:**
```javascript
// Check form data before submission
console.log('Form Data:', formData);

// Check space sync settings
console.log('Enable Space Sync:', enableSpaceSync);
console.log('Selected Space:', syncToSpace);

// Verify API response
console.log('API Response:', response);
```

### Editing Subscriptions
**Test Cases:**
1. **Field Updates**
   - Change cost, billing cycle, dates
   - Update category, service name
   - **Expected**: Changes saved, UI updated

2. **Space Management**
   - Add existing subscription to space
   - Remove from space
   - Change target space

### Deleting Subscriptions
**Test Cases:**
1. **Single Delete**
   - Confirm dialog appears
   - **Expected**: Subscription removed from list

2. **Bulk Delete**
   - Select multiple subscriptions
   - **Expected**: All selected items deleted

---

## Shared Spaces Testing

### Space Creation
**Test Cases:**
1. **Basic Space**
   - Name (required), description (optional)
   - **Expected**: Space created, user is admin

2. **Validation**
   - Empty name
   - Very long names/descriptions
   - **Expected**: Appropriate validation messages

### Space Navigation
**Test Cases:**
1. **Enter Space**
   - Click "Enter" from dashboard
   - **Expected**: Navigate to `/spaces/{spaceId}`

2. **Direct URL Access**
   - Type URL manually: `/spaces/123`
   - **Expected**: Load space if user is member

3. **Unauthorized Access**
   - Access space user isn't member of
   - **Expected**: Access denied error

### Role-Based Permissions

#### Admin Role Testing
**Available Actions:**
- View/edit all subscriptions
- Invite new users
- Remove members
- Delete space

**Test Cases:**
1. **Invite Users**
   - Valid email addresses
   - Different roles (admin/editor/viewer)
   - **Expected**: Invitation sent, pending status

2. **Member Management**
   - View all members with roles
   - Remove members (not self)
   - Change member roles

3. **Space Management**
   - Delete space button visible
   - Confirmation dialog
   - **Expected**: Space and all data deleted

#### Editor Role Testing
**Available Actions:**
- View/edit subscriptions
- Add new subscriptions
- Cannot invite users or manage members

**Test Cases:**
1. **Subscription Management**
   - Add new subscriptions to space
   - Edit existing subscriptions
   - **Expected**: Changes reflected immediately

2. **Restricted Actions**
   - Invite button hidden
   - No member management options
   - "Leave Space" button (not delete)

#### Viewer Role Testing
**Available Actions:**
- View subscriptions (read-only)
- View members list
- Cannot edit anything

**Test Cases:**
1. **Read-Only Access**
   - No edit buttons on subscriptions
   - No add subscription button
   - **Expected**: All data visible but not editable

2. **Limited UI**
   - "Leave Space" button only
   - No management options

### Member Invitations
**Test Cases:**
1. **Valid Invitations**
   - Existing user emails
   - Different roles
   - **Expected**: Invitation appears in user's pending list

2. **Invalid Invitations**
   - Non-existent emails
   - Already a member
   - **Expected**: Appropriate error messages

3. **Invitation Responses**
   - Accept invitation
   - Reject invitation
   - **Expected**: User added/removed from space

### Subscription Syncing
**Test Cases:**
1. **Sync Existing Subscriptions**
   - Select from personal subscriptions
   - Bulk sync multiple items
   - **Expected**: Subscriptions appear in space

2. **Add New to Space**
   - Create subscription directly in space
   - **Expected**: Appears in space immediately

**Debug Steps:**
```javascript
// Check current user role
console.log('Current User Role:', currentUserRole);

// Verify space membership
console.log('Space Members:', members);

// Check permissions
console.log('Can Edit:', currentUserRole === 'admin' || currentUserRole === 'editor');
console.log('Can Invite:', currentUserRole === 'admin');
```

---

## Reports & Export Testing

### PDF Export
**Test Cases:**
1. **Basic Export**
   - All subscriptions
   - **Expected**: PDF downloaded with correct data

2. **Filtered Export**
   - Date range filters
   - Category filters
   - Status filters

3. **Empty Data**
   - No subscriptions to export
   - **Expected**: Empty PDF with headers

**Troubleshooting:**
- **jsPDF Errors**: Check browser console for library issues
- **Download Fails**: Verify blob creation and download trigger
- **Formatting Issues**: Check autoTable configuration

### CSV Export
**Test Cases:**
1. **Data Integrity**
   - All fields exported correctly
   - Proper CSV formatting
   - **Expected**: Opens correctly in Excel/Sheets

2. **Special Characters**
   - Unicode characters
   - Commas in data
   - **Expected**: Proper escaping

---

## Settings & Profile Testing

### Profile Management
**Test Cases:**
1. **View Profile**
   - Display current user info
   - **Expected**: Username, email shown

2. **Update Profile**
   - Change email, password
   - **Expected**: Changes saved, session maintained

### Categories Management
**Test Cases:**
1. **Add Categories**
   - Custom category names
   - **Expected**: Available in subscription forms

2. **Edit/Delete Categories**
   - Modify existing categories
   - **Expected**: Changes reflected in dropdowns

### API Keys
**Test Cases:**
1. **Generate Keys**
   - Create new API key
   - **Expected**: Key displayed once, stored securely

2. **Manage Keys**
   - View active keys
   - Revoke keys
   - **Expected**: Proper access control

---

## Error Handling & Edge Cases

### Network Issues
**Test Cases:**
1. **Offline Scenarios**
   - Disconnect internet
   - **Expected**: Graceful error messages

2. **Slow Connections**
   - Throttle network speed
   - **Expected**: Loading indicators, timeouts

3. **Server Downtime**
   - Stop backend server
   - **Expected**: User-friendly error messages

### Data Validation
**Test Cases:**
1. **Invalid Dates**
   - End date before start date
   - Future dates for past subscriptions

2. **Numerical Limits**
   - Negative costs
   - Very large numbers
   - Invalid decimal places

3. **Text Limits**
   - Very long service names
   - Special characters
   - Script injection attempts

### Browser Compatibility
**Test Cases:**
1. **JavaScript Disabled**
   - **Expected**: Graceful degradation message

2. **Local Storage Disabled**
   - **Expected**: Cookie fallback (already implemented)

3. **Cookies Disabled**
   - **Expected**: Warning message, limited functionality

---

## Cross-Browser Testing

### Supported Browsers
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### Test Matrix
For each browser:
1. **Core Functionality**
   - Login/logout
   - CRUD operations
   - Navigation

2. **Advanced Features**
   - PDF export
   - File uploads
   - Real-time updates

3. **Responsive Design**
   - Mobile devices
   - Tablet layouts
   - Desktop resolutions

---

## Performance Testing

### Load Testing
**Test Cases:**
1. **Large Datasets**
   - 100+ subscriptions
   - 50+ spaces
   - **Expected**: Smooth performance

2. **Concurrent Users**
   - Multiple users in same space
   - **Expected**: No conflicts, data consistency

### Memory Testing
**Test Cases:**
1. **Memory Leaks**
   - Navigate between pages repeatedly
   - **Expected**: Memory usage stable

2. **Component Cleanup**
   - Unmount/remount components
   - **Expected**: Proper cleanup

---

## Troubleshooting Guide

### Common Issues & Solutions

#### Authentication Problems
**Issue**: "User not authenticated" errors
**Solutions:**
1. Check if backend server is running
2. Verify CORS headers in PHP files
3. Check browser cookies/session storage
4. Clear browser cache and try again

**Debug Commands:**
```javascript
// Check auth context
const { user, isAuthenticated } = useAuth();
console.log('User:', user, 'Authenticated:', isAuthenticated);

// Check API response
fetch('/api_auth.php?action=check_auth', {
  credentials: 'include'
}).then(r => r.json()).then(console.log);
```

#### Space Access Issues
**Issue**: "You are not a member of this space"
**Solutions:**
1. Verify user ID in AuthContext matches member list
2. Check space members API response
3. Ensure user has accepted invitation

**Debug Commands:**
```javascript
// Check user ID matching
console.log('User ID:', user?.id);
console.log('Members:', members);
console.log('Current Member:', members.find(m => m.user_id === user?.id));
```

#### API Connection Issues
**Issue**: Network errors or failed requests
**Solutions:**
1. Check backend server status
2. Verify API endpoint URLs
3. Check CORS configuration
4. Inspect network requests in DevTools

**Debug Commands:**
```bash
# Test API endpoints directly
curl -X GET "http://localhost/api_auth.php?action=check_auth" -H "Cookie: PHPSESSID=..."

# Check server logs
tail -f /var/log/apache2/error.log
```

#### Role Permission Issues
**Issue**: Wrong buttons showing for user role
**Solutions:**
1. Verify currentUserRole state
2. Check member role in API response
3. Clear component state and reload

**Debug Commands:**
```javascript
// Check role detection
console.log('Current User Role:', currentUserRole);
console.log('Should show admin buttons:', currentUserRole === 'admin');
```

#### PDF Export Issues
**Issue**: PDF export fails or shows errors
**Solutions:**
1. Check browser console for jsPDF errors
2. Verify autoTable import
3. Check data formatting

**Debug Commands:**
```javascript
// Test PDF generation
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';

const doc = new jsPDF();
autoTable(doc, {
  head: [['Test']],
  body: [['Data']]
});
```

### Performance Issues
**Issue**: Slow loading or unresponsive UI
**Solutions:**
1. Check for memory leaks in DevTools
2. Optimize database queries
3. Implement pagination for large datasets
4. Add loading indicators

### Data Consistency Issues
**Issue**: Data not updating across components
**Solutions:**
1. Check state management patterns
2. Verify API response handling
3. Implement proper cache invalidation
4. Use React DevTools to inspect state

---

## Testing Checklist

### Pre-Release Testing
- [ ] All authentication flows work
- [ ] CRUD operations for subscriptions
- [ ] Space creation and management
- [ ] Role-based permissions enforced
- [ ] PDF/CSV exports functional
- [ ] Error handling graceful
- [ ] Cross-browser compatibility
- [ ] Mobile responsiveness
- [ ] Performance acceptable

### Regression Testing
- [ ] Previously fixed bugs don't reappear
- [ ] Core workflows unchanged
- [ ] API compatibility maintained
- [ ] Database migrations successful

### Security Testing
- [ ] SQL injection prevention
- [ ] XSS protection
- [ ] CSRF protection
- [ ] Proper authentication/authorization
- [ ] Secure session management

---

## Automated Testing Setup

### Unit Tests
```bash
# Run component tests
npm test

# Run with coverage
npm test -- --coverage
```

### Integration Tests
```bash
# Run API tests
npm run test:integration

# Run E2E tests
npm run test:e2e
```

### Performance Tests
```bash
# Run Lighthouse audit
npx lighthouse http://localhost:3003 --view

# Run bundle analyzer
npm run analyze
```

---

This comprehensive testing guide covers all major scenarios and provides specific debugging steps for common issues. Use this systematically to ensure robust application functionality across all user workflows.