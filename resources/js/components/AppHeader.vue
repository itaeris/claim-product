<script setup>
import { inject, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import BrandMark from './BrandMark.vue';
import { logout, user } from '../session';

const route = useRoute();
const router = useRouter();
const claimCart = inject('claimCart', null);
const open = ref(false);
const menu = ref(null);
const menuStyle = ref({});

function closeMenu() {
    open.value = false;
}

function onDocumentClick(event) {
    const target = event.target instanceof Element ? event.target : event.target?.parentElement;

    if (menu.value?.contains(target) || target?.closest('.profile-dropdown')) {
        return;
    }

    closeMenu();
}

function toggleMenu() {
    open.value = !open.value;

    if (!open.value || !menu.value) {
        return;
    }

    const rect = menu.value.getBoundingClientRect();
    menuStyle.value = {
        top: `${rect.bottom + 8}px`,
        right: `${Math.max(12, window.innerWidth - rect.right)}px`,
    };
}

function onKeydown(event) {
    if (event.key !== 'Escape') {
        return;
    }

    closeMenu();
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
});

watch(() => route.path, closeMenu);

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
            <button v-if="claimCart" class="icon-btn cart-btn" type="button" aria-label="Keranjang klaim" data-tooltip="Keranjang klaim" @click="claimCart.openCart()">
                <svg viewBox="0 0 24 24" fill="none"><path d="M6.5 8h11l-1 12h-9l-1-12Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 8V7a3 3 0 0 1 6 0v1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                <span v-if="claimCart.count" class="cart-badge">{{ claimCart.count }}</span>
            </button>
            <button class="icon-btn notify-btn" type="button" aria-label="Notifikasi" data-tooltip="Notifikasi">
                <svg viewBox="0 0 24 24" fill="none"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 8h18c0-1-3-1-3-8ZM14 20h-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                <span class="dot"></span>
            </button>
            <RouterLink v-if="route.path !== '/'" to="/" class="icon-btn" aria-label="Katalog" data-tooltip="Katalog">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="13" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="4" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="13" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/></svg>
            </RouterLink>
            <RouterLink v-if="route.path !== '/progres'" to="/progres" class="icon-btn" aria-label="Progres" data-tooltip="Progres">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.7"/><path d="M12 8v4.5l3 2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
            </RouterLink>
            <RouterLink v-if="user?.is_admin && !route.path.startsWith('/kelola')" to="/kelola/produk" class="icon-btn" aria-label="Kelola" data-tooltip="Kelola">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="8" cy="7" r="1.6" fill="white" stroke="currentColor" stroke-width="1.7"/><circle cx="15" cy="12" r="1.6" fill="white" stroke="currentColor" stroke-width="1.7"/><circle cx="10" cy="17" r="1.6" fill="white" stroke="currentColor" stroke-width="1.7"/></svg>
            </RouterLink>
            <div ref="menu" class="profile-menu" :class="{ open }">
                <button class="profile" type="button" :aria-expanded="open" aria-haspopup="menu" data-tooltip="Akun" @click.stop="toggleMenu">
                    <span class="avatar">{{ user?.initials || '—' }}</span>
                    <span class="profile-copy">
                        <strong>{{ user?.name || 'Karyawan' }}</strong>
                        <small>{{ user?.is_admin ? 'Admin' : 'Karyawan' }}<template v-if="user?.team"> · {{ user.team }}</template></small>
                    </span>
                    <svg class="profile-caret" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
            <Teleport to="body">
                <div v-if="open" class="profile-dropdown" role="menu" :style="menuStyle">
                    <RouterLink v-if="route.path !== '/profile'" to="/profile" role="menuitem" @click="closeMenu">Profil</RouterLink>
                    <button class="danger" type="button" role="menuitem" @click="onLogout">Keluar</button>
                </div>
            </Teleport>
        </div>
    </header>
</template>
