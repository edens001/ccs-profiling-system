// frontend/src/config.js
// API configuration for development vs production

const isDevelopment = import.meta.env.DEV || window.location.hostname === 'localhost';

export const API_BASE_URL = isDevelopment 
    ? 'http://localhost/backend/api'  // Your local PHP setup
    : import.meta.env.VITE_API_BASE_URL || 'https://ccs-profiling-backend.onrender.com/api';

// In your API calls (axios), use:
// axios.defaults.baseURL = API_BASE_URL;