import axios from 'axios';

// Create a dedicated Axios instance
const api = axios.create({
    baseURL: 'http://127.0.0.1:8000/api', // Pointing to your Laravel API
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Interceptors can be added here later for Tokens/Auth
export default api;