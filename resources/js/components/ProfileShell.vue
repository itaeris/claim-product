<script setup>
import AppHeader from './AppHeader.vue';

defineProps({
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="app-shell">
        <AppHeader />
        <main class="admin-main">
            <div class="admin-heading">
                <div>
                    <h1>Profil</h1>
                    <p>{{ description }}</p>
                </div>
                <div class="admin-tabs">
                    <RouterLink to="/employee/profile/riwayat" class="admin-tab">Riwayat produk</RouterLink>
                    <RouterLink to="/employee/profile/pengaturan" class="admin-tab">Pengaturan</RouterLink>
                </div>
            </div>
            <slot />
        </main>
    </div>
</template>
