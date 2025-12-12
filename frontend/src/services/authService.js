import apiClient from './apiClient';

export default {
    register(credentials) {
        return apiClient.post('/register', credentials);
    },
    login(credentials) {
        return apiClient.post('/login', credentials);
    },
    logout() {
        return apiClient.post('/logout');
    },
    /**
     * NOTE: The backend API specification does not currently include a
     * 'forgot-password' endpoint. This is a placeholder.
     */
    forgotPassword(email) {
        console.warn("Forgot Password functionality is not yet implemented in the backend.");
        // Example of what it might look like:
        // return apiClient.post('/forgot-password', { email });
        return Promise.resolve({ message: "Forgot password functionality is pending backend implementation." });
    },
    getProfile() {
        return apiClient.get('/profile');
    }

};
