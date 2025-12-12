import { defineStore } from 'pinia';
import authService from '../services/authService';
import pusherService from '../services/pusherService';
import { useOrderStore } from './OrderStore'; // Import useOrderStore
import { setAuthToken } from '../services/apiClient'; // Import setAuthToken

export const useAuthStore = defineStore('auth', {
  state: () => ({
    authToken: localStorage.getItem('authToken') || null,
    user: null, // User profile data
    isLoggedIn: !!localStorage.getItem('authToken'),
    pusherChannel: null, // To store the Pusher channel subscription
  }),
  getters: {
    isAuthenticated: (state) => state.isLoggedIn,
    currentUser: (state) => state.user,
  },
  actions: {
    async login(credentials) {
      const response = await authService.login(credentials);
      
      const token = response.data?.data?.token;

      if (!token || typeof token !== 'string' || token.trim() === '') {
        throw new Error('Authentication failed: Invalid token received from server.');
      }

      this.authToken = token;
      localStorage.setItem('authToken', token);
      setAuthToken(token);
      this.isLoggedIn = true;

      // Always fetch the full profile from the dedicated endpoint after login.
      await this.fetchUserProfile();
      
      this.subscribeToPusherChannel();
    },

    async register(credentials) {
      const response = await authService.register(credentials);
      
      const token = response.data?.data?.token;

      if (!token || typeof token !== 'string' || token.trim() === '') {
        throw new Error('Registration failed: Invalid token received from server.');
      }

      this.authToken = token;
      localStorage.setItem('authToken', token);
      setAuthToken(token);
      this.isLoggedIn = true;
      
      // Always fetch the full profile from the dedicated endpoint after registration.
      await this.fetchUserProfile();

      this.subscribeToPusherChannel();
    },

    async logout() {
      // First, attempt to log out from the server.
      // We wrap this in a try/catch in case of network errors,
      // but we proceed with client-side cleanup regardless.
      try {
        await authService.logout();
      } catch (error) {
        console.error("Server logout failed, but proceeding with client-side cleanup:", error);
      }

      // ALWAYS perform client-side cleanup.
      this.authToken = null;
      this.user = null;
      this.isLoggedIn = false;
      localStorage.removeItem('authToken');
      setAuthToken(null); // Clear token from apiClient
      this.unsubscribeFromPusherChannel();
    },
    async fetchUserProfile() {
      if (!this.authToken) {
        this.user = null;
        return;
      }
      try {
        const response = await authService.getProfile();
        this.user = response.data.data; // Extract from the 'data' property
      } catch (error) {
        if (error.response && error.response.status === 401) {
          this.logout();
        }
        // No need to console.error here as it's handled by the calling component
        throw error;
      }
    },
    initializeAuth() {
      if (this.authToken && !this.user) {
        setAuthToken(this.authToken); // Set token in apiClient on init if available
        this.fetchUserProfile().then(() => {
          // Subscribe only if fetchUserProfile was successful and user is set
          if (this.user && this.user.id) {
            this.subscribeToPusherChannel();
          }
        }).catch(() => {
          this.logout();
        });
      } else if (!this.authToken) {
          this.unsubscribeFromPusherChannel();
      }
    },
    subscribeToPusherChannel() {
      try {
        if (this.user && this.user.id && !this.pusherChannel) {
          console.log('Initializing Pusher subscription for user:', this.user.id);
          this.pusherChannel = pusherService.subscribeToPrivateChannel(
            this.user.id,
            'OrderMatched',
            (data) => {
              console.log('OrderMatched event received:', data);
              // Refresh user profile to update balances
              this.fetchUserProfile();
              // Also notify OrderStore to update its relevant state
              const orderStore = useOrderStore();
              orderStore.handleOrderMatched(data);
            },
            this.authToken // Pass the auth token
          );
        }
      } catch (error) {
        console.warn(
          'Real-time connection failed. The application will continue without live updates. Please ensure your WebSocket server is running and configured correctly.',
          error
        );
      }
    },
    unsubscribeFromPusherChannel() {
      if (this.pusherChannel) {
        this.pusherChannel.unsubscribe();
        this.pusherChannel = null;
        pusherService.disconnect();
        console.log('Unsubscribed from Pusher channel.');
      }
    }
  },
});