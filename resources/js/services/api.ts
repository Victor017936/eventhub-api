import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
    },
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('eventhub_token');

    if (token) {
        config.headers.set(
            'Authorization',
            `Bearer ${token}`,
        );
    }

    return config;
});

export default api;
