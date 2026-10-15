<script setup>
import { computed, ref, watch } from 'vue';
import { brandLogo } from '../brandLogo';
import { rupiah } from '../format';
import ProductPhoto from './ProductPhoto.vue';
import ProductPreview from './ProductPreview.vue';

const props = defineProps({
    brands: {
        type: Array,
        required: true,
    },
    discount: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['add']);

const activeBrandId = ref(props.brands[0]?.id ?? null);
const category = ref('Semua');
const query = ref('');
const selected = ref(null);

watch(() => props.brands, (brands) => {
    if (!brands.some((brand) => brand.id === activeBrandId.value)) {
        activeBrandId.value = brands[0]?.id ?? null;
        category.value = 'Semua';
    }
});

const activeBrand = computed(() => props.brands.find((brand) => brand.id === activeBrandId.value) ?? props.brands[0]);
const categories = computed(() => ['Semua', ...(activeBrand.value?.categories ?? [])]);
const products = computed(() => {
    const list = activeBrand.value?.products ?? [];
    const term = query.value.trim().toLowerCase();

    return list.filter((product) => {
        const matchesCategory = category.value === 'Semua' || product.category === category.value;
        const matchesQuery = product.name.toLowerCase().includes(term);

        return matchesCategory && matchesQuery;
    });
});
const countLabel = computed(() => {
    if (!activeBrand.value) {
        return 'Pilih brand dan produk yang kamu inginkan';
    }

    return `${products.value.length} produk tersedia dari ${activeBrand.value.name}`;
});

function selectBrand(id) {
    activeBrandId.value = id;
    category.value = 'Semua';
}

function openProduct(product) {
    selected.value = {
        ...product,
        brand: activeBrand.value?.name ?? '',
    };
}
</script>

<template>
    <section class="toolbar">
        <div>
            <h2>Katalog Produk</h2>
            <p>{{ countLabel }}</p>
        </div>
        <label class="search">
            <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="m16.5 16.5 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <input v-model="query" type="search" placeholder="Cari produk..." />
        </label>
    </section>

    <div class="brand-tabs">
        <button
            v-for="brand in brands"
            :key="brand.id"
            type="button"
            class="brand-tab"
            :class="{ active: brand.id === activeBrand?.id }"
            @click="selectBrand(brand.id)"
        >
            <span class="brand-icon">
                <img v-if="brandLogo(brand)" :src="brandLogo(brand)" :alt="brand.name">
                <template v-else>{{ brand.short }}</template>
            </span>
            <span><strong>{{ brand.name }}</strong><small>{{ brand.subtitle }}</small></span>
        </button>
    </div>

    <div class="categories">
        <button
            v-for="name in categories"
            :key="name"
            type="button"
            class="category"
            :class="{ active: name === category }"
            @click="category = name"
        >
            {{ name }}
        </button>
    </div>

    <div class="product-grid">
        <article
            v-for="product in products"
            :key="product.id"
            class="product-card"
            role="button"
            tabindex="0"
            :aria-label="`Lihat detail ${product.name}`"
            @click="openProduct(product)"
            @keydown.enter.prevent="openProduct(product)"
            @keydown.space.prevent="openProduct(product)"
        >
            <div class="product-image">
                <ProductPhoto :src="product.image || ''" :alt="product.name" />
                <span class="discount">{{ discount }}% OFF</span>
            </div>
            <div class="product-info">
                <span class="product-cat">{{ product.category }}</span>
                <div class="product-name">{{ product.name }}</div>
                <div class="price-row">
                    <div class="price">
                        <small>{{ rupiah(product.price) }}</small>
                        <strong>{{ rupiah(product.claim_price) }}</strong>
                    </div>
                    <button class="add-btn" type="button" :aria-label="`Tambahkan ${product.name}`" @click.stop="emit('add', product)">+</button>
                </div>
            </div>
        </article>
        <div v-if="products.length === 0" class="empty">Produk yang kamu cari belum tersedia.</div>
    </div>

    <ProductPreview
        :product="selected"
        :discount="discount"
        @close="selected = null"
        @add="emit('add', $event)"
    />
</template>
