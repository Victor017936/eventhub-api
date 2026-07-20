import {
    createRouter,
    createWebHistory,
} from 'vue-router';

import AdminCategoriesView from '@/views/AdminCategoriesView.vue';
import AdminDashboardView from '@/views/AdminDashboardView.vue';
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
            path: '/login',
            name: 'login',
            component: LoginView,
        },
        {
            path: '/register',
            name: 'register',
            component: RegisterView,
        },
    ],
});

router.beforeEach((to) => {
    const token = localStorage.getItem('eventhub_token');

    if (to.meta.requiresAuth && ! token) {
        return {
            name: 'login',
            query: {
                redirect: to.fullPath,
            },
        };
    }
});

export default router;