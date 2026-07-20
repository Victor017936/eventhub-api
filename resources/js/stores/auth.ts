import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import api from '@/services/api';

export type UserRole = 'user' | 'admin';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    role: UserRole;
}

interface AuthorizationData {
    token: string;
    type: string;
    expires_in: number;
}

interface AuthResponse {
    message: string;
    user: AuthUser;
    authorization: AuthorizationData;
}

export interface LoginPayload {
    email: string;
    password: string;
}

export interface RegisterPayload {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export const useAuthStore = defineStore('auth', () => {
    const user = ref<AuthUser | null>(null);

    const token = ref<string | null>(
        localStorage.getItem('eventhub_token'),
    );

    const isLoading = ref(false);

    const isAuthenticated = computed(
        () => token.value !== null,
    );

    const isAdmin = computed(
        () => user.value?.role === 'admin',
    );

    function saveAuthentication(response: AuthResponse): void {
        user.value = response.user;
        token.value = response.authorization.token;

        localStorage.setItem(
            'eventhub_token',
            response.authorization.token,
        );
    }

    function clearAuthentication(): void {
        user.value = null;
        token.value = null;

        localStorage.removeItem('eventhub_token');
    }

    async function login(payload: LoginPayload): Promise<void> {
        isLoading.value = true;

        try {
            const response = await api.post<AuthResponse>(
                '/login',
                payload,
            );

            saveAuthentication(response.data);
        } finally {
            isLoading.value = false;
        }
    }

    async function register(
        payload: RegisterPayload,
    ): Promise<void> {
        isLoading.value = true;

        try {
            const response = await api.post<AuthResponse>(
                '/register',
                payload,
            );

            saveAuthentication(response.data);
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchUser(): Promise<void> {
        if (! token.value) {
            return;
        }

        try {
            const response = await api.get<{
                user: AuthUser;
            }>('/me');

            user.value = response.data.user;
        } catch {
            clearAuthentication();
        }
    }

    async function logout(): Promise<void> {
        try {
            if (token.value) {
                await api.post('/logout');
            }
        } finally {
            clearAuthentication();
        }
    }

    return {
        user,
        token,
        isLoading,
        isAuthenticated,
        isAdmin,
        login,
        register,
        fetchUser,
        logout,
    };
});
