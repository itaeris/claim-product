<script setup>
import { inject, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { logout, user } from '../session';

const route = useRoute();
const router = useRouter();
const claimCart = inject('claimCart', null);
const open = ref(false);
const menu = ref(null);

function closeMenu() {
    open.value = false;
}

function onDocumentClick(event) {
    if (!menu.value?.contains(event.target)) {
        closeMenu();
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
                    <svg viewBox="0 0 24 24" fill="none"><path d="M6 12.5 10.2 17 18 7.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4.5 5.5h15v14h-15z" stroke="currentColor" stroke-width="1.5" rx="3"/></svg>
                </div>
                <div class="brandmark-name">claim<span>ly</span></div>
            </RouterLink>
            <div class="header-title">Employee Product Claim</div>
        </div>
        <div class="header-actions">
            <button v-if="claimCart" class="icon-btn cart-btn" type="button" aria-label="Keranjang klaim" @click="claimCart.openCart()">
                <svg viewBox="0 0 24 24" fill="none"><path d="M6.5 8h11l-1 12h-9l-1-12Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 8V7a3 3 0 0 1 6 0v1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                <span v-if="claimCart.count" class="cart-badge">{{ claimCart.count }}</span>
            </button>
            <button class="icon-btn notify-btn" type="button" aria-label="Notifikasi">
                <svg viewBox="0 0 24 24" fill="none"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 8h18c0-1-3-1-3-8ZM14 20h-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                <span class="dot"></span>
            </button>
            <RouterLink v-if="route.path !== '/'" to="/" class="text-btn">Katalog</RouterLink>
            <RouterLink v-if="route.path !== '/progres'" to="/progres" class="text-btn">Progres</RouterLink>
            <RouterLink v-if="user?.is_admin && !route.path.startsWith('/kelola')" to="/kelola/produk" class="text-btn">Kelola</RouterLink>
            <div ref="menu" class="profile-menu" :class="{ open }">
                <button class="profile" type="button" :aria-expanded="open" aria-haspopup="menu" @click="open = !open">
                    <span class="avatar">{{ user?.initials || '—' }}</span>
                    <span class="profile-copy">
                        <strong>{{ user?.name || 'Karyawan' }}</strong>
                        <small>{{ user?.is_admin ? 'Admin' : 'Karyawan' }}<template v-if="user?.team"> · {{ user.team }}</template></small>
                    </span>
                    <svg class="profile-caret" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div v-if="open" class="profile-dropdown" role="menu">
                    <button type="button" role="menuitem" @click="onLogout">Keluar</button>
                </div>
            </div>
        </div>
    </header>
</template>
