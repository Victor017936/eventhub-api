import {
    createPinia,
    setActivePinia,
} from 'pinia';
import {
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';
import { useAuthStore } from '@/stores/auth';

const apiMock = vi.hoisted(() => ({
    get: vi.fn(),
    post: vi.fn(),
}));

vi.mock('@/services/api', () => ({
    default: apiMock,
}));

describe('auth store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        localStorage.clear();

        apiMock.get.mockReset();
        apiMock.post.mockReset();
    });

    it('saves the authenticated user and token after login', async () => {
        apiMock.post.mockResolvedValue({
            data: {
                message: 'Authenticated.',
                user: {
                    id: 4,
                    name: 'vic1',
                    email: 'vic1@gmail.com',
                    role: 'admin',
                },
                authorization: {
                    token: 'test-jwt-token',
                    type: 'bearer',
                    expires_in: 3600,
                },
            },
        });

        const authStore = useAuthStore();

        await authStore.login({
            email: 'vic1@gmail.com',
            password: 'password',
        });

        expect(apiMock.post).toHaveBeenCalledWith(
            '/login',
            {
                email: 'vic1@gmail.com',
                password: 'password',
            },
        );

        expect(authStore.user?.name).toBe('vic1');
        expect(authStore.isAuthenticated).toBe(true);
        expect(authStore.isAdmin).toBe(true);

        expect(
            localStorage.getItem('eventhub_token'),
        ).toBe('test-jwt-token');
    });

    it('loads the user when a saved token exists', async () => {
        localStorage.setItem(
            'eventhub_token',
            'existing-token',
        );

        apiMock.get.mockResolvedValue({
            data: {
                user: {
                    id: 2,
                    name: 'Victor Test',
                    email: 'victor2@example.com',
                    role: 'user',
                },
            },
        });

        const authStore = useAuthStore();

        await authStore.fetchUser();

        expect(apiMock.get).toHaveBeenCalledWith('/me');
        expect(authStore.user?.name).toBe('Victor Test');
        expect(authStore.isAuthenticated).toBe(true);
        expect(authStore.isAdmin).toBe(false);
    });

    it('clears authentication during logout', async () => {
        localStorage.setItem(
            'eventhub_token',
            'existing-token',
        );

        apiMock.get.mockResolvedValue({
            data: {
                user: {
                    id: 4,
                    name: 'vic1',
                    email: 'vic1@gmail.com',
                    role: 'admin',
                },
            },
        });

        apiMock.post.mockResolvedValue({
            data: {
                message: 'Logged out.',
            },
        });

        const authStore = useAuthStore();

        await authStore.fetchUser();
        await authStore.logout();

        expect(apiMock.post).toHaveBeenCalledWith(
            '/logout',
        );

        expect(authStore.user).toBeNull();
        expect(authStore.isAuthenticated).toBe(false);

        expect(
            localStorage.getItem('eventhub_token'),
        ).toBeNull();
    });
});
