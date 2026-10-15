import { createRouter, createWebHistory } from 'vue-router';
import { loadSession, user } from './session';
import CategoryAdmin from './pages/CategoryAdmin.vue';
import ClaimAdmin from './pages/ClaimAdmin.vue';
import ClaimDesk from './pages/ClaimDesk.vue';
import DiscountAdmin from './pages/DiscountAdmin.vue';
import LoginPage from './pages/LoginPage.vue';
import PriceAdmin from './pages/PriceAdmin.vue';
import ProductAdmin from './pages/ProductAdmin.vue';
import ProgressPage from './pages/ProgressPage.vue';
import UserAdmin from './pages/UserAdmin.vue';

export const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/login', component: LoginPage, meta: { guest: true } },
        { path: '/', component: ClaimDesk, meta: { auth: true } },
        { path: '/progres', component: ProgressPage, meta: { auth: true } },
        { path: '/kelola/kategori', component: CategoryAdmin, meta: { auth: true, admin: true } },
        { path: '/kelola/produk', component: ProductAdmin, meta: { auth: true, admin: true } },
        { path: '/kelola/harga', component: PriceAdmin, meta: { auth: true, admin: true } },
        { path: '/kelola/diskon', component: DiscountAdmin, meta: { auth: true, admin: true } },
        { path: '/kelola/user', component: UserAdmin, meta: { auth: true, admin: true } },
        { path: '/kelola/klaim', component: ClaimAdmin, meta: { auth: true, admin: true } },
    ],
});

router.beforeEach(async (to) => {
    await loadSession();

    if (to.meta.auth && !user.value) {
        return { path: '/login', query: { redirect: to.fullPath } };
    }

    if (to.meta.admin && !user.value?.is_admin) {
        return '/';
    }

    if (to.meta.guest && user.value) {
        return '/';
    }

    return true;
});
