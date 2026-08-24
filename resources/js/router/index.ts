import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            component: () => import('@/layouts/AppLayout.vue'),
            meta: { requiresAuth: true },
            children: [
                {
                    path: '',
                    name: 'dashboard',
                    component: () => import('@/pages/DashboardPage.vue'),
                },
                {
                    path: 'wishlist',
                    name: 'wishlist',
                    component: () => import('@/pages/WishlistPage.vue'),
                },
                {
                    path: 'shopping',
                    name: 'shopping',
                    component: () => import('@/pages/ShoppingPage.vue'),
                },
                {
                    path: 'purchases',
                    name: 'purchases',
                    component: () => import('@/pages/PurchaseHistoryPage.vue'),
                },
                {
                    path: 'budget',
                    name: 'budget',
                    component: () => import('@/pages/BudgetPage.vue'),
                },
                {
                    path: 'categories',
                    name: 'categories',
                    component: () => import('@/pages/CategoriesPage.vue'),
                },
                {
                    path: 'settings',
                    name: 'settings',
                    component: () => import('@/pages/SettingsPage.vue'),
                },
            ],
        },
        {
            path: '/login',
            name: 'login',
            component: () => import('@/pages/auth/LoginPage.vue'),
            meta: { guestOnly: true },
        },
        {
            path: '/register',
            name: 'register',
            component: () => import('@/pages/auth/RegisterPage.vue'),
            meta: { guestOnly: true },
        },
    ],
});

router.beforeEach(async (to) => {
    const auth = useAuth();

    if (auth.status.value === 'idle' || auth.status.value === 'loading') {
        await auth.init();
    }

    const isAuthenticated = auth.status.value === 'authenticated';

    if (to.meta.requiresAuth && !isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guestOnly && isAuthenticated) {
        return { name: 'dashboard' };
    }
});

export default router;
