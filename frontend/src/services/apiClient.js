import axios from 'axios';

const apiClient = axios.create({
    baseURL: 'http://localhost:8000/api', // Your Laravel API URL
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    }
});

// Function to set the auth token directly
export const setAuthToken = (token) => {
    if (token) {
        apiClient.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    } else {
        delete apiClient.defaults.headers.common['Authorization'];
    }
};

// Add a request interceptor to automatically add the token to headers
apiClient.interceptors.request.use(config => {
    const token = localStorage.getItem('authToken');
    if (token && !config.headers.Authorization) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
}, error => {
    return Promise.reject(error);
});

// Initialize with any existing token from localStorage
setAuthToken(localStorage.getItem('authToken'));

export default apiClient;
