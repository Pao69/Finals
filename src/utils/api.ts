import axios from 'axios';

const baseURL = 'http://localhost/codes/PROJ/dbConnect';

const api = axios.create({
  baseURL,
  withCredentials: true
});

// Add request interceptor to add token to all requests
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Add response interceptor to handle errors consistently
api.interceptors.response.use(
  (response) => response,
  (error) => {
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