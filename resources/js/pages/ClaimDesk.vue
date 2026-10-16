<script setup>
import { computed, onMounted, provide, reactive, ref } from 'vue';
import { api } from '../api';
import { useToast } from '../toast';
import AppHeader from '../components/AppHeader.vue';
import ClaimSidebar from '../components/ClaimSidebar.vue';
import HeroBanner from '../components/HeroBanner.vue';
import ProductCatalog from '../components/ProductCatalog.vue';

const { show } = useToast();
const catalog = ref(null);
const cart = ref({
    items: [],
    groups: [],
    count: 0,
    retail_total: 0,
    saving_total: 0,
    claim_total: 0,
    delivery_fee_total: 0,
    sku_limit: 5,
});
const loadError = ref('');
const submitting = ref(false);
const cartOpen = ref(false);
const fulfillment = ref('pickup');
const address = ref('');
const claimCart = reactive({
    count: computed(() => cart.value.count),
    openCart() {
        cartOpen.value = true;
    },
});

provide('claimCart', claimCart);
let cartQueue = Promise.resolve();

function enqueue(task) {
    const run = cartQueue.then(task, task);
    cartQueue = run.then(() => undefined, () => undefined);

    return run;
}

onMounted(async () => {
    try {
        const [catalogData, cartData] = await Promise.all([
            api.get('/api/catalog'),
            api.get('/api/cart'),
        ]);
        catalog.value = catalogData;
        cart.value = cartData;
    } catch (error) {
        loadError.value = error.message;
    }
});

function addProduct(product) {
    return enqueue(async () => {
        try {
            cart.value = await api.post('/api/cart/items', { product_id: product.id });
            show('Produk ditambahkan ke keranjang');
        } catch (error) {
            show(error.message);
        }
    });
}

function changeQty(item, delta) {
    const qty = item.qty + delta;

    return enqueue(async () => {
        try {
            cart.value = qty <= 0
                ? await api.delete(`/api/cart/items/${item.product_id}`)
                : await api.patch(`/api/cart/items/${item.product_id}`, { qty });
        } catch (error) {
            show(error.message);
        }
    });
}

function removeItem(item) {
    return enqueue(async () => {
        try {
            cart.value = await api.delete(`/api/cart/items/${item.product_id}`);
        } catch (error) {
            show(error.message);
        }
    });
}

function submitClaim() {
    if (!cart.value.count || submitting.value) {
        return;
    }

    submitting.value = true;

    return enqueue(async () => {
        try {
            const result = await api.post('/api/claims', {
                fulfillment: fulfillment.value,
                address: fulfillment.value === 'delivery' ? address.value : null,
            });
            cart.value = result.cart;
            cartOpen.value = false;
            show(result.message);
        } catch (error) {
            show(error.message);
        } finally {
            submitting.value = false;
        }
    });
}
</script>

<template>
    <div class="app-shell">
        <AppHeader />
        <div class="layout" :class="{ 'cart-open': cartOpen }">
            <main>
                <HeroBanner :discount="catalog?.max_discount_percent ?? 40" :sku-limit="catalog?.sku_limit ?? 5" />
                <ProductCatalog
                    v-if="catalog"
                    :brands="catalog.brands"
                    :sku-limit="catalog.sku_limit"
                    @add="addProduct"
                />
                <div v-else class="empty loading-note">{{ loadError || 'Memuat katalog...' }}</div>
            </main>
            <div class="cart-slot">
                <ClaimSidebar
                    v-model:fulfillment="fulfillment"
                    v-model:address="address"
                    :open="cartOpen"
                    :cart="cart"
                    :period="catalog?.period"
                    :warehouse-address="catalog?.warehouse_address || ''"
                    :submitting="submitting"
                    @close="cartOpen = false"
                    @change="changeQty"
                    @remove="removeItem"
                    @submit="submitClaim"
                />
            </div>
        </div>
    </div>
</template>
