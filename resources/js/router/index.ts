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
import NotFoundView from '@/views/NotFoundView.vue';
import RegisterView from '@/views/RegisterView.vue';

const applicationName = 'EventHub';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'home',
            component: HomeView,
            meta: {
                title: 'Acasă',
            },
        },
        {
            path: '/events',
            name: 'events',
            component: EventsView,
            meta: {
                title: 'Evenimente',
            },
        },
        {
            path: '/events/:id',
            name: 'event-details',
            component: EventDetailsView,
            meta: {
                title: 'Detalii eveniment',
            },
        },
        {
            path: '/my-reservations',
            name: 'my-reservations',
            component: MyReservationsView,
            meta: {
                title: 'Rezervările mele',
                requiresAuth: true,
            },
        },
        {
            path: '/admin/dashboard',
            name: 'admin-dashboard',
            component: AdminDashboardView,
            meta: {
                title: 'Dashboard',
                requiresAuth: true,
                requiresAdmin: true,
            },
        },
        {
            path: '/admin/categories',
            name: 'admin-categories',
            component: AdminCategoriesView,
            meta: {
                title: 'Administrare categorii',
                requiresAuth: true,
                requiresAdmin: true,
            },
        },
        {
            path: '/admin/events',
            name: 'admin-events',
            component: AdminEventsView,
            meta: {
                title: 'Administrare evenimente',
                requiresAuth: true,
                requiresAdmin: true,
            },
        },
        {
            path: '/admin/events/create',
            name: 'admin-event-create',
            component: AdminEventFormView,
            meta: {
                title: 'Eveniment nou',
                requiresAuth: true,
                requiresAdmin: true,
            },
        },
        {
            path: '/admin/events/:id/edit',
            name: 'admin-event-edit',
            component: AdminEventFormView,
            meta: {
                title: 'Editare eveniment',
                requiresAuth: true,
                requiresAdmin: true,
            },
        },
        {
            path: '/admin/events/:id/reservations',
            name: 'admin-event-reservations',
            component: AdminEventReservationsView,
            meta: {
                title: 'Participanți',
                requiresAuth: true,
                requiresAdmin: true,
            },
        },
        {
            path: '/login',
            name: 'login',
            component: LoginView,
            meta: {
                title: 'Autentificare',
                guestOnly: true,
            },
        },
        {
            path: '/register',
            name: 'register',
            component: RegisterView,
            meta: {
                title: 'Creează cont',
                guestOnly: true,
            },
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'not-found',
            component: NotFoundView,
            meta: {
                title: 'Pagina nu a fost găsită',
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

router.afterEach((to) => {
    const pageTitle =
        typeof to.meta.title === 'string'
            ? to.meta.title
            : '';

    document.title = pageTitle
        ? `${pageTitle} | ${applicationName}`
        : applicationName;
});

export default router;
