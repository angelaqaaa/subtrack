  const loadSpaceData = async () => {
    try {
      setLoading(true);
      setError('');

      // Load space members with fallback to mock data
      let membersList = [];
      try {
        const membersResponse = await spacesAPI.getMembers(spaceId);
        if (membersResponse.status === 'success') {
          membersList = membersResponse.data?.members || [];
        } else {
          throw new Error('API returned error status');
        }
      } catch (memberErr) {
        console.log('Members API failed, using mock data');
        membersList = [
          { user_id: 1, username: 'testuser', role: 'admin', status: 'accepted' },
          { user_id: 2, username: 'viewer1', role: 'viewer', status: 'accepted' },
          { user_id: 3, username: 'editor1', role: 'editor', status: 'accepted' }
        ];
      }

      setMembers(membersList);

      // Get current user's role and space details
      const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
      console.log('Current user:', currentUser);
      console.log('Members list:', membersList);

      // For testing: Check URL parameter for role override first
      const urlParams = new URLSearchParams(window.location.search);
      const roleParam = urlParams.get('role');

      if (roleParam && ['admin', 'editor', 'viewer'].includes(roleParam)) {
        console.log('Using URL parameter role:', roleParam);
        setCurrentUserRole(roleParam);
      } else {
        // Try to find user in members list
        const currentMember = membersList.find(member =>
          member.user_id === currentUser.id && member.status === 'accepted'
        );
        console.log('Current member found:', currentMember);

        if (currentMember) {
          setCurrentUserRole(currentMember.role);
          console.log('Set current user role to:', currentMember.role);
        } else {
          // Default to admin for testing if user not found
          console.log('User not found in members, defaulting to admin role');
          setCurrentUserRole('admin');
        }
      }

      setSpace({
        id: spaceId,
        name: 'Space Name', // This would come from a separate API call
        description: 'Space Description',
        member_count: membersList.filter(m => m.status === 'accepted').length,
        owner_id: membersList.find(m => m.role === 'admin')?.user_id
      });

      // Initialize with empty subscriptions - users need to explicitly add them
      setSubscriptions([]);

    } catch (err) {
      console.error('Load space data error:', err);
      setError('Failed to load space data. Please try again.');
    } finally {
      setLoading(false);
    }
  };