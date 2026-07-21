import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
    },
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem(
        'eventhub_token',
    );

    if (token) {
        config.headers.set(
            'Authorization',
            `Bearer ${token}`,
        );
    }

    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error: unknown) => {
        if (
            axios.isAxiosError(error)
            && error.response?.status === 401
        ) {
            const requestUrl =
                error.config?.url ?? '';

            const ignoredEndpoints = [
                '/login',
                '/register',
                '/logout',
                '/refresh',
            ];

            const isIgnoredEndpoint =
                ignoredEndpoints.includes(requestUrl);

            const hasAuthenticationToken =
                localStorage.getItem(
                    'eventhub_token',
                ) !== null;

            if (
                hasAuthenticationToken
                && ! isIgnoredEndpoint
            ) {
                window.dispatchEvent(
                    new Event(
                        'eventhub:unauthorized',
                    ),
                );
            }
        }

        return Promise.reject(error);
    },
);

export default api;
