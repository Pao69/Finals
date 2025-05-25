import axios from 'axios';

const baseURL = 'http://localhost/codes/PROJ/dbConnect';

const api = axios.create({
  baseURL,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
  }
});

// Add request interceptor to add token to all requests
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    console.error('Request error:', error);
    return Promise.reject(error);
  }
);

// Add response interceptor to handle errors consistently
api.interceptors.response.use(
  (response) => {
    // Handle successful responses
    if (response.data?.success === false) {
      return Promise.reject({
        message: response.data.message || 'Operation failed',
        response: response,
        status: response.status
      });
    }
    return response;
  },
  (error) => {
    console.error('Response error:', error);
    
    // Handle network errors
    if (!error.response) {
      return Promise.reject({
        message: 'Network error. Please check your connection.',
        status: 0
      });
    }

    // Handle API errors
    const errorMessage = error.response?.data?.message || error.message || 'An error occurred';
    return Promise.reject({
      message: errorMessage,
      response: error.response,
      status: error.response?.status,
      headers: error.response?.headers
    });
  }
);

export default api; 