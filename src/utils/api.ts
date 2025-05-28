// NOTE: The following comments are for educational/debugging purposes and may not cover all edge cases.
// api.ts - Axios instance for API requests. Handles authentication, CORS, and error handling.

import axios from 'axios';
import router from '../router';

const baseURL = 'http://localhost/codes/PROJ/Finals/dbConnect';

const api = axios.create({
  baseURL,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
  }
});

// NOTE: Request interceptor to add JWT token to all requests.
api.interceptors.request.use(
  (config) => {
    // Try to get token from either storage
    let token = localStorage.getItem('token');
    if (!token) {
      token = sessionStorage.getItem('token');
    }
    
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    } else {
      // If no token is found, check if we're on a public route
      const publicRoutes = ['/login.php', '/signup.php', '/request_reset.php', '/reset_password.php'];
      const isPublicRoute = publicRoutes.some(route => config.url?.endsWith(route));
      
      if (!isPublicRoute) {
        // For non-public routes without token, redirect to login
        router.push('/login');
        return Promise.reject('No auth token found');
      }
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// NOTE: Response interceptor to handle token expiration and unauthorized errors.
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Clear both storages on unauthorized
      localStorage.clear();
      sessionStorage.clear();
      router.push('/login');
    }
    return Promise.reject(error);
  }
);

export default api; 