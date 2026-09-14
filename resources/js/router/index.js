import { createRouter, createWebHistory } from 'vue-router';
import api from '../services/api';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../pages/LoginPage.vue'),
        meta: {
            guest: true,
        },
    },
    {
        path: '/settings',
        name: 'settings',
        component: () => import('../pages/SettingsPage.vue'),
        meta: {
            requiresAuth: true,
        },
    },
    {
        path: '/reviews',
        name: 'reviews',
        component: () => import('../pages/ReviewsPage.vue'),
        meta: {
            requiresAuth: true,
        },
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/login',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    if (!to.meta.requiresAuth && !to.meta.guest) {
        return true;
    }

    try {
        await api.get('/api/user');

        if (to.meta.guest) {
            return '/settings';
        }

        return true;
    } catch (e) {
        if (to.meta.requiresAuth) {
            return '/login';
        }

        return true;
    }
});

export default router;
