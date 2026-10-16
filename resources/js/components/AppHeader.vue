<script setup>
import { computed, inject, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import BrandMark from './BrandMark.vue';
import { api } from '../api';
import { logout, user } from '../session';
import { actsAsAdmin, setUserMode, userMode } from '../viewMode';
import { loadQuota, quota } from '../quota';

const route = useRoute();
const router = useRouter();
const claimCart = inject('claimCart', null);
const open = ref(false);
const menu = ref(null);
const menuStyle = ref({});
const bell = ref(null);
const bellOpen = ref(false);
const bellStyle = ref({});
const notifications = ref([]);
const unread = ref(0);
let poller = null;

const showQuota = computed(() => Boolean(quota.value) && !route.path.startsWith('/kelola'));
const inCart = computed(() => claimCart?.count ?? quota.value?.in_cart ?? 0);
const quotaLeft = computed(() => Math.max(0, (quota.value?.remaining ?? 0) - inCart.value));

function quotaCell(index) {
    const used = quota.value?.used ?? 0;

    if (index <= used) {
        return 'used';
    }

    return index <= used + inCart.value ? 'cart' : '';
}

function closeMenu() {
    open.value = false;
    bellOpen.value = false;
}

function onDocumentClick(event) {
    const target = event.target instanceof Element ? event.target : event.target?.parentElement;

    if (menu.value?.contains(target) || bell.value?.contains(target) || target?.closest('.profile-dropdown, .notify-dropdown')) {
        return;
    }

    closeMenu();
}

function anchorStyle(element) {
    const rect = element.getBoundingClientRect();

    return {
        top: `${rect.bottom + 8}px`,
        right: `${Math.max(12, window.innerWidth - rect.right)}px`,
    };
}

function toggleMenu() {
    bellOpen.value = false;
    open.value = !open.value;

    if (open.value && menu.value) {
        menuStyle.value = anchorStyle(menu.value);
    }
}

async function loadNotifications() {
    try {
        const data = await api.get('/api/notifications');
        notifications.value = data.items;
        unread.value = data.unread;
    } catch {
        // The bell is best-effort; the page keeps working without it.
    }
}

async function toggleBell() {
    open.value = false;
    bellOpen.value = !bellOpen.value;

    if (!bellOpen.value || !bell.value) {
        return;
    }

    bellStyle.value = anchorStyle(bell.value);
    await loadNotifications();

    if (unread.value > 0) {
        unread.value = 0;
        api.post('/api/notifications/read').catch(() => {});
    }
}

function openNotification(item) {
    closeMenu();

    if (item.url) {
        router.push(item.url);
    }
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        closeMenu();
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
    loadNotifications();
    if (!route.path.startsWith('/kelola')) {
        loadQuota(true);
    }
    poller = window.setInterval(loadNotifications, 60000);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
    window.clearInterval(poller);
});

watch(() => route.path, closeMenu);

async function toggleUserMode() {
    const enabled = !userMode.value;
    setUserMode(enabled);
    closeMenu();
    await router.push(enabled ? '/' : '/kelola/klaim');
}

async function onLogout() {
    closeMenu();
    await logout();
    await router.push('/login');
}
</script>

<template>
    <header>
        <div class="brandmark">
            <RouterLink to="/" class="brandmark">
                <div class="brandmark-logo">
                    <BrandMark />
                </div>
                <div class="brandmark-name">Product Claim</div>
            </RouterLink>
        </div>
        <div class="header-actions">
            <RouterLink
                v-if="showQuota"
                to="/profile"
                class="quota-pill"
                :class="{ empty: quotaLeft === 0 }"
                :aria-label="`Sisa kuota pembelian ${quotaLeft} dari ${quota.total} pcs`"
                :data-tooltip="`Kuota ${quota.year} · reset ${quota.resets_label}`"
            >
                <span class="quota-dots" aria-hidden="true">
                    <i v-for="index in quota.total" :key="index" :class="quotaCell(index)"></i>
                </span>
                <span class="quota-text"><strong>{{ quotaLeft }}/{{ quota.total }}</strong><small>sisa kuota</small></span>
            </RouterLink>
            <button v-if="claimCart" class="icon-btn cart-btn" type="button" aria-label="Keranjang" data-tooltip="Keranjang" @click="claimCart.openCart()">
                <svg viewBox="0 0 24 24" fill="none"><path d="M6.5 8h11l-1 12h-9l-1-12Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 8V7a3 3 0 0 1 6 0v1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                <span v-if="claimCart.count" class="cart-badge">{{ claimCart.count }}</span>
            </button>
            <div ref="bell" class="notify-wrap">
                <button class="icon-btn notify-btn" type="button" :aria-expanded="bellOpen" aria-label="Notifikasi" data-tooltip="Notifikasi" @click.stop="toggleBell">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 8h18c0-1-3-1-3-8ZM14 20h-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    <span v-if="unread" class="cart-badge">{{ unread > 9 ? '9+' : unread }}</span>
                </button>
            </div>
            <RouterLink v-if="route.path !== '/'" to="/" class="icon-btn" aria-label="Katalog" data-tooltip="Katalog">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="13" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="4" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="13" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/></svg>
            </RouterLink>
            <RouterLink v-if="route.path !== '/progres'" to="/progres" class="icon-btn" aria-label="Progres" data-tooltip="Progres">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.7"/><path d="M12 8v4.5l3 2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
            </RouterLink>
            <RouterLink v-if="actsAsAdmin && !route.path.startsWith('/kelola')" to="/kelola/klaim" class="icon-btn" aria-label="Kelola" data-tooltip="Kelola">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="8" cy="7" r="1.6" fill="white" stroke="currentColor" stroke-width="1.7"/><circle cx="15" cy="12" r="1.6" fill="white" stroke="currentColor" stroke-width="1.7"/><circle cx="10" cy="17" r="1.6" fill="white" stroke="currentColor" stroke-width="1.7"/></svg>
            </RouterLink>
            <div ref="menu" class="profile-menu" :class="{ open }">
                <button class="profile" type="button" :aria-expanded="open" aria-haspopup="menu" data-tooltip="Akun" @click.stop="toggleMenu">
                    <span class="avatar">{{ user?.initials || '—' }}</span>
                    <span class="profile-copy">
                        <strong>{{ user?.name || 'Karyawan' }}</strong>
                        <small>{{ actsAsAdmin ? 'Admin' : 'Karyawan' }}<template v-if="user?.team"> · {{ user.team }}</template></small>
                    </span>
                    <svg class="profile-caret" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
            <Teleport to="body">
                <div v-if="open" class="profile-dropdown" role="menu" :style="menuStyle">
                    <RouterLink v-if="route.path !== '/profile'" to="/profile" role="menuitem" @click="closeMenu">Profil</RouterLink>
                    <button v-if="user?.is_admin" type="button" role="menuitem" @click="toggleUserMode">
                        {{ userMode ? 'Kembali ke mode admin' : 'Belanja sebagai user' }}
                    </button>
                    <button class="danger" type="button" role="menuitem" @click="onLogout">Keluar</button>
                </div>
                <div v-if="bellOpen" class="notify-dropdown" :style="bellStyle">
                    <div class="notify-head">Notifikasi</div>
                    <p v-if="notifications.length === 0" class="notify-empty">Belum ada notifikasi.</p>
                    <button
                        v-for="item in notifications"
                        :key="item.id"
                        type="button"
                        class="notify-item"
                        :class="{ unread: !item.read }"
                        @click="openNotification(item)"
                    >
                        <strong>{{ item.title }}</strong>
                        <span>{{ item.body }}</span>
                        <small>{{ item.time_label }}</small>
                    </button>
                </div>
            </Teleport>
        </div>
    </header>
</template>
