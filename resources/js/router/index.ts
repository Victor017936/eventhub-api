import {
    createRouter,
    createWebHistory,
} from 'vue-router';

import { useAuthStore } from '@/stores/auth';
import AdminCategoriesView from '@/views/AdminCategoriesView.vue';
import AdminDashboardView from '@/views/AdminDashboardView.vue';
import AdminEventFormView from '@/views/AdminEventFormView.vue';
import AdminEventReservationsView from '@/views/AdminEventReservationsView.vue';
import AdminEventsView from '@/views/AdminEventsView.vue';
import EventDetailsView from '@/views/EventDetailsView.vue';
import EventsView from '@/views/EventsView.vue';
import HomeView from '@/views/HomeView.vue';
import LoginView from '@/views/LoginView.vue';
import MyReservationsView from '@/views/MyReservationsView.vue';
import RegisterView from '@/views/RegisterView.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'home',
            component: HomeView,
        },
        {
            path: '/events',
            name: 'events',
            component: EventsView,
        },
        {
            path: '/events/:id',
            name: 'event-details',
            component: EventDetailsView,
        },
        {
            path: '/my-reservations',
            name: 'my-reservations',
            component: MyReservationsView,
            meta: {
                requiresAuth: true,
            },
        },
        {
            path: '/admin/dashboard',
            name: 'admin-dashboard',
            component: AdminDashboardView,
            meta: {
                requiresAuth: true,
                requiresAdmin: true,
            },
        },
        {
            path: '/admin/categories',
            name: 'admin-categories',
            component: AdminCategoriesView,
            meta: {
                requiresAuth: true,
                requiresAdmin: true,
            },
        },
        {
            path: '/admin/events',
            name: 'admin-events',
            component: AdminEventsView,
            meta: {
                requiresAuth: true,
                requiresAdmin: true,
            },
        },
        {
            path: '/admin/events/create',
            name: 'admin-event-create',
            component: AdminEventFormView,
            meta: {
                requiresAuth: true,
                requiresAdmin: true,
            },
        },
        {
            path: '/admin/events/:id/edit',
            name: 'admin-event-edit',
            component: AdminEventFormView,
            meta: {
                requiresAuth: true,
                requiresAdmin: true,
            },
        },
        {
            path: '/admin/events/:id/reservations',
            name: 'admin-event-reservations',
            component: AdminEventReservationsView,
            meta: {
                requiresAuth: true,
                requiresAdmin: true,
            },
        },
        {
            path: '/login',
            name: 'login',
            component: LoginView,
            meta: {
                guestOnly: true,
            },
        },
        {
            path: '/register',
            name: 'register',
            component: RegisterView,
            meta: {
                guestOnly: true,
            },
        },
    ],
});

router.beforeEach(async (to) => {
    const authStore = useAuthStore();

    if (! authStore.isInitialized) {
        await authStore.initialize();
    }

    if (
        to.meta.guestOnly
        && authStore.isAuthenticated
    ) {
        return {
            name: 'home',
        };
    }

    if (
        to.meta.requiresAuth
        && ! authStore.isAuthenticated
    ) {
        return {
            name: 'login',
            query: {
                redirect: to.fullPath,
                ...(authStore.sessionExpired
                    ? {
                        reason: 'expired',
                    }
                    : {}),
            },
        };
    }

    if (
        to.meta.requiresAdmin
        && ! authStore.isAdmin
    ) {
        return {
            name: 'home',
        };
    }
});

export default router;
