import Pusher from 'pusher-js';
import apiClient from './apiClient';

let pusherInstance = null;

const getPusherInstance = (authToken = null) => {
  const token = authToken || localStorage.getItem('authToken');
  
  if (!pusherInstance) {
    const PUSHER_APP_KEY = import.meta.env.VITE_PUSHER_APP_KEY;
    const PUSHER_APP_CLUSTER = import.meta.env.VITE_PUSHER_APP_CLUSTER;
    const PUSHER_SCHEME = import.meta.env.VITE_PUSHER_SCHEME || 'https';

    pusherInstance = new Pusher(PUSHER_APP_KEY, {
      cluster: PUSHER_APP_CLUSTER,
      forceTLS: PUSHER_SCHEME === 'https',
      authEndpoint: `${apiClient.defaults.baseURL}/broadcasting/auth`,
      auth: {
        headers: {
          Authorization: `Bearer ${token}`
        },
      },
    });
  }
  return pusherInstance;
};

const updateAuthToken = (authToken) => {
  if (pusherInstance && pusherInstance.config && pusherInstance.config.auth) {
    pusherInstance.config.auth.headers = {
      Authorization: `Bearer ${authToken}`
    };
  }
};

const subscribeToPrivateChannel = (userId, eventName, callback, authToken = null) => {
  // Update token before subscribing
  if (authToken) {
    updateAuthToken(authToken);
  }
  
  const pusher = getPusherInstance(authToken);
  const channelName = `private-user.${userId}`;
  const channel = pusher.subscribe(channelName);

  channel.bind(eventName, (data) => {
    callback(data);
  });

  console.log(`Subscribed to private channel: ${channelName}`);

  return channel; // Return the channel object to allow unbinding if needed
};

const disconnect = () => {
  if (pusherInstance) {
    pusherInstance.disconnect();
    pusherInstance = null;
  }
};

export default {
  getPusherInstance,
  subscribeToPrivateChannel,
  updateAuthToken,
  disconnect,
};
