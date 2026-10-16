<script setup>
import { computed, onMounted, ref } from 'vue';
import { adminBrandId, adminBrands, loadAdminBrands } from '../adminBrand';
import { api } from '../api';
import { rupiah } from '../format';
import { useToast } from '../toast';
import AdminShell from '../components/AdminShell.vue';
import ProductPhoto from '../components/ProductPhoto.vue';

const { show } = useToast();
const products = ref([]);
const drafts = ref({});
const discount = computed(() => adminBrands.value.find((brand) => brand.id === adminBrandId.value)?.discount_percent ?? 0);
const query = ref('');
const loading = ref(true);
const savingId = ref(null);

const filtered = computed(() => {
    const term = query.value.trim().toLowerCase();

    return products.value.filter((product) => product.brand_id === adminBrandId.value && product.name.toLowerCase().includes(term));
});

function claimPrice(product) {
    const retail = Number(drafts.value[product.id] ?? product.retail_price);

    return Math.round(retail * (100 - discount.value) / 100);
}

async function load() {
    const [, productList] = await Promise.all([
        loadAdminBrands(),
        api.get('/api/products'),
    ]);
    products.value = productList;
    drafts.value = Object.fromEntries(productList.map((product) => [product.id, product.retail_price]));
}

onMounted(async () => {
    try {
        await load();
    } catch (error) {
        show(error.message);
    } finally {
        loading.value = false;
    }
});

async function save(product) {
    savingId.value = product.id;

    try {
        const updated = await api.patch(`/api/products/${product.id}/price`, {
            retail_price: Number(drafts.value[product.id]),
        });
        products.value = products.value.map((item) => (item.id === product.id ? updated : item));
        drafts.value[product.id] = updated.retail_price;
        show('Harga disimpan');
    } catch (error) {
        show(error.message);
    } finally {
        savingId.value = null;
    }
}
</script>

<template>
    <AdminShell>
        <div class="admin-toolbar">
            <label class="search">
                <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="m16.5 16.5 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <input v-model="query" type="search" placeholder="Cari produk...">
            </label>
            <p v-if="!loading" class="currency-note">Semua harga dalam Rupiah (IDR). Diskon brand ini {{ discount }}%.</p>
        </div>
        <section class="table-card admin-layout">
            <div v-if="loading" class="empty">Memuat harga...</div>
            <div v-else-if="filtered.length === 0" class="empty">Produk tidak ditemukan.</div>
            <div v-else class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga retail</th>
                            <th>Harga klaim</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="product in filtered" :key="product.id">
                            <td>
                                <div class="product-cell">
                                    <ProductPhoto :src="product.image_url || ''" :alt="product.name" />
                                    <span>{{ product.name }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="money money-compact">
                                    <b>Rp</b>
                                    <input v-model="drafts[product.id]" type="number" min="0" :aria-label="`Harga ${product.name}`">
                                </div>
                            </td>
                            <td>{{ rupiah(claimPrice(product)) }}</td>
                            <td class="row-actions">
                                <button type="button" :disabled="savingId === product.id" @click="save(product)">{{ savingId === product.id ? 'Menyimpan...' : 'Simpan' }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AdminShell>
</template>
