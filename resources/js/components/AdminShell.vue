<script setup>
import { computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import { adminBrandId, adminBrands, loadAdminBrands, selectAdminBrand } from '../adminBrand';
import { brandLogo } from '../brandLogo';
import AppHeader from './AppHeader.vue';

const route = useRoute();
const brandPaths = ['/admin/produk', '/admin/kategori', '/admin/harga', '/admin/diskon'];
const showBrands = computed(() => brandPaths.includes(route.path));

watch(showBrands, (visible) => {
    if (visible) {
        loadAdminBrands().catch(() => {});
    }
}, { immediate: true });
</script>

<template>
    <div class="app-shell">
        <AppHeader />
        <main class="admin-main">
            <div class="admin-heading">
                <div>
                    <h1>Kelola Product Claim</h1>
                    <p>Pesanan per invoice, produk, harga, dan ketentuan tiap brand. Semua nominal dalam Rupiah.</p>
                </div>
                <div class="admin-tabs">
                    <RouterLink to="/admin/pesanan" class="admin-tab">Pesanan</RouterLink>
                    <RouterLink to="/admin/produk" class="admin-tab" :class="{ 'router-link-active': showBrands }">Katalog</RouterLink>
                    <RouterLink to="/admin/user" class="admin-tab">User</RouterLink>
                </div>
            </div>
            <div
                v-if="showBrands && adminBrands.length"
                class="admin-brand-block"
                :style="{ '--brand-cols': adminBrands.length }"
            >
                <div class="brand-tabs">
                    <button
                        v-for="brand in adminBrands"
                        :key="brand.id"
                        type="button"
                        class="brand-tab"
                        :class="{ active: brand.id === adminBrandId }"
                        @click="selectAdminBrand(brand.id)"
                    >
                        <span class="brand-icon">
                            <img v-if="brandLogo(brand)" :src="brandLogo(brand)" :alt="brand.name">
                            <template v-else>{{ brand.short }}</template>
                        </span>
                        <span><strong>{{ brand.name }}</strong><small>{{ brand.categories.length }} kategori · diskon {{ brand.discount_percent }}%</small></span>
                    </button>
                </div>
                <div class="admin-tabs brand-filters">
                    <RouterLink to="/admin/produk" class="admin-tab">Produk</RouterLink>
                    <RouterLink to="/admin/kategori" class="admin-tab">Kategori</RouterLink>
                    <RouterLink to="/admin/harga" class="admin-tab">Harga</RouterLink>
                    <RouterLink to="/admin/diskon" class="admin-tab">Diskon & ongkir</RouterLink>
                </div>
            </div>
            <slot />
        </main>
    </div>
</template>
