import { createRouter, createWebHistory } from 'vue-router';
import { loadSession, user } from './session';
import { actsAsAdmin, homePath } from './viewMode';
import CategoryAdmin from './pages/CategoryAdmin.vue';
import ClaimAdmin from './pages/ClaimAdmin.vue';
import ClaimDesk from './pages/ClaimDesk.vue';
import DiscountAdmin from './pages/DiscountAdmin.vue';
import LoginPage from './pages/LoginPage.vue';
import PriceAdmin from './pages/PriceAdmin.vue';
import ProductAdmin from './pages/ProductAdmin.vue';
import HistoryPage from './pages/HistoryPage.vue';
import ProgressPage from './pages/ProgressPage.vue';
import SettingsPage from './pages/SettingsPage.vue';
import UserAdmin from './pages/UserAdmin.vue';

const employee = { auth: true, area: 'employee' };
const admin = { auth: true, area: 'admin' };

export const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/login', component: LoginPage, meta: { guest: true } },
        { path: '/', redirect: () => homePath(), meta: { auth: true } },

        { path: '/employee', component: ClaimDesk, meta: employee },
        { path: '/employee/progres', component: ProgressPage, meta: employee },
        { path: '/employee/profile', redirect: '/employee/profile/riwayat' },
        { path: '/employee/profile/riwayat', component: HistoryPage, meta: employee },
        { path: '/employee/profile/pengaturan', component: SettingsPage, meta: employee },

        { path: '/admin', redirect: '/admin/pesanan' },
        { path: '/admin/pesanan', component: ClaimAdmin, meta: admin },
        { path: '/admin/produk', component: ProductAdmin, meta: admin },
        { path: '/admin/kategori', component: CategoryAdmin, meta: admin },
        { path: '/admin/harga', component: PriceAdmin, meta: admin },
        { path: '/admin/diskon', component: DiscountAdmin, meta: admin },
        { path: '/admin/user', component: UserAdmin, meta: admin },

        { path: '/progres', redirect: '/employee/progres' },
        { path: '/profile', redirect: '/employee/profile/riwayat' },
        { path: '/profile/riwayat', redirect: '/employee/profile/riwayat' },
        { path: '/profile/pengaturan', redirect: '/employee/profile/pengaturan' },
        { path: '/kelola', redirect: '/admin' },
        { path: '/kelola/klaim', redirect: '/admin/pesanan' },
        { path: '/kelola/produk', redirect: '/admin/produk' },
        { path: '/kelola/kategori', redirect: '/admin/kategori' },
        { path: '/kelola/harga', redirect: '/admin/harga' },
        { path: '/kelola/diskon', redirect: '/admin/diskon' },
        { path: '/kelola/user', redirect: '/admin/user' },
    ],
});

router.beforeEach(async (to) => {
    await loadSession();

    if (to.meta.auth && !user.value) {
        return { path: '/login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guest && user.value) {
        return homePath();
    }

    if (to.meta.area === 'admin' && !actsAsAdmin.value) {
        return '/employee';
    }

    if (to.meta.area === 'employee' && actsAsAdmin.value) {
        return '/admin';
    }

    return true;
});
